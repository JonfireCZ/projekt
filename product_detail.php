<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: mainpage.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if (!is_logged_in()) {
        $error = 'Pro akci se musíte přihlásit';
    } else {
        add_to_cart($product['id'], $qty);
        $redirect = $_POST['redirect'] ?? ('product_detail.php?id=' . $product['id']);
        header('Location: ' . $redirect);
        exit;
    }
}

$page_title = escape($product['name']) . ' — LockerRoom';
$css_path = 'style.css';
$base_url = '';

require_once __DIR__ . '/header.php';
?>
    <div class="container my-4">
        <div class="row">
            <aside class="col-md-3 mb-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-warning"><?= escape($error) ?></div>
                        <?php endif; ?>
                        <h5 class="card-title">Kategorie</h5>
                        <div class="d-flex flex-column gap-2">
                            <a href="mainpage.php" class="btn btn-outline-secondary btn-sm">← Všechny produkty</a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="category.php?id=<?= $cat['id'] ?>" class="btn <?= ($cat['id'] == $product['category_id']) ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                                    <?= escape($cat['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="col-md-9">
                <a href="mainpage.php" class="btn btn-outline-secondary btn-sm mb-3">← Zpět</a>

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <?php if (!empty($product['image_mime'])): ?>
                            <img src="image.php?id=<?= $product['id'] ?>" alt="<?= escape($product['name']) ?>" class="img-fluid rounded mb-3" style="max-height: 400px; object-fit: cover; width: 100%;">
                        <?php elseif (!empty($product['image_path']) && file_exists(__DIR__ . '/' . $product['image_path'])): ?>
                            <img src="<?= escape($product['image_path']) ?>" alt="<?= escape($product['name']) ?>" class="img-fluid rounded mb-3" style="max-height: 400px; object-fit: cover; width: 100%;">
                        <?php else: ?>
                            <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center" style="height: 300px;">
                                <span class="text-muted">Bez obrázku</span>
                            </div>
                        <?php endif; ?>

                        <h1><?= escape($product['name']) ?></h1>
                        <?php if ($product['category']): ?>
                            <p class="text-muted mb-3">Kategorie: <strong><?= escape($product['category']) ?></strong></p>
                        <?php endif; ?>

                        <div class="card-text">
                            <p><?= nl2br(escape($product['description'])) ?></p>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="text-primary mb-0"><?= number_format($product['price'], 2, ',', ' ') ?> Kč</h3>
                            <?php if ($product['stock'] > 0): ?>
                                <form method="post" action="" class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="action" value="add">
                                    <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="form-control form-control-sm" style="width:90px;">
                                    <input type="hidden" name="redirect" value="product_detail.php?id=<?= $product['id'] ?>">
                                    <button class="btn btn-primary btn-lg">Přidat do košíku</button>
                                </form>
                            <?php else: ?>
                                <span class="badge bg-danger">Vyprodáno</span>
                            <?php endif; ?>
                        </div>

                        <?php if (is_admin()): ?>
                            <div class="mt-3">
                                <a href="admin/edit_product.php?id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">Upravit</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

<?php require_once __DIR__ . '/footer.php'; ?>
