<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

// viewing profile for another user? (profile.php?id=123)
$view_user_id = isset($_GET['id']) ? (int)$_GET['id'] : $user_id;

// only process settings update when viewing own profile
if ($view_user_id === $user_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $errors = [];

    if (empty($username)) $errors[] = 'Uživatelské jméno je povinné.';
    if (empty($first_name)) $errors[] = 'Jméno je povinné.';
    if (empty($last_name)) $errors[] = 'Příjmení je povinné.';
    if (empty($email)) $errors[] = 'Email je povinný.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Neplatný formát emailu.';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = 'Toto uživatelské jméno je již používáno jiným uživatelem.';
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = 'Tento email je již používán jiným uživatelem.';
    }

    if (empty($errors)) {
        $update_fields = ['username' => $username, 'first_name' => $first_name, 'last_name' => $last_name, 'email' => $email];

        $set_clause = implode(', ', array_map(fn($k) => "$k = ?", array_keys($update_fields)));
        $values = array_values($update_fields);
        $values[] = $user_id;

        $stmt = $pdo->prepare("UPDATE users SET $set_clause WHERE id = ?");
        $stmt->execute($values);

        $_SESSION['username'] = $username; // Update session
        $_SESSION['message'] = 'Profil byl úspěšně aktualizován.';
        header('Location: profile.php');
        exit;
    }
}

$stmt = $pdo->prepare("SELECT id, first_name, last_name, username, email FROM users WHERE id = ?");
$stmt->execute([$view_user_id]);
$user = $stmt->fetch();

