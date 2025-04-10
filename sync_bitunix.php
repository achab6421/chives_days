<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Taipei');
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once 'db_connection.php';

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('尚未登入');
    }
    $user_id = $_SESSION['user_id'];

    // 取得 Bitunix API 金鑰
    $stmt = $conn->prepare("SELECT api_key, api_secret FROM user_apis WHERE user_id = ? AND exchange_name = 'bitunix' LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $api_data = $stmt->get_result()->fetch_assoc();
    if (!$api_data) throw new Exception('找不到 Bitunix API 金鑰');

    $api_key = $api_data['api_key'];
    $api_secret = $api_data['api_secret'];

    // Bitunix API 呼叫函式
    function call_bitunix_api($path, $api_key, $api_secret, $params = []) {
        $timestamp = round(microtime(true) * 1000);
        $params['timestamp'] = $timestamp;

        ksort($params);
        $query_string = http_build_query($params);
        $signature = hash_hmac('sha256', $query_string, $api_secret);
        $url = "https://api.bitunix.com{$path}?{$query_string}&sign={$signature}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "ApiKey: {$api_key}"
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!$data || !isset($data['code']) || $data['code'] !== '00000') {
            $msg = $data['msg'] ?? '未知錯誤';
            throw new Exception("Bitunix API 錯誤：$msg，HTTP 狀態碼：$http_code，URL：$url");
        }

        return $data['data']['rows'] ?? [];
    }

    // 清除舊資料
    $del = $conn->prepare("DELETE FROM strategies WHERE user_id = ? AND note LIKE '來自 Bitunix 訂單紀錄%'");
    $del->bind_param('i', $user_id);
    $del->execute();

    // 查詢最新流水號
    $sn_stmt = $conn->prepare("SELECT sn FROM strategies WHERE sn LIKE 'BITUNIX-%' ORDER BY sn DESC LIMIT 1");
    $sn_stmt->execute();
    $max_sn_row = $sn_stmt->get_result()->fetch_assoc();
    $next_num = $max_sn_row ? (int)substr($max_sn_row['sn'], 8) + 1 : 1;

    $insert = $conn->prepare("INSERT INTO strategies (
        user_id, sn, title, content, related_platform,
        contract_date, amount, note, created_at, leverage, profit_loss, bitunix_pos_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");

    // 抓 Bitunix 現貨歷史訂單
    $orders = call_bitunix_api('/api/spot/v1/order/history/page', $api_key, $api_secret, [
        'pageNum' => 1,
        'pageSize' => 100
    ]);

    $inserted = 0;
    foreach ($orders as $order) {
        $sn = 'BITUNIX-' . str_pad($next_num++, 5, '0', STR_PAD_LEFT);
        $symbol = $order['symbol'] ?? '未知';
        $side = strtolower($order['side'] ?? '') === 'buy' ? '多單' : '空單';
        $platform = 'Bitunix';

        $contract_date = date('Y-m-d H:i:s', intval($order['createTime']) / 1000);
        $price = floatval($order['price'] ?? 0);
        $vol = floatval($order['vol'] ?? 0);
        $amount = $price * $vol;

        $note = '來自 Bitunix 訂單紀錄';
        $leverage = 1.00;
        $profit_loss = 0.00; // Bitunix 沒有回傳盈虧
        $bitunix_pos_id = $order['orderId'] ?? null;

        $insert->bind_param('isssssdsdds',
            $user_id, $sn, $symbol, $side, $platform,
            $contract_date, $amount, $note, $leverage, $profit_loss, $bitunix_pos_id
        );
        $insert->execute();
        $inserted++;
    }

    echo json_encode(['success' => true, 'positions_count' => $inserted]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
