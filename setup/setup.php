<?php
// setup/setup.php

session_start();

// プロジェクトルート
$root    = dirname(__DIR__);
$simple  = $root . '/simple_config.php';
$config  = $root . '/config.php';

$errors  = [];
$message = '';

// =======================================
// 1) config.php が無い場合 → インストールフォーム
//    （DB設定＋最初の管理ユーザー）
// =======================================
if (!file_exists($config)) {
    if (!file_exists($simple)) {
        exit('simple-config.php が見つかりません。プロジェクト直下に simple-config.php を置いてください。');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // DB 設定
        $db_driver = $_POST['db_driver'] ?? 'sqlite';   // 'sqlite' or 'mysql'
        $db_host   = trim($_POST['db_host']   ?? 'localhost');
        $db_name   = trim($_POST['db_name']   ?? 'myblog');
        $db_user   = trim($_POST['db_user']   ?? 'root');
        $db_pass   = trim($_POST['db_pass']   ?? '');

        // 最初のユーザー
        $admin_user = trim($_POST['admin_user'] ?? '');
        $admin_pass = trim($_POST['admin_pass'] ?? '');

        if ($db_driver !== 'sqlite' && $db_driver !== 'mysql') {
            $errors[] = 'DB種別が不正です。';
        }

        if ($db_driver === 'mysql') {
            if ($db_host === '' || $db_name === '' || $db_user === '') {
                $errors[] = 'MySQL を使う場合は host / database / user を入力してください。';
            }
        }

        if ($admin_user === '' || $admin_pass === '') {
            $errors[] = '最初のユーザー名とパスワードを入力してください。';
        }

        if (empty($errors)) {
            // simple-config.php をベースに config.php を生成
            $configSrc = file_get_contents($simple);
            if ($configSrc === false) {
                $errors[] = 'simple-config.php の読み込みに失敗しました。';
            } else {
                // DB_DRIVER
                $configSrc = preg_replace(
                    "/define\\('DB_DRIVER',\\s*'[^']*'\\);/",
                    "define('DB_DRIVER', '" . $db_driver . "');",
                    $configSrc
                );
                // MySQL パラメータ
                $configSrc = preg_replace(
                    "/define\\('DB_HOST',\\s*'[^']*'\\);/",
                    "define('DB_HOST', '" . addslashes($db_host) . "');",
                    $configSrc
                );
                $configSrc = preg_replace(
                    "/define\\('DB_NAME',\\s*'[^']*'\\);/",
                    "define('DB_NAME', '" . addslashes($db_name) . "');",
                    $configSrc
                );
                $configSrc = preg_replace(
                    "/define\\('DB_USER',\\s*'[^']*'\\);/",
                    "define('DB_USER', '" . addslashes($db_user) . "');",
                    $configSrc
                );
                $configSrc = preg_replace(
                    "/define\\('DB_PASS',\\s*'[^']*'\\);/",
                    "define('DB_PASS', '" . addslashes($db_pass) . "');",
                    $configSrc
                );

                if (file_put_contents($config, $configSrc) === false) {
                    $errors[] = 'config.php の書き込みに失敗しました。パーミッションを確認してください。';
                } else {
                    // 一時的に admin_user / admin_pass をセッションに保存
                    $_SESSION['setup_admin_user'] = $admin_user;
                    $_SESSION['setup_admin_pass'] = $admin_pass;

                    header('Location: setup.php');
                    exit;
                }
            }
        }
    }

    // フォーム表示
    ?>
    <!doctype html>
    <html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>WordBlog セットアップ - DB & ユーザー設定</title>
    </head>
    <body>
    <h1>WordBlog セットアップ - DB & ユーザー設定</h1>

    <?php if ($errors): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="setup.php">
        <h2>データベース種別</h2>
        <p>
            <label>
                <input type="radio" name="db_driver" value="sqlite"
                    <?php echo (($_POST['db_driver'] ?? 'sqlite') === 'sqlite') ? 'checked' : ''; ?>>
                SQLite（簡単・ファイルベース）
            </label><br>
            <label>
                <input type="radio" name="db_driver" value="mysql"
                    <?php echo (($_POST['db_driver'] ?? '') === 'mysql') ? 'checked' : ''; ?>>
                MySQL / MariaDB
            </label>
        </p>

        <h2>MySQL の場合の接続情報</h2>
        <p>SQLite を使う場合は、そのままでも構いません。</p>
        <p>
            ホスト名: <input type="text" name="db_host" size="40"
                        value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost', ENT_QUOTES, 'UTF-8'); ?>"><br>
            データベース名: <input type="text" name="db_name" size="40"
                        value="<?php echo htmlspecialchars($_POST['db_name'] ?? 'myblog', ENT_QUOTES, 'UTF-8'); ?>"><br>
            ユーザー名: <input type="text" name="db_user" size="40"
                        value="<?php echo htmlspecialchars($_POST['db_user'] ?? 'root', ENT_QUOTES, 'UTF-8'); ?>"><br>
            パスワード: <input type="password" name="db_pass" size="40"
                        value="<?php echo htmlspecialchars($_POST['db_pass'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </p>

        <h2>最初の管理ユーザー</h2>
        <p>
            ユーザー名: <input type="text" name="admin_user" size="30"
                        value="<?php echo htmlspecialchars($_POST['admin_user'] ?? 'admin', ENT_QUOTES, 'UTF-8'); ?>"><br>
            パスワード: <input type="password" name="admin_pass" size="30"
                        value="<?php echo htmlspecialchars($_POST['admin_pass'] ?? 'admin', ENT_QUOTES, 'UTF-8'); ?>">
        </p>

        <p>
            <button type="submit">設定を保存してセットアップを続行</button>
        </p>
    </form>

    </body>
    </html>
    <?php
    exit;
}

