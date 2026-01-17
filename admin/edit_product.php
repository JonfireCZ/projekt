<?php
require_once __DIR__ . '/../database_connect.php';
require_once __DIR__ . '/../functions.php';

require_login();
if (!is_admin()) {
    header('Location: ../mainpage.php');
    exit;
}

$product = null;
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product) {
        header('Location: manage_products.php');
        exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $image_path = $product['image_path'] ?? '';
    $image_blob = $product['image_blob'] ?? null;
    $image_mime = $product['image_mime'] ?? null;

    if (!$name || !$description || $price <= 0) {
        $error = 'Vyplňte všechna povinná pole a cena musí být větší než 0.';
    } else {
        if (!empty($_FILES['image']['name'])) {
            $file = $_FILES['image'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!in_array($file['type'], $allowed)) {
                $error = 'Povolené formáty: JPEG, PNG, GIF, WebP.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'Soubor je příliš velký (max 5MB).';
            } else {
                $data = file_get_contents($file['tmp_name']);
                if ($data === false) {
                    $error = 'Chyba při čtení souboru.';
                } else {
                    // store in DB blob and mime, clear file path (we keep backward compatibility)
                    $image_blob = $data;
                    $image_mime = $file['type'];
                    $image_path = null;
                }
            }
        }

        if (!$error) {
            if ($product_id) {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, image_blob = ?, image_mime = ?, image_path = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $stock, $category_id ?: null, $image_blob, $image_mime, $image_path ?: null, $product_id]);
                header('Location: manage_products.php?updated=1');
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category_id, image_blob, image_mime, image_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $description, $price, $stock, $category_id ?: null, $image_blob, $image_mime, $image_path ?: null]);
                header('Location: manage_products.php?created=1');
            }
            exit;
        }
    }
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

$page_title = ($product_id ? 'Upravit Produkt' : 'Nový Produkt') . ' — Admin Panel';
$css_path = '../style.css';
$base_url = '../';

require_once __DIR__ . '/../header.php';
?>
    <div class="container py-5" style="max-width:640px;">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Název produktu *</label>
                        <input type="text" class="form-control" name="name" value="<?= escape($product['name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Popis *</label>
                        <textarea class="form-control" name="description" rows="5" required><?= escape($product['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Obrázek produktu</label>
                        <?php if ($product && (!empty($product['image_blob']) || (!empty($product['image_path']) && file_exists(__DIR__ . '/../' . $product['image_path'])))): ?>
                            <div class="mb-2">
                                <?php if (!empty($product['image_blob'])): ?>
                                    <img src="../image.php?id=<?= $product['id'] ?>" alt="<?= escape($product['name']) ?>" style="max-height: 150px; max-width: 200px;">
                                <?php else: ?>
                                    <img src="../<?= escape($product['image_path']) ?>" alt="<?= escape($product['name']) ?>" style="max-height: 150px; max-width: 200px;">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="image" accept="image/*">
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cena (Kč) *</label>
                            <input type="number" step="0.01" class="form-control" name="price" value="<?= $product['price'] ?? '' ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Počet kusů na skladě *</label>
                            <input type="number" class="form-control" name="stock" value="<?= $product['stock'] ?? '0' ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kategorie</label>
                            <select class="form-control" name="category_id">
                                <option value="">-- Bez kategorie --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($product && $product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= escape($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="manage_products.php" class="btn btn-outline-secondary">Zpět</a>
                        <button type="submit" class="btn btn-primary ms-auto">
                            <?= $product ? 'Uložit změny' : 'Vytvořit produkt' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../footer.php'; ?>
