<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

// 若尚未登入，直接回傳失敗
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => '尚未登入']);
    exit;
}

// 接收前端送來的 JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['strategy_id'])) {
    echo json_encode(['success' => false, 'error' => '缺少 strategy_id']);
    exit;
}

$strategy_id = (int) $data['strategy_id'];

// 驗證此筆策略紀錄是否屬於當前登入者
$username = $_SESSION['user'];
$stmt = $conn->prepare("
    SELECT s.id
    FROM strategies s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ? AND u.username = ?
    LIMIT 1
");
$stmt->bind_param('is', $strategy_id, $username);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => '無權限刪除此紀錄']);
    exit;
}

// 真的刪除
$del = $conn->prepare("DELETE FROM strategies WHERE id = ?");
$del->bind_param('i', $strategy_id);
if ($del->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => '刪除失敗']);
}
