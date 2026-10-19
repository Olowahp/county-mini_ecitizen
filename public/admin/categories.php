<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();
$pageTitle = 'Service categories';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (mb_strlen($name) < 3) $errors[] = 'Category name must be at least 3 characters.';
        else {
            try {
                $stmt = $db->prepare('INSERT INTO categories (name,description) VALUES (?,?)');
                $stmt->execute([$name,$description]);
                flash('success','Service category added.'); redirect('/admin/categories.php');
            } catch (mysqli_sql_exception $exception) { $errors[] = 'A category with that name already exists.'; }
        }
    } elseif ($action === 'toggle') {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $stmt = $db->prepare('UPDATE categories SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id=?');
        $stmt->execute([$categoryId]);
        flash('success','Category availability updated.'); redirect('/admin/categories.php');
    }
}
$categories = $db->query('SELECT c.*, COUNT(r.id) AS request_count FROM categories c LEFT JOIN requests r ON r.category_id=c.id GROUP BY c.id ORDER BY c.name')->fetchAll();
require dirname(__DIR__, 2) . '/app/views/header.php';
?>
<div class="page-header">
    <div>
        <span class="eyebrow">SYSTEM CONFIGURATION</span><h1>Service categories</h1>
    <p>Manage the public service areas available to citizens.</p>
</div>
</div>
<div class="admin-nav">
    <a href="admin/index.php">Overview</a>
    <a href="admin/requests.php">All requests</a>
    <a class="active" href="admin/categories.php">Service categories</a>
    <a href="admin/users.php">Users and staff</a>
    <a href="admin/payments.php">Water payments</a>
    <a href="admin/business-licences.php">Business licences</a>
    <a href="admin/news.php">News & Notices</a></div>
<div class="detail-grid category-layout"><section class="panel">
    <div class="panel-heading">
        <div><h2>Available categories</h2>
        <p>Deactivate a category to hide it from new requests.</p></div>
    </div>
    <div class="category-list">
        <?php foreach ($categories as $category): ?>
            <div class="category-row">
                <div><strong><?= e($category['name']) ?></strong>
                <p><?= e($category['description']) ?></p><small><?= (int)$category['request_count'] ?> request(s)</small>
            </div>
                <div class="category-actions">
                    <span class="status-pill <?= $category['is_active'] ? 'success' : 'neutral' ?>"><?= $category['is_active'] ? 'Active' : 'Inactive' ?></span>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="category_id" value="<?= (int)$category['id'] ?>"><button class="button small ghost" type="submit"><?= $category['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                    </form></div></div><?php endforeach; ?>
                </div>
            </section>
<section class="panel">
    <div class="panel-heading">
        <div><h2>Add a category</h2>
        <p>Make another service area available.</p>
    </div>
</div>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="stack-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">
        <label>Category name<input name="name" required minlength="3" placeholder="e.g. Licensing and permits"></label>
        <label>Description<textarea name="description" rows="4" placeholder="Briefly explain which requests belong here."></textarea></label>
        <button class="button primary full" type="submit">Add category →</button>
    </form>
</section>
</div>
<?php require dirname(__DIR__, 2) . '/app/views/footer.php'; ?>
