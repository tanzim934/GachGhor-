<?php
// ============================================================
// GachGhor — Admin: Category Management
// File: backend/admin/categories.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$db = getDB();

// Delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $hasProducts = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id=?");
    $hasProducts->execute([$delId]);
    if ($hasProducts->fetchColumn() > 0) {
        setFlash('error', 'Cannot delete — this category has products assigned to it.');
    } else {
        $db->prepare("DELETE FROM categories WHERE id=?")->execute([$delId]);
        setFlash('success', 'Category deleted.');
    }
    redirect(SITE_URL . '/backend/admin/categories.php');
}

$errors = [];

// Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId = (int)($_POST['edit_id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $icon   = trim($_POST['icon'] ?? '🌿');
    $desc   = trim($_POST['description'] ?? '');
    $slug   = makeSlug($name);

    if (!$name) $errors[] = 'Category name is required.';

    if (empty($errors)) {
        if ($editId) {
            $db->prepare("UPDATE categories SET name=?,slug=?,icon=?,description=? WHERE id=?")
               ->execute([$name, $slug, $icon, $desc, $editId]);
            setFlash('success', 'Category updated.');
        } else {
            try {
                $db->prepare("INSERT INTO categories (name,slug,icon,description) VALUES (?,?,?,?)")
                   ->execute([$name, $slug, $icon, $desc]);
                setFlash('success', "Category '$name' added!");
            } catch (PDOException $e) {
                $errors[] = 'A category with this name already exists.';
            }
        }
        if (empty($errors)) redirect(SITE_URL . '/backend/admin/categories.php');
    }
}

// Edit mode
$editCat = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM categories WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editCat = $s->fetch();
}

$categories = $db->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c LEFT JOIN products p ON c.id=p.category_id AND p.is_active=1
    GROUP BY c.id ORDER BY c.name
")->fetchAll();

$pageTitle = 'Categories';
include __DIR__ . '/admin-header.php';
?>
<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">🏷️ Categories</h4>
    <div class="row g-4">

        <!-- Add/Edit Form -->
        <div class="col-md-4">
            <div class="gg-card p-4">
                <h6 class="fw-bold mb-3"><?= $editCat ? '✏️ Edit Category' : '➕ Add Category' ?></h6>
                <?php if($errors): ?>
                <div class="alert alert-danger gg-alert py-2"><?= implode('<br>', array_map('h', $errors)) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <?php if($editCat): ?>
                    <input type="hidden" name="edit_id" value="<?= $editCat['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" class="form-control gg-form-control"
                               value="<?= h($editCat['name'] ?? $_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Emoji Icon</label>
                        <input type="text" name="icon" class="form-control gg-form-control"
                               value="<?= h($editCat['icon'] ?? $_POST['icon'] ?? '🌿') ?>"
                               placeholder="e.g. 🌵" maxlength="5">
                        <div class="form-text">Paste any emoji to use as icon.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control gg-form-control" rows="2"><?= h($editCat['description'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn gg-btn-green flex-fill">
                            <?= $editCat ? 'Update' : 'Add Category' ?>
                        </button>
                        <?php if($editCat): ?>
                        <a href="<?= SITE_URL ?>/backend/admin/categories.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Categories List -->
        <div class="col-md-8">
            <div class="gg-card overflow-hidden">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Icon</th><th>Name</th><th>Products</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($categories as $cat): ?>
                    <tr>
                        <td style="font-size:1.5rem"><?= h($cat['icon']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= h($cat['name']) ?></div>
                            <?php if($cat['description']): ?>
                            <small class="text-muted"><?= h(substr($cat['description'],0,60)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-success"><?= $cat['product_count'] ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="<?= SITE_URL ?>/frontend/products.php?category=<?= h($cat['slug']) ?>" target="_blank"
                                   class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                <?php if($cat['product_count'] == 0): ?>
                                <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete category?')"><i class="bi bi-trash3"></i></a>
                                <?php endif; ?>
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
