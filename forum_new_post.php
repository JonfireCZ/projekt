<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$page_title = 'Nový příspěvek - Fórum';
$css_path = 'style.css';
$base_url = '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? '');
    
    if (!$title) {
        $error = 'Zadejte nadpis příspěvku.';
    } elseif (!$content) {
        $error = 'Zadejte obsah příspěvku.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO forum_posts (user_id, title, content, category) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $content, $category ?: null]);
        $post_id = $pdo->lastInsertId();
        
        header("Location: forum_post.php?id=$post_id");
        exit;
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h2 class="h4 mb-0">Vytvořit nový příspěvek</h2>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= escape($error) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nadpis příspěvku</label>
                            <input type="text" name="title" class="form-control form-control-lg" value="<?= escape($_POST['title'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Kategorie <span class="text-muted fw-normal">(nepovinné)</span></label>
                            <input type="text" name="category" class="form-control" value="<?= escape($_POST['category'] ?? '') ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Obsah příspěvku</label>
                            <textarea name="content" class="form-control" rows="10" required><?= escape($_POST['content'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary btn-lg px-4">Publikovat příspěvek</button>
                            <a href="forum.php" class="btn btn-outline-secondary btn-lg px-4">Zrušit</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
