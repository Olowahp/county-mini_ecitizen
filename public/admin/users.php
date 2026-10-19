<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_super_admin();
$pageTitle = 'User and staff management';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'staff';
        $password = $_POST['password'] ?? '';
        if (mb_strlen($name) < 2) $errors[] = 'Enter the staff member’s full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (!in_array($role, ['citizen','staff','admin','super_admin'], true)) $errors[] = 'Choose a valid role.';
        if (strlen($password) < 8) $errors[] = 'Temporary password must contain at least 8 characters.';
        if (!$errors) {
            try {
                $stmt = $db->prepare('INSERT INTO users (name,email,phone,password_hash,role) VALUES (?,?,?,?,?)');
                $stmt->execute([$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT),$role]);
                flash('success','User account created. Share the temporary password securely and ask the user to change it.');
                redirect('/admin/users.php');
            } catch (mysqli_sql_exception $exception) { $errors[] = 'That email address is already registered, or the database needs its role migration applied.'; }
        }
    } elseif ($action === 'update') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'citizen';
        if (mb_strlen($name) < 2) $errors[] = 'Name must contain at least 2 characters.';
        if (!in_array($role, ['citizen','staff','admin','super_admin'], true)) $errors[] = 'Choose a valid role.';
        if ($userId === (int)current_user()['id'] && $role !== 'super_admin') $errors[] = 'You cannot remove your own super administrator access.';
        if (!$errors) {
            $stmt = $db->prepare('UPDATE users SET name=?,phone=?,role=? WHERE id=?');
            $stmt->execute([$name,$phone,$role,$userId]);
            flash('success','User details updated.'); redirect('/admin/users.php');
        }
    } elseif ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === (int)current_user()['id']) $errors[] = 'You cannot delete your own account.';
        else {
            $check = $db->prepare('SELECT COUNT(*) FROM requests WHERE user_id=?');
            $check->execute([$userId]);
            if ((int)$check->fetchColumn() > 0) $errors[] = 'This account has service requests and cannot be deleted. Change its role instead to preserve the audit trail.';
            else {
                $stmt = $db->prepare('DELETE FROM users WHERE id=?');
                $stmt->execute([$userId]);
                flash('success','User account deleted.'); redirect('/admin/users.php');
            }
        }
    }
}
$users = $db->query("SELECT u.id,u.name,u.email,u.phone,u.role,u.created_at,COUNT(r.id) AS request_count FROM users u LEFT JOIN requests r ON r.user_id=u.id GROUP BY u.id ORDER BY u.created_at DESC")->fetchAll();
require dirname(__DIR__, 2) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">SUPER ADMINISTRATION</span>
    <h1>Users and county staff</h1><p>Create staff accounts and manage access across the portal.</p></div>
</div>
<div class="admin-nav">
    <a href="admin/index.php">Overview</a>
    <a href="admin/requests.php">All requests</a>
    <a href="admin/categories.php">Service categories</a>
    <a class="active" href="admin/users.php">Users and staff</a>
    <a href="admin/payments.php">Water payments</a>
    <a href="admin/business-licences.php">Business licences</a>
    <a href="admin/news.php">News & Notices</a></div>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
<div class="detail-grid category-layout"><section class="panel"><div class="panel-heading">
    <div><h2>Create user or staff account</h2>
    <p>Super administrators control access privileges.</p>
</div>
</div>
<form method="post" class="stack-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="create">
    <label>Full name<input name="name" required minlength="2"></label>
    <label>Email address<input type="email" name="email" required></label>
    <label>Phone number<input name="phone"></label>
    <label>Role<select name="role"><option value="staff">County staff</option>
    <option value="admin">Administrator</option>
    <option value="citizen">Citizen</option>
    <option value="super_admin">Super administrator</option></select></label>
    <label>Temporary password<input type="password" name="password" required minlength="8">
    <small>Please enter a secure password.</small></label>
    <button class="button primary full" type="submit">Create account →</button>
</form></section>
<section class="panel">
    <div class="panel-heading">
    <div><h2>Role guide</h2><p>Grant only the access each user needs.</p>
</div>
</div>
<div class="role-guide">
    <p><strong>Super administrator</strong><span>Full system access, user/staff CRUD and payment verification.</span></p>
    <p><strong>Administrator</strong><span>Manage service requests, categories and payment workflow.</span></p>
    <p><strong>County staff</strong><span>Staff role is reserved for staff-facing workflows; admin access can be granted by a super administrator.</span></p>
    <p><strong>Citizen</strong><span>Submit and track their own service requests and payments.</span></p>
</div></section>
</div>
<section class="panel">
    <div class="panel-heading">
        <div><h2>All accounts</h2>
        <p>Update user details and roles. Accounts with requests cannot be deleted to preserve case history.</p>
    </div><span class="step-tag"><?= count($users) ?> accounts</span>
</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Name / email</th>
                <th>Phone</th><th>Requests</th>
                <th>Joined</th><th>Edit account</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody><?php foreach ($users as $u): ?><tr><td><strong><?= e($u['name']) ?></strong><br><span class="muted"><?= e($u['email']) ?></span></td>
            <td><?= e($u['phone'] ?: '—') ?></td>
            <td><?= (int)$u['request_count'] ?></td>
            <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
            <td><form method="post" class="user-edit-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <input name="name" value="<?= e($u['name']) ?>" required aria-label="Name">
                <input name="phone" value="<?= e($u['phone']) ?>" placeholder="Phone" aria-label="Phone">
                <select name="role"><?php foreach (['citizen','staff','admin','super_admin'] as $role): ?>
                    <option value="<?= $role ?>" <?= $u['role'] === $role ? 'selected' : '' ?>><?= e(ucwords(str_replace('_',' ',$role))) ?></option><?php endforeach; ?>
                </select>
                <button class="button small ghost">Save</button>
            </form>
        </td>
        <td><?php if ((int)$u['id'] !== (int)current_user()['id'] && (int)$u['request_count'] === 0): ?>
            <form method="post" onsubmit="return confirm('Delete this account permanently?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button class="button small danger" type="submit">Delete</button>
            </form><?php else: ?><span class="muted">Protected</span><?php endif; ?></td></tr><?php endforeach; ?>
        </tbody>
    </table>
</div>
</section>
<?php require dirname(__DIR__, 2) . '/app/views/footer.php'; ?>
