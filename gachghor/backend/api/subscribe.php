<?php
// ============================================================
// GachGhor — Subscribe API
// File: backend/api/subscribe.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';

$email = trim($_POST['email'] ?? '');
if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('success', 'Thank you for subscribing! 🌿 We\'ll send you plant tips and offers.');
} else {
    setFlash('error', 'Please enter a valid email address.');
}

// Redirect back to referring page or home
$ref = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/frontend/index.php';
redirect($ref);
