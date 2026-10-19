<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (current_user()) redirect('/dashboard.php');
$pageTitle = 'Reset password';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $db->prepare('SELECT id,name,email FROM users WHERE email=?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $save = $db->prepare('INSERT INTO password_resets (user_id,token_hash,expires_at) VALUES (?,?,?)');
            $save->execute([$user['id'], $tokenHash, $expires]);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $resetUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000') . url('reset-password.php') . '?token=' . urlencode($token);
            $subject = "Murang'a County Portal password reset";
            $body = "Hello {$user['name']},\n\nA password reset was requested for your portal account. Open the link below within 60 minutes:\n\n{$resetUrl}\n\nIf you did not request this, you can ignore this email.\n\nMurang'a County Public Service Portal";
            $headers = "From: " . $config['mail_from'] . "\r\nContent-Type: text/plain; charset=UTF-8";
            // Production deployment must configure PHP mail or replace this with authenticated SMTP.
            @mail($user['email'], $subject, $body, $headers);
        }
    }
    // Avoid disclosing whether the email is registered.
    $message = 'If that email is registered and email delivery is configured, a password reset link will be sent. Check your inbox and spam folder.';
}
require dirname(__DIR__) . '/app/views/header.php';
?>
<div class="auth-layout">
    <div class="auth-aside"><span class="eyebrow">ACCOUNT RECOVERY</span><h1>Get back into your account.</h1>
    <p>We will send a time-limited password reset link to your registered email address.</p>
    <div class="aside-feature">✓ &nbsp; Reset links expire after 60 minutes</div>
    <div class="aside-feature">✓ &nbsp; Your password is stored as a secure hash</div>
</div>
<div class="form-card"><span class="eyebrow">PASSWORD HELP</span><h2>Reset your password</h2>
<p class="muted">Enter the email address linked to your account.</p><?php if ($message): ?>
    <div class="alert success"><?= e($message) ?></div>
    <?php endif; ?>
    <form method="post" class="stack-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Registered email address<input type="email" name="email" required autocomplete="email"></label>
        <button class="button primary full" type="submit">Send reset link →</button>
    </form><p class="form-switch"><a href="login.php">← Back to sign in</a></p>
</div>
</div>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
