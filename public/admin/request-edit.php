<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); exit('Request not found.'); }
$stmt = $db->prepare('SELECT r.*, c.name AS category_name, u.name AS citizen_name, u.email AS citizen_email FROM requests r JOIN categories c ON c.id=r.category_id JOIN users u ON u.id=r.user_id WHERE r.id=?');
$stmt->execute([$id]); $request = $stmt->fetch();
if (!$request) { http_response_code(404); exit('Request not found.'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $newStatus = $_POST['status'] ?? '';
    $note = trim($_POST['admin_note'] ?? '');
    if (!in_array($newStatus, ['Pending','In Progress','Resolved'], true)) $errors[] = 'Choose a valid status.';
    if (!$errors) {
        $db->beginTransaction();
        try {
            $update = $db->prepare('UPDATE requests SET status=?, admin_note=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $update->execute([$newStatus, $note, $id]);
            if ($newStatus !== $request['status'] || $note !== $request['admin_note']) {
                $history = $db->prepare('INSERT INTO status_history (request_id,status,note,changed_by) VALUES (?,?,?,?)');
                $history->execute([$id, $newStatus, $note ?: 'Request status updated by administrator.', current_user()['id']]);
            }
            $db->commit();
            flash('success', 'Request ' . $request['reference_no'] . ' updated successfully.');
            redirect('/admin/request-edit.php?id=' . $id);
        } catch (Throwable $exception) {
            $db->rollBack();
            $errors[] = 'The request could not be updated. Please try again.';
        }
    }
}
$pageTitle = 'Manage request';
require dirname(__DIR__, 2) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">REQUEST MANAGEMENT</span><h1><?= e($request['reference_no']) ?></h1>
<p>Review citizen details and update the service case.</p></div><a class="button ghost" href="admin/requests.php">← All requests</a>
</div>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?>
</div><?php endforeach; ?>
<div class="detail-grid">
    <section class="panel">
        <div class="panel-heading">
            <div><h2>Citizen request</h2><p>Submitted by <?= e($request['citizen_name']) ?></p>
        </div>
    </div>
    <dl class="detail-list">
        <div><dt>Email</dt>
        <dd><?= e($request['citizen_email']) ?></dd>
    </div>
    <div><dt>Service</dt>
    <dd><?= e($request['category_name']) ?></dd>
</div>
<div>
    <dt>Location</dt><dd><?= e($request['location']) ?></dd>
</div>
<div><dt>Submitted</dt><dd><?= e(date('d M Y, H:i', strtotime($request['created_at']))) ?></dd>
</div>
<div><dt>Description</dt><dd class="description-text"><?= nl2br(e($request['description'])) ?></dd>
</div></dl><?php if ($request['image_path']): ?>
    <div class="attached-image">
        <h3>Attached image</h3>
        <a href="<?= e(url($request['image_path'])) ?>" target="_blank" rel="noopener"><img src="<?= e(url($request['image_path'])) ?>" alt="Citizen attachment"></a>
    </div><?php endif; ?></section>
<section class="panel"><div class="panel-heading"><div><h2>Update case</h2><p>Keep the status and note accurate.</p></div></div>
<form method="post" class="stack-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Request status<select name="status" required><?php foreach (['Pending','In Progress','Resolved'] as $s): ?>
        <option <?= $request['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
    </label>
    <label>Update / resolution note<textarea name="admin_note" rows="5" placeholder="Add progress, action taken, or resolution details."><?= e($request['admin_note']) ?></textarea>
</label>
<button class="button primary full" type="submit">Save update →</button>
<a class="button ghost full" href="request-view.php?id=<?= (int)$id ?>">View citizen-facing page</a>
</form>
</section>
</div>
<?php require dirname(__DIR__, 2) . '/app/views/footer.php'; ?>
