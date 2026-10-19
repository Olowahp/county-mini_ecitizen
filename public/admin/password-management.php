<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_login();

$access = $db->prepare('SELECT role,can_reset_password FROM users WHERE id=?');
$access->execute([(int)current_user()['id']]);
$actor = $access->fetch();
$isSuperAdmin = ($actor['role'] ?? '') === 'super_admin';
$hasPermission = $isSuperAdmin || (($actor['role'] ?? '') === 'admin' && (int)($actor['can_reset_password'] ?? 0) === 1);
if (!$hasPermission) {
    http_response_code(403);
    exit('Access denied. A super administrator must enable password-management permission for your account.');
}

$pageTitle = 'User password management';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $userId = (int)($_POST['user_id'] ?? 0);
    $password = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if ($userId < 1) $errors[] = 'Select a valid user account.';
    if (strlen($password) < 8) $errors[] = 'The new password must contain at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'The new password and confirmation do not match.';
    if ($userId === (int)current_user()['id']) $errors[] = 'Use the account password reset process to change your own password.';

    if (!$errors) {
        $targetStmt = $db->prepare('SELECT id,name,role FROM users WHERE id=?');
        $targetStmt->execute([$userId]);
        $target = $targetStmt->fetch();
        if (!$target) {
            $errors[] = 'The selected user account could not be found.';
        } elseif (!$isSuperAdmin && !in_array($target['role'], ['citizen','staff'], true)) {
            $errors[] = 'Administrators with delegated permission can reset citizen and county staff passwords only. Contact a super administrator for other accounts.';
        } elseif ($isSuperAdmin && $target['role'] === 'super_admin') {
            $errors[] = 'Super administrator passwords must be changed by the account holder or through the secure password reset process.';
        } else {
            $update = $db->prepare('UPDATE users SET password_hash=? WHERE id=?');
            $update->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
            $invalidate = $db->prepare('DELETE FROM password_resets WHERE user_id=?');
            $invalidate->execute([$userId]);
            flash('success', 'Password updated for ' . $target['name'] . '. Share the new password securely and ask the user to change it after signing in.');
            redirect('/admin/password-management.php');
        }
    }
}

if ($isSuperAdmin) {
    $users = $db->query("SELECT id,name,email,role FROM users WHERE role <> 'super_admin' ORDER BY name ASC")->fetchAll();
} else {
    $users = $db->query("SELECT id,name,email,role FROM users WHERE role IN ('citizen','staff') ORDER BY name ASC")->fetchAll();
}
require dirname(__DIR__, 2) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">ACCOUNT SECURITY</span><h1>User password management</h1>
    <p>You are ask to do the security check before doing a password reste to a user.</p>
</div>
</div>
<div class="admin-nav">
    <a href="admin/index.php">Overview</a><?php if ($isSuperAdmin): ?>
        <a href="admin/users.php">Users and staff</a><?php endif; ?>
        <a class="active" href="admin/password-management.php">User passwords</a>
    </div>
<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= e($error) ?>
</div
><?php endforeach; ?>
<section class="panel">
    <div class="panel-heading"><div><h2>Reset a user's password</h2>
    <p>Passwords are securely hashed. Existing password-reset links for this account will be invalidated.</p>
</div>
</div>
<?php if (!$users): ?>
    <div class="empty-inline">There no eligible user account to manage this time</div><?php else: ?>
<form method="post" class="stack-form" style="max-width:640px">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>User account<select name="user_id" required><option value="">Choose an account</option>
<?php foreach ($users as $targetUser): ?>
    <option value="<?= (int)$targetUser['id'] ?>"><?= e($targetUser['name']) ?> — <?= e($targetUser['email']) ?> (<?= e(str_replace('_',' ',$targetUser['role'])) ?>)</option>
    <?php endforeach; ?>
</select>
</label>
<label>New password<input type="password" name="new_password" minlength="8" autocomplete="new-password" required>
<small>Use at least 8 characters with a special character. Do not send passwords in plain-text email or public messages.</small>
</label>
<label>Confirm new password<input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></label>
<button class="button primary" type="submit">Update password</button>
</form><?php endif; ?>
</section>
<?php require dirname(__DIR__, 2) . '/app/views/footer.php'; ?>
