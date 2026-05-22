<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function require_game_access(): void
{
    $participant = participant();

    if (!$participant) {
        redirect_to('/participant/login.php');
    }

    if ((int) $participant['has_completed_profile'] !== 1) {
        redirect_to('/participant/profile.php');
    }

    $mode = $_GET['mode'] ?? ($_SESSION['play_mode'] ?? null);
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $pageLevels = [
        'verification_easy.php' => 'easy',
        'verification_medium.php' => 'medium',
        'verification_hard.php' => 'hard',
    ];

    if ($mode === 'training') {
        unset($_SESSION['tournament_active']);
        $_SESSION['play_mode'] = 'training';
        return;
    }

    if ($mode === 'tournament') {
        if ((int) ($participant['account_locked'] ?? 0) === 1 || empty($_SESSION['tournament_active'])) {
            redirect_to('/participant/mode.php');
        }

        $expectedLevel = $_SESSION['tournament_level'] ?? null;
        if (isset($pageLevels[$currentPage]) && $pageLevels[$currentPage] !== $expectedLevel) {
            redirect_to('/participant/mode.php');
        }

        $_SESSION['play_mode'] = 'tournament';
        return;
    }

    redirect_to('/participant/mode.php');
}

require_game_access();
