<?php
// 獲取當前頁面檔案名稱
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="index.php"><b>韭菜</b>日常</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active fw-bold' : ''; ?>" href="dashboard.php">儀表板</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active fw-bold' : ''; ?>" href="profile.php">個人資訊</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'contracts.php' ? 'active fw-bold' : ''; ?>" href="contracts.php">合約紀錄</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'my_assets.php' ? 'active fw-bold' : ''; ?>" href="my_assets.php">我的資產</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'favorites.php' ? 'active fw-bold' : ''; ?>" href="favorites.php">收藏</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['exchange_settings.php', 'currency_settings.php', 'homepage_settings.php', 'tags_settings.php']) ? 'active fw-bold' : ''; ?>" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        欄位設定
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="settingsDropdown">
                        <li><a class="dropdown-item <?php echo $current_page == 'exchange_settings.php' ? 'active fw-bold' : ''; ?>" href="exchange_settings.php">交易所欄位設定</a></li>
                        <li><a class="dropdown-item <?php echo $current_page == 'currency_settings.php' ? 'active fw-bold' : ''; ?>" href="currency_settings.php">幣種設定</a></li>
                        <li><a class="dropdown-item <?php echo $current_page == 'homepage_settings.php' ? 'active fw-bold' : ''; ?>" href="homepage_settings.php">設定首頁形象</a></li>
                        <li><a class="dropdown-item <?php echo $current_page == 'tags_settings.php' ? 'active fw-bold' : ''; ?>" href="tags_settings.php">標籤設定</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'logout.php' ? 'active fw-bold' : ''; ?>" href="logout.php">登出</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
