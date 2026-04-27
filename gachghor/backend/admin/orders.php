<?php
// ============================================================
// GachGhor — Admin: Order Management
// File: backend/admin/orders.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$db = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId   = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $allowed   = ['pending','confirmed','processing','shipped','delivered','cancelled'];
    if (in_array($newStatus, $allowed)) {
        $db->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$newStatus, $orderId]);
        setFlash('success', "Order status updated to <strong>" . ucfirst($newStatus) . "</strong>.");
    }
    redirect(SITE_URL . '/backend/admin/orders.php');
}

// Filters
$status  = $_GET['status'] ?? '';
$search  = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($status) { $where[] = "o.status = ?"; $params[] = $status; }
if ($search) { $where[] = "(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

$total = $db->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id=u.id $whereSQL");
$total->execute($params);
$total      = (int)$total->fetchColumn();
$totalPages = ceil($total / $perPage);

$orders = $db->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email
    FROM orders o JOIN users u ON o.user_id = u.id
    $whereSQL
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$orders->execute($params);
$orders = $orders->fetchAll();

$statuses = ['pending','confirmed','processing','shipped','delivered','cancelled'];

$pageTitle = 'Order Management';
include __DIR__ . '/admin-header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">📦 Orders <span class="badge bg-success"><?= $total ?></span></h4>
    </div>

    <!-- Status filter tabs -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="?<?= $search ? 'q='.urlencode($search) : '' ?>"
           class="gg-cat-pill py-1 px-3 <?= !$status?'active':'' ?>" style="font-size:0.82rem">All</a>
        <?php foreach($statuses as $s): ?>
        <a href="?status=<?= $s ?><?= $search ? '&q='.urlencode($search) : '' ?>"
           class="gg-cat-pill py-1 px-3 <?= $status===$s?'active':'' ?>" style="font-size:0.82rem">
            <?= ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Search -->
    <div class="gg-card p-3 mb-4">
        <form method="GET" class="row g-2">
            <?php if($status): ?><input type="hidden" name="status" value="<?= h($status) ?>"><?php endif; ?>
            <div class="col-md-6">
                <input type="search" name="q" class="form-control gg-form-control"
                       placeholder="Search by order number, customer..." value="<?= h($search) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn gg-btn-green">Search</button>
                <a href="<?= SITE_URL ?>/backend/admin/orders.php" class="btn btn-outline-secondary ms-1">Reset</a>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="gg-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($orders)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No orders found.</td></tr>
                    <?php endif; ?>
                    <?php foreach($orders as $order): ?>
                    <?php
                    $itemCount = $db->prepare("SELECT COUNT(*) FROM order_items WHERE order_id=?");
                    $itemCount->execute([$order['id']]);
                    $itemCount = $itemCount->fetchColumn();
                    ?>
                    <tr>
                        <td><strong class="text-green"><?= h($order['order_number']) ?></strong></td>
                        <td>
                            <div class="fw-semibold"><?= h($order['customer_name']) ?></div>
                            <small class="text-muted"><?= h($order['customer_email']) ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= $itemCount ?> item(s)</span></td>
                        <td><strong><?= formatPrice($order['total_price']) ?></strong></td>
                        <td>
                            <span class="badge <?= $order['payment_method']==='cod'?'bg-secondary':'bg-primary' ?>">
                                <?= $order['payment_method'] === 'cod' ? 'COD' : 'Online' ?>
                            </span>
                        </td>
                        <td>
                            <!-- Inline status update -->
                            <form method="POST" class="d-flex align-items-center gap-1">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" class="form-select form-select-sm"
                                        style="width:130px;font-size:0.78rem"
                                        onchange="this.form.submit()">
                                    <?php foreach($statuses as $s): ?>
                                    <option value="<?= $s ?>" <?= $order['status']===$s?'selected':'' ?>>
                                        <?= ucfirst($s) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                        <td><small class="text-muted"><?= date('d M Y', strtotime($order['created_at'])) ?></small></td>
                        <td>
                            <a href="<?= SITE_URL ?>/backend/admin/order-detail.php?id=<?= $order['id'] ?>"
                               class="btn btn-sm btn-outline-secondary" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="p-3 border-top">
            <nav><ul class="pagination pagination-sm mb-0">
                <?php for($i=1;$i<=$totalPages;$i++):
                    $pUrl = '?'.http_build_query(array_merge($_GET,['page'=>$i]));
                ?>
                <li class="page-item <?= $i===$page?'active':'' ?>">
                    <a class="page-link" href="<?= $pUrl ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/admin-footer.php'; ?>
