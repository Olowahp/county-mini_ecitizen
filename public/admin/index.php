<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();
$pageTitle = 'Admin dashboard';
$counts = [
    'total' => (int)$db->query('SELECT COUNT(*) FROM requests')->fetchColumn(),
    'pending' => (int)$db->query("SELECT COUNT(*) FROM requests WHERE status='Pending'")->fetchColumn(),
    'progress' => (int)$db->query("SELECT COUNT(*) FROM requests WHERE status='In Progress'")->fetchColumn(),
    'resolved' => (int)$db->query("SELECT COUNT(*) FROM requests WHERE status='Resolved'")->fetchColumn(),
    'citizens' => (int)$db->query("SELECT COUNT(*) FROM users WHERE role='citizen'")->fetchColumn(),
];
$recent = $db->query('SELECT r.id,r.reference_no,r.location,r.status,r.created_at,c.name AS category_name,u.name AS citizen_name FROM requests r JOIN categories c ON c.id=r.category_id JOIN users u ON u.id=r.user_id ORDER BY r.created_at DESC LIMIT 8')->fetchAll();
$categories = $db->query('SELECT c.name, COUNT(r.id) AS total FROM categories c LEFT JOIN requests r ON r.category_id=c.id GROUP BY c.id ORDER BY total DESC,c.name LIMIT 6')->fetchAll();
$maxCategory = max(1, ...array_map(fn($c) => (int)$c['total'], $categories));
require dirname(__DIR__, 2) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">SYSTEM ADMINISTRATION</span><h1>Admin dashboard</h1>
    <p>Monitor service delivery and manage requests across Murang'a County.</p>
</div><a class="button primary" href="admin/requests.php">Manage requests →</a></div>

<div class="admin-nav" style="tyle="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.6;">
    <a class="active" href="admin/index.php">Overview</a>
    <a href="admin/requests.php">All requests</a>
    <a href="admin/categories.php">Service categories</a>
    <a href="admin/users.php">Users and staff</a>
    <a href="admin/payments.php">Water payments</a>
    <a href="admin/business-licences.php">Business licences</a>
    <a href="admin/news.php">News & Notices</a>
</div>

<div class="stats-grid four">
    <div class="stat-card"><span>Total requests</span><strong><?= $counts['total'] ?></strong><small>All recorded cases</small></div>
    <div class="stat-card"><span>Pending</span><strong><?= $counts['pending'] ?></strong><small>Not yet started</small></div>
    <div class="stat-card"><span>In progress</span><strong><?= $counts['progress'] ?></strong><small>Being addressed</small></div>
    <div class="stat-card"><span>Resolved</span><strong><?= $counts['resolved'] ?></strong><small>Completed cases</small></div></div>

<div class="analytics-grid"><section class="panel">
    <div class="panel-heading"><div>
        <h2>Requests by service</h2><p>Distribution across service categories.</p>
    </div>
</div>
<?php if (!$categories || $counts['total'] === 0): ?><div class="empty-inline">All service requsts report submited by the public will appear here.</div
    <?php else: foreach ($categories as $category): ?><div class="bar-row"><div><span><?= e($category['name']) ?></span><strong><?= (int)$category['total'] ?></strong></div><div class="bar-track"><span style="width:<?= round(((int)$category['total'] / $maxCategory) * 100) ?>%"></span></div></div><?php endforeach; endif; ?></section>
<section class="panel">
    <div class="panel-heading">
    <div><h2>Portal overview</h2><p>Current system activity.</p></div>
</div>
<div class="overview-number"><strong><?= $counts['citizens'] ?></strong><span>Registered citizens</span></div>
<div class="overview-number"><strong><?= $counts['total'] ? round($counts['resolved'] / $counts['total'] * 100) : 0 ?>%</strong><span>Resolution rate</span></div>
<div class="overview-number"><strong><?= $counts['pending'] + $counts['progress'] ?></strong><span>Open cases</span></div></section></div>
<section class="panel">
    <div class="panel-heading">
        <div><h2>Recently submitted requests</h2>
        <p>Latest activity across the portal.</p></div><a class="text-link" href="admin/requests.php">View all →</a>
    </div>
<?php if (!$recent): ?>
    <div class="empty-state compact"><h3>No requests yet</h3>
    <p>New citizen requests will appear here.</p></div>
    <?php else: ?><div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Citizen</th>
                    <th>Service</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td class="reference"><?= e($r['reference_no']) ?></td>
                        <td><?= e($r['citizen_name']) ?></td><td><?= e($r['category_name']) ?></td>
                        <td><?= e($r['location']) ?></td>
                        <td><span class="status-pill <?= status_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
                        <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
                        <td><a class="table-link" href="admin/request-edit.php?id=<?= (int)$r['id'] ?>">Manage →</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
<?php require dirname(__DIR__, 2) . '/app/views/footer.php'; ?>
