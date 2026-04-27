<?php
// ============================================================
// GachGhor — Admin: Blog Management
// File: backend/admin/blog.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$db = getDB();

// Delete post
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM blog_posts WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success','Blog post deleted.'); redirect(SITE_URL.'/backend/admin/blog.php');
}
// Toggle publish
if (isset($_GET['toggle'])) {
    $db->prepare("UPDATE blog_posts SET is_published = NOT is_published WHERE id=?")->execute([(int)$_GET['toggle']]);
    redirect(SITE_URL.'/backend/admin/blog.php');
}

$errors = [];
$editPost = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM blog_posts WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editPost = $s->fetch();
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $editId  = (int)($_POST['edit_id']??0);
    $title   = trim($_POST['title']??'');
    $content = trim($_POST['content']??'');
    $slug    = makeSlug($title) . '-' . time();
    $imgFile = $editPost['image'] ?? null;

    if (!$title)   $errors[]='Title is required.';
    if (!$content) $errors[]='Content is required.';

    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $newName   = 'blog-'.time().'.'.$ext;
            $uploadDir = __DIR__.'/../../assets/images/blog/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir.$newName)) $imgFile = $newName;
        }
    }

    if (empty($errors)) {
        if ($editId) {
            $db->prepare("UPDATE blog_posts SET title=?,content=?,image=? WHERE id=?")
               ->execute([$title,$content,$imgFile,$editId]);
            setFlash('success','Post updated.');
        } else {
            $db->prepare("INSERT INTO blog_posts (title,slug,content,image,author_id) VALUES (?,?,?,?,?)")
               ->execute([$title,$slug,$content,$imgFile,$_SESSION['user_id']]);
            setFlash('success','Post published!');
        }
        redirect(SITE_URL.'/backend/admin/blog.php');
    }
}

$posts = $db->query("SELECT b.*, u.name as author FROM blog_posts b LEFT JOIN users u ON b.author_id=u.id ORDER BY b.created_at DESC")->fetchAll();

$pageTitle = 'Blog Management';
include __DIR__ . '/admin-header.php';
?>
<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">📝 Blog Posts</h4>
    <div class="row g-4">

        <!-- Form -->
        <div class="col-md-5">
            <div class="gg-card p-4">
                <h6 class="fw-bold mb-3"><?= $editPost?'✏️ Edit Post':'➕ New Post' ?></h6>
                <?php if($errors): ?><div class="alert alert-danger gg-alert py-2"><?= implode('<br>',array_map('h',$errors)) ?></div><?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <?php if($editPost): ?><input type="hidden" name="edit_id" value="<?= $editPost['id'] ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control gg-form-control" required
                               value="<?= h($editPost['title']??$_POST['title']??'') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content * (HTML allowed)</label>
                        <textarea name="content" class="form-control gg-form-control" rows="8" required><?= h($editPost['content']??'') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Featured Image</label>
                        <input type="file" name="image" class="form-control gg-form-control" accept="image/*">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn gg-btn-green flex-fill"><?= $editPost?'Update':'Publish Post' ?></button>
                        <?php if($editPost): ?><a href="?" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Posts List -->
        <div class="col-md-7">
            <div class="gg-card overflow-hidden">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Title</th><th>Author</th><th>Status</th><th>Date</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php if(empty($posts)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No posts yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach($posts as $p): ?>
                    <tr>
                        <td class="fw-semibold small"><?= h(substr($p['title'],0,45)) ?><?= strlen($p['title'])>45?'...':'' ?></td>
                        <td><small><?= h($p['author']??'Admin') ?></small></td>
                        <td>
                            <a href="?toggle=<?= $p['id'] ?>" class="badge <?= $p['is_published']?'bg-success':'bg-secondary' ?> text-decoration-none">
                                <?= $p['is_published']?'Published':'Draft' ?>
                            </a>
                        </td>
                        <td><small class="text-muted"><?= date('d M Y',strtotime($p['created_at'])) ?></small></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="<?= SITE_URL ?>/frontend/blog-post.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash3"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/admin-footer.php'; ?>