// fetch forum posts by this user
$stmt = $pdo->prepare("SELECT id, title, category, views, comments_count, likes_count, created_at FROM forum_posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$view_user_id]);
$user_posts = $stmt->fetchAll();

// fetch shared public order items (grouped) for this user
$stmt = $pdo->prepare("SELECT oi.product_id, oi.product_name, oi.price, SUM(oi.quantity) AS total_quantity
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.user_id = ? AND COALESCE(o.is_public,0) = 1
    GROUP BY oi.product_id, oi.product_name, oi.price
    ORDER BY MAX(o.created_at) DESC");
$stmt->execute([$view_user_id]);
$shared_items = $stmt->fetchAll();

// fetch orders depending on viewer: owner sees all, others see only public orders
if ($view_user_id === $user_id) {
    $stmt = $pdo->prepare("SELECT id, total_price, status, created_at, COALESCE(is_public,0) AS is_public, shipping_address FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$view_user_id]);
    $orders = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT id, created_at FROM orders WHERE user_id = ? AND COALESCE(is_public,0) = 1 ORDER BY created_at DESC");
    $stmt->execute([$view_user_id]);
    $orders = $stmt->fetchAll();
    // load items for public orders
    $public_order_items = [];
    if (!empty($orders)) {
        $order_ids = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        $stmt = $pdo->prepare("SELECT order_id, product_name, quantity, price FROM order_items WHERE order_id IN ($placeholders) ORDER BY order_id");
        $stmt->execute($order_ids);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $public_order_items[$r['order_id']][] = $r;
        }
    }
}

// determine active tab for both own and other's profiles
$tab = $_GET['tab'] ?? 'posts';

$page_title = ($view_user_id === $user_id) ? 'Můj profil — LockerRoom' : ('Profil uživatele ' . ($user['username'] ?? ''));
$css_path = 'style.css';
$base_url = '';

require_once __DIR__ . '/header.php';
?>

<div class="container my-4">
    <div class="main-grid">
        <div>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h2 class="card-title mb-4"><?php if ($view_user_id === $user_id) { echo 'Můj profil'; } else { echo 'Profil uživatele ' . escape($user['username']); } ?></h2>

                    <?php if (!empty($_SESSION['message']) && $view_user_id === $user_id): ?>
                        <div class="alert alert-success">
                            <?= escape($_SESSION['message']) ?>
                        </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

                    <?php if (!empty($errors) && $view_user_id === $user_id): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= escape($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($view_user_id === $user_id): ?>
                        <?php $tab = $_GET['tab'] ?? 'posts'; ?>
                        <ul class="nav nav-tabs mb-3">
                            <li class="nav-item"><a class="nav-link <?= $tab === 'posts' ? 'active' : '' ?>" href="<?= $view_user_id === $user_id ? 'profile.php' : 'profile.php?id=' . $view_user_id ?>">Příspěvky</a></li>
                            <li class="nav-item"><a class="nav-link <?= $tab === 'orders' ? 'active' : '' ?>" href="<?= $view_user_id === $user_id ? 'profile.php?tab=orders' : 'profile.php?id=' . $view_user_id . '&tab=orders' ?>">Objednávky</a></li>
                            <?php if ($view_user_id === $user_id): ?>
                                <li class="nav-item"><a class="nav-link <?= $tab === 'settings' ? 'active' : '' ?>" href="profile.php?tab=settings">Nastavení</a></li>
                            <?php endif; ?>
                        </ul>

                        <?php if ($tab === 'orders'): ?>
                            <?php if (empty($orders)): ?>
                                <div class="text-center py-5">
                                    <h5 class="text-muted">Žádné objednávky k zobrazení.</h5>
                                </div>
                            <?php else: ?>
                                <?php if ($view_user_id === $user_id): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <?php
                                            $stmt = $pdo->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
                                            $stmt->execute([$order['id']]);
                                            $order_items = $stmt->fetchAll();
                                            $address = json_decode($order['shipping_address'] ?? '{}', true);
                                            $collapseId = 'orderDetails-' . $order['id'];
                                        ?>
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <a href="order_details.php?id=<?= $order['id'] ?>" class="text-dark text-decoration-none">
                                                            <strong>Objednávka #<?= $order['id'] ?></strong>
                                                            <div class="small text-muted">Vytvořeno: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?> • <?= number_format($order['total_price'], 2, ',', ' ') ?> Kč</div>
                                                        </a>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="badge <?= status_badge_class($order['status']) ?>"><?= translate_status($order['status']) ?></span>
                                                        <?php if (!empty($order['is_public'])): ?><span class="badge bg-secondary ms-2">Veřejná</span><?php endif; ?>
                                                        <button type="button" class="btn btn-sm btn-link ms-2" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">Zobrazit</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="collapse" id="<?= $collapseId ?>">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <h6>Položky objednávky:</h6>
                                                            <ul class="list-unstyled">
                                                                <?php foreach ($order_items as $it): ?>
                                                                    <li class="mb-1"><?= escape($it['product_name']) ?> <span class="text-muted">(<?= (int)$it['quantity'] ?>× <?= number_format($it['price'], 2, ',', ' ') ?> Kč)</span></li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <h6>Dodací adresa:</h6>
                                                            <address>
                                                                <?= escape($address['first_name'] ?? '') ?> <?= escape($address['last_name'] ?? '') ?><br>
                                                                <?= escape($address['street'] ?? '') ?><br>
                                                                <?= escape($address['zip'] ?? '') ?> <?= escape($address['city'] ?? '') ?><br>
                                                                <small class="text-muted">Tel: <?= escape($address['phone'] ?? '') ?></small>
                                                            </address>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($orders as $o): ?>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h6 class="mb-2">Objednávka #<?= $o['id'] ?> <small class="text-muted">(<?= date('d.m.Y', strtotime($o['created_at'])) ?>)</small></h6>
                                                <?php $items = $public_order_items[$o['id']] ?? []; ?>
                                                <ul class="list-unstyled mb-0">
                                                    <?php foreach ($items as $it): ?>
                                                        <li><?= escape($it['product_name']) ?> <span class="text-muted">(<?= (int)$it['quantity'] ?>× <?= number_format($it['price'], 2, ',', ' ') ?> Kč)</span></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif ($tab === 'settings'): ?>
                            <form method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Uživatelské jméno</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?= escape($user['username']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="first_name" class="form-label">Jméno</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?= escape($user['first_name'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Příjmení</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?= escape($user['last_name'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= escape($user['email']) ?>" required>
                                </div>

                                <button type="submit" class="btn btn-primary">Uložit změny</button>
                                <a href="change_password.php" class="btn btn-outline-secondary ms-2">Změnit heslo</a>
                            </form>
                        <?php else: ?>
                            <?php if (empty($user_posts)): ?>
                                <div class="text-center py-5">
                                    <h5 class="text-muted">Uživatel zatím nepřidal žádné příspěvky.</h5>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($user_posts as $p): ?>
                                        <a href="forum_post.php?id=<?= $p['id'] ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h5 class="mb-1"><?= escape($p['title']) ?></h5>
                                                <small class="text-muted"><?= date('d.m.Y', strtotime($p['created_at'])) ?></small>
                                            </div>
                                            <p class="mb-1 small text-muted"><?= escape($p['category'] ?? '') ?> • Zobrazení <?= $p['views'] ?> • Komentáře <?= $p['comments_count'] ?> • Lajky <?= $p['likes_count'] ?></p>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php // viewing another user's profile: show tabs (posts/orders) and shared items ?>
                        <?php $tab = $_GET['tab'] ?? 'posts'; ?>
                        <ul class="nav nav-tabs mb-3">
                            <li class="nav-item"><a class="nav-link <?= $tab === 'posts' ? 'active' : '' ?>" href="profile.php?id=<?= $view_user_id ?>">Příspěvky</a></li>
                            <li class="nav-item"><a class="nav-link <?= $tab === 'orders' ? 'active' : '' ?>" href="profile.php?id=<?= $view_user_id ?>&tab=orders">Objednávky</a></li>
                        </ul>

                        <?php if ($tab === 'orders'): ?>
                            <?php if (empty($orders)): ?>
                                <div class="text-center py-5">
                                    <h5 class="text-muted">Žádné veřejné objednávky k zobrazení.</h5>
                                </div>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <?php $items = $public_order_items[$o['id']] ?? []; ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="mb-2">Objednávka #<?= $o['id'] ?> <small class="text-muted">(<?= date('d.m.Y', strtotime($o['created_at'])) ?>)</small></h6>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($items as $it): ?>
                                                    <li><?= escape($it['product_name']) ?> <span class="text-muted">(<?= (int)$it['quantity'] ?>× <?= number_format($it['price'], 2, ',', ' ') ?> Kč)</span></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($shared_items)): ?>
                                    <div class="divider"></div>
                                    <h5>Sdílené položky</h5>
                                    <div class="list-group">
                                        <?php foreach ($shared_items as $it): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?= escape($it['product_name']) ?></strong>
                                                    <div class="small text-muted"><?= number_format($it['price'], 2, ',', ' ') ?> Kč • Celkem objednáno: <?= (int)$it['total_quantity'] ?></div>
                                                </div>
                                                <div>
                                                    <form method="post" action="add_shared_to_cart.php">
                                                        <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary">Přidat do košíku</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (empty($user_posts)): ?>
                                <div class="text-center py-5">
                                    <h5 class="text-muted">Uživatel zatím nepřidal žádné příspěvky.</h5>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($user_posts as $p): ?>
                                        <a href="forum_post.php?id=<?= $p['id'] ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h5 class="mb-1"><?= escape($p['title']) ?></h5>
                                                <small class="text-muted"><?= date('d.m.Y', strtotime($p['created_at'])) ?></small>
                                            </div>
                                            <p class="mb-1 small text-muted"><?= escape($p['category'] ?? '') ?> • Zobrazení <?= $p['views'] ?> • Komentáře <?= $p['comments_count'] ?> • Lajky <?= $p['likes_count'] ?></p>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- shared items moved to Orders tab to avoid showing on Posts view -->
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <aside class="card page-surface">
            <div class="card-body text-center">
                <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center fw-bold" style="width:90px;height:90px;background:var(--brand);color:#fff;font-size:34px;">
                    <?= strtoupper(substr($user['username'] ?? '', 0, 1)) ?>
                </div>
                <h5 class="mt-3 mb-1"><?= escape(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></h5>
                <div class="text-muted">@<?= escape($user['username'] ?? '') ?></div>

                <?php if ($view_user_id === $user_id): ?>
                    <div class="mt-3">
                        <a href="profile.php?tab=settings" class="btn btn-outline-primary btn-sm">Upravit profil</a>
                    </div>
                    <div class="mt-2 text-muted small"><?= escape($user['email'] ?? '') ?></div>
                <?php else: ?>
                    <div class="mt-3">
                        <a href="message.php?to=<?= $view_user_id ?>" class="btn btn-outline-primary btn-sm">Poslat zprávu</a>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>