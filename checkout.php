<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    header('Location: login.php?message=' . urlencode('Pro dokončení objednávky se musíte přihlásit'));
    exit;
}

$page_title = 'Dokončení objednávky';
$css_path = 'style.css';
$base_url = '';

$items = [];
$total = 0.0;

$stmt = $pdo->prepare("SELECT c.product_id, c.quantity, p.name, p.price, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    header('Location: cart.php?message=' . urlencode('Váš košík je prázdný'));
    exit;
}

foreach ($rows as $r) {
    if ($r['quantity'] > $r['stock']) {
        header('Location: cart.php?error=' . urlencode('Produkt "' . $r['name'] . '" není dostupný v požadovaném množství'));
        exit;
    }
    
    $subtotal = $r['quantity'] * $r['price'];
    $items[] = [
        'product_id' => $r['product_id'],
        'name' => $r['name'],
        'price' => $r['price'],
        'quantity' => $r['quantity'],
        'subtotal' => $subtotal,
    ];
    $total += $subtotal;
}

$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

require_once __DIR__ . '/header.php';
?>

<div class="container my-5">
    <h2>Dokončení objednávky</h2>
    
    <div class="row mt-4">
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Dodací údaje</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="process_order.php" id="orderForm">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Jméno *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Jan" value="<?= escape($user['first_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Příjmení *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Novák" value="<?= escape($user['last_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="jan.novak@email.cz" value="<?= escape($user['email'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefon *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="+420 123 456 789" required>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <label for="street" class="form-label">Ulice a číslo popisné *</label>
                            <input type="text" class="form-control" id="street" name="street" placeholder="Hlavní 123" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">Město *</label>
                                <input type="text" class="form-control" id="city" name="city" placeholder="Praha" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="zip" class="form-label">PSČ *</label>
                                <input type="text" class="form-control" id="zip" name="zip" placeholder="120 00" pattern="[0-9]{3}\s?[0-9]{2}" required>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Odeslat objednávku</button>
                            <a href="cart.php" class="btn btn-outline-secondary btn-lg">Zpět do košíku</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-5">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Souhrn objednávky</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <?php foreach ($items as $it): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <strong><?= escape($it['name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= $it['quantity'] ?>× <?= number_format($it['price'], 2, ',', ' ') ?> Kč</small>
                                </div>
                                <div class="text-end">
                                    <?= number_format($it['subtotal'], 2, ',', ' ') ?> Kč
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Mezisoučet:</strong>
                        <span><?= number_format($total, 2, ',', ' ') ?> Kč</span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Doprava:</strong>
                        <span>Zdarma</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <h5><strong>Celkem:</strong></h5>
                        <h5><strong><?= number_format($total, 2, ',', ' ') ?> Kč</strong></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
