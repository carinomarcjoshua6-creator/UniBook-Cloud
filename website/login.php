<?php
require_once __DIR__ . '/config/auth.php';
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, username, password, full_name, role FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<section class="login-card">
  <div class="eyebrow">CAMPUS OPERATIONS</div>
  <h1>Welcome to UniBook Cloud</h1>
  <p class="muted">Request rooms and shared equipment in one place.</p>
  <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <label>Username</label><input name="username" autocomplete="username" required>
    <label>Password</label><input type="password" name="password" autocomplete="current-password" required>
    <button class="btn primary full">Sign in</button>
  </form>
  <p class="hint">First setup? Open <code>/setup_admin.php</code> to create the first administrator.</p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
