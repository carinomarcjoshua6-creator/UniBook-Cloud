<?php
require_once __DIR__ . '/config/auth.php';
require_login();
$user = $_SESSION['user'];
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $resourceId = (int)($_POST['resource_id'] ?? 0);
        $purpose = trim($_POST['purpose'] ?? '');
        $date = $_POST['reservation_date'] ?? '';
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';
        $resourceStmt = $pdo->prepare("SELECT id FROM resources WHERE id=? AND status='Available'");
        $resourceStmt->execute([$resourceId]);
        $resourceExists = $resourceStmt->fetchColumn();
        if (!$resourceExists || $purpose === '' || !$date || !$start || !$end || $start >= $end || $date < date('Y-m-d')) {
            $error = 'Check the resource, purpose, date, and time range. End time must be after start time and the date cannot be in the past.';
        } else {
            $conflict = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE resource_id=? AND reservation_date=? AND status='Approved' AND start_time < ? AND end_time > ?");
            $conflict->execute([$resourceId,$date,$end,$start]);
            if ((int)$conflict->fetchColumn() > 0) {
                $error = 'This resource already has an approved reservation that overlaps the selected time.';
            } else {
                $code = 'RSV-' . date('YmdHis') . '-' . random_int(100,999);
                $stmt = $pdo->prepare("INSERT INTO reservations(reservation_code,user_id,resource_id,purpose,reservation_date,start_time,end_time) VALUES(?,?,?,?,?,?,?)");
                $stmt->execute([$code,$user['id'],$resourceId,$purpose,$date,$start,$end]);
                $newId = (int)$pdo->lastInsertId();
                $log = $pdo->prepare("INSERT INTO reservation_activity(reservation_id,actor_user_id,action,details) VALUES(?,?,?,?)");
                $log->execute([$newId,(int)$user['id'],'Created','Reservation request submitted']);
                header('Location: reservations.php?id=' . $newId);
                exit;
            }
        }
    } elseif ($action === 'review' && $user['role'] === 'admin') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $note = trim($_POST['admin_note'] ?? '');
        if (!in_array($status, ['Approved','Rejected'], true)) {
            $error = 'Invalid review status.';
        } else {
            $stmt = $pdo->prepare("UPDATE reservations SET status=?, admin_note=? WHERE id=? AND status='Pending'");
            $stmt->execute([$status,$note,$id]);
            if ($stmt->rowCount() > 0) {
                $log = $pdo->prepare("INSERT INTO reservation_activity(reservation_id,actor_user_id,action,details) VALUES(?,?,?,?)");
                $log->execute([$id,(int)$user['id'],$status,$note !== '' ? $note : 'Request reviewed by admin']);
            }
            header('Location: reservations.php?id=' . $id);
            exit;
        }
    } elseif ($action === 'cancel') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE reservations SET status='Cancelled' WHERE id=? AND user_id=? AND status='Pending'");
        $stmt->execute([$id,$user['id']]);
        if ($stmt->rowCount() > 0) {
            $log = $pdo->prepare("INSERT INTO reservation_activity(reservation_id,actor_user_id,action,details) VALUES(?,?,?,?)");
            $log->execute([$id,(int)$user['id'],'Cancelled','Requester cancelled the pending reservation']);
        }
        header('Location: reservations.php');
        exit;
    }
}
$resources = $pdo->query("SELECT id,resource_name,resource_type,location FROM resources WHERE status='Available' ORDER BY resource_name")->fetchAll();
if (isset($_GET['action']) && $_GET['action']==='new') {
    $pageTitle = 'New Reservation';
    include __DIR__ . '/includes/header.php';
?>
<div class="hero"><div><div class="eyebrow">REQUEST ACCESS</div><h1>New Reservation</h1><p>Submit a request for an available room or shared equipment.</p></div></div>
<?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<section class="panel narrow"><form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="create">
<label>Resource</label><select name="resource_id" required><option value="">Select a resource</option><?php foreach($resources as $r): ?><option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['resource_name'].' · '.$r['resource_type'].' · '.$r['location']) ?></option><?php endforeach; ?></select>
<label>Purpose</label><input name="purpose" maxlength="180" required placeholder="e.g. Student project meeting">
<label>Reservation date</label><input type="date" name="reservation_date" min="<?= date('Y-m-d') ?>" required>
<div class="form-grid two"><div><label>Start time</label><input type="time" name="start_time" required></div><div><label>End time</label><input type="time" name="end_time" required></div></div>
<button class="btn primary full">Submit Request</button></form></section>
<?php include __DIR__ . '/includes/footer.php'; exit; }
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT r.*, u.full_name, u.username, res.resource_name, res.resource_type, res.location FROM reservations r JOIN users u ON u.id=r.user_id JOIN resources res ON res.id=r.resource_id WHERE r.id=?";
    $params = [$id];
    if ($user['role'] !== 'admin') { $sql .= " AND r.user_id=?"; $params[] = $user['id']; }
    $stmt = $pdo->prepare($sql); $stmt->execute($params); $reservation = $stmt->fetch();
    if (!$reservation) { http_response_code(404); exit('Reservation not found.'); }
    $activityStmt = $pdo->prepare('SELECT a.action,a.details,a.created_at,u.full_name FROM reservation_activity a JOIN users u ON u.id=a.actor_user_id WHERE a.reservation_id=? ORDER BY a.created_at DESC');
    $activityStmt->execute([$id]);
    $activity = $activityStmt->fetchAll();
    $pageTitle = 'Reservation Details';
    include __DIR__ . '/includes/header.php';
