<?php
// user_list.php
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

// ユーザー一覧取得（id 昇順）
$stmt = $pdo->query('SELECT id, username, role FROM users ORDER BY id ASC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// テーマCSS（任意）
$themeCss = get_setting($pdo, 'theme_css', '/theme/default.css');
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>ユーザー管理</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<h1>ユーザー管理</h1>
<p><a href="admin.php">← 管理画面へ戻る</a></p>

<p><a href="user_form.php">＋ ユーザーを追加</a></p>

<?php if (!$users): ?>
<p>ユーザーがいません。</p>
<?php else: ?>
<table border="1" cellpadding="4" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>ユーザー名</th>
        <th>ロール</th>
        <th>操作</th>
    </tr>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <a href="user_form.php?id=<?php echo urlencode($user['id']); ?>">編集</a>
                <?php if ((int)$user['id'] !== $currentUserId): // 自分自身は削除不可 ?>
                    |
                    <a href="user_delete.php?id=<?php echo urlencode($user['id']); ?>"
                       onclick="return confirm('本当にこのユーザーを削除しますか？');">
                        削除
                    </a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</body>
</html>
