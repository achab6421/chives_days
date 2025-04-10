<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$username = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>首頁</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap + AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
    html, body {
        margin: 0;
        padding: 0;
        height: 100%;
        overflow-x: hidden;
    }
    .hero-banner {
        position: relative;
        height: 100vh;
        width: 100%;
        background-size: cover;
        background-position: center;
        transition: background-image 1s ease-in-out;
    }
    .banner-overlay-text {
        position: absolute;
        top: 15%; /* 往上移 */
        left: 50%;
        transform: translateX(-50%);
    
        
        color: white;
        font-size: 3rem;
        font-weight: bold;
        text-shadow: 2px 2px 6px rgba(0,0,0,0.6);
        z-index: 10;
        text-align: center;
    }
    .banner-controls {
        position: absolute;
        bottom: 40px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 11;
        display: flex;
        gap: 20px;
    }
    .banner-controls button {
        font-size: 1rem;
        padding: 6px 20px;
    }
</style>

</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <?php include 'navbar.php'; ?>

    <!-- 全螢幕背景圖片區塊 -->
    <div class="hero-banner" id="heroBanner">
        <div class="banner-overlay-text">
            賺錢了嗎！, <?php echo htmlspecialchars($username); ?>!
        </div>
        <div class="banner-controls">
            <button class="btn btn-light btn-sm" onclick="prevBanner()">⬅️ 上一張</button>
            <button class="btn btn-light btn-sm" onclick="nextBanner()">下一張 ➡️</button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
const bannerImages = [
    "assets/chives1.png",
    "assets/chives2.png",
    "assets/chives3.png",
    "assets/chives4.png",
    "assets/chives5.png"
];

let currentBanner = 0;
const bannerEl = document.getElementById("heroBanner");

function showBanner(index) {
    bannerEl.style.backgroundImage = `url('${bannerImages[index]}')`;
}

function nextBanner() {
    currentBanner = (currentBanner + 1) % bannerImages.length;
    showBanner(currentBanner);
}

function prevBanner() {
    currentBanner = (currentBanner - 1 + bannerImages.length) % bannerImages.length;
    showBanner(currentBanner);
}

showBanner(currentBanner);
setInterval(nextBanner, 30000); // 每 30 秒切換
</script>
</body>
</html>
