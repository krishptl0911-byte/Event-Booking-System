<?php require_once __DIR__ . '/init.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'EventHub') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>body { padding-top: 68px; }</style>
</head>
<body>
<nav class="navbar">
  <div class="container nav-inner">
    <a class="nav-brand" href="<?= BASE_URL ?>/index.php">🎟️ EventHub</a>
    <button class="nav-toggle" id="navToggle" type="button" aria-label="Menu">☰</button>
    <ul class="nav-links" id="navLinks">
      <li><a href="<?= BASE_URL ?>/index.php">Events</a></li>
      <?php if (isLoggedIn()): ?>
        <?php if (isAdmin()): ?>
          <li><a href="<?= BASE_URL ?>/admin/index.php">Admin Panel</a></li>
          <li><a href="<?= BASE_URL ?>/admin/verify.php">Verify Ticket</a></li>
        <?php else: ?>
          <li><a href="<?= BASE_URL ?>/my_bookings.php">My Bookings</a></li>
        <?php endif; ?>
        <li><span class="nav-user">Hi, <?= e($_SESSION['user']['name']) ?></span></li>
        <li><a href="<?= BASE_URL ?>/logout.php">Logout</a></li>
      <?php else: ?>
        <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
        <li><a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/register.php">Register</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>
<main>
<?php if (isset($_SESSION['flash'])): ?>
  <div class="container" style="padding-top: 20px;">
    <div class="alert alert-<?= e($_SESSION['flash']['type']) ?>">
      <?= e($_SESSION['flash']['msg']) ?>
      <button type="button" class="alert-close">&times;</button>
    </div>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
