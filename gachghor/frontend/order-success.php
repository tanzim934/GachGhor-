<?php
// ============================================================
// GachGhor — Order Success Page
// File: frontend/order-success.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
requireLogin();
$db = getDB();

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) { redirect(SITE_URL . '/frontend/index.php'); }

$stmt = $db->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) { redirect(SITE_URL . '/frontend/index.php'); }

$items = $db->prepare("SELECT * FROM order_items WHERE order_id=?");
$items->execute([$orderId]);
$items = $items->fetchAll();

$pageTitle = 'Order Confirmed!';
include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="container my-5">
    <div class="text-center mb-5">
        <div style="font-size:5rem;animation:pop 0.5s ease">🎉</div>
        <h2 class="gg-section-title text-green mt-3">Order Placed Successfully!</h2>
        <p class="text-muted fs-5">Thank you, <?= h($_SESSION['user_name']) ?>! Your plants are on their way 🌿</p>
        <div class="d-inline-block px-4 py-2 rounded-pill" style="background:var(--gg-green-pale);border:2px solid var(--gg-border);">
            <span class="fw-bold text-green fs-5">Order #<?= h($order['order_number']) ?></span>
        </div>
    </div>

    <div class="row justify-content-center g-4">
        <div class="col-md-8">
            <!-- Order Details -->
            <div class="gg-card p-4 mb-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-green"></i>Order Details</h6>
                <?php foreach($items as $item): ?>
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <span class="fw-semibold"><?= h($item['product_name']) ?></span>
                        <span class="text-muted ms-2">× <?= $item['quantity'] ?></span>
                    </div>
                    <span class="fw-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between mt-2">
                    <span class="fw-bold">Total Paid</span>
                    <span class="fw-bold text-green fs-5"><?= formatPrice($order['total_price']) ?></span>
                </div>
            </div>

            <!-- Delivery Info -->
            <div class="gg-card p-4 mb-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2 text-green"></i>Delivery Information</h6>
                <p class="mb-1"><strong>Name:</strong> <?= h($order['shipping_name']) ?></p>
                <p class="mb-1"><strong>Phone:</strong> <?= h($order['shipping_phone']) ?></p>
                <p class="mb-1"><strong>Address:</strong> <?= h($order['shipping_address']) ?></p>
                <p class="mb-1"><strong>City:</strong> <?= h($order['shipping_city']) ?></p>
                <p class="mb-0"><strong>Payment:</strong> <?= $order['payment_method'] === 'cod' ? '💵 Cash on Delivery' : '💳 Online Payment' ?></p>
            </div>

            <!-- Estimated delivery -->
            <div class="gg-card p-4 bg-green-pale mb-4">
                <div class="d-flex align-items-center gap-3">
                    <span style="font-size:2.5rem">🚚</span>
                    <div>
                        <div class="fw-bold">Estimated Delivery</div>
                        <div class="text-muted">
                            <?php
                            $city = strtolower($order['shipping_city']);
                            echo (strpos($city,'dhaka')!==false)
                                ? 'Same day or next day delivery'
                                : '2-3 business days nationwide';
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                <a href="<?= SITE_URL ?>/frontend/orders.php" class="btn gg-btn-outline-green px-4">
                    <i class="bi bi-bag me-2"></i>View All Orders
                </a>
                <a href="<?= SITE_URL ?>/frontend/products.php" class="btn gg-btn-green px-4">
                    <i class="bi bi-cart me-2"></i>Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
