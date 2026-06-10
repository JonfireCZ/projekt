<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

require_login();
if (!is_admin()) {
    header('Location: ./index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? 'customer';

    if (in_array($role, ['customer', 'editor', 'admin'])) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $user_id]);
    }
}

header('Location: manage_users.php');
exit;
?>
