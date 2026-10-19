<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (current_user()) redirect('/dashboard.php');
$pageTitle = 'Choose a new password';
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = false;
$reset = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = $db->prepare('SELECT * FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP ORDER BY id DESC LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $reset = $stmt->fetch();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$reset) $error = 'This reset link is invalid or expired. Request a new one.';
    elseif (strlen($password) < 8) $error = 'Your new password must contain at least 8 characters.';
    elseif ($password !== $confirm) $error = 'The passwords do not match.';
    else {
        $db->beginTransaction();
        $update = $db->prepare('UPDATE users SET password_hash=? WHERE id=?');
        $update->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
        $mark = $db->prepare('UPDATE password_resets SET used_at=CURRENT_TIMESTAMP WHERE user_id=? AND used_at IS NULL');
        $mark->execute([$reset['user_id']]);
        $db->commit();
        $success = true;
    }
}
require dirname(__DIR__) . '/app/views/header.php';
?>
<div class="auth-layout">
    <div class="auth-aside"><span class="eyebrow">ACCOUNT SECURITY</span>
    <h1>Craete a new password.</h1>
    <p>For your security, reset links can only be used once and expire after one hour.</p>
</div>
<div class="form-card"><span class="eyebrow">NEW PASSWORD</span>
<h2>Set a new password</h2>
<?php if ($success): ?>
    <div class="alert success">Your password has been updated. You can now sign in.</div>
    <a class="button primary full" href="login.php">Go to sign in →</a>
    <?php elseif (!$reset): ?>
        <div class="alert error">This reset link is invalid or expired. Request a new one.</div>
        <a class="button primary full" href="forgot-password.php">Request another link</a><?php else: ?><?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <label>New password<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
                <label>Confirm new password<input type="password" name="confirm_password" minlength="8" required autocomplete="new-password"></label>
                <button class="button primary full" type="submit">Change password →</button>
            </form><?php endif; ?></div></div>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
