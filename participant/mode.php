<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/tournament.php';
require_once __DIR__ . '/../app/layout.php';

require_participant();
$participant = participant();

if (!$participant || (int) ($participant['account_locked'] ?? 0) === 1) {
    logout_all();
    redirect_to('/participant/login.php');
}

if (abandon_started_results_and_lock((int) $participant['id'])) {
    logout_all();
    redirect_to('/participant/login.php');
}

$modeError = $_SESSION['mode_error'] ?? '';
unset($_SESSION['mode_error']);

$results = participant_level_results((int) $participant['id']);

render_header('Choix du mode', 'rtl');
?>
<section class="panel topbar">
    <div>
        <img src="/logo.jpg" alt="Fassarly" class="participant-logo participant-logo-inline">
        <h1>اختيار نمط اللعب</h1>
        <p class="muted">مرحباً <?= e($participant['first_name']) ?>، تابع تقدمك واختر البطولة التي تريد لعبها.</p>
    </div>
    <a class="button" href="/participant/logout.php">تسجيل الخروج</a>
</section>

<?php if ($modeError): ?>
    <div class="message message-error"><?= e($modeError) ?></div>
<?php endif; ?>

<section class="grid">
    <article class="panel mode-card">
        <h2>التدريب</h2>
        <p class="muted">اللعبة تعمل بشكل عادي. هذا النمط غير مصنف ولا يتم حفظ نتيجته.</p>
        <a class="button button-secondary" href="/participant/start_training.php">بدء التدريب</a>
    </article>

    <?php foreach (LEVEL_LABELS as $level => $label): ?>
        <?php
            $result = $results[$level] ?? null;
            $isPlayed = is_terminal_result($result);
            $isStarted = $result && $result['status'] === 'started';
        ?>
        <article class="panel mode-card">
            <h2>بطولة <?= e($label) ?></h2>
            <p><span class="status-pill <?= $isPlayed ? 'status-ok' : 'status-warn' ?>"><?= e(status_label($result['status'] ?? null)) ?></span></p>
            <?php if ($result && $result['elapsed_seconds'] !== null): ?>
                <p class="muted">الوقت: <?= e(format_duration((int) $result['elapsed_seconds'])) ?> | المحاولات: <?= (int) $result['attempts_count'] ?></p>
            <?php endif; ?>

            <?php if ($isPlayed): ?>
                <button type="button" disabled>تم اللعب</button>
            <?php else: ?>
                <form method="post" action="/participant/start_tournament.php" onsubmit="return confirm('هل أنت متأكد؟ لديك محاولة واحدة فقط في هذه البطولة.');">
                    <input type="hidden" name="level" value="<?= e($level) ?>">
                    <button type="submit"><?= $isStarted ? 'متابعة البطولة' : 'بدء البطولة' ?></button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
<?php render_footer(); ?>
