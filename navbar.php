<?php
// navbar.php — shared navigation include
// Requires: $user (array), $currentRole (array), $activePage (string)
// Called after config.php and user fetch.
?>
<nav class="topnav">
    <div class="nav-brand">
        <span class="nav-icon">⚔</span>
        <span class="nav-title">Albion Guild Tracker</span>
    </div>

    <div class="nav-links">
        <a href="dashboard.php"
           class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <span>🏠</span><span class="nav-link-text">Dashboard</span>
        </a>
        <a href="flip.php"
           class="nav-link <?= ($activePage ?? '') === 'flip' ? 'active' : '' ?>">
            <span>🔄</span><span class="nav-link-text">Flip Calc</span>
        </a>
        <a href="trades.php"
           class="nav-link <?= ($activePage ?? '') === 'trades' ? 'active' : '' ?>">
            <span>📜</span><span class="nav-link-text">My Trades</span>
        </a>
    </div>

    <div class="nav-user">
        <span class="nav-username">
            <?= $currentRole['icon'] ?> <?= htmlspecialchars($user['username']) ?>
        </span>
        <a href="logout.php" class="btn-logout">
            <span>⏻</span> <span class="nav-link-text">Logout</span>
        </a>
    </div>
</nav>
