<?php
require_once '../includes/init.php';
requireAdmin();
$pageTitle = 'Admin Panel';

// Dashboard numbers (aggregate SQL functions)
$totalEvents = $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn();
$totalCheckedIn = $pdo->query("SELECT COUNT(*) FROM bookings WHERE checked_in=1")->fetchColumn();
$revenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='success'")->fetchColumn();

$events = $pdo->query('SELECT * FROM events ORDER BY event_date ASC')->fetchAll();
$bookings = $pdo->query("SELECT b.booking_code, b.quantity, b.total_amount, b.status, b.created_at, b.checked_in,
                                u.name AS user_name, u.email, e.title
                         FROM bookings b
                         JOIN users u ON b.user_id = u.id
                         JOIN events e ON b.event_id = e.id
                         ORDER BY b.created_at DESC")->fetchAll();
$badge = ['pending' => 'warning', 'confirmed' => 'success', 'cancelled' => 'secondary'];
require '../includes/header.php';
?>
<div class="flex-between mb">
  <h3 style="margin:0">Admin Dashboard</h3>
  <div>
    <a href="verify.php" class="btn btn-warning">🔍 Gate Ticket Verification</a>
    <a href="event_form.php" class="btn btn-success">+ Add Event</a>
  </div>
</div>

<div class="stats">
  <div class="stat"><small class="muted">Total Events</small><h2><?= (int)$totalEvents ?></h2></div>
  <div class="stat"><small class="muted">Confirmed Bookings</small><h2><?= (int)$totalBookings ?></h2></div>
  <div class="stat"><small class="muted">Admitted / Checked In</small><h2><?= (int)$totalCheckedIn ?></h2></div>
  <div class="stat"><small class="muted">Total Revenue</small><h2>₹<?= number_format($revenue, 0) ?></h2></div>
</div>

<div class="flex-between mb">
  <h4 style="margin:0">Manage events</h4>
  <a href="event_form.php" class="btn btn-sm btn-success">+ Add event</a>
</div>
<div class="table-wrap mb-lg">
<table>
  <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Date</th><th>Price</th><th>Seats left</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($events as $ev): ?>
    <tr>
      <td><?= (int)$ev['id'] ?></td>
      <td><?= e($ev['title']) ?></td>
      <td><?= e($ev['category']) ?></td>
      <td><?= e(fmtDate($ev['event_date'])) ?></td>
      <td><?= e(fmtPrice($ev['price'])) ?></td>
      <td><?= (int)$ev['available_seats'] ?> / <?= (int)$ev['total_seats'] ?></td>
      <td class="nowrap">
        <a href="event_form.php?id=<?= (int)$ev['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
        <form method="post" action="event_delete.php" class="inline" data-confirm="Delete this event permanently?">
          <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
          <button class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<h4>All bookings</h4>
<div class="table-wrap">
<table>
  <thead><tr><th>Code</th><th>User</th><th>Event</th><th>Qty</th><th>Amount</th><th>Status</th><th>Check-in</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($bookings as $b): ?>
    <tr>
      <td><code><?= e($b['booking_code']) ?></code></td>
      <td><?= e($b['user_name']) ?><br><small class="muted"><?= e($b['email']) ?></small></td>
      <td><?= e($b['title']) ?></td>
      <td><?= (int)$b['quantity'] ?></td>
      <td>₹<?= number_format($b['total_amount'], 2) ?></td>
      <td><span class="badge badge-<?= $badge[$b['status']] ?>"><?= e(ucfirst($b['status'])) ?></span></td>
      <td>
        <?php if ($b['status'] === 'confirmed'): ?>
          <span class="badge badge-<?= $b['checked_in'] ? 'secondary' : 'success' ?>">
            <?= $b['checked_in'] ? 'Checked In' : 'Not Admitted' ?>
          </span>
        <?php else: ?>
          <span class="muted small">-</span>
        <?php endif; ?>
      </td>
      <td class="nowrap">
        <a href="verify.php?code=<?= urlencode($b['booking_code']) ?>" class="btn btn-sm btn-outline">Verify</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php require '../includes/footer.php'; ?>
