<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : $category_id;

if ($selected_category != $category_id) {
    if ($selected_category == 0) {
        $query = $_GET;
        unset($query['id'], $query['category']);
        header('Location: mainpage.php?' . http_build_query($query));
        exit;
    } else {
        $query = $_GET;
        $query['id'] = $selected_category;
        unset($query['category']);
        header('Location: category.php?' . http_build_query($query));
        exit;
    }
}

$price_min = isset($_GET['price_min']) && is_numeric($_GET['price_min']) ? max(0, (float)$_GET['price_min']) : '';
$price_max = isset($_GET['price_max']) && is_numeric($_GET['price_max']) ? max(0, (float)$_GET['price_max']) : '';

if ($price_min !== '' && $price_max !== '' && $price_max < $price_min) {
    $price_max = $price_min;
}

$in_stock = isset($_GET['in_stock']) ? true : false;

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: mainpage.php');
    exit;
}

$where_clauses = ["p.category_id = ?"];
$params = [$category_id];

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

$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

$stmt = $pdo->prepare("SELECT * FROM products p $where_sql ORDER BY created_at DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

$page_title = escape($category['name']) . ' — LockerRoom';
$css_path = 'style.css';
$base_url = '';
$show_search = true;

require_once __DIR__ . '/header.php';
?>
    <div class="container my-4">
        <div class="row">
            <aside class="col-md-3 mb-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="mt-3">Filtry</h6>
                        <form method="get" action="category.php">
                            <input type="hidden" name="id" value="<?= $category_id ?>">
                            <div class="mb-3">
                                <label for="category" class="form-label">Kategorie</label>
                                <select name="category" id="category" class="form-select form-select-sm">
                                    <option value="">Všechny kategorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= escape($cat['name']) ?></option>
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
                            <a href="category.php?id=<?= $category_id ?>" class="btn btn-outline-secondary btn-sm w-100">Vymazat filtry</a>
                        </form>
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
                            <a href="mainpage.php" class="btn btn-primary">Zpět na hlavní stránku</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                        <div class="col-md-6 mb-4">
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
                                        <p class="card-text"><?= escape(substr($product['description'], 0, 100)) ?>...</p>
                                        <h6 class="text-primary"><?= number_format($product['price'], 1, ',', ' ') ?> Kč</h6>
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
