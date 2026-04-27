<?php
// ============================================================
// GachGhor — Checkout Page
// File: frontend/checkout.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
requireLogin();
$db = getDB();

// Load cart
$stmt = $db->prepare("
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name,
           p.price, p.sale_price, p.image, p.stock
    FROM cart c JOIN products p ON c.product_id=p.id
    WHERE c.user_id=?
");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();

if (empty($items)) {
    setFlash('error', 'Your cart is empty. Add products before checking out.');
    redirect(SITE_URL . '/frontend/cart.php');
}

// Calculate totals
$subtotal = 0;
foreach($items as $item) {
    $subtotal += ($item['sale_price'] ?: $item['price']) * $item['quantity'];
}

// Coupon
$discount = 0;
$couponCode = $_SESSION['coupon_code'] ?? null;
if ($couponCode) {
    $cp = $db->prepare("SELECT * FROM coupons WHERE code=? AND is_active=1 AND expiry_date >= CURDATE()");
    $cp->execute([$couponCode]);
    $coupon = $cp->fetch();
    if ($coupon) {
        $discount = $coupon['type'] === 'percentage'
            ? round($subtotal * $coupon['discount'] / 100, 2)
            : min($coupon['discount'], $subtotal);
    }
}

$shipping = SHIPPING_CHARGE;
$total    = $subtotal - $discount + $shipping;

// Prefill user data
$user = $db->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipName    = trim($_POST['ship_name'] ?? '');
    $shipPhone   = trim($_POST['ship_phone'] ?? '');
    $shipAddress = trim($_POST['ship_address'] ?? '');
    $shipCity    = trim($_POST['ship_city'] ?? '');
    $payMethod   = in_array($_POST['payment_method'] ?? '', ['cod','online']) ? $_POST['payment_method'] : 'cod';
    $notes       = trim($_POST['notes'] ?? '');

    $orderErrors = [];
    if (!$shipName)    $orderErrors[] = 'Name is required.';
    if (!$shipPhone)   $orderErrors[] = 'Phone is required.';
    if (!$shipAddress) $orderErrors[] = 'Address is required.';
    if (!$shipCity)    $orderErrors[] = 'City is required.';

    if (empty($orderErrors)) {
        try {
            $db->beginTransaction();

            $orderNum = generateOrderNumber();

            // Create order
            $db->prepare("
                INSERT INTO orders (user_id, order_number, subtotal, discount, shipping, total_price,
                    coupon_code, payment_method, status, shipping_name, shipping_phone,
                    shipping_address, shipping_city, notes)
                VALUES (?,?,?,?,?,?,?,?,'pending',?,?,?,?,?)
            ")->execute([
                $_SESSION['user_id'], $orderNum, $subtotal, $discount,
                $shipping, $total, $couponCode, $payMethod,
                $shipName, $shipPhone, $shipAddress, $shipCity, $notes
            ]);

            $orderId = $db->lastInsertId();

            // Insert order items + reduce stock
            foreach ($items as $item) {
                $price = $item['sale_price'] ?: $item['price'];
                $db->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, quantity, price)
                    VALUES (?,?,?,?,?)
                ")->execute([$orderId, $item['product_id'], $item['name'], $item['quantity'], $price]);

                $db->prepare("UPDATE products SET stock = stock - ? WHERE id=? AND stock >= ?")
                   ->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            }

            // Update coupon usage
            if ($couponCode) {
                $db->prepare("UPDATE coupons SET used_count = used_count+1 WHERE code=?")->execute([$couponCode]);
                unset($_SESSION['coupon_code']);
            }

            // Clear cart
            $db->prepare("DELETE FROM cart WHERE user_id=?")->execute([$_SESSION['user_id']]);

            $db->commit();

            setFlash('success', "🎉 Order placed successfully! Your order number is <strong>$orderNum</strong>");
            redirect(SITE_URL . '/frontend/order-success.php?id=' . $orderId);

        } catch (Exception $e) {
            $db->rollBack();
            $orderErrors[] = 'Order failed. Please try again.';
        }
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="gg-page-banner">
    <div class="container">
        <h1><i class="bi bi-credit-card me-2"></i>Checkout</h1>
    </div>
</div>

