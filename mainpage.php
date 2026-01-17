<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

$page_title = 'LockerRoom - E-shop';
$css_path = 'style.css';
$base_url = '';

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

$stmt = $pdo->query("SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY RAND() LIMIT 8");
$products = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>
        <div class="container my-3">
            <div class="row mb-3 align-items-stretch">
                <div class="col-lg-3 col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body py-3">
                            <h5 class="card-title">Kategorie</h5>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($categories as $cat): ?>
                                    <a href="category.php?id=<?= $cat['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <?= escape($cat['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9 col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body py-3">
                            <h2>Vítejte v našem e-shopu!</h2>
                            <p class="text-muted">Níže si prohlédněte naši nabídku. Vybíráme pro vás náhodně vybrané produkty.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <?php foreach ($products as $product): ?>
                <div class="col-md-3 mb-4">
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
                                <p class="text-muted small"><?= escape($product['category'] ?? 'Bez kategorie') ?></p>
                                <?php $d = $product['description'] ?? ''; $short = mb_strlen($d, 'UTF-8') > 40 ? mb_substr($d, 0, 40, 'UTF-8') . '...' : $d; ?>
                                <p class="card-text"><?= escape($short) ?></p>
                                <h6 class="text-primary"><?= number_format($product['price'], 2, ',', ' ') ?> Kč</h6>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
                </div>
            </div>

        <?php require_once __DIR__ . '/footer.php'; ?>