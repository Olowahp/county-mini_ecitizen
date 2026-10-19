<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_login();
$pageTitle = 'My requests';
$user = current_user();
$stmt = $db->prepare('SELECT r.*, c.name AS category_name FROM requests r JOIN categories c ON c.id=r.category_id WHERE r.user_id=? ORDER BY r.created_at DESC');
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();
$countyServices = $db->query('SELECT id,name,description FROM categories WHERE is_active=1 ORDER BY CASE WHEN name="Water and Sanitation" THEN 0 WHEN name="Business and Trade" THEN 1 ELSE 2 END,name')->fetchAll();
$open = count(array_filter($requests, fn($r) => $r['status'] !== 'Resolved'));
$resolved = count($requests) - $open;
require dirname(__DIR__) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">CITIZEN DASHBOARD</span><h1>Welcome, <?= e($user['name']) ?></h1>
    <p>Manage your service requests and follow every update.</p></div>
    <div class="hero-actions">
        <a class="button ghost" href="water-payment.php">Water payments</a>
        <a class="button primary" href="request-create.php">＋ New request</a>
    </div>
</div>

<section class="section-heading dashboard-services-heading"><div><span class="eyebrow">COUNTY SERVICE DIRECTORY</span>
<h2>Services available to you</h2>
<p>Choose a service to report an issue or start an application.</p></div></section>
<section class="service-grid dashboard-service-grid">
<a class="service-card" href="request-create.php"><span class="service-icon icon-0">◉</span><h3>Water and Sanitation</h3>
<p>Report water supply, meter, billing, sewerage and sanitation problems.</p><span class="service-card-link">Report issue or view payments →</span></a>
<a class="service-card" href="business-licence.php"><span class="service-icon icon-1">▤</span><h3>Business licences</h3>
<p>Register a new business, renew a licence, and submit an M-Pesa code for verification.</p><span class="service-card-link">Register or renew →</span></a>
<?php foreach ($countyServices as $i=>$service): if ($service['name']==='Water and Sanitation') continue; ?>
<a class="service-card" href="request-create.php"><span class="service-icon icon-<?= ($i+2)%6 ?>"><?= ['⌁','◉','♻','✚','▤','⌂'][($i+2)%6] ?></span>
<h3><?= e($service['name']) ?></h3><p><?= e($service['description']) ?></p><span class="service-card-link">Request service →</span></a><?php endforeach; ?>
<a class="service-card" href="water-payment.php"><span class="service-icon icon-2">KSh</span><h3>Water service payments</h3>
<p>View assessed water-related charges and submit your M-Pesa transaction code.</p><span class="service-card-link">View payments →</span></a>
<a class="service-card" href="news.php"><span class="service-icon icon-3">▣</span><h3>County news and notices</h3>
<p>Read public notices, service updates, and county announcements.</p><span class="service-card-link">Read updates →</span></a>
</section>
<div class="stats-grid three">
    <div class="stat-card"><span>Total requests</span><strong><?= count($requests) ?></strong><small>All submitted cases</small></div>
    <div class="stat-card"><span>Open requests</span><strong><?= $open ?></strong><small>Awaiting resolution</small></div>
    <div class="stat-card"><span>Resolved</span><strong><?= $resolved ?></strong><small>Completed cases</small></div>
</div>
<section class="panel"><div class="panel-heading">
    <div><h2>Your requests</h2>
<p>Use your reference number when following up.</p>
</div>
</div>
<?php if (!$requests): ?>
    <div class="empty-state"><div class="empty-icon">▤</div><h3>No requests yet</h3>
    <p>When you report a service issue, it will appear here.</p>
    <a class="button primary" href="request-create.php">Submit your first request</a>
</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Service</th>
                    <th>Location</th>
                    <th>Date submitted</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($requests as $request): ?>
    <tr>
        <td class="reference"><?= e($request['reference_no']) ?></td>
        <td><?= e($request['category_name']) ?></td><td><?= e($request['location']) ?></td>
        <td><?= e(date('d M Y', strtotime($request['created_at']))) ?></td>
        <td><span class="status-pill <?= status_class($request['status']) ?>"><?= e($request['status']) ?></span></td>
        <td><a class="table-link" href="request-view.php?id=<?= (int)$request['id'] ?>">View →</a></td>
    </tr>
    <?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
