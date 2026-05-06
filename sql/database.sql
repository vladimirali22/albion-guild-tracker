-- ═══════════════════════════════════════════════════════════
--  ALBION GUILD TRACKER — Database Setup
--  Run this in phpMyAdmin or MySQL CLI before first use.
--  (config.php also auto-creates the DB and table on first run)
-- ═══════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS albion_guild_tracker
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE albion_guild_tracker;

-- ── Users table ──
CREATE TABLE IF NOT EXISTS users (
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    guild_name  VARCHAR(100) NOT NULL,
    total_fame  BIGINT       NOT NULL DEFAULT 0,
    role        VARCHAR(30)  NOT NULL DEFAULT 'Recruit',
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_username (username),
    INDEX idx_email    (email),
    INDEX idx_role     (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Optional: seed a demo admin user ──
-- Password is: Admin@1234  (bcrypt hash below)
INSERT IGNORE INTO users (username, email, password, guild_name, total_fame, role)
VALUES (
    'Demonlord',
    'admin@albion.test',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'Shadowveil Council',
    98500000,
    'Leader'
);
