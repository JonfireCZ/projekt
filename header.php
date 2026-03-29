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
        <div class="d-flex align-items-center">
            <h1 class="h3 mb-0">LockerRoom</h1>
            <?php if (isset($show_search) && $show_search): ?>
                <form method="get" action="mainpage.php" class="d-flex align-items-center ms-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Hledat produkty..." value="<?= escape($_GET['search'] ?? '') ?>" style="border-radius: 25px 0 0 25px; border-right: none;">
                        <button class="btn text-white" type="submit" style="background: #0f3460; border-color: #0f3460; border-radius: 0 25px 25px 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                        </button>
                        <?php if (!empty($_GET['search'])): ?>
                            <a href="mainpage.php" class="btn btn-outline-secondary ms-2" style="border-radius: 25px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
                                    <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                                </svg>
                                Zrušit
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <nav class="d-flex gap-3 align-items-center">
            <a href="<?= $base_url ?? '' ?>mainpage.php" class="text-white text-decoration-none">E-shop</a>
            <a href="<?= $base_url ?? '' ?>forum.php" class="text-white text-decoration-none">Fórum</a>
            <?php if (is_admin()): ?>
                <a href="<?= $base_url ?? '' ?>admin/dashboard.php" class="text-white text-decoration-none">Správa</a>
            <?php endif; ?>
            <?php if (is_logged_in()): ?>
                <a href="<?= $base_url ?? '' ?>my_orders.php" class="text-white text-decoration-none">Moje objednávky</a>
                <a href="<?= $base_url ?? '' ?>profile.php" class="text-white text-decoration-none">Můj profil</a>
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