<div class="container my-4">
    <?php if(!empty($orderErrors)): ?>
    <div class="alert alert-danger gg-alert">
        <?php foreach($orderErrors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Progress steps -->
    <div class="row g-2 mb-4">
        <div class="col-4"><div class="checkout-step"><div class="checkout-step-num">1</div><span>Cart</span></div></div>
        <div class="col-4"><div class="checkout-step active"><div class="checkout-step-num">2</div><span>Checkout</span></div></div>
        <div class="col-4"><div class="checkout-step"><div class="checkout-step-num">3</div><span>Confirmation</span></div></div>
    </div>

    <form method="POST" class="needs-validation" novalidate>
    <div class="row g-4">

        <!-- ===== SHIPPING FORM ===== -->
        <div class="col-lg-7">
            <div class="gg-card p-4 mb-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-geo-alt me-2 text-green"></i>Delivery Address</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="ship_name" class="form-control gg-form-control"
                               value="<?= h($_POST['ship_name'] ?? $user['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" name="ship_phone" class="form-control gg-form-control"
                               value="<?= h($_POST['ship_phone'] ?? $user['phone'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Street Address *</label>
                        <textarea name="ship_address" class="form-control gg-form-control" rows="2" required
                                  placeholder="House no., Road, Area..."><?= h($_POST['ship_address'] ?? $user['address'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">City *</label>
                        <input type="text" name="ship_city" class="form-control gg-form-control"
                               value="<?= h($_POST['ship_city'] ?? $user['city'] ?? '') ?>"
                               placeholder="e.g. Dhaka, Chittagong" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Order Notes <small class="text-muted">(optional)</small></label>
                        <textarea name="notes" class="form-control gg-form-control" rows="2"
                                  placeholder="Any special instructions..."><?= h($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment method -->
            <div class="gg-card p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-wallet2 me-2 text-green"></i>Payment Method</h5>
                <div class="d-flex flex-column gap-3">
                    <label class="d-flex align-items-center gap-3 p-3 rounded border cursor-pointer" style="cursor:pointer;border-color:var(--gg-border)!important;">
                        <input type="radio" name="payment_method" value="cod" class="form-check-input" checked>
                        <div>
                            <div class="fw-bold">💵 Cash on Delivery</div>
                            <small class="text-muted">Pay when your order arrives. Available nationwide.</small>
                        </div>
                    </label>
                    <label class="d-flex align-items-center gap-3 p-3 rounded border" style="cursor:pointer;border-color:var(--gg-border)!important;">
                        <input type="radio" name="payment_method" value="online" class="form-check-input">
                        <div>
                            <div class="fw-bold">💳 Online Payment</div>
                            <small class="text-muted">Pay via bKash, Nagad, card or mobile banking.</small>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- ===== ORDER SUMMARY ===== -->
        <div class="col-lg-5">
            <div class="gg-card p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-receipt me-2 text-green"></i>Order Summary</h5>

                <!-- Items -->
                <?php foreach($items as $item): ?>
                <?php $price = $item['sale_price'] ?: $item['price']; ?>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= SITE_URL ?>/assets/images/products/<?= h($item['image'] ?: 'placeholder.jpg') ?>"
                         style="width:50px;height:50px;object-fit:cover;border-radius:8px;background:var(--gg-green-pale)"
                         onerror="this.src='<?= SITE_URL ?>/assets/images/plant-placeholder.svg'"
                         alt="<?= h($item['name']) ?>">
                    <div class="flex-grow-1">
                        <div class="small fw-bold"><?= h($item['name']) ?></div>
                        <div class="small text-muted">Qty: <?= $item['quantity'] ?></div>
                    </div>
                    <div class="fw-bold"><?= formatPrice($price * $item['quantity']) ?></div>
                </div>
                <?php endforeach; ?>

                <hr>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
                <?php if($discount > 0): ?>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Discount (<?= h($couponCode) ?>)</span><span class="text-danger">-<?= formatPrice($discount) ?></span></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-3"><span class="text-muted">Shipping</span><span><?= formatPrice($shipping) ?></span></div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                    <span class="fw-bold fs-5">Total</span>
                    <span class="fw-bold fs-5 text-green"><?= formatPrice($total) ?></span>
                </div>

                <button type="submit" class="btn gg-btn-green w-100 py-3 fw-bold fs-5">
                    <i class="bi bi-bag-check me-2"></i>Place Order — <?= formatPrice($total) ?>
                </button>
                <p class="text-center small text-muted mt-2">
                    <i class="bi bi-shield-lock me-1"></i>Your order is secure and protected
                </p>
            </div>
        </div>
    </div>
    </form>
</div>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
