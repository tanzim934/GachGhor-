<?php
// ============================================================
// GachGhor — Shared Footer
// File: backend/includes/footer.php
// ============================================================
?>
</main><!-- end main -->

<!-- =================== DESKTOP FOOTER =================== -->
<footer class="gg-footer d-none d-md-block">
    <div class="container py-5">
        <div class="row g-4">
            <!-- Brand -->
            <div class="col-md-4">
                <h5 class="gg-logo mb-3"><span class="logo-leaf">🌿</span> GachGhor গাছঘর</h5>
                <p class="text-muted small">Bangladesh's premier online plant store. We deliver happiness, one plant at a time. 🌱</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="gg-social-link"><i class="bi bi-facebook fs-5"></i></a>
                    <a href="#" class="gg-social-link"><i class="bi bi-instagram fs-5"></i></a>
                    <a href="#" class="gg-social-link"><i class="bi bi-youtube fs-5"></i></a>
                    <a href="#" class="gg-social-link"><i class="bi bi-whatsapp fs-5"></i></a>
                </div>
            </div>
            <!-- Quick Links -->
            <div class="col-md-2">
                <h6 class="fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= SITE_URL ?>/frontend/products.php" class="gg-footer-link">All Plants</a></li>
                    <li><a href="<?= SITE_URL ?>/frontend/blog.php" class="gg-footer-link">Plant Care Blog</a></li>
                    <li><a href="<?= SITE_URL ?>/frontend/contact.php" class="gg-footer-link">Contact Us</a></li>
                    <li><a href="<?= SITE_URL ?>/frontend/faq.php" class="gg-footer-link">FAQ</a></li>
                </ul>
            </div>
            <!-- Categories -->
            <div class="col-md-3">
                <h6 class="fw-bold mb-3">Categories</h6>
                <ul class="list-unstyled small">
                    <?php foreach(array_slice(getCategories(), 0, 5) as $cat): ?>
                    <li><a href="<?= SITE_URL ?>/frontend/products.php?category=<?= h($cat['slug']) ?>" class="gg-footer-link"><?= h($cat['icon']) ?> <?= h($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <!-- Contact -->
            <div class="col-md-3">
                <h6 class="fw-bold mb-3">Contact</h6>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2"><i class="bi bi-geo-alt-fill text-success me-2"></i>Dhaka, Bangladesh</li>
                    <li class="mb-2"><i class="bi bi-telephone-fill text-success me-2"></i>01700-GACHGHOR</li>
                    <li class="mb-2"><i class="bi bi-envelope-fill text-success me-2"></i>hello@gachghor.com</li>
                    <li class="mb-2"><i class="bi bi-clock-fill text-success me-2"></i>Sat–Thu: 9am–8pm</li>
                </ul>
                <!-- Newsletter -->
                <form class="mt-3" action="<?= SITE_URL ?>/backend/api/subscribe.php" method="POST">
                    <div class="input-group input-group-sm">
                        <input type="email" name="email" class="form-control" placeholder="Your email">
                        <button class="btn gg-btn-green" type="submit">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
        <hr class="mt-4">
        <div class="d-flex justify-content-between align-items-center small text-muted">
            <span>© <?= date('Y') ?> GachGhor. Made with 💚 in Bangladesh.</span>
            <div class="d-flex gap-3">
                <a href="#" class="gg-footer-link">Privacy Policy</a>
                <a href="#" class="gg-footer-link">Terms of Service</a>
                <a href="#" class="gg-footer-link">Returns</a>
            </div>
        </div>
    </div>
</footer>

<!-- =================== MOBILE BOTTOM NAV BAR =================== -->
<nav class="gg-bottom-nav d-md-none">
    <?php $cp = basename($_SERVER['PHP_SELF']); ?>
    <a href="<?= SITE_URL ?>/frontend/index.php" class="gg-bottom-nav-item <?= $cp === 'index.php' ? 'active' : '' ?>">
        <i class="bi bi-house-fill"></i>
        <span>Home</span>
    </a>
    <a href="<?= SITE_URL ?>/frontend/products.php" class="gg-bottom-nav-item <?= $cp === 'products.php' ? 'active' : '' ?>">
        <i class="bi bi-grid-fill"></i>
        <span>Plants</span>
    </a>
    <a href="<?= SITE_URL ?>/frontend/cart.php" class="gg-bottom-nav-item position-relative <?= $cp === 'cart.php' ? 'active' : '' ?>">
        <i class="bi bi-cart3-fill"></i>
        <?php if(getCartCount() > 0): ?>
        <span class="cart-badge-bottom"><?= getCartCount() ?></span>
        <?php endif; ?>
        <span>Cart</span>
    </a>
    <a href="<?= isLoggedIn() ? SITE_URL.'/frontend/profile.php' : SITE_URL.'/frontend/login.php' ?>" 
       class="gg-bottom-nav-item <?= in_array($cp, ['profile.php','login.php']) ? 'active' : '' ?>">
        <i class="bi bi-person-fill"></i>
        <span><?= isLoggedIn() ? 'Profile' : 'Login' ?></span>
    </a>
</nav>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
