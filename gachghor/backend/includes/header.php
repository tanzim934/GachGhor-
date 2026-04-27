<?php
// ============================================================
// GachGhor — Shared Header (nav, meta, CSS imports)
// File: backend/includes/header.php
// ============================================================
require_once __DIR__ . '/config.php';
$cartCount = getCartCount();
$flash = getFlash();
$categories = getCategories();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? SITE_NAME) ?> — <?= h(SITE_TAGLINE) ?></title>
    <meta name="description" content="GachGhor - Bangladesh's premier online plant and gardening store. Buy indoor plants, outdoor trees, bonsai, pots and gardening tools.">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,500&display=swap" rel="stylesheet">
    <!-- GachGhor Custom CSS -->
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- =================== TOP NAVIGATION (Desktop) =================== -->
<nav class="navbar navbar-expand-lg sticky-top gg-navbar" id="mainNav">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand gg-logo" href="<?= SITE_URL ?>/frontend/index.php">
            <span class="logo-leaf">🌿</span>
            <span class="logo-text">GachGhor</span>
            <span class="logo-bn">গাছঘর</span>
        </a>

        <!-- Mobile icons -->
        <div class="d-flex d-lg-none align-items-center gap-2 ms-auto me-2">
            <a href="<?= SITE_URL ?>/frontend/cart.php" class="nav-icon-btn position-relative">
                <i class="bi bi-cart3 fs-5"></i>
                <?php if($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
            <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
                <i class="bi bi-moon-stars-fill"></i>
            </button>
        </div>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <!-- Search bar -->
            <form class="d-flex mx-auto gg-search-form" action="<?= SITE_URL ?>/frontend/products.php" method="GET">
                <div class="input-group">
                    <input class="form-control gg-search-input" type="search" name="q"
                           placeholder="Search plants, tools, pots..." 
                           value="<?= h($_GET['q'] ?? '') ?>">
                    <button class="btn gg-search-btn" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- Desktop nav links -->
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/frontend/index.php">Home</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Plants</a>
                    <ul class="dropdown-menu gg-dropdown">
                        <?php foreach($categories as $cat): ?>
                        <li>
                            <a class="dropdown-item" href="<?= SITE_URL ?>/frontend/products.php?category=<?= h($cat['slug']) ?>">
                                <?= h($cat['icon']) ?> <?= h($cat['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item fw-semibold" href="<?= SITE_URL ?>/frontend/products.php">All Products</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/frontend/blog.php">Blog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/frontend/contact.php">Contact</a>
                </li>

                <?php if(isLoggedIn()): ?>
                    <!-- Cart -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= SITE_URL ?>/frontend/cart.php">
                            <i class="bi bi-cart3 fs-5"></i>
                            <?php if($cartCount > 0): ?>
                            <span class="cart-badge"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <!-- User dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" data-bs-toggle="dropdown">
                            <img src="<?= SITE_URL ?>/assets/images/avatar-default.png" class="gg-avatar-sm" alt="avatar">
                            <?= h($_SESSION['user_name'] ?? 'Account') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end gg-dropdown">
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/frontend/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/frontend/orders.php"><i class="bi bi-bag me-2"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/frontend/wishlist.php"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
                            <?php if(isAdmin()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-success" href="<?= SITE_URL ?>/backend/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/backend/api/auth.php?action=logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn gg-btn-outline-green btn-sm ms-1" href="<?= SITE_URL ?>/frontend/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn gg-btn-green btn-sm ms-1" href="<?= SITE_URL ?>/frontend/register.php">Register</a>
                    </li>
                <?php endif; ?>

                <li class="nav-item d-none d-lg-block ms-1">
                    <button class="theme-toggle" id="themeToggleDesktop" title="Toggle dark mode">
                        <i class="bi bi-moon-stars-fill"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- =================== FLASH MESSAGES =================== -->
<?php if($flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : 'info') ?> alert-dismissible fade show gg-alert" role="alert">
        <?= h($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<!-- Main content starts -->
<main>
