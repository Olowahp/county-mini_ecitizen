<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();
$pageTitle = 'Manage requests';
$status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$sql = 'SELECT r.*, c.name AS category_name, u.name AS citizen_name FROM requests r JOIN categories c ON c.id=r.category_id JOIN users u ON u.id=r.user_id WHERE 1=1';
$params = [];
if (in_array($status, ['Pending','In Progress','Resolved'], true)) { $sql .= ' AND r.status=?'; $params[] = $status; }
if ($search !== '') { $sql .= ' AND (r.reference_no LIKE ? OR u.name LIKE ? OR r.location LIKE ? OR c.name LIKE ?)'; $term = '%' . $search . '%'; array_push($params, $term, $term, $term, $term); }
$sql .= ' ORDER BY r.created_at DESC';
$stmt = $db->prepare($sql); $stmt->execute($params); $requests = $stmt->fetchAll();
require dirname(__DIR__, 2) . '/app/views/header.php';
?>
<div class="page-header">
    <div>
        <span class="eyebrow">SYSTEM ADMINISTRATION</span>
        <h1>All service requests</h1>
        <p>Search, review and update cases submitted by citizens.</p>
    </div>
</div>

<div class="admin-nav">
    <a href="admin/index.php">Overview</a>
    <a class="active" href="admin/requests.php">All requests</a>
    <a href="admin/categories.php">Service categories</a>
    <a href="admin/users.php">Users and staff</a>
    <a href="admin/payments.php">Water payments</a>
    <a href="admin/business-licences.php">Business licences</a>
    <a href="admin/news.php">News & Notices</a>
</div>

<section class="panel">
    <form class="filter-form" method="get">
        <input name="q" value="<?= e($search) ?>" placeholder="Search reference, citizen, location…">
        <select name="status">
            <option value="">All statuses</option><?php foreach (['Pending','In Progress','Resolved'] as $s): ?>
                <option <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
            </select>
            <button class="button primary" type="submit">Filter</button><a class="button ghost" href="admin/requests.php">Reset</a>
        </form>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Citizen</th>
                <th>Category</th>
                <th>Location</th>
                <th>Submitted</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($requests as $r): ?>
    <tr>
        <td class="reference"><?= e($r['reference_no']) ?></td>
        <td><?= e($r['citizen_name']) ?></td><td><?= e($r['category_name']) ?></td>
        <td><?= e($r['location']) ?></td>
        <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
        <td><a class="table-link" href="admin/request-edit.php?id=<?= (int)$r['id'] ?>">Manage →</a></td>
    </tr><?php endforeach; ?>
<?php if (!$requests): ?>
    <tr>
        <td colspan="6" class="empty-cell">No requests match your filters.</td>
    </tr><?php endif; ?>
</tbody>
</table>
</div>
<div class="table-foot">Showing <?= count($requests) ?> request(s)</div>
</section>
<?php require dirname(__DIR__, 2) . '/app/views/footer.php'; ?>
