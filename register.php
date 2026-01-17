<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $username   = trim($_POST['username']);
    $password   = $_POST['password'];
    $password2  = $_POST['password2'];
    $email      = trim($_POST['email']);
    $note       = isset($_POST['note']) ? trim($_POST['note']) : '';
    if (!$first_name || !$last_name || !$username || !$password || !$password2 || !$email) {
        $errors[] = "Vyplňte všechna povinná pole.";
    }

    if (strpos($email, '@') === false) {
        $errors[] = "Email musí obsahovat '@'.";
    }

    if ($password !== $password2) {
        $errors[] = "Hesla se neshodují.";
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $errors[] = "Uživatelské jméno nebo email již existuje.";
    }

    if (empty($errors)) {
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
            <a href="mainpage.php" class="text-white text-decoration-none">Domů</a>
        </nav>
    </div>
</header>

<main class="flex-grow-1">
<div class="container py-5" style="max-width:640px;">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h2 class="card-title mb-4">Registrace</h2>
            <?php if ($errors): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars(implode('<br>', $errors)) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <form method="post" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3"><input class="form-control" name="first_name" placeholder="Jméno" required></div>
                    <div class="col-md-6 mb-3"><input class="form-control" name="last_name" placeholder="Příjmení" required></div>
                </div>
                <div class="mb-3"><input class="form-control" name="username" placeholder="Username" required></div>
                <div class="row">
                    <div class="col-md-6 mb-3"><input class="form-control" type="password" name="password" placeholder="Heslo" required></div>
                    <div class="col-md-6 mb-3"><input class="form-control" type="password" name="password2" placeholder="Potvrzení hesla" required></div>
                </div>
                <div class="mb-3"><input class="form-control" type="email" name="email" placeholder="Email" required></div>
                <div class="mb-3"><textarea class="form-control" name="note" placeholder="Poznámka (nepovinné)"></textarea></div>
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