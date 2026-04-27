<?php
// ============================================================
// GachGhor — Admin: User Management
// File: backend/admin/users.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$db = getDB();

// Handle actions
if (isset($_GET['block'])) {
    $db->prepare("UPDATE users SET is_blocked=1 WHERE id=? AND role='customer'")->execute([(int)$_GET['block']]);
    setFlash('success','User blocked.'); redirect(SITE_URL.'/backend/admin/users.php');
}
if (isset($_GET['unblock'])) {
    $db->prepare("UPDATE users SET is_blocked=0 WHERE id=?")->execute([(int)$_GET['unblock']]);
    setFlash('success','User unblocked.'); redirect(SITE_URL.'/backend/admin/users.php');
}
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $db->prepare("DELETE FROM cart WHERE user_id=?")->execute([$delId]);
    $db->prepare("DELETE FROM wishlist WHERE user_id=?")->execute([$delId]);
    $db->prepare("DELETE FROM users WHERE id=? AND role='customer'")->execute([$delId]);
    setFlash('success','User deleted.'); redirect(SITE_URL.'/backend/admin/users.php');
}

$search = trim($_GET['q'] ?? '');
$page   = max(1,(int)($_GET['page'] ?? 1));
$perPage= 15;
$offset = ($page-1)*$perPage;

$where  = ["role='customer'"];
$params = [];
if ($search) { $where[]="(name LIKE ? OR email LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; }
$whereSQL = "WHERE ".implode(" AND ",$where);

$total = $db->prepare("SELECT COUNT(*) FROM users $whereSQL"); $total->execute($params);
$total = (int)$total->fetchColumn(); $totalPages = ceil($total/$perPage);

$users = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) as order_count FROM users u $whereSQL ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset");
$users->execute($params); $users=$users->fetchAll();

$pageTitle='User Management';
include __DIR__.'/admin-header.php';
?>
<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">👥 Customers <span class="badge bg-success"><?=$total?></span></h4>
    <div class="gg-card p-3 mb-4">
        <form method="GET" class="row g-2">
            <div class="col-md-5"><input type="search" name="q" class="form-control gg-form-control" placeholder="Search name or email..." value="<?=h($search)?>"></div>
            <div class="col-auto"><button type="submit" class="btn gg-btn-green">Search</button> <a href="?" class="btn btn-outline-secondary ms-1">Reset</a></div>
        </form>
    </div>
    <div class="gg-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Joined</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if(empty($users)): ?><tr><td colspan="7" class="text-center py-4 text-muted">No users found.</td></tr><?php endif; ?>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><div class="fw-semibold"><?=h($u['name'])?></div></td>
                    <td><small><?=h($u['email'])?></small></td>
                    <td><small><?=h($u['phone']??'-')?></small></td>
                    <td><span class="badge bg-success"><?=$u['order_count']?></span></td>
                    <td><small><?=date('d M Y',strtotime($u['created_at']))?></small></td>
                    <td>
                        <?php if($u['is_blocked']): ?>
                        <span class="badge bg-danger">Blocked</span>
                        <?php else: ?>
                        <span class="badge bg-success">Active</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if($u['is_blocked']): ?>
                            <a href="?unblock=<?=$u['id']?>" class="btn btn-sm btn-outline-success" title="Unblock"><i class="bi bi-person-check"></i></a>
                            <?php else: ?>
                            <a href="?block=<?=$u['id']?>" class="btn btn-sm btn-outline-warning" title="Block" onclick="return confirm('Block this user?')"><i class="bi bi-person-dash"></i></a>
                            <?php endif; ?>
                            <a href="?delete=<?=$u['id']?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Permanently delete this user?')"><i class="bi bi-trash3"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if($totalPages>1): ?>
        <div class="p-3 border-top">
            <nav><ul class="pagination pagination-sm mb-0">
                <?php for($i=1;$i<=$totalPages;$i++): ?>
                <li class="page-item <?=$i===$page?'active':''?>"><a class="page-link" href="?<?=http_build_query(array_merge($_GET,['page'=>$i]))?>"><?=$i?></a></li>
                <?php endfor; ?>
            </ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__.'/admin-footer.php'; ?>
