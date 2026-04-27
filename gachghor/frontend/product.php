<?php
// ============================================================
// GachGhor — Product Detail Page
// File: frontend/product.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect(SITE_URL . '/frontend/products.php'); }

// Fetch product with category
$stmt = $db->prepare("
    SELECT p.*, c.name as cat_name, c.slug as cat_slug
    FROM products p JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { redirect(SITE_URL . '/frontend/products.php'); }

// Increment view count
$db->prepare("UPDATE products SET views = views+1 WHERE id=?")->execute([$id]);

// Reviews
$reviews = $db->prepare("
    SELECT r.*, u.name as user_name, u.avatar FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ? AND r.is_approved = 1
    ORDER BY r.created_at DESC
");
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

$avgRating = 0;
if ($reviews) {
    $avgRating = array_sum(array_column($reviews, 'rating')) / count($reviews);
}

// Related products
$related = $db->prepare("
    SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id
    WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
    ORDER BY RAND() LIMIT 4
");
$related->execute([$p['category_id'], $id]);
$related = $related->fetchAll();

// Is in wishlist?
$isWishlisted = false;
if (isLoggedIn()) {
    $ws = $db->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
    $ws->execute([$_SESSION['user_id'], $id]);
    $isWishlisted = (bool)$ws->fetch();
}

// Has user reviewed?
$userReview = null;
if (isLoggedIn()) {
    $rv = $db->prepare("SELECT * FROM reviews WHERE user_id=? AND product_id=?");
    $rv->execute([$_SESSION['user_id'], $id]);
    $userReview = $rv->fetch();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    requireLogin();
    $rating = (int)$_POST['rating'];
    $review = trim($_POST['review']);
    if ($rating >= 1 && $rating <= 5 && $review) {
        try {
            $db->prepare("
                INSERT INTO reviews (product_id, user_id, rating, review)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE rating=VALUES(rating), review=VALUES(review)
            ")->execute([$id, $_SESSION['user_id'], $rating, $review]);
            setFlash('success', 'Review submitted! Thank you.');
        } catch (Exception $e) {
            setFlash('error', 'Could not save review.');
        }
        redirect(SITE_URL . '/frontend/product.php?id=' . $id);
    }
}

$pageTitle = $p['name'];
include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb gg-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/frontend/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/frontend/products.php">Products</a></li>
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/frontend/products.php?category=<?= h($p['cat_slug']) ?>"><?= h($p['cat_name']) ?></a></li>
            <li class="breadcrumb-item active"><?= h($p['name']) ?></li>
        </ol>
    </nav>

    <!-- ===== PRODUCT MAIN ===== -->
    <div class="row g-4">
        <!-- Images -->
        <div class="col-md-5">
            <img src="<?= SITE_URL ?>/assets/images/products/<?= h($p['image'] ?: 'placeholder.jpg') ?>"
                 alt="<?= h($p['name']) ?>" class="product-detail-img mb-3" id="mainProductImg"
                 onerror="this.src='<?= SITE_URL ?>/assets/images/plant-placeholder.svg'">
            <!-- Thumbnails -->
            <?php
            $thumbs = array_filter([$p['image'], $p['image2'], $p['image3']]);
            if(count($thumbs) > 1):
            ?>
            <div class="d-flex gap-2 flex-wrap">
                <?php foreach($thumbs as $img): ?>
                <img src="<?= SITE_URL ?>/assets/images/products/<?= h($img) ?>"
                     class="product-thumb rounded" style="width:72px;height:72px;object-fit:cover;cursor:pointer;border:2px solid var(--gg-border);"
                     onerror="this.style.display='none'">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="col-md-7">
            <div class="gg-badge-green mb-2"><?= h($p['cat_name']) ?></div>
            <h1 class="h2 fw-bold"><?= h($p['name']) ?></h1>

            <!-- Rating -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="star-rating">
                    <?php for($i=1;$i<=5;$i++): ?>
                    <i class="bi bi-star<?= $i<=round($avgRating) ? '-fill' : ($i-0.5<$avgRating ? '-half' : '') ?>"></i>
                    <?php endfor; ?>
                </div>
                <span class="fw-bold"><?= number_format($avgRating,1) ?></span>
                <span class="text-muted">(<?= count($reviews) ?> reviews)</span>
            </div>

            <!-- Price -->
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="fs-2 fw-bold text-green"><?= formatPrice($p['sale_price'] ?: $p['price']) ?></span>
                <?php if($p['sale_price']): ?>
                <span class="fs-5 text-muted text-decoration-line-through"><?= formatPrice($p['price']) ?></span>
                <span class="badge bg-danger">
                    <?= round((1 - $p['sale_price']/$p['price'])*100) ?>% OFF
                </span>
                <?php endif; ?>
            </div>

            <!-- Stock -->
            <?php if($p['stock'] > 0): ?>
            <p class="text-success fw-semibold mb-3">
                <i class="bi bi-check-circle-fill me-1"></i>
                In Stock (<?= $p['stock'] ?> available)
            </p>
            <?php else: ?>
            <p class="text-danger fw-semibold mb-3"><i class="bi bi-x-circle-fill me-1"></i>Out of Stock</p>
            <?php endif; ?>

            <!-- Description -->
            <p class="text-muted mb-4"><?= nl2br(h($p['description'])) ?></p>

            <!-- Care Info -->
            <?php if($p['care_watering'] || $p['care_sunlight']): ?>
            <div class="row g-2 mb-4">
                <?php if($p['care_watering']): ?>
                <div class="col-4"><div class="care-icon-box"><div class="ci">💧</div><div class="small fw-semibold">Watering</div><div class="small text-muted"><?= h($p['care_watering']) ?></div></div></div>
                <?php endif; ?>
                <?php if($p['care_sunlight']): ?>
                <div class="col-4"><div class="care-icon-box"><div class="ci">☀️</div><div class="small fw-semibold">Sunlight</div><div class="small text-muted"><?= h($p['care_sunlight']) ?></div></div></div>
                <?php endif; ?>
                <?php if($p['care_temperature']): ?>
                <div class="col-4"><div class="care-icon-box"><div class="ci">🌡️</div><div class="small fw-semibold">Temperature</div><div class="small text-muted"><?= h($p['care_temperature']) ?></div></div></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Quantity & Actions -->
            <?php if($p['stock'] > 0): ?>
            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                <div class="quantity-control">
                    <button class="quantity-btn" id="qtyMinus" onclick="changeQty(-1)"><i class="bi bi-dash"></i></button>
                    <input class="quantity-input" type="number" id="detailQty" value="1" min="1" max="<?= $p['stock'] ?>" readonly>
                    <button class="quantity-btn" id="qtyPlus" onclick="changeQty(1)"><i class="bi bi-plus"></i></button>
                </div>
                <button class="btn gg-btn-green px-4 py-2 fw-bold btn-add-cart" data-id="<?= $p['id'] ?>" id="addToCartBtn">
                    <i class="bi bi-cart3 me-2"></i>Add to Cart
                </button>
                <button class="btn <?= $isWishlisted ? 'btn-danger' : 'btn-outline-danger' ?> px-3 py-2 btn-wishlist" data-id="<?= $p['id'] ?>">
                    <i class="bi bi-heart<?= $isWishlisted ? '-fill' : '' ?>"></i>
                    <span class="ms-1 d-none d-sm-inline"><?= $isWishlisted ? 'Wishlisted' : 'Wishlist' ?></span>
                </button>
            </div>
            <?php endif; ?>

            <!-- Delivery info -->
            <div class="gg-card p-3">
                <div class="row g-2">
                    <div class="col-6 col-sm-4 d-flex align-items-center gap-2 small">
                        <span>🚚</span><span>Dhaka: Same day</span>
                    </div>
                    <div class="col-6 col-sm-4 d-flex align-items-center gap-2 small">
                        <span>📦</span><span>Nationwide: 2-3 days</span>
                    </div>
                    <div class="col-6 col-sm-4 d-flex align-items-center gap-2 small">
                        <span>🔄</span><span>7-day return policy</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== REVIEWS ===== -->
    <div class="row mt-5">
        <div class="col-md-8">
            <h4 class="gg-section-title mb-3">Customer Reviews</h4>
            <div class="gg-section-divider"></div>

            <!-- Submit review form -->
            <?php if(isLoggedIn() && !$userReview): ?>
            <div class="gg-card p-4 mb-4">
                <h6 class="fw-bold mb-3">Write a Review</h6>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Your Rating</label>
                        <div class="stars-input d-flex gap-1">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <span class="star" data-value="<?= $i ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingValue" required>
                    </div>
                    <div class="mb-3">
                        <textarea name="review" class="form-control gg-form-control" rows="3"
                                  placeholder="Share your experience with this plant..." required minlength="10"></textarea>
                        <div class="invalid-feedback">Please write at least 10 characters.</div>
                    </div>
                    <button type="submit" name="submit_review" class="btn gg-btn-green">Submit Review</button>
                </form>
            </div>
            <?php elseif(!isLoggedIn()): ?>
            <div class="alert alert-info mb-4">
                <a href="<?= SITE_URL ?>/frontend/login.php" class="fw-bold">Login</a> to write a review.
            </div>
            <?php endif; ?>

            <!-- Reviews list -->
            <?php if($reviews): ?>
            <?php foreach($reviews as $rev): ?>
            <div class="gg-card p-4 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-bold"><?= h($rev['user_name']) ?></div>
                        <div class="star-rating">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <i class="bi bi-star<?= $i<=$rev['rating'] ? '-fill' : '' ?>" style="font-size:0.85rem"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <small class="text-muted"><?= date('d M Y', strtotime($rev['created_at'])) ?></small>
                </div>
                <p class="mt-2 mb-0 text-muted"><?= nl2br(h($rev['review'])) ?></p>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p class="text-muted">No reviews yet. Be the first to review this product!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== RELATED PRODUCTS ===== -->
    <?php if($related): ?>
    <div class="mt-5">
        <h4 class="gg-section-title mb-1">Related Products</h4>
        <div class="gg-section-divider"></div>
        <div class="row row-cols-2 row-cols-md-4 g-3">
            <?php foreach($related as $rp): ?>
            <div class="col">
                <div class="product-card h-100">
                    <div class="product-card-img-wrap" style="height:160px;">
                        <a href="<?= SITE_URL ?>/frontend/product.php?id=<?= $rp['id'] ?>">
                            <img src="<?= SITE_URL ?>/assets/images/products/<?= h($rp['image'] ?: 'placeholder.jpg') ?>"
                                 alt="<?= h($rp['name']) ?>"
                                 onerror="this.src='<?= SITE_URL ?>/assets/images/plant-placeholder.svg'">
                        </a>
                    </div>
                    <div class="product-card-body">
                        <div class="product-card-title">
                            <a href="<?= SITE_URL ?>/frontend/product.php?id=<?= $rp['id'] ?>" class="text-decoration-none text-dark">
                                <?= h($rp['name']) ?>
                            </a>
                        </div>
                        <div class="price-current"><?= formatPrice($rp['sale_price'] ?: $rp['price']) ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function changeQty(delta) {
    const inp = document.getElementById('detailQty');
    const max = parseInt(inp.max);
    let val = parseInt(inp.value) + delta;
    inp.value = Math.max(1, Math.min(max, val));
    document.querySelector('.btn-add-cart').dataset.qty = inp.value;
}
</script>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
