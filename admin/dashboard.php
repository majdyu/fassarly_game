<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/layout.php';

require_admin();

$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$notPlayedStmt = db()->query(
    'SELECT * FROM participants WHERE has_participated = 0 ORDER BY created_at DESC'
);
$notPlayedParticipants = $notPlayedStmt->fetchAll();

function ranking_for_level(string $level): array
{
    $sql = "
        SELECT ranked.*
        FROM (
            SELECT
                p.id,
                p.login,
                p.plain_password,
                p.phone,
                p.last_name,
                p.first_name,
                p.has_logged_in,
                p.has_participated,
                r.level,
                r.elapsed_seconds,
                r.attempts_count,
                r.success,
                r.is_random_mode,
                r.random_number,
                DENSE_RANK() OVER (
                    PARTITION BY r.level
                    ORDER BY r.elapsed_seconds ASC, r.attempts_count ASC
                ) AS rank_position
            FROM tournament_results r
            INNER JOIN participants p ON p.id = r.participant_id
            WHERE r.level = ?
        ) ranked
        ORDER BY ranked.rank_position ASC, ranked.elapsed_seconds ASC, ranked.attempts_count ASC, ranked.login ASC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$level]);
    return $stmt->fetchAll();
}

function login_status_label(array $participant): string
{
    return (int) $participant['has_logged_in'] === 1 ? 'A connecté' : 'Jamais connecté';
}

function participation_status_label(array $participant): string
{
    return (int) $participant['has_participated'] === 1 ? 'Déjà participé' : 'Pas encore participé';
}

render_header('Dashboard admin');
?>
<section class="panel topbar">
    <div>
        <h1>Dashboard admin</h1>
        <p class="muted">Gestion des participants et classement du tournoi.</p>
    </div>
    <div class="actions">
        <form method="post" action="/admin/generate_participant.php">
            <button type="submit" class="button-secondary">Générer</button>
        </form>
        <a class="button" href="/admin/logout.php">Déconnexion</a>
    </div>
</section>

<?php if ($flashSuccess): ?>
    <div class="message message-success"><?= e($flashSuccess) ?></div>
<?php endif; ?>

<section class="panel">
    <h2>Participants - Pas encore participé</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Identifiant</th>
                    <th>Mot de passe</th>
                    <th>Connexion</th>
                    <th>Participation</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$notPlayedParticipants): ?>
                    <tr><td colspan="5">Aucun participant en attente.</td></tr>
                <?php endif; ?>
                <?php foreach ($notPlayedParticipants as $participant): ?>
                    <tr>
                        <td class="code"><?= e($participant['login']) ?></td>
                        <td class="code"><?= e($participant['plain_password']) ?></td>
                        <td><span class="status-pill <?= (int) $participant['has_logged_in'] === 1 ? 'status-ok' : 'status-warn' ?>"><?= e(login_status_label($participant)) ?></span></td>
                        <td><span class="status-pill status-warn"><?= e(participation_status_label($participant)) ?></span></td>
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

<?php foreach (LEVEL_LABELS as $level => $label): ?>
    <?php $rows = ranking_for_level($level); ?>
    <section class="panel">
        <h2>Classement du niveau <?= e($label) ?></h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Classement</th>
                        <th>Identifiant</th>
                        <th>Mot de passe</th>
                        <th>Connexion</th>
                        <th>Participation</th>
                        <th>Téléphone</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Temps</th>
                        <th>Tentatives</th>
                        <th>Réussite</th>
                        <th>Nombre généré</th>
                        <th>Niveau</th>
                        <th>Mode aléatoire</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="15">Aucun résultat pour ce niveau.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= (int) $row['rank_position'] ?></td>
                            <td class="code"><?= e($row['login']) ?></td>
                            <td class="code"><?= e($row['plain_password']) ?></td>
                            <td><span class="status-pill status-ok"><?= e(login_status_label($row)) ?></span></td>
                            <td><span class="status-pill status-ok"><?= e(participation_status_label($row)) ?></span></td>
                            <td><?= e($row['phone']) ?></td>
                            <td><?= e($row['last_name']) ?></td>
                            <td><?= e($row['first_name']) ?></td>
                            <td><?= (int) $row['elapsed_seconds'] ?> s</td>
                            <td><?= (int) $row['attempts_count'] ?></td>
                            <td><?= (int) $row['success'] === 1 ? 'Oui' : 'Non' ?></td>
                            <td class="code"><?= e($row['random_number']) ?></td>
                            <td><?= e(level_label($row['level'])) ?></td>
                            <td><?= (int) $row['is_random_mode'] === 1 ? 'Oui' : 'Non' ?></td>
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
<?php endforeach; ?>
<?php render_footer(); ?>
