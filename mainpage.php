<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

$page_title = 'LockerRoom - E-shop';
$css_path = 'style.css';
$base_url = '';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

if ($search) {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.name LIKE ? ORDER BY p.name");
    $stmt->execute(['%' . $search . '%']);
    $products = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY RAND() LIMIT 8");
    $products = $stmt->fetchAll();
}

require_once __DIR__ . '/header.php';
?>
        <div class="container my-3">
            <div class="row mb-4">
                <div class="col-lg-8 mx-auto">
                    <form method="get" action="mainpage.php" class="mb-0">
                        <div class="input-group input-group-lg shadow-sm">
                            <input type="text" name="search" class="form-control" placeholder="Hledat produkty..." value="<?= escape($search) ?>" style="border-radius: 25px 0 0 25px; border-right: none;">
                            <button class="btn text-white" type="submit" style="background: #0f3460; border-color: #0f3460; border-radius: 0 25px 25px 0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                                </svg>
                            </button>
                            <?php if ($search): ?>
                                <a href="mainpage.php" class="btn btn-outline-secondary" style="border-radius: 25px; margin-left: 10px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
                                        <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                                    </svg>
                                    Zrušit
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php if ($search): ?>
                        <div class="mt-3 text-center text-muted">
                            <?php if (count($products) > 0): ?>
                                Nalezeno <?= count($products) ?> produktů pro "<?= escape($search) ?>"
                            <?php else: ?>
                                Žádné produkty nenalezeny pro "<?= escape($search) ?>"
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

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
            </div>

            <div class="row">
                <?php if (empty($products) && $search): ?>
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body text-center py-5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#0f3460" class="bi bi-inbox mb-3 opacity-50" viewBox="0 0 16 16">
                                    <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm9.954 5H10.45a2.5 2.5 0 0 1-4.9 0H1.066l.32 2.562a.5.5 0 0 0 .497.438h12.234a.5.5 0 0 0 .496-.438L14.933 9zM3.809 3.563A1.5 1.5 0 0 1 4.981 3h6.038a1.5 1.5 0 0 1 1.172.563l3.7 4.625a.5.5 0 0 1 .105.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374l3.7-4.625z"/>
                                </svg>
                                <h4 class="fw-bold mb-2" style="color: #0f3460;">Nenalezeny žádné produkty</h4>
                                <p class="text-muted">Zkuste použít jiná klíčová slova nebo procházejte podle kategorií.</p>
                                <a href="mainpage.php" class="btn btn-primary mt-3">Zobrazit všechny produkty</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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