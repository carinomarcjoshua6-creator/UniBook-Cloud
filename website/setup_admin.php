<?php
require_once __DIR__ . '/config/auth.php';
$count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$message = '';
$error = '';
if ($count > 0) {
    http_response_code(403);
    exit('Setup is disabled because a user account already exists. Remove setup_admin.php from the web directory.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($fullName === '' || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username) || strlen($password) < 10) {
        $error = 'Enter a name, a username of 3–50 allowed characters, and a password with at least 10 characters.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (username,password,full_name,role) VALUES (?,?,?,?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullName, 'admin']);
        $message = 'Admin account created. Delete setup_admin.php before using this system in a shared environment.';
    }
}
$pageTitle = 'Initial Setup';
include __DIR__ . '/includes/header.php';
?>
<section class="login-card">
  <div class="eyebrow">ONE-TIME SETUP</div>
  <h1>Create Admin Account</h1>
  <p class="muted">This page only works while the users table is empty.</p>
  <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($message): ?><div class="success"><?= htmlspecialchars($message) ?> <a href="login.php">Go to login</a></div><?php else: ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <label>Full name</label><input name="full_name" required>
    <label>Username</label><input name="username" required minlength="3">
    <label>Password (minimum 10 characters)</label><input type="password" name="password" required minlength="10">
    <button class="btn primary full">Create Admin</button>
  </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
