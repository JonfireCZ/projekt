<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['reset_user_id'])) {
    header('Location: forgot_pass.php');
    exit;
}

$field_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password  = $_POST['new_password']  ?? '';
    $new_password2 = $_POST['new_password2'] ?? '';

    if ($new_password === '') {
        $field_errors['new_password'] = 'Vyplňte nové heslo.';
    }
    
    if ($new_password2 === '') {
        $field_errors['new_password2'] = 'Potvrďte nové heslo.';
    } elseif ($new_password !== $new_password2) {
        $field_errors['new_password2'] = 'Hesla se neshodují.';
    }
    
    if (empty($field_errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $_SESSION['reset_user_id']]);

        unset($_SESSION['reset_user_id']);

        header('Location: login.php?reset=1');
        exit;
    }
}
?>
<?php
$page_title = 'Změna hesla — LockerRoom';
$css_path = 'style.css';
$base_url = '';

require_once __DIR__ . '/header.php';
?>
<div style="max-width: 480px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
  <h2 style="color: #0f3460; margin-bottom: 1.5rem;">Změna hesla</h2>

  <form method="post" autocomplete="off">
    <div class="mb-3">
      <label class="form-label">Nové heslo</label>
      <input type="password" name="new_password" class="form-control <?= isset($field_errors['new_password']) ? 'is-invalid' : '' ?>" required>
      <?php if (isset($field_errors['new_password'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($field_errors['new_password']) ?></div>
      <?php endif; ?>
    </div>

    <div class="mb-3">
      <label class="form-label">Potvrzení nového hesla</label>
      <input type="password" name="new_password2" class="form-control <?= isset($field_errors['new_password2']) ? 'is-invalid' : '' ?>" required>
      <?php if (isset($field_errors['new_password2'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($field_errors['new_password2']) ?></div>
      <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <a href="forgot_pass.php" class="btn btn-outline-primary">Zpět</a>
      <button class="btn btn-primary" type="submit">Změnit heslo</button>
    </div>
  </form>

  <p class="text-center mt-3"><a href="login.php">Přihlásit se</a></p>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
