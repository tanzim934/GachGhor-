<?php
// ============================================================
// GachGhor — Admin: Sales Reports
// File: backend/admin/reports.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$db = getDB();

// Monthly sales (last 12 months)
$monthly = $db->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') as month_key,
           DATE_FORMAT(created_at,'%b %Y') as month_label,
           SUM(total_price) as revenue,
           COUNT(*) as orders
    FROM orders WHERE status!='cancelled'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month_key ORDER BY month_key ASC
")->fetchAll();

// Top 10 products
$topProducts = $db->query("
    SELECT p.name, p.image, SUM(oi.quantity) as sold, SUM(oi.quantity*oi.price) as revenue
    FROM order_items oi JOIN products p ON oi.product_id=p.id
    GROUP BY p.id ORDER BY sold DESC LIMIT 10
")->fetchAll();

// Revenue by category
$byCat = $db->query("
    SELECT c.name, c.icon, SUM(oi.quantity*oi.price) as revenue, SUM(oi.quantity) as sold
    FROM order_items oi
    JOIN products p ON oi.product_id=p.id
    JOIN categories c ON p.category_id=c.id
    GROUP BY c.id ORDER BY revenue DESC
")->fetchAll();

// Today's stats
$today = $db->query("
    SELECT COUNT(*) as orders, COALESCE(SUM(total_price),0) as revenue
    FROM orders WHERE DATE(created_at)=CURDATE() AND status!='cancelled'
")->fetch();

// This month's stats
$thisMonth = $db->query("
    SELECT COUNT(*) as orders, COALESCE(SUM(total_price),0) as revenue
    FROM orders WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND status!='cancelled'
")->fetch();

$pageTitle = 'Sales Reports';
include __DIR__ . '/admin-header.php';
?>

<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">📊 Sales Reports</h4>

    <!-- Quick stats -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
            <div class="gg-stat-card text-center">
                <div class="text-muted small mb-1">Today's Orders</div>
                <div class="fw-bold fs-3"><?= $today['orders'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="gg-stat-card text-center">
                <div class="text-muted small mb-1">Today's Revenue</div>
                <div class="fw-bold fs-3 text-green"><?= formatPrice($today['revenue']) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="gg-stat-card text-center">
                <div class="text-muted small mb-1">This Month's Orders</div>
                <div class="fw-bold fs-3"><?= $thisMonth['orders'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="gg-stat-card text-center">
                <div class="text-muted small mb-1">This Month's Revenue</div>
                <div class="fw-bold fs-3 text-green"><?= formatPrice($thisMonth['revenue']) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Monthly Chart -->
        <div class="col-md-8">
            <div class="gg-card p-4">
                <h6 class="fw-bold mb-4">Monthly Revenue (Last 12 Months)</h6>
                <canvas id="revenueChart" height="280"></canvas>
            </div>
        </div>

        <!-- Revenue by Category -->
        <div class="col-md-4">
            <div class="gg-card p-4">
                <h6 class="fw-bold mb-3">Revenue by Category</h6>
                <?php
                $maxCatRevenue = max(array_column($byCat, 'revenue') ?: [1]);
                foreach($byCat as $cat):
                    $pct = round($cat['revenue'] / $maxCatRevenue * 100);
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-semibold"><?= h($cat['icon']) ?> <?= h($cat['name']) ?></span>
                        <span class="small text-green fw-bold"><?= formatPrice($cat['revenue']) ?></span>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar" style="width:<?= $pct ?>%;background:var(--gg-green)"></div>
                    </div>
                    <small class="text-muted"><?= $cat['sold'] ?> items sold</small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Top Products Table -->
    <div class="gg-card p-4 mt-4">
        <h6 class="fw-bold mb-3">🏆 Top 10 Best Selling Products</h6>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>#</th><th>Product</th><th>Units Sold</th><th>Revenue</th></tr>
                </thead>
                <tbody>
                    <?php foreach($topProducts as $i => $tp): ?>
                    <tr>
                        <td><span class="fw-bold text-muted"><?= $i+1 ?></span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= SITE_URL ?>/assets/images/products/<?= h($tp['image'] ?: 'placeholder.jpg') ?>"
                                     style="width:36px;height:36px;object-fit:cover;border-radius:6px;background:var(--gg-green-pale)"
                                     onerror="this.style.display='none'" alt="">
                                <span class="fw-semibold"><?= h($tp['name']) ?></span>
                            </div>
                        </td>
                        <td><?= number_format($tp['sold']) ?> units</td>
                        <td class="fw-bold text-green"><?= formatPrice($tp['revenue']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const monthlyData = <?= json_encode($monthly) ?>;
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: monthlyData.map(d => d.month_label),
        datasets: [{
            label: 'Revenue (৳)',
            data: monthlyData.map(d => parseFloat(d.revenue)),
            backgroundColor: 'rgba(45,122,79,0.7)',
            borderColor: '#2d7a4f',
            borderWidth: 2,
            borderRadius: 6,
        }, {
            label: 'Orders',
            data: monthlyData.map(d => parseInt(d.orders)),
            backgroundColor: 'rgba(244,166,35,0.6)',
            borderColor: '#f4a623',
            borderWidth: 2,
            borderRadius: 6,
            yAxisID: 'y2',
        }]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        scales: {
            y:  { beginAtZero: true, title: { display: true, text: 'Revenue (৳)' } },
            y2: { beginAtZero: true, position: 'right', title: { display: true, text: 'Orders' }, grid: { drawOnChartArea: false } }
        }
    }
});
</script>

<?php include __DIR__ . '/admin-footer.php'; ?>
