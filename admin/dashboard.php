<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/tournament.php';
require_once __DIR__ . '/../app/layout.php';

require_admin();

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

function login_status_label(array $participant): string
{
    return (int) $participant['has_logged_in'] === 1 ? 'A connecté' : 'Jamais connecté';
}

function account_status_label(array $participant): string
{
    return (int) ($participant['account_locked'] ?? 0) === 1 ? 'Compte terminé' : 'Actif';
}

function ranking_rows(): array
{
    $sql = "
        SELECT
            p.id,
            p.login,
            p.plain_password,
            p.phone,
            p.first_name,
            p.last_name,
            p.has_logged_in,
            p.account_locked,
            easy.elapsed_seconds AS easy_time,
            medium.elapsed_seconds AS medium_time,
            hard.elapsed_seconds AS hard_time,
            easy.attempts_count AS easy_attempts,
            medium.attempts_count AS medium_attempts,
            hard.attempts_count AS hard_attempts,
            easy.status AS easy_status,
            medium.status AS medium_status,
            hard.status AS hard_status,
            (COALESCE(easy.elapsed_seconds, 0) + COALESCE(medium.elapsed_seconds, 0) + COALESCE(hard.elapsed_seconds, 0)) AS total_time,
            (COALESCE(easy.attempts_count, 0) + COALESCE(medium.attempts_count, 0) + COALESCE(hard.attempts_count, 0)) AS total_attempts,
            DENSE_RANK() OVER (
                ORDER BY
                    (COALESCE(easy.elapsed_seconds, 0) + COALESCE(medium.elapsed_seconds, 0) + COALESCE(hard.elapsed_seconds, 0)) ASC,
                    (COALESCE(easy.attempts_count, 0) + COALESCE(medium.attempts_count, 0) + COALESCE(hard.attempts_count, 0)) ASC
            ) AS rank_position
        FROM participants p
        INNER JOIN tournament_level_results easy
            ON easy.participant_id = p.id AND easy.level = 'easy' AND easy.status IN ('completed', 'game_over', 'abandoned')
        INNER JOIN tournament_level_results medium
            ON medium.participant_id = p.id AND medium.level = 'medium' AND medium.status IN ('completed', 'game_over', 'abandoned')
        INNER JOIN tournament_level_results hard
            ON hard.participant_id = p.id AND hard.level = 'hard' AND hard.status IN ('completed', 'game_over', 'abandoned')
        ORDER BY rank_position ASC, total_time ASC, total_attempts ASC, p.login ASC
    ";

    return db()->query($sql)->fetchAll();
}

function progression_rows(): array
{
    $sql = "
        SELECT
            p.*,
            easy.status AS easy_status,
            medium.status AS medium_status,
            hard.status AS hard_status
        FROM participants p
        LEFT JOIN tournament_level_results easy ON easy.participant_id = p.id AND easy.level = 'easy'
        LEFT JOIN tournament_level_results medium ON medium.participant_id = p.id AND medium.level = 'medium'
        LEFT JOIN tournament_level_results hard ON hard.participant_id = p.id AND hard.level = 'hard'
        WHERE NOT (
            COALESCE(easy.status, '') IN ('completed', 'game_over', 'abandoned')
            AND COALESCE(medium.status, '') IN ('completed', 'game_over', 'abandoned')
            AND COALESCE(hard.status, '') IN ('completed', 'game_over', 'abandoned')
        )
        ORDER BY p.created_at DESC
    ";

    return db()->query($sql)->fetchAll();
}

$rankedParticipants = ranking_rows();
$progressionParticipants = progression_rows();

render_header('Dashboard admin');
?>
<section class="panel topbar">
    <div>
        <h1>Dashboard admin</h1>
        <p class="muted">Gestion des participants et classement global des 3 tournois.</p>
    </div>
    <a class="button" href="/admin/logout.php">Déconnexion</a>
</section>

<?php if ($flashSuccess): ?>
    <div class="message message-success"><?= e($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="message message-error"><?= e($flashError) ?></div>
<?php endif; ?>

<section class="panel">
    <h2>Générer un participant</h2>
    <form method="post" action="/admin/generate_participant.php" class="inline-form">
        <label for="first_name">Prénom</label>
        <input id="first_name" name="first_name" required>

        <label for="last_name">Nom</label>
        <input id="last_name" name="last_name" required>

        <button type="submit" class="button-secondary">Générer</button>
    </form>
</section>

<section class="panel">
    <h2>Classement global</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Participant</th>
                    <th>Téléphone</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Temps facile</th>
                    <th>Temps moyen</th>
                    <th>Temps difficile</th>
                    <th>Temps total</th>
                    <th>Tentative facile</th>
                    <th>Tentative moyen</th>
                    <th>Tentative difficile</th>
                    <th>Somme tentatives</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rankedParticipants): ?>
                    <tr><td colspan="14">Aucun participant classé pour le moment.</td></tr>
                <?php endif; ?>
                <?php foreach ($rankedParticipants as $row): ?>
                    <tr>
                        <td><?= (int) $row['rank_position'] ?></td>
                        <td class="code"><?= e($row['login']) ?></td>
                        <td><?= e($row['phone'] ?: '-') ?></td>
                        <td><?= e($row['last_name']) ?></td>
                        <td><?= e($row['first_name']) ?></td>
                        <td><?= e(format_duration((int) $row['easy_time'])) ?></td>
                        <td><?= e(format_duration((int) $row['medium_time'])) ?></td>
                        <td><?= e(format_duration((int) $row['hard_time'])) ?></td>
                        <td><?= e(format_duration((int) $row['total_time'])) ?></td>
                        <td><?= (int) $row['easy_attempts'] ?></td>
                        <td><?= (int) $row['medium_attempts'] ?></td>
                        <td><?= (int) $row['hard_attempts'] ?></td>
                        <td><?= (int) $row['total_attempts'] ?></td>
                        <td>
                            <form method="post" action="/admin/delete_participant.php" onsubmit="return confirm('Supprimer ce participant ?');">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="button-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2>Progression non classée</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Identifiant</th>
                    <th>Mot de passe</th>
                    <th>Connexion</th>
                    <th>Compte</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>سهل</th>
                    <th>متوسط</th>
                    <th>صعب</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$progressionParticipants): ?>
                    <tr><td colspan="10">Tous les participants sont classés.</td></tr>
                <?php endif; ?>
                <?php foreach ($progressionParticipants as $participant): ?>
                    <tr>
                        <td class="code"><?= e($participant['login']) ?></td>
                        <td class="code"><?= e($participant['plain_password']) ?></td>
                        <td><span class="status-pill <?= (int) $participant['has_logged_in'] === 1 ? 'status-ok' : 'status-warn' ?>"><?= e(login_status_label($participant)) ?></span></td>
                        <td><span class="status-pill <?= (int) ($participant['account_locked'] ?? 0) === 1 ? 'status-warn' : 'status-ok' ?>"><?= e(account_status_label($participant)) ?></span></td>
                        <td><?= e($participant['last_name']) ?></td>
                        <td><?= e($participant['first_name']) ?></td>
                        <td><?= e(status_label($participant['easy_status'])) ?></td>
                        <td><?= e(status_label($participant['medium_status'])) ?></td>
                        <td><?= e(status_label($participant['hard_status'])) ?></td>
                        <td>
                            <form method="post" action="/admin/delete_participant.php" onsubmit="return confirm('Supprimer ce participant ?');">
                                <input type="hidden" name="id" value="<?= (int) $participant['id'] ?>">
                                <button type="submit" class="button-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_footer(); ?>
