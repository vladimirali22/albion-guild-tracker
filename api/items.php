<?php
// api/items.php — Item search & add endpoint
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'search';

// ── SEARCH: GET /api/items.php?action=search&q=bow ──
if ($action === 'search') {
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    $stmt = $conn->prepare("SELECT id, name FROM items WHERE name LIKE ? ORDER BY name LIMIT 15");
    $stmt->bind_param('s', $q);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode(['items' => $items]);
    exit;
}

// ── ADD: POST /api/items.php?action=add  {name: "..."} ──
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');

    if (strlen($name) < 2 || strlen($name) > 150) {
        http_response_code(400);
        echo json_encode(['error' => 'Item name must be 2–150 characters.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO items (name) VALUES (?)");
    $stmt->bind_param('s', $name);
    $stmt->execute();

    // Fetch the id (whether inserted or already existed)
    $sel = $conn->prepare("SELECT id, name FROM items WHERE name = ?");
    $sel->bind_param('s', $name);
    $sel->execute();
    $item = $sel->get_result()->fetch_assoc();

    echo json_encode(['success' => true, 'item' => $item]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action.']);
