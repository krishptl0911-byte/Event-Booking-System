<?php
// Creates a booking. This is the CORE LOGIC of the project.
require_once 'includes/init.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || isAdmin()) { header('Location: index.php'); exit; }

$eventId = (int)($_POST['event_id'] ?? 0);
$qty = (int)($_POST['quantity'] ?? 0);
$userId = $_SESSION['user']['id'];

if ($qty < 1 || $qty > 10) {
    setFlash('danger', 'Quantity must be between 1 and 10.');
    header("Location: event.php?id=$eventId"); exit;
}

expirePendingBookings($pdo);

try {
    // TRANSACTION: either everything below happens, or nothing does
    $pdo->beginTransaction();

    // FOR UPDATE locks this event row, so two users cannot take the last seat together
    $stmt = $pdo->prepare('SELECT price, available_seats, event_date FROM events WHERE id = ? FOR UPDATE');
    $stmt->execute([$eventId]);
    $ev = $stmt->fetch();

    if (!$ev) throw new Exception('Event not found.');
    if (strtotime($ev['event_date']) < time()) throw new Exception('This event has ended.');
    if ($ev['available_seats'] < $qty) throw new Exception('Only ' . $ev['available_seats'] . ' seats are left.');

    $total = $qty * $ev['price'];
    $code = 'EVT-' . strtoupper(bin2hex(random_bytes(4))); // e.g. EVT-9F3A1C7B
    $status = $total > 0 ? 'pending' : 'confirmed';        // free events need no payment

    // 1) reduce seats
    $pdo->prepare('UPDATE events SET available_seats = available_seats - ? WHERE id = ?')
        ->execute([$qty, $eventId]);

    // 2) create booking
    $pdo->prepare('INSERT INTO bookings (user_id,event_id,quantity,total_amount,status,booking_code) VALUES (?,?,?,?,?,?)')
        ->execute([$userId, $eventId, $qty, $total, $status, $code]);
    $bookingId = $pdo->lastInsertId();

    // 3) free event: record a zero payment so every confirmed booking has a payment row
    if ($total == 0) {
        $pdo->prepare("INSERT INTO payments (booking_id,amount,method,status) VALUES (?,0,'Free','success')")
            ->execute([$bookingId]);
    }

    $pdo->commit();
} catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack(); // undo everything on error
    setFlash('danger', $ex->getMessage());
    header("Location: event.php?id=$eventId"); exit;
}

if ($status === 'confirmed') {
    setFlash('success', 'Booking confirmed!');
    header("Location: ticket.php?id=$bookingId");
} else {
    header("Location: checkout.php?id=$bookingId");
}
exit;
