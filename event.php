<?php
require_once 'includes/init.php';
expirePendingBookings($pdo);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
$stmt->execute([$id]);
$ev = $stmt->fetch();
if (!$ev) { setFlash('danger', 'Event not found.'); header('Location: index.php'); exit; }

$pageTitle = $ev['title'];
$maxQty = min(10, (int)$ev['available_seats']); // max 10 tickets per booking
require 'includes/header.php';
?>
<div class="two-col">
  <div>
    <?= renderBanner($ev, 'banner-lg') ?>
    <span class="badge"><?= e($ev['category']) ?></span>
    <h2 style="margin-top:8px"><?= e($ev['title']) ?></h2>
    <p class="info-line">📅 <strong><?= e(fmtDate($ev['event_date'])) ?></strong></p>
    <p>📍 <strong><?= e($ev['venue']) ?></strong></p>
    <h4>About this event</h4>
    <p><?= nl2br(e($ev['description'])) ?></p>
  </div>

  <div class="card sticky">
    <div class="card-body">
      <h3 class="price" style="margin-bottom:2px"><?= e(fmtPrice($ev['price'])) ?> <small class="muted small">per ticket</small></h3>
      <p class="muted"><?= (int)$ev['available_seats'] ?> of <?= (int)$ev['total_seats'] ?> seats left</p>

      <?php if ($ev['available_seats'] < 1): ?>
        <div class="alert alert-danger">Sold out</div>
      <?php elseif (!isLoggedIn()): ?>
        <a href="login.php?redirect=<?= urlencode('event.php?id=' . $id) ?>" class="btn btn-primary btn-block">Login to book</a>
      <?php elseif (isAdmin()): ?>
        <div class="alert alert-info">Admins cannot book tickets.</div>
      <?php elseif (strtotime($ev['event_date']) < time()): ?>
        <div class="alert alert-secondary">This event has ended.</div>
      <?php else: ?>
        <form method="post" action="book.php">
          <input type="hidden" name="event_id" value="<?= $id ?>">
          <div class="form-group">
            <label>Number of tickets</label>
            <input type="number" name="quantity" id="quantity" min="1" max="<?= $maxQty ?>" value="1"
                   data-price="<?= e($ev['price']) ?>" required>
          </div>
          <div class="flex-between mb"><span>Total</span><strong id="totalAmount"></strong></div>
          <button class="btn btn-success btn-block">Book now</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require 'includes/footer.php'; ?>
