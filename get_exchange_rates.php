<?php
// 用來抓取熱門幣種對 TWD 匯率
function getExchangeRates() {
    $cacheFile = 'exchange_rate_cache.json';
    $cacheTime = 60; // 秒數（例如快取 1 分鐘）

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $json = file_get_contents($cacheFile);
    } else {
        $url = "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,tether,solana,okb,pi-network,binancecoin&vs_currencies=twd";
        $json = @file_get_contents($url);
        if ($json !== false) {
            file_put_contents($cacheFile, $json);
        } else {
            // 如果 API 壞掉，回傳快取舊檔或空陣列
            $json = file_exists($cacheFile) ? file_get_contents($cacheFile) : '{}';
        }
    }

    $data = json_decode($json, true);

    return [
        'BTC' => $data['bitcoin']['twd'] ?? 0,
        'ETH' => $data['ethereum']['twd'] ?? 0,
        'USDT' => $data['tether']['twd'] ?? 0,
        'SOL' => $data['solana']['twd'] ?? 0,
        'OKB' => $data['okb']['twd'] ?? 0,
        'PI' => $data['pi-network']['twd'] ?? 0,
        'BNB' => $data['binancecoin']['twd'] ?? 0,
    ];
}


