<?php
// category_delete.php
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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: category_list.php');
    exit;
}

// このカテゴリを使っている記事の category_id を NULL に
$stmt = $pdo->prepare('UPDATE posts SET category_id = NULL WHERE category_id = :id');
$stmt->execute([':id' => $id]);

// カテゴリ削除
$stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
$stmt->execute([':id' => $id]);

header('Location: category_list.php');
exit;
