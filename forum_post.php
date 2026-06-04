<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/functions.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT fp.*, u.username as author, u.role as author_role
    FROM forum_posts fp
    LEFT JOIN users u ON fp.user_id = u.id
    WHERE fp.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: forum.php');
    exit;
}

$stmt = $pdo->prepare("UPDATE forum_posts SET views = views + 1 WHERE id = ?");
$stmt->execute([$post_id]);

$user_liked = false;
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT id FROM forum_post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    $user_liked = (bool)$stmt->fetch();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'like' && is_logged_in()) {
        if ($user_liked) {
            $stmt = $pdo->prepare("DELETE FROM forum_post_likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $_SESSION['user_id']]);
            $stmt = $pdo->prepare("UPDATE forum_posts SET likes_count = likes_count - 1 WHERE id = ?");
            $stmt->execute([$post_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO forum_post_likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $_SESSION['user_id']]);
            $stmt = $pdo->prepare("UPDATE forum_posts SET likes_count = likes_count + 1 WHERE id = ?");
            $stmt->execute([$post_id]);
        }
        header("Location: forum_post.php?id=$post_id");
        exit;
    }
    
    elseif ($_POST['action'] === 'comment' && is_logged_in()) {
        $content = trim($_POST['content'] ?? '');
        
        if (!$content) {
            $error = 'Zadejte obsah komentáře.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO forum_comments (post_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $_SESSION['user_id'], $content]);
            $stmt = $pdo->prepare("UPDATE forum_posts SET comments_count = comments_count + 1 WHERE id = ?");
            $stmt->execute([$post_id]);
            header("Location: forum_post.php?id=$post_id#comments");
            exit;
        }
    }
    
    elseif ($_POST['action'] === 'delete_post' && is_admin()) {
        $stmt = $pdo->prepare("DELETE FROM forum_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        header("Location: forum.php?deleted=1");
        exit;
    }
    
    elseif ($_POST['action'] === 'delete_comment' && is_logged_in()) {
        $comment_id = (int)($_POST['comment_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT user_id FROM forum_comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();
        
        if ($comment && (is_admin() || $comment['user_id'] == $_SESSION['user_id'])) {
            $stmt = $pdo->prepare("DELETE FROM forum_comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $stmt = $pdo->prepare("UPDATE forum_posts SET comments_count = comments_count - 1 WHERE id = ?");
            $stmt->execute([$post_id]);
            header("Location: forum_post.php?id=$post_id#comments");
            exit;
        }
    }
}

$stmt = $pdo->prepare("
    SELECT fc.*, u.username, u.role
    FROM forum_comments fc
    LEFT JOIN users u ON fc.user_id = u.id
    WHERE fc.post_id = ?
    ORDER BY fc.created_at ASC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

$page_title = escape($post['title']) . ' - Fórum';
$css_path = 'style.css';
$base_url = '';

require_once __DIR__ . '/header.php';
?>

<div class="container my-4">
    <a href="forum.php" class="btn btn-outline-secondary btn-sm mb-3">Zpět na fórum</a>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <?php if ($post['category']): ?>
                        <span class="badge bg-light text-primary mb-2"><?= escape($post['category']) ?></span>
                    <?php endif; ?>
                    <h1 class="h3 mb-2"><?= escape($post['title']) ?></h1>
                    <div class="d-flex align-items-center gap-3 text-white-50">
                        <span>Autor: <strong><?= escape($post['author']) ?></strong>
                            <?php if ($post['author_role'] === 'admin'): ?>
                                <span class="badge bg-danger ms-1">Admin</span>
                            <?php endif; ?>
                        </span>
                        <span>Vytvořeno: <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?></span>
                    </div>
                </div>
                <?php if (is_admin()): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Opravdu chcete smazat tento příspěvek?');">
                        <input type="hidden" name="action" value="delete_post">
                        <button type="submit" class="btn btn-sm btn-danger">Smazat</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="mb-4" style="line-height: 1.8;">
                <?= nl2br(escape($post['content'])) ?>
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex gap-4">
                    <div class="text-muted">
                        Zobrazení <strong><?= $post['views'] ?></strong>
                    </div>
                    <div class="text-muted">
                        Komentáře <strong><?= $post['comments_count'] ?></strong>
                    </div>
                </div>
                <div>
                    <?php if (is_logged_in()): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="like">
                            <button type="submit" class="btn btn-sm <?= $user_liked ? 'btn-danger' : 'btn-outline-danger' ?>">
                                Lajky <strong><?= $post['likes_count'] ?></strong>
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="badge bg-light text-danger">
                            Lajky <?= $post['likes_count'] ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= escape($error) ?></div>
    <?php endif; ?>

    <div id="comments" class="mb-4">
        <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-4">
                    <h3 class="h5 mb-3">Komentáře <span class="badge bg-primary"><?= count($comments) ?></span></h3>
        
                <?php if (empty($comments)): ?>
                    <div class="text-center py-4 text-muted">
                        <p>Zatím žádné komentáře. Buďte první!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong class="text-primary"><?= escape($comment['username']) ?></strong>
                                    <?php if ($comment['role'] === 'admin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php endif; ?>
                                    <small class="text-muted d-block">
                                        Přidáno: <?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?>
                                    </small>
                                </div>
                                <?php if (is_logged_in() && (is_admin() || $comment['user_id'] == $_SESSION['user_id'])): ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Opravdu chcete smazat tento komentář?');">
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Smazat</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div><?= nl2br(escape($comment['content'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (is_logged_in()): ?>
        <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                <h4 class="h6 mb-0">Napište komentář</h4>
            </div>
            <div class="card-body p-4">
                <form method="post">
                    <input type="hidden" name="action" value="comment">
                    <div class="mb-3">
                        <textarea name="content" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Odeslat komentář</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
                <div class="card-body text-center py-4">
                <h5>Přidejte se k diskuzi</h5>
                <p class="text-muted">Pro přidání komentáře se musíte přihlásit</p>
                <a href="login.php" class="btn btn-primary me-2">Přihlásit se</a>
                <a href="register.php" class="btn btn-outline-secondary">Registrovat se</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
