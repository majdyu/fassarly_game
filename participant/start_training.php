<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_participant();
unset($_SESSION['tournament_active']);
$_SESSION['play_mode'] = 'training';
redirect_to('/game.php?mode=training');
