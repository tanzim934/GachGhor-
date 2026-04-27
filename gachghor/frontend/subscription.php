<?php
// ============================================================
// GachGhor — Monthly Plant Subscription Page
// File: frontend/subscription.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $plan     = in_array($_POST['plan'] ?? '', ['basic','standard','premium']) ? $_POST['plan'] : 'basic';
    $prices   = ['basic' => 299, 'standard' => 549, 'premium' => 899];
    $price    = $prices[$plan];
    $next     = date('Y-m-d', strtotime('+1 month'));

    $db = getDB();

    // Check for existing active subscription
    $existing = $db->prepare("SELECT id FROM subscriptions WHERE user_id=? AND status='active'");
    $existing->execute([$_SESSION['user_id']]);

    if ($existing->fetch()) {
        $error = 'You already have an active subscription. Please cancel it first.';
    } else {
        $db->prepare("INSERT INTO subscriptions (user_id, plan, price, next_delivery) VALUES (?,?,?,?)")
           ->execute([$_SESSION['user_id'], $plan, $price, $next]);
        $success = "🎉 You've subscribed to the " . ucfirst($plan) . " plan! Your first delivery is on " . date('d M Y', strtotime($next)) . ".";
    }
}

$pageTitle = 'Monthly Plant Subscription';
include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="gg-page-banner">
    <div class="container"><h1>📦 Monthly Plant Subscription</h1></div>
</div>

<div class="container my-5">

    <?php if($success): ?>
    <div class="alert alert-success gg-alert text-center py-4">
        <div style="font-size:3rem">🌿</div>
        <h5 class="mt-2"><?= h($success) ?></h5>
        <a href="<?= SITE_URL ?>/frontend/index.php" class="btn gg-btn-green mt-3">Back to Home</a>
    </div>
    <?php else: ?>

    <?php if($error): ?>
    <div class="alert alert-danger gg-alert"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="text-center mb-5">
        <h2 class="gg-section-title">Choose Your Plan</h2>
        <p class="text-muted">Get hand-picked plants delivered monthly. Cancel anytime.</p>
        <div class="gg-section-divider mx-auto"></div>
    </div>

    <form method="POST">
    <div class="row justify-content-center g-4 mb-5">

        <!-- Basic Plan -->
        <div class="col-md-4">
            <label class="plan-card d-block h-100" style="cursor:pointer;">
                <input type="radio" name="plan" value="basic" class="d-none" <?= (!isLoggedIn()||$_POST['plan']??'')==='basic'?'checked':'' ?>>
                <div class="plan-price mb-2">৳299</div>
                <div class="fw-bold fs-5 mb-1">Basic</div>
                <small class="text-muted d-block mb-3">per month</small>
                <ul class="list-unstyled text-start small">
                    <li class="mb-2">✅ 2 curated indoor plants</li>
                    <li class="mb-2">✅ Plant care card included</li>
                    <li class="mb-2">✅ Free delivery</li>
                    <li class="mb-2">✅ Beginner-friendly selection</li>
                    <li class="text-muted">❌ No tools included</li>
                    <li class="text-muted">❌ No pots included</li>
                </ul>
                <div class="mt-3 p-2 rounded text-center" style="background:var(--gg-green-pale)">
                    <small class="fw-bold text-green">Perfect for beginners</small>
                </div>
            </label>
        </div>

        <!-- Standard Plan (Popular) -->
        <div class="col-md-4">
            <label class="plan-card popular d-block h-100" style="cursor:pointer;">
                <input type="radio" name="plan" value="standard" class="d-none">
                <div class="plan-price mb-2">৳549</div>
                <div class="fw-bold fs-5 mb-1">Standard</div>
                <small class="text-muted d-block mb-3">per month</small>
                <ul class="list-unstyled text-start small">
                    <li class="mb-2">✅ 3 curated plants (indoor/outdoor)</li>
                    <li class="mb-2">✅ 1 gardening tool</li>
                    <li class="mb-2">✅ Plant care card + tips booklet</li>
                    <li class="mb-2">✅ Free priority delivery</li>
                    <li class="mb-2">✅ Seasonal plant selection</li>
                    <li class="text-muted">❌ No pots included</li>
                </ul>
                <div class="mt-3 p-2 rounded text-center" style="background:var(--gg-green-pale)">
                    <small class="fw-bold text-green">Best value for plant lovers</small>
                </div>
            </label>
        </div>

        <!-- Premium Plan -->
        <div class="col-md-4">
            <label class="plan-card d-block h-100" style="cursor:pointer;">
                <input type="radio" name="plan" value="premium" class="d-none">
                <div class="plan-price mb-2">৳899</div>
                <div class="fw-bold fs-5 mb-1">Premium</div>
                <small class="text-muted d-block mb-3">per month</small>
                <ul class="list-unstyled text-start small">
                    <li class="mb-2">✅ 4 premium/rare plants</li>
                    <li class="mb-2">✅ 1 decorative pot</li>
                    <li class="mb-2">✅ 1 gardening tool</li>
                    <li class="mb-2">✅ Soil & fertilizer pack</li>
                    <li class="mb-2">✅ Monthly plant magazine</li>
                    <li class="mb-2">✅ Priority WhatsApp support</li>
                </ul>
                <div class="mt-3 p-2 rounded text-center" style="background:var(--gg-green-pale)">
                    <small class="fw-bold text-green">For serious plant collectors</small>
                </div>
            </label>
        </div>
    </div>

    <!-- Benefits row -->
    <div class="row g-3 mb-5">
        <?php
        $benefits = [
            ['🚚','Free Delivery','All subscription boxes ship free nationwide'],
            ['🌿','Expert Curated','Our botanists hand-pick each plant for your box'],
            ['📅','Flexible','Pause or cancel your subscription anytime online'],
            ['🎁','Surprise Element','Each month brings a different botanical experience'],
        ];
        foreach($benefits as [$icon,$title,$desc]):
        ?>
        <div class="col-6 col-md-3">
            <div class="gg-card p-3 text-center h-100">
                <div style="font-size:2rem"><?= $icon ?></div>
                <div class="fw-bold small mt-2"><?= $title ?></div>
                <p class="text-muted" style="font-size:0.75rem;margin:4px 0 0"><?= $desc ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center">
        <?php if(isLoggedIn()): ?>
        <button type="submit" class="btn gg-btn-green px-5 py-3 fw-bold fs-5">
            <i class="bi bi-gift me-2"></i>Subscribe Now
        </button>
        <p class="text-muted small mt-2">Cancel anytime from your profile page. No hidden fees.</p>
        <?php else: ?>
        <a href="<?= SITE_URL ?>/frontend/login.php?redirect=<?= urlencode(SITE_URL.'/frontend/subscription.php') ?>"
           class="btn gg-btn-green px-5 py-3 fw-bold fs-5">
            <i class="bi bi-person me-2"></i>Login to Subscribe
        </a>
        <?php endif; ?>
    </div>
    </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
