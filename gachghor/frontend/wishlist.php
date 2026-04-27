<?php
// ============================================================
// GachGhor — Wishlist Page
// File: frontend/wishlist.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
requireLogin();
$db = getDB();

$items = $db->prepare("
    SELECT p.*, c.name as cat_name, c.slug as cat_slug, w.id as wish_id
    FROM wishlist w
    JOIN products p ON w.product_id=p.id
    JOIN categories c ON p.category_id=c.id
    WHERE w.user_id=?
    ORDER BY w.created_at DESC
");
$items->execute([$_SESSION['user_id']]);
$items = $items->fetchAll();

$pageTitle = 'My Wishlist';
include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="gg-page-banner">
    <div class="container">
        <h1><i class="bi bi-heart me-2"></i>My Wishlist <span class="badge bg-white text-success ms-2" style="font-size:1rem"><?= count($items) ?></span></h1>
    </div>
</div>

<div class="container my-4">
    <?php if(empty($items)): ?>
    <div class="text-center py-5">
        <div style="font-size:4rem">❤️</div>
        <h5 class="mt-3">Your wishlist is empty</h5>
        <p class="text-muted">Save your favourite plants and come back to buy them later.</p>
        <a href="<?= SITE_URL ?>/frontend/products.php" class="btn gg-btn-green mt-2">Explore Plants</a>
    </div>
    <?php else: ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
        <?php foreach($items as $p): ?>
        <div class="col">
            <div class="product-card h-100">
                <div class="product-card-img-wrap">
                    <a href="<?= SITE_URL ?>/frontend/product.php?id=<?= $p['id'] ?>">
                        <img src="<?= SITE_URL ?>/assets/images/products/<?= h($p['image'] ?: 'placeholder.jpg') ?>"
                             alt="<?= h($p['name']) ?>"
                             onerror="this.src='<?= SITE_URL ?>/assets/images/plant-placeholder.svg'">
                    </a>
                    <button class="product-card-wishlist wishlisted btn-wishlist" data-id="<?= $p['id'] ?>">
                        <i class="bi bi-heart-fill"></i>
                    </button>
                </div>
                <div class="product-card-body">
                    <div class="product-card-cat"><?= h($p['cat_name']) ?></div>
                    <h6 class="product-card-title"><?= h($p['name']) ?></h6>
                    <div class="price-current"><?= formatPrice($p['sale_price'] ?: $p['price']) ?></div>
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
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
