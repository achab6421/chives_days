<?php
session_start();
require_once 'db_connection.php'; //找根目錄的db_connection.php

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];

// 查詢 Bitget API
$exchange = 'bitget';
$api_query = $conn->prepare("
    SELECT ua.api_key, ua.api_secret, ua.api_passphrase
    FROM user_apis ua
    JOIN users u ON ua.user_id = u.id
    WHERE u.username = ? AND ua.exchange_name = ?
    LIMIT 1
");
$api_query->bind_param('ss', $username, $exchange);
$api_query->execute();
$api_data = $api_query->get_result()->fetch_assoc();

if (!$api_data) {
    header('Location: contracts.php?okx_sync_error=no_api'); 
    exit;
}

$api_key = $api_data['api_key'];
$secret_key = $api_data['api_secret'];
$passphrase = $api_data['api_passphrase'];

// ▼▼ Bitget API 請求 (此為範例)
$timestamp = gmdate("Y-m-d\\TH:i:s\\Z");
$method = "GET";
$request_path = "/api/mix/v1/trace/fills"; // (隨便舉例 Bitget 路徑)
$body = "";
$prehash = $timestamp . $method . $request_path . $body;
$signature = base64_encode(hash_hmac('sha256', $prehash, $secret_key, true));

$ch = curl_init("https://api.bitget.com" . $request_path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "BITGET-ACCESS-KEY: $api_key",
    "BITGET-ACCESS-SIGN: $signature",
    "BITGET-ACCESS-TIMESTAMP: $timestamp",
    "BITGET-ACCESS-PASSPHRASE: $passphrase",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
if (!isset($result['data'])) {
    header('Location: contracts.php?okx_sync_error=fetch_fail');
    exit;
}

// user_id
$user_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_stmt->bind_param('s', $username);
$user_stmt->execute();
$user_id = $user_stmt->get_result()->fetch_assoc()['id'];

// 刪除舊 Bitget API 資料
$del_stmt = $conn->prepare("
    DELETE FROM strategies
    WHERE user_id = ? AND note = '來自 BITGET API'
");
$del_stmt->bind_param('i', $user_id);
$del_stmt->execute();

$records = $result['data'];

// 找最大 sn => 'BITGET-xxxxx'
$sn_stmt = $conn->prepare("
    SELECT sn
    FROM strategies
    WHERE sn LIKE 'BITGET-%'
    ORDER BY sn DESC
    LIMIT 1
");
$sn_stmt->execute();
$max_sn_row = $sn_stmt->get_result()->fetch_assoc();

if ($max_sn_row) {
    $max_sn = $max_sn_row['sn']; 
    $num_part = (int) substr($max_sn, 7); // 'BITGET-' len=7
    $next_num = $num_part + 1;
} else {
    $next_num = 1;
}

// 插入
$insert_stmt = $conn->prepare("
    INSERT INTO strategies
    (user_id, sn, title, content, related_platform,
     contract_date, amount, note, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

foreach ($records as $row) {
    $sn_number = str_pad($next_num++, 5, '0', STR_PAD_LEFT); 
    $sn = 'BITGET-' . $sn_number;

    $title = $row['instId'] ?? '未知商品';
    $content = ($row['side'] ?? 'buy') === 'buy' ? '多單' : '空單';
    $related_platform = 'Bitget';
    $ts = isset($row['ts']) ? $row['ts']/1000 : time();
    $contract_date = date('Y-m-d', $ts);
    $fillPx = floatval($row['fillPx'] ?? 0);
    $fillSz = floatval($row['fillSz'] ?? 0);
    $amount = $fillPx * $fillSz;
    $note = '來自 BITGET API';

    $insert_stmt->bind_param(
      'isssssds',
      $user_id,
      $sn,
      $title,
      $content,
      $related_platform,
      $contract_date,
      $amount,
      $note
    );
    $insert_stmt->execute();
}

// 完成
header('Location: contracts.php?okx_sync_success=1');
exit;
