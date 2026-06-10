<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$page_title = 'Fórum - LockerRoom';
$css_path = 'style.css';
$base_url = '';
$show_search = true;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = $_GET['category'] ?? 'all';

// always load categories for sidebar (prevent undefined variable when searching users)
$stmt = $pdo->query("SELECT DISTINCT category FROM forum_posts WHERE category IS NOT NULL ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($search) {
    $stmt = $pdo->prepare("SELECT id, username, first_name, last_name, email FROM users WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ? ORDER BY username");
    $param = '%' . $search . '%';
    $stmt->execute([$param, $param, $param]);
    $users = $stmt->fetchAll();
} else {
    if ($category_filter === 'all') {
        $stmt = $pdo->query("
            SELECT 
                fp.id,
                fp.title,
                fp.category,
                fp.views,
                fp.likes_count,
                fp.comments_count,
                fp.created_at,
                u.username as author
            FROM forum_posts fp
            LEFT JOIN users u ON fp.user_id = u.id
            ORDER BY fp.created_at DESC
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                fp.id,
                fp.title,
                fp.category,
                fp.views,
                fp.likes_count,
                fp.comments_count,
                fp.created_at,
                u.username as author
            FROM forum_posts fp
            LEFT JOIN users u ON fp.user_id = u.id
            WHERE fp.category = ?
            ORDER BY fp.created_at DESC
        ");
        $stmt->execute([$category_filter]);
    }
    $posts = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT DISTINCT category FROM forum_posts WHERE category IS NOT NULL ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

require_once __DIR__ . '/header.php';
?>

<div class="container my-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2">Diskuzní fórum</h2>
                    <p class="text-muted mb-0">Připojte se ke komunitě a sdílejte své názory</p>
                </div>
                <?php if (is_logged_in()): ?>
                    <a href="forum_new_post.php" class="btn btn-primary">+ Přidat příspěvek</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h5>Kategorie</h5>
            <div class="d-flex flex-wrap gap-2">
                <a href="forum.php?category=all" class="btn btn-sm <?= $category_filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">Všechny</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="forum.php?category=<?= urlencode($cat) ?>" class="btn btn-sm <?= $category_filter === $cat ? 'btn-primary' : 'btn-outline-primary' ?>"><?= escape($cat) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($search): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5>Výsledky hledání uživatelů pro "<?= escape($search) ?>"</h5>
                <?php if (empty($users)): ?>
                    <p class="text-muted">Žádní uživatelé nenalezeni.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($users as $u): ?>
                            <a href="profile.php?id=<?= $u['id'] ?>" class="list-group-item list-group-item-action">
                                <strong><?= escape($u['username']) ?></strong>
                                <div class="small text-muted"><?= escape(trim(($u['first_name'] . ' ' . $u['last_name']))) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
    <?php if (empty($posts)): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <h4 class="text-muted">Zatím nebyly vytvořeny žádné příspěvky</h4>
                <p class="text-muted">Buďte první, kdo založí diskuzi!</p>
                <?php if (is_logged_in()): ?>
                    <a href="forum_new_post.php" class="btn btn-primary">Vytvořit první příspěvek</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            <?php foreach ($posts as $post): ?>
                <div class="col">
                    <a href="forum_post.php?id=<?= $post['id'] ?>" class="text-decoration-none text-reset">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <?php if ($post['category']): ?>
                                    <span class="badge bg-primary mb-2"><?= escape($post['category']) ?></span>
                                <?php endif; ?>
                                <h5 class="card-title"><?= escape($post['title']) ?></h5>
                                <p class="text-muted small mb-2">
                                    <strong><?= escape($post['author']) ?></strong> • 
                                    <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center text-muted small">
                                    <span>Zobrazení <?= $post['views'] ?></span>
                                    <span>Komentáře <?= $post['comments_count'] ?></span>
                                    <span>Lajky <?= $post['likes_count'] ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
