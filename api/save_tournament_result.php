<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

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

if (empty($_SESSION['tournament_active'])) {
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

if (!array_key_exists($level, LEVEL_LABELS) || $elapsedSeconds < 0 || $attemptsCount < 1 || $randomNumber === '') {
    json_response(422, ['success' => false, 'message' => 'بيانات النتيجة غير كاملة.']);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM participants WHERE id = ? FOR UPDATE');
    $stmt->execute([$participantId]);
    $participant = $stmt->fetch();

    if (!$participant) {
        throw new RuntimeException('Participant not found.');
    }

    if ((int) $participant['has_participated'] === 1) {
        $pdo->rollBack();
        json_response(409, ['success' => false, 'message' => 'لقد تم تسجيل مشاركتك من قبل.']);
    }

    $insert = $pdo->prepare(
        'INSERT INTO tournament_results
            (participant_id, level, elapsed_seconds, attempts_count, success, is_random_mode, random_number)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $insert->execute([
        $participantId,
        $level,
        $elapsedSeconds,
        $attemptsCount,
        $success,
        $isRandomMode,
        $randomNumber,
    ]);

    $newPassword = random_code(5);
    $update = $pdo->prepare(
        'UPDATE participants
         SET has_participated = 1, plain_password = ?, password_hash = ?
         WHERE id = ?'
    );
    $update->execute([$newPassword, password_hash($newPassword, PASSWORD_DEFAULT), $participantId]);

    $pdo->commit();
    unset($_SESSION['tournament_active']);

    json_response(200, ['success' => true]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(500, ['success' => false, 'message' => 'تعذر حفظ نتيجة البطولة.']);
}
