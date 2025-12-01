<?php
// category_form.php
session_start();
require_once __DIR__ . '/config.php';

$pdo = get_pdo();

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

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$name    = '';
$message = '';
$errors  = [];

// 編集時は既存カテゴリを読み込み
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id > 0) {
    $stmt = $pdo->prepare('SELECT id, name FROM categories WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cat) {
        $name = $cat['name'];
    } else {
        $message = '指定されたカテゴリが見つかりません。';
        $id = 0;
    }
}

// POST: 追加 or 更新
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $errors[] = 'カテゴリ名を入力してください。';
    } else {
        if ($id > 0) {
            // 重複チェック（自分以外）
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = :name AND id <> :id');
            $stmt->execute([':name' => $name, ':id' => $id]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors[] = '同じ名前のカテゴリがすでに存在します。';
            }
        } else {
            // 新規時
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = :name');
            $stmt->execute([':name' => $name]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors[] = '同じ名前のカテゴリがすでに存在します。';
            }
        }
    }

    if (empty($errors)) {
        if ($id > 0) {
            // 更新
            $stmt = $pdo->prepare('UPDATE categories SET name = :name WHERE id = :id');
            $stmt->execute([
                ':name' => $name,
                ':id'   => $id,
            ]);
            header('Location: category_list.php');
            exit;
        } else {
            // 追加
            $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (:name)');
            $stmt->execute([':name' => $name]);
            header('Location: category_list.php');
            exit;
        }
    }
}

$themeCss = get_setting($pdo, 'theme_css', '/theme/default.css');
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title><?php echo $id > 0 ? 'カテゴリ編集' : 'カテゴリ追加'; ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<h1><?php echo $id > 0 ? 'カテゴリ編集' : 'カテゴリ追加'; ?></h1>
<p><a href="category_list.php">← カテゴリ一覧へ戻る</a></p>

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

<form method="post" action="category_form.php<?php echo $id > 0 ? '?id=' . urlencode($id) : ''; ?>">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
    <div>
        <label>カテゴリ名:
            <input type="text" name="name" size="40"
                   value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </div>
    <div>
        <button type="submit"><?php echo $id > 0 ? '更新' : '追加'; ?></button>
    </div>
</form>

</body>
</html>
