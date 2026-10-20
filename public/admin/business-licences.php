<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();
$pageTitle = 'Business licences';
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action=$_POST['action'] ?? '';
    if ($action==='rate') {
        $size=(string)($_POST['business_size'] ?? ''); $term=(int)($_POST['term_years'] ?? 0); $fee=filter_var($_POST['fee_amount'] ?? null,FILTER_VALIDATE_FLOAT);
        if (!in_array($size,['Small','Medium','Large'],true) || !in_array($term,[1,3,7],true) || $fee===false || $fee<0 || $fee>100000000) $errors[]='Enter a valid business size, licence period and non-negative fee.';
        else { $stmt=$db->prepare('UPDATE business_licence_rates SET fee_amount=? WHERE business_size=? AND term_years=?'); $stmt->execute([$fee,$size,$term]); flash('success','Business licence fee updated. Confirm it matches the approved county fee schedule.'); redirect('/admin/business-licences.php'); }
    } elseif ($action==='application') {
        $id=(int)($_POST['id'] ?? 0); $status=(string)($_POST['application_status'] ?? 'Submitted'); $payment=(string)($_POST['payment_status'] ?? ''); $note=trim((string)($_POST['admin_note'] ?? ''));
        if (!in_array($status,['Submitted','Under Review','Approved','Rejected'],true)) $errors[]='Invalid application status.';
        elseif ($payment!=='' && !in_array($payment,['Verified','Rejected'],true)) $errors[]='Invalid payment verification action.';
        else {
            if ($payment==='Verified') { $stmt=$db->prepare("UPDATE business_licences SET application_status=?,payment_status='Verified',admin_note=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND mpesa_code<>'' AND payment_status='Code Submitted'"); $stmt->execute([$status,$note,$id]); }
            elseif ($payment==='Rejected') { $stmt=$db->prepare("UPDATE business_licences SET application_status=?,payment_status='Rejected',admin_note=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND payment_status='Code Submitted'"); $stmt->execute([$status,$note,$id]); }
            else { $stmt=$db->prepare('UPDATE business_licences SET application_status=?,admin_note=?,updated_at=CURRENT_TIMESTAMP WHERE id=?'); $stmt->execute([$status,$note,$id]); }
            if ($stmt->rowCount()===0) $errors[]='No update was made. Check the application and whether an M-Pesa code has been submitted.';
            else { flash('success','Business licence application updated.'); redirect('/admin/business-licences.php'); }
        }
    }
}
$rates=$db->query('SELECT * FROM business_licence_rates ORDER BY CASE business_size WHEN "Small" THEN 1 WHEN "Medium" THEN 2 ELSE 3 END,term_years')->fetchAll();
$applications=$db->query('SELECT b.*,u.name AS citizen_name,u.email FROM business_licences b JOIN users u ON u.id=b.user_id ORDER BY b.created_at DESC')->fetchAll();
require dirname(__DIR__, 2) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">LICENSING ADMINISTRATION</span><h1>Business licences</h1>
    <p>Manage the fee schedule, review applications and verify submitted M-Pesa transaction codes.</p></div>
</div>
<div class="admin-nav" >
    <a href="admin/index.php">Overview</a>
    <a href="admin/requests.php">All requests</a>
    <a href="admin/categories.php">Service categories</a>
    <a href="admin/users.php">Users and staff</a>
    <a href="admin/payments.php">Water payments</a>
    <a class="active" href="admin/business-licences.php">Business licences</a>
    <a href="admin/news.php">News & Notices</a></div>
<div class="alert warning"><strong>Important:</strong> The seeded fees are are approve by the relevant county authorities. Currently  approved rate by Murang’a County.</div>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
<section class="panel">
    <div class="panel-heading">
        <div><h2>Business licence fee schedule</h2>
        <p>Set separate fees for Small, Medium and Large businesses for 1-, 3- and 7-year terms.</p>
    </div>
</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Business size</th>
                <th>Licence period</th>
                <th>Fee (KSh)</th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rates as $rate): ?>
                <tr>
                    <td><?= e($rate['business_size']) ?></td>
                    <td><?= (int)$rate['term_years'] ?> year(s)</td>
                    <td><form method="post" class="role-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="rate"><input type="hidden" name="business_size" value="<?= e($rate['business_size']) ?>">
                        <input type="hidden" name="term_years" value="<?= (int)$rate['term_years'] ?>">
                        <input name="fee_amount" type="number" min="0" max="100000000" step="0.01" required value="<?= number_format((float)$rate['fee_amount'],2,'.','') ?>" aria-label="Fee in Kenya shillings">
                        <button class="button small primary" type="submit">Save</button>
                    </form>
                </td>
                <td>Admin configurable</td>
            </tr><?php endforeach; ?>
        </tbody>
    </table>
</div>
</section>
<section class="panel">
    <div class="panel-heading">
        <div><h2>Applications and payments</h2>
        <p>Approve or reject applications, then verify transaction codes after the user submits them.</p>
    </div>
</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Applicant / business</th>
                <th>Identification</th>
                <th>Licence & fee</th>
                <th>Location</th>
                <th>Payment</th>
                <th>Review / action</th>
            </tr>
        </thead>
        <tbody><?php if (!$applications): ?><tr>
            <td colspan="6" class="empty-cell">You haven't applied for any business licence.</td>
        </tr>
        <?php else: foreach ($applications as $app): ?>
            <tr>
            <td><strong><?= e($app['business_name']) ?></strong><br><small><?= e($app['citizen_name']) ?> · <?= e($app['phone']) ?></small><br><small><?= e($app['reference_no']) ?></small></td>
            <td><?= e($app['id_number']) ?></td>
            <td><?= e($app['application_type']) ?> · <?= e($app['business_size']) ?><br><?= (int)$app['term_years'] ?> year(s)<br><strong>KSh <?= number_format((float)$app['fee_amount'],2) ?></strong></td>
            <td><?= e($app['subcounty']) ?> / <?= e($app['ward']) ?><br><small><?= e($app['area']) ?></small></td>
            <td><span class="status-pill <?= $app['payment_status']==='Verified'?'success':($app['payment_status']==='Rejected'?'warning':'info') ?>"><?= e($app['payment_status']) ?></span><br><small><?= e($app['mpesa_code'] ?: 'No code submitted') ?></small></td>
            <td>
                <form method="post" class="stack-form admin-licence-review">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="application">
                    <input type="hidden" name="id" value="<?= (int)$app['id'] ?>">
                    <label>Application status<select name="application_status">
                        <?php foreach (['Submitted','Under Review','Approved','Rejected'] as $status): ?>
                            <option <?= $app['application_status']===$status?'selected':'' ?>><?= e($status) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Admin note on request or service progess
                    <input name="admin_note" value="<?= e($app['admin_note']) ?>" maxlength="500" placeholder="Optional note to applicant">
                </label>
                <button class="button small primary" type="submit">Save review</button>
                <?php if ($app['payment_status']==='Code Submitted'): ?>
                    <button class="button small primary" name="payment_status" value="Verified" type="submit">Verify M-Pesa payments</button>
                    <button class="button small danger" name="payment_status" value="Rejected" type="submit">Reject code</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
</section>
<?php require dirname(__DIR__, 2) . '/app/views/footer.php'; ?>
