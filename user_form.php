<?php
// user_form.php
session_start();
require_once __DIR__ . '/config.php';

$pdo = get_pdo();

// ログイン＆ロールチェック（admin のみ）
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id');
$stmt->execute([':id' => $currentUserId]);
$currentRole = $stmt->fetchColumn();

if ($currentRole !== 'admin') {
    http_response_code(403);
    exit('このページにアクセスする権限がありません。（adminのみ）');
}

// 編集対象ID
$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$errors  = [];
$username = '';
$role     = 'editor'; // デフォルトロール
$password = '';       // フォーム入力用（空なら変更なし）

// 編集時は既存ユーザーを読み込み
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id > 0) {
    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $username = $user['username'];
        $role     = $user['role'];
    } else {
        $message = '指定されたユーザーが見つかりません。';
        $id = 0;
    }
}

// POST時（追加・更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $username = trim($_POST['username'] ?? '');
    $role     = trim($_POST['role'] ?? 'editor');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        $errors[] = 'ユーザー名を入力してください。';
    }

    // 許可ロール（必要に応じて追加）
    $allowedRoles = ['admin', 'editor', 'viewer', 'commenter']; // [web:352][web:368]
    if (!in_array($role, $allowedRoles, true)) {
        $errors[] = 'ロールの値が不正です。';
    }

    if (empty($errors)) {
        if ($id > 0) {
            // 既存ユーザー更新
            // ユーザー名重複チェック（自分以外）
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u AND id <> :id');
            $stmt->execute([':u' => $username, ':id' => $id]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors[] = '同じユーザー名がすでに存在します。';
            } else {
                if ($password !== '') {
                    // パスワード変更あり
                    $hash = password_hash($password, PASSWORD_DEFAULT); // [web:337][web:340][web:345][web:380]
                    $stmt = $pdo->prepare(
                        'UPDATE users SET username = :u, role = :r, password_hash = :p WHERE id = :id'
                    );
                    $stmt->execute([
                        ':u' => $username,
                        ':r' => $role,
                        ':p' => $hash,
                        ':id'=> $id,
                    ]);
                } else {
                    // パスワード変更なし
                    $stmt = $pdo->prepare(
                        'UPDATE users SET username = :u, role = :r WHERE id = :id'
                    );
                    $stmt->execute([
                        ':u' => $username,
                        ':r' => $role,
                        ':id'=> $id,
                    ]);
                }
                header('Location: user_list.php');
                exit;
            }
        } else {
            // 新規ユーザー追加
            // ユーザー名重複チェック
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u');
            $stmt->execute([':u' => $username]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors[] = '同じユーザー名がすでに存在します。';
            } elseif ($password === '') {
                $errors[] = '新規ユーザーにはパスワードを入力してください。';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT); // [web:337][web:340][web:345][web:380]
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, password_hash, role) VALUES (:u, :p, :r)'
                );
                $stmt->execute([
                    ':u' => $username,
                    ':p' => $hash,
                    ':r' => $role,
                ]);
                header('Location: user_list.php');
                exit;
            }
        }
    }
}

// テーマCSS
$themeCss = get_setting($pdo, 'theme_css', '/theme/default.css');
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title><?php echo $id > 0 ? 'ユーザー編集' : 'ユーザー追加'; ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<h1><?php echo $id > 0 ? 'ユーザー編集' : 'ユーザー追加'; ?></h1>
<p><a href="user_list.php">← ユーザー一覧に戻る</a></p>

<?php if ($message): ?>
<p style="color:green;"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($errors): ?>
<ul style="color:red;">
    <?php foreach ($errors as $e): ?>
        <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<form method="post" action="user_form.php<?php echo $id > 0 ? '?id=' . urlencode($id) : ''; ?>">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">

    <div>
        <label>ユーザー名:
            <input type="text" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </div>

    <div>
        <label>ロール:
            <select name="role">
                <option value="admin" <?php if ($role === 'admin') echo 'selected'; ?>>admin（管理者）</option>
                <option value="editor" <?php if ($role === 'editor') echo 'selected'; ?>>editor（変更者）</option>
                <option value="viewer" <?php if ($role === 'viewer') echo 'selected'; ?>>viewer（閲覧者）</option>
                <option value="commenter" <?php if ($role === 'commenter') echo 'selected'; ?>>commenter（コメント）</option>
            </select>
        </label>
    </div>

    <div>
        <label>パスワード:
            <input type="password" name="password">
        </label>
        <?php if ($id > 0): ?>
            <small>※ 空のままならパスワードは変更しません。</small>
        <?php endif; ?>
    </div>

    <div>
        <button type="submit"><?php echo $id > 0 ? '更新' : '追加'; ?></button>
    </div>
</form>
</body>
</html>
