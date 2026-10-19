<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_login();
$pageTitle = 'Business licences';
$user = current_user();
$errors = [];
$subcounties = [
    'Gatanga' => ['Ithanga','Kakuzi/Mitubiri','Mugumo-ini','Kihumbu-ini','Gatanga','Kariara'],
    'Kandara' => ["Ng'araria",'Muruka','Kagundu-ini','Gaichanjiru','Ithiru','Ruchu'],
    'Kigumo' => ['Kahumbu','Muthithi','Kigumo','Kinyona','Kiru'],
    'Kangema' => ['Kanyenya-ini','Muguru','Rwathia'],
    'Mathioya' => ['Gitugi','Kiru','Kamacharia'],
    'Maragua' => ['Kimorori/Wempa','Makuyu','Kambiti','Kamahuha','Ichagaki','Nginda'],
    'Kiharu' => ['Wangu','Mugoiri','Mbiri','Township','Murarandia','Gaturi'],
];
$rates = $db->query('SELECT business_size,term_years,fee_amount FROM business_licence_rates ORDER BY business_size,term_years')->fetchAll();
$rateMap = [];
foreach ($rates as $rate) $rateMap[$rate['business_size']][(int)$rate['term_years']] = (float)$rate['fee_amount'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'apply';
    if ($action === 'pay') {
        $id = (int)($_POST['licence_id'] ?? 0);
        $code = strtoupper(trim((string)($_POST['mpesa_code'] ?? '')));
        $stmt = $db->prepare('SELECT id,payment_status,application_status FROM business_licences WHERE id=? AND user_id=?');
        $stmt->execute([$id, $user['id']]); $record = $stmt->fetch();
        if (!$record) $errors[] = 'Business licence application was not found.';
        elseif ($record['application_status'] !== 'Approved') $errors[] = 'Your application must be approved by the county before payment.';
        elseif (!in_array($record['payment_status'], ['Awaiting Payment','Rejected'], true)) $errors[] = 'A transaction code has already been submitted or verified.';
        elseif (!preg_match('/^[A-Z0-9]{8,12}$/', $code)) $errors[] = 'Enter a valid M-Pesa transaction code (8–12 letters or numbers).';
        else {
            $stmt = $db->prepare("UPDATE business_licences SET mpesa_code=?,payment_status='Code Submitted',updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$code,$id]); flash('success','M-Pesa code submitted for county verification.'); redirect('/business-licence.php');
        }
    } else {
        $type = ($_POST['application_type'] ?? '') === 'Renewal' ? 'Renewal' : 'Registration';
        $licenceNo = trim((string)($_POST['existing_licence_no'] ?? ''));
        $idNumber = trim((string)($_POST['id_number'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $businessName = trim((string)($_POST['business_name'] ?? ''));
        $size = (string)($_POST['business_size'] ?? '');
        $term = (int)($_POST['term_years'] ?? 0);
        $subcounty = (string)($_POST['subcounty'] ?? '');
        $ward = (string)($_POST['ward'] ?? '');
        $area = trim((string)($_POST['area'] ?? ''));
        if (strlen($idNumber) < 5 || strlen($idNumber) > 20) $errors[] = 'Enter a valid national ID or identification number.';
        if (!preg_match('/^[+0-9 ()-]{9,16}$/', $phone)) $errors[] = 'Enter a valid phone number.';
        if (mb_strlen($businessName) < 2) $errors[] = 'Enter the registered or trading name of the business.';
        if (!in_array($size, ['Small','Medium','Large'], true)) $errors[] = 'Choose the business size.';
        if (!in_array($term, [1,3,7], true)) $errors[] = 'Choose a licence period of 1, 3 or 7 years.';
        if (!isset($subcounties[$subcounty]) || !in_array($ward, $subcounties[$subcounty] ?? [], true)) $errors[] = 'Choose a valid Murang’a sub-county and ward.';
        if (mb_strlen($area) < 2) $errors[] = 'Enter the local area, village, street, market or landmark.';
        if ($type === 'Renewal' && $licenceNo === '') $errors[] = 'Enter the existing business licence number for renewal.';
        $fee = (float)($rateMap[$size][$term] ?? 0);
        if ($fee <= 0) $errors[] = 'The fee for this business size and term is not configured. Contact the county licensing office.';
        if (!$errors) {
            $reference = 'MBL-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $db->prepare('INSERT INTO business_licences (reference_no,user_id,application_type,existing_licence_no,id_number,phone,business_name,business_size,term_years,subcounty,ward,area,fee_amount) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$reference,$user['id'],$type,$licenceNo,$idNumber,$phone,$businessName,$size,$term,$subcounty,$ward,$area,$fee]);
            flash('success', 'Business licence application submitted. Reference: ' . $reference . '. Follow the payment instructions shown in your applications list.');
            redirect('/business-licence.php');
        }
    }
}
$stmt = $db->prepare('SELECT * FROM business_licences WHERE user_id=? ORDER BY created_at DESC');
$stmt->execute([$user['id']]); $applications = $stmt->fetchAll();
require dirname(__DIR__) . '/app/views/header.php';
?>
<div class="page-header"><div><span class="eyebrow">COUNTY BUSINESS SERVICES</span><h1>Business licences</h1><p>Register a business or renew an existing licence in Murang’a County.</p></div><a class="button ghost" href="dashboard.php">← My services</a></div>
<div class="flag-banner"></div>
<div class="content-narrow panel"><div class="panel-heading"><div><h2>Apply for a licence</h2><p>Enter your identification, business and location details.</p></div></div>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>

<form method="post" class="stack-form" id="licence-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="apply">
<div class="news-form-grid">
<label>Application type<select name="application_type" id="application_type">
    <option value="Registration">Register a new business</option>
    <option value="Renewal">Renew an existing licence</option>
</select>
</label>
<label id="licence-no-wrap" hidden>Existing licence number<input name="existing_licence_no" id="existing_licence_no" placeholder="Enter current licence number"></label>
<label>National ID / identification number<input name="id_number" required maxlength="20" value="<?= e($_POST['id_number'] ?? '') ?>" autocomplete="off"></label>
<label>Phone number<input name="phone" type="tel" required maxlength="16" value="<?= e($_POST['phone'] ?? $user['phone'] ?? '') ?>" placeholder="07XX XXX XXX"></label>
<label class="wide">Business name<input name="business_name" required maxlength="180" value="<?= e($_POST['business_name'] ?? '') ?>" placeholder="Name of the business"></label>
<label>Business size<select name="business_size" id="business_size" required><option value="">Select size</option><?php foreach (['Small','Medium','Large'] as $size): ?><option value="<?= $size ?>" <?= (($_POST['business_size'] ?? '')===$size)?'selected':'' ?>><?= $size ?></option><?php endforeach; ?></select><small>Choose according to the county's business classification.</small></label>
<label>Licence period<select name="term_years" id="term_years" required><option value="">Select period</option><?php foreach ([1,3,7] as $years): ?><option value="<?= $years ?>" <?= ((int)($_POST['term_years'] ?? 0)===$years)?'selected':'' ?>><?= $years ?> year<?= $years===1?'':'s' ?></option><?php endforeach; ?></select></label>
<label>Sub-county<select name="subcounty" id="subcounty" required><option value="">Select sub-county</option><?php foreach ($subcounties as $name=>$wards): ?><option value="<?= e($name) ?>" <?= (($_POST['subcounty'] ?? '')===$name)?'selected':'' ?>><?= e($name) ?></option><?php endforeach; ?></select></label>
<label>Ward<select name="ward" id="ward" required><option value="">Select ward</option></select></label>
<label class="wide">Specific area / location<input name="area" required maxlength="180" value="<?= e($_POST['area'] ?? '') ?>" placeholder="Enter village, estate, street, market or landmark"><small>Choose the sub-county and ward above, then enter the exact local area manually.</small></label>
</div>
<div class="rate-preview helper-note" id="rate-preview">Select business size and licence period to see the configured fee. All fees shown are demo values until replaced with officially approved county rates.</div>
<div class="form-actions"><button class="button primary" type="submit">Submit application →</button></div></form></div>
<section class="panel">
    <div class="panel-heading">
        <div><h2>Your applications</h2>
        <p>Track application and payment verification status.</p>
    </div>
</div>
<?php if (!$applications): ?>
    <div class="empty-state"><h3>No business licence applications yet</h3>
    <p>Your registrations and renewals will appear here after submission.</p>
</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Business</th>
                    <th>Licence</th>
                    <th>Location</th>
                    <th>Fee</th>
                    <th>Application</th>
                    <th>Payment</th>
                    <th>Pay / submit code</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                    <tr>
                        <td class="reference"><?= e($app['reference_no']) ?></td>
                        <td><?= e($app['business_name']) ?><br><small><?= e($app['business_size']) ?> · <?= (int)$app['term_years'] ?> year(s)</small></td>
                        <td><?= e($app['application_type']) ?><?= $app['existing_licence_no'] ? '<br><small>'.e($app['existing_licence_no']).'</small>' : '' ?></td>
                        <td><?= e($app['subcounty']) ?>, <?= e($app['ward']) ?><br><small><?= e($app['area']) ?></small></td>
                        <td><strong>KSh <?= number_format((float)$app['fee_amount'],2) ?></strong></td>
                        <td><span class="status-pill <?= $app['application_status']==='Approved'?'success':($app['application_status']==='Rejected'?'warning':'info') ?>"><?= e($app['application_status']) ?></span></td>
                        <td><span class="status-pill <?= $app['payment_status']==='Verified'?'success':($app['payment_status']==='Rejected'?'warning':'info') ?>"><?= e($app['payment_status']) ?></span><?php if ($app['admin_note']): ?><br><small><?= e($app['admin_note']) ?></small><?php endif; ?></td>
                            <td><?php if ($app['application_status']!=='Approved'): ?><span class="muted">Awaiting approval</span><?php elseif (in_array($app['payment_status'],['Awaiting Payment','Rejected'],true)): ?>
                                <form method="post" class="payment-code-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="pay">
                                    <input type="hidden" name="licence_id" value="<?= (int)$app['id'] ?>">
                                    <input name="mpesa_code" required pattern="[A-Za-z0-9]{8,12}" placeholder="M-Pesa code" aria-label="M-Pesa transaction code">
                                    <button class="button small primary" type="submit">Submit</button>
                                </form>
                                <?php else: ?><span class="muted">No action</span><?php endif; ?>
                                </td>
                            </tr><?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </section>
<script>
(() => {
 const wards = <?= json_encode($subcounties, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
 const rates = <?= json_encode($rateMap, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
 const subcounty=document.getElementById('subcounty'), ward=document.getElementById('ward');
 const size=document.getElementById('business_size'), term=document.getElementById('term_years'), preview=document.getElementById('rate-preview');
 const type=document.getElementById('application_type'), noWrap=document.getElementById('licence-no-wrap'), noInput=document.getElementById('existing_licence_no');
 const selectedWard=<?= json_encode((string)($_POST['ward'] ?? '')) ?>;
 function updateWards(){const list=wards[subcounty.value]||[];ward.innerHTML='<option value="">Select ward</option>';list.forEach(w=>{const o=document.createElement('option');o.value=w;o.textContent=w;if(w===selectedWard)o.selected=true;ward.appendChild(o);});}
 function updateFee(){const amount=rates[size.value]?.[term.value];preview.textContent=(amount!==undefined)?`Configured fee: KSh ${Number(amount).toLocaleString('en-KE',{minimumFractionDigits:2,maximumFractionDigits:2})}. Demo tariff only—replace with official county-approved rates before production.`:'Select business size and licence period to see the configured fee. Demo rates must be confirmed by the county.';}
 function updateType(){const renew=type.value==='Renewal';noWrap.hidden=!renew;noInput.required=renew;}
 subcounty.addEventListener('change',updateWards);size.addEventListener('change',updateFee);term.addEventListener('change',updateFee);type.addEventListener('change',updateType);updateWards();updateFee();updateType();
})();
</script>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
