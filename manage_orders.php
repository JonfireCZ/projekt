<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Kontrola přihlášení a oprávnění
if (!is_logged_in() || !is_admin()) {
    header('Location: ./login.php?message=' . urlencode('Nemáte oprávnění k přístupu'));
    exit;
}

$page_title = 'Správa objednávek';
$css_path = './style.css';
$base_url = '../';

// Filtr podle stavu
$status_filter = $_GET['status'] ?? 'all';

// Načtení objednávek
if ($status_filter === 'all') {
    $stmt = $pdo->query("SELECT o.id, o.user_id, o.total_price, o.status, o.created_at, u.username, u.email 
                         FROM orders o 
                         LEFT JOIN users u ON o.user_id = u.id 
                         ORDER BY o.created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT o.id, o.user_id, o.total_price, o.status, o.created_at, u.username, u.email 
                           FROM orders o 
                           LEFT JOIN users u ON o.user_id = u.id 
                           WHERE o.status = ?
                           ORDER BY o.created_at DESC");
    $stmt->execute([$status_filter]);
}
$orders = $stmt->fetchAll();

// Statistiky
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM orders");
$stats = $stmt->fetch();

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

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Správa objednávek</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Zpět do administrace</a>
    </div>
    
    <!-- Statistiky -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?= $stats['total'] ?></h5>
                    <p class="card-text text-muted small">Celkem</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h5 class="card-title"><?= $stats['pending'] ?></h5>
                    <p class="card-text text-muted small">Čeká</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-info">
                <div class="card-body">
                    <h5 class="card-title"><?= $stats['processing'] ?></h5>
                    <p class="card-text text-muted small">Zpracovává se</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-primary">
                <div class="card-body">
                    <h5 class="card-title"><?= $stats['shipped'] ?></h5>
                    <p class="card-text text-muted small">Odesláno</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h5 class="card-title"><?= $stats['delivered'] ?></h5>
                    <p class="card-text text-muted small">Doručeno</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h5 class="card-title"><?= $stats['cancelled'] ?></h5>
                    <p class="card-text text-muted small">Zrušeno</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtr -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="?status=all" class="btn btn-sm <?= $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">Vše</a>
                <a href="?status=pending" class="btn btn-sm <?= $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">Čeká</a>
                <a href="?status=processing" class="btn btn-sm <?= $status_filter === 'processing' ? 'btn-info' : 'btn-outline-info' ?>">Zpracovává se</a>
                <a href="?status=shipped" class="btn btn-sm <?= $status_filter === 'shipped' ? 'btn-primary' : 'btn-outline-primary' ?>">Odesláno</a>
                <a href="?status=delivered" class="btn btn-sm <?= $status_filter === 'delivered' ? 'btn-success' : 'btn-outline-success' ?>">Doručeno</a>
                <a href="?status=cancelled" class="btn btn-sm <?= $status_filter === 'cancelled' ? 'btn-danger' : 'btn-outline-danger' ?>">Zrušeno</a>
            </div>
        </div>
    </div>
    
    <!-- Tabulka objednávek -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <div class="text-center py-5 text-muted">
                    Žádné objednávky nebyly nalezeny.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Zákazník</th>
                                <th>Email</th>
                                <th>Celková cena</th>
                                <th>Stav</th>
                                <th>Datum vytvoření</th>
                                <th>Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= $order['id'] ?></strong></td>
                                    <td><?= escape($order['username'] ?? 'Neznámý') ?></td>
                                    <td><?= escape($order['email'] ?? '-') ?></td>
                                    <td><?= number_format($order['total_price'], 2, ',', ' ') ?> Kč</td>
                                    <td>
                                        <span class="badge <?= status_badge_class($order['status']) ?>">
                                            <?= translate_status($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <a href="edit_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">Upravit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
