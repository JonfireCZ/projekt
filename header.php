<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'LockerRoom' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $css_path ?? 'style.css' ?>">
</head>
<body class="d-flex flex-column">
    
<header class="bg-dark text-white py-3 sticky-top">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">LockerRoom</h1>
        <nav class="d-flex gap-3 align-items-center">
            <a href="<?= $base_url ?? '' ?>mainpage.php" class="text-white text-decoration-none">Hlavní stránka</a>
            <a href="#" class="text-white text-decoration-none">Fórum</a>
            <?php if (is_admin()): ?>
                <a href="<?= $base_url ?? '' ?>admin/dashboard.php" class="text-white text-decoration-none">Správa</a>
            <?php endif; ?>
            <?php if (is_logged_in()): ?>
                <a href="<?= $base_url ?? '' ?>my_orders.php" class="text-white text-decoration-none">Moje objednávky</a>
                <a href="<?= $base_url ?? '' ?>cart.php" class="btn btn-sm btn-outline-light">
                    <?php $cart_count = count(get_cart_items()); ?>
                    Košík (<?= $cart_count ?>)
                </a>
            <?php endif; ?>
            <?php if (is_logged_in()): ?>
                <a href="<?= $base_url ?? '' ?>logout.php" class="btn btn-sm btn-outline-light">Odhlásit</a>
            <?php else: ?>
                <a href="<?= $base_url ?? '' ?>login.php" class="btn btn-sm btn-outline-light">Přihlásit</a>
                <a href="<?= $base_url ?? '' ?>register.php" class="btn btn-sm btn-light">Registrovat</a>
            <?php endif; ?>
        </nav>
        </div>
    </header>

<main class="flex-grow-1">
