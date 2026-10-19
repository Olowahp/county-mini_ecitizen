<?php
declare(strict_types=1);

session_start();
$config = require dirname(__DIR__) . '/config/config.php';
foreach ([$config['upload_dir'], $config['news_upload_dir']] as $directory) {
    if (!is_dir($directory)) mkdir($directory, 0775, true);
}
require_once __DIR__ . '/mysql.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
try {
    $db = new DatabaseConnection($config);
    initialize_database($db);
} catch (Throwable $exception) {
    http_response_code(500);
    error_log('Muranga portal database connection error: ' . $exception->getMessage());
    exit('Database connection failed. Check config/config.php, confirm MySQL is running, and import database/muranga_citizen.sql.');
}
