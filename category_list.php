<?php
// category_list.php
session_start();
require_once __DIR__ . '/config.php';

$pdo = get_pdo();

// 1) 一般公開用: ?name=カテゴリ名 が指定されている場合は
//    「そのカテゴリの記事一覧」として動作させる
if (isset($_GET['name']) && $_GET['name'] !== '') {
    $name = trim($_GET['name']);

    // カテゴリ取得
    $stmt = $pdo->prepare('SELECT id, name FROM categories WHERE name = :name');
    $stmt->execute([':name' => $name]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        http_response_code(404);
        exit('指定されたカテゴリが見つかりません。');
    }

    // 該当カテゴリの記事一覧
    $stmt = $pdo->prepare(
        'SELECT id, title, body, created_at
         FROM posts
         WHERE category_id = :cat_id
         ORDER BY id DESC'
    );
    $stmt->execute([':cat_id' => $category['id']]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $themeCss = get_setting($pdo, 'theme_css', '/theme/default.css');
    ?>
    <!doctype html>
    <html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>カテゴリ: <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?> - WordBlog</title>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8'); ?>">
    </head>
    <body>
    <h1><a href="index.php">WordBlog</a></h1>
    <h2>カテゴリ: <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></h2>

    <?php if (!$posts): ?>
        <p>このカテゴリにはまだ記事がありません。</p>
    <?php else: ?>
        <ul>
            <?php foreach ($posts as $post): ?>
                <li>
                    <a href="post.php?id=<?php echo urlencode($post['id']); ?>">
                        <?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    （<?php echo htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8'); ?>）
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p><a href="index.php">← Home に戻る</a></p>
    </body>
    </html>
    <?php
    exit;
}

// ログイン＆ロールチェック（admin のみ）
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$role = current_user_role($pdo);
if ($role !== 'admin') {
    http_response_code(403);
    exit('カテゴリを管理する権限がありません。（adminのみ）');
}

// 一覧取得
$stmt = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC');
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$themeCss = get_setting($pdo, 'theme_css', '/theme/default.css');
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>カテゴリ管理</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<h1>カテゴリ管理</h1>
<p><a href="admin.php">← 管理画面へ戻る</a></p>

<p><a href="category_form.php">＋ カテゴリを追加</a></p>

<?php if (!$categories): ?>
    <p>カテゴリがありません。</p>
<?php else: ?>
    <table border="1" cellpadding="4" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>カテゴリ名</th>
            <th>操作</th>
        </tr>
        <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?php echo (int)$cat['id']; ?></td>
                <td><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <a href="category_form.php?id=<?php echo urlencode($cat['id']); ?>">編集</a>
                    |
                    <a href="category_delete.php?id=<?php echo urlencode($cat['id']); ?>"
                       onclick="return confirm('このカテゴリを削除しますか？\n※このカテゴリを使っている記事の category_id は NULL になります。');">
                        削除
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>
