<?php
// ============================================================
// GachGhor — Admin Shared Header
// File: backend/admin/admin-header.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$flash = getFlash();
$adminPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Admin') ?> — GachGhor Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { padding-bottom: 0 !important; }
        .gg-admin-top-bar {
            position: fixed; top: 0; left: 0; right: 0;
            height: var(--gg-nav-height);
            background: var(--gg-green-dark);
            display: flex; align-items: center;
            padding: 0 20px;
            z-index: 1050;
            border-bottom: 2px solid var(--gg-green);
        }
        .gg-admin-top-bar .logo { color: white; font-weight: 700; font-size: 1.2rem; text-decoration: none; }
        .gg-admin-wrapper { display: flex; padding-top: var(--gg-nav-height); min-height: 100vh; }
        .gg-admin-sidebar { width: 240px; flex-shrink: 0; }
        .gg-admin-content { flex: 1; overflow-x: auto; }
        @media(max-width:768px) { .gg-admin-sidebar { display: none; } }
        .sidebar-section-title { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--gg-text-muted); padding: 14px 20px 6px; font-weight: 700; }
    </style>
</head>
<body>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<!-- Top Admin Bar -->
<div class="gg-admin-top-bar">
    <a href="<?= SITE_URL ?>/backend/admin/dashboard.php" class="logo">
        🌿 GachGhor <span style="opacity:0.6;font-size:0.75rem;font-weight:400;margin-left:6px;">Admin Panel</span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-white-50 small d-none d-md-inline">👋 <?= h($_SESSION['user_name']) ?></span>
        <a href="<?= SITE_URL ?>/frontend/index.php" target="_blank" class="btn btn-sm btn-outline-light">
            <i class="bi bi-shop me-1"></i>Store
        </a>
        <a href="<?= SITE_URL ?>/backend/api/auth.php?action=logout" class="btn btn-sm btn-danger">Logout</a>
    </div>
</div>

<div class="gg-admin-wrapper">
    <!-- Sidebar -->
    <div class="gg-admin-sidebar" style="position:fixed;top:var(--gg-nav-height);bottom:0;overflow-y:auto;background:var(--gg-surface);border-right:2px solid var(--gg-border);">

        <div class="sidebar-section-title">Main</div>
        <a href="<?= SITE_URL ?>/backend/admin/dashboard.php" class="gg-sidebar-link <?= $adminPage==='dashboard'?'active':'' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="sidebar-section-title">Catalog</div>
        <a href="<?= SITE_URL ?>/backend/admin/products.php" class="gg-sidebar-link <?= $adminPage==='products'?'active':'' ?>">
            <i class="bi bi-flower1"></i> Products
        </a>
        <a href="<?= SITE_URL ?>/backend/admin/product-form.php" class="gg-sidebar-link <?= $adminPage==='product-form'?'active':'' ?>">
            <i class="bi bi-plus-square"></i> Add Product
        </a>
        <a href="<?= SITE_URL ?>/backend/admin/categories.php" class="gg-sidebar-link <?= $adminPage==='categories'?'active':'' ?>">
            <i class="bi bi-tags"></i> Categories
        </a>

        <div class="sidebar-section-title">Sales</div>
        <a href="<?= SITE_URL ?>/backend/admin/orders.php" class="gg-sidebar-link <?= $adminPage==='orders'?'active':'' ?>">
            <i class="bi bi-bag"></i> Orders
        </a>
        <a href="<?= SITE_URL ?>/backend/admin/coupons.php" class="gg-sidebar-link <?= $adminPage==='coupons'?'active':'' ?>">
            <i class="bi bi-ticket-perforated"></i> Coupons
        </a>
        <a href="<?= SITE_URL ?>/backend/admin/reports.php" class="gg-sidebar-link <?= $adminPage==='reports'?'active':'' ?>">
            <i class="bi bi-graph-up"></i> Reports
        </a>

        <div class="sidebar-section-title">Users</div>
        <a href="<?= SITE_URL ?>/backend/admin/users.php" class="gg-sidebar-link <?= $adminPage==='users'?'active':'' ?>">
            <i class="bi bi-people"></i> Customers
        </a>

        <div class="sidebar-section-title">Content</div>
        <a href="<?= SITE_URL ?>/backend/admin/blog.php" class="gg-sidebar-link <?= $adminPage==='blog'?'active':'' ?>">
            <i class="bi bi-journal-text"></i> Blog
        </a>
        <a href="<?= SITE_URL ?>/backend/admin/messages.php" class="gg-sidebar-link <?= $adminPage==='messages'?'active':'' ?>">
            <i class="bi bi-envelope"></i> Messages
        </a>

        <div class="sidebar-section-title">Account</div>
        <a href="<?= SITE_URL ?>/frontend/index.php" target="_blank" class="gg-sidebar-link">
            <i class="bi bi-shop"></i> View Store
        </a>
        <a href="<?= SITE_URL ?>/backend/api/auth.php?action=logout" class="gg-sidebar-link text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <!-- Main Content -->
    <div class="gg-admin-content" style="margin-left:240px;">
        <?php if($flash): ?>
        <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> gg-alert m-3 mb-0">
            <?= $flash['msg'] ?>
        </div>
        <?php endif; ?>
