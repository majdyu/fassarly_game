<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/tournament.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$participantId = current_participant_id();
if (!$participantId) {
    json_response(401, ['success' => false, 'message' => 'يجب تسجيل الدخول قبل حفظ نتيجة البطولة.']);
}

$resultId = (int) ($_SESSION['tournament_result_id'] ?? 0);
$sessionLevel = (string) ($_SESSION['tournament_level'] ?? '');
if (empty($_SESSION['tournament_active']) || $resultId <= 0 || !array_key_exists($sessionLevel, LEVEL_LABELS)) {
    json_response(403, ['success' => false, 'message' => 'لم يتم بدء البطولة من المسار الصحيح.']);
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    json_response(400, ['success' => false, 'message' => 'Invalid payload.']);
}

$level = (string) ($payload['level'] ?? '');
$elapsedSeconds = (int) ($payload['elapsedSeconds'] ?? -1);
$attemptsCount = (int) ($payload['attemptsCount'] ?? -1);
$success = !empty($payload['success']) ? 1 : 0;
$randomNumber = (string) ($payload['randomNumber'] ?? '');
$isRandomMode = !empty($payload['isRandomMode']) ? 1 : 0;

if ($level !== $sessionLevel || $elapsedSeconds < 0 || $attemptsCount < 1 || $randomNumber === '') {
    json_response(422, ['success' => false, 'message' => 'بيانات النتيجة غير كاملة.']);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $lockClause = env_value('DB_DRIVER', 'mysql') === 'sqlite' ? '' : ' FOR UPDATE';
    $stmt = $pdo->prepare('SELECT * FROM tournament_level_results WHERE id = ? AND participant_id = ?' . $lockClause);
    $stmt->execute([$resultId, $participantId]);
    $result = $stmt->fetch();

    if (!$result || $result['status'] !== 'started') {
        $pdo->rollBack();
        json_response(409, ['success' => false, 'message' => 'هذه البطولة مسجلة من قبل.']);
    }

    $status = $success === 1 ? 'completed' : 'game_over';
    $update = $pdo->prepare(
        'UPDATE tournament_level_results
         SET status = ?, elapsed_seconds = ?, attempts_count = ?, success = ?, is_random_mode = ?, random_number = ?, finished_at = CURRENT_TIMESTAMP
         WHERE id = ?'
    );
    $update->execute([$status, $elapsedSeconds, $attemptsCount, $success, $isRandomMode, $randomNumber, $resultId]);

    clear_tournament_session();
    $finishedAll = participant_has_finished_all_levels($participantId);
    if ($finishedAll) {
        lock_participant_account($participantId);
    }

    $pdo->commit();

    json_response(200, [
        'success' => true,
        'finishedAll' => $finishedAll,
        'redirect' => $finishedAll ? '/participant/logout.php' : '/participant/mode.php',
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(500, ['success' => false, 'message' => 'تعذر حفظ نتيجة البطولة.']);
}
