<?php
require_once __DIR__ . '/../database_connect.php';
require_once __DIR__ . '/../functions.php';

require_login();
if (!is_admin()) {
    header('Location: ../mainpage.php');
    exit;
}

$delete_id = isset($_GET['delete']) ? (int)$_GET['delete'] : 0;
if ($delete_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$delete_id]);
    header('Location: manage_products.php?deleted=1');
    exit;
}

$stmt = $pdo->query("SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.created_at DESC");
$products = $stmt->fetchAll();

$page_title = 'Správa Produktů — Admin Panel';
$css_path = '../style.css';
$base_url = '../';

require_once __DIR__ . '/../header.php';
?>
    <div class="container py-5">
        <div class="mb-4">
            <a href="edit_product.php" class="btn btn-success">+ Přidat nový produkt</a>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Produkt byl úspěšně smazán.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title">Seznam Produktů</h5>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Název</th>
                            <th>Kategorie</th>
                            <th>Cena</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= escape($product['name']) ?></td>
                            <td><?= escape($product['category'] ?? 'Bez kategorie') ?></td>
                            <td><?= number_format($product['price'], 2, ',', ' ') ?> Kč</td>
                            <td>
                                <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-warning">Upravit</a>
                                <a href="manage_products.php?delete=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Opravdu smazat?')">Smazat</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../footer.php'; ?>
