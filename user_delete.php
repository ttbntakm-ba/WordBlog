<?php
// user_delete.php
session_start();
require_once __DIR__ . '/config.php';

$pdo = get_pdo();

// ログイン＆ロールチェック（admin のみ）
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id');
$stmt->execute([':id' => $currentUserId]);
$currentRole = $stmt->fetchColumn();

if ($currentRole !== 'admin') {
    http_response_code(403);
    exit('このページにアクセスする権限がありません。（adminのみ）');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 自分自身は削除させない
if ($id <= 0 || $id === $currentUserId) {
    header('Location: user_list.php');
    exit;
}

// 削除実行
$stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
$stmt->execute([':id' => $id]); // DELETE[web:222][web:358][web:361][web:369]

header('Location: user_list.php');
exit;
