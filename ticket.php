<?php
require_once 'includes/init.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);

// Owner or admin can view the ticket
$sql = "SELECT b.*, e.title, e.venue, e.event_date, u.name AS user_name, p.method
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        JOIN users u ON b.user_id = u.id
        LEFT JOIN payments p ON p.booking_id = b.id
        WHERE b.id = ? AND b.status = 'confirmed'";
$params = [$id];
if (!isAdmin()) { $sql .= ' AND b.user_id = ?'; $params[] = $_SESSION['user']['id']; }
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$t = $stmt->fetch();
if (!$t) { setFlash('danger', 'Ticket not found.'); header('Location: index.php'); exit; }

$pageTitle = 'Ticket ' . $t['booking_code'];
require 'includes/header.php';
?>
<div class="ticket">
  <div class="ticket-head">
    <div class="flex-between">
      <small>E-TICKET</small>
      <span class="badge badge-<?= $t['checked_in'] ? 'secondary' : 'success' ?>">
        <?= $t['checked_in'] ? 'Checked In' : 'Valid Ticket' ?>
      </span>
    </div>
    <h3><?= e($t['title']) ?></h3>
  </div>
  <div class="ticket-body">
    <div><small class="muted">Attendee</small><div><strong><?= e($t['user_name']) ?></strong></div></div>
    <div><small class="muted">Tickets</small><div><strong><?= (int)$t['quantity'] ?></strong></div></div>
    <div><small class="muted">Date &amp; time</small><div><strong><?= e(fmtDate($t['event_date'])) ?></strong></div></div>
    <div><small class="muted">Venue</small><div><strong><?= e($t['venue']) ?></strong></div></div>
    <div><small class="muted">Amount paid</small><div><strong>₹<?= number_format($t['total_amount'], 2) ?></strong> (<?= e($t['method'] ?? '-') ?>)</div></div>
    <div><small class="muted">Gate Status</small><div>
      <?= $t['checked_in'] ? '<strong style="color:#b91c1c">Admitted (' . e(date('d M, h:i A', strtotime($t['checked_in_at']))) . ')</strong>' : '<strong style="color:#15803d">Ready for Entry</strong>' ?>
    </div></div>
  </div>
  <div class="ticket-qr">
    <canvas id="qrcode" data-code="<?= e($t['booking_code']) ?>"></canvas>
    <div class="booking-code"><?= e($t['booking_code']) ?></div>
    <small class="muted">Show this QR code at the entry gate</small>
  </div>
</div>
<div class="text-center mt no-print">
  <button class="btn btn-outline" onclick="window.print()">🖨️ Print ticket</button>
  <a href="my_bookings.php" class="btn btn-primary">My bookings</a>
  <?php if (isAdmin()): ?>
    <a href="admin/verify.php?code=<?= urlencode($t['booking_code']) ?>" class="btn btn-warning">Gate Verification</a>
  <?php endif; ?>
</div>
<script src="<?= BASE_URL ?>/assets/js/qr.js"></script>
<?php require 'includes/footer.php'; ?>
