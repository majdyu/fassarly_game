<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_admin();

function generate_unique_login(): string
{
    do {
        $login = 'P' . random_int(100000, 999999);
        $stmt = db()->prepare('SELECT id FROM participants WHERE login = ? LIMIT 1');
        $stmt->execute([$login]);
    } while ($stmt->fetch());

    return $login;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');

    if ($firstName === '' || $lastName === '') {
        $_SESSION['flash_error'] = 'Le nom et le prénom sont obligatoires.';
        redirect_to('/admin/dashboard.php');
    }

    $login = generate_unique_login();
    $plainPassword = random_code(4);
    $stmt = db()->prepare(
        'INSERT INTO participants
            (login, password_hash, plain_password, first_name, last_name, has_completed_profile)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    $stmt->execute([$login, password_hash($plainPassword, PASSWORD_DEFAULT), $plainPassword, $firstName, $lastName]);
    $_SESSION['flash_success'] = "Compte participant généré : {$login}";
}

redirect_to('/admin/dashboard.php');
