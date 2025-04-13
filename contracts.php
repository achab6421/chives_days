<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];

$user_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_stmt->bind_param('s', $username);
$user_stmt->execute();
$user_id = $user_stmt->get_result()->fetch_assoc()['id'];

$stmt = $conn->prepare("
    SELECT s.id,
           s.sn,
           s.title,
           s.content,
           s.related_platform,
           s.contract_date,
           s.amount,
           s.note,
           s.created_at,
           s.leverage,
           s.profit_loss,
           (
             SELECT COUNT(*)
             FROM favorites f
             WHERE f.user_id = u.id 
               AND f.strategy_id = s.id
           ) AS is_favorite
    FROM strategies s
    JOIN users u ON s.user_id = u.id
    WHERE u.username = ?
    ORDER BY s.created_at DESC
");
$stmt->bind_param('s', $username);
$stmt->execute();
$strategies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>合約紀錄</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <?php include 'navbar.php'; ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container d-flex justify-content-between align-items-center">
                <h1 class="text-center">合約紀錄</h1>
                <div class="d-flex align-items-center gap-2">
                    <select id="platformFilter" class="form-select me-2" style="width: auto;">
                        <option value="">所有平台</option>
                        <option value="OKX">OKX</option>
                        <option value="Bitget">Bitget</option>
                        <option value="Bybit">Bybit</option>
                        <option value="Bitunix">Bitunix</option>
                        <option value="Bitget">Bitget</option>
                        <option value="幣安">幣安</option>
                    </select>
                    <button id="refreshBtn" class="btn btn-warning">
                        <i class="fas fa-sync-alt"></i> 刷新資料
                        </button>
                    <button id="toggleView" class="btn btn-primary">切換視圖</button>
                    
                </div>
            </div>
        </div>

       

        <div class="container" id="viewContainer">
    <?php if (empty($strategies)): ?>
        <p class="text-center">目前沒有任何合約紀錄。</p>
    <?php else: ?>
        <div id="cardView" class="row">
            <?php foreach ($strategies as $strategy): ?>
                <?php
                    $content = $strategy['content'];
                    $content_color = ($content === '多單') ? 'green' : 'red';

                    $profit = floatval($strategy['profit_loss']);
                    $is_green = ($content === '多單' && $profit > 0) || ($content === '空單' && $profit < 0);
                    $profit_color = $is_green ? 'green' : 'red';
                ?>
                <div class="col-md-6 strategy-card">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <button class="btn btn-link p-0 favorite-btn" data-strategy-id="<?= $strategy['id']; ?>">
                                <i class="fas fa-heart fa-2x" style="color: <?= $strategy['is_favorite'] ? 'red' : 'gray'; ?>;"></i>
                            </button>
                            <h5 class="card-title mb-0"><?= htmlspecialchars($strategy['sn'] ?: $strategy['title']); ?></h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><span style="color: <?= $content_color ?>"><?= htmlspecialchars($content) ?></span></p>
                            <p><strong>相關平台:</strong> <?= htmlspecialchars($strategy['related_platform']); ?></p>
                            <p><strong>合約日期:</strong> <?= date('Y年m月d日 H:i:s', strtotime($strategy['contract_date'])) ?></p>
                            <p><strong>倉位總價值 (USDT):</strong> <?= number_format(floatval($strategy['amount']), 2); ?></p>
                            <p><strong>槓桿倍數:</strong> <?= htmlspecialchars($strategy['leverage']); ?>x</p>
                            <p><strong>營收 (USDT):</strong>
                                <span style="color: <?= $profit_color ?>;">
                                    <?= (abs($profit) >= 0.001) ? number_format($profit, 6) : '0.000000'; ?>
                                </span>
                            </p>
                            <p><strong>備註:</strong> <?= nl2br(htmlspecialchars($strategy['note'])); ?></p>
                            <!-- 保留標籤與新增標籤表單（未變） -->
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center text-muted">
                            <span>建立日期: <?= htmlspecialchars($strategy['created_at']); ?></span>
                            <button class="btn btn-sm btn-danger delete-btn" data-strategy-id="<?= $strategy['id']; ?>">刪除</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="tableView" class="d-none">
            <table id="contractsTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>流水號</th>
                        <th>收藏</th>
                        <th>標題</th>
                        <th>內容</th>
                        <th>相關平台</th>
                        <th>合約日期</th>
                        <th>倉位總價值 (USDT)</th>
                        <th>槓桿倍數</th>
                        <th>營收 (USDT)</th>
                        <th>備註</th>
                        <th>標籤</th>
                        <th>建立日期</th>
                        <th>刪除</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($strategies as $strategy): ?>
                        <?php
                            $content = $strategy['content'];
                            $content_color = ($content === '多單') ? 'green' : 'red';
                            $profit = floatval($strategy['profit_loss']);
                            $is_green = ($content === '多單' && $profit > 0) || ($content === '空單' && $profit < 0);
                            $profit_color = $is_green ? 'green' : 'red';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($strategy['sn']); ?></td>
                            <td class="text-center">
                                <button class="btn btn-link p-0 favorite-btn" data-strategy-id="<?= $strategy['id']; ?>">
                                    <i class="fas fa-heart fa-lg" style="color: <?= $strategy['is_favorite'] ? 'red' : 'gray'; ?>;"></i>
                                </button>
                            </td>
                            <td><?= htmlspecialchars($strategy['title']); ?></td>
                            <td><span style="color: <?= $content_color ?>;"><?= htmlspecialchars($content) ?></span></td>
                            <td><?= htmlspecialchars($strategy['related_platform']); ?></td>
                            <td><?= htmlspecialchars($strategy['contract_date']); ?></td>
                            <td><?= is_numeric($strategy['amount']) ? number_format(floatval($strategy['amount']), 2) : '0.00'; ?></td>
                            <td><?= htmlspecialchars($strategy['leverage']); ?>x</td>
                            <td style="color: <?= $profit_color ?>;">
                                <?= (abs($profit) >= 0.001) ? number_format($profit, 6) : '0.000000'; ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($strategy['note'])); ?></td>
                            <td><!-- 標籤維持不變 --></td>
                            <td><?= htmlspecialchars($strategy['created_at']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-danger delete-btn" data-strategy-id="<?= $strategy['id']; ?>">刪除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () { 
    const cardView = document.getElementById('cardView');
    const tableView = document.getElementById('tableView');
    const platformFilter = document.getElementById('platformFilter');
    const cards = document.querySelectorAll('.strategy-card');
    const favoriteButtons = document.querySelectorAll('.favorite-btn');

    // ✅ 正確：DataTable 初始化完成後分開寫
    const table = $('#contractsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/zh-TW.json'
        }
    });
    

    // ✅ 正確：這裡才開始寫收藏功能
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const strategyId = this.getAttribute('data-strategy-id');
            const heartIcon = this.querySelector('i');
            console.log('收藏按鈕點擊');

            fetch('toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ strategy_id: strategyId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    heartIcon.style.color = data.is_favorite ? 'red' : 'gray';
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '操作失敗',
                        text: '請稍後再試。',
                        confirmButtonText: '確定'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '操作失敗',
                    text: '請稍後再試。',
                    confirmButtonText: '確定'
                });
            });
        });
    });



 
      
        // 點擊刷新按鈕觸發 sync_okx.php
        document.getElementById('refreshBtn')?.addEventListener('click', function () {
        const selectedPlatform = document.getElementById('platformFilter').value;
        if (!selectedPlatform) {
            Swal.fire({ icon: 'warning', title: '請先選擇交易所', text: '請從下拉選單選擇一個平台。' });
            return;
        }

        Swal.fire({
            title: '資料同步中...\n',
            text: `請稍候，正在從 ${selectedPlatform} 同步資料`,
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        let syncUrl = '';
        switch (selectedPlatform.toLowerCase()) {
            case 'okx':
                syncUrl = 'sync_okx.php';
                break;
            case 'bitget':
                syncUrl = 'sync_bitget.php';
                break;
            case 'bybit':
                syncUrl = 'sync_bybit.php';
                break;
            // case 'bitunix':
            //     syncUrl = 'sync_bitunix.php';
            //     break;
            default:
                Swal.fire({ icon: 'error', title: '不支援的平台', text: '尚未支援此平台資料同步。' });
                return;
        }

        fetch(syncUrl)
            .then(response => {
                if (!response.ok) throw new Error(`伺服器錯誤（HTTP ${response.status}）`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: '同步完成',
                        text: `已取得 ${data.positions_count || 0} 筆資料`,
                        confirmButtonText: '重新載入'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '同步失敗',
                        text: data.error || '請稍後再試',
                    });
                }
            })
            .catch(error => {
                console.error('同步錯誤:', error);
                Swal.fire({
                    icon: 'error',
                    title: '發生錯誤',
                    text: error.message || '請稍後再試',
                });
            });
    });




    // 切換視圖按鈕
    document.getElementById('toggleView').addEventListener('click', function () {
        cardView.classList.toggle('d-none');
        tableView.classList.toggle('d-none');
    });

    // 篩選功能 + 記憶選項
    const savedPlatform = localStorage.getItem('selectedPlatform');
    if (savedPlatform) {
        platformFilter.value = savedPlatform;
        filterView(savedPlatform);
    }

    platformFilter.addEventListener('change', function () {
        const selected = this.value;
        localStorage.setItem('selectedPlatform', selected);
        filterView(selected);
    });

    function filterView(keyword) {
        const lowerKeyword = keyword.toLowerCase();

        cards.forEach(card => {
            const platformText = card.querySelector('.card-body p:nth-of-type(2)').innerText.toLowerCase();
            card.style.display = (!lowerKeyword || platformText.includes(lowerKeyword)) ? '' : 'none';
        });

        table.column(4).search(keyword).draw();
    }

        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const strategyId = this.getAttribute('data-strategy-id');

                Swal.fire({
                    icon: 'warning',
                    title: '確認刪除？',
                    text: '刪除後將無法復原！',
                    showCancelButton: true,
                    confirmButtonText: '確定刪除',
                    cancelButtonText: '取消'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('delete_strategy.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ strategy_id: strategyId })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '刪除成功',
                                    confirmButtonText: '確定'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '刪除失敗',
                                    text: data.error || '請稍後再試',
                                    confirmButtonText: '確定'
                                });
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            Swal.fire({
                                icon: 'error',
                                title: '操作失敗',
                                text: '請稍後再試。',
                                confirmButtonText: '確定'
                            });
                        });
                    }
                });
            });
        });
    });
</script>
</body>
</html>