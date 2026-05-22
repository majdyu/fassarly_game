<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/layout.php';

require_participant();
$participant = participant();

if (!$participant) {
    logout_participant();
    redirect_to('/participant/login.php');
}

if ((int) $participant['has_completed_profile'] === 1) {
    redirect_to('/participant/mode.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');

    if ($phone === '' || $lastName === '' || $firstName === '') {
        $error = 'كل المعلومات مطلوبة.';
    } else {
        $stmt = db()->prepare(
            'UPDATE participants
             SET phone = ?, last_name = ?, first_name = ?, has_completed_profile = 1
             WHERE id = ?'
        );
        $stmt->execute([$phone, $lastName, $firstName, (int) $participant['id']]);
        redirect_to('/participant/mode.php');
    }
}

render_header('Informations participant', 'rtl');
?>
<section class="panel narrow">
    <div class="participant-logo-wrap">
        <img src="/logo.jpg" alt="Fassarly" class="participant-logo">
    </div>
    <h1>معلوماتك الشخصية</h1>
    <p class="muted">املأ هذه المعلومات مرة واحدة قبل اللعب.</p>
    <?php if ($error): ?>
        <div class="message message-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="phone">الهاتف</label>
        <input id="phone" name="phone" required>

        <label for="first_name">الاسم</label>
        <input id="first_name" name="first_name" required>

        <label for="last_name">اللقب</label>
        <input id="last_name" name="last_name" required>

        <p><button type="submit">حفظ ومتابعة</button></p>
    </form>
</section>
<?php render_footer(); ?>
