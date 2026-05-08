<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function current_admin_id(): ?int
{
    return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
}

function current_participant_id(): ?int
{
    return isset($_SESSION['participant_id']) ? (int) $_SESSION['participant_id'] : null;
}

function require_admin(): void
{
    if (!current_admin_id()) {
        redirect_to('/admin/login.php');
    }
}

function require_participant(): void
{
    if (!current_participant_id()) {
        redirect_to('/participant/login.php');
    }
}

function participant(): ?array
{
    $id = current_participant_id();
    if (!$id) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM participants WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $participant = $stmt->fetch();

    return $participant ?: null;
}

function logout_all(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
