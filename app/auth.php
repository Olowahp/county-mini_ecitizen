<?php
declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        flash('warning', 'Please sign in to continue.');
        redirect('/login.php');
    }
}

function is_admin_role(?string $role): bool
{
    return in_array($role, ['admin', 'super_admin'], true);
}

function require_admin(): void
{
    require_login();
    if (!is_admin_role(current_user()['role'] ?? null)) {
        http_response_code(403);
        exit('Access denied. Administrator permission is required.');
    }
}

function require_super_admin(): void
{
    require_login();
    if ((current_user()['role'] ?? '') !== 'super_admin') {
        http_response_code(403);
        exit('Access denied. Super administrator permission is required.');
    }
}
