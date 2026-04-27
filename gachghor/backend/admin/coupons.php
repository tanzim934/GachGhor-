<?php
// ============================================================
// GachGhor — Admin: Coupon Management
// File: backend/admin/coupons.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$db = getDB();

// Delete coupon
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM coupons WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success','Coupon deleted.'); redirect(SITE_URL.'/backend/admin/coupons.php');
}

// Toggle active
if (isset($_GET['toggle'])) {
    $db->prepare("UPDATE coupons SET is_active = NOT is_active WHERE id=?")->execute([(int)$_GET['toggle']]);
    redirect(SITE_URL.'/backend/admin/coupons.php');
}

$errors = [];

// Add coupon
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $code     = strtoupper(trim($_POST['code']??''));
    $type     = $_POST['type']==='fixed'?'fixed':'percentage';
    $discount = (float)($_POST['discount']??0);
    $minOrder = (float)($_POST['min_order']??0);
    $maxUses  = (int)($_POST['max_uses']??100);
    $expiry   = $_POST['expiry_date']??'';

    if (!$code)         $errors[]='Coupon code is required.';
    if ($discount <= 0) $errors[]='Discount must be greater than 0.';
    if ($type==='percentage' && $discount > 100) $errors[]='Percentage cannot exceed 100.';

    if (empty($errors)) {
        try {
            $db->prepare("INSERT INTO coupons (code,type,discount,min_order,max_uses,expiry_date) VALUES (?,?,?,?,?,?)")
               ->execute([$code,$type,$discount,$minOrder,$maxUses,$expiry?:null]);
            setFlash('success',"Coupon <strong>$code</strong> created!");
            redirect(SITE_URL.'/backend/admin/coupons.php');
        } catch (PDOException $e) {
            $errors[]='Coupon code already exists.';
        }
    }
}

$coupons = $db->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();

$pageTitle='Coupon Management';
include __DIR__.'/admin-header.php';
?>
<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">🎟️ Coupons</h4>
    <div class="row g-4">

        <!-- Add Form -->
        <div class="col-md-4">
            <div class="gg-card p-4">
                <h6 class="fw-bold mb-3">Create New Coupon</h6>
                <?php if($errors): ?>
                <div class="alert alert-danger gg-alert py-2">
                    <?php foreach($errors as $e): ?><div><?=h($e)?></div><?php endforeach; ?>
                </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Coupon Code *</label>
                        <input type="text" name="code" class="form-control gg-form-control" placeholder="e.g. SUMMER20"
                               value="<?=h($_POST['code']??'')?>" required style="text-transform:uppercase">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discount Type *</label>
                        <select name="type" class="form-select gg-form-control">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (৳)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discount Value *</label>
                        <input type="number" name="discount" class="form-control gg-form-control"
                               value="<?=h($_POST['discount']??'')?>" min="1" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Minimum Order (৳)</label>
                        <input type="number" name="min_order" class="form-control gg-form-control"
                               value="<?=h($_POST['min_order']??0)?>" min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Max Uses</label>
                        <input type="number" name="max_uses" class="form-control gg-form-control"
                               value="<?=h($_POST['max_uses']??100)?>" min="1">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control gg-form-control"
                               value="<?=h($_POST['expiry_date']??'')?>" min="<?=date('Y-m-d')?>">
                    </div>
                    <button type="submit" class="btn gg-btn-green w-100">Create Coupon</button>
                </form>
            </div>
        </div>

        <!-- Coupons List -->
        <div class="col-md-8">
            <div class="gg-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Code</th><th>Discount</th><th>Min Order</th><th>Used</th><th>Expiry</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php if(empty($coupons)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No coupons yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach($coupons as $c): ?>
                        <tr>
                            <td><strong class="text-green"><?=h($c['code'])?></strong></td>
                            <td>
                                <?php if($c['type']==='percentage'): ?>
                                <span class="badge bg-info"><?=h($c['discount'])?>% OFF</span>
                                <?php else: ?>
                                <span class="badge bg-success">৳<?=h($c['discount'])?> OFF</span>
                                <?php endif; ?>
                            </td>
                            <td><?=formatPrice($c['min_order'])?></td>
                            <td><?=$c['used_count']?>/<?=$c['max_uses']?></td>
                            <td>
                                <?php if($c['expiry_date']): ?>
                                <small class="<?=strtotime($c['expiry_date'])<time()?'text-danger':'text-muted'?>">
                                    <?=date('d M Y',strtotime($c['expiry_date']))?>
                                </small>
                                <?php else: ?><small class="text-muted">No expiry</small><?php endif; ?>
                            </td>
                            <td>
                                <a href="?toggle=<?=$c['id']?>" class="badge <?=$c['is_active']?'bg-success':'bg-secondary'?> text-decoration-none">
                                    <?=$c['is_active']?'Active':'Inactive'?>
                                </a>
                            </td>
                            <td>
                                <a href="?delete=<?=$c['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete coupon?')">
                                    <i class="bi bi-trash3"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__.'/admin-footer.php'; ?>
