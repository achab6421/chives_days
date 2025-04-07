<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Taipei'); // ✅ 設定為台灣時間
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once 'db_connection.php';

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('尚未登入');
    }
    $user_id = $_SESSION['user_id'];

    // 取得 OKX API 金鑰
    $stmt = $conn->prepare("SELECT api_key, api_secret, api_passphrase FROM user_apis WHERE user_id = ? AND exchange_name = 'okx' LIMIT 1");
    if (!$stmt) throw new Exception("取得 API 金鑰失敗：" . $conn->error);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $api_data = $stmt->get_result()->fetch_assoc();
    if (!$api_data) throw new Exception('找不到 OKX API 金鑰');

    $api_key = $api_data['api_key'];
    $secret_key = $api_data['api_secret'];
    $passphrase = $api_data['api_passphrase'];

    // 呼叫 OKX API
    function call_okx_api($path, $api_key, $secret_key, $passphrase) {
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        $method = "GET";
        $prehash = $timestamp . $method . $path;
        $signature = base64_encode(hash_hmac('sha256', $prehash, $secret_key, true));

        $ch = curl_init("https://www.okx.com" . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "OK-ACCESS-KEY: $api_key",
            "OK-ACCESS-SIGN: $signature",
            "OK-ACCESS-TIMESTAMP: $timestamp",
            "OK-ACCESS-PASSPHRASE: $passphrase",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) throw new Exception("CURL 錯誤：" . curl_error($ch));
        curl_close($ch);

        $data = json_decode($response, true);
        if ($data === null) throw new Exception("JSON 解碼錯誤：" . substr($response, 0, 200));
        if ($data['code'] !== '0') throw new Exception("OKX API 錯誤：" . ($data['msg'] ?? '未知錯誤'));

        return $data;
    }

    // 抓歷史倉位資料（含 realizedPnl）分頁處理
    $positions = [];
    $cursor = '';
    do {
        $path = "/api/v5/account/positions-history?instType=SWAP&limit=100" . ($cursor ? "&after=$cursor" : '');
        $result = call_okx_api($path, $api_key, $secret_key, $passphrase);
        $data = $result['data'] ?? [];

        if (empty($data)) break;

        $positions = array_merge($positions, $data);
        $cursor = end($data)['uTime'] ?? '';
    } while (!empty($data) && count($data) === 100);

    // 建立已存在資料索引（避免重複）
    $existing_sn = [];
    $sn_result = $conn->query("SELECT sn FROM strategies WHERE sn LIKE 'OKX-%'");
    while ($row = $sn_result->fetch_assoc()) {
        $existing_sn[$row['sn']] = true;
    }

    // 取得最新流水號
    $sn_stmt = $conn->prepare("SELECT sn FROM strategies WHERE sn LIKE 'OKX-%' ORDER BY sn DESC LIMIT 1");
    $sn_stmt->execute();
    $max_sn_row = $sn_stmt->get_result()->fetch_assoc();
    $next_num = ($max_sn_row) ? (int)substr($max_sn_row['sn'], 4) + 1 : 1;

    $upsert = $conn->prepare("INSERT INTO strategies (
        user_id, sn, title, content, related_platform,
        contract_date, amount, note, created_at, leverage, profit_loss, okx_pos_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        amount = VALUES(amount),
        leverage = VALUES(leverage),
        profit_loss = VALUES(profit_loss),
        updated_at = NOW()");
    if (!$upsert) throw new Exception("預備 upsert 失敗：" . $conn->error);

    $inserted = 0;
    foreach ($positions as $pos) {
        $sn = 'OKX-' . str_pad($next_num++, 5, '0', STR_PAD_LEFT);
        if (isset($existing_sn[$sn])) continue;

        $title = $pos['instId'];
        $content = $pos['posSide'] === 'long' ? '多單' : '空單';
        $platform = 'OKX';

        // ✅ 安全轉換時間為台灣時區
        $timestamp_ms = $pos['cTime'] ?? null;
        if ($timestamp_ms && is_numeric($timestamp_ms)) {
            $contract_date = date('Y-m-d H:i:s', $timestamp_ms / 1000);
        } else {
            $contract_date = null;
        }

        $avgPx = floatval($pos['closeAvgPx'] ?? $pos['avgPx'] ?? 0);
        $posSize = floatval($pos['closeTotalPos'] ?? $pos['pos'] ?? 0);
        $amount = $avgPx * $posSize;
        $note = '來自 OKX 合約紀錄';
        $leverage = floatval($pos['lever'] ?? 0);
        $profit_loss = floatval($pos['realizedPnl'] ?? 0);
        $okx_pos_id = $pos['posId'];

        $upsert->bind_param('isssssdsdds', $user_id, $sn, $title, $content, $platform, $contract_date, $amount, $note, $leverage, $profit_loss, $okx_pos_id);

        if ($upsert->execute()) {
            $inserted++;
        }
    }

    echo json_encode(['success' => true, 'positions_count' => $inserted]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
