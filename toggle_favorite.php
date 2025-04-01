<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$strategy_id = $data['strategy_id'] ?? null;

if (!$strategy_id) {
    echo json_encode(['success' => false, 'message' => '缺少策略 ID']);
    exit;
}

$stmt = $conn->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ? AND strategy_id = ?');
$stmt->bind_param('ii', $user_id, $strategy_id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    $stmt = $conn->prepare('DELETE FROM favorites WHERE user_id = ? AND strategy_id = ?');
    $stmt->bind_param('ii', $user_id, $strategy_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'is_favorite' => false]);
} else {
    $stmt = $conn->prepare('INSERT INTO favorites (user_id, strategy_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $user_id, $strategy_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'is_favorite' => true]);
}
?>
