<?php
// Deletes an event only if nobody has booked it (foreign key safety)
require_once '../includes/init.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$id = (int)($_POST['id'] ?? 0);
$cnt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE event_id = ?');
$cnt->execute([$id]);

if ($cnt->fetchColumn() > 0) {
    setFlash('danger', 'Cannot delete: this event already has bookings.');
} else {
    $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
    setFlash('success', 'Event deleted.');
}
header('Location: index.php');
exit;
