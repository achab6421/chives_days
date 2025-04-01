<?php
session_start();
require_once 'db_connection.php'; // 引入資料庫連線

// 檢查用戶是否登入
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // 若未登入則重定向到登入頁
    exit;
}

// 獲取用戶ID
$username = $_SESSION['user'];
$stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];
$stmt->close();

// 獲取已收藏的合約記錄
$stmt = $conn->prepare('SELECT s.id, s.title, s.content, s.related_platform, s.contract_date, s.amount, s.note, s.created_at, 
                        1 AS is_favorite
                        FROM strategies s 
                        JOIN favorites f ON s.id = f.strategy_id
                        WHERE f.user_id = ? 
                        ORDER BY s.created_at DESC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$favorites = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的收藏</title>
    <!-- AdminLTE and Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .list-view .card {
            display: flex;
            flex-direction: row;
        }
        .list-view .card-body {
            flex: 1;
        }
        .list-view .card-header, .list-view .card-footer {
            flex: 0 0 auto;
        }
    </style>
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
                                <div class="col-md-6 strategy-card">
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <button class="btn btn-link p-0 favorite-btn" data-strategy-id="<?php echo $strategy['id']; ?>">
                                                <i class="fas fa-heart fa-2x" aria-hidden="true" style="color: red;"></i>
                                            </button>
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($strategy['title']); ?></h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars($strategy['content'])); ?></p>
                                            <p><strong>相關平台:</strong> <?php echo htmlspecialchars($strategy['related_platform']); ?></p>
                                            <p><strong>合約日期:</strong> <?php echo htmlspecialchars($strategy['contract_date']); ?></p>
                                            <p><strong>金額:</strong> <?php echo htmlspecialchars($strategy['amount']); ?> USD</p>
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
                                        <th>金額 (USD)</th>
                                        <th>備註</th>
                                        <th>建立日期</th>
                                        <th>收藏</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($favorites as $strategy): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($strategy['title']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($strategy['content'])); ?></td>
                                            <td><?php echo htmlspecialchars($strategy['related_platform']); ?></td>
                                            <td><?php echo htmlspecialchars($strategy['contract_date']); ?></td>
                                            <td><?php echo htmlspecialchars($strategy['amount']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($strategy['note'])); ?></td>
                                            <td><?php echo htmlspecialchars($strategy['created_at']); ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-link p-0 favorite-btn" data-strategy-id="<?php echo $strategy['id']; ?>">
                                                    <i class="fas fa-heart fa-lg" aria-hidden="true" style="color: red;"></i>
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

    <!-- AdminLTE, Bootstrap 5, and DataTables JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.getElementById('toggleView').addEventListener('click', function () {
            const cardView = document.getElementById('cardView');
            const tableView = document.getElementById('tableView');
            cardView.classList.toggle('d-none');
            tableView.classList.toggle('d-none');
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
                    const heartIcon = this.querySelector('i');

                    fetch('toggle_favorite.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ strategy_id: strategyId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // 如果成功取消收藏，從頁面移除此條目
                            if (!data.is_favorite) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '取消收藏',
                                    text: '已從收藏中移除!',
                                    confirmButtonText: '確定'
                                }).then(() => {
                                    // 找到包含此按鈕的卡片或表格行並移除
                                    const card = button.closest('.strategy-card');
                                    const row = button.closest('tr');
                                    
                                    if (card) card.remove();
                                    if (row) row.remove();
                                    
                                    // 檢查是否已沒有收藏項目
                                    if (document.querySelectorAll('.strategy-card').length === 0 && 
                                        document.querySelectorAll('#favoritesTable tbody tr').length === 0) {
                                        location.reload(); // 重新載入頁面顯示空狀態
                                    }
                                });
                            }
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
        });
    </script>
</body>
</html>
