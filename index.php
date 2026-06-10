<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

$page_title = 'LockerRoom - E-shop';
$css_path = 'style.css';
$base_url = '';
$show_search = true;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$price_min = isset($_GET['price_min']) && is_numeric($_GET['price_min']) ? max(0, (float)$_GET['price_min']) : '';
$price_max = isset($_GET['price_max']) && is_numeric($_GET['price_max']) ? max(0, (float)$_GET['price_max']) : '';

if ($price_min !== '' && $price_max !== '' && $price_max < $price_min) {
    $price_max = $price_min;
}

$in_stock = isset($_GET['in_stock']) ? true : false;

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

$where_clauses = [];
$params = [];

if ($search) {
    $where_clauses[] = "p.name LIKE ?";
    $params[] = '%' . $search . '%';
}

if ($category_filter > 0) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($price_min !== '') {
    $where_clauses[] = "p.price >= ?";
    $params[] = $price_min;
}

if ($price_max !== '') {
    $where_clauses[] = "p.price <= ?";
    $params[] = $price_max;
}

if ($in_stock) {
    $where_clauses[] = "p.stock > 0";
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

if ($search || $category_filter || $price_min !== '' || $price_max !== '' || $in_stock) {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id=c.id $where_sql ORDER BY p.name");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY RAND() LIMIT 8");
    $products = $stmt->fetchAll();
}

require_once __DIR__ . '/header.php';
?>
    <div class="container my-4">
        <div class="d-flex d-md-none justify-content-between mb-3">
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">Filtry</button>
            <a href="mainpage.php" class="btn btn-outline-secondary">Vymazat filtry</a>
        </div>
        <div class="row">
            <aside class="col-md-3 mb-3">
                <div id="filtersCollapse" class="collapse d-md-block">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="mt-3">Filtry</h6>
                        <form method="get" action="mainpage.php">
                            <div class="mb-3">
                                <label for="category" class="form-label">Kategorie</label>
                                <select name="category" id="category" class="form-select form-select-sm">
                                    <option value="">Všechny kategorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>><?= escape($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cena</label>
                                <div class="d-flex gap-2">
                                    <input type="number" name="price_min" id="minInput" class="form-control form-control-sm" min="0" max="99999" step="0.1" value="<?= $price_min ?: 0 ?>">
                                    <span class="align-self-center">-</span>
                                    <input type="number" name="price_max" id="maxInput" class="form-control form-control-sm" min="0" max="99999" step="0.1" value="<?= $price_max ?: 99999 ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="in_stock" id="in_stock" class="form-check-input" value="1" <?= $in_stock ? 'checked' : '' ?>>
                                    <label for="in_stock" class="form-check-label">Pouze skladem</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">Filtrovat</button>
                            <a href="mainpage.php" class="btn btn-outline-secondary btn-sm w-100">Vymazat filtry</a>
                            <input type="hidden" name="search" value="<?= escape($search) ?>">
                        </form>
                    </div>
                </div>
                </div>
            </aside>

            <section class="col-md-9">
                <?php if ($search): ?>
                    <div class="mb-3 text-center text-muted">
                        <?php if (count($products) > 0): ?>
                            Nalezeno <?= count($products) ?> produktů pro "<?= escape($search) ?>"
                        <?php else: ?>
                            Žádné produkty nenalezeny pro "<?= escape($search) ?>"
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

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
                    <div class="col-md-4 mb-4">
                        <a href="product_detail.php?id=<?= $product['id'] ?>" class="text-decoration-none text-reset">
                            <div class="card shadow-sm border-0 h-100">
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
            </section>
        </div>
    </div>

        <script>
        </script>

        <?php require_once __DIR__ . '/footer.php'; ?>