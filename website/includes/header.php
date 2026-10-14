<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> | UniBook Cloud</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php">UniBook Cloud</a>
  <nav>
    <?php if ($user): ?>
      <a href="index.php">Dashboard</a>
      <a href="reservations.php">Reservations</a>
      <a href="resources.php">Resources</a>
      <?php if ($user['role'] === 'admin'): ?><a href="users.php">Users</a><?php endif; ?>
      <span class="who"><?= htmlspecialchars($user['full_name']) ?> · <?= htmlspecialchars(ucfirst($user['role'])) ?></span>
      <a href="logout.php">Logout</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container">
