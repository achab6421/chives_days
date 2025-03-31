<?php
session_start();
require_once 'db_connection.php'; // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}

$username = $_SESSION['user'];
$success = $error = '';

// Handle form submission for updating user information
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newEmail = $_POST['email'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Update email and password in the database
        $stmt = $conn->prepare('UPDATE users SET email = ?, password = ? WHERE username = ?');
        $hashedPassword = !empty($newPassword) ? password_hash($newPassword, PASSWORD_BCRYPT) : $user['password'];
        $stmt->bind_param('sss', $newEmail, $hashedPassword, $username);

        if ($stmt->execute()) {
            $success = '個人資訊已成功更新！';
        } else {
            $error = '更新失敗，請稍後再試。';
        }
    }
}

// Fetch user information from the database
$stmt = $conn->prepare('SELECT username, email, created_at FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人資訊</title>
    <!-- AdminLTE and Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="hold-transition layout-top-nav">
    <div class="wrapper">
        <?php include 'navbar.php'; ?>
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container">
                    <h1 class="text-center">個人資訊</h1>
                </div>
            </div>
            <div class="content">
                <div class="container">
                    <div class="card">
                        <div class="card-body">
                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            <form method="POST" action="profile.php">
                                <div class="mb-3">
                                    <label for="username" class="form-label"><strong>帳號:</strong></label>
                                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label"><strong>Email:</strong></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label"><strong>新密碼 (選填):</strong></label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="輸入新密碼">
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label"><strong>確認新密碼:</strong></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="再次輸入新密碼">
                                </div>
                                <button type="submit" class="btn btn-primary">更新資訊</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AdminLTE and Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
