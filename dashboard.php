<?php
session_start();
require_once 'db_connection.php';
require_once 'fetch_market_data.php';
require_once 'get_exchange_rates.php';

// 檢查用戶是否登入
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];

// 設定預設幣種，或從 URL 參數獲取
$selectedCoin = isset($_GET['coin']) ? $_GET['coin'] : 'BTC';

// 可用幣種列表
$availableCoins = [
    'BTC' => '比特幣 (Bitcoin)',
    'ETH' => '以太坊 (Ethereum)',
    'SOL' => '索拉納 (Solana)',
    'BNB' => '幣安幣 (Binance Coin)',
    'XRP' => '瑞波幣 (Ripple)',
    'ADA' => '艾達幣 (Cardano)',
    'DOGE' => '狗狗幣 (Dogecoin)',
    'DOT' => '波卡幣 (Polkadot)'
];

// 獲取所選幣種的交易時段數據
$marketData = fetchMarketSessionData($selectedCoin);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>儀表板 - 韭菜日常</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .market-card {
            transition: all 0.3s ease;
        }
        .market-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .price-up {
            color: #28a745;
        }
        .price-down {
            color: #dc3545;
        }
        .session-title {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .coin-selector {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background-color: #f8f9fa;
        }
        .coin-icon {
            width: 24px;
            height: 24px;
            margin-right: 5px;
        }
        /* 新增的樣式 */
        .session-header {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .session-content {
            transition: max-height 0.3s ease;
            overflow: hidden;
        }
        .collapse-icon {
            transition: transform 0.3s ease;
        }
        .collapsed .collapse-icon {
            transform: rotate(-180deg);
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <?php include 'navbar.php'; ?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">儀表板</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">首頁</a></li>
                            <li class="breadcrumb-item active">儀表板</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container">
                <!-- 幣種選擇器 -->
                <div class="coin-selector">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-0">
                                <i class="fas fa-coins text-warning"></i>
                                選擇幣種查看交易時段數據
                            </h4>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-md-end">
                                <select id="coinSelector" class="form-select" style="max-width: 250px;">
                                    <?php foreach ($availableCoins as $code => $name): ?>
                                        <option value="<?= $code ?>" <?= $selectedCoin === $code ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($name) ?> (<?= $code ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button id="refreshMarketData" class="btn btn-primary ms-2">
                                    <i class="fas fa-sync-alt"></i> 刷新數據
                                </button>
                                <a href="api_test.php" class="btn btn-info ms-2">
                                    <i class="fas fa-vial"></i> API 測試
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 市場概況 -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line me-1"></i>
                            <?= htmlspecialchars($availableCoins[$selectedCoin] ?? $selectedCoin) ?> 交易時段概況
                        </h3>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-info me-2">資料來源: CoinGecko API</span>
                            <span class="badge bg-secondary">最後更新: <?= date('Y-m-d H:i:s') ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="marketDataContainer">
                            <!-- 亞洲時段 -->
                            <div class="col-md-4">
                                <div class="session-block" id="asia-session">
                                    <div class="session-title">
                                        <div class="session-header" data-target="asia-content">
                                            <h4><i class="fas fa-sun text-warning"></i> 亞洲時段 (00:00-08:00 UTC)</h4>
                                            <i class="fas fa-chevron-up collapse-icon"></i>
                                        </div>
                                    </div>
                                    <div class="session-content" id="asia-content">
                                        <?php if(empty($marketData['asia'])): ?>
                                            <div class="alert alert-info">暫無亞洲時段數據</div>
                                        <?php else: ?>
                                            <?php foreach($marketData['asia'] as $exchange => $data): ?>
                                                <div class="card market-card mb-3">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?= htmlspecialchars($exchange) ?></h5>
                                                        <div class="d-flex justify-content-between">
                                                            <div>
                                                                <p class="mb-1">最高: <span class="price-up"><?= isset($data['high']) ? number_format($data['high'], 2) : 'N/A' ?> USD</span></p>
                                                                <p class="mb-1">最低: <span class="price-down"><?= isset($data['low']) ? number_format($data['low'], 2) : 'N/A' ?> USD</span></p>
                                                            </div>
                                                            <div>
                                                                <p class="mb-1">波動率: <?= isset($data['volatility']) ? $data['volatility'] : 'N/A' ?>%</p>
                                                                <p class="mb-0">時間: <?= isset($data['time']) ? $data['time'] : 'N/A' ?></p>
                                                                <p class="mb-0 text-muted small">數據來源: CoinGecko</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 倫敦時段 -->
                            <div class="col-md-4">
                                <div class="session-block" id="london-session">
                                    <div class="session-title">
                                        <div class="session-header" data-target="london-content">
                                            <h4><i class="fas fa-building text-primary"></i> 倫敦時段 (08:00-16:00 UTC)</h4>
                                            <i class="fas fa-chevron-up collapse-icon"></i>
                                        </div>
                                    </div>
                                    <div class="session-content" id="london-content">
                                        <?php if(empty($marketData['london'])): ?>
                                            <div class="alert alert-info">暫無倫敦時段數據</div>
                                        <?php else: ?>
                                            <?php foreach($marketData['london'] as $exchange => $data): ?>
                                                <div class="card market-card mb-3">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?= htmlspecialchars($exchange) ?></h5>
                                                        <div class="d-flex justify-content-between">
                                                            <div>
                                                                <p class="mb-1">最高: <span class="price-up"><?= isset($data['high']) ? number_format($data['high'], 2) : 'N/A' ?> USD</span></p>
                                                                <p class="mb-1">最低: <span class="price-down"><?= isset($data['low']) ? number_format($data['low'], 2) : 'N/A' ?> USD</span></p>
                                                            </div>
                                                            <div>
                                                                <p class="mb-1">波動率: <?= isset($data['volatility']) ? $data['volatility'] : 'N/A' ?>%</p>
                                                                <p class="mb-0">時間: <?= isset($data['time']) ? $data['time'] : 'N/A' ?></p>
                                                                <p class="mb-0 text-muted small">數據來源: CoinGecko</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 紐約時段 -->
                            <div class="col-md-4">
                                <div class="session-block" id="newyork-session">
                                    <div class="session-title">
                                        <div class="session-header" data-target="newyork-content">
                                            <h4><i class="fas fa-city text-danger"></i> 紐約時段 (16:00-24:00 UTC)</h4>
                                            <i class="fas fa-chevron-up collapse-icon"></i>
                                        </div>
                                    </div>
                                    <div class="session-content" id="newyork-content">
                                        <?php if(empty($marketData['newyork'])): ?>
                                            <div class="alert alert-info">暫無紐約時段數據</div>
                                        <?php else: ?>
                                            <?php foreach($marketData['newyork'] as $exchange => $data): ?>
                                                <div class="card market-card mb-3">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?= htmlspecialchars($exchange) ?></h5>
                                                        <div class="d-flex justify-content-between">
                                                            <div>
                                                                <p class="mb-1">最高: <span class="price-up"><?= isset($data['high']) ? number_format($data['high'], 2) : 'N/A' ?> USD</span></p>
                                                                <p class="mb-1">最低: <span class="price-down"><?= isset($data['low']) ? number_format($data['low'], 2) : 'N/A' ?> USD</span></p>
                                                            </div>
                                                            <div>
                                                                <p class="mb-1">波動率: <?= isset($data['volatility']) ? $data['volatility'] : 'N/A' ?>%</p>
                                                                <p class="mb-0">時間: <?= isset($data['time']) ? $data['time'] : 'N/A' ?></p>
                                                                <p class="mb-0 text-muted small">數據來源: CoinGecko</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 其他儀表板內容 -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 幣種選擇器變更事件
        document.getElementById('coinSelector').addEventListener('change', function() {
            const selectedCoin = this.value;
            window.location.href = `dashboard.php?coin=${selectedCoin}`;
        });
        
        // 刷新市場數據
        document.getElementById('refreshMarketData').addEventListener('click', function() {
            const selectedCoin = document.getElementById('coinSelector').value;
            
            Swal.fire({
                title: '數據更新中...',
                text: `正在獲取 ${selectedCoin} 的最新交易時段數據`,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`fetch_market_data.php?refresh=1&coin=${selectedCoin}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '數據已更新',
                            confirmButtonText: '確定'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '更新失敗',
                            text: data.error || '請稍後再試',
                            confirmButtonText: '確定'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: '發生錯誤',
                        text: '無法連接到伺服器，請稍後再試',
                        confirmButtonText: '確定'
                    });
                });
        });
        
        // 初始化展開/收起功能
        initCollapsibleSections();

        // 初始化所有 select 元素為 Select2
        $('select').select2();
    });
    
    // 展開/收起功能實現
    function initCollapsibleSections() {
        const sessionHeaders = document.querySelectorAll('.session-header');
        const sessionState = JSON.parse(localStorage.getItem('dashboardSessionState') || '{}');
        
        sessionHeaders.forEach(header => {
            const targetId = header.getAttribute('data-target');
            const contentElement = document.getElementById(targetId);
            const sessionBlock = header.closest('.session-block');
            
            // 恢復之前的狀態
            if (sessionState[targetId] === 'collapsed') {
                contentElement.style.maxHeight = '0px';
                sessionBlock.classList.add('collapsed');
            } else {
                contentElement.style.maxHeight = contentElement.scrollHeight + 'px';
            }
            
            header.addEventListener('click', function() {
                sessionBlock.classList.toggle('collapsed');
                
                // 更新高度
                if (sessionBlock.classList.contains('collapsed')) {
                    contentElement.style.maxHeight = '0px';
                    sessionState[targetId] = 'collapsed';
                } else {
                    contentElement.style.maxHeight = contentElement.scrollHeight + 'px';
                    sessionState[targetId] = 'expanded';
                }
                
                // 保存狀態到 localStorage
                localStorage.setItem('dashboardSessionState', JSON.stringify(sessionState));
            });
        });
    }
</script>
</body>
</html>
