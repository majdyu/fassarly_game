<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function participant_level_results(int $participantId): array
{
    $stmt = db()->prepare('SELECT * FROM tournament_level_results WHERE participant_id = ?');
    $stmt->execute([$participantId]);
    $results = [];

    foreach ($stmt->fetchAll() as $row) {
        $results[$row['level']] = $row;
    }

    return $results;
}

function terminal_statuses(): array
{
    return ['completed', 'game_over', 'abandoned'];
}

function is_terminal_result(?array $result): bool
{
    return $result && in_array($result['status'], terminal_statuses(), true);
}

function participant_has_finished_all_levels(int $participantId): bool
{
    $results = participant_level_results($participantId);

    foreach (array_keys(LEVEL_LABELS) as $level) {
        if (!is_terminal_result($results[$level] ?? null)) {
            return false;
        }
    }

    return true;
}

function lock_participant_account(int $participantId): void
{
    $newPassword = random_code(5);
    $stmt = db()->prepare(
        'UPDATE participants
         SET account_locked = 1, has_participated = 1, plain_password = ?, password_hash = ?
         WHERE id = ?'
    );
    $stmt->execute([$newPassword, password_hash($newPassword, PASSWORD_DEFAULT), $participantId]);
}

function abandon_started_results_and_lock(int $participantId): bool
{
    $stmt = db()->prepare(
        "SELECT id FROM tournament_level_results WHERE participant_id = ? AND status = 'started' LIMIT 1"
    );
    $stmt->execute([$participantId]);

    if (!$stmt->fetch()) {
        return false;
    }

    $update = db()->prepare(
        "UPDATE tournament_level_results
         SET status = 'abandoned',
             elapsed_seconds = COALESCE(elapsed_seconds, 0),
             attempts_count = COALESCE(attempts_count, 9),
             success = 0,
             finished_at = CURRENT_TIMESTAMP
         WHERE participant_id = ? AND status = 'started'"
    );
    $update->execute([$participantId]);
    lock_participant_account($participantId);
    clear_tournament_session();

    return true;
}

function clear_tournament_session(): void
{
    unset($_SESSION['tournament_active'], $_SESSION['tournament_level'], $_SESSION['tournament_result_id']);
}
