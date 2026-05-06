<?php
require_once 'config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username']   ?? '');
    $email      = trim($_POST['email']      ?? '');
    $password   = $_POST['password']        ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $guild_name = trim($_POST['guild_name'] ?? '');
    $total_fame = (int)($_POST['total_fame'] ?? 0);

    // Validation
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Username must be between 3 and 50 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (strlen($guild_name) < 2) {
        $errors[] = 'Guild name is required.';
    }
    if ($total_fame < 0) {
        $errors[] = 'Total fame cannot be negative.';
    }

    if (empty($errors)) {
        // Check uniqueness
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'Username or email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $role   = 'Recruit';

            $ins = $conn->prepare("INSERT INTO users (username, email, password, guild_name, total_fame, role) VALUES (?,?,?,?,?,?)");
            $ins->bind_param('ssssds', $username, $email, $hashed, $guild_name, $total_fame, $role);

            if ($ins->execute()) {
                set_flash('success', 'Your account has been forged. You may now enter the realm.');
                redirect('login.php');
            } else {
                $errors[] = 'Database error. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Join the World — Albion Guild Tracker</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
</head>
<body class="auth-page register-page">

<!-- Particle canvas -->
<canvas id="particles"></canvas>

<!-- Ambient light orbs -->
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="auth-container">
    <div class="auth-card" id="authCard">

        <!-- Logo / Crest -->
        <div class="crest">
            <div class="crest-ring outer"></div>
            <div class="crest-ring inner"></div>
            <div class="crest-icon">⚔</div>
        </div>

        <h1 class="auth-title">Join the World</h1>
        <p class="auth-subtitle">Forge your legend. Rise through the ranks.</p>

        <!-- Flash / Error messages -->
        <?php if (!empty($errors)): ?>
        <div class="flash flash-error">
            <span class="flash-icon">⚠</span>
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="auth-form" id="registerForm">

            <div class="form-row two-col">
                <div class="form-group">
                    <label for="username"><span class="label-icon">👤</span> Username</label>
                    <input type="text" id="username" name="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Warrior's name" required autocomplete="off">
                    <span class="input-glow"></span>
                </div>
                <div class="form-group">
                    <label for="guild_name"><span class="label-icon">🏰</span> Guild Name</label>
                    <input type="text" id="guild_name" name="guild_name"
                           value="<?= htmlspecialchars($_POST['guild_name'] ?? '') ?>"
                           placeholder="Your guild" required>
                    <span class="input-glow"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="email"><span class="label-icon">✉</span> Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="your@email.com" required>
                <span class="input-glow"></span>
            </div>

            <div class="form-row two-col">
                <div class="form-group">
                    <label for="password"><span class="label-icon">🔒</span> Password</label>
                    <input type="password" id="password" name="password"
                           placeholder="Min. 8 characters" required>
                    <span class="input-glow"></span>
                </div>
                <div class="form-group">
                    <label for="confirm_password"><span class="label-icon">🔒</span> Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Repeat password" required>
                    <span class="input-glow"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="total_fame"><span class="label-icon">✨</span> Total Fame</label>
                <input type="number" id="total_fame" name="total_fame"
                       value="<?= htmlspecialchars($_POST['total_fame'] ?? '0') ?>"
                       placeholder="0" min="0">
                <span class="input-glow"></span>
            </div>

            <div class="form-group rank-display">
                <span class="label-icon">🎖</span>
                <span>Starting Rank: </span>
                <span class="rank-badge rank-recruit">Recruit</span>
            </div>

            <button type="submit" class="btn-primary btn-glow">
                <span class="btn-text">Forge Your Legend</span>
                <span class="btn-shine"></span>
            </button>

        </form>

        <div class="auth-footer">
            <p>Already walk these lands? <a href="login.php" class="gold-link">Enter the Realm</a></p>
        </div>

    </div>
</div>

<script src="assets/js/script.js"></script>
</body>
</html>
