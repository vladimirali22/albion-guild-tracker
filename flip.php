<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

// Fetch user
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

// Recent trades for sidebar
$recentStmt = $conn->prepare("
    SELECT item_name, tier, enchant, profit, created_at
    FROM profits
    WHERE user_id = ? AND type = 'flipping'
    ORDER BY created_at DESC
    LIMIT 5
");
$recentStmt->bind_param('i', $user['id']);
$recentStmt->execute();
$recentTrades = $recentStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$activePage = 'flip';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Flip Calculator — Albion Guild Tracker</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
</head>
<body class="dashboard-page">

<canvas id="particles"></canvas>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<?php require_once 'navbar.php'; ?>

<main class="dashboard-main flip-main">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title"><span class="page-title-icon">🔄</span> Flip Calculator</h1>
            <p class="page-subtitle">Calculate your flipping profit with live fee breakdown.</p>
        </div>
        <a href="trades.php" class="btn-secondary btn-sm">
            <span>📜</span> My Trades
        </a>
    </div>

    <div class="flip-layout">

        <!-- ═══════════════════════════════
             LEFT — Calculator Form
        ═══════════════════════════════ -->
        <div class="flip-left">

            <section class="flip-card">
                <div class="flip-card-header">
                    <span class="flip-card-icon">⚙</span>
                    <span>Trade Parameters</span>
                </div>

                <div class="flip-form" id="flipForm">

                    <!-- Item Name with autocomplete -->
                    <div class="form-group">
                        <label for="itemName"><span class="label-icon">🗡</span> Item Name</label>
                        <div class="autocomplete-wrapper">
                            <input type="text" id="itemName" placeholder="Search or enter item name…"
                                   autocomplete="off" spellcheck="false">
                            <span class="input-glow"></span>
                            <div class="autocomplete-list" id="autocompleteList"></div>
                        </div>
                        <div class="add-item-hint" id="addItemHint" style="display:none">
                            <button type="button" class="add-item-btn" id="addItemBtn">
                                <span>＋</span> Add "<span id="addItemName"></span>" to database
                            </button>
                        </div>
                    </div>

                    <!-- Tier + Enchant -->
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="tier"><span class="label-icon">🏆</span> Tier</label>
                            <select id="tier" class="form-select">
                                <option value="T4">T4</option>
                                <option value="T5">T5</option>
                                <option value="T6" selected>T6</option>
                                <option value="T7">T7</option>
                                <option value="T8">T8</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="enchant"><span class="label-icon">✨</span> Enchant Level</label>
                            <select id="enchant" class="form-select">
                                <option value="0" selected>.0 — No Enchant</option>
                                <option value="1">.1 — Enchant 1</option>
                                <option value="2">.2 — Enchant 2</option>
                                <option value="3">.3 — Enchant 3</option>
                                <option value="4">.4 — Enchant 4</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tier display badge -->
                    <div class="tier-display-row">
                        <span class="tier-label">Item Designation:</span>
                        <span class="tier-badge" id="tierBadge">T6.0</span>
                    </div>

                    <!-- Buy Method -->
                    <div class="form-group">
                        <label><span class="label-icon">🛒</span> Buy Method</label>
                        <div class="radio-group" id="buyMethodGroup">
                            <label class="radio-card active" data-value="buy_order">
                                <input type="radio" name="buyMethod" value="buy_order" checked>
                                <div class="radio-card-inner">
                                    <span class="radio-icon">📋</span>
                                    <span class="radio-title">Buy Order</span>
                                    <span class="radio-desc">+2.5% setup fee</span>
                                </div>
                            </label>
                            <label class="radio-card" data-value="instant_buy">
                                <input type="radio" name="buyMethod" value="instant_buy">
                                <div class="radio-card-inner">
                                    <span class="radio-icon">⚡</span>
                                    <span class="radio-title">Instant Buy</span>
                                    <span class="radio-desc">No setup fee</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Prices -->
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="buyPrice"><span class="label-icon">🪙</span> Buy Price</label>
                            <input type="number" id="buyPrice" placeholder="0" min="0" step="1">
                            <span class="input-glow"></span>
                        </div>
                        <div class="form-group">
                            <label for="sellPrice"><span class="label-icon">💎</span> Sell Price</label>
                            <input type="number" id="sellPrice" placeholder="0" min="0" step="1">
                            <span class="input-glow"></span>
                        </div>
                    </div>

                    <!-- Quantity -->
                    <div class="form-group">
                        <label for="quantity"><span class="label-icon">📦</span> Quantity</label>
                        <div class="quantity-row">
                            <button type="button" class="qty-btn" id="qtyMinus">−</button>
                            <input type="number" id="quantity" value="1" min="1" max="9999999" step="1">
                            <button type="button" class="qty-btn" id="qtyPlus">+</button>
                            <button type="button" class="qty-preset" data-qty="10">×10</button>
                            <button type="button" class="qty-preset" data-qty="20">×20</button>
                            <button type="button" class="qty-preset" data-qty="100">×100</button>
                        </div>
                        <span class="input-glow"></span>
                    </div>

                    <!-- Premium -->
                    <div class="form-group">
                        <label class="premium-toggle" id="premiumToggle">
                            <input type="checkbox" id="premium" checked>
                            <span class="toggle-track">
                                <span class="toggle-thumb"></span>
                            </span>
                            <span class="toggle-label">
                                <span class="toggle-icon">👑</span>
                                <span>I have <strong>Premium</strong></span>
                                <span class="toggle-tax-info" id="taxInfo">4% transaction tax</span>
                            </span>
                        </label>
                    </div>

                </div><!-- /flip-form -->
            </section>

            <!-- Fee Breakdown -->
            <section class="flip-card breakdown-card">
                <div class="flip-card-header">
                    <span class="flip-card-icon">📊</span>
                    <span>Fee Breakdown</span>
                </div>
                <div class="breakdown-table" id="breakdownTable">
                    <div class="breakdown-row">
                        <span class="br-label">Buy Total</span>
                        <span class="br-value" id="brBuyTotal">—</span>
                    </div>
                    <div class="breakdown-row">
                        <span class="br-label">Buy Setup Fee <span class="br-note">(2.5%)</span></span>
                        <span class="br-value br-fee" id="brBuySetup">—</span>
                    </div>
                    <div class="breakdown-row">
                        <span class="br-label">Sell Total</span>
                        <span class="br-value" id="brSellTotal">—</span>
                    </div>
                    <div class="breakdown-row">
                        <span class="br-label">Sell Setup Fee <span class="br-note">(2.5%)</span></span>
                        <span class="br-value br-fee" id="brSellSetup">—</span>
                    </div>
                    <div class="breakdown-row">
                        <span class="br-label">Transaction Tax <span class="br-note" id="brTaxRate">(4%)</span></span>
                        <span class="br-value br-fee" id="brTax">—</span>
                    </div>
                    <div class="breakdown-row breakdown-total-row">
                        <span class="br-label">Total Fees</span>
                        <span class="br-value br-total-fees" id="brTotalFees">—</span>
                    </div>
                </div>
            </section>

        </div><!-- /flip-left -->

        <!-- ═══════════════════════════════
             RIGHT — Results Panel
        ═══════════════════════════════ -->
        <div class="flip-right">

            <!-- Main Result -->
            <section class="flip-card result-card" id="resultCard">
                <div class="flip-card-header">
                    <span class="flip-card-icon">💰</span>
                    <span>Profit Result</span>
                </div>

                <div class="result-empty" id="resultEmpty">
                    <div class="result-empty-icon">⚔</div>
                    <p>Fill in the form to<br>calculate your profit.</p>
                </div>

                <div class="result-content" id="resultContent" style="display:none">

                    <div class="result-main" id="resultMain">
                        <div class="result-label">Profit per Item</div>
                        <div class="result-value" id="resultPerItem">0</div>
                        <div class="result-margin" id="resultMargin">0% margin</div>
                    </div>

                    <div class="result-grid">
                        <div class="result-grid-item">
                            <div class="rg-qty">×1</div>
                            <div class="rg-value" id="rg1">—</div>
                        </div>
                        <div class="result-grid-item">
                            <div class="rg-qty">×10</div>
                            <div class="rg-value" id="rg10">—</div>
                        </div>
                        <div class="result-grid-item">
                            <div class="rg-qty">×20</div>
                            <div class="rg-value" id="rg20">—</div>
                        </div>
                        <div class="result-grid-item highlight">
                            <div class="rg-qty" id="rgCustomQtyLabel">×1</div>
                            <div class="rg-value" id="rgCustom">—</div>
                        </div>
                    </div>

                    <div class="result-verdict" id="resultVerdict"></div>

                    <!-- Save Button -->
                    <button type="button" class="btn-primary btn-glow btn-save" id="saveTradeBtn" style="display:none">
                        <span class="btn-text">💾 Save This Trade</span>
                        <span class="btn-shine"></span>
                    </button>
                    <div class="save-feedback" id="saveFeedback"></div>

                </div>
            </section>

            <!-- Quick Stats -->
            <section class="flip-card quick-stats-card" id="quickStatsCard" style="display:none">
                <div class="flip-card-header">
                    <span class="flip-card-icon">⚡</span>
                    <span>Quick Stats</span>
                </div>
                <div class="quick-stats">
                    <div class="qs-item">
                        <span class="qs-label">ROI</span>
                        <span class="qs-value" id="qsROI">—</span>
                    </div>
                    <div class="qs-item">
                        <span class="qs-label">Profit Margin</span>
                        <span class="qs-value" id="qsMargin">—</span>
                    </div>
                    <div class="qs-item">
                        <span class="qs-label">Total Fees</span>
                        <span class="qs-value qs-neg" id="qsFees">—</span>
                    </div>
                    <div class="qs-item">
                        <span class="qs-label">Break Even Sell</span>
                        <span class="qs-value" id="qsBreakEven">—</span>
                    </div>
                </div>
            </section>

            <!-- Recent Trades -->
            <section class="flip-card recent-trades-card">
                <div class="flip-card-header">
                    <span class="flip-card-icon">🕐</span>
                    <span>Recent Trades</span>
                    <a href="trades.php" class="flip-card-link">View All →</a>
                </div>
                <?php if (empty($recentTrades)): ?>
                <div class="no-trades">
                    <span>🗡</span>
                    <p>No trades saved yet.<br>Calculate and save your first flip!</p>
                </div>
                <?php else: ?>
                <div class="recent-list">
                    <?php foreach ($recentTrades as $trade): ?>
                    <div class="recent-item">
                        <div class="recent-item-left">
                            <div class="recent-item-name">
                                <?= htmlspecialchars($trade['item_name']) ?>
                            </div>
                            <div class="recent-item-meta">
                                <?= htmlspecialchars($trade['tier']) ?>.<?= $trade['enchant'] ?>
                                · <?= date('M j', strtotime($trade['created_at'])) ?>
                            </div>
                        </div>
                        <div class="recent-item-profit <?= $trade['profit'] >= 0 ? 'profit-pos' : 'profit-neg' ?>">
                            <?= ($trade['profit'] >= 0 ? '+' : '') . number_format($trade['profit']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

        </div><!-- /flip-right -->

    </div><!-- /flip-layout -->

</main>

<footer class="site-footer">
    <p>⚔ Albion Guild Tracker &copy; <?= date('Y') ?> · All Rights Reserved ⚔</p>
</footer>

<script src="assets/js/script.js"></script>
<script src="assets/js/flip.js"></script>
</body>
</html>
