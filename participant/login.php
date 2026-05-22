<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/layout.php';

if (current_participant_id()) {
    redirect_to('/participant/mode.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM participants WHERE login = ? LIMIT 1');
    $stmt->execute([$login]);
    $participant = $stmt->fetch();

    if ($participant && (int) ($participant['account_locked'] ?? 0) === 1) {
        $error = 'هذا الحساب لم يعد متاحاً.';
    } elseif ($participant && password_verify($password, $participant['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['participant_id'] = (int) $participant['id'];
        db()->prepare('UPDATE participants SET has_logged_in = 1 WHERE id = ?')->execute([(int) $participant['id']]);
        redirect_to('/participant/mode.php');
    } else {
        $error = 'Identifiant ou mot de passe incorrect.';
    }
}

render_header('Connexion participant', 'rtl');
?>
<section class="panel narrow">
    <div class="participant-logo-wrap">
        <img src="/logo.jpg" alt="Fassarly" class="participant-logo">
    </div>
    <h1>تسجيل الدخول</h1>
    <p class="muted">استعمل المعرف وكلمة المرور المقدمة لك.</p>
    <?php if ($error): ?>
        <div class="message message-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="login">المعرف</label>
        <input id="login" name="login" required dir="ltr">

        <label for="password">كلمة المرور</label>
        <input id="password" name="password" type="password" required dir="ltr">

        <p><button type="submit">دخول</button></p>
    </form>
</section>
<?php render_footer(); ?>
