<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (current_user()) redirect('/dashboard.php');
$pageTitle = 'Create account';
$errors = [];
$name = $email = $phone = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    if (mb_strlen($name) < 2) $errors[] = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
    if (!$errors) {
        try {
            $stmt = $db->prepare("INSERT INTO users (name,email,phone,password_hash) VALUES (?,?,?,?)");
            $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)$db->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'citizen'];
            flash('success', 'Your account is ready. You can now submit a service request.');
            redirect('/dashboard.php');
        } catch (mysqli_sql_exception $exception) {
            $errors[] = 'That email address is already registered.';
        }
    }
}
require dirname(__DIR__) . '/app/views/header.php';
?>
<div class="auth-layout">
    <div class="auth-aside"><span class="eyebrow">CITIZEN ACCESS</span><h1>Public services, without the runaround.</h1><p>Create an account to report issues, follow progress and keep your service requests in one place.</p>
    <div class="aside-feature">✓ &nbsp; One dashboard for your requests</div>
    <div class="aside-feature">✓ &nbsp; Clear status updates</div>
    <div class="aside-feature">✓ &nbsp; Reference number for every case</div>
</div>
<div class="form-card"><span class="eyebrow">GET STARTED</span><h2>Create your account</h2>
<p class="muted">Enter your details below to register.</p>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" class="stack-form">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Full name<input name="name" value="<?= e($name) ?>" required autocomplete="name"></label>
<label>Email address<input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
<label>Phone number <span class="optional">(optional)</span><input name="phone" value="<?= e($phone) ?>" autocomplete="tel"></label>
<label>Password<input type="password" name="password" minlength="8" required autocomplete="new-password"><small>The password must be 8 character long.</small></label>
<button class="button primary full" type="submit">Create account →</button>
</form>
<p class="form-switch">Already registered? <a href="login.php">Sign in</a></p>
</div>
</div>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
