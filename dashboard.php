<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

// Fetch fresh user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    redirect('login.php');
}

// Role metadata
$roles = [
    'Recruit'  => ['icon' => '🪖', 'color' => '#9ca3af', 'next' => 'Soldier',  'order' => 1],
    'Soldier'  => ['icon' => '⚔',  'color' => '#60a5fa', 'next' => 'Captain',  'order' => 2],
    'Captain'  => ['icon' => '🛡',  'color' => '#a78bfa', 'next' => 'General',  'order' => 3],
    'General'  => ['icon' => '⭐',  'color' => '#fbbf24', 'next' => 'Leader',   'order' => 4],
    'Leader'   => ['icon' => '👑',  'color' => '#f97316', 'next' => null,       'order' => 5],
];

$currentRole = $roles[$user['role']] ?? $roles['Recruit'];
$roleOrder   = $currentRole['order'];
$totalRoles  = count($roles);
$progress    = ($roleOrder / $totalRoles) * 100;

// Format fame
function formatFame(int $n): string {
    if ($n >= 1_000_000_000) return round($n / 1_000_000_000, 2) . 'B';
    if ($n >= 1_000_000)     return round($n / 1_000_000, 2)     . 'M';
    if ($n >= 1_000)         return round($n / 1_000, 1)         . 'K';
    return number_format($n);
}

$flash = get_flash();

// ── Profit stats by category ──
$profitStats = ['total' => 0, 'flipping' => 0, 'crafting' => 0, 'refining' => 0, 'transport' => 0];
$pStmt = $conn->prepare("SELECT type, SUM(profit) AS total FROM profits WHERE user_id = ? GROUP BY type");
$pStmt->bind_param('i', $user['id']);
$pStmt->execute();
$pResult = $pStmt->get_result();
while ($row = $pResult->fetch_assoc()) {
    $type = strtolower($row['type']);
    if (isset($profitStats[$type])) {
        $profitStats[$type] = (int)$row['total'];
    }
    $profitStats['total'] += (int)$row['total'];
}

$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>War Room — <?= htmlspecialchars($user['username']) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
</head>
<body class="dashboard-page">

<canvas id="particles"></canvas>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<?php require_once 'navbar.php'; ?>

