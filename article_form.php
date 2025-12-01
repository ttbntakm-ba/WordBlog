<?php
// article_form.php
session_start();
require_once __DIR__ . '/config.php';

$pdo = get_pdo();

// ログインチェック
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// ロールチェック（admin / editor のみ）
$role = current_user_role($pdo);
if (!in_array($role, ['admin', 'editor'], true)) {
    http_response_code(403);
    exit('この記事を編集する権限がありません。');
}

// カテゴリ一覧取得（なければ空配列になるだけ）
$stmt = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC');
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$id          = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title       = '';
$body        = '';
$categoryId  = null;
$message     = '';
$editor_mode = 'simple'; // 'simple' or 'html'

// GET: 編集時の読み込み
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id > 0) {
    $stmt = $pdo->prepare('SELECT id, title, body, category_id, editor_mode FROM posts WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($post) {
        $title       = $post['title'];
        $body        = $post['body'];
        $categoryId  = $post['category_id'];
        $editor_mode = $post['editor_mode'] ?: 'simple';
    } else {
        $message = '指定された記事が見つかりません。';
        $id = 0;
    }
}

// POST: 保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title       = $_POST['title'] ?? '';
    $body        = $_POST['body'] ?? '';
    $editor_mode = $_POST['editor_mode'] ?? 'simple';
    $categoryId  = isset($_POST['category_id']) && $_POST['category_id'] !== ''
        ? (int)$_POST['category_id']
        : null;

    if ($title === '' || $body === '') {
        $message = 'タイトルと本文を入力してください。';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE posts
                 SET title = :title, body = :body, category_id = :category_id, editor_mode = :editor_mode
                 WHERE id = :id'
            );
            $stmt->execute([
                ':title'       => $title,
                ':body'        => $body,
                ':category_id' => $categoryId,
                ':editor_mode' => $editor_mode,
                ':id'          => $id,
            ]);
            $message = '記事を更新しました。';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO posts (title, body, created_at, category_id, editor_mode)
                 VALUES (:title, :body, :created_at, :category_id, :editor_mode)'
            );
            $stmt->execute([
                ':title'       => $title,
                ':body'        => $body,
                ':created_at'  => date('Y-m-d H:i:s'),
                ':category_id' => $categoryId,
                ':editor_mode' => $editor_mode,
            ]);
            $message = '記事を新規作成しました。';
            $id = (int)$pdo->lastInsertId();
        }
    }
}

$themeCss = get_setting($pdo, 'theme_css', '/theme/default.css');
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title><?php echo $id > 0 ? '記事編集' : '記事入力'; ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<h1><?php echo $id > 0 ? '記事編集' : '新規記事入力'; ?></h1>
<p><a href="admin.php">← 管理画面へ戻る</a></p>

<?php if ($message): ?>
<p style="color:green;"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="article_form.php<?php echo $id > 0 ? '?id=' . urlencode($id) : ''; ?>">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">

    <div>
        <label>タイトル:
            <input type="text" name="title" size="60"
                   value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </div>

    <div>
        <label>カテゴリ:
            <select name="category_id">
                <option value="">（未分類）</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>"
                        <?php if ((int)$categoryId === (int)$cat['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <hr>

    <div>
        <label>
            エディターモード:
            <select name="editor_mode">
                <option value="simple" <?php if ($editor_mode === 'simple') echo 'selected'; ?>>
                    シンプルモード（テキスト）
                </option>
                <option value="html" <?php if ($editor_mode === 'html') echo 'selected'; ?>>
                    HTMLモード（html,bodyタグはなしでよし,HTMLを自分で書く）
                </option>
            </select>
        </label>
    </div>

    <div>
        <label>本文:<br>
            <textarea name="body" rows="15" cols="80"><?php
                echo htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
            ?></textarea>
        </label>
    </div>

    <div>
        <button type="submit"><?php echo $id > 0 ? '更新' : '保存'; ?></button>
    </div>
</form>
</body>
</html>
