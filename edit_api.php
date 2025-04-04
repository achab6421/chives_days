<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success'=>false,'error'=>'尚未登入']);
    exit;
}

$username = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param('s', $username);
$stmt->execute();
$user_id = $stmt->get_result()->fetch_assoc()['id'];

$apiId = $_GET['id'] ?? null;
if (!$apiId) {
    echo json_encode(['success'=>false, 'error'=>'缺少 id']);
    exit;
}

// 查這筆是否屬於此 user
$stmt = $conn->prepare("SELECT * FROM user_apis WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $apiId, $user_id);
$stmt->execute();
$api = $stmt->get_result()->fetch_assoc();
if (!$api) {
    echo json_encode(['success'=>false,'error'=>'找不到此API或無權限']);
    exit;
}

// 回傳 JSON
echo json_encode([
    'success'=>true,
    'data'=> [
      'id'=>$api['id'],
      'exchange_name'=>$api['exchange_name'],
      'api_key'=>$api['api_key'],
      'api_secret'=>$api['api_secret'],
      'api_passphrase'=>$api['api_passphrase'],
      'permission_level'=>$api['permission_level'],
      'usage_note'=>$api['usage_note']
    ]
]);
