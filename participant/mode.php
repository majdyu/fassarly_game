<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/layout.php';

require_participant();
$participant = participant();

if (!$participant) {
    logout_all();
    redirect_to('/participant/login.php');
}

if ((int) $participant['has_completed_profile'] !== 1) {
    redirect_to('/participant/profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'start_tournament') {
    if ((int) $participant['has_participated'] === 1) {
        $_SESSION['mode_error'] = 'لقد شاركت في البطولة من قبل.';
        redirect_to('/participant/mode.php');
    }

    $_SESSION['tournament_active'] = true;
    $_SESSION['play_mode'] = 'tournament';
    redirect_to('/index.html?mode=tournament');
}

$modeError = $_SESSION['mode_error'] ?? '';
unset($_SESSION['mode_error']);

render_header('Choix du mode', 'rtl');
?>
<section class="panel topbar">
    <div>
        <img src="/logo.jpg" alt="Fassarly" class="participant-logo participant-logo-inline">
        <h1>اختيار نمط اللعب</h1>
        <p class="muted">مرحباً <?= e($participant['first_name']) ?>، اختر كيف تريد اللعب.</p>
    </div>
    <a class="button" href="/participant/logout.php">تسجيل الخروج</a>
</section>

<?php if ($modeError): ?>
    <div class="message message-error"><?= e($modeError) ?></div>
<?php endif; ?>

<section class="grid">
    <article class="panel mode-card">
        <h2>التدريب</h2>
        <p class="muted">اللعبة تعمل بشكل عادي. هذا النمط غير مصنف ولا يتم حفظ نتيجته في البطولة.</p>
        <a class="button button-secondary" href="/participant/start_training.php">بدء التدريب</a>
    </article>

    <article class="panel mode-card">
        <h2>بدء البطولة</h2>
        <?php if ((int) $participant['has_participated'] === 1): ?>
            <p class="message message-error">لقد لعبت محاولة البطولة الخاصة بك من قبل.</p>
        <?php else: ?>
            <p class="muted">لديك محاولة واحدة فقط في البطولة. سيتم تسجيل النتيجة عند نهاية اللعب.</p>
            <form method="post" onsubmit="return confirm('هل أنت متأكد؟ لديك محاولة واحدة فقط في البطولة.');">
                <input type="hidden" name="action" value="start_tournament">
                <button type="submit">بدء البطولة</button>
            </form>
        <?php endif; ?>
    </article>
</section>
<?php render_footer(); ?>
