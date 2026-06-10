<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

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

if (!function_exists('escape')) {
    function escape($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$page_title = 'Moje objednávky';
$css_path = 'style.css';
$base_url = '';

$stmt = $pdo->prepare("SELECT id, user_id, total_price, status, shipping_address, created_at, COALESCE(is_public,0) AS is_public FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="container my-5">
    <h2>Moje objednávky</h2>
    
    <?php if (empty($orders)): ?>
        <div class="card mt-4">
            <div class="card-body text-center py-5">
                <p class="text-muted">Zatím jste nevytvořili žádnou objednávku.</p>
                <a href="index.php" class="btn btn-primary">Začít nakupovat</a>
            </div>
        </div>
    <?php else: ?>
        <div class="mt-4">
            <?php foreach ($orders as $order): ?>
                <?php
                    $stmt = $pdo->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
                    $stmt->execute([$order['id']]);
                    $items = $stmt->fetchAll();
                ?>
                
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <strong>Objednávka #<?= $order['id'] ?></strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Vytvořeno: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></small>
                            </div>
                            <div class="col-md-3">
                                <span class="badge <?= status_badge_class($order['status']) ?>">
                                    <?= translate_status($order['status']) ?>
                                </span>
                                <?php if (!empty($order['is_public'])): ?>
                                    <span class="badge bg-secondary ms-2">Veřejná</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted ms-2">Soukromá</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 text-end">
                                <strong><?= number_format($order['total_price'], 2, ',', ' ') ?> Kč</strong>
                                <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary ms-2">Detaily</a>
                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="collapse" data-bs-target="#orderDetails-<?= $order['id'] ?>" aria-expanded="false" aria-controls="orderDetails-<?= $order['id'] ?>">
                                    Zobrazit
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="orderDetails-<?= $order['id'] ?>" class="collapse">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6>Položky objednávky:</h6>
                                    <ul class="list-unstyled">
                                        <?php foreach ($items as $item): ?>
                                            <li class="mb-1">
                                                <?= escape($item['product_name']) ?> 
                                                <span class="text-muted">(<?= $item['quantity'] ?>× <?= number_format($item['price'], 2, ',', ' ') ?> Kč)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <h6>Dodací adresa:</h6>
                                    <address>
                                        <?= escape($order['shipping_address'] ?? '-') ?>
                                    </address>
                                    <p class="mb-0"><strong>Stav:</strong> <span class="badge <?= status_badge_class($order['status']) ?>"><?= translate_status($order['status']) ?></span></p>
                                    <p class="mb-0"><strong>Cena celkem:</strong> <?= number_format($order['total_price'], 2, ',', ' ') ?> Kč</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>