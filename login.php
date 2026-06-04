<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: mainpage.php');
    exit;
}

$error = '';
$info = '';
$field_errors = [];
$email_value = '';

if (isset($_GET['registered'])) {
    $info = 'Registrace proběhla úspěšně. Přihlaste se.';
}
if (isset($_GET['reset'])) {
    $info = 'Heslo bylo změněno. Přihlaste se.';
}
if (isset($_GET['logout'])) {
    $info = 'Odhlášení proběhlo úspěšně!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $email_value = $email;

    if ($email === '') {
        $field_errors['email'] = 'Vyplňte email.';
    } elseif (strpos($email, '@') === false) {
        $field_errors['email'] = 'Email musí obsahovat "@".';
    }
    
    if ($password === '') {
        $field_errors['password'] = 'Vyplňte heslo.';
    }

    if (empty($field_errors)) {
        $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $field_errors['email'] = 'Email nebyl registrován.';
        } elseif (!password_verify($password, $user['password'])) {
            $field_errors['password'] = 'Zadané heslo bylo chybné.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: mainpage.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Přihlášení — LockerRoom</title>
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
<div class="container py-5" style="max-width:480px;">
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <h2 class="card-title mb-4">Přihlášení</h2>

      <?php if ($info): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($info) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="text" name="email" class="form-control <?= isset($field_errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($email_value) ?>" required>
          <?php if (isset($field_errors['email'])): ?>
            <div class="invalid-feedback d-block"><?= htmlspecialchars($field_errors['email']) ?></div>
          <?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label">Heslo</label>
          <input type="password" name="password" class="form-control <?= isset($field_errors['password']) ? 'is-invalid' : '' ?>" required>
          <?php if (isset($field_errors['password'])): ?>
            <div class="invalid-feedback d-block"><?= htmlspecialchars($field_errors['password']) ?></div>
          <?php endif; ?>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
          <div></div>
          <button class="btn btn-primary" type="submit">Přihlásit</button>
        </div>
      </form>

      <p class="text-center mt-3">Nemáte u nás účet? <a href="register.php">Registrujte se</a></p>
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