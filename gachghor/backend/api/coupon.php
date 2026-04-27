<?php
// ============================================================
// GachGhor — Coupon API (AJAX endpoint)
// File: backend/api/coupon.php
// Validates coupon code and stores in session
// Returns: JSON with discount and updated totals
// ============================================================
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$db     = getDB();
$userId = $_SESSION['user_id'];
$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$code   = strtoupper(trim($data['code'] ?? ''));

if (!$code) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
    exit;
}

// Find valid coupon
$stmt = $db->prepare("
    SELECT * FROM coupons
    WHERE code = ? AND is_active = 1 AND expiry_date >= CURDATE() AND used_count < max_uses
");
$stmt->execute([$code]);
$coupon = $stmt->fetch();

if (!$coupon) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code.']);
    exit;
}

// Calculate cart subtotal
$cartStmt = $db->prepare("
    SELECT SUM(COALESCE(p.sale_price, p.price) * c.quantity) as subtotal
    FROM cart c JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$cartStmt->execute([$userId]);
$subtotal = (float)($cartStmt->fetchColumn() ?? 0);

// Check minimum order
if ($subtotal < $coupon['min_order']) {
    echo json_encode([
        'success' => false,
        'message' => 'Minimum order of ' . formatPrice($coupon['min_order']) . ' required for this coupon.'
    ]);
    exit;
}

// Calculate discount
$discount = $coupon['type'] === 'percentage'
    ? round($subtotal * $coupon['discount'] / 100, 2)
    : min($coupon['discount'], $subtotal);

$shipping = SHIPPING_CHARGE;
$total    = max(0, $subtotal - $discount + $shipping);

// Save coupon in session
$_SESSION['coupon_code'] = $code;

echo json_encode([
    'success'        => true,
    'discount_label' => formatPrice($discount),
    'totals' => [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'total'    => $total,
    ]
]);
