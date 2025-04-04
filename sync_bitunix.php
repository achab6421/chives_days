<?php
session_start();
require_once 'db_connection.php'; //找根目錄的db_connection.php

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];

// 查詢 BITUNIX API Key
$exchange = 'bitunix';
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

// 若沒設 API，就回到 contracts.php
if (!$api_data) {
    header('Location: contracts.php?okx_sync_error=no_api'); // 你可以改成 sync_error=no_api
    exit;
}

$api_key = $api_data['api_key'];
$secret_key = $api_data['api_secret'];
$passphrase = $api_data['api_passphrase'];

// ▼▼ 依BitUnix官方文件，發出API請求 (範例)
$timestamp = gmdate("Y-m-d\\TH:i:s\\Z");
$method = "GET";

// BitUnix 可能不是 /api/v5/trade/fills 路徑，請改成該交易所正確 Endpoint
$request_path = "/api/v1/some_endpoint_for_fills";
$body = "";

// 產生簽名 (此處只是範例，你需依BitUnix官方檔案實際計算)
$prehash = $timestamp . $method . $request_path . $body;
$signature = base64_encode(hash_hmac('sha256', $prehash, $secret_key, true));

$ch = curl_init("https://www.bitunix.com" . $request_path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "BITUNIX-ACCESS-KEY: $api_key",
    "BITUNIX-ACCESS-SIGN: $signature",
    "BITUNIX-ACCESS-TIMESTAMP: $timestamp",
    "BITUNIX-ACCESS-PASSPHRASE: $passphrase",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

// 解析
$result = json_decode($response, true);

// 若 data 不存在或表示失敗
if (!isset($result['data'])) {
    header('Location: contracts.php?okx_sync_error=fetch_fail'); 
    exit;
}

// 取得 user_id
$user_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_stmt->bind_param('s', $username);
$user_stmt->execute();
$user_id = $user_stmt->get_result()->fetch_assoc()['id'];

// 刪除「來自 BITUNIX API」舊紀錄
$del_stmt = $conn->prepare("
    DELETE FROM strategies
    WHERE user_id = ? AND note = '來自 BITUNIX API'
");
$del_stmt->bind_param('i', $user_id);
$del_stmt->execute();

$records = $result['data'];

// 找目前最大的 BITUNIX-xxxxx
$sn_stmt = $conn->prepare("
    SELECT sn
    FROM strategies
    WHERE sn LIKE 'BITUNIX-%'
    ORDER BY sn DESC
    LIMIT 1
");
$sn_stmt->execute();
$max_sn_row = $sn_stmt->get_result()->fetch_assoc();
if ($max_sn_row) {
    $max_sn = $max_sn_row['sn']; // e.g. 'BITUNIX-00123'
    $num_part = (int) substr($max_sn, 8); // 'BITUNIX-' 長度 8
    $next_num = $num_part + 1;
} else {
    $next_num = 1;
}

// 插入資料
$insert_stmt = $conn->prepare("
    INSERT INTO strategies
    (user_id, sn, title, content, related_platform, contract_date, amount, note, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

foreach ($records as $row) {
    // 產生下個流水號 e.g. BITUNIX-00001
    $sn_number = str_pad($next_num++, 5, '0', STR_PAD_LEFT); 
    $sn = 'BITUNIX-' . $sn_number; 

    // 對應檔案 (範例)
    $title = $row['instId'] ?? '未知商品'; 
    $content = ($row['side'] ?? 'buy') === 'buy' ? '多單' : '空單';
    $related_platform = 'BitUnix';
    $ts = isset($row['ts']) ? $row['ts']/1000 : time();
    $contract_date = date('Y-m-d', $ts);
    $fillPx = floatval($row['fillPx'] ?? 0);
    $fillSz = floatval($row['fillSz'] ?? 0);
    $amount = $fillPx * $fillSz;
    $note = '來自 BITUNIX API';

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

// 同步完成，回 contracts.php
header('Location: contracts.php?okx_sync_success=1');
exit;
