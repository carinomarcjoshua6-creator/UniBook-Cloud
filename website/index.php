<?php
require_once __DIR__ . '/config/auth.php';
require_login();
$user = $_SESSION['user'];
if ($user['role'] === 'admin') {
    $stats = [
        'Resources' => (int)$pdo->query('SELECT COUNT(*) FROM resources')->fetchColumn(),
        'Pending Requests' => (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status='Pending'")->fetchColumn(),
        'Approved' => (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status='Approved'")->fetchColumn(),
        'Total Requests' => (int)$pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn()
    ];
    $stmt = $pdo->query("SELECT r.*,u.full_name,res.resource_name FROM reservations r JOIN users u ON u.id=r.user_id JOIN resources res ON res.id=r.resource_id ORDER BY r.created_at DESC LIMIT 8");
} else {
    $stmtStats = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id=?");
    $stmtStats->execute([$user['id']]);
    $mine = (int)$stmtStats->fetchColumn();
    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id=? AND status='Pending'");
    $stmtPending->execute([$user['id']]);
    $pending = (int)$stmtPending->fetchColumn();
    $stats = ['My Requests' => $mine, 'Pending' => $pending];
    $stmt = $pdo->prepare("SELECT r.*,u.full_name,res.resource_name FROM reservations r JOIN users u ON u.id=r.user_id JOIN resources res ON res.id=r.resource_id WHERE r.user_id=? ORDER BY r.created_at DESC LIMIT 8");
    $stmt->execute([$user['id']]);
}
$recent = $stmt->fetchAll();
$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<div class="hero">
  <div><div class="eyebrow">CAMPUS OPERATIONS PORTAL</div><h1>Hello, <?= htmlspecialchars($user['full_name']) ?>.</h1><p>Manage facility and equipment reservations from one dashboard.</p></div>
  <a class="btn primary" href="reservations.php?action=new">+ New Reservation</a>
</div>
<div class="stats">
<?php foreach ($stats as $label => $value): ?><article class="stat"><span><?= htmlspecialchars($label) ?></span><strong><?= $value ?></strong></article><?php endforeach; ?>
</div>
<section class="panel">
  <div class="panel-title"><div><h2><?= $user['role']==='admin' ? 'Recent Reservations' : 'My Recent Reservations' ?></h2><p class="muted">Latest reservation activity</p></div><a href="reservations.php">View all</a></div>
  <div class="table-wrap"><table><thead><tr><th>Code</th><?php if ($user['role']==='admin'): ?><th>Requester</th><?php endif; ?><th>Resource</th><th>Date</th><th>Time</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($recent as $r): ?><tr><td><a href="reservations.php?id=<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['reservation_code']) ?></a></td><?php if ($user['role']==='admin'): ?><td><?= htmlspecialchars($r['full_name']) ?></td><?php endif; ?><td><?= htmlspecialchars($r['resource_name']) ?></td><td><?= htmlspecialchars($r['reservation_date']) ?></td><td><?= htmlspecialchars(substr($r['start_time'],0,5).'–'.substr($r['end_time'],0,5)) ?></td><td><span class="status <?= strtolower($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td></tr><?php endforeach; ?>
  <?php if (!$recent): ?><tr><td colspan="6" class="muted">No reservations yet. Create the first request to get started.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
