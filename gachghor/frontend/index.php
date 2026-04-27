<?php
// ============================================================
// GachGhor — Home Page (Landing Page)
// File: frontend/index.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
$pageTitle = 'Home';
$db = getDB();

// Fetch featured products
$featured = $db->query("
    SELECT p.*, c.name as cat_name, c.slug as cat_slug,
           COALESCE(AVG(r.rating),0) as avg_rating,
           COUNT(r.id) as review_count
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved = 1
    WHERE p.is_featured = 1 AND p.is_active = 1
    GROUP BY p.id
    LIMIT 8
")->fetchAll();

// Fetch categories with product count
$categories = $db->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
    GROUP BY c.id
    ORDER BY product_count DESC
")->fetchAll();

// Latest blog posts
$blogs = $db->query("SELECT * FROM blog_posts WHERE is_published=1 ORDER BY created_at DESC LIMIT 3")->fetchAll();

// User wishlist IDs (if logged in)
$wishlistIds = [];
if (isLoggedIn()) {
    $ws = $db->prepare("SELECT product_id FROM wishlist WHERE user_id=?");
    $ws->execute([$_SESSION['user_id']]);
    $wishlistIds = $ws->fetchAll(PDO::FETCH_COLUMN);
}

include __DIR__ . '/../backend/includes/header.php';
?>

<!-- SITE_URL for JS -->
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<!-- =================== HERO SECTION =================== -->
<section class="gg-hero">
    <div class="gg-hero-leaf">🌿</div>
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <p class="badge bg-white text-success fw-bold mb-3 px-3 py-2 fs-6">🌱 Bangladesh's #1 Online Plant Store</p>
                <h1 class="display-4 fw-bold mb-3">Bring Nature Home<br>with <em>GachGhor</em></h1>
                <p class="lead mb-4">Discover 200+ plants, trees, bonsai & gardening essentials. Delivered to your doorstep across Bangladesh.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= SITE_URL ?>/frontend/products.php" class="btn btn-light gg-btn-accent fw-bold px-4 py-2">
                        <i class="bi bi-grid-fill me-2"></i>Shop Now
                    </a>
                    <a href="<?= SITE_URL ?>/frontend/products.php?category=indoor" class="btn btn-outline-light fw-bold px-4 py-2">
                        🪴 Indoor Plants
                    </a>
                </div>
                <!-- Trust badges -->
                <div class="d-flex flex-wrap gap-4 mt-4">
                    <div class="text-white-50 small"><i class="bi bi-truck text-white me-1"></i> Free delivery over ৳1500</div>
                    <div class="text-white-50 small"><i class="bi bi-shield-check text-white me-1"></i> Healthy plant guarantee</div>
                    <div class="text-white-50 small"><i class="bi bi-arrow-clockwise text-white me-1"></i> Easy returns</div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-flex justify-content-center">
                <div class="hero-plants-display text-center" style="font-size:7rem; line-height:1; letter-spacing:10px; filter:drop-shadow(0 10px 30px rgba(0,0,0,0.2));">
                    🌳🪴🌺🌵<br>
                    <span style="font-size:4rem;">🌱 🎋 🌻 🌿</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =================== STATS BAR =================== -->
<section class="py-3 bg-success text-white">
    <div class="container">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3">
                <div class="fw-bold fs-4">200+</div>
                <small class="opacity-75">Plants & Products</small>
            </div>
            <div class="col-6 col-md-3">
                <div class="fw-bold fs-4">5000+</div>
                <small class="opacity-75">Happy Customers</small>
            </div>
            <div class="col-6 col-md-3">
                <div class="fw-bold fs-4">64</div>
                <small class="opacity-75">Districts Delivered</small>
            </div>
            <div class="col-6 col-md-3">
                <div class="fw-bold fs-4">4.8⭐</div>
                <small class="opacity-75">Average Rating</small>
            </div>
        </div>
    </div>
</section>

<!-- =================== SHOP BY CATEGORY =================== -->
<section class="container my-5">
    <div class="text-center mb-4">
        <h2 class="gg-section-title">Shop by Category</h2>
        <p class="gg-section-subtitle">Find exactly what your garden needs</p>
        <div class="gg-section-divider mx-auto"></div>
    </div>
    <div class="d-flex flex-wrap justify-content-center gap-3">
        <?php foreach($categories as $cat): ?>
        <a href="<?= SITE_URL ?>/frontend/products.php?category=<?= h($cat['slug']) ?>" class="gg-cat-pill">
            <span style="font-size:1.3rem"><?= h($cat['icon']) ?></span>
            <span><?= h($cat['name']) ?></span>
            <span class="badge bg-success rounded-pill ms-1"><?= $cat['product_count'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- =================== FEATURED PRODUCTS =================== -->
<section class="container my-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="gg-section-title mb-1">Featured Plants & Products</h2>
            <div class="gg-section-divider"></div>
        </div>
        <a href="<?= SITE_URL ?>/frontend/products.php" class="gg-btn-outline-green btn btn-sm">View All <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
        <?php foreach($featured as $p): ?>
        <div class="col">
            <div class="product-card h-100">
                <!-- Image -->
                <div class="product-card-img-wrap">
                    <a href="<?= SITE_URL ?>/frontend/product.php?id=<?= $p['id'] ?>">
                        <img src="<?= SITE_URL ?>/assets/images/products/<?= h($p['image'] ?: 'placeholder.jpg') ?>"
                             alt="<?= h($p['name']) ?>" loading="lazy"
                             onerror="this.src='<?= SITE_URL ?>/assets/images/plant-placeholder.svg'">
                    </a>
                    <!-- Badges -->
                    <div class="product-card-badges">
                        <?php if($p['sale_price']): ?>
                        <span class="badge-sale">SALE</span>
                        <?php endif; ?>
                        <?php if($p['stock'] == 0): ?>
                        <span class="badge-out">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    <!-- Wishlist -->
                    <button class="product-card-wishlist btn-wishlist <?= in_array($p['id'], $wishlistIds) ? 'wishlisted' : '' ?>" data-id="<?= $p['id'] ?>">
                        <i class="bi bi-heart<?= in_array($p['id'], $wishlistIds) ? '-fill' : '' ?>"></i>
                    </button>
                </div>
                <!-- Body -->
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
                        <span class="price-current">
                            <?= formatPrice($p['sale_price'] ?: $p['price']) ?>
                        </span>
                        <?php if($p['sale_price']): ?>
                        <span class="price-original"><?= formatPrice($p['price']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Footer -->
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
</section>

<!-- =================== SUBSCRIPTION BANNER =================== -->
<section class="container my-5">
    <div class="gg-card p-5 text-center" style="background: linear-gradient(135deg, var(--gg-green-pale), var(--gg-surface))">
        <div style="font-size:3rem">📦🌿</div>
        <h3 class="gg-section-title mt-2">Monthly Plant Subscription</h3>
        <p class="text-muted mb-4">Get 2-4 hand-picked plants delivered to your door every month. Curated by our botanists.</p>
        <div class="row justify-content-center g-3 mb-4">
            <?php
            $plans = [
                ['Basic', '৳299', '2 plants/month', false],
                ['Standard', '৳549', '3 plants + tools', true],
                ['Premium', '৳899', '4 plants + pots + blog', false],
            ];
            foreach($plans as [$name, $price, $desc, $popular]):
            ?>
            <div class="col-md-3">
                <div class="plan-card <?= $popular ? 'popular' : '' ?>">
                    <div class="plan-price"><?= $price ?></div>
                    <div class="fw-bold mb-1"><?= $name ?></div>
                    <small class="text-muted"><?= $desc ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="<?= SITE_URL ?>/frontend/subscription.php" class="btn gg-btn-green px-5 py-2 fw-bold">
            <i class="bi bi-gift me-2"></i>Subscribe Now
        </a>
    </div>
</section>

<!-- =================== BLOG / PLANT TIPS =================== -->
<?php if($blogs): ?>
<section class="container my-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="gg-section-title mb-1">Plant Care Tips & Blog</h2>
            <div class="gg-section-divider"></div>
        </div>
        <a href="<?= SITE_URL ?>/frontend/blog.php" class="gg-btn-outline-green btn btn-sm">All Articles <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4">
        <?php foreach($blogs as $blog): ?>
        <div class="col-md-4">
            <div class="blog-card h-100">
                <img src="<?= SITE_URL ?>/assets/images/blog/<?= h($blog['image'] ?: 'default.jpg') ?>"
                     alt="<?= h($blog['title']) ?>"
                     onerror="this.style.display='none'">
                <div class="blog-card-body">
                    <small class="text-muted"><?= date('d M Y', strtotime($blog['created_at'])) ?></small>
                    <h6 class="fw-bold mt-1 mb-2"><?= h($blog['title']) ?></h6>
                    <a href="<?= SITE_URL ?>/frontend/blog-post.php?id=<?= $blog['id'] ?>" class="text-green fw-semibold small">
                        Read More <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- =================== WHY GACHGHOR =================== -->
<section class="bg-green-pale py-5 mt-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="gg-section-title">Why Choose GachGhor?</h2>
            <div class="gg-section-divider mx-auto"></div>
        </div>
        <div class="row g-4 text-center">
            <?php
            $features = [
                ['🌱', 'Expert Curation', 'Every plant is hand-picked and quality-checked by our botanists before shipping.'],
                ['📦', 'Safe Packaging', 'Plants are packed with care to survive delivery in perfect condition.'],
                ['🚚', 'Fast Delivery', 'Same-day delivery in Dhaka, 2-3 days nationwide across Bangladesh.'],
                ['💬', 'Plant Support', '24/7 WhatsApp support for plant care tips and guidance.'],
            ];
            foreach($features as [$icon, $title, $desc]):
            ?>
            <div class="col-6 col-md-3">
                <div class="gg-card p-4 h-100">
                    <div style="font-size:2.5rem" class="mb-3"><?= $icon ?></div>
                    <h6 class="fw-bold"><?= $title ?></h6>
                    <p class="small text-muted mb-0"><?= $desc ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
