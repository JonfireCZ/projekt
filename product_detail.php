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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'add') {
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
    
    elseif ($_POST['action'] === 'add_review' && is_logged_in()) {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        if ($rating < 1 || $rating > 5) {
            $error = 'Vyberte hodnocení od 1 do 5 hvězdiček.';
        } elseif (!$content) {
            $error = 'Zadejte text recenze.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, title, content) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$product_id, $_SESSION['user_id'], $rating, $title, $content]);
            header("Location: product_detail.php?id=$product_id#reviews");
            exit;
        }
    }
    
    elseif ($_POST['action'] === 'like_review' && is_logged_in()) {
        $review_id = (int)($_POST['review_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id FROM product_review_likes WHERE review_id = ? AND user_id = ?");
        $stmt->execute([$review_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM product_review_likes WHERE review_id = ? AND user_id = ?");
            $stmt->execute([$review_id, $_SESSION['user_id']]);
            $stmt = $pdo->prepare("UPDATE product_reviews SET likes_count = likes_count - 1 WHERE id = ?");
            $stmt->execute([$review_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO product_review_likes (review_id, user_id) VALUES (?, ?)");
            $stmt->execute([$review_id, $_SESSION['user_id']]);
            $stmt = $pdo->prepare("UPDATE product_reviews SET likes_count = likes_count + 1 WHERE id = ?");
            $stmt->execute([$review_id]);
        }
        header("Location: product_detail.php?id=$product_id#reviews");
        exit;
    }
    
    elseif ($_POST['action'] === 'delete_review' && is_logged_in()) {
        $review_id = (int)($_POST['review_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT user_id FROM product_reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $review = $stmt->fetch();
        
        if ($review && (is_admin() || $review['user_id'] == $_SESSION['user_id'])) {
            $stmt = $pdo->prepare("DELETE FROM product_reviews WHERE id = ?");
            $stmt->execute([$review_id]);
            header("Location: product_detail.php?id=$product_id#reviews");
            exit;
        }
    }
}

$stmt = $pdo->prepare("
    SELECT 
        pr.id,
        pr.rating,
        pr.title,
        pr.content,
        pr.likes_count,
        pr.created_at,
        pr.user_id,
        u.username,
        u.role
    FROM product_reviews pr
    LEFT JOIN users u ON pr.user_id = u.id
    WHERE pr.product_id = ?
    ORDER BY pr.created_at DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

$user_review_likes = [];
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT review_id FROM product_review_likes WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    while ($row = $stmt->fetch()) {
        $user_review_likes[$row['review_id']] = true;
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

                <?php if ($error): ?>
                    <div class="alert alert-danger mt-3"><?= escape($error) ?></div>
                <?php endif; ?>

                <div id="reviews" class="mt-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="#0f3460" class="bi bi-star-fill me-3" viewBox="0 0 16 16">
                                    <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                                </svg>
                                <h3 class="h4 mb-0 fw-bold" style="color: #0f3460;">Recenze</h3>
                                <span class="badge ms-3 px-4 py-2" style="background: #0f3460; border-radius: 20px;"><?= count($reviews) ?></span>
                            </div>

                            <?php if (empty($reviews)): ?>
                                <div class="text-center py-5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="#0f3460" class="bi bi-star mb-3 opacity-50" viewBox="0 0 16 16">
                                        <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z"/>
                                    </svg>
                                    <h5 class="fw-bold" style="color: #0f3460;">Zatím žádné recenze</h5>
                                    <p class="text-muted">Buďte první, kdo ohodnotí tento produkt!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="mb-3 p-4" style="background: rgba(15,52,96,0.03); border-left: 4px solid #0f3460; border-radius: 10px;">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 45px; height: 45px; background: #0f3460;">
                                                    <?= strtoupper(substr($review['username'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold" style="color: #0f3460;">
                                                        <?= escape($review['username']) ?>
                                                        <?php if ($review['role'] === 'admin'): ?>
                                                            <span class="badge bg-danger ms-2">Admin</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="mb-1">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="<?= $i <= $review['rating'] ? '#ffc107' : '#e0e0e0' ?>" class="bi bi-star-fill" viewBox="0 0 16 16">
                                                                <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                                                            </svg>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                                                            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                                        </svg>
                                                        <?= date('d.m.Y H:i', strtotime($review['created_at'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <?php if (is_logged_in()): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="like_review">
                                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                        <button type="submit" class="btn btn-sm <?= isset($user_review_likes[$review['id']]) ? 'btn-danger' : 'btn-outline-danger' ?>" style="border-radius: 20px;">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-heart-fill me-1" viewBox="0 0 16 16">
                                                                <path fill-rule="evenodd" d="M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314z"/>
                                                            </svg>
                                                            <?= $review['likes_count'] ?>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-danger px-2 py-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-heart-fill me-1" viewBox="0 0 16 16">
                                                            <path fill-rule="evenodd" d="M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314z"/>
                                                        </svg>
                                                        <?= $review['likes_count'] ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (is_logged_in() && (is_admin() || $review['user_id'] == $_SESSION['user_id'])): ?>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Opravdu chcete smazat tuto recenzi?');">
                                                        <input type="hidden" name="action" value="delete_review">
                                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 20px;">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($review['title']): ?>
                                            <h5 class="fw-bold mb-2" style="margin-left: 60px; color: #0f3460;"><?= escape($review['title']) ?></h5>
                                        <?php endif; ?>
                                        <div style="line-height: 1.7; color: #333; margin-left: 60px;"><?= nl2br(escape($review['content'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (is_logged_in()): ?>
                    <div class="card shadow-sm border-0 mt-3">
                        <div class="card-header py-4" style="background: #0f3460; border-radius: 0;">
                            <h4 class="h5 mb-0 fw-bold text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-pencil-square me-2" viewBox="0 0 16 16">
                                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                </svg>
                                Napište recenzi
                            </h4>
                        </div>
                        <div class="card-body p-4">
                            <form method="post">
                                <input type="hidden" name="action" value="add_review">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Hodnocení</label>
                                    <div class="btn-group d-flex gap-2" role="group">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <input type="radio" class="btn-check" name="rating" id="rating<?= $i ?>" value="<?= $i ?>" required>
                                            <label class="btn btn-outline-warning flex-fill" for="rating<?= $i ?>">
                                                <?= $i ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
                                                    <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                                                </svg>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nadpis <span class="text-muted fw-normal">(nepovinné)</span></label>
                                    <input type="text" name="title" class="form-control" placeholder="Shrňte svou zkušenost...">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Recenze</label>
                                    <textarea name="content" class="form-control" rows="4" placeholder="Popište svou zkušenost s produktem..." required style="resize: vertical;"></textarea>
                                </div>
                                <button type="submit" class="btn btn-lg px-5 text-white" style="background: #0f3460; border-color: #0f3460; border-radius: 25px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-send me-2" viewBox="0 0 16 16">
                                        <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
                                    </svg>
                                    Odeslat recenzi
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm border-0 mt-3">
                        <div class="card-body text-center py-5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#0f3460" class="bi bi-lock mb-3" viewBox="0 0 16 16">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/>
                            </svg>
                            <h5 class="fw-bold mb-3" style="color: #0f3460;">Přidejte svou recenzi</h5>
                            <p class="text-muted mb-4">Pro přidání recenze se musíte přihlásit</p>
                            <a href="login.php" class="btn btn-lg px-5 me-3 text-white" style="background: #0f3460; border-color: #0f3460; border-radius: 25px;">Přihlásit se</a>
                            <a href="register.php" class="btn btn-outline-dark btn-lg px-5" style="border-radius: 25px;">Registrovat se</a>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

<?php require_once __DIR__ . '/footer.php'; ?>
