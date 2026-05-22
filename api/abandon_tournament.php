<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/tournament.php';

header('Content-Type: application/json; charset=utf-8');

function abandon_json_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abandon_json_response(405, ['success' => false]);
}

$participantId = current_participant_id();
$resultId = (int) ($_SESSION['tournament_result_id'] ?? 0);
if (!$participantId || $resultId <= 0 || empty($_SESSION['tournament_active'])) {
    abandon_json_response(200, ['success' => true]);
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    $payload = [];
}

$elapsedSeconds = TOURNAMENT_PENALTY_SECONDS;
$attemptsCount = max(1, (int) ($payload['attemptsCount'] ?? 9));
$randomNumber = (string) ($payload['randomNumber'] ?? '');

$pdo = db();

try {
    $pdo->beginTransaction();

    $lockClause = env_value('DB_DRIVER', 'mysql') === 'sqlite' ? '' : ' FOR UPDATE';
    $stmt = $pdo->prepare('SELECT * FROM tournament_level_results WHERE id = ? AND participant_id = ?' . $lockClause);
    $stmt->execute([$resultId, $participantId]);
    $result = $stmt->fetch();

    if ($result && $result['status'] === 'started') {
        $update = $pdo->prepare(
            'UPDATE tournament_level_results
             SET status = ?, elapsed_seconds = ?, attempts_count = ?, success = 0, random_number = ?, finished_at = CURRENT_TIMESTAMP
             WHERE id = ?'
        );
        $update->execute(['abandoned', $elapsedSeconds, $attemptsCount, $randomNumber, $resultId]);
    }

    lock_participant_account($participantId);
    clear_tournament_session();
    $_SESSION = [];

    $pdo->commit();
    abandon_json_response(200, ['success' => true, 'redirect' => '/participant/login.php']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    abandon_json_response(500, ['success' => false]);
}
