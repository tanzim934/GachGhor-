<?php
// ============================================================
// GachGhor — Admin: Contact Messages
// File: backend/admin/messages.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$db = getDB();

// Mark as read
if (isset($_GET['read'])) {
    $db->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([(int)$_GET['read']]);
    redirect(SITE_URL . '/backend/admin/messages.php');
}
// Delete
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success','Message deleted.'); redirect(SITE_URL.'/backend/admin/messages.php');
}

$messages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
$unread   = array_filter($messages, fn($m) => !$m['is_read']);

$pageTitle = 'Messages';
include __DIR__ . '/admin-header.php';
?>
<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">
        ✉️ Contact Messages
        <?php if(count($unread)): ?>
        <span class="badge bg-danger ms-2"><?= count($unread) ?> new</span>
        <?php endif; ?>
    </h4>
    <div class="gg-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if(empty($messages)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No messages yet.</td></tr>
                <?php endif; ?>
                <?php foreach($messages as $msg): ?>
                <tr class="<?= !$msg['is_read'] ? 'fw-semibold' : '' ?>">
                    <td><?= h($msg['name']) ?><?php if(!$msg['is_read']): ?> <span class="badge bg-danger ms-1" style="font-size:0.6rem">NEW</span><?php endif; ?></td>
                    <td><a href="mailto:<?= h($msg['email']) ?>" class="text-green"><?= h($msg['email']) ?></a></td>
                    <td><?= h($msg['subject'] ?: '—') ?></td>
                    <td><small class="text-muted"><?= h(substr($msg['message'], 0, 80)) ?>...</small></td>
                    <td><small class="text-muted"><?= date('d M Y', strtotime($msg['created_at'])) ?></small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if(!$msg['is_read']): ?>
                            <a href="?read=<?= $msg['id'] ?>" class="btn btn-sm btn-outline-success" title="Mark Read"><i class="bi bi-check-lg"></i></a>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#msg-<?= $msg['id'] ?>">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="?delete=<?= $msg['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash3"></i></a>
                        </div>
                    </td>
                </tr>
                <!-- Message modal -->
                <div class="modal fade" id="msg-<?= $msg['id'] ?>">
                    <div class="modal-dialog"><div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title fw-bold"><?= h($msg['subject'] ?: 'Message') ?></h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>From:</strong> <?= h($msg['name']) ?> &lt;<?= h($msg['email']) ?>&gt;</p>
                            <p><strong>Date:</strong> <?= date('d M Y H:i', strtotime($msg['created_at'])) ?></p>
                            <hr>
                            <p class="mb-0"><?= nl2br(h($msg['message'])) ?></p>
                        </div>
                        <div class="modal-footer">
                            <a href="mailto:<?= h($msg['email']) ?>" class="btn gg-btn-green btn-sm">
                                <i class="bi bi-reply me-1"></i>Reply via Email
                            </a>
                        </div>
                    </div></div>
                </div>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/admin-footer.php'; ?>
