<?php
// One form used for BOTH adding and editing an event
require_once '../includes/init.php';
requireAdmin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$ev = ['title'=>'','description'=>'','category'=>'','venue'=>'','event_date'=>'','price'=>'0','total_seats'=>'','image_url'=>''];
$old = null;

if ($id) { // edit mode: load existing event
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) { setFlash('danger', 'Event not found.'); header('Location: index.php'); exit; }
    $ev = $old;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ev = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'venue' => trim($_POST['venue'] ?? ''),
        'event_date' => str_replace('T', ' ', $_POST['event_date'] ?? ''),
        'price' => $_POST['price'] ?? '',
        'total_seats' => $_POST['total_seats'] ?? '',
        'image_url' => trim($_POST['image_url'] ?? ''),
    ];
    if ($ev['title'] === '') $errors[] = 'Title is required.';
    if ($ev['category'] === '') $errors[] = 'Category is required.';
    if ($ev['venue'] === '') $errors[] = 'Venue is required.';
    if (!strtotime($ev['event_date'])) $errors[] = 'Enter a valid date and time.';
    if (!is_numeric($ev['price']) || $ev['price'] < 0) $errors[] = 'Price must be 0 or more.';
    if (!ctype_digit((string)$ev['total_seats']) || $ev['total_seats'] < 1) $errors[] = 'Total seats must be at least 1.';

    // On edit: keep the booked seats, only change the free ones
    if (!$errors && $old) {
        $booked = $old['total_seats'] - $old['available_seats'];
        if ($ev['total_seats'] < $booked) $errors[] = "Total seats cannot be less than already booked seats ($booked).";
    }

    if (!$errors) {
        if ($old) {
            $available = $ev['total_seats'] - ($old['total_seats'] - $old['available_seats']);
            $pdo->prepare('UPDATE events SET title=?,description=?,category=?,venue=?,event_date=?,price=?,total_seats=?,available_seats=?,image_url=? WHERE id=?')
                ->execute([$ev['title'],$ev['description'],$ev['category'],$ev['venue'],$ev['event_date'],$ev['price'],$ev['total_seats'],$available,$ev['image_url'],$id]);
            setFlash('success', 'Event updated.');
        } else {
            $pdo->prepare('INSERT INTO events (title,description,category,venue,event_date,price,total_seats,available_seats,image_url) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$ev['title'],$ev['description'],$ev['category'],$ev['venue'],$ev['event_date'],$ev['price'],$ev['total_seats'],$ev['total_seats'],$ev['image_url']]);
            setFlash('success', 'Event added.');
        }
        header('Location: index.php'); exit;
    }
}

$pageTitle = $id ? 'Edit event' : 'Add event';
$dateValue = $ev['event_date'] ? date('Y-m-d\TH:i', strtotime($ev['event_date'])) : '';
require '../includes/header.php';
?>
<div class="card form-card-wide">
  <h3><?= $id ? 'Edit event' : 'Add new event' ?></h3>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="form-group"><label>Title</label>
      <input name="title" value="<?= e($ev['title']) ?>" required></div>
    <div class="form-group"><label>Description</label>
      <textarea name="description" rows="4"><?= e($ev['description']) ?></textarea></div>
    <div class="form-row">
      <div class="form-group"><label>Category</label>
        <input name="category" list="cats" value="<?= e($ev['category']) ?>" required>
        <datalist id="cats"><option>Concert</option><option>Workshop</option><option>Seminar</option><option>Sports</option><option>College Fest</option></datalist></div>
      <div class="form-group"><label>Date &amp; time</label>
        <input type="datetime-local" name="event_date" value="<?= e($dateValue) ?>" required></div>
    </div>
    <div class="form-group"><label>Venue</label>
      <input name="venue" value="<?= e($ev['venue']) ?>" required></div>
    <div class="form-row">
      <div class="form-group"><label>Ticket price (₹, 0 = free)</label>
        <input type="number" step="0.01" min="0" name="price" value="<?= e($ev['price']) ?>" required></div>
      <div class="form-group"><label>Total seats</label>
        <input type="number" min="1" name="total_seats" value="<?= e($ev['total_seats']) ?>" required></div>
    </div>
    <div class="form-group"><label>Image URL (optional, leave empty for a coloured banner)</label>
      <input type="url" name="image_url" value="<?= e($ev['image_url']) ?>"></div>
    <button class="btn btn-success"><?= $id ? 'Update' : 'Add' ?> event</button>
    <a href="index.php" class="btn btn-outline">Cancel</a>
  </form>
</div>
<?php require '../includes/footer.php'; ?>
