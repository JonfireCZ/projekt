<?php
// Test stránka pro zjištění, proč se objednávka neposílá
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

echo "<h1>Debug informace</h1>";

echo "<h2>1. Session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>2. Je uživatel přihlášen?</h2>";
echo is_logged_in() ? "ANO (user_id: {$_SESSION['user_id']})" : "NE";

echo "<h2>3. POST data (pokud byla odeslána)</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "Žádná POST data nebyla odeslána";
}

echo "<h2>4. Košík uživatele</h2>";
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll();
    echo "<pre>";
    print_r($items);
    echo "</pre>";
} else {
    echo "Uživatel není přihlášen";
}

echo "<h2>5. Struktura tabulky orders</h2>";
try {
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll();
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Chyba: " . $e->getMessage();
}

echo "<h2>6. Test formuláře</h2>";
?>
<form method="post" action="test_order_debug.php">
    <input type="text" name="test_field" placeholder="Test pole" required>
    <button type="submit">Odeslat test</button>
</form>
