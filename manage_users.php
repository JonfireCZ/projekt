<?php
require_once __DIR__ . '/../database_connect.php';
require_once __DIR__ . '/../functions.php';

require_login();
if (!is_admin()) {
    header('Location: ../mainpage.php');
    exit;
}

$delete_id = isset($_GET['delete']) ? (int)$_GET['delete'] : 0;
if ($delete_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($delete_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        header('Location: manage_users.php?deleted=1');
        exit;
    }
}

$confirm_delete = $delete_id && $_SERVER['REQUEST_METHOD'] === 'GET';

$stmt = $pdo->query("SELECT id, first_name, last_name, username, email, role FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();

$page_title = 'Správa Uživatelů — Admin Panel';
$css_path = '../style.css';
$base_url = '../';

require_once __DIR__ . '/../header.php';
?>
    <div class="container py-5">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Uživatel byl úspěšně smazán.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title">Seznam Uživatelů</h5>
                <table class="table table-striped table-responsive">
                    <thead>
                        <tr>
                            <th>Jméno</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= escape($user['first_name']) ?> <?= escape($user['last_name']) ?></td>
                            <td><?= escape($user['email']) ?></td>
                            <td>
                                <form method="post" action="update_user_role.php" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>customer</option>
                                        <option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>editor</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Upravit</a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $user['id'] ?>">Smazat</button>
                                <?php else: ?>
                                    <span class="text-muted text-sm">(Vy)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php foreach ($users as $user): ?>
            <?php if ($user['id'] != $_SESSION['user_id']): ?>
            <div class="modal fade" id="deleteModal<?= $user['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Smazání uživatele</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            Opravdu chcete smazat uživatele <strong><?= escape($user['first_name']) ?> <?= escape($user['last_name']) ?></strong>?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                            <form method="post" action="manage_users.php?delete=<?= $user['id'] ?>" style="display:inline;">
                                <button type="submit" class="btn btn-danger">Smazat</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../footer.php'; ?>
