<?php
session_start();

// config.php が無ければセットアップへリダイレクト
if (!file_exists(__DIR__ . '/config.php')) {
    header('Location: setup/setup.php');
    exit;
}

require_once __DIR__ . '/config.php';

$pdo = get_pdo();

// 検索キーワード
$keyword = trim($_GET['q'] ?? '');

// 1ページあたりの件数
$perPage = 10;

// 現在ページ (?page=1,2,...)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$params = [];
$where  = '';

// タイトル or 本文の LIKE 検索
if ($keyword !== '') {
    $where = 'WHERE (title LIKE :kw OR body LIKE :kw)';
    $params[':kw'] = '%' . $keyword . '%';
}

// 総件数取得
$sqlCount = 'SELECT COUNT(*) FROM posts ' . $where;
$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// 一覧データ取得（新しい順）
$sqlList = 'SELECT id, title, body, created_at
            FROM posts
            ' . $where . '
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset';
$stmt = $pdo->prepare($sqlList);

// LIKE パラメータ
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
// LIMIT/OFFSET は整数でバインド
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// テーマCSS
$themeCss = get_setting($pdo, 'theme_css', '/theme/default.css');
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>WordBlog</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<h1>WordBlog</h1>

<?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
    <p><a href="admin.php">管理画面に戻る</a></p>
<?php else: ?>
    <!-- <p><a href="login.php">管理画面にログイン</a></p> -->
<?php endif; ?>

<!-- 検索フォーム -->
<form method="get" action="index.php">
    <input type="text" name="q" size="40"
           value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
           placeholder="検索キーワードを入力（タイトル・本文）">
    <button type="submit">検索</button>
</form>
<hr>

<?php if ($keyword !== ''): ?>
    <p><a href="index.php">ホームに戻る</a></p>
    <p>「<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>」の検索結果: <?php echo $total; ?> 件</p>
<?php endif; ?>

<!--<?php 
echo '<p>DEBUG: total posts = ' . (int)$total . '</p>';
?>-->

<?php if (!$posts): ?>
<p>記事がありません。</p>
<?php else: ?>
<?php foreach ($posts as $post): ?>
<article>
    <h2>
        <a href="post.php?id=<?php echo urlencode($post['id']); ?>">
            <?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
    </h2>
    <p>
        <?php
        // 本文の先頭10文字だけ抜粋表示
        $snippet = mb_substr($post['body'], 0, 10);
        echo nl2br(htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8'));
        ?>
        <?php if (mb_strlen($post['body']) > 10): ?>...<?php endif; ?>
    </p>
    <p>投稿日: <?php echo htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>
</article>
<hr>
<?php endforeach; ?>

<!-- ページネーション（検索キーワードも維持） -->
<p>
<?php if ($page > 1): ?>
    <a href="?q=<?php echo urlencode($keyword); ?>&page=<?php echo $page - 1; ?>">« 前へ</a>
<?php endif; ?>

 ページ <?php echo $page; ?> / <?php echo $totalPages; ?>

<?php if ($page < $totalPages): ?>
    <a href="?q=<?php echo urlencode($keyword); ?>&page=<?php echo $page + 1; ?>">次へ »</a>
<?php endif; ?>
</p>
<?php endif; ?>

</body>
</html>
