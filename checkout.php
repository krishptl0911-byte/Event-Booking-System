<?php
// Simulated payment page. A real site would use Razorpay/Stripe here.
require_once 'includes/init.php';
requireLogin();
$userId = $_SESSION['user']['id'];
$id = (int)($_GET['id'] ?? $_POST['booking_id'] ?? 0);

$stmt = $pdo->prepare("SELECT b.*, e.title, e.venue, e.event_date,
                       (600 - TIMESTAMPDIFF(SECOND, b.created_at, NOW())) AS seconds_left
                       FROM bookings b JOIN events e ON b.event_id = e.id
                       WHERE b.id = ? AND b.user_id = ?");
$stmt->execute([$id, $userId]);
$b = $stmt->fetch();

if (!$b) {
    setFlash('danger', 'Booking not found.');
    header('Location: my_bookings.php'); exit;
}

// If already paid, take the user directly to the e-ticket
if ($b['status'] === 'confirmed') {
    header("Location: ticket.php?id=$id"); exit;
}

// If pending but past 10 minutes, expire it and redirect
if ($b['status'] !== 'pending' || $b['seconds_left'] <= 0) {
    expirePendingBookings($pdo);
    setFlash('warning', 'This booking has expired or was cancelled.');
    header('Location: my_bookings.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'] ?? '';
    if (!in_array($method, ['UPI', 'Card', 'Net Banking'], true)) {
        setFlash('danger', 'Choose a valid payment method.');
        header("Location: checkout.php?id=$id"); exit;
    }
    try {
        $pdo->beginTransaction();
        // Lock the booking and re-check status so it cannot be paid twice
        $chk = $pdo->prepare("SELECT status FROM bookings WHERE id = ? FOR UPDATE");
        $chk->execute([$id]);
        if ($chk->fetchColumn() !== 'pending') throw new Exception('Booking is no longer pending.');

        $pdo->prepare("INSERT INTO payments (booking_id,amount,method,status) VALUES (?,?,?,'success')")
            ->execute([$id, $b['total_amount'], $method]);
        $pdo->prepare("UPDATE bookings SET status='confirmed' WHERE id = ?")->execute([$id]);
        $pdo->commit();
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', $ex->getMessage());
        header('Location: my_bookings.php'); exit;
    }
    setFlash('success', 'Payment successful! Your ticket is ready.');
    header("Location: ticket.php?id=$id"); exit;
}

$pageTitle = 'Checkout';
require 'includes/header.php';
?>
<div class="alert alert-warning flex-between timer-bar">
  <span>Your seats are held for</span>
  <span id="timer" data-seconds="<?= (int)$b['seconds_left'] ?>" data-redirect="my_bookings.php"></span>
</div>
<div class="checkout-grid">
  <div class="card"><div class="card-body">
    <h4>Order summary</h4>
    <p><strong><?= e($b['title']) ?></strong></p>
    <p class="muted small">📅 <?= e(fmtDate($b['event_date'])) ?></p>
    <p class="muted small">📍 <?= e($b['venue']) ?></p>
    <hr>
    <div class="flex-between"><span>Tickets</span><span><?= (int)$b['quantity'] ?></span></div>
    <div class="total-row"><span>Total</span><span>₹<?= number_format($b['total_amount'], 2) ?></span></div>
  </div></div>

  <div class="card"><div class="card-body">
    <h4>Payment method <span class="badge badge-info">Demo</span></h4>
    <form method="post">
      <input type="hidden" name="booking_id" value="<?= $id ?>">
      <div class="radio-row"><input type="radio" name="method" id="m1" value="UPI" checked><label for="m1">UPI</label></div>
      <div class="radio-row"><input type="radio" name="method" id="m2" value="Card"><label for="m2">Credit / Debit card</label></div>
      <div class="radio-row"><input type="radio" name="method" id="m3" value="Net Banking"><label for="m3">Net banking</label></div>

      <div class="pay-fields form-group mt" data-method="UPI"><input placeholder="yourname@upi (demo, not saved)"></div>
      <div class="pay-fields form-group mt hidden" data-method="Card">
        <input placeholder="Card number (demo, not saved)" style="margin-bottom:8px">
        <div class="form-row"><input placeholder="MM/YY"><input placeholder="CVV"></div>
      </div>
      <div class="pay-fields form-group mt hidden" data-method="Net Banking">
        <select><option>State Bank</option><option>HDFC Bank</option><option>ICICI Bank</option></select>
      </div>

      <button class="btn btn-success btn-block">Pay ₹<?= number_format($b['total_amount'], 2) ?></button>
    </form>
    <p class="muted small mt">This is a simulated payment. No real money or card data is used.</p>
  </div></div>
</div>
<?php require 'includes/footer.php'; ?>
