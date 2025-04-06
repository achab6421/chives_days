<?php
session_start();
// 檢查用戶是否登入
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API 測試</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <h1 class="mb-4">CoinGecko API 測試</h1>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">測試 CoinGecko API</h5>
            </div>
            <div class="card-body">
                <form method="get" action="" class="mb-4">
                    <div class="mb-3">
                        <label for="coin" class="form-label">選擇幣種</label>
                        <select name="coin" id="coin" class="form-select">
                            <option value="bitcoin" <?= ($_GET['coin'] ?? '') === 'bitcoin' ? 'selected' : '' ?>>Bitcoin (BTC)</option>
                            <option value="ethereum" <?= ($_GET['coin'] ?? '') === 'ethereum' ? 'selected' : '' ?>>Ethereum (ETH)</option>
                            <option value="solana" <?= ($_GET['coin'] ?? '') === 'solana' ? 'selected' : '' ?>>Solana (SOL)</option>
                            <option value="binancecoin" <?= ($_GET['coin'] ?? '') === 'binancecoin' ? 'selected' : '' ?>>Binance Coin (BNB)</option>
                            <option value="ripple" <?= ($_GET['coin'] ?? '') === 'ripple' ? 'selected' : '' ?>>Ripple (XRP)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">獲取數據</button>
                </form>

                <?php
                if (isset($_GET['coin'])) {
                    $coin = $_GET['coin'];
                    // 獲取過去 24 小時的數據
                    $endTime = time();
                    $startTime = $endTime - (24 * 60 * 60);
                    $url = "https://api.coingecko.com/api/v3/coins/{$coin}/market_chart/range?vs_currency=usd&from={$startTime}&to={$endTime}&precision=full";
                    
                    echo "<p>API URL: <code>$url</code></p>";
                    
                    // 改用 file_get_contents 替代 cURL
                    $opts = [
                        'http' => [
                            'method' => 'GET',
                            'header' => [
                                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                            ]
                        ]
                    ];
                    
                    $context = stream_context_create($opts);
                    $response = @file_get_contents($url, false, $context);
                    $httpCode = $response !== false ? 200 : 404;
                    
                    echo "<h5 class='mt-4'>API 回應 (HTTP " . ($response !== false ? '200' : '請求失敗') . "):</h5>";
                    
                    if ($response !== false) {
                        $data = json_decode($response, true);
                        
                        if (isset($data['prices']) && !empty($data['prices'])) {
                            echo "<h6>價格數據：</h6>";
                            echo "<div class='table-responsive'>";
                            echo "<table class='table table-bordered table-striped'>";
                            echo "<thead><tr><th>時間</th><th>價格 (USD)</th></tr></thead>";
                            echo "<tbody>";
                            
                            // 只顯示 10 條數據
                            $count = 0;
                            foreach ($data['prices'] as $price) {
                                if ($count >= 10) break;
                                $time = date('Y-m-d H:i:s', intval($price[0] / 1000));
                                $priceValue = number_format($price[1], 2);
                                echo "<tr><td>$time</td><td>$priceValue</td></tr>";
                                $count++;
                            }
                            
                            echo "</tbody></table></div>";
                            
                            echo "<p>總共 " . count($data['prices']) . " 條數據點</p>";
                            
                            // 計算亞洲時段、倫敦時段和紐約時段的最高最低價
                            $today = date('Y-m-d');
                            $asiaStart = strtotime("$today 00:00:00");
                            $asiaEnd = strtotime("$today 08:00:00");
                            $londonStart = strtotime("$today 08:00:00");
                            $londonEnd = strtotime("$today 16:00:00");
                            $nyStart = strtotime("$today 16:00:00");
                            $nyEnd = strtotime("$today 24:00:00");
                            
                            $asiaPrices = [];
                            $londonPrices = [];
                            $nyPrices = [];
                            
                            foreach ($data['prices'] as $price) {
                                $time = intval($price[0] / 1000);
                                if ($time >= $asiaStart && $time <= $asiaEnd) {
                                    $asiaPrices[] = $price[1];
                                } else if ($time >= $londonStart && $time <= $londonEnd) {
                                    $londonPrices[] = $price[1];
                                } else if ($time >= $nyStart && $time <= $nyEnd) {
                                    $nyPrices[] = $price[1];
                                }
                            }
                            
                            echo "<h6 class='mt-4'>各時段數據：</h6>";
                            echo "<div class='row'>";
                            
                            // 亞洲時段
                            echo "<div class='col-md-4'>";
                            echo "<div class='card mb-3'>";
                            echo "<div class='card-header bg-warning text-white'>亞洲時段 (00:00-08:00 UTC)</div>";
                            echo "<div class='card-body'>";
                            if (!empty($asiaPrices)) {
                                $asiaHigh = max($asiaPrices);
                                $asiaLow = min($asiaPrices);
                                $asiaVolatility = round(($asiaHigh - $asiaLow) / $asiaLow * 100, 2);
                                
                                echo "<p>最高價: $" . number_format($asiaHigh, 2) . "</p>";
                                echo "<p>最低價: $" . number_format($asiaLow, 2) . "</p>";
                                echo "<p>波動率: " . $asiaVolatility . "%</p>";
                            } else {
                                echo "<p>暫無數據</p>";
                            }
                            echo "</div></div></div>";
                            
                            // 倫敦時段
                            echo "<div class='col-md-4'>";
                            echo "<div class='card mb-3'>";
                            echo "<div class='card-header bg-primary text-white'>倫敦時段 (08:00-16:00 UTC)</div>";
                            echo "<div class='card-body'>";
                            if (!empty($londonPrices)) {
                                $londonHigh = max($londonPrices);
                                $londonLow = min($londonPrices);
                                $londonVolatility = round(($londonHigh - $londonLow) / $londonLow * 100, 2);
                                
                                echo "<p>最高價: $" . number_format($londonHigh, 2) . "</p>";
                                echo "<p>最低價: $" . number_format($londonLow, 2) . "</p>";
                                echo "<p>波動率: " . $londonVolatility . "%</p>";
                            } else {
                                echo "<p>暫無數據</p>";
                            }
                            echo "</div></div></div>";
                            
                            // 紐約時段
                            echo "<div class='col-md-4'>";
                            echo "<div class='card mb-3'>";
                            echo "<div class='card-header bg-danger text-white'>紐約時段 (16:00-24:00 UTC)</div>";
                            echo "<div class='card-body'>";
                            if (!empty($nyPrices)) {
                                $nyHigh = max($nyPrices);
                                $nyLow = min($nyPrices);
                                $nyVolatility = round(($nyHigh - $nyLow) / $nyLow * 100, 2);
                                
                                echo "<p>最高價: $" . number_format($nyHigh, 2) . "</p>";
                                echo "<p>最低價: $" . number_format($nyLow, 2) . "</p>";
                                echo "<p>波動率: " . $nyVolatility . "%</p>";
                            } else {
                                echo "<p>暫無數據</p>";
                            }
                            echo "</div></div></div>";
                            
                            echo "</div>";
                        } else {
                            echo "<div class='alert alert-warning'>未找到價格數據</div>";
                        }
                    } else {
                        echo "<div class='alert alert-danger'>API 請求失敗。可能的原因：伺服器連線問題或 API 限制達到上限。</div>";
                        echo "<div class='alert alert-info'>建議：啟用 PHP 的 cURL 擴展以獲得更好的 API 請求支援。</div>";
                    }
                }
                ?>
            </div>
        </div>
        
        <a href="dashboard.php" class="btn btn-secondary">返回儀表板</a>
    </div>
</body>
</html>
