<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['order_id'] ?? 0;

// Ověření, že objednávka patří uživateli
$stmt = $pdo->prepare("SELECT id, total_price, created_at FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: my_orders.php');
    exit;
}

$page_title = 'Potvrzení objednávky';
$css_path = 'style.css';
$base_url = '';

require_once __DIR__ . '/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-check-circle text-success" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                        </svg>
                    </div>
                    
                    <h2 class="text-success mb-3">Děkujeme za vaši objednávku!</h2>
                    
                    <p class="lead">Vaše objednávka byla úspěšně odeslána.</p>
                    
                    <div class="my-4">
                        <p class="mb-2"><strong>Číslo objednávky:</strong> #<?= $order['id'] ?></p>
                        <p class="mb-2"><strong>Celková částka:</strong> <?= number_format($order['total_price'], 2, ',', ' ') ?> Kč</p>
                        <p class="mb-2"><strong>Datum vytvoření:</strong> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
                    </div>
                    
                    <div class="alert alert-info">
                        <p class="mb-0">O stavu vaší objednávky vás budeme informovat emailem.</p>
                    </div>
                    
                    <div class="mt-4 d-flex gap-2 justify-content-center">
                        <a href="my_orders.php" class="btn btn-primary">Zobrazit moje objednávky</a>
                        <a href="mainpage.php" class="btn btn-outline-primary">Pokračovat v nákupu</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
