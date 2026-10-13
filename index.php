<?php
require_once 'includes/init.php';
$pageTitle = 'EventHub - Find and book events';
expirePendingBookings($pdo); // free seats of unpaid bookings

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

// Build the query step by step; user input only goes through ? placeholders
$sql = 'SELECT * FROM events WHERE 1=1';
$params = [];
if ($q !== '') { $sql .= ' AND (title LIKE ? OR venue LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($category !== '') { $sql .= ' AND category = ?'; $params[] = $category; }
$sql .= ' ORDER BY event_date ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$categories = $pdo->query('SELECT DISTINCT category FROM events ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
require 'includes/header.php';
?>
<div class="hero">
  <div class="hero-particles"></div>
  <div class="hero-content">
    <div class="hero-pill">
      <span class="hero-pill-dot">🎟️</span>
      Discover &amp; Book Events Near You
    </div>
    <h1>Find Your Next<br><span class="gradient-text">Unforgettable</span> Experience</h1>
    <p>Concerts, workshops, seminars, sports and college fests — all in one beautifully curated place.</p>
    <div class="hero-cta">
      <a href="#events" class="btn btn-primary">Explore Events →</a>
      <?php if (!isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline">Get Started Free</a>
      <?php endif; ?>
    </div>
    <form class="search-form" method="get">
      <input type="text" name="q" placeholder="🔍  Search events or venues..." value="<?= e($q) ?>">
      <select name="category">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= e($c) ?>" <?= $c === $category ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary">Search</button>
    </form>
  </div>
  <div class="hero-scroll">
    <div class="scroll-line"></div>
    <span>scroll</span>
  </div>
</div>

<div class="container" style="padding-top:60px; padding-bottom:80px;">

  <div id="events" class="section-header reveal">
    <h2><?= $q || $category ? 'Search Results' : 'Upcoming Events' ?></h2>
    <p><?= count($events) ?> event<?= count($events) !== 1 ? 's' : '' ?> available<?= $category ? ' in <strong>' . e($category) . '</strong>' : '' ?></p>
  </div>

  <?php if (!$events): ?>
    <div class="alert alert-info">No events found. Try a different search.</div>
  <?php endif; ?>

  <div class="grid">
  <?php foreach ($events as $ev): ?>
    <div class="card event-card">
      <div class="event-card-visual">
        <?= renderBanner($ev) ?>
        <div class="event-card-overlay">
          <span class="badge"><?= e($ev['category']) ?></span>
          <h3><?= e($ev['title']) ?></h3>
          <p class="small">📅 <?= e(fmtDate($ev['event_date'])) ?></p>
          <p class="small">📍 <?= e($ev['venue']) ?></p>
        </div>
      </div>
      <div class="card-footer-row">
        <span class="price"><?= e(fmtPrice($ev['price'])) ?></span>
        <?php if ($ev['available_seats'] > 0): ?>
          <a href="event.php?id=<?= (int)$ev['id'] ?>" class="btn btn-primary btn-sm">View &amp; Book</a>
        <?php else: ?>
          <span class="badge badge-danger">Sold out</span>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  </div>

</div>
<?php require 'includes/footer.php'; ?>
