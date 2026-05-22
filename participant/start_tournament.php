<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/tournament.php';

require_participant();
$participant = participant();

if (!$participant || (int) ($participant['account_locked'] ?? 0) === 1) {
    logout_participant();
    redirect_to('/participant/login.php');
}

$level = $_POST['level'] ?? '';
if (!array_key_exists($level, LEVEL_LABELS)) {
    $_SESSION['mode_error'] = 'مستوى البطولة غير صالح.';
    redirect_to('/participant/mode.php');
}

$results = participant_level_results((int) $participant['id']);
if (is_terminal_result($results[$level] ?? null)) {
    $_SESSION['mode_error'] = 'لقد لعبت هذه البطولة من قبل.';
    redirect_to('/participant/mode.php');
}

$pdo = db();
$existing = $results[$level] ?? null;

if ($existing && $existing['status'] === 'started') {
    $resultId = (int) $existing['id'];
} else {
    $stmt = $pdo->prepare(
        'INSERT INTO tournament_level_results (participant_id, level, status, started_at)
         VALUES (?, ?, ?, CURRENT_TIMESTAMP)'
    );
    $stmt->execute([(int) $participant['id'], $level, 'started']);
    $resultId = (int) $pdo->lastInsertId();
}

$_SESSION['play_mode'] = 'tournament';
$_SESSION['tournament_active'] = true;
$_SESSION['tournament_level'] = $level;
$_SESSION['tournament_result_id'] = $resultId;

redirect_to('/game.php?mode=tournament&level=' . urlencode($level));
