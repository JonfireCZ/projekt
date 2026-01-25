<?php
require_once __DIR__ . '/../database_connect.php';
require_once __DIR__ . '/../functions.php';

// Kontrola přihlášení a oprávnění
if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php?message=' . urlencode('Nemáte oprávnění k přístupu'));
    exit;
}

$order_id = $_GET['id'] ?? 0;

// Zpracování formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        $message = 'Stav objednávky byl aktualizován';
    }
    
    elseif ($action === 'update_item') {
        $item_id = $_POST['item_id'] ?? 0;
        $quantity = (int)($_POST['quantity'] ?? 1);
        $price = (float)($_POST['price'] ?? 0);
        
        $stmt = $pdo->prepare("UPDATE order_items SET quantity = ?, price = ? WHERE id = ? AND order_id = ?");
        $stmt->execute([$quantity, $price, $item_id, $order_id]);
        
        // Přepočítat celkovou cenu objednávky
        $stmt = $pdo->prepare("SELECT SUM(quantity * price) as total FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $new_total = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("UPDATE orders SET total_price = ? WHERE id = ?");
        $stmt->execute([$new_total, $order_id]);
        
        $message = 'Položka byla aktualizována';
    }
    
    elseif ($action === 'delete_item') {
        $item_id = $_POST['item_id'] ?? 0;
        
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE id = ? AND order_id = ?");
        $stmt->execute([$item_id, $order_id]);
        
        // Přepočítat celkovou cenu objednávky
        $stmt = $pdo->prepare("SELECT SUM(quantity * price) as total FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $result = $stmt->fetch();
        $new_total = $result['total'] ?? 0;
        
        $stmt = $pdo->prepare("UPDATE orders SET total_price = ? WHERE id = ?");
        $stmt->execute([$new_total, $order_id]);
        
        $message = 'Položka byla odstraněna';
    }
    
    elseif ($action === 'cancel_order') {
        $pdo->beginTransaction();
        try {
            // Vrátit produkty zpět na sklad
            $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll();
            
            foreach ($items as $item) {
                if ($item['product_id']) {
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
            }
            
            // Změnit stav objednávky na zrušeno
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$order_id]);
            
            $pdo->commit();
            $message = 'Objednávka byla zrušena a produkty vráceny na sklad';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Chyba při rušení objednávky';
        }
    }
    
    header('Location: edit_order.php?id=' . $order_id . '&message=' . urlencode($message ?? $error ?? ''));
    exit;
}

// Načtení objednávky
$stmt = $pdo->prepare("SELECT o.*, u.username, u.email, u.first_name, u.last_name 
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: manage_orders.php');
    exit;
}

// Načtení položek objednávky
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Dekódování dodací adresy
$address = json_decode($order['shipping_address'], true);

$page_title = 'Úprava objednávky #' . $order_id;
$css_path = '../style.css';
$base_url = '../';

require_once __DIR__ . '/../header.php';

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
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Úprava objednávky #<?= $order['id'] ?></h2>
        <a href="manage_orders.php" class="btn btn-outline-secondary">Zpět na seznam objednávek</a>
    </div>
    
    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success"><?= escape($_GET['message']) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Informace o objednávce -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Informace o objednávce</h5>
                    <span class="badge bg-light text-dark"><?= translate_status($order['status']) ?></span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Číslo objednávky:</strong> #<?= $order['id'] ?></p>
                            <p><strong>Datum vytvoření:</strong> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
                            <p><strong>Poslední úprava:</strong> <?= date('d.m.Y H:i', strtotime($order['updated_at'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Zákazník:</strong> <?= escape($order['username'] ?? 'Neznámý') ?></p>
                            <p><strong>Email:</strong> <?= escape($order['email'] ?? '-') ?></p>
                            <p><strong>Celková cena:</strong> <?= number_format($order['total_price'], 2, ',', ' ') ?> Kč</p>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['note'])): ?>
                        <div class="alert alert-info">
                            <strong>Poznámka zákazníka:</strong><br>
                            <?= nl2br(escape($order['note'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Změna stavu -->
                    <?php if ($order['status'] === 'delivered'): ?>
                        <div class="alert alert-success mt-3">
                            <strong>✅ Objednávka byla doručena</strong><br>
                            Doručené objednávky již nelze upravovat.
                        </div>
                    <?php elseif ($order['status'] === 'cancelled'): ?>
                        <div class="alert alert-danger mt-3">
                            <strong>❌ Objednávka byla zrušena</strong><br>
                            Zrušené objednávky již nelze upravovat.
                        </div>
                    <?php else: ?>
                    <form method="post" class="mt-3">
                        <input type="hidden" name="action" value="update_status">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <label for="status" class="form-label"><strong>Změnit stav objednávky:</strong></label>
                                <select name="status" id="status" class="form-select">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Čeká na zpracování</option>
                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Zpracovává se</option>
                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Odesláno</option>
                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Doručeno</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Zrušeno</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary">Aktualizovat stav</button>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Položky objednávky -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Položky objednávky</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produkt</th>
                                    <th>Množství</th>
                                    <th>Cena/ks</th>
                                    <th>Celkem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= escape($item['product_name']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td><?= number_format($item['price'], 2, ',', ' ') ?> Kč</td>
                                        <td><?= number_format($item['quantity'] * $item['price'], 2, ',', ' ') ?> Kč</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Dodací adresa -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Dodací adresa</h5>
                </div>
                <div class="card-body">
                    <address>
                        <strong><?= escape($address['first_name'] ?? '') ?> <?= escape($address['last_name'] ?? '') ?></strong><br>
                        <?= escape($address['street'] ?? '') ?><br>
                        <?= escape($address['zip'] ?? '') ?> <?= escape($address['city'] ?? '') ?><br>
                        <br>
                        <strong>Tel:</strong> <?= escape($address['phone'] ?? '') ?><br>
                        <strong>Email:</strong> <?= escape($address['email'] ?? '') ?>
                    </address>
                </div>
            </div>
            
            <!-- Akce -->
            <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Zrušit objednávku</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Zrušení objednávky vrátí produkty zpět na sklad.</p>
                    <form method="post" onsubmit="return confirm('Opravdu chcete zrušit tuto objednávku? Produkty budou vráceny na sklad.');">
                        <input type="hidden" name="action" value="cancel_order">
                        <button type="submit" class="btn btn-danger w-100">
                            Zrušit objednávku
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
