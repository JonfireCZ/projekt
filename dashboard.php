<?php
require_once __DIR__ . '/../database_connect.php';
require_once __DIR__ . '/../functions.php';

require_login();
if (!is_admin()) {
    header('Location: ../mainpage.php');
    exit;
}

$page_title = 'Admin Panel — LockerRoom';
$css_path = '../style.css';
$base_url = '../';

require_once __DIR__ . '/../header.php';
?>
    <div class="container py-5">
        <div class="row">
            <aside class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title">Správa</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <a href="manage_products.php" class="btn btn-outline-primary btn-sm w-100 text-start">
                                    Produkty
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="manage_users.php" class="btn btn-outline-primary btn-sm w-100 text-start">
                                    Uživatelé
                                </a>
                            </li>
                            <li>
                                <a href="manage_orders.php" class="btn btn-outline-primary btn-sm w-100 text-start">
                                    Objednávky
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </aside>

            <section class="col-md-9">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h2>Vítejte v Admin Panelu</h2>
                        <p class="text-muted">Vyberte jednu z možností v levém menu pro správu obsahu.</p>
                        <hr>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h5>Správa Produktů</h5>
                                <p>Přidávejte, upravujte a odstraňujte produkty z e-shopu.</p>
                                <a href="manage_products.php" class="btn btn-primary btn-sm">Spravovat produkty</a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h5>Správa Uživatelů</h5>
                                <p>Spravujte uživatele, měňte jejich role a oprávnění.</p>
                                <a href="manage_users.php" class="btn btn-primary btn-sm">Spravovat uživatele</a>
                            </div>
                            <div class="col-md-6">
                                <h5>Správa Objednávek</h5>
                                <p>Zobrazujte a spravujte objednávky zákazníků.</p>
                                <a href="manage_orders.php" class="btn btn-primary btn-sm">Spravovat objednávky</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

<?php require_once __DIR__ . '/../footer.php'; ?>
