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

    // 取得 Bybit API 金鑰
    $stmt = $conn->prepare("SELECT api_key, api_secret FROM user_apis WHERE user_id = ? AND exchange_name = 'bybit' LIMIT 1");
    if (!$stmt) throw new Exception("取得 API 金鑰失敗：" . $conn->error);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $api_data = $stmt->get_result()->fetch_assoc();
    if (!$api_data) throw new Exception('找不到 Bybit API 金鑰');

    $api_key = $api_data['api_key'];
    $api_secret = $api_data['api_secret'];

    function get_bybit_server_time() {
        $url = "https://api.bybit.com/v5/market/time";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        return isset($data['time']) ? (int)$data['time'] : round(microtime(true) * 1000);
    }

    function call_bybit_api($endpoint, $api_key, $api_secret, $params = []) {
        $base_url = "https://api.bybit.com";
        $timestamp = get_bybit_server_time();
        $params['api_key'] = $api_key;
        $params['timestamp'] = $timestamp;
        $params['recvWindow'] = 10000;

        ksort($params);
        $query = http_build_query($params, '', '&');

        $signature = hash_hmac('sha256', $query, $api_secret);
        $params['sign'] = $signature;

        $url = $base_url . $endpoint . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) throw new Exception("CURL 錯誤：" . curl_error($ch));
        curl_close($ch);

        $data = json_decode($response, true);
        if ($data === null) throw new Exception("JSON 解碼錯誤，原始回應內容：" . $response);
        if (!isset($data['retCode']) || $data['retCode'] !== 0) {
            throw new Exception("Bybit API 錯誤：" . json_encode($data));
        }

        return $data['result']['list'] ?? [];
    }

    // 清除舊資料
    $del_stmt = $conn->prepare("DELETE FROM strategies WHERE user_id = ? AND note LIKE '來自 Bybit 合約紀錄%'");
    if (!$del_stmt) throw new Exception("刪除失敗：" . $conn->error);
    $del_stmt->bind_param('i', $user_id);
    $del_stmt->execute();

    // 最新流水號
    $sn_stmt = $conn->prepare("SELECT sn FROM strategies WHERE sn LIKE 'BYBIT-%' ORDER BY sn DESC LIMIT 1");
    if (!$sn_stmt) throw new Exception("查詢流水號失敗：" . $conn->error);
    $sn_stmt->execute();
    $max_sn_row = $sn_stmt->get_result()->fetch_assoc();
    $next_num = ($max_sn_row) ? (int)substr($max_sn_row['sn'], 6) + 1 : 1;

    $insert = $conn->prepare("INSERT INTO strategies (
        user_id, sn, title, content, related_platform,
        contract_date, amount, note, created_at, leverage, profit_loss, bybit_pos_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");
    if (!$insert) throw new Exception("預備插入失敗：" . $conn->error);

    $inserted = 0;
    $now = get_bybit_server_time();
    $days = 30;
    $chunk = 7 * 86400 * 1000;

    for ($i = 0; $i < $days; $i += 7) {
        $endTime = $now - ($i * 86400 * 1000);
        $startTime = $endTime - $chunk;

        $closed_positions = call_bybit_api("/v5/position/closed-pnl", $api_key, $api_secret, [
            "category" => "linear",
            "limit" => 200,
            "startTime" => $startTime,
            "endTime" => $endTime
        ]);

        foreach ($closed_positions as $row) {
            $sn = 'BYBIT-' . str_pad($next_num++, 5, '0', STR_PAD_LEFT);
            $symbol = $row['symbol'] ?? '無標題';
            $title = $symbol;
            $content = ($row['side'] ?? '') === 'Buy' ? '多單' : '空單';
            $platform = 'Bybit';
            $contract_date = date('Y-m-d H:i:s', ($row['createdTime'] ?? time()) / 1000);

            $price = floatval($row['avgExitPrice'] ?? 0);
            $qty = floatval($row['closedSize'] ?? 0);
            $amount = $price * $qty;

            $note = '來自 Bybit 合約紀錄';
            $leverage = floatval($row['leverage'] ?? 1.00);
            $profit_loss = floatval($row['closedPnl'] ?? 0);
            $bybit_pos_id = $row['orderId'] ?? null;

            $insert->bind_param('isssssdsdds',
                $user_id, $sn, $title, $content, $platform,
                $contract_date, $amount, $note, $leverage, $profit_loss, $bybit_pos_id
            );
            $insert->execute();
            $inserted++;
        }
    }

    echo json_encode([
        'success' => true,
        'positions_count' => $inserted
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
