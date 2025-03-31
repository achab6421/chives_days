<?php
session_start();
require_once 'db_connection.php'; // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}

$username = $_SESSION['user'];

$stmt = $conn->prepare('SELECT s.id, s.title, s.content, s.related_platform, s.contract_date, s.amount, s.note, s.created_at, 
                        (SELECT COUNT(*) FROM favorites f WHERE f.user_id = u.id AND f.strategy_id = s.id) AS is_favorite
                        FROM strategies s 
                        JOIN users u ON s.user_id = u.id 
                        WHERE u.username = ? 
                        ORDER BY s.created_at DESC');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$strategies = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>合約紀錄</title>
    <!-- AdminLTE and Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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
                    <h1 class="text-center">合約紀錄</h1>
                    <div>
                        <button id="toggleView" class="btn btn-primary">切換視圖</button>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addContractModal">新增合約紀錄</button>
                    </div>
                </div>
            </div>
            <!-- Add Contract Modal -->
            <div class="modal fade" id="addContractModal" tabindex="-1" aria-labelledby="addContractModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="add_contract.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addContractModalLabel">新增合約紀錄</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">標題</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="content" class="form-label">內容</label>
                                    <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="related_platform" class="form-label">相關平台</label>
                                    <input type="text" class="form-control" id="related_platform" name="related_platform">
                                </div>
                                <div class="mb-3">
                                    <label for="contract_date" class="form-label">合約日期</label>
                                    <input type="date" class="form-control" id="contract_date" name="contract_date" required>
                                </div>
                                <div class="mb-3">
                                    <label for="amount" class="form-label">金額 (USD)</label>
                                    <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                                </div>
                                <div class="mb-3">
                                    <label for="note" class="form-label">備註</label>
                                    <textarea class="form-control" id="note" name="note" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                <button type="submit" class="btn btn-success">新增</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="content">
                <div class="container" id="viewContainer">
                    <?php if (empty($strategies)): ?>
                        <p class="text-center">目前沒有任何合約紀錄。</p>
                    <?php else: ?>
                        <div id="cardView" class="row">
                            <?php foreach ($strategies as $strategy): ?>
                                <div class="col-md-6 strategy-card">
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($strategy['title']); ?></h5>
                                            <button class="btn btn-link p-0 favorite-btn" data-strategy-id="<?php echo $strategy['id']; ?>">
                                                <i class="fas fa-heart" style="color: <?php echo $strategy['is_favorite'] ? 'red' : 'gray'; ?>;"></i>
                                            </button>
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
                            <table id="contractsTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>標題</th>
                                        <th>內容</th>
                                        <th>相關平台</th>
                                        <th>合約日期</th>
                                        <th>金額 (USD)</th>
                                        <th>備註</th>
                                        <th>建立日期</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($strategies as $strategy): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($strategy['title']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($strategy['content'])); ?></td>
                                            <td><?php echo htmlspecialchars($strategy['related_platform']); ?></td>
                                            <td><?php echo htmlspecialchars($strategy['contract_date']); ?></td>
                                            <td><?php echo htmlspecialchars($strategy['amount']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($strategy['note'])); ?></td>
                                            <td><?php echo htmlspecialchars($strategy['created_at']); ?></td>
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
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        document.getElementById('toggleView').addEventListener('click', function () {
            const cardView = document.getElementById('cardView');
            const tableView = document.getElementById('tableView');
            cardView.classList.toggle('d-none');
            tableView.classList.toggle('d-none');
        });

        $(document).ready(function () {
            $('#contractsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/zh-HANT.json'
                }
            });

            // Check for success parameter in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                Swal.fire({
                    icon: 'success',
                    title: '新增成功',
                    text: '合約紀錄已成功新增！',
                    confirmButtonText: '確定'
                });
            }
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
        });
    </script>
</body>
</html>
