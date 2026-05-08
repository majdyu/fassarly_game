<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/layout.php';

if (current_admin_id()) {
    redirect_to('/admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        redirect_to('/admin/dashboard.php');
    }

    $error = 'Identifiants admin incorrects.';
}

render_header('Connexion admin');
?>
<section class="panel narrow">
    <h1>Connexion admin</h1>
    <p class="muted">Espace de gestion du tournoi Fassarly.</p>
    <?php if ($error): ?>
        <div class="message message-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="username">Identifiant</label>
        <input id="username" name="username" required>

        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password" required>

        <p><button type="submit">Se connecter</button></p>
    </form>
</section>
<?php render_footer(); ?>
