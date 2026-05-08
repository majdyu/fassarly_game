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
    if ($mode === 'training') {
        unset($_SESSION['tournament_active']);
        $_SESSION['play_mode'] = 'training';
        return;
    }

    if ($mode === 'tournament') {
        if ((int) $participant['has_participated'] === 1 || empty($_SESSION['tournament_active'])) {
            redirect_to('/participant/mode.php');
        }
        $_SESSION['play_mode'] = 'tournament';
        return;
    }

    redirect_to('/participant/mode.php');
}

require_game_access();
