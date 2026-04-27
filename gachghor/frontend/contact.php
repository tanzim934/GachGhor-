<?php
// ============================================================
// GachGhor — Contact Page with FAQ
// File: frontend/contact.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = getDB();
        $db->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)")
           ->execute([$name, $email, $subject, $message]);
        $success = "Thank you, $name! Your message has been received. We'll reply within 24 hours.";
    }
}

$pageTitle = 'Contact Us';
include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="gg-page-banner">
    <div class="container"><h1><i class="bi bi-envelope me-2"></i>Contact Us</h1></div>
</div>

<div class="container my-5">
    <div class="row g-5">

        <!-- Contact Info -->
        <div class="col-md-4">
            <h4 class="gg-section-title mb-1">Get in Touch</h4>
            <div class="gg-section-divider"></div>
            <p class="text-muted mb-4">We're here to help with any questions about plants, orders, or care tips.</p>

            <div class="d-flex gap-3 mb-3">
                <div class="gg-stat-icon" style="background:var(--gg-green-pale);width:44px;height:44px;flex-shrink:0">📍</div>
                <div><div class="fw-bold">Office</div><small class="text-muted">Dhaka, Bangladesh</small></div>
            </div>
            <div class="d-flex gap-3 mb-3">
                <div class="gg-stat-icon" style="background:var(--gg-green-pale);width:44px;height:44px;flex-shrink:0">📞</div>
                <div><div class="fw-bold">Phone / WhatsApp</div><small class="text-muted">01700-GACHGHOR</small></div>
            </div>
            <div class="d-flex gap-3 mb-3">
                <div class="gg-stat-icon" style="background:var(--gg-green-pale);width:44px;height:44px;flex-shrink:0">✉️</div>
                <div><div class="fw-bold">Email</div><small class="text-muted">hello@gachghor.com</small></div>
            </div>
            <div class="d-flex gap-3 mb-4">
                <div class="gg-stat-icon" style="background:var(--gg-green-pale);width:44px;height:44px;flex-shrink:0">🕐</div>
                <div><div class="fw-bold">Hours</div><small class="text-muted">Sat–Thu: 9am–8pm BST</small></div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-md-8">
            <?php if($success): ?>
            <div class="alert alert-success gg-alert text-center py-4">
                <div style="font-size:2.5rem">🌿</div>
                <h5 class="mt-2"><?= h($success) ?></h5>
            </div>
            <?php else: ?>
            <?php if($error): ?>
            <div class="alert alert-danger gg-alert"><?= h($error) ?></div>
            <?php endif; ?>
            <div class="gg-card p-4">
                <h5 class="fw-bold mb-4">Send us a Message</h5>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Your Name *</label>
                            <input type="text" name="name" class="form-control gg-form-control"
                                   value="<?= h($_POST['name'] ?? (isLoggedIn() ? $_SESSION['user_name'] : '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control gg-form-control"
                                   value="<?= h($_POST['email'] ?? (isLoggedIn() ? $_SESSION['email'] : '')) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Subject</label>
                            <select name="subject" class="form-select gg-form-control">
                                <option>General Inquiry</option>
                                <option>Order Issue</option>
                                <option>Plant Care Question</option>
                                <option>Return / Refund</option>
                                <option>Partnership</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message *</label>
                            <textarea name="message" class="form-control gg-form-control" rows="5"
                                      placeholder="Tell us how we can help..." required minlength="10"><?= h($_POST['message'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn gg-btn-green mt-3 px-4">
                        <i class="bi bi-send me-2"></i>Send Message
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="mt-5">
        <div class="text-center mb-4">
            <h3 class="gg-section-title">Frequently Asked Questions</h3>
            <div class="gg-section-divider mx-auto"></div>
        </div>
        <div class="row g-3 justify-content-center">
            <div class="col-md-8">
                <div class="accordion" id="faqAccordion">
                    <?php
                    $faqs = [
                        ['Do you deliver outside Dhaka?', 'Yes! We deliver nationwide across all 64 districts of Bangladesh. Dhaka delivery: same or next day. Outside Dhaka: 2-3 business days via courier.'],
                        ['Are the plants healthy and guaranteed?', 'All our plants are nursery-grown and quality-checked before shipping. We guarantee healthy plants on arrival. If your plant arrives damaged, contact us within 48 hours for a free replacement.'],
                        ['How are plants packaged for delivery?', 'Plants are carefully packed with protective foam padding, newspaper lining, and waterproof bags. Roots are kept moist and pots are secured to prevent damage.'],
                        ['Can I return a plant if I\'m not satisfied?', 'Yes, we offer a 7-day return policy for all products. Plants must be in their original condition. Contact us with a photo and we\'ll arrange pickup or replacement.'],
                        ['Do you offer plant care advice after purchase?', 'Absolutely! Our team is available on WhatsApp and email to answer all your plant care questions. We also have an extensive blog with care guides.'],
                        ['What payment methods do you accept?', 'We accept Cash on Delivery (COD), bKash, Nagad, Rocket, and major debit/credit cards through our online payment gateway.'],
                        ['Can I modify or cancel an order?', 'Orders can be modified or cancelled within 2 hours of placement. After that, please contact us and we\'ll do our best to accommodate your request.'],
                    ];
                    foreach($faqs as $i => [$q, $a]):
                    ?>
                    <div class="accordion-item" style="border:1.5px solid var(--gg-border);border-radius:var(--gg-radius-sm);margin-bottom:8px;overflow:hidden;">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq-<?= $i ?>"
                                    style="background:var(--gg-surface);color:var(--gg-text);font-weight:600;">
                                <?= h($q) ?>
                            </button>
                        </h2>
                        <div id="faq-<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted" style="background:var(--gg-surface-2);">
                                <?= h($a) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
