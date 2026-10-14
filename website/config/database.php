<?php
$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'unibook';
$user = getenv('DB_USER') ?: 'unibook_user';
$password = getenv('DB_PASSWORD') ?: '';

$dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $user, $password, $options);
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Manila');
