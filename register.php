<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$field_errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $password2  = $_POST['password2'] ?? '';
    $email      = trim($_POST['email'] ?? '');
    $note       = trim($_POST['note'] ?? '');
    
    // Uložení dat pro zachování hodnot
    $form_data = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'username' => $username,
        'email' => $email,
        'note' => $note
    ];
    
    // Validace jednotlivých polí
    if (!$first_name) {
        $field_errors['first_name'] = "Vyplňte jméno.";
    }
    if (!$last_name) {
        $field_errors['last_name'] = "Vyplňte příjmení.";
    }
    if (!$username) {
        $field_errors['username'] = "Vyplňte uživatelské jméno.";
    }
    if (!$email) {
        $field_errors['email'] = "Vyplňte email.";
    } elseif (strpos($email, '@') === false) {
        $field_errors['email'] = "Email musí obsahovat '@'.";
    }
    if (!$password) {
        $field_errors['password'] = "Vyplňte heslo.";
    }
    if (!$password2) {
        $field_errors['password2'] = "Potvrďte heslo.";
    } elseif ($password !== $password2) {
        $field_errors['password2'] = "Hesla se neshodují.";
    }

    // Kontrola duplicity pouze pokud jsou email a username vyplněny
    if ($username && $email && empty($field_errors['username']) && empty($field_errors['email'])) {
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($existing = $stmt->fetch()) {
            if ($existing['username'] === $username) {
                $field_errors['username'] = "Uživatelské jméno již existuje.";
            }
            if ($existing['email'] === $email) {
                $field_errors['email'] = "Email je již registrován.";
            }
        }
    }

    if (empty($field_errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, password, role, note) VALUES (?, ?, ?, ?, ?, 'customer', ?)");
        $stmt->execute([$first_name, $last_name, $username, $email, $hashed_password, $note]);
        header('Location: login.php?registered=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Registrace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="d-flex flex-column">
<header class="bg-dark py-3 sticky-top">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0 text-white">LockerRoom</h1>
        <nav class="d-flex gap-2">
            <a href="index.php" class="text-white text-decoration-none">Domů</a>
        </nav>
    </div>
</header>

<main class="flex-grow-1">
<div class="container py-5" style="max-width:640px;">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h2 class="card-title mb-4">Registrace</h2>
            <form method="post" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input class="form-control <?= isset($field_errors['first_name']) ? 'is-invalid' : '' ?>" name="first_name" placeholder="Jméno" value="<?= htmlspecialchars($form_data['first_name'] ?? '') ?>" required>
                        <?php if (isset($field_errors['first_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($field_errors['first_name']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input class="form-control <?= isset($field_errors['last_name']) ? 'is-invalid' : '' ?>" name="last_name" placeholder="Příjmení" value="<?= htmlspecialchars($form_data['last_name'] ?? '') ?>" required>
                        <?php if (isset($field_errors['last_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($field_errors['last_name']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <input class="form-control <?= isset($field_errors['username']) ? 'is-invalid' : '' ?>" name="username" placeholder="Username" value="<?= htmlspecialchars($form_data['username'] ?? '') ?>" required>
                    <?php if (isset($field_errors['username'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($field_errors['username']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input class="form-control <?= isset($field_errors['password']) ? 'is-invalid' : '' ?>" type="password" name="password" placeholder="Heslo" required>
                        <?php if (isset($field_errors['password'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($field_errors['password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input class="form-control <?= isset($field_errors['password2']) ? 'is-invalid' : '' ?>" type="password" name="password2" placeholder="Potvrzení hesla" required>
                        <?php if (isset($field_errors['password2'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($field_errors['password2']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <input class="form-control <?= isset($field_errors['email']) ? 'is-invalid' : '' ?>" type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required>
                    <?php if (isset($field_errors['email'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($field_errors['email']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <textarea class="form-control" name="note" placeholder="Poznámka (nepovinné)"><?= htmlspecialchars($form_data['note'] ?? '') ?></textarea>
                </div>
                <button class="btn btn-primary w-100" type="submit">Registrovat</button>
            </form>
            <p class="text-center mt-3">Máte již účet? <a href="login.php">Přihlaste se</a></p>
        </div>
    </div>
</div>
</main>

<footer class="bg-dark text-white text-center py-3 mt-auto">
    <p class="mb-1">&copy; <?= date('Y') ?> LockerRoom</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>