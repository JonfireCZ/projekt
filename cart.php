<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    header('Location: login.php?message=' . urlencode('Pro akci se musíte přihlásit'));
    exit;
}

$page_title = 'Košík';
$css_path = 'style.css';
$base_url = '';
require_once __DIR__ . '/header.php';

$items = [];
$total = 0.0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'set' && isset($_POST['product_id'])) {
        $pid = (int)$_POST['product_id'];
        $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        set_cart_quantity($pid, $qty);
    }
    if (isset($_POST['action']) && $_POST['action'] === 'remove' && isset($_POST['product_id'])) {
        $pid = (int)$_POST['product_id'];
        remove_from_cart($pid);
    }
    header('Location: cart.php');
    exit;
}

if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT c.product_id, c.quantity, p.name, p.price, p.image_path, p.image_mime, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $subtotal = $r['quantity'] * $r['price'];
        $items[] = [
            'product_id' => $r['product_id'],
            'name' => $r['name'],
            'price' => $r['price'],
            'quantity' => $r['quantity'],
            'image_path' => (!empty($r['image_mime'])) ? ('image.php?id=' . $r['product_id']) : $r['image_path'],
            'stock' => $r['stock'],
            'subtotal' => $subtotal,
        ];
        $total += $subtotal;
    }
} else {
    $session_cart = $_SESSION['cart'] ?? [];
    if ($session_cart) {
        $ids = array_keys($session_cart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price, image_path, image_mime, stock FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll();
        $map = [];
        foreach ($rows as $r) $map[$r['id']] = $r;
        foreach ($session_cart as $pid => $qty) {
            if (!isset($map[$pid])) continue;
            $r = $map[$pid];
            $subtotal = $qty * $r['price'];
            $items[] = [
                'product_id' => $pid,
                'name' => $r['name'],
                'price' => $r['price'],
                'quantity' => $qty,
                'image_path' => (!empty($r['image_mime'])) ? ('image.php?id=' . $r['id']) : $r['image_path'],
                'stock' => $r['stock'],
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }
    }
}
?>

<div class="container my-5">
    <h2>Košík</h2>

    <?php if (empty($items)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <p class="text-muted">V košíku zatím nic není.</p>
                <a href="index.php" class="btn btn-primary">Pokračovat v nákupu</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Produkt</th>
                        <th>Cena</th>
                        <th>Počet</th>
                        <th>Mezisoučet</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($it['image_path']) && (strpos($it['image_path'], 'image.php?id=') === 0 || file_exists(__DIR__ . '/' . $it['image_path']))): ?>
                                        <img src="<?= escape($it['image_path']) ?>" alt="<?= escape($it['name']) ?>" style="height:60px;width:60px;object-fit:cover;border-radius:6px;">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= escape($it['name']) ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td><?= number_format($it['price'], 2, ',', ' ') ?> Kč</td>
                            <td>
                                <form method="post" action="" class="d-inline-block">
                                    <input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
                                    <input type="hidden" name="action" value="set">
                                    <input type="number" name="quantity" value="<?= $it['quantity'] ?>" min="0" max="<?= $it['stock'] ?>" style="width:80px;" class="form-control form-control-sm d-inline">
                                    
                                    <button class="btn btn-sm btn-primary mt-1">Aktualizovat</button>
                                </form>
                            </td>
                            <td><?= number_format($it['subtotal'], 2, ',', ' ') ?> Kč</td>
                            <td>
                                <form method="post" action="">
                                    <input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button class="btn btn-sm btn-danger">Odebrat</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <div class="me-4">
                <h4>Celkem: <?= number_format($total, 2, ',', ' ') ?> Kč</h4>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-secondary">Pokračovat v nákupu</a>
                <?php if (is_logged_in()): ?>
                    <a href="checkout.php" class="btn btn-primary">Dokončit objednávku</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Přihlásit a dokončit objednávku</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>