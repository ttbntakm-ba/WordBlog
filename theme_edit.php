<?php
// theme_edit.php
session_start();
require_once __DIR__ . '/config.php';

$pdo = get_pdo();

// ログインチェック
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// ロールチェック（admin のみテーマ編集可）
$role = current_user_role($pdo);
if ($role !== 'admin') {
    http_response_code(403);
    exit('テーマCSSを編集する権限がありません。（adminのみ）');
}

// 現在のテーマCSSパス
$themeCss = get_setting($pdo, 'theme_css', '/theme/default.css');

// 編集対象ファイルを決める
$docRoot = dirname(__FILE__);

// http から始まる場合などは書き込み対象外にする（安全のため）
if (preg_match('#^https?://#i', $themeCss)) {
    $fileEditable = false;
    $filePath = '';
} else {
    $fileEditable = true;
    if (strpos($themeCss, '/theme/') === 0) {
        $filePath = $docRoot . $themeCss;
    } else {
        $filePath = $docRoot . '/' . ltrim($themeCss, '/');
    }
}

$message = '';
$errors  = [];

// POST: CSS保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['css_content'])) {
    if (!$fileEditable || $filePath === '') {
        $errors[] = 'このテーマはローカルファイルではないため、直接編集できません。';
    } else {
        $content = $_POST['css_content'];

        // ディレクトリが無ければ作成
        if (!is_dir(dirname($filePath))) {
            if (!mkdir(dirname($filePath), 0777, true) && !is_dir(dirname($filePath))) {
                $errors[] = 'CSSディレクトリの作成に失敗しました。パーミッションを確認してください。';
            }
        }

        if (empty($errors)) {
            if (file_put_contents($filePath, $content) === false) {
                $errors[] = 'CSSファイルの保存に失敗しました。パーミッションを確認してください。';
            } else {
                $message = 'CSSを保存しました。';
            }
        }
    }
}

// 現在のCSS内容を読み込み
$currentCss = '';
if ($fileEditable && $filePath !== '' && file_exists($filePath)) {
    $currentCss = file_get_contents($filePath);
}

$themeList = [
    '/theme/default.css' => 'default.css',
    '/theme/dark.css'    => 'dark.css',
    $themeCss            => '現在のテーマ (' . $themeCss . ')',
];

$themeCssEsc = htmlspecialchars($themeCss, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>テーマCSS編集</title>
</head>
<body>
<h1>テーマCSS編集</h1>
<p><a href="admin.php">← 管理画面へ戻る</a></p>

<p>現在のテーマCSS: <?php echo $themeCssEsc; ?></p>

<?php if (!$fileEditable): ?>
    <p style="color:red;">
        現在のテーマは URL (http...) のため、ここから直接編集できません。<br>
        /theme/xxx.css のようなローカルパスをテーマに設定すると編集できます。
    </p>
<?php else: ?>
    <p>編集対象ファイル: <?php echo htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<h2>別テーマCSSを選んで編集したい場合</h2>
<form method="get" action="theme_edit.php">
    <select name="file">
        <?php foreach ($themeList as $path => $label): ?>
            <option value="<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">このCSSを開く（テーマ設定とは別）</button>
</form>

<?php
// ?file= で別CSSを明示的に指定された場合、そのファイルを編集対象にする
if (isset($_GET['file']) && $_GET['file'] !== '') {
    $path = $_GET['file'];

    if (!preg_match('#^https?://#i', $path)) {
        if (strpos($path, '/theme/') === 0) {
            $filePath = $docRoot . $path;
        } else {
            $filePath = $docRoot . '/' . ltrim($path, '/');
        }
        $fileEditable = true;
        $currentCss   = file_exists($filePath) ? file_get_contents($filePath) : '';
        $themeCssEsc  = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
        echo '<p>編集対象CSSを変更: ' . $themeCssEsc . '</p>';
    } else {
        echo '<p style="color:red;">URL のCSSは編集対象にできません。</p>';
    }
}
?>

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

<?php if ($fileEditable && $filePath !== ''): ?>
    <form method="post" action="theme_edit.php<?php
        echo isset($_GET['file']) ? '?file=' . urlencode($_GET['file']) : '';
    ?>">
        <p>CSS 内容:</p>
        <textarea name="css_content" rows="25" cols="80"><?php
            echo htmlspecialchars($currentCss, ENT_QUOTES, 'UTF-8');
        ?></textarea><br>
        <button type="submit">CSSを保存</button>
    </form>
<?php endif; ?>

</body>
</html>
