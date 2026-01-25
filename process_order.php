<?php
// Zapnutí zobrazování chyb pro ladění
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

// Načtení POST dat
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$street = trim($_POST['street'] ?? '');
$city = trim($_POST['city'] ?? '');
$zip = trim($_POST['zip'] ?? '');
$note = null; // Poznámka není použita

// Validace
if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($street) || empty($city) || empty($zip)) {
    header('Location: checkout.php?error=' . urlencode('Všechna povinná pole musí být vyplněna'));
    exit;
}

// Sestavení dodací adresy
$shipping_address = json_encode([
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'phone' => $phone,
    'street' => $street,
    'city' => $city,
    'zip' => $zip,
], JSON_UNESCAPED_UNICODE);

try {
    $pdo->beginTransaction();
    
    // Načtení položek z košíku
    $stmt = $pdo->prepare("SELECT c.product_id, c.quantity, p.name, p.price, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? FOR UPDATE");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();
    
    if (empty($cart_items)) {
        $pdo->rollBack();
        header('Location: cart.php?error=' . urlencode('Váš košík je prázdný'));
        exit;
    }
    
    // Kontrola dostupnosti a výpočet celkové ceny
    $total = 0.0;
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $pdo->rollBack();
            header('Location: cart.php?error=' . urlencode('Produkt "' . $item['name'] . '" není dostupný v požadovaném množství'));
            exit;
        }
        $total += $item['quantity'] * $item['price'];
    }
    
    // Vytvoření objednávky
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address) VALUES (?, ?, 'pending', ?)");
    $result = $stmt->execute([$_SESSION['user_id'], $total, $shipping_address]);
    
    if (!$result) {
        throw new Exception("Nepodařilo se vytvořit objednávku");
    }
    
    $order_id = $pdo->lastInsertId();
    
    if (!$order_id) {
        throw new Exception("Nepodařilo se získat ID objednávky");
    }
    
    // Vložení položek objednávky a aktualizace skladu
    $stmt_insert = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
    $stmt_update_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    
    foreach ($cart_items as $item) {
        $stmt_insert->execute([
            $order_id,
            $item['product_id'],
            $item['name'],
            $item['quantity'],
            $item['price']
        ]);
        
        $stmt_update_stock->execute([
            $item['quantity'],
            $item['product_id']
        ]);
    }
    
    // Vyprázdnění košíku
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    $pdo->commit();
    
    // Přesměrování na stránku s potvrzením
    header('Location: order_confirmation.php?order_id=' . $order_id);
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Order processing error: " . $e->getMessage());
    
    // Pro ladění zobrazíme konkrétní chybu
    die("Chyba při zpracování objednávky: " . htmlspecialchars($e->getMessage()) . "<br><br><a href='checkout.php'>Zpět</a>");
    
    // Po opravě použijte toto místo die():
    // header('Location: checkout.php?error=' . urlencode('Nastala chyba při zpracování objednávky. Zkuste to prosím znovu.'));
    // exit;
}
