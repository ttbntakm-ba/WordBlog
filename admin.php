<?php
session_start();
require_once __DIR__ . '/config.php';

// ログインチェック
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();
$currentUserRole = current_user_role($pdo) ?? 'viewer';

// 現在のテーマCSS取得
$themeCss = get_setting($pdo, 'theme_css', '/theme/default.css');

// テーマ変更処理（admin だけ）
if ($currentUserRole === 'admin'
    && $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['theme_css'])) {
    $newTheme = trim($_POST['theme_css']);
    if ($newTheme !== '') {
        set_setting($pdo, 'theme_css', $newTheme);
        $themeCss = $newTheme;
    }
}

// 記事一覧取得
$stmt = $pdo->query('SELECT id, title, created_at FROM posts ORDER BY id DESC');
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>WordBlog 管理画面</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<h1>WordBlog 管理画面</h1>
<p>
    ログインユーザー:
    <?php echo htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
    （ロール: <?php echo htmlspecialchars($currentUserRole, ENT_QUOTES, 'UTF-8'); ?>）
</p>

<?php if ($currentUserRole === 'admin'): ?>
<h2>テーマ設定</h2>
<form method="post" action="admin.php">
    <p>現在のテーマCSS: <?php echo htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8'); ?></p>
    <p>
        プリセット:
        <select name="preset" onchange="document.getElementById('theme_css').value=this.value;">
            <option value="">--選択してください--</option>
            <option value="/theme/default.css">/theme/default.css</option>
            <option value="/theme/dark.css">/theme/dark.css</option>
        </select>
    </p>
    <p>
        直接URLまたはパスを入力:<br>
        <input type="text" name="theme_css" id="theme_css" size="60"
               value="<?php echo htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8'); ?>"><br>
        例: /theme/a.css, /theme/b.css, /theme/aa.css, http://127.0.0.1/theme/custom.css
    </p>
    <p>
        <button type="submit">テーマを変更</button>
    </p>
</form>
<?php endif; ?>

<hr>

<ul>
    <?php if (in_array($currentUserRole, ['admin', 'editor'], true)): ?>
        <li><a href="article_form.php">新規記事作成</a></li>
    <?php endif; ?>

    <?php if ($currentUserRole === 'admin'): ?>
        <li><a href="user_list.php">ユーザーを管理</a></li>
        <li><a href="theme_edit.php">テーマCSSを編集</a></li>
		<li><a href="category_list.php">カテゴリを管理</a></li>
    <?php endif; ?>
</ul>

<h2>記事一覧（編集・削除）</h2>
<?php if (!$posts): ?>
<p>まだ記事がありません。</p>
<?php else: ?>
<ul>
<?php foreach ($posts as $post): ?>
    <li>
        [<?php echo htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8'); ?>]
        <?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>
        （<?php echo htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8'); ?>）

        <?php if (in_array($currentUserRole, ['admin', 'editor'], true)): ?>
            - <a href="article_form.php?id=<?php echo urlencode($post['id']); ?>">編集</a>
        <?php endif; ?>

        <?php if ($currentUserRole === 'admin'): ?>
            - <a href="delete.php?id=<?php echo urlencode($post['id']); ?>"
                 onclick="return confirm('本当に削除しますか？');">削除</a>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<p><a href="index.php">サイトを表示</a></p>
<p><a href="logout.php">ログアウト</a></p>
</body>
</html>
