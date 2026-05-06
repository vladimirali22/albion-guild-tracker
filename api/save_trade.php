<?php
// api/save_trade.php — Save a calculated trade to the profits table
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// ── Validate & sanitize ──
$userId    = (int)$_SESSION['user_id'];
$type      = 'flipping';
$itemName  = trim($data['item_name']  ?? '');
$tier      = trim($data['tier']       ?? 'T4');
$enchant   = (int)($data['enchant']   ?? 0);
$buyPrice  = (int)($data['buy_price'] ?? 0);
$sellPrice = (int)($data['sell_price']?? 0);
$quantity  = max(1, (int)($data['quantity'] ?? 1));
$profit    = (int)($data['profit']    ?? 0);

$validTiers    = ['T4','T5','T6','T7','T8'];
$validEnchants = [0, 1, 2, 3, 4];

if (empty($itemName)) {
    echo json_encode(['error' => 'Item name is required.']); exit;
}
if (!in_array($tier, $validTiers, true)) {
    echo json_encode(['error' => 'Invalid tier.']); exit;
}
if (!in_array($enchant, $validEnchants, true)) {
    echo json_encode(['error' => 'Invalid enchant level.']); exit;
}
if ($buyPrice <= 0 || $sellPrice <= 0) {
    echo json_encode(['error' => 'Prices must be greater than 0.']); exit;
}

// ── Insert ──
$stmt = $conn->prepare("
    INSERT INTO profits (user_id, type, item_name, tier, enchant, buy_price, sell_price, quantity, profit)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('isssiiiii', $userId, $type, $itemName, $tier, $enchant, $buyPrice, $sellPrice, $quantity, $profit);

if ($stmt->execute()) {
    $newId = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'trade_id' => $newId,
        'message' => 'Trade saved successfully!'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save trade. Please try again.']);
}
