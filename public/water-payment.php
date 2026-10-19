<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_login();
$pageTitle = 'Water service payments';
$user = current_user();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $code = strtoupper(trim($_POST['mpesa_code'] ?? ''));
    $stmt = $db->prepare("SELECT wp.*,r.user_id,r.reference_no FROM water_payments wp JOIN requests r ON r.id=wp.request_id WHERE wp.id=? AND r.user_id=?");
    $stmt->execute([$paymentId,$user['id']]); $payment = $stmt->fetch();
    if (!$payment) $errors[] = 'Payment request not found.';
    elseif ($payment['payment_status'] !== 'Awaiting Payment') $errors[] = 'This payment is not awaiting a transaction code.';
    elseif (!preg_match('/^[A-Z0-9]{8,12}$/', $code)) $errors[] = 'Enter a valid M-Pesa transaction code (8–12 letters or numbers).';
    else {
        $update = $db->prepare("UPDATE water_payments SET mpesa_code=?,payment_status='Code Submitted',updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $update->execute([$code,$paymentId]);
        flash('success','M-Pesa code submitted. An administrator must verify it before payment is marked complete.');
        redirect('/water-payment.php');
    }
}
$stmt = $db->prepare('SELECT wp.*,r.reference_no,r.location FROM water_payments wp JOIN requests r ON r.id=wp.request_id WHERE r.user_id=? ORDER BY wp.created_at DESC');
$stmt->execute([$user['id']]); $payments = $stmt->fetchAll();
require dirname(__DIR__) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">WATER & SANITATION</span><h1>Water service payments</h1>
    <p>Pay only after the service team verifies the issue and creates an official payment request.</p></div>
    <a class="button ghost" href="dashboard.php">← My requests</a>
</div>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
<section class="panel">
    <div class="panel-heading"><div><h2>Your payment requests</h2>
    <p>After paying through the official instructions provided by the county, submit the M-Pesa transaction code.</p></div>
</div>
<?php if (!$payments): ?>
    <div class="empty-state">
        <div class="empty-icon">KSh</div>
        <h3>No water payments requested</h3>
        <p>If your water meter needs paid repair or replacement, staff will first assess the request and send the amount due here.</p>
    </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Location</th>
                        <th>Amount (KSh)</th>
                        <th>Payment status</th>
                        <th>M-Pesa code</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td class="reference"><?= e($pay['reference_no']) ?></td>
                            <td><?= e($pay['location']) ?></td>
                            <td><strong><?= number_format((float)$pay['amount'],2) ?></strong></td>
                            <td><span class="status-pill <?= $pay['payment_status']==='Verified'?'success':($pay['payment_status']==='Rejected'?'warning':'info') ?>"><?= e($pay['payment_status']) ?></span><?php if ($pay['admin_note']): ?><br><small><?= e($pay['admin_note']) ?></small><?php endif; ?></td>
                                <td><?= e($pay['mpesa_code'] ?: '—') ?></td>
                                <td><?php if ($pay['payment_status']==='Awaiting Payment'): ?>
                                    <form method="post" class="payment-code-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="payment_id" value="<?= (int)$pay['id'] ?>">
                                        <input name="mpesa_code" required pattern="[A-Za-z0-9]{8,12}" placeholder="M-Pesa code" aria-label="M-Pesa transaction code">
                                        <button class="button small primary" type="submit">Submit code</button>
                                    </form>
                                    <?php else: ?><span class="muted">No action</span><?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </section>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
