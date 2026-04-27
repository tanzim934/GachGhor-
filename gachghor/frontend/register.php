<?php
// ============================================================
// GachGhor — Registration Page
// File: frontend/register.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
if (isLoggedIn()) { redirect(SITE_URL . '/frontend/index.php'); }

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Validation
    if (strlen($name) < 3)     $errors[] = 'Name must be at least 3 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();
        // Check email exists
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'This email is already registered. Please login.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?,?,?,?,'customer')")
               ->execute([$name, $email, $phone, $hash]);
            $userId = $db->lastInsertId();

            // Auto login after registration
            $_SESSION['user_id']   = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['email']     = $email;
            $_SESSION['role']      = 'customer';

            setFlash('success', "Welcome to GachGhor, $name! 🌿 Start exploring our plants.");
            redirect(SITE_URL . '/frontend/index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — GachGhor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="min-vh-100 d-flex align-items-center justify-content-center p-3" style="background: var(--gg-bg);">
    <div class="gg-auth-card w-100" style="max-width:480px;">
        <div class="gg-auth-logo">🌱</div>
        <h2 class="text-center gg-section-title mb-1">Create Account</h2>
        <p class="text-center text-muted mb-4">Join GachGhor and start your plant journey</p>

        <?php if($errors): ?>
        <div class="alert alert-danger gg-alert">
            <ul class="mb-0 ps-3">
                <?php foreach($errors as $e): ?>
                <li><?= h($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control gg-form-control"
                       placeholder="Your full name" value="<?= h($_POST['name'] ?? '') ?>"
                       required minlength="3">
                <div class="invalid-feedback">Please enter your full name.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control gg-form-control"
                       placeholder="you@example.com" value="<?= h($_POST['email'] ?? '') ?>" required>
                <div class="invalid-feedback">Please enter a valid email.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone Number <small class="text-muted">(optional)</small></label>
                <input type="tel" name="phone" class="form-control gg-form-control"
                       placeholder="01XXXXXXXXX" value="<?= h($_POST['phone'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control gg-form-control"
                       placeholder="Minimum 8 characters" required minlength="8">
                <div class="form-text">At least 8 characters recommended.</div>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control gg-form-control"
                       placeholder="Re-enter your password" required>
            </div>
            <div class="form-check mb-4">
                <input type="checkbox" class="form-check-input" id="terms" required>
                <label class="form-check-label small" for="terms">
                    I agree to the <a href="#" class="text-green">Terms of Service</a> and <a href="#" class="text-green">Privacy Policy</a>
                </label>
                <div class="invalid-feedback">You must agree before registering.</div>
            </div>
            <button type="submit" class="btn gg-btn-green w-100 py-2 fw-bold">
                <i class="bi bi-person-plus me-2"></i>Create Account
            </button>
        </form>

        <hr class="my-4">
        <p class="text-center text-muted mb-0">
            Already have an account? <a href="<?= SITE_URL ?>/frontend/login.php" class="text-green fw-bold">Sign in</a>
        </p>
        <p class="text-center mt-2">
            <a href="<?= SITE_URL ?>/frontend/index.php" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>Back to Home</a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
