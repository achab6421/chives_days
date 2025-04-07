<?php
session_start();
require_once 'db_connection.php';

// 檢查用戶是否登入
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $strategyId = $_POST['strategy_id'] ?? 0;
    $tagId = $_POST['tag_id'] ?? 0;

    // 確保策略 ID 和標籤 ID 都有效
    if (!empty($strategyId) && !empty($tagId)) {
        // 檢查標籤是否已經存在於該策略中
        $stmt = $conn->prepare('SELECT COUNT(*) FROM strategy_tags WHERE strategy_id = ? AND tag_id = ?');
        $stmt->bind_param('ii', $strategyId, $tagId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close(); // 確保關閉語句以避免同步錯誤

        if ($count == 0) {
            // 插入到 strategy_tags 表中
            $stmt = $conn->prepare('INSERT INTO strategy_tags (strategy_id, tag_id) VALUES (?, ?)');
            $stmt->bind_param('ii', $strategyId, $tagId);
            $stmt->execute();
            $stmt->close(); // 確保關閉語句
        }
    }
}

// 返回到上一頁
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;