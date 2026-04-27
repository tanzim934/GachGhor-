<?php
// ============================================================
// GachGhor — Admin: Product Management
// File: backend/admin/products.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$db = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    // Soft delete - set inactive
    $db->prepare("UPDATE products SET is_active=0 WHERE id=?")->execute([$delId]);
    setFlash('success', 'Product deleted successfully.');
    redirect(SITE_URL . '/backend/admin/products.php');
}

// Handle restore
if (isset($_GET['restore'])) {
    $restId = (int)$_GET['restore'];
    $db->prepare("UPDATE products SET is_active=1 WHERE id=?")->execute([$restId]);
    setFlash('success', 'Product restored.');
    redirect(SITE_URL . '/backend/admin/products.php');
}

// Filters
$search   = trim($_GET['q'] ?? '');
$catId    = (int)($_GET['cat'] ?? 0);
$showAll  = $_GET['show'] ?? 'active'; // active | all
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 15;
$offset   = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($showAll !== 'all') { $where[] = "p.is_active = 1"; }
if ($search) { $where[] = "p.name LIKE ?"; $params[] = "%$search%"; }
if ($catId)  { $where[] = "p.category_id = ?"; $params[] = $catId; }

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

$total = $db->prepare("SELECT COUNT(*) FROM products p $whereSQL");
$total->execute($params);
$total = (int)$total->fetchColumn();
$totalPages = ceil($total / $perPage);

$products = $db->prepare("
    SELECT p.*, c.name as cat_name
    FROM products p JOIN categories c ON p.category_id = c.id
    $whereSQL
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$products->execute($params);
$products = $products->fetchAll();

$categories = getCategories();

$pageTitle = 'Product Management';
include __DIR__ . '/admin-header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">🌿 Products <span class="badge bg-success"><?= $total ?></span></h4>
        <a href="<?= SITE_URL ?>/backend/admin/product-form.php" class="btn gg-btn-green">
            <i class="bi bi-plus-circle me-2"></i>Add New Product
        </a>
    </div>

    <!-- Filters -->
    <div class="gg-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="search" name="q" class="form-control gg-form-control"
                       placeholder="Search products..." value="<?= h($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="cat" class="form-select gg-form-control">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $catId==$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="show" class="form-select gg-form-control">
                    <option value="active" <?= $showAll==='active'?'selected':'' ?>>Active Only</option>
                    <option value="all" <?= $showAll==='all'?'selected':'' ?>>Show All</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn gg-btn-green me-2">Filter</button>
                <a href="<?= SITE_URL ?>/backend/admin/products.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="gg-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="60">Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($products)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No products found.</td></tr>
                    <?php endif; ?>
                    <?php foreach($products as $p): ?>
                    <tr class="<?= !$p['is_active'] ? 'table-secondary opacity-75' : '' ?>">
                        <td>
                            <img src="<?= SITE_URL ?>/assets/images/products/<?= h($p['image'] ?: 'placeholder.jpg') ?>"
                                 style="width:48px;height:48px;object-fit:cover;border-radius:8px;background:var(--gg-green-pale)"
                                 onerror="this.src='<?= SITE_URL ?>/assets/images/plant-placeholder.svg'"
                                 alt="">
                        </td>
                        <td>
                            <div class="fw-semibold"><?= h($p['name']) ?></div>
                            <?php if($p['is_featured']): ?>
                            <span class="badge bg-warning text-dark" style="font-size:0.65rem">⭐ Featured</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="gg-badge-green"><?= h($p['cat_name']) ?></span></td>
                        <td>
                            <div class="fw-bold text-green"><?= formatPrice($p['sale_price'] ?: $p['price']) ?></div>
                            <?php if($p['sale_price']): ?>
                            <small class="text-muted text-decoration-line-through"><?= formatPrice($p['price']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $p['stock'] == 0 ? 'bg-danger' : ($p['stock'] <= 5 ? 'bg-warning text-dark' : 'bg-success') ?>">
                                <?= $p['stock'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if($p['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= SITE_URL ?>/frontend/product.php?id=<?= $p['id'] ?>" target="_blank"
                                   class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= SITE_URL ?>/backend/admin/product-form.php?id=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if($p['is_active']): ?>
                                <a href="?delete=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this product?')" title="Delete">
                                    <i class="bi bi-trash3"></i>
                                </a>
                                <?php else: ?>
                                <a href="?restore=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success" title="Restore">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                                <?php endif; ?>
                            </div>
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
                    $pUrl = '?' . http_build_query(array_merge($_GET, ['page'=>$i]));
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
