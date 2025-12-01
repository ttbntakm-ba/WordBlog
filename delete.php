<?php
// delete.php
session_start();
require_once __DIR__ . '/config.php';

$pdo = get_pdo();

// ログインチェック
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// ロールチェック（admin のみ）
$role = current_user_role($pdo);
if ($role !== 'admin') {
    http_response_code(403);
    exit('この記事を削除する権限がありません。（adminのみ）');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

header('Location: admin.php');
exit;
