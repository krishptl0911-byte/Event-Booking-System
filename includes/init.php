<?php
// Included at the top of every page: session + DB + helper functions
session_start();
require_once __DIR__ . '/../config/db.php';

// Dynamic BASE_URL: auto-detects folder name so it works in both XAMPP (e.g. /event-booking)
// and PHP built-in server (e.g. php -S localhost:8000).
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = preg_replace('#/admin$#', '', $scriptDir);
    define('BASE_URL', rtrim($base, '/'));
}

// Escape output to prevent XSS (script injection)
function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user']['role'] === 'admin';
}

// Redirect to login if user is not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// Only admins may continue
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// One-time messages (flash) shown after redirects
function setFlash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// Event picture. If the admin gave an image URL we show it,
// otherwise we draw a coloured box with an emoji (works fully offline).
function renderBanner($ev, $extraClass = '') {
    $cls = trim('banner ' . $extraClass);
    if (!empty($ev['image_url'])) {
        return '<img src="' . e($ev['image_url']) . '" class="' . $cls . '" alt="' . e($ev['title']) . '">';
    }
    $icons = ['Concert' => '🎤', 'Workshop' => '🛠️', 'Seminar' => '🎓', 'Sports' => '🏏', 'College Fest' => '🎉'];
    $icon = $icons[$ev['category']] ?? '🎟️';
    $hue = crc32($ev['category']) % 360;   // same category = same colour
    $style = 'background:linear-gradient(135deg,hsl(' . $hue . ',65%,45%),hsl(' . (($hue + 50) % 360) . ',70%,35%))';
    return '<div class="' . $cls . '" style="' . $style . '">' . $icon . '</div>';
}

// Nicely formatted date, e.g. 05 Dec 2026, 07:00 PM
function fmtDate($d) {
    return date('d M Y, h:i A', strtotime($d));
}

// Price display: 0 becomes "Free"
function fmtPrice($p) {
    return $p > 0 ? '₹' . number_format($p, 2) : 'Free';
}

// Pending bookings not paid within 10 minutes are cancelled and seats returned.
// This is how a "seat lock with expiry" works in a small project.
function expirePendingBookings($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, event_id, quantity FROM bookings
                             WHERE status='pending' AND created_at < (NOW() - INTERVAL 10 MINUTE)");
        foreach ($stmt->fetchAll() as $b) {
            $pdo->beginTransaction();
            $up = $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND status='pending'");
            $up->execute([$b['id']]);
            // Only restore seats if the booking was actually pending and cancelled by this query
            if ($up->rowCount() > 0) {
                $pdo->prepare("UPDATE events SET available_seats = available_seats + ? WHERE id=?")
                    ->execute([$b['quantity'], $b['event_id']]);
            }
            $pdo->commit();
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
}
