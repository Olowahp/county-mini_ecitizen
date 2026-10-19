<?php
require dirname(__DIR__) . '/app/bootstrap.php';
 
//Ensure the visitor is logged in
require_login();

// Validate the request ID from the URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Request not found.');
}

// Fetch the request details from database
$stmt = $db->prepare('SELECT r.*, c.name AS category_name, u.name AS citizen_name 
                      FROM requests r 
                      JOIN categories c ON c.id = r.category_id 
                      LEFT JOIN users u ON u.id = r.user_id 
                      WHERE r.id = ?');
$stmt->execute([$id]);
$request = $stmt->fetch();

// Verify request exists
if (!$request) {
    http_response_code(404);
    exit('Request not found.');
}

// AUTHORIZATION CHECK: Allow ONLY the Admin OR the User who created the request
$user = current_user();
$isAdmin = isset($user['role']) && $user['role'] === 'admin';
$isOwner = isset($request['user_id']) && (int)$request['user_id'] === (int)$user['id'];

$historyStmt = $db->prepare('SELECT h.*, u.name AS changed_by_name 
                             FROM status_history h 
                             LEFT JOIN users u ON u.id = h.changed_by 
                             WHERE h.request_id = ? 
                             ORDER BY h.created_at DESC');


$historyStmt = $db->prepare('SELECT h.*, u.name AS changed_by_name FROM status_history h LEFT JOIN users u ON u.id=h.changed_by WHERE h.request_id=? ORDER BY h.created_at DESC');
$historyStmt->execute([$id]);
$history = $historyStmt->fetchAll();
$pageTitle = 'Request ' . $request['reference_no'];
require dirname(__DIR__) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">REQUEST DETAILS</span><h1><?= e($request['reference_no']) ?></h1>
    <p>Submitted <?= e(date('d M Y, H:i', strtotime($request['created_at']))) ?></p>
</div><a class="button ghost" href="<?= current_user()['role'] === 'admin' ? '/admin/requests.php' : './dashboard.php' ?>">← Back to requests</a></div>
<div class="detail-grid">
    <section class="panel">
        <div class="panel-heading">
            <div><h2>Request information</h2>
            <p>Details supplied by the citizen.</p>
        </div>
            <span class="status-pill <?= status_class($request['status']) ?>"><?= e($request['status']) ?></span></div>
<dl class="detail-list">
    <div>
        <dt>Service category</dt><dd><?= e($request['category_name']) ?></dd>
    </div>
    <div>
        <dt>Location</dt><dd><?= e($request['location']) ?></dd>
    </div>
    <div>
        <dt>Submitted by</dt><dd><?= e($request['citizen_name']) ?></dd>
    </div>
    <div>
        <dt>Description</dt><dd class="description-text"><?= nl2br(e($request['description'])) ?></dd>
    </div>
    <?php if ($request['admin_note']): ?>
        <div><dt>Latest administrator note</dt><dd><?= nl2br(e($request['admin_note'])) ?></dd>
    </div><?php endif; ?></dl>
<?php if ($request['image_path']): ?>
    <div class="attached-image">
        <h3>Attached image</h3>
        <a href="<?= e(url($request['image_path'])) ?>" target="_blank" rel="noopener"><img src="<?= e(url($request['image_path'])) ?>" alt="Image attached to service request"></a></div><?php endif; ?>
</section>
<aside class="panel">
    <div class="panel-heading">
        <div><h2>Status history</h2>
        <p>Progress updates for this case.</p>
    </div>
</div>
<div class="timeline"><?php foreach ($history as $item): ?>
    <div class="timeline-item"><span class="timeline-dot <?= status_class($item['status']) ?>"></span>
    <div><strong><?= e($item['status']) ?></strong><p><?= e($item['note'] ?: 'Status updated.') ?></p><small><?= e(date('d M Y, H:i', strtotime($item['created_at']))) ?><?= $item['changed_by_name'] ? ' · ' . e($item['changed_by_name']) : '' ?></small></div>
</div>
<?php endforeach; ?>
</div>
<?php if (current_user()['role'] === 'admin'): ?><a class="button primary full" href="admin/request-edit.php?id=<?= (int)$request['id'] ?>">Manage this request →</a><?php endif; ?></aside></div>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
