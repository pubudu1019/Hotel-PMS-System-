<?php
include 'includes/session_check.php';
include 'includes/db.php';

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// NOTE: adjust to match your real session role variable.
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
if (!$is_admin) {
    die("Access denied. Admins only.");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$sql = "SELECT r.*, rm.room_number, rm.room_type
        FROM reservations r
        LEFT JOIN rooms rm ON r.room_id = rm.room_id
        WHERE r.is_deleted = 1
        ORDER BY r.deleted_at DESC";
$query = $conn->query($sql);
if (!$query) {
    die("Query Error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deleted Check-out History (Trash)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; font-size: 13px; }
        .top-navbar { background-color: #12536d; color: white; padding: 10px 20px; }
        .table-container { background: #fff; padding: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table thead th { background-color: #f8fafc; color: #12536d; font-weight: 600; }
        tr.row-restoring { opacity: 0.4; transition: opacity 0.3s; }
    </style>
</head>
<body>

<div class="top-navbar d-flex justify-content-between align-items-center">
    <h5 class="m-0 fw-bold"><i class="fas fa-trash-restore me-2"></i> DELETED CHECK-OUT HISTORY</h5>
    <a href="checkout_history.php" class="btn btn-sm btn-outline-light">
        <i class="fas fa-arrow-left me-1"></i> Back to History
    </a>
</div>

<div class="container-fluid mt-3 px-4">
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle bg-white">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-out Date</th>
                        <th>Deleted At</th>
                        <th>Deleted By</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($query->num_rows > 0): $i = 1; while ($row = $query->fetch_assoc()): ?>
                    <tr id="row-<?= (int)$row['res_id'] ?>">
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['guest_name']) ?></td>
                        <td><?= htmlspecialchars($row['room_number'] ?? 'N/A') ?></td>
                        <td><?= date('d/m/Y', strtotime($row['check_out'])) ?></td>
                        <td><?= $row['deleted_at'] ? date('d/m/Y h:i A', strtotime($row['deleted_at'])) : '-' ?></td>
                        <td><?= htmlspecialchars($row['deleted_by'] ?? '-') ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-success btn-restore-row" data-id="<?= (int)$row['res_id'] ?>">
                                <i class="fas fa-trash-restore me-1"></i> Restore
                            </button>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Trash is empty.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="toastArea" style="position:fixed; top:15px; right:15px; z-index:2000;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CSRF_TOKEN = "<?= $csrf_token ?>";

function showToast(message, type = 'success') {
    const box = document.createElement('div');
    box.className = `alert alert-${type} shadow-sm`;
    box.style.minWidth = '260px';
    box.textContent = message;
    document.getElementById('toastArea').appendChild(box);
    setTimeout(() => box.remove(), 3500);
}

document.querySelectorAll('.btn-restore-row').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        btn.disabled = true;
        try {
            const res = await fetch('ajax_restore_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `res_id=${encodeURIComponent(id)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
            });
            const data = await res.json();
            if (data.success) {
                const row = document.getElementById('row-' + id);
                if (row) {
                    row.classList.add('row-restoring');
                    setTimeout(() => row.remove(), 300);
                }
                showToast('Record restored.');
            } else {
                showToast(data.message || 'Restore failed.', 'danger');
                btn.disabled = false;
            }
        } catch (e) {
            showToast('Network error while restoring.', 'danger');
            btn.disabled = false;
        }
    });
});
</script>
</body>
</html>