<?php
$pageTitle = $pageTitle ?? "Murang'a County Public E-Service Portal";
$user = current_user();
$notice = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Murang'a County</title>
    <base href="<?= e(url('')) ?>/">
    <meta name="theme-color" content="#08783e">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=20261020-2">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php">
        <span class="brand-mark"><i class="fa-solid fa-landmark"></i></span>
        <span><strong>Murang'a County</strong><small>Public E-Service Portal</small></span>
    </a>
    <nav>
        <a href="index.php"><i class="fa-solid fa-house me-1" aria-hidden="true"></i>Home</a>
        <a href="news.php"><i class="fa-regular fa-newspaper me-1" aria-hidden="true"></i>News & Notices</a>
        <?php if ($user): ?>
            <a href="dashboard.php"><i class="fa-solid fa-table-cells-large me-1" aria-hidden="true"></i>Services</a>
            <a href="business-licence.php"><i class="fa-solid fa-store me-1" aria-hidden="true"></i>Business licences</a>
            <?php if (in_array($user['role'], ['admin','super_admin'], true)): ?><a href="admin/index.php"><i class="fa-solid fa-gauge-high me-1" aria-hidden="true"></i>Admin Panel</a><?php endif; ?>
            <form class="nav-form" method="post" action="logout.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="nav-link" type="submit"><i class="fa-solid fa-right-from-bracket me-1" aria-hidden="true"></i>Sign out</button>
            </form>
        <?php else: ?>
            <a href="login.php"><i class="fa-solid fa-right-to-bracket me-1" aria-hidden="true"></i>Sign in</a>
            <a class="nav-cta" href="register.php"><i class="fa-solid fa-user-plus me-1" aria-hidden="true"></i>Create account</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
<?php if ($notice): ?>
    <div id="portal-flash-notice" hidden data-type="<?= e((string)$notice['type']) ?>" data-message="<?= e((string)$notice['message']) ?>"></div>
<?php endif; ?>
