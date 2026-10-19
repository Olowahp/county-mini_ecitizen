<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (current_user()) redirect('/dashboard.php');
$pageTitle = 'Sign in';
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $stmt = $db->prepare('SELECT id,name,email,password_hash,role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
        redirect(in_array($user['role'], ['admin','super_admin'], true) ? '/admin/index.php' : '/dashboard.php');
    }
    $error = 'The email address or password was not recognised.';
}
require dirname(__DIR__) . '/app/views/header.php';
?>
<div class="auth-layout">
    <div class="auth-aside"><span class="eyebrow">WELCOME BACK</span><h1>Pick up where your request left off.</h1>
    <p>Sign in to view your cases, check updates and submit a new service request.</p>
    <div class="aside-feature">✓ &nbsp; Secure access to your requests</div>
<div class="aside-feature">✓ &nbsp; Updates from service administrators</div>
</div>
<div class="form-card"><span class="eyebrow">CITIZEN / ADMIN</span><h2>Sign in to your account</h2><p class="muted">Use the email and password associated with your account.</p>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="stack-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Email address<input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
<label>Password<input type="password" name="password" required autocomplete="current-password"></label>
<p class="forgot-link"><a href="forgot-password.php">Forgot your password?</a></p>
<button class="button primary full" type="submit">Sign in →</button></form>
<p class="form-switch">Dont have an account? <a href="register.php">Create an account</a></p>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
