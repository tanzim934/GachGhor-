<?php
// ============================================================
// GachGhor — Reset Password Page
// File: frontend/reset-password.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
if (isLoggedIn()) redirect(SITE_URL . '/frontend/index.php');

$token = trim($_GET['token'] ?? '');
$error = '';
$success = '';

$db = getDB();

// Validate token
$user = null;
if ($token) {
    $stmt = $db->prepare("SELECT * FROM users WHERE reset_token=? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
}

if (!$user && $token) {
    $error = 'This reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($new) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?")
           ->execute([$hash, $user['id']]);
        $success = 'Password reset successfully! You can now login with your new password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reset Password — GachGhor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="min-vh-100 d-flex align-items-center justify-content-center p-3" style="background:var(--gg-bg)">
    <div class="gg-auth-card w-100">
        <div class="gg-auth-logo">🔐</div>
        <h2 class="text-center gg-section-title mb-1">Reset Password</h2>
        <p class="text-center text-muted mb-4">Choose a new secure password.</p>

        <?php if($success): ?>
        <div class="alert alert-success gg-alert"><?= h($success) ?></div>
        <div class="text-center mt-3">
            <a href="<?= SITE_URL ?>/frontend/login.php" class="btn gg-btn-green px-4">Login Now</a>
        </div>
        <?php elseif($error && !$user): ?>
        <div class="alert alert-danger gg-alert"><?= h($error) ?></div>
        <p class="text-center">
            <a href="<?= SITE_URL ?>/frontend/forgot-password.php" class="text-green">← Request new link</a>
        </p>
        <?php else: ?>
        <?php if($error): ?><div class="alert alert-danger gg-alert"><?= h($error) ?></div><?php endif; ?>
        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label class="form-label">New Password *</label>
                <input type="password" name="new_password" class="form-control gg-form-control" required minlength="8" placeholder="Min. 8 characters">
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm Password *</label>
                <input type="password" name="confirm_password" class="form-control gg-form-control" required placeholder="Repeat new password">
            </div>
            <button type="submit" class="btn gg-btn-green w-100 py-2 fw-bold">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
