<?php
// Destroy the session and go home
require_once 'includes/init.php';
$_SESSION = [];
session_destroy();
header('Location: index.php');
exit;
