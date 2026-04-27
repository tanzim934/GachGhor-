<?php
// ============================================================
// GachGhor — Auth API (logout + forgot password)
// File: backend/api/auth.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'logout':
        session_destroy();
        redirect(SITE_URL . '/frontend/login.php');
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
