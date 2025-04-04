<?php
session_start();
require_once 'db_connection.php'; // 資料庫連線

// 未登入導回登入頁
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];

// 取得使用者資料
$stmt = $conn->prepare('SELECT id, username, email, password, created_at FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "查無此使用者";
    exit;
}

$success = '';
$error = '';

// 更新處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newEmail = $_POST['email'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $error = '新密碼與確認密碼不一致。';
    } else {
        $hashedPassword = $user['password']; // 預設不改變密碼
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $updateStmt = $conn->prepare('UPDATE users SET email = ?, password = ? WHERE id = ?');
        $updateStmt->bind_param('ssi', $newEmail, $hashedPassword, $user['id']);

        if ($updateStmt->execute()) {
            $success = '個人資訊已成功更新！';
            $user['email'] = $newEmail;
            $user['password'] = $hashedPassword;
        } else {
            $error = '更新失敗，請稍後再試。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>個人資訊</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <?php include 'navbar.php'; ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container">
                <h1 class="text-center mt-3">個人資訊</h1>
            </div>
        </div>
        <div class="content">
            <div class="container">

                <!-- 訊息提示 -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- 編輯表單 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">編輯個人資訊</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="profile.php">
                            <input type="hidden" name="update_profile" value="1">

                            <div class="mb-3">
                                <label for="username" class="form-label"><strong>帳號:</strong></label>
                                <input type="text" class="form-control" id="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label"><strong>Email:</strong></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><strong>新密碼（選填）:</strong></label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="若要修改密碼請輸入">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label"><strong>確認新密碼:</strong></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="再次輸入新密碼">
                            </div>
                            <button type="submit" class="btn btn-primary">更新資訊</button>
                        </form>
                    </div>
                </div>

                <!-- 前往 API 設定頁 -->
                <div class="mt-4">
                    <a href="api_settings.php" class="btn btn-info">管理 API 設定</a>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
