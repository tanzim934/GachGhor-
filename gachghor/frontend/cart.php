<?php
// ============================================================
// GachGhor — Cart Page
// File: frontend/cart.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
requireLogin();
$db = getDB();

// Fetch cart items
$stmt = $db->prepare("
    SELECT c.id as cart_id, c.quantity, p.id, p.name, p.price, p.sale_price,
           p.image, p.stock, cat.name as cat_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN categories cat ON p.category_id = cat.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $price = $item['sale_price'] ?: $item['price'];
    $subtotal += $price * $item['quantity'];
}

// Coupon from session
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
    } else {
        unset($_SESSION['coupon_code']);
        $couponCode = null;
    }
}

$shipping = empty($items) ? 0 : SHIPPING_CHARGE;
$total    = max(0, $subtotal - $discount + $shipping);

$pageTitle = 'Shopping Cart';
include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="gg-page-banner">
    <div class="container">
        <h1><i class="bi bi-cart3 me-2"></i>Shopping Cart
            <?php if($items): ?>
            <span class="badge bg-white text-success ms-2" style="font-size:1rem"><?= count($items) ?> items</span>
            <?php endif; ?>
        </h1>
    </div>
</div>

<div class="container my-4">
    <?php if(empty($items)): ?>
    <!-- Empty cart -->
    <div class="text-center py-5">
        <div style="font-size:5rem">🛒</div>
        <h4 class="mt-3">Your cart is empty</h4>
        <p class="text-muted">Browse our plants and add something beautiful to your cart!</p>
        <a href="<?= SITE_URL ?>/frontend/products.php" class="btn gg-btn-green mt-2 px-4">
            <i class="bi bi-grid me-2"></i>Browse Plants
        </a>
    </div>
    <?php else: ?>
    <div class="row g-4">

        <!-- ===== CART ITEMS ===== -->
        <div class="col-lg-8">
            <?php foreach($items as $item): ?>
            <?php $price = $item['sale_price'] ?: $item['price']; ?>
            <div class="cart-item d-flex gap-3 align-items-start" id="cart-item-<?= $item['cart_id'] ?>">
                <!-- Image -->
                <a href="<?= SITE_URL ?>/frontend/product.php?id=<?= $item['id'] ?>">
                    <img src="<?= SITE_URL ?>/assets/images/products/<?= h($item['image'] ?: 'placeholder.jpg') ?>"
                         class="cart-item-img"
                         onerror="this.src='<?= SITE_URL ?>/assets/images/plant-placeholder.svg'"
                         alt="<?= h($item['name']) ?>">
                </a>
                <!-- Details -->
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted"><?= h($item['cat_name']) ?></small>
                            <h6 class="mb-1 fw-bold">
                                <a href="<?= SITE_URL ?>/frontend/product.php?id=<?= $item['id'] ?>" class="text-decoration-none text-dark">
                                    <?= h($item['name']) ?>
                                </a>
                            </h6>
                        </div>
                        <button class="btn btn-sm text-danger btn-remove-cart" data-cart-id="<?= $item['cart_id'] ?>" title="Remove">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-2 flex-wrap gap-2">
                        <!-- Quantity -->
                        <div class="quantity-control">
                            <button class="quantity-btn" data-action="decrease" data-cart-id="<?= $item['cart_id'] ?>">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input class="quantity-input" type="number" value="<?= $item['quantity'] ?>"
                                   min="1" max="<?= $item['stock'] ?>" readonly>
                            <button class="quantity-btn" data-action="increase" data-cart-id="<?= $item['cart_id'] ?>">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <!-- Price -->
                        <div class="text-end">
                            <div class="fw-bold text-green fs-5"><?= formatPrice($price * $item['quantity']) ?></div>
                            <small class="text-muted"><?= formatPrice($price) ?> each</small>
                        </div>
                    </div>
                    <?php if($item['stock'] <= 5 && $item['stock'] > 0): ?>
                    <small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Only <?= $item['stock'] ?> left in stock!</small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Continue shopping -->
            <a href="<?= SITE_URL ?>/frontend/products.php" class="btn gg-btn-outline-green mt-3">
                <i class="bi bi-arrow-left me-2"></i>Continue Shopping
            </a>
        </div>

        <!-- ===== ORDER SUMMARY ===== -->
        <div class="col-lg-4">
            <div class="gg-card p-4 position-sticky" style="top: calc(var(--gg-nav-height) + 20px);">
                <h5 class="fw-bold mb-4">Order Summary</h5>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span id="cartSubtotal"><?= formatPrice($subtotal) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Discount</span>
                    <span class="text-danger" id="cartDiscount">-<?= formatPrice($discount) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Shipping</span>
                    <span id="cartShipping"><?= formatPrice($shipping) ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                    <span class="fw-bold fs-5">Total</span>
                    <span class="fw-bold fs-5 text-green" id="cartTotal"><?= formatPrice($total) ?></span>
                </div>

                <!-- Coupon -->
                <form id="couponForm" class="mb-4">
                    <label class="form-label small fw-bold">Coupon Code</label>
                    <div class="input-group">
                        <input type="text" id="couponCode" class="form-control gg-form-control"
                               placeholder="Enter coupon code"
                               value="<?= h($couponCode ?? '') ?>">
                        <button class="btn gg-btn-green" type="submit">Apply</button>
                    </div>
                    <div id="couponMsg" class="mt-1 small">
                        <?php if($couponCode && $discount > 0): ?>
                        <span class="text-success fw-bold">✅ Coupon applied! You saved <?= formatPrice($discount) ?></span>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="appliedCoupon" name="applied_coupon" value="<?= h($couponCode ?? '') ?>">
                </form>

                <a href="<?= SITE_URL ?>/frontend/checkout.php" class="btn gg-btn-green w-100 py-2 fw-bold">
                    <i class="bi bi-credit-card me-2"></i>Proceed to Checkout
                </a>

                <!-- Payment icons -->
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <span class="small text-muted">We accept:</span>
                    <span class="badge bg-light text-dark">Cash on Delivery</span>
                    <span class="badge bg-primary">Online</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