// =======================================
// 2) config.php がある → テーブル作成など
// =======================================

require_once $config;
$pdo = get_pdo();

// posts テーブル
if (DB_DRIVER === 'sqlite') {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    created_at TEXT NOT NULL
);
SQL;
} else {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
}
$pdo->exec($sql);

// posts に editor_mode カラムを追加（無ければ）
try {
    if (DB_DRIVER === 'sqlite') {
        $pdo->exec("ALTER TABLE posts ADD COLUMN editor_mode TEXT DEFAULT 'simple';");
    } else {
        $pdo->exec("ALTER TABLE posts ADD COLUMN editor_mode VARCHAR(20) NOT NULL DEFAULT 'simple';");
    }
} catch (Exception $e) {
    // 既に存在する場合などは無視
}

// settings テーブル
if (DB_DRIVER === 'sqlite') {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS settings (
    name TEXT PRIMARY KEY,
    value TEXT NOT NULL
);
SQL;
} else {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS settings (
    name VARCHAR(191) NOT NULL PRIMARY KEY,
    value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
}
$pdo->exec($sql);

// 初期テーマ
$stmt = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE name = :name');
$stmt->execute([':name' => 'theme_css']);
if ((int)$stmt->fetchColumn() === 0) {
    $stmt = $pdo->prepare('INSERT INTO settings (name, value) VALUES (:name, :value)');
    $stmt->execute([
        ':name'  => 'theme_css',
        ':value' => '/theme/default.css',
    ]);
}

// users テーブル
if (DB_DRIVER === 'sqlite') {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL
);
SQL;
} else {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
}
$pdo->exec($sql);

// comments テーブル
if (DB_DRIVER === 'sqlite') {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    author TEXT NOT NULL,
    body TEXT NOT NULL,
    created_at TEXT NOT NULL
);
SQL;
} else {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    author VARCHAR(191) NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_comments_post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
}
$pdo->exec($sql);

// categories テーブル
if (DB_DRIVER === 'sqlite') {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);
SQL;
} else {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
}
$pdo->exec($sql);

// posts に category_id カラムを追加（無ければ）
try {
    if (DB_DRIVER === 'sqlite') {
        $pdo->exec("ALTER TABLE posts ADD COLUMN category_id INTEGER DEFAULT NULL;");
    } else {
        $pdo->exec("ALTER TABLE posts ADD COLUMN category_id INT NULL;");
    }
} catch (Exception $e) {
    // 既に存在する場合などは無視
}

// デフォルトカテゴリ
$stmt = $pdo->query('SELECT COUNT(*) FROM categories');
if ((int)$stmt->fetchColumn() === 0) {
    $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (:name)');
    $stmt->execute([':name' => 'General']);
}

// 最初の管理ユーザー
$stmt = $pdo->query('SELECT COUNT(*) FROM users');
if ((int)$stmt->fetchColumn() === 0) {
    $admin_user = $_SESSION['setup_admin_user'] ?? 'admin';
    $admin_pass = $_SESSION['setup_admin_pass'] ?? 'admin';

    $hash = password_hash($admin_pass, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash, role) VALUES (:u, :p, :r)'
    );
    $stmt->execute([
        ':u' => $admin_user,
        ':p' => $hash,
        ':r' => 'admin',
    ]);

    unset($_SESSION['setup_admin_user'], $_SESSION['setup_admin_pass']);
}

// セットアップ完了後はトップへ
header('Location: ../');
exit;
