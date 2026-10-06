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
  <h1>Discover &amp; book amazing events</h1>
  <p>Concerts, workshops, seminars, sports and college fests in one place.</p>
  <form class="search-form" method="get">
    <input type="text" name="q" placeholder="Search by event or venue" value="<?= e($q) ?>">
    <select name="category">
      <option value="">All categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= e($c) ?>" <?= $c === $category ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Search</button>
  </form>
</div>

<?php if (!$events): ?>
  <div class="alert alert-info">No events found. Try a different search.</div>
<?php endif; ?>

<div class="grid">
<?php foreach ($events as $ev): ?>
  <div class="card event-card">
    <?= renderBanner($ev) ?>
    <div class="card-body">
      <span class="badge"><?= e($ev['category']) ?></span>
      <h3><?= e($ev['title']) ?></h3>
      <p class="muted small">📅 <?= e(fmtDate($ev['event_date'])) ?></p>
      <p class="muted small">📍 <?= e($ev['venue']) ?></p>
      <div class="card-footer-row">
        <span class="price"><?= e(fmtPrice($ev['price'])) ?></span>
        <?php if ($ev['available_seats'] > 0): ?>
          <a href="event.php?id=<?= (int)$ev['id'] ?>" class="btn btn-primary btn-sm">View &amp; Book</a>
        <?php else: ?>
          <span class="badge badge-danger">Sold out</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php require 'includes/footer.php'; ?>
