<?php
// ============================================================
// GachGhor — Admin Dashboard
// File: backend/admin/dashboard.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$db = getDB();

// ---- DASHBOARD STATS ----
$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
$totalOrders   = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue  = $db->query("SELECT SUM(total_price) FROM orders WHERE status != 'cancelled'")->fetchColumn();

// Monthly revenue (last 6 months)
$monthlyRevenue = $db->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') as month,
           SUM(total_price) as revenue,
           COUNT(*) as order_count
    FROM orders
    WHERE status != 'cancelled'
      AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY created_at ASC
")->fetchAll();

// Recent orders
$recentOrders = $db->query("
    SELECT o.*, u.name as customer_name
    FROM orders o JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 10
")->fetchAll();

// Top selling products
$topProducts = $db->query("
    SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi JOIN products p ON oi.product_id = p.id
    GROUP BY p.id ORDER BY total_sold DESC LIMIT 5
")->fetchAll();

// Low stock products
$lowStock = $db->query("SELECT * FROM products WHERE stock <= 5 AND is_active=1 ORDER BY stock ASC LIMIT 5")->fetchAll();

// Orders by status
$ordersByStatus = $db->query("
    SELECT status, COUNT(*) as count FROM orders GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/admin-header.php';
?>

<div class="container-fluid py-4">

    <!-- ===== STAT CARDS ===== -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="gg-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Total Customers</p>
                        <h3 class="fw-bold mb-0"><?= number_format($totalUsers) ?></h3>
                    </div>
                    <div class="gg-stat-icon" style="background:#e8f5e9">👥</div>
                </div>
                <a href="<?= SITE_URL ?>/backend/admin/users.php" class="text-green small mt-2 d-block">Manage Users →</a>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="gg-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Total Products</p>
                        <h3 class="fw-bold mb-0"><?= number_format($totalProducts) ?></h3>
                    </div>
                    <div class="gg-stat-icon" style="background:#e8f5e9">🌿</div>
                </div>
                <a href="<?= SITE_URL ?>/backend/admin/products.php" class="text-green small mt-2 d-block">Manage Products →</a>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="gg-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Total Orders</p>
                        <h3 class="fw-bold mb-0"><?= number_format($totalOrders) ?></h3>
                    </div>
                    <div class="gg-stat-icon" style="background:#e8f5e9">📦</div>
                </div>
                <a href="<?= SITE_URL ?>/backend/admin/orders.php" class="text-green small mt-2 d-block">Manage Orders →</a>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="gg-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Total Revenue</p>
                        <h3 class="fw-bold mb-0"><?= formatPrice((float)$totalRevenue) ?></h3>
                    </div>
                    <div class="gg-stat-icon" style="background:#e8f5e9">💰</div>
                </div>
                <a href="<?= SITE_URL ?>/backend/admin/reports.php" class="text-green small mt-2 d-block">View Reports →</a>
            </div>
        </div>
    </div>

    <!-- ===== ORDER STATUS OVERVIEW ===== -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <!-- Recent Orders -->
            <div class="gg-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Recent Orders</h6>
                    <a href="<?= SITE_URL ?>/backend/admin/orders.php" class="btn btn-sm gg-btn-outline-green">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentOrders as $order): ?>
                            <tr>
                                <td><strong class="text-green"><?= h($order['order_number']) ?></strong></td>
                                <td><?= h($order['customer_name']) ?></td>
                                <td><?= formatPrice($order['total_price']) ?></td>
                                <td>
                                    <span class="badge px-2 py-1 status-<?= $order['status'] ?> rounded-pill">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td><small><?= date('d M', strtotime($order['created_at'])) ?></small></td>
                                <td>
                                    <a href="<?= SITE_URL ?>/backend/admin/order-detail.php?id=<?= $order['id'] ?>"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Order Status Summary -->
            <div class="gg-card p-4 mb-4">
                <h6 class="fw-bold mb-3">Orders by Status</h6>
                <?php
                $statuses = ['pending','confirmed','processing','shipped','delivered','cancelled'];
                $statusIcons = ['⏳','✅','⚙️','🚚','🎉','❌'];
                foreach($statuses as $i => $s):
                    $count = $ordersByStatus[$s] ?? 0;
                ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span><?= $statusIcons[$i] ?> <?= ucfirst($s) ?></span>
                    <span class="badge bg-secondary rounded-pill"><?= $count ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Low Stock Alert -->
            <?php if($lowStock): ?>
            <div class="gg-card p-4" style="border-left:4px solid var(--gg-accent);">
                <h6 class="fw-bold mb-3">⚠️ Low Stock Alert</h6>
                <?php foreach($lowStock as $lp): ?>
                <div class="d-flex justify-content-between mb-2">
                    <small class="text-truncate me-2"><?= h($lp['name']) ?></small>
                    <span class="badge <?= $lp['stock']==0 ? 'bg-danger' : 'bg-warning text-dark' ?>"><?= $lp['stock'] ?> left</span>
                </div>
                <?php endforeach; ?>
                <a href="<?= SITE_URL ?>/backend/admin/products.php" class="btn btn-sm gg-btn-outline-green w-100 mt-2">Manage Stock</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== TOP PRODUCTS ===== -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="gg-card p-4">
                <h6 class="fw-bold mb-3">🏆 Top Selling Products</h6>
                <?php foreach($topProducts as $i => $tp): ?>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="fw-bold text-muted" style="width:20px"><?= $i+1 ?></span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small"><?= h($tp['name']) ?></div>
                        <small class="text-muted"><?= $tp['total_sold'] ?> sold</small>
                    </div>
                    <div class="text-green fw-bold small"><?= formatPrice($tp['revenue']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-6">
            <div class="gg-card p-4">
                <h6 class="fw-bold mb-3">⚡ Quick Actions</h6>
                <div class="d-grid gap-2">
                    <a href="<?= SITE_URL ?>/backend/admin/product-form.php" class="btn gg-btn-green">
                        <i class="bi bi-plus-circle me-2"></i>Add New Product
                    </a>
                    <a href="<?= SITE_URL ?>/backend/admin/orders.php?status=pending" class="btn btn-outline-warning">
                        <i class="bi bi-clock me-2"></i>View Pending Orders
                        <?php if($ordersByStatus['pending'] ?? 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $ordersByStatus['pending'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= SITE_URL ?>/backend/admin/coupons.php" class="btn btn-outline-success">
                        <i class="bi bi-ticket-perforated me-2"></i>Manage Coupons
                    </a>
                    <a href="<?= SITE_URL ?>/backend/admin/reports.php" class="btn btn-outline-secondary">
                        <i class="bi bi-graph-up me-2"></i>View Sales Reports
                    </a>
                    <a href="<?= SITE_URL ?>/frontend/index.php" class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-shop me-2"></i>View Storefront
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/admin-footer.php'; ?>
