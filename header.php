<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'LockerRoom' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $css_path ?? 'style.css' ?>">
</head>
<body class="d-flex flex-column">
    
<header class="bg-dark text-white py-3 sticky-top">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="d-flex align-items-center text-white me-3">
                <div style="width:44px;height:44px;border-radius:8px;background:linear-gradient(135deg,var(--brand),var(--brand-600));display:flex;align-items:center;justify-content:center;font-weight:700;margin-right:10px;box-shadow:0 6px 20px rgba(15,52,96,0.18)">LR</div>
                <h1 class="h3 mb-0">LockerRoom</h1>
            </div>
            <?php if (isset($show_search) && $show_search): ?>
                <?php $current_script = basename($_SERVER['PHP_SELF']);
                      $search_action = ($current_script === 'forum.php') ? 'forum.php' : 'mainpage.php';
                      $search_placeholder = ($current_script === 'forum.php') ? 'Hledat uživatele...' : 'Hledat produkty...';
                ?>
                <div class="header-search-wrap ms-3">
                    <!-- desktop: icon button toggles inline search -->
                    <div class="d-none d-md-flex align-items-center">
                        <button id="desktopSearchBtn" class="btn btn-sm btn-outline-light me-2" type="button" data-bs-toggle="collapse" data-bs-target="#desktopSearch" aria-expanded="false" aria-controls="desktopSearch">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                        </button>
                        <div id="desktopSearch" class="collapse">
                            <form method="get" action="<?= $search_action ?>" class="d-flex align-items-center">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="<?= $search_placeholder ?>" value="<?= escape($_GET['search'] ?? '') ?>" style="border-radius: 25px 0 0 25px; border-right: none;">
                                    <button class="btn text-white" type="submit" style="background: #0f3460; border-color: #0f3460; border-radius: 0 25px 25px 0;">Hledat</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- mobile search button handled separately -->
                </div>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center">
            <button class="btn btn-sm btn-outline-light d-md-none me-2" id="mobileMenuBtn" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-expanded="false" aria-controls="siteNav">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 2.75A.75.75 0 011.75 2h12.5a.75.75 0 010 1.5H1.75A.75.75 0 011 2.75zm0 5a.75.75 0 01.75-.75h12.5a.75.75 0 010 1.5H1.75A.75.75 0 011 7.75zm0 5a.75.75 0 01.75-.75h12.5a.75.75 0 010 1.5H1.75a.75.75 0 01-.75-.75z"/></svg>
            </button>
            <button class="btn btn-sm btn-outline-light d-md-none me-2" id="mobileSearchBtn" type="button" aria-expanded="false" aria-controls="mobileSearch">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
            </button>

            <div class="collapse d-md-block" id="siteNav">
                <nav class="d-flex gap-3 align-items-center flex-column flex-md-row">
                    <a href="<?= $base_url ?? '' ?>mainpage.php" class="text-white text-decoration-none">E-shop</a>
                    <a href="<?= $base_url ?? '' ?>forum.php" class="text-white text-decoration-none">Fórum</a>
                    <?php if (is_admin()): ?>
                        <a href="<?= $base_url ?? '' ?>dashboard.php" class="text-white text-decoration-none">Správa</a>
                    <?php endif; ?>
                    <?php if (is_logged_in()): ?>
                        <a href="<?= $base_url ?? '' ?>profile.php" class="text-white text-decoration-none">Můj profil</a>
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
            
            <!-- mobile search input (hidden on md+) -->
            <div id="mobileSearch" class="mobile-search collapse d-md-none ms-2">
                <form method="get" action="<?= $search_action ?>" class="d-flex align-items-center">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="<?= $search_placeholder ?>" value="<?= escape($_GET['search'] ?? '') ?>">
                    <button class="btn btn-sm btn-outline-light ms-2" type="submit">Hledat</button>
                    <button class="btn btn-sm btn-outline-light ms-2" type="button" id="mobileSearchClose">Zavřít</button>
                </form>
            </div>
        </div>
    </div>
</header>

<main class="flex-grow-1">
