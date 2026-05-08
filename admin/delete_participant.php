<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = db()->prepare('DELETE FROM participants WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['flash_success'] = 'Participant supprimé.';
    }
}

redirect_to('/admin/dashboard.php');
