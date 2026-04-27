<?php
// ============================================================
// GachGhor — Forgot Password Page
// File: frontend/forgot-password.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
if (isLoggedIn()) redirect(SITE_URL . '/frontend/index.php');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db   = getDB();
        $user = $db->prepare("SELECT id FROM users WHERE email=?");
        $user->execute([$email]);
        $user = $user->fetch();

        if ($user) {
            // Generate reset token (in production: send email)
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $db->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE email=?")
               ->execute([$token, $expires, $email]);
            $resetLink = SITE_URL . '/frontend/reset-password.php?token=' . $token;
            // In production: email this link. For demo we show it directly.
            $success = "Password reset link generated. <br><small class='text-muted'>Demo only — link: <a href='$resetLink'>$resetLink</a></small>";
        } else {
            // Don't reveal if email exists or not (security)
            $success = "If that email is registered, you'll receive a reset link shortly.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Forgot Password — GachGhor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="min-vh-100 d-flex align-items-center justify-content-center p-3" style="background:var(--gg-bg)">
    <div class="gg-auth-card w-100">
        <div class="gg-auth-logo">🔑</div>
        <h2 class="text-center gg-section-title mb-1">Forgot Password</h2>
        <p class="text-center text-muted mb-4">Enter your email and we'll send you a reset link.</p>

        <?php if($success): ?>
        <div class="alert alert-success gg-alert"><?= $success ?></div>
        <div class="text-center mt-3"><a href="<?= SITE_URL ?>/frontend/login.php" class="btn gg-btn-green px-4">Back to Login</a></div>
        <?php else: ?>
        <?php if($error): ?><div class="alert alert-danger gg-alert"><?= h($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control gg-form-control" placeholder="you@example.com" required>
            </div>
            <button type="submit" class="btn gg-btn-green w-100 py-2 fw-bold">Send Reset Link</button>
        </form>
        <p class="text-center mt-3 text-muted">
            <a href="<?= SITE_URL ?>/frontend/login.php" class="text-green">← Back to Login</a>
        </p>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
