<?php
require_once __DIR__ . '/../database_connect.php';
require_once __DIR__ . '/../functions.php';

require_login();
if (!is_admin()) {
    header('Location: ../mainpage.php');
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT id, first_name, last_name, email, username, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: manage_users.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!$first_name || !$last_name || !$email) {
        $error = 'Vyplňte všechna pole.';
    } elseif (strpos($email, '@') === false) {
        $error = 'Email musí obsahovat "@".';
    } else {

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Tento email již existuje.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $user_id]);
            $success = 'Údaje uživatele byly úspěšně aktualizovány.';
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, username, role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        }
    }
}

$page_title = 'Upravit Uživatele — Admin Panel';
$css_path = '../style.css';
$base_url = '../';

require_once __DIR__ . '/../header.php';
?>
    <div class="container py-5" style="max-width:640px;">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title">Údaje uživatele: <?= escape($user['username']) ?></h5>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jméno *</label>
                            <input type="text" class="form-control" name="first_name" value="<?= escape($user['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Příjmení *</label>
                            <input type="text" class="form-control" name="last_name" value="<?= escape($user['last_name']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" value="<?= escape($user['email']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= escape($user['role']) ?>" disabled>
                        <small class="text-muted">Roli můžete měnit v seznamu uživatelů.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="manage_users.php" class="btn btn-outline-secondary">Zpět</a>
                        <button type="submit" class="btn btn-primary ms-auto">Uložit změny</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../footer.php'; ?>
