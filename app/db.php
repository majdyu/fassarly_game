<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $driver = env_value('DB_DRIVER', 'mysql');

    if ($driver === 'sqlite') {
        $path = env_value('SQLITE_PATH', __DIR__ . '/../data/fassarly.sqlite');
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $dsn = "sqlite:{$path}";
        $user = null;
        $pass = null;
    } else {
        $host = env_value('DB_HOST', '127.0.0.1');
        $name = env_value('DB_NAME', 'fassarly_game');
        $user = env_value('DB_USER', 'fassarly_user');
        $pass = env_value('DB_PASS', 'fassarly_pass');
        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function bootstrap_database(): void
{
    static $bootstrapped = false;

    if ($bootstrapped) {
        return;
    }

    $pdo = db();
    $schemaFile = env_value('DB_DRIVER', 'mysql') === 'sqlite' ? 'schema.sqlite.sql' : 'schema.sql';
    $schema = file_get_contents(__DIR__ . '/' . $schemaFile);
    if ($schema === false) {
        throw new RuntimeException('Unable to read database schema.');
    }

    $pdo->exec($schema);
    migrate_database($pdo);

    $adminUsername = env_value('ADMIN_USERNAME', 'admin');
    $adminPassword = env_value('ADMIN_PASSWORD', 'admin123');
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$adminUsername]);
    $admin = $stmt->fetch();

    if (!$admin) {
        $insert = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
        $insert->execute([$adminUsername, password_hash($adminPassword, PASSWORD_DEFAULT)]);
    } else {
        $check = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ? LIMIT 1');
        $check->execute([(int) $admin['id']]);
        $existing = $check->fetch();

        if ($existing && !password_verify($adminPassword, $existing['password_hash'])) {
            $update = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
            $update->execute([password_hash($adminPassword, PASSWORD_DEFAULT), (int) $admin['id']]);
        }
    }

    $bootstrapped = true;
}

bootstrap_database();

function migrate_database(PDO $pdo): void
{
    $driver = env_value('DB_DRIVER', 'mysql');

    if ($driver === 'sqlite') {
        $columns = $pdo->query("PRAGMA table_info(participants)")->fetchAll();
        $columnNames = array_column($columns, 'name');
        if (!in_array('account_locked', $columnNames, true)) {
            $pdo->exec('ALTER TABLE participants ADD COLUMN account_locked INTEGER NOT NULL DEFAULT 0');
        }
        return;
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM participants LIKE 'account_locked'");
    if (!$stmt->fetch()) {
        $pdo->exec('ALTER TABLE participants ADD COLUMN account_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER has_participated');
    }
}
