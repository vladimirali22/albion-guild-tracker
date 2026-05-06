<?php
require_once 'config.php';

$_SESSION = [];
session_destroy();

// Clear remember-me cookie if set
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

redirect('login.php');
