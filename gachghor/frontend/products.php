<?php
// ============================================================
// GachGhor — Product Listing Page
// File: frontend/products.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
$db = getDB();

// ---- READ FILTER PARAMS ----
$search   = trim($_GET['q'] ?? '');
$catSlug  = trim($_GET['category'] ?? '');
$sort     = $_GET['sort'] ?? 'newest';
$maxPrice = (int)($_GET['max_price'] ?? 5000);
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// Build WHERE clause
$where = ["p.is_active = 1"];
$params = [];

if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Category filter
$catId = null;
if ($catSlug) {
    $stmt = $db->prepare("SELECT id, name FROM categories WHERE slug = ?");
    $stmt->execute([$catSlug]);
    $catRow = $stmt->fetch();
    if ($catRow) {
        $catId = $catRow['id'];
        $where[] = "p.category_id = ?";
        $params[] = $catId;
    }
}

// Price filter
$where[] = "COALESCE(p.sale_price, p.price) <= ?";
$params[] = $maxPrice;

$whereSQL = "WHERE " . implode(" AND ", $where);

// Sort
$orderSQL = match($sort) {
    'price_asc'  => "ORDER BY COALESCE(p.sale_price, p.price) ASC",
    'price_desc' => "ORDER BY COALESCE(p.sale_price, p.price) DESC",
    'popular'    => "ORDER BY p.views DESC",
    'rating'     => "ORDER BY avg_rating DESC",
    default      => "ORDER BY p.created_at DESC",
};

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM products p $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Fetch products
$sql = "
    SELECT p.*, c.name as cat_name, c.slug as cat_slug,
           COALESCE(AVG(r.rating),0) as avg_rating,
           COUNT(DISTINCT r.id) as review_count
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved=1
    $whereSQL
    GROUP BY p.id
    $orderSQL
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// All categories for sidebar
$allCats = $db->query("SELECT c.*, COUNT(p.id) as cnt FROM categories c LEFT JOIN products p ON c.id=p.category_id AND p.is_active=1 GROUP BY c.id")->fetchAll();

