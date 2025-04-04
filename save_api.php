<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success'=>false, 'error'=>'未登入']);
    exit;
}

$username = $_SESSION['user'];
// 查 user_id
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param('s', $username);
$stmt->execute();
$user_id = $stmt->get_result()->fetch_assoc()['id'];

// 取得表單
$apiId = $_POST['api_id'] ?? '';
$exchange_name = $_POST['exchange_name'] ?? '';
$api_key = $_POST['api_key'] ?? '';
$api_secret = $_POST['api_secret'] ?? '';
$api_passphrase = $_POST['api_passphrase'] ?? '';
$permission_level = $_POST['permission_level'] ?? '';
$usage_note = $_POST['usage_note'] ?? '';

if ($apiId !== '') {
    // 編輯模式
    // 先查是否有權限
    $chk = $conn->prepare("SELECT id FROM user_apis WHERE id=? AND user_id=?");
    $chk->bind_param('ii', $apiId, $user_id);
    $chk->execute();
    if ($chk->get_result()->num_rows===0) {
        echo json_encode(['success'=>false, 'error'=>'無此API或無權限']);
        exit;
    }

    $upd = $conn->prepare("
        UPDATE user_apis
        SET exchange_name=?, api_key=?, api_secret=?,
            api_passphrase=?, permission_level=?, usage_note=?
        WHERE id=? AND user_id=?
    ");
    $upd->bind_param(
      'ssssssii',
      $exchange_name, $api_key, $api_secret, 
      $api_passphrase, $permission_level, $usage_note,
      $apiId, $user_id
    );
    if ($upd->execute()) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'error'=>'更新失敗']);
    }
} else {
    // 新增模式
    $ins = $conn->prepare("
        INSERT INTO user_apis
        (user_id, exchange_name, api_key, api_secret,
         api_passphrase, permission_level, usage_note)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param(
      'issssss',
      $user_id,
      $exchange_name, $api_key, $api_secret,
      $api_passphrase, $permission_level, $usage_note
    );
    if ($ins->execute()) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'error'=>'新增失敗']);
    }
}
