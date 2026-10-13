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

// Event picture. Curated category photos with title-based overrides.
function renderBanner($ev, $extraClass = '') {
    $cls = trim('banner ' . $extraClass);

    // Curated high quality imagery based on event category or keyword
    $photos = [
        'Concert'      => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&auto=format&fit=crop&q=80',
        'Workshop'     => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=800&auto=format&fit=crop&q=80',
        'Seminar'      => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&auto=format&fit=crop&q=80',
        'Sports'       => 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?w=800&auto=format&fit=crop&q=80',
        'College Fest' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800&auto=format&fit=crop&q=80',
    ];

    // Specific overrides for special titles
    if (stripos($ev['title'], 'comedy') !== false) {
        $photo = 'https://images.unsplash.com/photo-1585699324551-f6c309eedeca?w=800&auto=format&fit=crop&q=80';
    } elseif (stripos($ev['title'], 'photo') !== false) {
        $photo = 'https://images.unsplash.com/photo-1542038784456-1ea8e935640e?w=800&auto=format&fit=crop&q=80';
    } elseif (stripos($ev['title'], 'robot') !== false || stripos($ev['title'], 'ai') !== false) {
        $photo = 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800&auto=format&fit=crop&q=80';
    } else {
        $photo = !empty($ev['image_url']) ? $ev['image_url'] : ($photos[$ev['category']] ?? 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800&auto=format&fit=crop&q=80');
    }

    return '<div class="' . $cls . '"><img src="' . e($photo) . '" alt="' . e($ev['title']) . '" loading="lazy"></div>';
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
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = "SELECT id, event_id, quantity FROM bookings WHERE status='pending' AND created_at < datetime('now', '-10 minutes')";
        } else {
            $sql = "SELECT id, event_id, quantity FROM bookings WHERE status='pending' AND created_at < (NOW() - INTERVAL 10 MINUTE)";
        }
        $stmt = $pdo->query($sql);
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
