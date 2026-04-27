<?php
// ============================================================
// GachGhor — Blog Post Detail Page
// File: frontend/blog-post.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
$db = getDB();

$id   = (int)($_GET['id'] ?? 0);
$post = $db->prepare("SELECT b.*, u.name as author_name FROM blog_posts b LEFT JOIN users u ON b.author_id=u.id WHERE b.id=? AND b.is_published=1");
$post->execute([$id]);
$post = $post->fetch();
if (!$post) { redirect(SITE_URL . '/frontend/blog.php'); }

// Related posts
$related = $db->prepare("SELECT * FROM blog_posts WHERE id!=? AND is_published=1 ORDER BY RAND() LIMIT 3");
$related->execute([$id]);
$related = $related->fetchAll();

$pageTitle = $post['title'];
include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="gg-page-banner">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb gg-breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/frontend/index.php" class="text-white-50">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/frontend/blog.php" class="text-white-50">Blog</a></li>
                <li class="breadcrumb-item active text-white">Article</li>
            </ol>
        </nav>
        <h1 style="font-size:1.8rem"><?= h($post['title']) ?></h1>
        <div class="mt-2 text-white-50 small">
            <?php if($post['author_name']): ?>By <?= h($post['author_name']) ?> · <?php endif; ?>
            <?= date('d F Y', strtotime($post['created_at'])) ?>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if($post['image']): ?>
            <img src="<?= SITE_URL ?>/assets/images/blog/<?= h($post['image']) ?>"
                 class="img-fluid rounded-gg mb-4 w-100" style="max-height:400px;object-fit:cover;"
                 onerror="this.style.display='none'" alt="<?= h($post['title']) ?>">
            <?php endif; ?>

            <div class="gg-card p-4 p-md-5" style="line-height:1.9;font-size:1.05rem;">
                <?= $post['content'] ?>
            </div>

            <!-- Share buttons -->
            <div class="mt-4 d-flex align-items-center gap-3">
                <span class="fw-bold text-muted">Share:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL.'/frontend/blog-post.php?id='.$id) ?>"
                   target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-facebook me-1"></i>Facebook
                </a>
                <a href="https://wa.me/?text=<?= urlencode($post['title'].' '.SITE_URL.'/frontend/blog-post.php?id='.$id) ?>"
                   target="_blank" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-whatsapp me-1"></i>WhatsApp
                </a>
            </div>

            <!-- Related Posts -->
            <?php if($related): ?>
            <div class="mt-5">
                <h5 class="fw-bold mb-3">More Articles</h5>
                <div class="row g-3">
                    <?php foreach($related as $r): ?>
                    <div class="col-md-4">
                        <a href="<?= SITE_URL ?>/frontend/blog-post.php?id=<?= $r['id'] ?>" class="text-decoration-none">
                            <div class="blog-card h-100">
                                <?php if($r['image']): ?>
                                <img src="<?= SITE_URL ?>/assets/images/blog/<?= h($r['image']) ?>"
                                     alt="" style="height:120px;"
                                     onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div class="blog-card-body">
                                    <p class="fw-semibold small mb-0 text-dark"><?= h($r['title']) ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
