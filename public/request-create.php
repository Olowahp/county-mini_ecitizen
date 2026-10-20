<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_login();
$pageTitle = 'Submit a request';
$categories = $db->query('SELECT id,name FROM categories WHERE is_active=1 ORDER BY CASE WHEN name="Water and Sanitation" THEN 0 ELSE 1 END, name')->fetchAll();
$errors = [];
$categoryId = (int)($_POST['category_id'] ?? 0);
$location = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');
$waterProblem = trim($_POST['water_problem'] ?? '');
$otherProblem = trim($_POST['other_problem'] ?? '');
$waterProblems = [
    'Water meter damaged or not working',
    'Water meter reading is incorrect',
    'Water leakage at meter or connection',
    'No water supply',
    'Low water pressure',
    'Water quality or discolouration',
    'Unexpected water bill',
    'Burst pipe or water leakage',
    'Blocked sewer or drainage',
    'Sewer overflow',
    'Blocked public drain',
    'Other',
];
$categoryNames = array_column($categories, 'name', 'id');
$isWater = ($categoryNames[$categoryId] ?? '') === 'Water and Sanitation';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$categoryId || !isset($categoryNames[$categoryId])) $errors[] = 'Choose a valid service category.';
    if (mb_strlen($location) < 3) $errors[] = 'Enter a specific location, such as a village, estate or landmark.';
    if ($isWater && !in_array($waterProblem, $waterProblems, true)) $errors[] = 'Choose the water or sanitation problem.';
    if ($isWater && $waterProblem === 'Other' && mb_strlen($otherProblem) < 5) $errors[] = 'Describe the other water or sanitation problem.';
    if (mb_strlen($description) < 10) $errors[] = 'Please provide at least 10 characters of additional details.';
    $imagePath = null;
    if (!$errors) {
        try {
            if (!empty($_FILES['image'])) $imagePath = upload_request_image($_FILES['image'], $config);
            $finalDescription = $isWater
                ? 'Problem: ' . $waterProblem . ($waterProblem === 'Other' ? ' — ' . $otherProblem : '') . "\n\nDetails: " . $description
                : $description;
            $reference = make_reference();
            $stmt = $db->prepare("INSERT INTO requests (reference_no,user_id,category_id,location,description,image_path) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$reference, current_user()['id'], $categoryId, $location, $finalDescription, $imagePath]);
            $requestId = (int)$db->lastInsertId();
            $history = $db->prepare('INSERT INTO status_history (request_id,status,note,changed_by) VALUES (?,?,?,?)');
            $history->execute([$requestId, 'Pending', 'Request submitted by citizen.', current_user()['id']]);
            flash('success', 'Request submitted successfully. Your reference is ' . $reference . '.');
            redirect('/request-view.php?id=' . $requestId);
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}
require dirname(__DIR__) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">CITIZEN SERVICES</span><h1>Submit a service request</h1>
    <p>Water and sanitation services are listed first. Choose the problem that best describes your issue.</p></div>
    <a class="button ghost" href="dashboard.php">← My requests</a>
</div>
<div class="content-narrow panel">
    <div class="panel-heading">
        <div><h2>Request details</h2>
        <p>Fields marked with * are required.</p></div><span class="step-tag">STEP 1 OF 1</span></div>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="stack-form" id="request-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Service category *<select name="category_id" id="category_id" required>
    <option value="">Select a service</option><?php foreach ($categories as $category): ?>
        <option value="<?= (int)$category['id'] ?>" data-name="<?= e($category['name']) ?>" <?= $categoryId === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
        <?php endforeach; ?></select>
</label>
<div id="water-fields" class="water-fields" <?= $isWater ? '' : 'hidden' ?>>
    <label>Water / sanitation problem *<select name="water_problem" id="water_problem"><option value="">Select the problem</option><?php foreach ($waterProblems as $problem): ?><option <?= $waterProblem === $problem ? 'selected' : '' ?>><?= e($problem) ?></option>
        <?php endforeach; ?></select>
</label>
    <label id="other-problem-wrap" <?= $waterProblem === 'Other' ? '' : 'hidden' ?>>Describe the other problem *<input name="other_problem" id="other_problem" value="<?= e($otherProblem) ?>" placeholder="Describe the water or sanitation issue"></label>
    <p class="helper-note">If your water meter requires repair or replacement, staff may inspect the issue and send a payment request with the amount due. Pay only after the official request appears in your account, then submit your M-Pesa transaction code for administrator verification.</p>
</div>
<label>Location *<input name="location" value="<?= e($location) ?>" placeholder="e.g. Kiharu, near the market gate" required><small>Include a village, estate, road, facility or nearby landmark.</small></label>
<label>Additional details *<textarea name="description" rows="5" minlength="10" required placeholder="Explain what happened, when you noticed it, and any details that may help the service team."><?= e($description) ?></textarea></label>
<label>Attach an image <span class="optional">(optional, JPG/PNG/WebP up to 5 MB)</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
<div class="form-actions"><a class="button ghost" href="dashboard.php">Cancel</a>
<button class="button primary" type="submit">Submit request →</button></div>
</form>
</div>
<script>
(() => {
 const category = document.getElementById('category_id');
 const waterFields = document.getElementById('water-fields');
 const waterProblem = document.getElementById('water_problem');
 const otherWrap = document.getElementById('other-problem-wrap');
 const otherInput = document.getElementById('other_problem');
 function updateFields() {
   const selected = category.options[category.selectedIndex];
   const isWater = selected && selected.dataset.name === 'Water and Sanitation';
   waterFields.hidden = !isWater;
   waterProblem.required = !!isWater;
   otherWrap.hidden = !isWater || waterProblem.value !== 'Other';
   otherInput.required = !!isWater && waterProblem.value === 'Other';
 }
 category.addEventListener('change', updateFields);
 waterProblem.addEventListener('change', updateFields);
 updateFields();
})();
</script>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