<main class="dashboard-main">

    <!-- Flash messages -->
    <?php if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] ?> flash-top">
        <span class="flash-icon"><?= $flash['type'] === 'success' ? '✓' : '⚠' ?></span>
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- ═══ HERO BANNER ═══ -->
    <section class="hero-banner">
        <div class="hero-bg-grid"></div>
        <div class="hero-content">
            <div class="hero-avatar">
                <div class="avatar-ring"></div>
                <div class="avatar-inner"><?= $currentRole['icon'] ?></div>
            </div>
            <div class="hero-text">
                <h1 class="hero-name"><?= htmlspecialchars($user['username']) ?></h1>
                <div class="hero-meta">
                    <span class="hero-guild">🏰 <?= htmlspecialchars($user['guild_name']) ?></span>
                    <span class="hero-sep">·</span>
                    <span class="hero-date">Joined <?= date('M Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ STAT CARDS ═══ -->
    <section class="stats-grid">

        <div class="stat-card" data-delay="0">
            <div class="stat-icon">✨</div>
            <div class="stat-body">
                <div class="stat-label">Total Fame</div>
                <div class="stat-value fame-counter" data-target="<?= $user['total_fame'] ?>">
                    <?= formatFame((int)$user['total_fame']) ?>
                </div>
                <div class="stat-sub"><?= number_format($user['total_fame']) ?> pts</div>
            </div>
            <div class="stat-glow" style="--glow-color: #fbbf2455"></div>
        </div>

        <div class="stat-card" data-delay="1">
            <div class="stat-icon"><?= $currentRole['icon'] ?></div>
            <div class="stat-body">
                <div class="stat-label">Current Rank</div>
                <div class="stat-value" style="color: <?= $currentRole['color'] ?>">
                    <?= htmlspecialchars($user['role']) ?>
                </div>
                <div class="stat-sub">Rank <?= $roleOrder ?> of <?= $totalRoles ?></div>
            </div>
            <div class="stat-glow" style="--glow-color: <?= $currentRole['color'] ?>55"></div>
        </div>

        <div class="stat-card" data-delay="2">
            <div class="stat-icon">🏰</div>
            <div class="stat-body">
                <div class="stat-label">Guild</div>
                <div class="stat-value guild-name"><?= htmlspecialchars($user['guild_name']) ?></div>
                <div class="stat-sub">Active Member</div>
            </div>
            <div class="stat-glow" style="--glow-color: #a78bfa55"></div>
        </div>

        <div class="stat-card" data-delay="3">
            <div class="stat-icon">📅</div>
            <div class="stat-body">
                <div class="stat-label">Days Active</div>
                <div class="stat-value">
                    <?= max(1, (int)((time() - strtotime($user['created_at'])) / 86400)) ?>
                </div>
                <div class="stat-sub">Days in the realm</div>
            </div>
            <div class="stat-glow" style="--glow-color: #34d39955"></div>
        </div>

    </section>

    <!-- ═══ PROFIT OVERVIEW ═══ -->
    <section class="profit-overview">
        <h2 class="section-title"><span>💰</span> Profit Overview <span>💰</span></h2>
        <div class="profit-grid">

            <div class="profit-card profit-total">
                <div class="profit-card-icon">💰</div>
                <div class="profit-card-body">
                    <div class="profit-card-label">Total Profit</div>
                    <div class="profit-card-value <?= $profitStats['total'] >= 0 ? 'profit-pos' : 'profit-neg' ?>">
                        <?= ($profitStats['total'] >= 0 ? '+' : '') . formatFame($profitStats['total']) ?>
                    </div>
                </div>
                <div class="profit-card-badge">ALL TIME</div>
            </div>

            <?php
            $categories = [
                'flipping'  => ['icon' => '🔄', 'label' => 'Flipping'],
                'crafting'  => ['icon' => '🔨', 'label' => 'Crafting'],
                'refining'  => ['icon' => '⚗',  'label' => 'Refining'],
                'transport' => ['icon' => '🚚', 'label' => 'Transport'],
            ];
            foreach ($categories as $key => $cat):
                $val = $profitStats[$key];
            ?>
            <div class="profit-card">
                <div class="profit-card-icon"><?= $cat['icon'] ?></div>
                <div class="profit-card-body">
                    <div class="profit-card-label"><?= $cat['label'] ?></div>
                    <div class="profit-card-value <?= $val > 0 ? 'profit-pos' : ($val < 0 ? 'profit-neg' : 'profit-zero') ?>">
                        <?= ($val >= 0 ? '+' : '') . formatFame($val) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
        <div class="profit-cta">
            <a href="flip.php" class="btn-secondary">
                <span>🔄</span> Open Flip Calculator
            </a>
            <a href="trades.php" class="btn-secondary btn-secondary-dim">
                <span>📜</span> View All Trades
            </a>
        </div>
    </section>


    <!-- ═══ RANK PROGRESSION ═══ -->
    <section class="rank-section">
        <h2 class="section-title"><span>⚔</span> Rank Progression <span>⚔</span></h2>

        <div class="rank-track">
            <?php foreach ($roles as $name => $meta): ?>
            <div class="rank-node <?= $meta['order'] <= $roleOrder ? 'achieved' : '' ?> <?= $name === $user['role'] ? 'current' : '' ?>">
                <div class="rank-bubble" style="<?= $meta['order'] <= $roleOrder ? '--node-color:' . $meta['color'] : '' ?>">
                    <?= $meta['icon'] ?>
                </div>
                <span class="rank-label"><?= $name ?></span>
                <?php if ($meta['order'] < $totalRoles): ?>
                <div class="rank-connector <?= $meta['order'] < $roleOrder ? 'filled' : '' ?>"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="rank-progress-bar">
            <div class="rank-progress-fill" style="width: <?= $progress ?>%">
                <div class="progress-shine"></div>
            </div>
            <span class="rank-progress-label"><?= round($progress) ?>% to Leader</span>
        </div>
    </section>

    <!-- ═══ PROFILE DETAILS TABLE ═══ -->
    <section class="profile-section">
        <h2 class="section-title"><span>📜</span> Warrior Profile <span>📜</span></h2>

        <div class="profile-card">
            <table class="profile-table">
                <tbody>
                    <tr>
                        <td class="td-label">👤 Username</td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">✉ Email</td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">🏰 Guild</td>
                        <td><?= htmlspecialchars($user['guild_name']) ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">✨ Total Fame</td>
                        <td class="gold"><?= number_format($user['total_fame']) ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">🎖 Rank</td>
                        <td style="color: <?= $currentRole['color'] ?>"><?= htmlspecialchars($user['role']) ?></td>
                    </tr>
                    <tr>
                        <td class="td-label">📅 Joined</td>
                        <td><?= date('F j, Y', strtotime($user['created_at'])) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ═══ MILITARY RANKS GUIDE ═══ -->
    <section class="ranks-guide">
        <h2 class="section-title"><span>🎖</span> Military Ranks <span>🎖</span></h2>
        <div class="ranks-grid">
            <?php foreach ($roles as $name => $meta): ?>
            <div class="rank-card <?= $name === $user['role'] ? 'rank-current' : '' ?>">
                <div class="rank-card-icon" style="color: <?= $meta['color'] ?>"><?= $meta['icon'] ?></div>
                <div class="rank-card-name"><?= $name ?></div>
                <div class="rank-card-order">Tier <?= $meta['order'] ?></div>
                <?php if ($name === $user['role']): ?>
                <div class="rank-card-badge">YOUR RANK</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

</main>

<footer class="site-footer">
    <p>⚔ Albion Guild Tracker &copy; <?= date('Y') ?> · All Rights Reserved ⚔</p>
</footer>

<script src="assets/js/script.js"></script>
<script>
// Animate fame counter on load
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.fame-counter').forEach(el => {
        const target = parseInt(el.dataset.target, 10);
        if (isNaN(target)) return;
        let start = 0;
        const duration = 1800;
        const step = timestamp => {
            if (!start) start = timestamp;
            const progress = Math.min((timestamp - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(eased * target);
            el.textContent = formatNum(current);
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = formatNum(target);
        };
        requestAnimationFrame(step);
    });

    function formatNum(n) {
        if (n >= 1e9) return (n / 1e9).toFixed(2) + 'B';
        if (n >= 1e6) return (n / 1e6).toFixed(2) + 'M';
        if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
        return n.toLocaleString();
    }
});
</script>
</body>
</html>
