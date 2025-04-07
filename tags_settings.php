<?php
session_start();
require_once 'db_connection.php';

// 檢查用戶是否登入
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// 處理新增標籤請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $tagName = $_POST['name'] ?? '';
    if (!empty($tagName)) {
        $stmt = $conn->prepare('INSERT INTO tags (name) VALUES (?)');
        $stmt->bind_param('s', $tagName);
        $stmt->execute();
    }
    header('Location: tags_settings.php');
    exit;
}

// 處理更新標籤請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $tagId = $_POST['id'] ?? 0;
    $tagName = $_POST['name'] ?? '';
    if (!empty($tagId) && !empty($tagName)) {
        $stmt = $conn->prepare('UPDATE tags SET name = ? WHERE id = ?');
        $stmt->bind_param('si', $tagName, $tagId);
        $stmt->execute();
    }
    header('Location: tags_settings.php');
    exit;
}

// 處理刪除標籤請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $tagId = $_POST['id'] ?? 0;
    if (!empty($tagId)) {
        $stmt = $conn->prepare('DELETE FROM tags WHERE id = ?');
        $stmt->bind_param('i', $tagId);
        $stmt->execute();
    }
    header('Location: tags_settings.php');
    exit;
}

// 獲取所有標籤
$result = $conn->query('SELECT * FROM tags ORDER BY id ASC');
$tags = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>標籤設定</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="wrapper">
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <h1 class="mb-4">標籤設定</h1>

        <!-- 新增標籤表單 -->
        <form method="POST" class="mb-4">
            <input type="hidden" name="action" value="create">
            <div class="input-group">
                <input type="text" name="name" class="form-control" placeholder="新增標籤名稱" required>
                <button type="submit" class="btn btn-primary">新增</button>
            </div>
        </form>

        <!-- 標籤列表 -->
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>ID</th>
                <th>名稱</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tags as $tag): ?>
                <tr>
                    <td><?= htmlspecialchars($tag['id']) ?></td>
                    <td><?= htmlspecialchars($tag['name']) ?></td>
                    <td>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#editTagModal" data-id="<?= htmlspecialchars($tag['id']) ?>" data-name="<?= htmlspecialchars($tag['name']) ?>">編輯</button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('確定要刪除這個標籤嗎？');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($tag['id']) ?>">
                            <button type="submit" class="btn btn-danger btn-sm">刪除</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 編輯標籤 Modal -->
    <div class="modal fade" id="editTagModal" tabindex="-1" aria-labelledby="editTagModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTagModalLabel">編輯標籤</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editTagId">
                        <div class="mb-3">
                            <label for="editTagName" class="form-label">標籤名稱</label>
                            <input type="text" class="form-control" name="name" id="editTagName" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">儲存變更</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editTagModal = document.getElementById('editTagModal');
    editTagModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const tagId = button.getAttribute('data-id');
        const tagName = button.getAttribute('data-name');

        const modalIdInput = editTagModal.querySelector('#editTagId');
        const modalNameInput = editTagModal.querySelector('#editTagName');

        modalIdInput.value = tagId;
        modalNameInput.value = tagName;
    });

    // 初始化所有 select 元素為 Select2
    $('select').select2();
});
</script>
</body>
</html>