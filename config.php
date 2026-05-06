<?php
// ═══════════════════════════════════════════════
//  ALBION GUILD TRACKER — Database Configuration
// ═══════════════════════════════════════════════

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'albion_guild_tracker');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);

// Create users table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        username    VARCHAR(50)  NOT NULL UNIQUE,
        email       VARCHAR(100) NOT NULL UNIQUE,
        password    VARCHAR(255) NOT NULL,
        guild_name  VARCHAR(100) NOT NULL,
        total_fame  BIGINT       NOT NULL DEFAULT 0,
        role        VARCHAR(30)  NOT NULL DEFAULT 'Recruit',
        created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Items table — for autocomplete
$conn->query("
    CREATE TABLE IF NOT EXISTS items (
        id    INT AUTO_INCREMENT PRIMARY KEY,
        name  VARCHAR(150) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Profits table — saved trades
$conn->query("
    CREATE TABLE IF NOT EXISTS profits (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT          NOT NULL,
        type        VARCHAR(30)  NOT NULL DEFAULT 'flipping',
        item_name   VARCHAR(150) NOT NULL,
        tier        VARCHAR(5)   NOT NULL DEFAULT 'T4',
        enchant     TINYINT      NOT NULL DEFAULT 0,
        buy_price   BIGINT       NOT NULL DEFAULT 0,
        sell_price  BIGINT       NOT NULL DEFAULT 0,
        quantity    INT          NOT NULL DEFAULT 1,
        profit      BIGINT       NOT NULL DEFAULT 0,
        created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_type    (type),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Seed some common Albion Online items if table is empty
$itemCount = $conn->query("SELECT COUNT(*) AS c FROM items")->fetch_assoc()['c'];
if ((int)$itemCount === 0) {
    $seedItems = [
        'Adept\'s Bow','Adept\'s Crossbow','Adept\'s Broadsword','Adept\'s Claymore',
        'Adept\'s Warhammer','Adept\'s Shield','Adept\'s Plate Armor','Adept\'s Leather Armor',
        'Adept\'s Cloth Robe','Adept\'s Boots','Adept\'s Helmet','Expert\'s Bow',
        'Expert\'s Crossbow','Expert\'s Broadsword','Expert\'s Claymore','Expert\'s Warhammer',
        'Expert\'s Plate Armor','Expert\'s Leather Armor','Expert\'s Cloth Robe',
        'Master\'s Bow','Master\'s Broadsword','Master\'s Plate Armor',
        'Grandmaster\'s Bow','Grandmaster\'s Broadsword','Grandmaster\'s Plate Armor',
        'Elder\'s Bow','Elder\'s Broadsword','Elder\'s Plate Armor',
        'T4 Ore','T5 Ore','T6 Ore','T7 Ore','T8 Ore',
        'T4 Logs','T5 Logs','T6 Logs','T7 Logs','T8 Logs',
        'T4 Cloth','T5 Cloth','T6 Cloth','T7 Cloth','T8 Cloth',
        'T4 Leather','T5 Leather','T6 Leather','T7 Leather','T8 Leather',
        'Silver Ingot','Gold Ingot','Runite Bar','Mithril Bar',
        'Minor Healing Potion','Healing Potion','Major Healing Potion',
        'Minor Resistance Potion','Resistance Potion','Major Resistance Potion',
        'Invisibility Potion','Gigantify Potion','Sticky Potion',
        'Beef Stew','Mutton Stew','Goose Stew',
        'Pork Omelette','Lamb Chops','Roast Chicken',
        'Apple Pie','Blueberry Pie','Cherry Pie',
        'Fishing Bait','Reinforced Fishing Bait',
    ];
    $ins = $conn->prepare("INSERT IGNORE INTO items (name) VALUES (?)");
    foreach ($seedItems as $item) {
        $ins->bind_param('s', $item);
        $ins->execute();
    }
}

// Session start (safe — only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Flash message helpers
function set_flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}
