<?php
// ============================================================
// GachGhor — Cart API (AJAX endpoint)
// File: backend/api/cart.php
// Handles: add, update, remove cart items
// Returns: JSON
// ============================================================
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Must be logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to manage your cart.']);
    exit;
}

$db     = getDB();
$userId = $_SESSION['user_id'];

// Read JSON body
$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';

// ---- Helper: compute totals ----
function cartTotals(PDO $db, int $userId): array {
    $stmt = $db->prepare("
        SELECT SUM((COALESCE(p.sale_price, p.price)) * c.quantity) as subtotal
        FROM cart c JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $subtotal = (float)($stmt->fetchColumn() ?? 0);

    // Coupon discount
    $discount = 0;
    if (isset($_SESSION['coupon_code'])) {
        $cp = $db->prepare("SELECT * FROM coupons WHERE code=? AND is_active=1 AND expiry_date >= CURDATE()");
        $cp->execute([$_SESSION['coupon_code']]);
        $coupon = $cp->fetch();
        if ($coupon) {
            $discount = $coupon['type'] === 'percentage'
                ? round($subtotal * $coupon['discount'] / 100, 2)
                : min($coupon['discount'], $subtotal);
        }
    }

    $shipping = $subtotal > 0 ? SHIPPING_CHARGE : 0;
    return [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'total'    => max(0, $subtotal - $discount + $shipping),
    ];
}

// ---- Helper: cart item count ----
function cartCount(PDO $db, int $userId): int {
    $stmt = $db->prepare("SELECT SUM(quantity) FROM cart WHERE user_id=?");
    $stmt->execute([$userId]);
    return (int)($stmt->fetchColumn() ?? 0);
}

switch ($action) {

    // ======== ADD TO CART ========
    case 'add':
        $productId = (int)($data['product_id'] ?? 0);
        $quantity  = max(1, (int)($data['quantity'] ?? 1));

        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'Invalid product.']);
            exit;
        }

        // Check product exists and has stock
        $product = $db->prepare("SELECT id, stock FROM products WHERE id=? AND is_active=1");
        $product->execute([$productId]);
        $product = $product->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }

        // Check existing cart quantity
        $existing = $db->prepare("SELECT quantity FROM cart WHERE user_id=? AND product_id=?");
        $existing->execute([$userId, $productId]);
        $existingQty = (int)($existing->fetchColumn() ?? 0);

        if ($existingQty + $quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
            exit;
        }

        // Insert or update
        $db->prepare("
            INSERT INTO cart (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ")->execute([$userId, $productId, $quantity]);

        echo json_encode([
            'success'    => true,
            'cart_count' => cartCount($db, $userId),
            'totals'     => cartTotals($db, $userId),
        ]);
        break;

    // ======== UPDATE QUANTITY ========
    case 'update':
        $cartId   = (int)($data['cart_id'] ?? 0);
        $quantity = max(1, (int)($data['quantity'] ?? 1));

        // Validate ownership and stock
        $item = $db->prepare("
            SELECT c.id, p.stock FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.id=? AND c.user_id=?
        ");
        $item->execute([$cartId, $userId]);
        $item = $item->fetch();

        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Cart item not found.']);
            exit;
        }

        $quantity = min($quantity, $item['stock']); // cap at stock

        $db->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?")
           ->execute([$quantity, $cartId, $userId]);

        echo json_encode([
            'success'    => true,
            'cart_count' => cartCount($db, $userId),
            'totals'     => cartTotals($db, $userId),
        ]);
        break;

    // ======== REMOVE FROM CART ========
    case 'remove':
        $cartId = (int)($data['cart_id'] ?? 0);

        $db->prepare("DELETE FROM cart WHERE id=? AND user_id=?")
           ->execute([$cartId, $userId]);

        echo json_encode([
            'success'    => true,
            'cart_count' => cartCount($db, $userId),
            'totals'     => cartTotals($db, $userId),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
