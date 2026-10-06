<?php
// Gatekeeper / Admin Ticket Verification and Check-in page
require_once '../includes/init.php';
requireAdmin();
$pageTitle = 'Verify Ticket & Check-in';

$code = strtoupper(trim($_GET['code'] ?? $_POST['code'] ?? ''));
$ticket = null;
$searched = false;

// Handle check-in actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $action = $_POST['action'];

    if ($action === 'checkin' && $bookingId > 0) {
        $pdo->prepare("UPDATE bookings SET checked_in = 1, checked_in_at = NOW() WHERE id = ? AND status = 'confirmed'")
            ->execute([$bookingId]);
        setFlash('success', 'Attendee checked in successfully!');
    } elseif ($action === 'undo' && $bookingId > 0) {
        $pdo->prepare("UPDATE bookings SET checked_in = 0, checked_in_at = NULL WHERE id = ?")
            ->execute([$bookingId]);
        setFlash('info', 'Check-in status has been reset.');
    }
    header('Location: verify.php?code=' . urlencode($code));
    exit;
}

// Fetch ticket details if a booking code is entered
if ($code !== '') {
    $searched = true;
    $stmt = $pdo->prepare("SELECT b.*, e.title, e.venue, e.event_date, u.name AS user_name, u.email AS user_email, p.method, p.amount AS paid_amount
                           FROM bookings b
                           JOIN events e ON b.event_id = e.id
                           JOIN users u ON b.user_id = u.id
                           LEFT JOIN payments p ON p.booking_id = b.id
                           WHERE b.booking_code = ?");
    $stmt->execute([$code]);
    $ticket = $stmt->fetch();
}

// Fetch 5 most recent check-ins for the live gate log
$recentCheckins = $pdo->query("SELECT b.booking_code, b.checked_in_at, b.quantity, u.name, e.title
                              FROM bookings b
                              JOIN users u ON b.user_id = u.id
                              JOIN events e ON b.event_id = e.id
                              WHERE b.checked_in = 1
                              ORDER BY b.checked_in_at DESC LIMIT 5")->fetchAll();

require '../includes/header.php';
?>
<div class="flex-between mb">
  <h3>🔍 Gate Ticket Verification</h3>
  <a href="index.php" class="btn btn-outline">&larr; Back to Dashboard</a>
</div>

<!-- Search Form: Gatekeeper enters or scans the booking code -->
<div class="card mb-lg" style="max-width: 680px; margin: 0 auto 24px;">
  <div class="card-body">
    <p class="muted">Enter the 12-character booking code from the attendee's e-ticket or scan the QR code using a handheld scanner.</p>
    <form method="get" class="search-form" style="justify-content: flex-start;">
      <input type="text" name="code" value="<?= e($code) ?>" placeholder="e.g. EVT-A1B2C3D4"
             maxlength="20" style="flex: 1; text-transform: uppercase;" autofocus required>
      <button class="btn btn-primary">Verify Ticket</button>
      <?php if ($searched): ?>
        <a href="verify.php" class="btn btn-outline">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($searched): ?>
  <div style="max-width: 680px; margin: 0 auto 30px;">
    <?php if (!$ticket): ?>
      <div class="alert alert-danger">
        <strong>❌ INVALID TICKET CODE!</strong><br>
        No booking found with code <code><?= e($code) ?></code>. This ticket does not exist in the database.
      </div>
    <?php elseif ($ticket['status'] === 'cancelled'): ?>
      <div class="alert alert-danger">
        <strong>❌ ENTRY DENIED - CANCELLED BOOKING!</strong><br>
        Booking <code><?= e($code) ?></code> was cancelled and is no longer valid.
      </div>
    <?php elseif ($ticket['status'] === 'pending'): ?>
      <div class="alert alert-warning">
        <strong>⚠️ ENTRY DENIED - PAYMENT PENDING!</strong><br>
        Booking <code><?= e($code) ?></code> has not been paid for yet.
      </div>
    <?php elseif ($ticket['status'] === 'confirmed'): ?>
      <?php if ($ticket['checked_in'] == 1): ?>
        <div class="alert alert-danger">
          <strong>⚠️ ALREADY USED TICKET!</strong><br>
          This ticket was already checked in on <strong><?= e(fmtDate($ticket['checked_in_at'])) ?></strong>.<br>
          <em>Do not admit again if another person is holding a duplicate printout.</em>
        </div>
      <?php else: ?>
        <div class="alert alert-success">
          <strong>✅ VALID TICKET - ENTRY APPROVED!</strong><br>
          Attendee is verified. You may admit <strong><?= (int)$ticket['quantity'] ?> person(s)</strong>.
        </div>
      <?php endif; ?>

      <!-- Attendee & Event Details Card -->
      <div class="card">
        <div class="card-body">
          <div class="flex-between mb">
            <h4>Ticket Details</h4>
            <span class="badge badge-<?= $ticket['checked_in'] ? 'secondary' : 'success' ?>">
              <?= $ticket['checked_in'] ? 'Checked In' : 'Ready for Entry' ?>
            </span>
          </div>
          <table style="width: 100%; margin-bottom: 16px;">
            <tr><td><strong>Booking Code</strong></td><td><code><?= e($ticket['booking_code']) ?></code></td></tr>
            <tr><td><strong>Attendee Name</strong></td><td><?= e($ticket['user_name']) ?></td></tr>
            <tr><td><strong>Attendee Email</strong></td><td><?= e($ticket['user_email']) ?></td></tr>
            <tr><td><strong>Event</strong></td><td><?= e($ticket['title']) ?></td></tr>
            <tr><td><strong>Event Date &amp; Time</strong></td><td><?= e(fmtDate($ticket['event_date'])) ?></td></tr>
            <tr><td><strong>Venue</strong></td><td><?= e($ticket['venue']) ?></td></tr>
            <tr><td><strong>Seats / Quantity</strong></td><td><strong><?= (int)$ticket['quantity'] ?> Ticket(s)</strong></td></tr>
            <tr><td><strong>Amount Paid</strong></td><td>₹<?= number_format($ticket['total_amount'], 2) ?> (<?= e($ticket['method'] ?? 'Free') ?>)</td></tr>
            <?php if ($ticket['checked_in'] && $ticket['checked_in_at']): ?>
              <tr><td><strong>Checked In At</strong></td><td><span style="color:var(--danger); font-weight:600;"><?= e(fmtDate($ticket['checked_in_at'])) ?></span></td></tr>
            <?php endif; ?>
          </table>

          <!-- Gatekeeper Action Buttons -->
          <?php if ($ticket['checked_in'] == 0): ?>
            <form method="post">
              <input type="hidden" name="booking_id" value="<?= (int)$ticket['id'] ?>">
              <input type="hidden" name="code" value="<?= e($ticket['booking_code']) ?>">
              <input type="hidden" name="action" value="checkin">
              <button class="btn btn-success btn-block" style="font-size: 1.1rem; padding: 12px;">
                🎟️ Mark as Checked In &amp; Admit Attendee
              </button>
            </form>
          <?php else: ?>
            <form method="post" data-confirm="Reset check-in status for this ticket?">
              <input type="hidden" name="booking_id" value="<?= (int)$ticket['id'] ?>">
              <input type="hidden" name="code" value="<?= e($ticket['booking_code']) ?>">
              <input type="hidden" name="action" value="undo">
              <button class="btn btn-outline btn-block">Undo Check-In (Admin Reset)</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- Recent Check-ins Log -->
<div style="max-width: 680px; margin: 0 auto;">
  <h4 class="mb">Recent Gate Check-ins</h4>
  <?php if (!$recentCheckins): ?>
    <p class="muted">No attendees checked in yet today.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Code</th><th>Attendee</th><th>Event</th><th>Qty</th><th>Time</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentCheckins as $rc): ?>
            <tr>
              <td><code><?= e($rc['booking_code']) ?></code></td>
              <td><?= e($rc['name']) ?></td>
              <td><?= e($rc['title']) ?></td>
              <td><?= (int)$rc['quantity'] ?></td>
              <td><?= e(date('h:i A', strtotime($rc['checked_in_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>
