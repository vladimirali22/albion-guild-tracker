<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) { session_destroy(); redirect('login.php'); }

$roles = [
    'Recruit'  => ['icon' => '🪖', 'color' => '#9ca3af', 'order' => 1],
    'Soldier'  => ['icon' => '⚔',  'color' => '#60a5fa', 'order' => 2],
    'Captain'  => ['icon' => '🛡',  'color' => '#a78bfa', 'order' => 3],
    'General'  => ['icon' => '⭐',  'color' => '#fbbf24', 'order' => 4],
    'Leader'   => ['icon' => '👑',  'color' => '#f97316', 'order' => 5],
];
$currentRole = $roles[$user['role']] ?? $roles['Recruit'];

// ── Filter params ──
$filterItem   = trim($_GET['item']   ?? '');
$filterType   = trim($_GET['type']   ?? '');
$filterProfit = trim($_GET['profit'] ?? '');   // positive | negative | all
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

// ── Build WHERE ──
$where  = "WHERE user_id = ?";
$params = [$_SESSION['user_id']];
$types  = 'i';

if ($filterItem !== '') {
    $where  .= " AND item_name LIKE ?";
    $params[] = "%{$filterItem}%";
    $types  .= 's';
}
if ($filterType !== '' && in_array($filterType, ['flipping','crafting','refining','transport'])) {
    $where  .= " AND type = ?";
    $params[] = $filterType;
    $types  .= 's';
}
if ($filterProfit === 'positive') {
    $where .= " AND profit > 0";
} elseif ($filterProfit === 'negative') {
    $where .= " AND profit < 0";
}

