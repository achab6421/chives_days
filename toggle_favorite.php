<?php
session_start();
require_once 'db_connection.php'; // Include database connection

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$strategy_id = $data['strategy_id'] ?? null;

if (!$strategy_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid strategy ID']);
    exit;
}

// Check if the strategy is already a favorite
$stmt = $conn->prepare('SELECT id FROM favorites WHERE user_id = ? AND strategy_id = ?');
$stmt->bind_param('ii', $user_id, $strategy_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Remove from favorites
    $stmt = $conn->prepare('DELETE FROM favorites WHERE user_id = ? AND strategy_id = ?');
    $stmt->bind_param('ii', $user_id, $strategy_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'is_favorite' => false]);
} else {
    // Add to favorites
    $stmt = $conn->prepare('INSERT INTO favorites (user_id, strategy_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $user_id, $strategy_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'is_favorite' => true]);
}
exit;
?>
