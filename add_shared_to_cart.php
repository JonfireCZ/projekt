<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mainpage.php');
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$redirect = $_SERVER['HTTP_REFERER'] ?? 'mainpage.php';

if ($product_id > 0 && is_logged_in()) {
    add_to_cart($product_id, 1);
}

header('Location: ' . $redirect);
exit;

?>
