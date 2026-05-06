<?php
require_once 'config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
