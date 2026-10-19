<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Return URL paths that work from the public root or a nested installation. */
function app_base_path(): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if (preg_match('~^(.*?)/public(?:/|$)~', $script, $matches)) {
        return rtrim($matches[1], '/') . '/public';
    }
    $directory = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return $directory === '' || $directory === '.' ? '' : $directory;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    if (str_starts_with($path, '../uploads/')) $path = substr($path, 3);
    $base = app_base_path();
    if ($path === '') return $base;
    return $base . '/' . $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Your session token expired. Go back, refresh the page, and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function status_class(string $status): string
{
    return match ($status) {
        'Resolved' => 'success',
        'In Progress' => 'info',
        default => 'warning',
    };
}

function make_reference(): string
{
    return 'MUR-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function upload_request_image(array $file, array $config): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image could not be uploaded. Please try again.');
    }
    if (($file['size'] ?? 0) > $config['max_upload_bytes']) {
        throw new RuntimeException('The image must be 5 MB or smaller.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!in_array($mime, $config['allowed_image_mimes'], true)) {
        throw new RuntimeException('Use a JPG, PNG or WebP image.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $destination = rtrim($config['upload_dir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The image could not be saved.');
    }
    return '../uploads/requests/' . $filename;
}
