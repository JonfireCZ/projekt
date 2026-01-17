<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        $error = "Vyplňte email.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['reset_user_id'] = $user['id'];
            header('Location: reset_pass.php');
            exit;
        } else {
            $error = "Uživatel s tímto emailem nebyl nalezen.";
        }
    }
}
?>
<?php
$page_title = 'Obnova hesla';
$css_path = 'style.css';
$base_url = '';

require_once __DIR__ . '/header.php';
?>
<div class="container py-5" style="max-width:480px;">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h2 class="card-title mb-4">Obnova hesla</h2>
            <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <form method="post" novalidate>
                <div class="mb-3">
                    <label class="form-label">Zadejte svůj email</label>
                    <input type="email" class="form-control" name="email" placeholder="email@email.com" required>
                </div>
                <p class="text-muted small mb-3">Na váš email vám bude zaslán odkaz pro obnovení hesla.</p>
                <button class="btn btn-primary w-100" type="submit">Pokračovat</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>