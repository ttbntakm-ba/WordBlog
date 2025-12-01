<?php
// login.php
session_start();
require_once __DIR__ . '/config.php';

$pdo   = get_pdo();
$error = '';

// すでにログイン済みなら管理画面へ
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

// ログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === '' || $pass === '') {
        $error = 'ユーザー名とパスワードを入力してください。';
    } else {
        // users テーブルからユーザーを検索
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :u');
        $stmt->execute([':u' => $user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // パスワード検証（password_hash / password_verify）[web:337][web:340][web:343][web:351]
        if ($row && password_verify($pass, $row['password_hash'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username']  = $row['username'];
            $_SESSION['user_id']   = $row['id'];

            header('Location: admin.php');
            exit;
        } else {
            $error = 'ユーザー名またはパスワードが違います。';
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>ログイン</title>
</head>
<body>
<h1>ログイン</h1>

<p><a href="index.php">← ホームに戻る</a></p>

<?php if ($error): ?>
<p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="login.php">
    <div>
        <label>ユーザー名:
            <input type="text" name="username" value="">
        </label>
    </div>
    <div>
        <label>パスワード:
            <input type="password" name="password" value="">
        </label>
    </div>
    <div>
        <button type="submit">ログイン</button>
    </div>
</form>
</body>
</html>
