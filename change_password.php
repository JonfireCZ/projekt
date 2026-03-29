<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    if (empty($current_password)) {
        $errors[] = 'Zadejte současné heslo.';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Současné heslo je nesprávné.';
        }
    }

    if (empty($new_password)) {
        $errors[] = 'Zadejte nové heslo.';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'Nové heslo musí mít alespoň 6 znaků.';
    }

    if ($new_password !== $confirm_password) {
        $errors[] = 'Nové heslo a potvrzení se neshodují.';
    }

    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);

        $_SESSION['message'] = 'Heslo bylo úspěšně změněno.';
        header('Location: profile.php');
        exit;
    }
}

$page_title = 'Změna hesla — LockerRoom';
$css_path = 'style.css';
$base_url = '';

require_once __DIR__ . '/header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h2 class="card-title mb-4">Změna hesla</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= escape($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Současné heslo</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nové heslo</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="form-text text-muted">Heslo musí mít alespoň 6 znaků.</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Potvrdit nové heslo</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Změnit heslo</button>
                        <a href="profile.php" class="btn btn-outline-secondary ms-2">Zpět na profil</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>