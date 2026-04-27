<?php
// ============================================================
// GachGhor — Admin: Order Detail + Invoice
// File: backend/admin/order-detail.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$order = $db->prepare("SELECT o.*, u.name as cname, u.email as cemail, u.phone as cphone FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=?");
$order->execute([$id]);
$order = $order->fetch();
if (!$order) { setFlash('error','Order not found.'); redirect(SITE_URL.'/backend/admin/orders.php'); }

$items = $db->prepare("SELECT oi.*, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
$items->execute([$id]);
$items = $items->fetchAll();

$pageTitle = 'Order ' . $order['order_number'];
include __DIR__ . '/admin-header.php';
?>

<div class="container-fluid py-4" style="max-width:900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?= SITE_URL ?>/backend/admin/orders.php" class="btn btn-outline-secondary btn-sm me-2">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            <span class="fw-bold fs-5">Order: <span class="text-green"><?= h($order['order_number']) ?></span></span>
        </div>
        <button onclick="window.print()" class="btn gg-btn-outline-green btn-sm">
            <i class="bi bi-printer me-1"></i>Print Invoice
        </button>
    </div>

    <div class="row g-4">
        <!-- Invoice Card -->
        <div class="col-md-8">
            <div class="gg-card p-4" id="invoiceArea">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <div class="gg-logo fs-4">🌿 GachGhor গাছঘর</div>
                        <small class="text-muted">gachghor.com | hello@gachghor.com</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold fs-5">INVOICE</div>
                        <div class="text-muted small"><?= h($order['order_number']) ?></div>
                        <div class="text-muted small"><?= date('d M Y', strtotime($order['created_at'])) ?></div>
                    </div>
                </div>

                <!-- Customer -->
                <div class="row mb-4">
                    <div class="col-6">
                        <h6 class="fw-bold text-muted text-uppercase" style="font-size:0.7rem">Bill To</h6>
                        <div class="fw-bold"><?= h($order['cname']) ?></div>
                        <div class="small text-muted"><?= h($order['cemail']) ?></div>
                        <div class="small text-muted"><?= h($order['cphone']) ?></div>
                    </div>
                    <div class="col-6">
                        <h6 class="fw-bold text-muted text-uppercase" style="font-size:0.7rem">Ship To</h6>
                        <div class="fw-bold"><?= h($order['shipping_name']) ?></div>
                        <div class="small text-muted"><?= h($order['shipping_address']) ?></div>
                        <div class="small text-muted"><?= h($order['shipping_city']) ?></div>
                        <div class="small text-muted"><?= h($order['shipping_phone']) ?></div>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td><?= h($item['product_name']) ?></td>
                            <td class="text-center"><?= $item['quantity'] ?></td>
                            <td class="text-end"><?= formatPrice($item['price']) ?></td>
                            <td class="text-end fw-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end text-muted">Subtotal</td>
                            <td class="text-end"><?= formatPrice($order['subtotal']) ?></td>
                        </tr>
                        <?php if($order['discount'] > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end text-muted">Discount (<?= h($order['coupon_code']) ?>)</td>
                            <td class="text-end text-danger">-<?= formatPrice($order['discount']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="3" class="text-end text-muted">Shipping</td>
                            <td class="text-end"><?= formatPrice($order['shipping']) ?></td>
                        </tr>
                        <tr class="table-success">
                            <td colspan="3" class="text-end fw-bold fs-5">Total</td>
                            <td class="text-end fw-bold fs-5 text-green"><?= formatPrice($order['total_price']) ?></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="d-flex justify-content-between small text-muted mt-2">
                    <span>Payment: <?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment' ?></span>
                    <span class="badge px-2 py-1 status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="col-md-4">
            <div class="gg-card p-4 mb-4">
                <h6 class="fw-bold mb-3">Update Status</h6>
                <form method="POST" action="<?= SITE_URL ?>/backend/admin/orders.php">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="update_status" value="1">
                    <select name="status" class="form-select gg-form-control mb-3">
                        <?php foreach(['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $order['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn gg-btn-green w-100">Update Status</button>
                </form>
            </div>
            <div class="gg-card p-4">
                <h6 class="fw-bold mb-3">Order Info</h6>
                <div class="small mb-2"><strong>Placed:</strong> <?= date('d M Y H:i', strtotime($order['created_at'])) ?></div>
                <div class="small mb-2"><strong>Payment:</strong> <?= strtoupper($order['payment_method']) ?></div>
                <div class="small mb-2"><strong>Payment Status:</strong> <?= ucfirst($order['payment_status']) ?></div>
                <?php if($order['notes']): ?>
                <div class="small"><strong>Notes:</strong> <?= h($order['notes']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .gg-admin-top-bar, .gg-admin-sidebar, .col-md-4, .btn, nav { display:none !important; }
    .gg-admin-content { margin-left:0 !important; }
    .gg-card { border:1px solid #ccc !important; box-shadow:none !important; }
}
</style>

<?php include __DIR__ . '/admin-footer.php'; ?>
