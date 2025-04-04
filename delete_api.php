<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success'=>false, 'error'=>'未登入']);
    exit;
}

$username = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param('s', $username);
$stmt->execute();
$user_id = $stmt->get_result()->fetch_assoc()['id'];

$apiId = $_GET['id'] ?? null;
if (!$apiId) {
    echo json_encode(['success'=>false, 'error'=>'缺少ID']);
    exit;
}

// 檢查是否屬於此 user
$stmt = $conn->prepare("SELECT id FROM user_apis WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $apiId, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows===0) {
    echo json_encode(['success'=>false, 'error'=>'找不到或無權限']);
    exit;
}

// 執行刪除
$del = $conn->prepare("DELETE FROM user_apis WHERE id=?");
$del->bind_param('i', $apiId);
if ($del->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'error'=>'刪除失敗']);
}
