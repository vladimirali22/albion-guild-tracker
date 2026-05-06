<?php
require_once 'config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors  = [];
$flash   = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login']    ?? '');
    $password = $_POST['password']      ?? '';
    $remember = isset($_POST['remember']);

    if (empty($login) || empty($password)) {
        $errors[] = 'Please fill in all fields.';
    } else {
        // Allow login by username OR email
        $stmt = $conn->prepare("SELECT id, username, password, role, guild_name FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['username']   = $user['username'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['guild_name'] = $user['guild_name'];

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + 86400 * 30, '/', '', false, true);
                }

                redirect('dashboard.php');
            } else {
                $errors[] = 'Invalid credentials. The realm denies you entry.';
            }
        } else {
            $errors[] = 'No warrior found with those credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Enter the Realm — Albion Guild Tracker</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
</head>
<body class="auth-page login-page">

<canvas id="particles"></canvas>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="auth-container">
    <div class="auth-card login-card" id="authCard">

        <div class="crest">
            <div class="crest-ring outer"></div>
            <div class="crest-ring inner"></div>
            <div class="crest-icon">🛡</div>
        </div>

        <h1 class="auth-title">Enter the Realm</h1>
        <p class="auth-subtitle">The gates open for those who dare.</p>

        <!-- Flash messages -->
        <?php if ($flash): ?>
        <div class="flash flash-<?= $flash['type'] ?>">
            <span class="flash-icon"><?= $flash['type'] === 'success' ? '✓' : '⚠' ?></span>
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
        <?php endif; ?>

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

        <form method="POST" action="login.php" class="auth-form" id="loginForm">

            <div class="form-group">
                <label for="login"><span class="label-icon">👤</span> Username or Email</label>
                <input type="text" id="login" name="login"
                       value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                       placeholder="Your warrior name or email" required autocomplete="username">
                <span class="input-glow"></span>
            </div>

            <div class="form-group">
                <label for="password"><span class="label-icon">🔒</span> Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Your secret passphrase" required autocomplete="current-password">
                <span class="input-glow"></span>
                <button type="button" class="toggle-pwd" id="togglePwd" aria-label="Toggle password">👁</button>
            </div>

            <div class="form-options">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="checkbox-custom"></span>
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="btn-primary btn-glow">
                <span class="btn-text">Enter the Realm</span>
                <span class="btn-shine"></span>
            </button>

        </form>

        <div class="auth-footer">
            <p>New to these lands? <a href="register.php" class="gold-link">Join the World</a></p>
        </div>

    </div>
</div>

<script src="assets/js/script.js"></script>
</body>
</html>