// ── Count ──
$countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM profits {$where}");
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows  = (int)$countStmt->get_result()->fetch_assoc()['c'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// ── Fetch trades ──
$params[] = $perPage;
$params[] = $offset;
$typesLimit = $types . 'ii';

$tradesStmt = $conn->prepare("
    SELECT * FROM profits {$where}
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$tradesStmt->bind_param($typesLimit, ...$params);
$tradesStmt->execute();
$trades = $tradesStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Summary stats ──
$summaryStmt = $conn->prepare("
    SELECT
        COUNT(*)       AS trade_count,
        SUM(profit)    AS total_profit,
        MAX(profit)    AS best_trade,
        MIN(profit)    AS worst_trade,
        AVG(profit)    AS avg_profit
    FROM profits WHERE user_id = ?
");
$summaryStmt->bind_param('i', $_SESSION['user_id']);
$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();

function fmtSilver(int $n): string {
    $abs  = abs($n);
    $sign = $n < 0 ? '-' : ($n > 0 ? '+' : '');
    if ($abs >= 1_000_000_000) return $sign . round($abs / 1_000_000_000, 2) . 'B';
    if ($abs >= 1_000_000)     return $sign . round($abs / 1_000_000, 2)     . 'M';
    if ($abs >= 1_000)         return $sign . round($abs / 1_000, 1)         . 'K';
    return $sign . number_format($abs);
}

$activePage = 'trades';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Trades — Albion Guild Tracker</title>
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

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title"><span class="page-title-icon">📜</span> My Trades</h1>
            <p class="page-subtitle">Your complete flipping & trading history.</p>
        </div>
        <a href="flip.php" class="btn-secondary btn-sm">
            <span>🔄</span> New Calculation
        </a>
    </div>

    <!-- Summary Stats -->
    <section class="trades-summary">
        <div class="trades-summary-grid">
            <div class="ts-card">
                <div class="ts-icon">📊</div>
                <div class="ts-body">
                    <div class="ts-label">Total Trades</div>
                    <div class="ts-value"><?= number_format((int)$summary['trade_count']) ?></div>
                </div>
            </div>
            <div class="ts-card">
                <div class="ts-icon">💰</div>
                <div class="ts-body">
                    <div class="ts-label">Total Profit</div>
                    <div class="ts-value <?= (int)$summary['total_profit'] >= 0 ? 'profit-pos' : 'profit-neg' ?>">
                        <?= fmtSilver((int)$summary['total_profit']) ?>
                    </div>
                </div>
            </div>
            <div class="ts-card">
                <div class="ts-icon">🏆</div>
                <div class="ts-body">
                    <div class="ts-label">Best Trade</div>
                    <div class="ts-value profit-pos"><?= fmtSilver((int)$summary['best_trade']) ?></div>
                </div>
            </div>
            <div class="ts-card">
                <div class="ts-icon">📉</div>
                <div class="ts-body">
                    <div class="ts-label">Worst Trade</div>
                    <div class="ts-value profit-neg"><?= fmtSilver((int)$summary['worst_trade']) ?></div>
                </div>
            </div>
            <div class="ts-card">
                <div class="ts-icon">⚖</div>
                <div class="ts-body">
                    <div class="ts-label">Avg. Profit</div>
                    <div class="ts-value <?= (float)$summary['avg_profit'] >= 0 ? 'profit-pos' : 'profit-neg' ?>">
                        <?= fmtSilver((int)round($summary['avg_profit'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="trades-filters-section">
        <form method="GET" action="trades.php" class="trades-filter-form" id="filterForm">
            <div class="filter-group">
                <label>🔍 Item Name</label>
                <input type="text" name="item" placeholder="Search item…"
                       value="<?= htmlspecialchars($filterItem) ?>">
            </div>
            <div class="filter-group">
                <label>🗂 Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="flipping"  <?= $filterType==='flipping'  ? 'selected':'' ?>>🔄 Flipping</option>
                    <option value="crafting"  <?= $filterType==='crafting'  ? 'selected':'' ?>>🔨 Crafting</option>
                    <option value="refining"  <?= $filterType==='refining'  ? 'selected':'' ?>>⚗ Refining</option>
                    <option value="transport" <?= $filterType==='transport' ? 'selected':'' ?>>🚚 Transport</option>
                </select>
            </div>
            <div class="filter-group">
                <label>💰 Profit</label>
                <select name="profit" class="form-select">
                    <option value="">All</option>
                    <option value="positive" <?= $filterProfit==='positive' ? 'selected':'' ?>>✅ Positive Only</option>
                    <option value="negative" <?= $filterProfit==='negative' ? 'selected':'' ?>>❌ Negative Only</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary btn-sm">
                    <span class="btn-text">Apply</span><span class="btn-shine"></span>
                </button>
                <?php if ($filterItem || $filterType || $filterProfit): ?>
                <a href="trades.php" class="btn-ghost btn-sm">✕ Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- Trades Table -->
    <section class="trades-table-section">
        <div class="table-header-row">
            <span class="table-count"><?= number_format($totalRows) ?> trade<?= $totalRows !== 1 ? 's' : '' ?> found</span>
        </div>

        <?php if (empty($trades)): ?>
        <div class="no-trades-big">
            <div class="no-trades-icon">⚔</div>
            <h3>No Trades Found</h3>
            <p>
                <?php if ($filterItem || $filterType || $filterProfit): ?>
                    No trades match your filters. <a href="trades.php" class="gold-link">Clear filters</a>
                <?php else: ?>
                    You haven't saved any trades yet.<br>
                    <a href="flip.php" class="gold-link">Calculate your first flip →</a>
                <?php endif; ?>
            </p>
        </div>
        <?php else: ?>

        <div class="trades-table-wrap">
            <table class="trades-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Tier</th>
                        <th>Type</th>
                        <th>Buy Price</th>
                        <th>Sell Price</th>
                        <th>Qty</th>
                        <th>Profit</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trades as $trade):
                        $profitClass = (int)$trade['profit'] > 0 ? 'profit-pos' : ((int)$trade['profit'] < 0 ? 'profit-neg' : 'profit-zero');
                        $typeIcons = ['flipping'=>'🔄','crafting'=>'🔨','refining'=>'⚗','transport'=>'🚚'];
                        $typeIcon  = $typeIcons[$trade['type']] ?? '📋';
                    ?>
                    <tr class="trade-row">
                        <td class="trade-item-name"><?= htmlspecialchars($trade['item_name']) ?></td>
                        <td>
                            <span class="tier-pill tier-<?= strtolower($trade['tier']) ?>">
                                <?= htmlspecialchars($trade['tier']) ?>.<?= (int)$trade['enchant'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="type-pill">
                                <?= $typeIcon ?> <?= ucfirst(htmlspecialchars($trade['type'])) ?>
                            </span>
                        </td>
                        <td class="td-number"><?= number_format((int)$trade['buy_price']) ?></td>
                        <td class="td-number"><?= number_format((int)$trade['sell_price']) ?></td>
                        <td class="td-number"><?= number_format((int)$trade['quantity']) ?></td>
                        <td class="td-number <?= $profitClass ?> profit-bold">
                            <?= fmtSilver((int)$trade['profit']) ?>
                        </td>
                        <td class="td-date"><?= date('M j, Y', strtotime($trade['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $queryBase = '?' . http_build_query(array_filter([
                'item'   => $filterItem,
                'type'   => $filterType,
                'profit' => $filterProfit,
            ]));
            $queryBase .= $queryBase === '?' ? '' : '&';
            ?>
            <?php if ($page > 1): ?>
            <a href="<?= $queryBase ?>page=<?= $page - 1 ?>" class="page-btn">← Prev</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="<?= $queryBase ?>page=<?= $i ?>"
               class="page-btn <?= $i === $page ? 'page-current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <a href="<?= $queryBase ?>page=<?= $page + 1 ?>" class="page-btn">Next →</a>
            <?php endif; ?>

            <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </section>

</main>

<footer class="site-footer">
    <p>⚔ Albion Guild Tracker &copy; <?= date('Y') ?> · All Rights Reserved ⚔</p>
</footer>

<script src="assets/js/script.js"></script>
<script>
// Auto-submit filter form on select change
document.querySelectorAll('.trades-filter-form select').forEach(sel => {
    sel.addEventListener('change', () => document.getElementById('filterForm').submit());
});
</script>
</body>
</html>
