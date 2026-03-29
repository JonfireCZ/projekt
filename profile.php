<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $errors = [];

    if (empty($first_name)) $errors[] = 'Jméno je povinné.';
    if (empty($last_name)) $errors[] = 'Příjmení je povinné.';
    if (empty($email)) $errors[] = 'Email je povinný.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Neplatný formát emailu.';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = 'Tento email je již používán jiným uživatelem.';
    }

    if (empty($errors)) {
        $update_fields = ['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email];

        $set_clause = implode(', ', array_map(fn($k) => "$k = ?", array_keys($update_fields)));
        $values = array_values($update_fields);
        $values[] = $user_id;

        $stmt = $pdo->prepare("UPDATE users SET $set_clause WHERE id = ?");
        $stmt->execute($values);

        $_SESSION['message'] = 'Profil byl úspěšně aktualizován.';
        header('Location: profile.php');
        exit;
    }
}

$stmt = $pdo->prepare("SELECT first_name, last_name, username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$page_title = 'Můj profil — LockerRoom';
$css_path = 'style.css';
$base_url = '';

require_once __DIR__ . '/header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h2 class="card-title mb-4">Můj profil</h2>

                    <?php if (!empty($_SESSION['message'])): ?>
                        <div class="alert alert-success">
                            <?= escape($_SESSION['message']) ?>
                        </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

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
                            <label for="username" class="form-label">Uživatelské jméno</label>
                            <input type="text" class="form-control" id="username" value="<?= escape($user['username']) ?>" readonly>
                            <small class="form-text text-muted">Uživatelské jméno nelze změnit.</small>
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
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>