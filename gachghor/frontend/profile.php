<?php
// ============================================================
// GachGhor — User Profile Page
// File: frontend/profile.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
requireLogin();
$db = getDB();

$user = $db->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

$success = '';
$error   = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city'] ?? '');

    if (strlen($name) < 3) {
        $error = 'Name must be at least 3 characters.';
    } else {
        $db->prepare("UPDATE users SET name=?, phone=?, address=?, city=? WHERE id=?")
           ->execute([$name, $phone, $address, $city, $_SESSION['user_id']]);
        $_SESSION['user_name'] = $name;
        $success = 'Profile updated successfully!';

        // Refresh user data
        $user = $db->prepare("SELECT * FROM users WHERE id=?");
        $user->execute([$_SESSION['user_id']]);
        $user = $user->fetch();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $_SESSION['user_id']]);
        $success = 'Password changed successfully!';
    }
}

$pageTitle = 'My Profile';
include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="gg-page-banner">
    <div class="container">
        <h1><i class="bi bi-person me-2"></i>My Profile</h1>
    </div>
</div>

<div class="container my-4">
    <div class="row g-4">

        <!-- ===== SIDEBAR ===== -->
        <div class="col-md-3">
            <div class="gg-card p-4 text-center">
                <img src="<?= SITE_URL ?>/assets/images/avatar-default.png"
                     class="rounded-circle mb-3" style="width:100px;height:100px;object-fit:cover;border:3px solid var(--gg-green);"
                     alt="Avatar">
                <h6 class="fw-bold"><?= h($user['name']) ?></h6>
                <p class="text-muted small"><?= h($user['email']) ?></p>
                <span class="badge bg-success"><?= ucfirst($user['role']) ?></span>
            </div>
            <div class="gg-card mt-3 overflow-hidden">
                <a href="<?= SITE_URL ?>/frontend/profile.php" class="gg-sidebar-link active">
                    <i class="bi bi-person"></i> Edit Profile
                </a>
                <a href="<?= SITE_URL ?>/frontend/orders.php" class="gg-sidebar-link">
                    <i class="bi bi-bag"></i> My Orders
                </a>
                <a href="<?= SITE_URL ?>/frontend/wishlist.php" class="gg-sidebar-link">
                    <i class="bi bi-heart"></i> Wishlist
                </a>
                <a href="<?= SITE_URL ?>/backend/api/auth.php?action=logout" class="gg-sidebar-link text-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>

        <!-- ===== MAIN ===== -->
        <div class="col-md-9">
            <?php if($success): ?>
            <div class="alert alert-success gg-alert"><?= h($success) ?></div>
            <?php endif; ?>
            <?php if($error): ?>
            <div class="alert alert-danger gg-alert"><?= h($error) ?></div>
            <?php endif; ?>

            <!-- Edit Profile -->
            <div class="gg-card p-4 mb-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-pencil-square me-2 text-green"></i>Edit Profile</h5>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control gg-form-control"
                                   value="<?= h($user['name']) ?>" required minlength="3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <small class="text-muted">(cannot change)</small></label>
                            <input type="email" class="form-control gg-form-control" value="<?= h($user['email']) ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control gg-form-control"
                                   value="<?= h($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control gg-form-control"
                                   value="<?= h($user['city'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control gg-form-control" rows="2"><?= h($user['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn gg-btn-green mt-3">
                        <i class="bi bi-save me-2"></i>Save Changes
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="gg-card p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock me-2 text-green"></i>Change Password</h5>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Current Password *</label>
                            <input type="password" name="current_password" class="form-control gg-form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password *</label>
                            <input type="password" name="new_password" class="form-control gg-form-control" required minlength="8">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password *</label>
                            <input type="password" name="confirm_password" class="form-control gg-form-control" required>
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn gg-btn-outline-green mt-3">
                        <i class="bi bi-key me-2"></i>Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
