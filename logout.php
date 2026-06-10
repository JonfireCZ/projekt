<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (isset($_SESSION['user_id'])) {
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
}

session_destroy();
header('Location: login.php?logout=1');
exit;
?>
