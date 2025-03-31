<?php
session_start();
require_once 'db_connection.php';
require_once 'get_exchange_rates.php';

$rates = getExchangeRates();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];

$stmt = $conn->prepare('SELECT ua.api_key, ua.api_secret, ua.api_passphrase 
                        FROM user_apis ua 
                        JOIN users u ON ua.user_id = u.id 
                        WHERE u.username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$api = $result->fetch_assoc();

if (!$api) {
    echo "<h3 class='text-center text-danger mt-5'>❌ 未設定 OKX API 金鑰，請先到個人設定</h3>";
    exit;
}

$timestamp = gmdate("Y-m-d\TH:i:s") . ".000Z";
$method = "GET";
$requestPath = "/api/v5/account/balance";
$body = "";
$preHash = $timestamp . $method . $requestPath . $body;
$sign = base64_encode(hash_hmac('sha256', $preHash, $api['api_secret'], true));

$headers = [
    "OK-ACCESS-KEY: {$api['api_key']}",
    "OK-ACCESS-SIGN: $sign",
    "OK-ACCESS-TIMESTAMP: $timestamp",
    "OK-ACCESS-PASSPHRASE: {$api['api_passphrase']}",
    "Content-Type: application/json"
];

$ch = curl_init("https://www.okx.com{$requestPath}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$assets = $data['data'][0]['details'] ?? [];

$totalTWD = 0;

// for auto-color fallback
$bootstrapColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info'];
$colorMap = [
    'USDT' => 'success',
    'BTC' => 'warning',
    'ETH' => 'primary',
    'SOL' => 'primary',
    'PI' => 'info',
    'VINE' => 'info',
    'OKSOL' => 'danger',
];

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的 OKX 資產</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10">
            <div class="card shadow rounded">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">📊 我的 OKX 資產</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>幣種</th>
                                    <th>可用資產</th>
                                    <th>總資產</th>
                                    <th>折合台幣</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($assets as $a): 
                                $ccy = $a['ccy'] ?? '-';
                                $availBal = $a['availBal'] ?? '0';
                                $bal = $a['bal'] ?? '0';
                                $rate = isset($rates[$ccy]) ? $rates[$ccy] : 0;
                                $valueTWD = floatval($availBal) * floatval($rate);
                                $totalTWD += $valueTWD;
                                $badgeColor = $colorMap[$ccy] ?? $bootstrapColors[crc32($ccy) % count($bootstrapColors)];
                                ?>
                                <tr>
                                    <td><span class="badge bg-<?= $badgeColor ?> px-3 py-2"><?= htmlspecialchars($ccy) ?></span></td>
                                    <td><?= htmlspecialchars($availBal) ?></td>
                                    <td><?= htmlspecialchars($bal) ?></td>
                                    <td><?= number_format($valueTWD, 2) ?> 元</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-info">
                                    <td colspan="3" class="text-end fw-bold">總資產：</td>
                                    <td class="fw-bold text-primary"><?= number_format($totalTWD, 2) ?> 元</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="text-center mt-4">
                        <a href="profile.php" class="btn btn-outline-primary">返回個人設定</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
