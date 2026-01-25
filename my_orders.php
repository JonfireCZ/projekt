<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$page_title = 'Moje objednávky';
$css_path = 'style.css';
$base_url = '';

// Načtení objednávek
$stmt = $pdo->prepare("SELECT id, total_price, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/header.php';

// Funkce pro překlad stavů
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

// Funkce pro barvu stavů
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
?>

<div class="container my-5">
    <h2>Moje objednávky</h2>
    
    <?php if (empty($orders)): ?>
        <div class="card mt-4">
            <div class="card-body text-center py-5">
                <p class="text-muted">Zatím jste nevytvořili žádnou objednávku.</p>
                <a href="mainpage.php" class="btn btn-primary">Začít nakupovat</a>
            </div>
        </div>
    <?php else: ?>
        <div class="mt-4">
            <?php foreach ($orders as $order): ?>
                <?php
                    // Načtení položek objednávky
                    $stmt = $pdo->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
                    $stmt->execute([$order['id']]);
                    $items = $stmt->fetchAll();
                    
                    // Načtení dodací adresy
                    $stmt = $pdo->prepare("SELECT shipping_address FROM orders WHERE id = ?");
                    $stmt->execute([$order['id']]);
                    $order_details = $stmt->fetch();
                    $address = json_decode($order_details['shipping_address'], true);
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
                            </div>
                            <div class="col-md-3 text-end">
                                <strong><?= number_format($order['total_price'], 2, ',', ' ') ?> Kč</strong>
                            </div>
                        </div>
                    </div>
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
                                    <?= escape($address['first_name'] ?? '') ?> <?= escape($address['last_name'] ?? '') ?><br>
                                    <?= escape($address['street'] ?? '') ?><br>
                                    <?= escape($address['zip'] ?? '') ?> <?= escape($address['city'] ?? '') ?><br>
                                    <small class="text-muted">Tel: <?= escape($address['phone'] ?? '') ?></small>
                                </address>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
