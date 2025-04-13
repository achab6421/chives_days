<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];

$stmt = $conn->prepare('SELECT id, username, email, password, avatar, created_at FROM users WHERE username = ?');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newEmail = $_POST['email'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $hashedPassword = $user['password'];
    $avatar_path = $user['avatar'];

    if (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $error = '新密碼與確認密碼不一致。';
    } else {
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '_' . basename($_FILES['avatar']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                $avatar_path = $targetPath;
            } else {
                $error = '頭像上傳失敗。';
            }
        }

        if (empty($error)) {
            $updateStmt = $conn->prepare('UPDATE users SET email = ?, password = ?, avatar = ? WHERE id = ?');
            $updateStmt->bind_param('sssi', $newEmail, $hashedPassword, $avatar_path, $user['id']);

            if ($updateStmt->execute()) {
                $success = '個人資訊已成功更新！';

                // 重新撈資料確保更新後畫面即時變更
                $stmt = $conn->prepare('SELECT id, username, email, password, avatar, created_at FROM users WHERE username = ?');
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error = '更新失敗，請稍後再試。';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<meta name="viewport" content="width=device-width, initial-scale=1">
<head>
    <meta charset="UTF-8">
    <title>個人資訊</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .avatar-circle {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            background-color: #f0f0f0;
            cursor: pointer;
            transition: 0.3s;
        }
        .avatar-circle:hover {
            transform: scale(1.05);
            opacity: 0.85;
        }
    </style>
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
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">編輯個人資訊</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="profile.php" enctype="multipart/form-data">
                            <input type="hidden" name="update_profile" value="1">

                            <!-- 頭像預覽與上傳 -->
                            <div class="mb-3 text-center">
                                <label for="avatar">
                                    <img id="avatar-preview"
                                         src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'images/default-avatar.png'; ?>"
                                         alt="頭像預覽"
                                         class="avatar-circle"
                                         title="點我上傳頭像">
                                </label>
                                <input type="file" class="d-none" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(event)">
                            </div>

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
<script>
function previewAvatar(event) {
    const reader = new FileReader();
    reader.onload = function(){
        document.getElementById('avatar-preview').src = reader.result;
    };
    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}
</script>
</body>
</html>
