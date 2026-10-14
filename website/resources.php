<?php
require_once __DIR__ . '/config/auth.php';
require_login();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $code = strtoupper(trim($_POST['resource_code'] ?? ''));
        $name = trim($_POST['resource_name'] ?? '');
        $type = $_POST['resource_type'] ?? 'Room';
        $location = trim($_POST['location'] ?? '');
        $capacity = $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;
        if ($code === '' || $name === '' || $location === '' || !in_array($type, ['Room','Equipment'], true)) {
            $error = 'Please complete all required fields.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO resources(resource_code,resource_name,resource_type,location,capacity) VALUES(?,?,?,?,?)');
                $stmt->execute([$code,$name,$type,$location,$capacity]);
                header('Location: resources.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Unable to add resource. Check that the resource code is unique.';
            }
        }
    } elseif ($action === 'toggle') {
        $stmt = $pdo->prepare("UPDATE resources SET status=IF(status='Available','Unavailable','Available') WHERE id=?");
        $stmt->execute([(int)$_POST['id']]);
        header('Location: resources.php');
        exit;
    }
}
$resources = $pdo->query('SELECT * FROM resources ORDER BY resource_type,resource_name')->fetchAll();
$pageTitle = 'Resources';
include __DIR__ . '/includes/header.php';
?>
<div class="hero"><div><div class="eyebrow">RESOURCE DIRECTORY</div><h1>Resources</h1><p>Rooms and shared equipment available for reservation.</p></div></div>
<?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($_SESSION['user']['role']==='admin'): ?>
<section class="panel"><h2>Add Resource</h2><form method="post" class="form-grid">
<input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="add">
<div><label>Resource Code</label><input name="resource_code" required placeholder="ROOM-101"></div>
<div><label>Name</label><input name="resource_name" required placeholder="Seminar Room"></div>
<div><label>Type</label><select name="resource_type"><option>Room</option><option>Equipment</option></select></div>
<div><label>Location</label><input name="location" required placeholder="Main Building"></div>
<div><label>Capacity (optional)</label><input name="capacity" type="number" min="1"></div>
<div class="form-action"><button class="btn primary">Add Resource</button></div>
</form></section>
<?php endif; ?>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Code</th><th>Resource</th><th>Type</th><th>Location</th><th>Capacity</th><th>Status</th><?php if ($_SESSION['user']['role']==='admin'): ?><th>Action</th><?php endif; ?></tr></thead><tbody>
<?php foreach ($resources as $r): ?><tr><td><?= htmlspecialchars($r['resource_code']) ?></td><td><?= htmlspecialchars($r['resource_name']) ?></td><td><?= htmlspecialchars($r['resource_type']) ?></td><td><?= htmlspecialchars($r['location']) ?></td><td><?= htmlspecialchars($r['capacity'] ?? '—') ?></td><td><?= htmlspecialchars($r['status']) ?></td><?php if ($_SESSION['user']['role']==='admin'): ?><td><form method="post" class="inline"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn small"><?= $r['status']==='Available'?'Mark unavailable':'Mark available' ?></button></form></td><?php endif; ?></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
