<?php
// category.php
session_start();
require_once __DIR__ . '/config.php';

$pdo = get_pdo();

$catId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($catId <= 0) {
    http_response_code(404);
    exit('カテゴリが指定されていません。');
}

// カテゴリ情報取得
$stmt = $pdo->prepare('SELECT id, name FROM categories WHERE id = :id');
$stmt->execute([':id' => $catId]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$category) {
    http_response_code(404);
    exit('カテゴリが見つかりません。');
}

// 記事一覧（カテゴリ指定）
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
    <title><?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?> - WordBlog</title>
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

<p><a href="index.php">← 一覧に戻る</a></p>
</body>
</html>
