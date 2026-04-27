<?php
// ============================================================
// GachGhor — Blog Listing Page
// File: frontend/blog.php
// ============================================================
require_once __DIR__ . '/../backend/includes/config.php';
$db = getDB();

$posts = $db->query("
    SELECT b.*, u.name as author_name
    FROM blog_posts b LEFT JOIN users u ON b.author_id=u.id
    WHERE b.is_published=1 ORDER BY b.created_at DESC
")->fetchAll();

$pageTitle = 'Plant Care Blog';
include __DIR__ . '/../backend/includes/header.php';
?>
<script>const SITE_URL = '<?= SITE_URL ?>';</script>

<div class="gg-page-banner">
    <div class="container"><h1>🌱 Plant Care Blog</h1></div>
</div>

<div class="container my-5">
    <?php if(empty($posts)): ?>
    <div class="text-center py-5">
        <div style="font-size:4rem">📰</div>
        <h5 class="mt-3">No blog posts yet</h5>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach($posts as $post): ?>
        <div class="col-md-4">
            <div class="blog-card h-100">
                <?php if($post['image']): ?>
                <img src="<?= SITE_URL ?>/assets/images/blog/<?= h($post['image']) ?>"
                     alt="<?= h($post['title']) ?>"
                     onerror="this.style.display='none'">
                <?php endif; ?>
                <div class="blog-card-body">
                    <small class="text-muted">
                        <?= date('d M Y', strtotime($post['created_at'])) ?>
                        <?php if($post['author_name']): ?> · by <?= h($post['author_name']) ?><?php endif; ?>
                    </small>
                    <h5 class="fw-bold mt-2 mb-2"><?= h($post['title']) ?></h5>
                    <p class="text-muted small"><?= h(substr(strip_tags($post['content']), 0, 120)) ?>...</p>
                    <a href="<?= SITE_URL ?>/frontend/blog-post.php?id=<?= $post['id'] ?>" class="btn gg-btn-outline-green btn-sm">
                        Read More <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../backend/includes/footer.php'; ?>
