<?php
// ============================================================
// GachGhor — Admin: Add/Edit Product Form
// File: backend/admin/product-form.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$db = getDB();

$id      = (int)($_GET['id'] ?? 0);
$product = null;
$errors  = [];

// Load existing product if editing
if ($id) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        setFlash('error', 'Product not found.');
        redirect(SITE_URL . '/backend/admin/products.php');
    }
}

$categories = getCategories();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $catId       = (int)($_POST['category_id'] ?? 0);
    $price       = (float)($_POST['price'] ?? 0);
    $salePrice   = $_POST['sale_price'] ? (float)$_POST['sale_price'] : null;
    $stock       = (int)($_POST['stock'] ?? 0);
    $desc        = trim($_POST['description'] ?? '');
    $watering    = trim($_POST['care_watering'] ?? '');
    $sunlight    = trim($_POST['care_sunlight'] ?? '');
    $temperature = trim($_POST['care_temperature'] ?? '');
    $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;
    $isActive    = isset($_POST['is_active']) ? 1 : 0;
    $slug        = makeSlug($name);

    // Validation
    if (!$name)   $errors[] = 'Product name is required.';
    if (!$catId)  $errors[] = 'Please select a category.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';
    if ($stock < 0)  $errors[] = 'Stock cannot be negative.';

    // Handle image upload
    $imageFile = $product['image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $file    = $_FILES['image'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Only JPG, PNG, WEBP, GIF images allowed.';
        } elseif ($file['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Image must be smaller than 3MB.';
        } else {
            $newName   = makeSlug($name) . '-' . time() . '.' . $ext;
            $uploadDir = __DIR__ . '/../../assets/images/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                $imageFile = $newName;
            } else {
                $errors[] = 'Image upload failed. Check folder permissions.';
            }
        }
    }

    if (empty($errors)) {
        if ($id) {
            // UPDATE
            $db->prepare("
                UPDATE products SET
                    name=?, slug=?, category_id=?, price=?, sale_price=?, stock=?,
                    description=?, care_watering=?, care_sunlight=?, care_temperature=?,
                    image=?, is_featured=?, is_active=?
                WHERE id=?
            ")->execute([
                $name, $slug, $catId, $price, $salePrice, $stock,
                $desc, $watering, $sunlight, $temperature,
                $imageFile, $isFeatured, $isActive, $id
            ]);
            setFlash('success', 'Product updated successfully!');
        } else {
            // INSERT
            $db->prepare("
                INSERT INTO products
                    (name, slug, category_id, price, sale_price, stock,
                     description, care_watering, care_sunlight, care_temperature,
                     image, is_featured, is_active)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $name, $slug, $catId, $price, $salePrice, $stock,
                $desc, $watering, $sunlight, $temperature,
                $imageFile, $isFeatured, $isActive
            ]);
            setFlash('success', 'Product added successfully! 🌿');
        }
        redirect(SITE_URL . '/backend/admin/products.php');
    }

    // On error, fill form with submitted values
    $product = array_merge($product ?? [], $_POST, ['image' => $imageFile]);
}

$pageTitle = $id ? 'Edit Product' : 'Add Product';
include __DIR__ . '/admin-header.php';
?>

<div class="container-fluid py-4" style="max-width:900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><?= $id ? '✏️ Edit Product' : '➕ Add New Product' ?></h4>
        <a href="<?= SITE_URL ?>/backend/admin/products.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Products
        </a>
    </div>

    <?php if($errors): ?>
    <div class="alert alert-danger gg-alert">
        <ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        <div class="row g-4">

            <!-- Left Column -->
            <div class="col-md-8">
                <div class="gg-card p-4 mb-4">
                    <h6 class="fw-bold mb-3">Basic Information</h6>
                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" class="form-control gg-form-control"
                               value="<?= h($product['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select name="category_id" class="form-select gg-form-control" required>
                            <option value="">Select category...</option>
                            <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? '')==$cat['id']?'selected':'' ?>>
                                <?= h($cat['icon']) ?> <?= h($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control gg-form-control" rows="5"
                                  placeholder="Detailed product description..."><?= h($product['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="gg-card p-4 mb-4">
                    <h6 class="fw-bold mb-3">Pricing & Stock</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Regular Price (৳) *</label>
                            <input type="number" name="price" class="form-control gg-form-control"
                                   value="<?= h($product['price'] ?? '') ?>"
                                   min="0" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sale Price (৳) <small class="text-muted">optional</small></label>
                            <input type="number" name="sale_price" class="form-control gg-form-control"
                                   value="<?= h($product['sale_price'] ?? '') ?>"
                                   min="0" step="0.01" placeholder="Leave blank if no sale">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock Quantity *</label>
                            <input type="number" name="stock" class="form-control gg-form-control"
                                   value="<?= h($product['stock'] ?? 0) ?>"
                                   min="0" required>
                        </div>
                    </div>
                </div>

                <!-- Care Instructions -->
                <div class="gg-card p-4">
                    <h6 class="fw-bold mb-3">🌱 Plant Care Instructions</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">💧 Watering</label>
                            <input type="text" name="care_watering" class="form-control gg-form-control"
                                   value="<?= h($product['care_watering'] ?? '') ?>"
                                   placeholder="e.g. Every 3-5 days">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">☀️ Sunlight</label>
                            <input type="text" name="care_sunlight" class="form-control gg-form-control"
                                   value="<?= h($product['care_sunlight'] ?? '') ?>"
                                   placeholder="e.g. Indirect light">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">🌡️ Temperature</label>
                            <input type="text" name="care_temperature" class="form-control gg-form-control"
                                   value="<?= h($product['care_temperature'] ?? '') ?>"
                                   placeholder="e.g. 15-30°C">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Image Upload -->
                <div class="gg-card p-4 mb-4">
                    <h6 class="fw-bold mb-3">Product Image</h6>
                    <?php if(!empty($product['image'])): ?>
                    <img src="<?= SITE_URL ?>/assets/images/products/<?= h($product['image']) ?>"
                         class="img-fluid rounded mb-3" style="max-height:180px;object-fit:cover;width:100%"
                         onerror="this.src='<?= SITE_URL ?>/assets/images/plant-placeholder.svg'">
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control gg-form-control" accept="image/*"
                           onchange="previewImage(this)">
                    <img id="imagePreview" src="" alt="" class="img-fluid rounded mt-2" style="display:none;max-height:150px;object-fit:cover;width:100%">
                    <small class="text-muted d-block mt-1">Max 3MB. JPG, PNG, WEBP supported.</small>
                </div>

                <!-- Status & Settings -->
                <div class="gg-card p-4">
                    <h6 class="fw-bold mb-3">Settings</h6>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" name="is_active" id="isActive"
                               <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isActive">
                            <strong>Active</strong> — visible to customers
                        </label>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input type="checkbox" class="form-check-input" name="is_featured" id="isFeatured"
                               <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isFeatured">
                            ⭐ <strong>Featured</strong> — show on homepage
                        </label>
                    </div>
                    <button type="submit" class="btn gg-btn-green w-100 fw-bold">
                        <i class="bi bi-save me-2"></i><?= $id ? 'Update Product' : 'Add Product' ?>
                    </button>
                    <?php if($id): ?>
                    <a href="<?= SITE_URL ?>/frontend/product.php?id=<?= $id ?>" target="_blank"
                       class="btn gg-btn-outline-green w-100 mt-2">
                        <i class="bi bi-eye me-1"></i>Preview
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include __DIR__ . '/admin-footer.php'; ?>
