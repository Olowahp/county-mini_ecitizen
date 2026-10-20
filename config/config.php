<?php
declare(strict_types=1);

return [
    'app_name' => "Murang'a County Public Service Portal",
    'base_url' => '',
    // MySQL/MariaDB connection settings (XAMPP defaults shown; change for hosting for my project).
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_port' => (int)(getenv('DB_PORT') ?: 3306),
    'db_name' => getenv('DB_NAME') ?: 'muranga_citizen',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_password' => getenv('DB_PASSWORD') ?: '',
    'upload_dir' => dirname(__DIR__) . '/public/uploads/requests',
    'news_upload_dir' => dirname(__DIR__) . '/public/uploads/news',
    'max_upload_bytes' => 5 * 1024 * 1024,
    'allowed_image_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    'mail_from' => 'noreply@muranga.go.ke',
    'support_phone' => '0704027671',
    'support_contact' => 'Benard Owino',
    'support_email' => 'benardolowah15@gmail.com',
];