?>
<div class="hero"><div><div class="eyebrow">RESERVATION DETAILS</div><h1><?= htmlspecialchars($reservation['reservation_code']) ?></h1><p>Review the request and its current approval status.</p></div><a class="btn" href="reservations.php">Back to list</a></div>
<section class="detail-grid"><div class="panel"><h2>Request Information</h2><p><b>Requester:</b> <?= htmlspecialchars($reservation['full_name']) ?></p><p><b>Resource:</b> <?= htmlspecialchars($reservation['resource_name']) ?></p><p><b>Type:</b> <?= htmlspecialchars($reservation['resource_type']) ?></p><p><b>Location:</b> <?= htmlspecialchars($reservation['location']) ?></p><p><b>Date:</b> <?= htmlspecialchars($reservation['reservation_date']) ?></p><p><b>Time:</b> <?= htmlspecialchars(substr($reservation['start_time'],0,5).'–'.substr($reservation['end_time'],0,5)) ?></p><p><b>Purpose:</b> <?= htmlspecialchars($reservation['purpose']) ?></p><p><b>Status:</b> <span class="status <?= strtolower($reservation['status']) ?>"><?= htmlspecialchars($reservation['status']) ?></span></p><p><b>Admin note:</b> <?= nl2br(htmlspecialchars($reservation['admin_note'] ?? 'No note')) ?></p></div><div class="panel"><h2>Activity History</h2><?php if (!$activity): ?><p class="muted">No activity recorded.</p><?php else: ?><ul class="activity-list"><?php foreach ($activity as $entry): ?><li><strong><?= htmlspecialchars($entry['action']) ?></strong> — <?= htmlspecialchars($entry['full_name']) ?><br><span class="muted"><?= htmlspecialchars($entry['details'] ?? '') ?> · <?= htmlspecialchars($entry['created_at']) ?></span></li><?php endforeach; ?></ul><?php endif; ?></div>
<?php if ($user['role']==='admin' && $reservation['status']==='Pending'): ?><div class="panel"><h2>Review Request</h2><form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="review"><input type="hidden" name="id" value="<?= (int)$reservation['id'] ?>"><label>Decision</label><select name="status"><option>Approved</option><option>Rejected</option></select><label>Admin note</label><textarea name="admin_note" rows="4" placeholder="Optional reason or instructions"></textarea><button class="btn primary full">Save Decision</button></form></div><?php endif; ?></section>
<?php include __DIR__ . '/includes/footer.php'; exit;
}
if ($user['role']==='admin') {
    $stmt = $pdo->query("SELECT r.*,u.full_name,res.resource_name FROM reservations r JOIN users u ON u.id=r.user_id JOIN resources res ON res.id=r.resource_id ORDER BY r.reservation_date DESC,r.start_time DESC");
} else {
    $stmt = $pdo->prepare("SELECT r.*,u.full_name,res.resource_name FROM reservations r JOIN users u ON u.id=r.user_id JOIN resources res ON res.id=r.resource_id WHERE r.user_id=? ORDER BY r.reservation_date DESC,r.start_time DESC");
    $stmt->execute([$user['id']]);
}
$reservations = $stmt->fetchAll();
$pageTitle = 'Reservations';
include __DIR__ . '/includes/header.php';
?>
<div class="hero"><div><div class="eyebrow">BOOKING MANAGEMENT</div><h1>Reservations</h1><p><?= $user['role']==='admin' ? 'Review all requests and manage approval status.' : 'Track your submitted requests and their status.' ?></p></div><a class="btn primary" href="reservations.php?action=new">+ New Reservation</a></div>
<?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Code</th><?php if($user['role']==='admin'): ?><th>Requester</th><?php endif; ?><th>Resource</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach($reservations as $r): ?><tr><td><a href="reservations.php?id=<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['reservation_code']) ?></a></td><?php if($user['role']==='admin'): ?><td><?= htmlspecialchars($r['full_name']) ?></td><?php endif; ?><td><?= htmlspecialchars($r['resource_name']) ?></td><td><?= htmlspecialchars($r['reservation_date']) ?></td><td><?= htmlspecialchars(substr($r['start_time'],0,5).'–'.substr($r['end_time'],0,5)) ?></td><td><?= htmlspecialchars($r['purpose']) ?></td><td><span class="status <?= strtolower($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td><td><?php if($user['role']==='staff' && $r['status']==='Pending'): ?><form method="post" class="inline"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn small">Cancel</button></form><?php else: ?><a href="reservations.php?id=<?= (int)$r['id'] ?>">Details</a><?php endif; ?></td></tr><?php endforeach; ?>
<?php if(!$reservations): ?><tr><td colspan="8" class="muted">No reservation records found.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
