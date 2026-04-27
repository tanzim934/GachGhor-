<?php
// ============================================================
// GachGhor — Wishlist API (AJAX endpoint)
// File: backend/api/wishlist.php
// Handles: toggle (add/remove) wishlist item
// Returns: JSON
// ============================================================
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to manage your wishlist.']);
    exit;
}

$db     = getDB();
$userId = $_SESSION['user_id'];
$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';

if ($action === 'toggle') {
    $productId = (int)($data['product_id'] ?? 0);

    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Invalid product.']);
        exit;
    }

    // Check if already wishlisted
    $check = $db->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
    $check->execute([$userId, $productId]);
    $exists = $check->fetch();

    if ($exists) {
        // Remove
        $db->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?")->execute([$userId, $productId]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Add
        $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?,?)")->execute([$userId, $productId]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
