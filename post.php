<?php
// post.php
session_start();
require_once __DIR__ . '/config.php';

$pdo = get_pdo();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    exit('記事がありません。');
}

// 記事取得（カテゴリ名＋editor_mode も一緒に）
$stmt = $pdo->prepare(
    'SELECT p.id, p.title, p.body, p.created_at, p.editor_mode,
            c.name AS category_name
     FROM posts p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.id = :id'
);
$stmt->execute([':id' => $id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    exit('記事が見つかりません。');
}

// コメントの追加処理（誰でも投稿可）
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_author'], $_POST['comment_body'])) {
    $author = trim($_POST['comment_author']);
    $body   = trim($_POST['comment_body']);

    if ($author === '') {
        $errors[] = 'お名前を入力してください。';
    }
    if ($body === '') {
        $errors[] = 'コメント本文を入力してください。';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO comments (post_id, author, body, created_at)
             VALUES (:post_id, :author, :body, :created_at)'
        );
        $stmt->execute([
            ':post_id'    => $post['id'],
            ':author'     => $author,
            ':body'       => $body,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        header('Location: post.php?id=' . urlencode($post['id']));
        exit;
    }
}

// コメント一覧
$stmt = $pdo->prepare(
    'SELECT id, author, body, created_at
     FROM comments
     WHERE post_id = :post_id
     ORDER BY id DESC'
);
$stmt->execute([':post_id' => $post['id']]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$themeCss   = get_setting($pdo, 'theme_css', '/theme/default.css');
$editorMode = $post['editor_mode'] ?? 'simple';
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?> - WordBlog</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<h1><a href="index.php">WordBlog</a></h1>

<article>
    <h2><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
    <p>投稿日: <?php echo htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if (!empty($post['category_name'])): ?>
        <p>
            カテゴリ:
            <a href="category_list.php?name=<?php echo urlencode($post['category_name']); ?>">
                <?php echo htmlspecialchars($post['category_name'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </p>
    <?php endif; ?>

    <div>
        <?php if ($editorMode === 'html'): ?>
            <?php
            // HTML MODE: 保存されたHTMLをそのまま出力（信頼できる管理者専用）
            echo $post['body'];
            ?>
        <?php else: ?>
            <?php
            // SIMPLE MODE: テキストとして安全に表示
            echo nl2br(htmlspecialchars($post['body'], ENT_QUOTES, 'UTF-8'));
            ?>
        <?php endif; ?>
    </div>
</article>

<hr>

<section>
    <h3>コメント</h3>

    <?php if ($errors): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="post.php?id=<?php echo urlencode($post['id']); ?>">
        <div>
            <label>お名前:
                <input type="text" name="comment_author" size="40"
                       value="<?php echo htmlspecialchars($_POST['comment_author'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </div>
        <div>
            <label>コメント:<br>
                <textarea name="comment_body" rows="4" cols="60"><?php
                    echo htmlspecialchars($_POST['comment_body'] ?? '', ENT_QUOTES, 'UTF-8');
                ?></textarea>
            </label>
        </div>
        <div>
            <button type="submit">コメントを投稿</button>
        </div>
    </form>

    <hr>

    <?php if (!$comments): ?>
        <p>まだコメントはありません。</p>
    <?php else: ?>
        <?php foreach ($comments as $c): ?>
            <div style="margin-bottom:1em; border-bottom:1px solid #ccc; padding-bottom:0.5em;">
                <strong><?php echo htmlspecialchars($c['author'], ENT_QUOTES, 'UTF-8'); ?></strong>
                （<?php echo htmlspecialchars($c['created_at'], ENT_QUOTES, 'UTF-8'); ?>）<br>
                <?php echo nl2br(htmlspecialchars($c['body'], ENT_QUOTES, 'UTF-8')); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<p><a href="index.php">← 記事一覧に戻る</a></p>
</body>
</html>
