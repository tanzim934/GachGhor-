<?php
// ============================================================
// GachGhor — My Orders Page
// File: frontend/orders.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
requireLogin();
$db = getDB();

$orders = $db->prepare("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$orders->execute([$_SESSION['user_id']]);
$orders = $orders->fetchAll();

$pageTitle = 'My Orders';
include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="gg-page-banner">
    <div class="container">
        <h1><i class="bi bi-bag me-2"></i>My Orders</h1>
    </div>
</div>

<div class="container my-4">
    <?php if(empty($orders)): ?>
    <div class="text-center py-5">
        <div style="font-size:4rem">📦</div>
        <h5 class="mt-3">No orders yet</h5>
        <p class="text-muted">You haven't placed any orders. Start shopping!</p>
        <a href="<?= SITE_URL ?>/frontend/products.php" class="btn gg-btn-green mt-2">Browse Plants</a>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach($orders as $order): ?>
        <div class="col-12">
            <div class="gg-card p-4">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Order Number</small>
                        <strong class="text-green"><?= h($order['order_number']) ?></strong>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted d-block">Date</small>
                        <?= date('d M Y', strtotime($order['created_at'])) ?>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted d-block">Items</small>
                        <?= $order['item_count'] ?> item(s)
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted d-block">Total</small>
                        <strong><?= formatPrice($order['total_price']) ?></strong>
                    </div>
                    <div class="col-md-2">
                        <span class="badge px-3 py-2 status-<?= $order['status'] ?> rounded-pill">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-sm gg-btn-outline-green"
                                data-bs-toggle="collapse"
                                data-bs-target="#order-<?= $order['id'] ?>">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Collapsible details -->
                <div class="collapse mt-3" id="order-<?= $order['id'] ?>">
                    <hr>
                    <?php
                    $items = $db->prepare("SELECT * FROM order_items WHERE order_id=?");
                    $items->execute([$order['id']]);
                    $items = $items->fetchAll();
                    ?>
                    <?php foreach($items as $item): ?>
                    <div class="d-flex justify-content-between small mb-2">
                        <span><?= h($item['product_name']) ?> × <?= $item['quantity'] ?></span>
                        <span class="fw-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <hr>
                    <div class="d-flex justify-content-between small">
                        <span>Shipping to: <?= h($order['shipping_city']) ?></span>
                        <span>Payment: <?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online' ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
