<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/database.php';

function require_login(): void {
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}
function require_admin(): void {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied. Admin role required.');
    }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    $submitted = $_POST['csrf'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Invalid or expired form token. Please go back and try again.');
    }
}
