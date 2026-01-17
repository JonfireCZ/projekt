<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: mainpage.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? ORDER BY created_at DESC");
$stmt->execute([$category_id]);
$products = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

$page_title = escape($category['name']) . ' — LockerRoom';
$css_path = 'style.css';
$base_url = '';

require_once __DIR__ . '/header.php';
?>
    <div class="container my-4">
        <div class="row">
            <aside class="col-md-3 mb-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title">Kategorie</h5>
                        <div class="d-flex flex-column gap-2">
                            <a href="mainpage.php" class="btn btn-outline-secondary btn-sm">← Všechny produkty</a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="category.php?id=<?= $cat['id'] ?>" class="btn <?= ($cat['id'] == $category_id) ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                                    <?= escape($cat['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="col-md-9">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h2><?= escape($category['name']) ?></h2>
                        <?php if ($category['description']): ?>
                            <p class="text-muted"><?= escape($category['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center py-5">
                            <h4 class="text-muted">nic zde není :(</h4>
                            <p class="text-muted mb-3">V této kategorii zatím nejsou žádné produkty.</p>
                            <a href="mainpage.php" class="btn btn-primary">← Zpět na hlavní stránku</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                        <div class="col-md-6 mb-4">
                            <a href="product_detail.php?id=<?= $product['id'] ?>" class="text-decoration-none text-reset">
                                <div class="card shadow-sm border-0 h-100 transition-shadow" style="transition: box-shadow 0.3s; cursor: pointer;">
                                    <?php if (!empty($product['image_mime'])): ?>
                                        <img src="image.php?id=<?= $product['id'] ?>" alt="<?= escape($product['name']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                                    <?php elseif (!empty($product['image_path']) && file_exists(__DIR__ . '/' . $product['image_path'])): ?>
                                        <img src="<?= escape($product['image_path']) ?>" alt="<?= escape($product['name']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <span class="text-muted">Bez obrázku</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?= escape($product['name']) ?></h5>
                                        <p class="card-text"><?= escape(substr($product['description'], 0, 100)) ?>...</p>
                                        <h6 class="text-primary"><?= number_format($product['price'], 2, ',', ' ') ?> Kč</h6>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

<?php require_once __DIR__ . '/footer.php'; ?>
