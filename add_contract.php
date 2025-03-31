<?php
session_start();
require_once 'db_connection.php'; // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id']; // Assuming user_id is stored in the session
    $title = $_POST['title'];
    $content = $_POST['content'];
    $related_platform = $_POST['related_platform'];
    $contract_date = $_POST['contract_date'];
    $amount = $_POST['amount'];
    $note = $_POST['note'];

    $stmt = $conn->prepare('INSERT INTO strategies (user_id, title, content, related_platform, contract_date, amount, note) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssds', $user_id, $title, $content, $related_platform, $contract_date, $amount, $note);

    if ($stmt->execute()) {
        header('Location: contracts.php?success=1'); // Redirect with success parameter
    } else {
        header('Location: contracts.php?error=1'); // Redirect with error parameter
    }
    exit;
}
?>
