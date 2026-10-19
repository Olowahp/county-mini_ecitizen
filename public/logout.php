<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/index.php');
verify_csrf();
$_SESSION = [];
session_regenerate_id(true);
flash('success', 'You have signed out.');
redirect('/index.php');
