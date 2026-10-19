<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();
$pageTitle = 'Water payment management';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $note = trim($_POST['admin_note'] ?? '');
        $check = $db->prepare("SELECT r.id FROM requests r JOIN categories c ON c.id=r.category_id WHERE r.id=? AND c.name='Water and Sanitation'");
        $check->execute([$requestId]);
        if (!$check->fetch()) $errors[] = 'Choose an existing Water and Sanitation request.';
        if ($amount <= 0 || $amount > 10000000) $errors[] = 'Enter a valid payment amount greater than zero.';
        if (!$errors) {
            $existing = $db->prepare("SELECT COUNT(*) FROM water_payments WHERE request_id=? AND payment_status IN ('Awaiting Payment','Code Submitted')");
            $existing->execute([$requestId]);
            if ((int)$existing->fetchColumn() > 0) $errors[] = 'This request already has an open payment request.';
            else {
                $stmt = $db->prepare("INSERT INTO water_payments (request_id,amount,admin_note,requested_by) VALUES (?,?,?,?)");
                $stmt->execute([$requestId,$amount,$note,current_user()['id']]);
                flash('success','Payment request created. The citizen can now see the amount and submit their M-Pesa code.');
                redirect('/admin/payments.php');
            }
        }
    } elseif ($action === 'verify') {
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $note = trim($_POST['admin_note'] ?? '');
        if (!in_array($decision,['Verified','Rejected'],true)) $errors[] = 'Choose a valid verification decision.';
        else {
            $stmt = $db->prepare("UPDATE water_payments SET payment_status=?,admin_note=?,verified_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND payment_status='Code Submitted'");
            $stmt->execute([$decision,$note,current_user()['id'],$paymentId]);
            if ($stmt->rowCount()) flash('success','M-Pesa transaction code marked ' . strtolower($decision) . '.');
            else flash('warning','This payment is not waiting for verification.');
            redirect('/admin/payments.php');
        }
    }
}
$waterRequests = $db->query("SELECT r.id,r.reference_no,r.location,u.name AS citizen_name FROM requests r JOIN categories c ON c.id=r.category_id JOIN users u ON u.id=r.user_id WHERE c.name='Water and Sanitation' ORDER BY r.created_at DESC")->fetchAll();
$payments = $db->query("SELECT wp.*,r.reference_no,r.location,u.name AS citizen_name,u.email FROM water_payments wp JOIN requests r ON r.id=wp.request_id JOIN users u ON u.id=r.user_id ORDER BY wp.created_at DESC")->fetchAll();
require dirname(__DIR__, 2) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">WATER & SANITATION</span><h1>Payment verification</h1>
    <p>Verify meter service payment codes and create payment requests after assessment.</p>
</div>
</div>
<div class="admin-nav">
    <a href="admin/index.php">Overview</a>
    <a href="admin/requests.php">All requests</a>
    <a href="admin/categories.php">Service categories</a>
    <a href="admin/users.php">Users and staff</a>
    <a class="active" href="admin/payments.php">Water payments</a></div>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
<div class="detail-grid category-layout">
    <section class="panel">
        <div class="panel-heading"><div><h2>Create payment request</h2>
        <p>Only create a charge after checking the service issue and confirming the amount.</p>
    </div>
</div>
<form method="post" class="stack-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="create">
    <label>Water service request
        <select name="request_id" required>
        <option value="">Choose a water request</option>
        <?php foreach ($waterRequests as $r): ?>
            <option value="<?= (int)$r['id'] ?>"><?= e($r['reference_no'] . ' · ' . $r['citizen_name'] . ' · ' . $r['location']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Amount (KSh)<input type="number" name="amount" min="1" max="10000000" step="0.01" required placeholder="e.g. 1500.00"></label>
    <label>Payment instructions / assessment note
        <textarea name="admin_note" rows="4" placeholder="Explain what was verified and how the citizen should pay through the official channel."></textarea>
    </label><div class="alert warning">Payments are verified by the system admnistarion staff, Ensure you've made the correct payment.</div>
    <button class="button primary full" type="submit">Send payment request →</button>
</form>
</section>
<section class="panel"><div class="panel-heading"><div><h2>Payment workflow</h2>
<p>Follow these steps for traceable payments.</p></div></div><div class="role-guide">
    <p><strong>1. Verify the issue</strong><span>Review the meter or sanitation request and determine whether a charge applies.</span></p>
    <p><strong>2. Request payment</strong><span>Enter the approved amount and clear official payment instructions.</span></p>
    <p><strong>3. Citizen pays</strong><span>The citizen pays using the official channel and enters the M-Pesa transaction code.</span></p>
    <p><strong>4. Verify code</strong><span>An administrator checks the transaction against the official M-Pesa record before confirming it.</span></p>
</div>
</section>
</div>
<section class="panel"><div class="panel-heading"><div><h2>Payment records</h2><p>Review submitted codes and record verification outcomes.</p></div><span class="step-tag"><?= count($payments) ?> records</span></div><div class="table-wrap"><table><thead><tr><th>Request / citizen</th><th>Amount</th><th>M-Pesa code</th><th>Status</th><th>Instructions / note</th><th>Verify</th></tr></thead><tbody><?php foreach ($payments as $pay): ?><tr><td><strong><?= e($pay['reference_no']) ?></strong><br><?= e($pay['citizen_name']) ?><br><small><?= e($pay['email']) ?></small></td><td>KSh <?= number_format((float)$pay['amount'],2) ?></td><td><strong><?= e($pay['mpesa_code'] ?: 'Not submitted') ?></strong></td><td><span class="status-pill <?= $pay['payment_status']==='Verified'?'success':($pay['payment_status']==='Rejected'?'warning':'info') ?>"><?= e($pay['payment_status']) ?></span></td><td><?= e($pay['admin_note'] ?: '—') ?></td><td><?php if ($pay['payment_status']==='Code Submitted'): ?><form method="post" class="verify-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="verify"><input type="hidden" name="payment_id" value="<?= (int)$pay['id'] ?>"><input name="admin_note" placeholder="Verification note" aria-label="Verification note"><button class="button small primary" name="decision" value="Verified">Verify</button><button class="button small danger" name="decision" value="Rejected">Reject</button></form><?php else: ?><span class="muted">—</span><?php endif; ?></td></tr><?php endforeach; ?><?php if (!$payments): ?><tr><td colspan="6" class="empty-cell">No payment records have been created.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require dirname(__DIR__, 2) . '/app/views/footer.php'; ?>