// User wishlist
$wishlistIds = [];
if (isLoggedIn()) {
    $ws = $db->prepare("SELECT product_id FROM wishlist WHERE user_id=?");
    $ws->execute([$_SESSION['user_id']]);
    $wishlistIds = $ws->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = $search ? "Search: $search" : ($catRow['name'] ?? 'All Products');

include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<!-- Page Banner -->
<div class="gg-page-banner">
    <div class="container">
        <h1 class="text-white"><i class="bi bi-grid me-2"></i><?= h($pageTitle) ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb gg-breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/frontend/index.php" class="text-white-50">Home</a></li>
                <li class="breadcrumb-item active text-white">Products</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container my-4">
    <div class="row g-4">

        <!-- ===== FILTER SIDEBAR ===== -->
        <div class="col-md-3 d-none d-md-block">
            <div class="gg-filter-box">
                <h6 class="fw-bold mb-3">🔍 Filters</h6>

                <!-- Category filter -->
                <h6 class="fw-semibold mt-3 mb-2 text-green">Category</h6>
                <a href="<?= SITE_URL ?>/frontend/products.php<?= $search ? '?q='.urlencode($search) : '' ?>"
                   class="gg-cat-pill d-block mb-2 <?= !$catSlug ? 'active' : '' ?> py-1 px-3" style="font-size:0.85rem">
                    All Products <span class="badge bg-success ms-1"><?= $total ?></span>
                </a>
                <?php foreach($allCats as $cat): ?>
                <?php
                $catUrl = SITE_URL . '/frontend/products.php?category=' . urlencode($cat['slug']);
                if ($search) $catUrl .= '&q=' . urlencode($search);
                ?>
                <a href="<?= $catUrl ?>" class="gg-cat-pill d-block mb-2 <?= $catSlug===$cat['slug'] ? 'active' : '' ?> py-1 px-3" style="font-size:0.85rem">
                    <?= h($cat['icon']) ?> <?= h($cat['name']) ?> <span class="badge bg-success ms-1"><?= $cat['cnt'] ?></span>
                </a>
                <?php endforeach; ?>

                <!-- Price range -->
                <h6 class="fw-semibold mt-4 mb-2 text-green">Price Range</h6>
                <form id="priceForm" method="GET" action="">
                    <?php if($catSlug): ?><input type="hidden" name="category" value="<?= h($catSlug) ?>"><?php endif; ?>
                    <?php if($search): ?><input type="hidden" name="q" value="<?= h($search) ?>"><?php endif; ?>
                    <input type="hidden" name="sort" value="<?= h($sort) ?>">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">৳0</small>
                        <small class="fw-bold text-green" id="priceDisplay">৳<?= number_format($maxPrice) ?></small>
                    </div>
                    <input type="range" class="form-range" name="max_price" id="priceRange"
                           min="100" max="5000" step="50" value="<?= $maxPrice ?>"
                           oninput="document.getElementById('priceDisplay').textContent='৳'+parseInt(this.value).toLocaleString()">
                    <button type="submit" class="btn gg-btn-green btn-sm w-100 mt-2">Apply Filter</button>
                </form>

                <!-- Sort -->
                <h6 class="fw-semibold mt-4 mb-2 text-green">Sort By</h6>
                <form method="GET">
                    <?php if($catSlug): ?><input type="hidden" name="category" value="<?= h($catSlug) ?>"><?php endif; ?>
                    <?php if($search): ?><input type="hidden" name="q" value="<?= h($search) ?>"><?php endif; ?>
                    <input type="hidden" name="max_price" value="<?= $maxPrice ?>">
                    <select name="sort" class="form-select gg-form-control" onchange="this.form.submit()">
                        <option value="newest"     <?= $sort==='newest'     ? 'selected' : '' ?>>Newest First</option>
                        <option value="popular"    <?= $sort==='popular'    ? 'selected' : '' ?>>Most Popular</option>
                        <option value="rating"     <?= $sort==='rating'     ? 'selected' : '' ?>>Top Rated</option>
                        <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort==='price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- ===== PRODUCT GRID ===== -->
        <div class="col-md-9">
            <!-- Top bar -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="mb-0 text-muted small">
                    Showing <strong><?= count($products) ?></strong> of <strong><?= $total ?></strong> products
                    <?= $search ? " for \"" . h($search) . "\"" : "" ?>
                </p>
                <!-- Mobile sort -->
                <div class="d-md-none">
                    <select class="form-select form-select-sm" onchange="location.href=this.value">
                        <option value="?sort=newest">Newest</option>
                        <option value="?sort=popular">Popular</option>
                        <option value="?sort=price_asc">Price ↑</option>
                        <option value="?sort=price_desc">Price ↓</option>
                    </select>
                </div>
            </div>

            <!-- No results -->
            <?php if(empty($products)): ?>
            <div class="text-center py-5">
                <div style="font-size:4rem">🔍</div>
                <h5 class="mt-3">No products found</h5>
                <p class="text-muted">Try a different search or browse our categories.</p>
                <a href="<?= SITE_URL ?>/frontend/products.php" class="btn gg-btn-green mt-2">Browse All Plants</a>
            </div>
            <?php else: ?>

            <div class="row row-cols-2 row-cols-md-3 g-3">
                <?php foreach($products as $p): ?>
                <div class="col">
                    <div class="product-card h-100">
                        <div class="product-card-img-wrap">
                            <a href="<?= SITE_URL ?>/frontend/product.php?id=<?= $p['id'] ?>">
                                <img src="<?= SITE_URL ?>/assets/images/products/<?= h($p['image'] ?: 'placeholder.jpg') ?>"
                                     alt="<?= h($p['name']) ?>" loading="lazy"
                                     onerror="this.src='<?= SITE_URL ?>/assets/images/plant-placeholder.svg'">
                            </a>
                            <div class="product-card-badges">
                                <?php if($p['sale_price']): ?>
                                <span class="badge-sale">SALE</span>
                                <?php endif; ?>
                                <?php if($p['stock'] == 0): ?>
                                <span class="badge-out">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                            <button class="product-card-wishlist btn-wishlist <?= in_array($p['id'], $wishlistIds) ? 'wishlisted' : '' ?>" data-id="<?= $p['id'] ?>">
                                <i class="bi bi-heart<?= in_array($p['id'], $wishlistIds) ? '-fill' : '' ?>"></i>
                            </button>
                        </div>
                        <div class="product-card-body">
                            <div class="product-card-cat"><?= h($p['cat_name']) ?></div>
                            <h6 class="product-card-title">
                                <a href="<?= SITE_URL ?>/frontend/product.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark">
                                    <?= h($p['name']) ?>
                                </a>
                            </h6>
                            <div class="product-card-stars mb-1">
                                <?php for($i=1;$i<=5;$i++): ?>
                                <i class="bi bi-star<?= $i <= round($p['avg_rating']) ? '-fill' : '' ?>"></i>
                                <?php endfor; ?>
                                <small class="text-muted">(<?= $p['review_count'] ?>)</small>
                            </div>
                            <div class="product-card-price">
                                <span class="price-current"><?= formatPrice($p['sale_price'] ?: $p['price']) ?></span>
                                <?php if($p['sale_price']): ?>
                                <span class="price-original"><?= formatPrice($p['price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="product-card-footer">
                            <?php if($p['stock'] > 0): ?>
                            <button class="btn gg-btn-green btn-sm flex-fill btn-add-cart" data-id="<?= $p['id'] ?>">
                                <i class="bi bi-cart3 me-1"></i>Add to Cart
                            </button>
                            <?php else: ?>
                            <button class="btn btn-secondary btn-sm flex-fill" disabled>Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for($i=1; $i<=$totalPages; $i++):
                        $pUrl = '?' . http_build_query(array_merge($_GET, ['page' => $i]));
                    ?>
                    <li class="page-item <?= $i===$page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $pUrl ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
