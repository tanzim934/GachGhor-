<?php
// ============================================================
// GachGhor — Database Configuration
// File: backend/includes/config.php
// Edit DB_PASS if your XAMPP MySQL has a password set
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // Default XAMPP has no password
define('DB_NAME', 'gachghor');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'GachGhor');
define('SITE_TAGLINE', 'গাছঘর — Your Online Plant Store');
define('SITE_URL', 'http://localhost/gachghor');
define('CURRENCY', '৳');        // Bangladeshi Taka symbol
define('SHIPPING_CHARGE', 60);  // Default shipping in BDT

// Start session once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Database connection using PDO (safe, prevents SQL injection)
// ============================================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;background:#fef2f2;color:#991b1b;padding:20px;border-radius:8px;margin:20px;">
                <strong>Database Connection Error:</strong><br>' . htmlspecialchars($e->getMessage()) . '<br><br>
                Please check your XAMPP MySQL server is running and the database <strong>gachghor</strong> exists.
            </div>');
        }
    }
    return $pdo;
}

// ============================================================
// Helper: Sanitize output (prevent XSS)
// ============================================================
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ============================================================
// Helper: Redirect
// ============================================================
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// ============================================================
// Helper: Check if user is logged in
// ============================================================
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// ============================================================
// Helper: Check if logged in user is Admin
// ============================================================
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// ============================================================
// Helper: Require login — redirect to login if not
// ============================================================
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/frontend/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

// ============================================================
// Helper: Require admin — redirect if not admin
// ============================================================
function requireAdmin(): void {
    if (!isAdmin()) {
        redirect(SITE_URL . '/frontend/index.php');
    }
}

// ============================================================
// Helper: Format price with Taka symbol
// ============================================================
function formatPrice(float $price): string {
    return CURRENCY . number_format($price, 2);
}

// ============================================================
// Helper: Get cart item count for current user
// ============================================================
function getCartCount(): int {
    if (!isLoggedIn()) return 0;
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

// ============================================================
// Helper: Get all categories (cached in session)
// ============================================================
function getCategories(): array {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

// ============================================================
// Helper: Flash messages (one-time session messages)
// ============================================================
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================================
// Helper: Generate order number
// ============================================================
function generateOrderNumber(): string {
    return 'GG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}

// ============================================================
// Helper: Slug generator
// ============================================================
function makeSlug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}
