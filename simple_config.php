<?php
// config.php
// ===== DB設定 =====

// SQLite を使う（必要なら 'mysql' に変更）
define('DB_DRIVER', 'sqlite');

// SQLite の場合
define('SQLITE_PATH', __DIR__ . '/data/blog.sqlite3');

// MySQL の設定（DB_DRIVER='mysql' にしたときに使用）
define('DB_HOST', 'localhost');
define('DB_NAME', 'myblog');
define('DB_USER', 'root');
define('DB_PASS', '');

// ===== PDO接続 =====
function get_pdo(): PDO
{
    if (DB_DRIVER === 'sqlite') {
        $dsn = 'sqlite:' . SQLITE_PATH;
        $pdo = new PDO($dsn);
    } elseif (DB_DRIVER === 'mysql') {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
    } else {
        throw new RuntimeException('Unsupported DB_DRIVER');
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

// ===== 設定テーブル用ヘルパー（テーマなど） =====
function get_setting(PDO $pdo, string $name, ?string $default = null): ?string
{
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE name = :name');
    $stmt->execute([':name' => $name]);
    $v = $stmt->fetchColumn();
    if ($v === false) {
        return $default;
    }
    return $v;
}

function set_setting(PDO $pdo, string $name, string $value): void
{
    if (DB_DRIVER === 'sqlite') {
        // SQLite 用 UPSERT
        $sql = 'INSERT INTO settings (name, value) VALUES (:name, :value)
                ON CONFLICT(name) DO UPDATE SET value = :value';
    } elseif (DB_DRIVER === 'mysql') {
        // MySQL 用 UPSERT（name に PRIMARY KEY or UNIQUE が必要）
        $sql = 'INSERT INTO settings (name, value) VALUES (:name, :value)
                ON DUPLICATE KEY UPDATE value = :value';
    } else {
        throw new RuntimeException('Unsupported DB_DRIVER for set_setting');
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'  => $name,
        ':value' => $value,
    ]);
}

// ===== ユーザーロール取得ヘルパー =====
function current_user_role(PDO $pdo): ?string
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id');
    $stmt->execute([':id' => (int)$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();
    if ($role === false) {
        return null;
    }
    return $role;
}
