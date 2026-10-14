<?php
require_once __DIR__ . '/config/auth.php';
require_admin();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        if ($fullName === '' || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username) || strlen($password) < 10 || !in_array($role,['admin','staff'],true)) {
            $error = 'Check the name, username, role, and password (minimum 10 characters).';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO users(username,password,full_name,role) VALUES(?,?,?,?)');
                $stmt->execute([$username,password_hash($password,PASSWORD_DEFAULT),$fullName,$role]);
                header('Location: users.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Could not create user. The username may already exist.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user']['id']) {
            $error = 'You cannot delete the account currently in use.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
            $stmt->execute([$id]);
            header('Location: users.php');
            exit;
        }
    }
}
$users = $pdo->query('SELECT id,username,full_name,role,created_at FROM users ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'Users';
include __DIR__ . '/includes/header.php';
?>
<div class="hero"><div><div class="eyebrow">ACCESS CONTROL</div><h1>User Management</h1><p>Create Admin and Staff accounts for the reservation workflow.</p></div></div>
<?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<section class="panel"><h2>Create User</h2><form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="create"><div><label>Full name</label><input name="full_name" required></div><div><label>Username</label><input name="username" required minlength="3"></div><div><label>Password (minimum 10 characters)</label><input type="password" name="password" required minlength="10"></div><div><label>Role</label><select name="role"><option value="staff">Staff</option><option value="admin">Admin</option></select></div><div class="form-action"><button class="btn primary">Create User</button></div></form></section>
<section class="panel"><h2>Registered Users</h2><div class="table-wrap"><table><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Created</th><th>Action</th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td><?= htmlspecialchars($u['full_name']) ?></td><td><?= htmlspecialchars($u['username']) ?></td><td><?= htmlspecialchars(ucfirst($u['role'])) ?></td><td><?= htmlspecialchars($u['created_at']) ?></td><td><?php if($u['id']!=$_SESSION['user']['id']): ?><form method="post" class="inline" onsubmit="return confirm('Delete this user?')"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn small danger">Delete</button></form><?php else: ?>Current account<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
