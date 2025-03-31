<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="index.php"><b>韭菜</b>日常</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">儀表板</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">個人資訊</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contracts.php">合約紀錄</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="favorites.php">收藏</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        欄位設定
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="settingsDropdown">
                        <li><a class="dropdown-item" href="exchange_settings.php">交易所欄位設定</a></li>
                        <li><a class="dropdown-item" href="currency_settings.php">幣種設定</a></li>
                        <li><a class="dropdown-item" href="homepage_settings.php">設定首頁形象</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">登出</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
