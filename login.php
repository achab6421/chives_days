<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $password_hash);
        $stmt->fetch();

        if (password_verify($password, $password_hash)) {
            $_SESSION['user'] = $username;
            $_SESSION['user_id'] = $id;
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '登入成功',
                        text: '歡迎回來，$username',
                        confirmButtonText: '確定'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                });
            </script>";
        } else {
            $error = '錯誤的帳號或密碼';
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: '登入失敗',
                        text: '帳號或密碼錯誤，請再試一次。',
                        confirmButtonText: '確定'
                    });
                });
            </script>";
        }
    } else {
        $error = '錯誤的帳號或密碼';
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: '登入失敗',
                    text: '帳號或密碼錯誤，請再試一次。',
                    confirmButtonText: '確定'
                });
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>韭菜日常｜登入</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- AdminLTE + Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* 🔽 首頁形象圖樣式 */
        .hero-section {
            background-image: url('assets/banner1.jpg'); /* 預設第一張 */
            background-size: cover;
            background-position: center;
            height: 250px;
            position: relative;
            color: white;
            transition: background-image 0.5s ease-in-out;
        }
        .hero-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
        }
    </style>
</head>
<body class="hold-transition login-page" style="background-color: #f4f6f9;">

<!-- 🔽 首頁形象主視覺 -->
<div class="container mt-4 mb-3">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="hero-section rounded shadow">
                <div class="hero-overlay">
                    <h1 class="display-5 fw-bold">韭菜日常</h1>
                    <p class="lead">讓你的交易紀錄與資產更有條理</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 登入框 -->
<div class="login-box mt-2">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <a href="#" class="h1" style="text-decoration: none; color: #007bff;"><b>韭菜</b>日常</a>
        </div>
        <div class="card-body">
            <p class="login-box-msg">登入開始成為韭菜</p>

            <form method="POST" action="login.php">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="帳號" name="username" required>
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-user"></span></div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" placeholder="密碼" name="password" required>
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger text-center">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-6">
                        <button type="submit" class="btn btn-primary btn-block">登入</button>
                    </div>
                    <div class="col-6">
                        <a href="register.php" class="btn btn-secondary btn-block">註冊</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- 每秒切換圖片 -->
<script>
    const images = [
        'assets/banner1.jpg',
        'assets/banner2.jpg',
        'assets/banner3.jpg',
        
    ];
    let index = 0;

    setInterval(() => {
        index = (index + 1) % images.length;
        document.querySelector('.hero-section').style.backgroundImage = `url('${images[index]}')`;
    }, 10000); // 每10秒切換一次
</script>
</body>
</html>
