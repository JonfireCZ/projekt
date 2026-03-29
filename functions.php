<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function escape(string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_editor(): bool {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['editor','admin'], true);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function add_to_cart(int $product_id, int $quantity = 1): void {
    global $pdo;
    if (!is_logged_in()) return;
    if ($quantity < 1) $quantity = 1;
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $p = $stmt->fetch();
    if (!$p) return;
    $available = (int)$p['stock'];

    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $row = $stmt->fetch();
        $current = $row ? (int)$row['quantity'] : 0;
        $newQty = min($current + $quantity, $available);
        if ($current) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$newQty, $user_id, $product_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $newQty]);
        }
    }
}

function set_cart_quantity(int $product_id, int $quantity): void {
    global $pdo;
    if ($quantity < 0) $quantity = 0;
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $p = $stmt->fetch();
    if (!$p) return;
    $available = (int)$p['stock'];
    $quantity = min($quantity, $available);

    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        if ($quantity <= 0) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }
    } else {
        return;
    }
}

function remove_from_cart(int $product_id): void {
    global $pdo;
    if (!is_logged_in()) return;
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
}

function get_cart_items(): array {
    global $pdo;
    $items = [];
    if (is_logged_in()) {
        $stmt = $pdo->prepare("SELECT c.product_id, c.quantity, p.name, p.price, p.image_path, p.image_mime, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $items[] = [
                'product_id' => $r['product_id'],
                'name' => $r['name'],
                'price' => $r['price'],
                'quantity' => $r['quantity'],
                'image_path' => (!empty($r['image_mime'])) ? ('image.php?id=' . $r['product_id']) : $r['image_path'],
                'stock' => $r['stock'],
                'subtotal' => $r['quantity'] * $r['price'],
            ];
        }
    } else {
        return [];
    }
    return $items;
}

function cart_total(): float {
    $items = get_cart_items();
    $total = 0.0;
    foreach ($items as $it) $total += $it['subtotal'];
    return $total;
}

?>
