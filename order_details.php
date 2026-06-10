<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

if (!function_exists('translate_status')) {
    function translate_status($status) {
        $translations = [
            'pending' => 'Čeká na zpracování',
            'processing' => 'Zpracovává se',
            'shipped' => 'Odesláno',
            'delivered' => 'Doručeno',
            'cancelled' => 'Zrušeno',
        ];
        return $translations[$status] ?? $status;
    }
}

if (!function_exists('status_badge_class')) {
    function status_badge_class($status) {
        $classes = [
            'pending' => 'bg-warning text-dark',
            'processing' => 'bg-info text-white',
            'shipped' => 'bg-primary text-white',
            'delivered' => 'bg-success text-white',
            'cancelled' => 'bg-danger text-white',
        ];
        return $classes[$status] ?? 'bg-secondary';
    }
}

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$order_id) {
    header('Location: my_orders.php');
    exit;
}

// fetch order
$stmt = $pdo->prepare('SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?');
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) {
    header('Location: my_orders.php');
    exit;
}

// permission: owner or public
$current_user = $_SESSION['user_id'];
if ($order['user_id'] != $current_user && empty($order['is_public'])) {
    // not allowed
    header('HTTP/1.1 403 Forbidden');
    echo 'Nemáte oprávnění zobrazit tuto objednávku.';
    exit;
}

// items
$stmt = $pdo->prepare('SELECT oi.*, p.image_path, p.image_mime FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

$page_title = 'Objednávka #' . $order_id;
$css_path = 'style.css';
$base_url = '';
require_once __DIR__ . '/header.php';
?>
<div class="container my-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Objednávka #<?= $order_id ?></h4>
                <small class="text-muted">Vytvořeno: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?> • Uživatel: <?= escape($order['username']) ?></small>
            </div>
            <div class="text-end">
                <span class="badge <?= status_badge_class($order['status']) ?>"><?= translate_status($order['status']) ?></span>
                <p class="mb-0">Celkem: <strong><?= number_format($order['total_price'],2,',',' ') ?> Kč</strong></p>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>Položky</h5>
                    <div class="list-group">
                        <?php foreach ($items as $it): ?>
                            <div class="list-group-item d-flex gap-3 align-items-center">
                                <div style="width:80px;flex:0 0 80px">
                                    <?php if (!empty($it['image_mime'])): ?>
                                        <img src="image.php?id=<?= $it['product_id'] ?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:6px">
                                    <?php elseif (!empty($it['image_path']) && file_exists(__DIR__ . '/' . $it['image_path'])): ?>
                                        <img src="<?= escape($it['image_path']) ?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:6px">
                                    <?php else: ?>
                                        <div style="width:80px;height:80px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#6c757d">No img</div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <strong><?= escape($it['product_name']) ?></strong>
                                    <div class="small text-muted">Cena: <?= number_format($it['price'],2,',',' ') ?> Kč • Množství: <?= (int)$it['quantity'] ?></div>
                                </div>
                                <div class="text-end">
                                    <div><strong><?= number_format($it['price'] * $it['quantity'],2,',',' ') ?> Kč</strong></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5>Dodací adresa</h5>
                    <address>
                        <?= escape($order['shipping_address'] ?? '-') ?>
                    </address>
                    <h6>Stav objednávky</h6>
                    <p><?= translate_status($order['status']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
 
