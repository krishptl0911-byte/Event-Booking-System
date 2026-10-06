<?php
// Cancels a booking, returns the seats and marks the payment as refunded
require_once 'includes/init.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: my_bookings.php'); exit; }

$id = (int)($_POST['booking_id'] ?? 0);
$userId = $_SESSION['user']['id'];

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? FOR UPDATE");
    $stmt->execute([$id, $userId]);
    $b = $stmt->fetch();

    if (!$b || $b['status'] === 'cancelled') throw new Exception('Booking cannot be cancelled.');
    if (!empty($b['checked_in'])) throw new Exception('Cannot cancel a ticket that has already been checked in at the event.');

    // Verify the event has not already passed
    $evStmt = $pdo->prepare("SELECT event_date FROM events WHERE id = ?");
    $evStmt->execute([$b['event_id']]);
    $evDate = $evStmt->fetchColumn();
    if ($evDate && strtotime($evDate) < time()) {
        throw new Exception('Cannot cancel booking for an event that has already ended.');
    }

    // Status is updated, row is NOT deleted (we keep history for records)
    $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id = ?")->execute([$id]);
    $pdo->prepare("UPDATE events SET available_seats = available_seats + ? WHERE id = ?")->execute([$b['quantity'], $b['event_id']]);
    if ($b['status'] === 'confirmed') {
        $pdo->prepare("UPDATE payments SET status='refunded' WHERE booking_id = ?")->execute([$id]);
    }
    $pdo->commit();
    setFlash('success', 'Booking cancelled.' . ($b['status'] === 'confirmed' ? ' Refund initiated (demo).' : ''));
} catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    setFlash('danger', $ex->getMessage());
}
header('Location: my_bookings.php');
exit;
