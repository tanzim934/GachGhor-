<?php
// ============================================================
// GachGhor — Login Page
// File: frontend/login.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';

// If already logged in, redirect to home
if (isLoggedIn()) { redirect(SITE_URL . '/frontend/index.php'); }

$redirect = $_GET['redirect'] ?? (SITE_URL . '/frontend/index.php');
$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_blocked = 0 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];

            // Redirect admin to dashboard
            if ($user['role'] === 'admin') {
                redirect(SITE_URL . '/backend/admin/dashboard.php');
            }
            redirect($redirect);
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= isset($_COOKIE['gg-theme']) ? h($_COOKIE['gg-theme']) : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — GachGhor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="min-vh-100 d-flex align-items-center justify-content-center p-3" style="background: var(--gg-bg);">
    <div class="gg-auth-card w-100">
        <!-- Logo -->
        <div class="gg-auth-logo">🌿</div>
        <h2 class="text-center gg-section-title mb-1">Welcome Back</h2>
        <p class="text-center text-muted mb-4">Sign in to your GachGhor account</p>

        <?php if($error): ?>
        <div class="alert alert-danger gg-alert"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:var(--gg-surface-2);border:1.5px solid var(--gg-border);border-right:none;">
                        <i class="bi bi-envelope text-muted"></i>
                    </span>
                    <input type="email" name="email" class="form-control gg-form-control"
                           placeholder="you@example.com"
                           value="<?= h($_POST['email'] ?? '') ?>" required
                           style="border-left:none;">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:var(--gg-surface-2);border:1.5px solid var(--gg-border);border-right:none;">
                        <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input type="password" name="password" id="passwordInput" class="form-control gg-form-control"
                           placeholder="Your password" required style="border-left:none;border-right:none;">
                    <button type="button" class="input-group-text" style="background:var(--gg-surface-2);border:1.5px solid var(--gg-border);border-left:none;cursor:pointer;"
                            onclick="togglePass()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                    <label class="form-check-label small" for="rememberMe">Remember me</label>
                </div>
                <a href="<?= SITE_URL ?>/frontend/forgot-password.php" class="small text-green">Forgot password?</a>
            </div>
            <button type="submit" class="btn gg-btn-green w-100 py-2 fw-bold">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <hr class="my-4">
        <p class="text-center text-muted mb-0">
            Don't have an account? <a href="<?= SITE_URL ?>/frontend/register.php" class="text-green fw-bold">Register here</a>
        </p>
        <p class="text-center mt-2">
            <a href="<?= SITE_URL ?>/frontend/index.php" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>Back to Home</a>
        </p>

        <!-- Demo credentials -->
        <div class="mt-3 p-3 rounded" style="background:var(--gg-green-pale);border:1.5px solid var(--gg-border);">
            <small class="text-muted d-block fw-bold mb-1">🔑 Demo Credentials:</small>
            <small class="text-muted d-block">Admin: admin@gachghor.com / password</small>
            <small class="text-muted d-block">Customer: rahim@example.com / password</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
function togglePass() {
    const inp = document.getElementById('passwordInput');
    const icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
