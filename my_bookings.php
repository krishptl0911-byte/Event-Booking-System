<?php
require_once 'includes/init.php';
requireLogin();
expirePendingBookings($pdo);
$pageTitle = 'My Bookings';

// JOIN: combine bookings with event details
$stmt = $pdo->prepare("SELECT b.*, e.title, e.venue, e.event_date
                       FROM bookings b JOIN events e ON b.event_id = e.id
                       WHERE b.user_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$_SESSION['user']['id']]);
$bookings = $stmt->fetchAll();
$badge = ['pending' => 'warning', 'confirmed' => 'success', 'cancelled' => 'secondary'];
require 'includes/header.php';
?>
<h3>My Bookings</h3>
<?php if (!$bookings): ?>
  <div class="alert alert-info">You have no bookings yet. <a href="index.php">Browse events</a></div>
<?php else: ?>
<div class="table-wrap">
<table>
  <thead><tr>
    <th>Code</th><th>Event</th><th>Date</th><th>Qty</th><th>Amount</th><th>Status</th><th>Actions</th>
  </tr></thead>
  <tbody>
  <?php foreach ($bookings as $b): ?>
    <tr>
      <td><code><?= e($b['booking_code']) ?></code></td>
      <td><?= e($b['title']) ?><br><small class="muted"><?= e($b['venue']) ?></small></td>
      <td><?= e(fmtDate($b['event_date'])) ?></td>
      <td><?= (int)$b['quantity'] ?></td>
      <td>₹<?= number_format($b['total_amount'], 2) ?></td>
      <td>
        <span class="badge badge-<?= $badge[$b['status']] ?>"><?= e(ucfirst($b['status'])) ?></span>
        <?php if (!empty($b['checked_in'])): ?>
          <span class="badge badge-secondary">Attended</span>
        <?php endif; ?>
      </td>
      <td class="nowrap">
        <?php if ($b['status'] === 'confirmed'): ?>
          <a href="ticket.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-primary">Ticket</a>
        <?php elseif ($b['status'] === 'pending'): ?>
          <a href="checkout.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-success">Pay now</a>
        <?php endif; ?>
        <?php if ($b['status'] !== 'cancelled' && empty($b['checked_in']) && strtotime($b['event_date']) > time()): ?>
          <form method="post" action="cancel.php" class="inline" data-confirm="Cancel this booking?">
            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Cancel</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
