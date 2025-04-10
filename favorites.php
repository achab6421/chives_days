<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once 'db_connection.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$user_id = $user['id'];
$stmt->close();

$stmt = $conn->prepare("
    SELECT s.id,
           s.title,
           s.content,
           s.related_platform,
           s.contract_date,
           s.amount,
           s.note,
           s.created_at,
           s.leverage,
           s.profit_loss,
           1 AS is_favorite
    FROM strategies s
    JOIN favorites f ON s.id = f.strategy_id
    WHERE f.user_id = ?
    ORDER BY s.created_at DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$favorites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>我的收藏</title>
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
                <h1 class="text-center">我的收藏</h1>
                <div>
                    <button id="toggleView" class="btn btn-primary">切換視圖</button>
                    <a href="contracts.php" class="btn btn-secondary">返回合約紀錄</a>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="container" id="viewContainer">
                <?php if (empty($favorites)): ?>
                    <div class="alert alert-info text-center">
                        <p>您目前沒有任何收藏的合約紀錄。</p>
                        <a href="contracts.php" class="btn btn-primary mt-2">去瀏覽合約紀錄</a>
                    </div>
                <?php else: ?>
                    <div id="cardView" class="row">
                        <?php foreach ($favorites as $strategy): ?>
                            <?php
                                $content = $strategy['content'];
                                $profit = floatval($strategy['profit_loss']);
                                $is_green = ($content === '多單' && $profit > 0) || ($content === '空單' && $profit < 0);
                                $profit_color = $is_green ? 'green' : 'red';
                                $side_color = ($content === '多單') ? 'green' : 'red';
                            ?>
                            <div class="col-md-6 strategy-card">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <button class="btn btn-link p-0 favorite-btn" data-strategy-id="<?php echo $strategy['id']; ?>">
                                            <i class="fas fa-heart fa-2x" style="color: red;"></i>
                                        </button>
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($strategy['title']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><span style="color: <?php echo $side_color; ?>"><?php echo htmlspecialchars($content); ?></span></p>
                                        <p><strong>相關平台:</strong> <?php echo htmlspecialchars($strategy['related_platform']); ?></p>
                                        <p><strong>合約日期:</strong> <?php echo htmlspecialchars($strategy['contract_date']); ?></p>
                                        <p><strong>槓桿倍數:</strong> <?php echo htmlspecialchars($strategy['leverage']); ?>x</p>
                                        <p><strong>營收 (USDT):</strong>
                                            <span style="color: <?php echo $profit_color; ?>"><?php echo $profit; ?></span>
                                        </p>
                                        <p><strong>倉位總價值 (USDT):</strong> <?php echo htmlspecialchars($strategy['amount']); ?></p>
                                        <p><strong>備註:</strong> <?php echo nl2br(htmlspecialchars($strategy['note'])); ?></p>
                                    </div>
                                    <div class="card-footer text-muted">
                                        建立日期: <?php echo htmlspecialchars($strategy['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="tableView" class="d-none">
                        <table id="favoritesTable" class="table table-bordered">
                            <thead>
                            <tr>
                                <th>標題</th>
                                <th>內容</th>
                                <th>相關平台</th>
                                <th>合約日期</th>
                                <th>槓桿倍數</th>
                                <th>營收</th>
                                <th>金額</th>
                                <th>備註</th>
                                <th>建立日期</th>
                                <th>收藏</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($favorites as $strategy): ?>
                                <?php
                                    $content = $strategy['content'];
                                    $profit = floatval($strategy['profit_loss']);
                                    $is_green = ($content === '多單' && $profit > 0) || ($content === '空單' && $profit < 0);
                                    $profit_color = $is_green ? 'green' : 'red';
                                    $side_color = ($content === '多單') ? 'green' : 'red';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($strategy['title']); ?></td>
                                    <td><span style="color: <?php echo $side_color; ?>"><?php echo htmlspecialchars($content); ?></span></td>
                                    <td><?php echo htmlspecialchars($strategy['related_platform']); ?></td>
                                    <td><?php echo htmlspecialchars($strategy['contract_date']); ?></td>
                                    <td><?php echo htmlspecialchars($strategy['leverage']); ?>x</td>
                                    <td><span style="color: <?php echo $profit_color; ?>"><?php echo $profit; ?></span></td>
                                    <td><?php echo htmlspecialchars($strategy['amount']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($strategy['note'])); ?></td>
                                    <td><?php echo htmlspecialchars($strategy['created_at']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-link p-0 favorite-btn" data-strategy-id="<?php echo $strategy['id']; ?>">
                                            <i class="fas fa-heart fa-lg" style="color: red;"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
    document.getElementById('toggleView').addEventListener('click', function () {
        document.getElementById('cardView').classList.toggle('d-none');
        document.getElementById('tableView').classList.toggle('d-none');
    });

    $(document).ready(function () {
        $('#favoritesTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/zh-HANT.json'
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const favoriteButtons = document.querySelectorAll('.favorite-btn');
        favoriteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const strategyId = this.getAttribute('data-strategy-id');
                fetch('toggle_favorite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ strategy_id: strategyId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && !data.is_favorite) {
                        Swal.fire({
                            icon: 'success',
                            title: '取消收藏',
                            text: '已從收藏中移除！',
                            confirmButtonText: '確定'
                        }).then(() => {
                            const card = button.closest('.strategy-card');
                            const row = button.closest('tr');
                            if (card) card.remove();
                            if (row) row.remove();
                            if (
                                document.querySelectorAll('.strategy-card').length === 0 &&
                                document.querySelectorAll('#favoritesTable tbody tr').length === 0
                            ) {
                                location.reload();
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('錯誤：', error);
                    Swal.fire({
                        icon: 'error',
                        title: '操作失敗',
                        text: '請稍後再試。',
                        confirmButtonText: '確定'
                    });
                });
            });
        });
    });
</script>
</body>
</html>
