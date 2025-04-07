<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once 'db_connection.php';

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('尚未登入');
    }
    $user_id = $_SESSION['user_id'];

    // 取得 Bitget API 金鑰
    $stmt = $conn->prepare("SELECT api_key, api_secret, api_passphrase FROM user_apis WHERE user_id = ? AND exchange_name = 'bitget' LIMIT 1");
    if (!$stmt) throw new Exception("取得 API 金鑰失敗：" . $conn->error);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $api_data = $stmt->get_result()->fetch_assoc();
    if (!$api_data) throw new Exception('找不到 Bitget API 金鑰');

    $api_key = $api_data['api_key'];
    $secret_key = $api_data['api_secret'];
    $passphrase = $api_data['api_passphrase'];

    // Bitget API 簽名與請求
    function call_bitget_api($path, $api_key, $secret_key, $passphrase) {
        $timestamp = round(microtime(true) * 1000);
        $method = "GET";
        $prehash = $timestamp . $method . $path;
        $signature = hash_hmac('sha256', $prehash, $secret_key, true);
        $sign = base64_encode($signature);

        $ch = curl_init("https://api.bitget.com" . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "ACCESS-KEY: $api_key",
            "ACCESS-SIGN: $sign",
            "ACCESS-TIMESTAMP: $timestamp",
            "ACCESS-PASSPHRASE: $passphrase",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) throw new Exception("CURL 錯誤：" . curl_error($ch));
        curl_close($ch);

        $data = json_decode($response, true);
        if ($data === null) throw new Exception("JSON 解碼錯誤：" . substr($response, 0, 200));
        if (!isset($data['code']) || $data['code'] !== '00000') {
            throw new Exception("Bitget API 錯誤：" . ($data['msg'] ?? '未知錯誤'));
        }

        return $data['data'] ?? [];
    }

    // 刪除 Bitget 舊資料
    $del_stmt = $conn->prepare("DELETE FROM strategies WHERE user_id = ? AND note LIKE '來自 Bitget%'");
    if (!$del_stmt) throw new Exception("刪除失敗：" . $conn->error);
    $del_stmt->bind_param('i', $user_id);
    $del_stmt->execute();

    // 最新流水號
    $sn_stmt = $conn->prepare("SELECT sn FROM strategies WHERE sn LIKE 'Bitget-%' ORDER BY sn DESC LIMIT 1");
    $sn_stmt->execute();
    $max_sn_row = $sn_stmt->get_result()->fetch_assoc();
    $next_num = ($max_sn_row) ? (int)substr($max_sn_row['sn'], 7) + 1 : 1;

    // 預備 insert 語句
    $insert = $conn->prepare("INSERT INTO strategies (
        user_id, sn, title, content, related_platform,
        contract_date, amount, note, created_at, leverage, profit_loss
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
    if (!$insert) throw new Exception("預備插入失敗：" . $conn->error);

    $inserted = 0;

    // 抓 Bitget 成交紀錄（加上時間）
    $endTime = round(microtime(true) * 1000);
    $startTime = $endTime - (70 * 24 * 60 * 60 * 1000); // 最近 7 天
    $fills = call_bitget_api("/api/mix/v1/order/fills?productType=umcbl&symbol=BTCUSDT_UMCBL&startTime=$startTime&endTime=$endTime", $api_key, $secret_key, $passphrase);

    foreach ($fills as $row) {
        $sn = 'Bitget-' . str_pad($next_num++, 5, '0', STR_PAD_LEFT);
        $title = $row['symbol'] ?? '無標題';
        $content = ($row['side'] ?? '') === 'open_long' ? '多單' : '空單';
        $platform = 'Bitget';
        $contract_date = date('Y-m-d H:i:s', ($row['createdTime'] ?? time()) / 1000);


        $priceAvg = isset($row['priceAvg']) ? floatval($row['priceAvg']) : 0;
        $size = isset($row['size']) ? floatval($row['size']) : 0;
        $amount = $priceAvg * $size;

        $note = '來自 Bitget 成交紀錄';
        $leverage = isset($row['leverage']) ? floatval($row['leverage']) : 1.00;
        $profit_loss = floatval($row['profit'] ?? 0);

        $insert->bind_param('isssssdsdd', $user_id, $sn, $title, $content, $platform, $contract_date, $amount, $note, $leverage, $profit_loss);
        $insert->execute();
        $inserted++;
    }

    echo json_encode([
        'success' => true,
        'positions_count' => $inserted
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
