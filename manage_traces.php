<?php
include 'includes/session_check.php';
include 'includes/db.php';

$filter_status = isset($_GET['status']) ? $_GET['status'] : 'Pending';

$sql = "SELECT t.*, r.guest_name, r.res_no, r.status AS res_status 
        FROM traces t 
        LEFT JOIN reservations r ON t.res_id = r.res_id 
        WHERE 1=1";

if ($filter_status !== 'All') {
    $filter_status_esc = mysqli_real_escape_string($conn, $filter_status);
    $sql .= " AND t.status = '$filter_status_esc'";
}

$sql .= " ORDER BY t.trace_date ASC, t.trace_id DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Traces - PMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { background:#f4f7f6; font-family:'Segoe UI',sans-serif; font-size:13px; }
    .top-navbar { background-color:#0d4b68; color:#fff; padding:10px 20px; }
    .panel { background:#fff; padding:20px; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,0.06); }
    .badge-pending { background:#f59e0b; }
    .badge-completed { background:#10b981; }
    .badge-cancelled { background:#ef4444; }
    .row-overdue { background-color:#fce8e6 !important; }
    .filter-tabs a { margin-right:5px; }
</style>
</head>
<body>

<div class="top-navbar d-flex justify-content-between align-items-center">
    <div>
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-light me-3 py-1 px-3" style="font-size: 11px;">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <strong>MANAGE TRACES</strong>
    </div>
    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#addTraceModal">
        <i class="fas fa-plus me-1"></i> New Trace
    </button>
</div>

<div class="container-fluid mt-4">
    <div class="panel">

        <div class="filter-tabs mb-3">
            <a href="?status=Pending" class="btn btn-sm <?= $filter_status=='Pending' ? 'btn-warning' : 'btn-outline-secondary' ?>">Pending</a>
            <a href="?status=Completed" class="btn btn-sm <?= $filter_status=='Completed' ? 'btn-success' : 'btn-outline-secondary' ?>">Completed</a>
            <a href="?status=Cancelled" class="btn btn-sm <?= $filter_status=='Cancelled' ? 'btn-danger' : 'btn-outline-secondary' ?>">Cancelled</a>
            <a href="?status=All" class="btn btn-sm <?= $filter_status=='All' ? 'btn-dark' : 'btn-outline-secondary' ?>">All</a>
        </div>

        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Trace Date</th>
                    <th>Department</th>
                    <th>Related Guest</th>
                    <th>Description</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()):
                    $is_overdue = ($row['status']=='Pending' && strtotime($row['trace_date']) < strtotime(date('Y-m-d')));
                    $badge_class = $row['status']=='Pending' ? 'badge-pending' : ($row['status']=='Completed' ? 'badge-completed' : 'badge-cancelled');

                    // Guest name එක click කළාම reservation status එක අනුව target page එක තීරණය කිරීම
                    if ($row['res_status'] === 'Checked-In') {
                        $guest_link = 'inhouse_rooms.php?id=' . $row['res_id'];
                    } else {
                        $guest_link = 'arrivals.php?id=' . $row['res_id'];
                    }
                ?>
                <tr class="<?= $is_overdue ? 'row-overdue' : '' ?>">
                    <td>
                        <?= date('d/m/Y', strtotime($row['trace_date'])) ?>
                        <?php if($is_overdue): ?><br><small class="text-danger"><i class="fas fa-exclamation-circle"></i> Overdue</small><?php endif; ?>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['department']) ?></span></td>
                    <td>
                        <?php if($row['res_id']): ?>
                            <a href="<?= $guest_link ?>"><?= htmlspecialchars($row['guest_name']) ?></a>
                        <?php else: ?>
                            <span class="text-muted">General</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= htmlspecialchars($row['assigned_to'] ?? '-') ?></td>
                    <td><span class="badge <?= $badge_class ?>"><?= $row['status'] ?></span></td>
                    <td class="text-end">
                        <?php if($row['status']=='Pending'): ?>
                        <a href="process_trace.php?action=complete&id=<?= $row['trace_id'] ?>&status=<?= $filter_status ?>" class="btn btn-sm btn-outline-success" title="Mark Completed"><i class="fas fa-check"></i></a>
                        <a href="process_trace.php?action=cancel&id=<?= $row['trace_id'] ?>&status=<?= $filter_status ?>" class="btn btn-sm btn-outline-danger" title="Cancel" onclick="return confirm('Cancel this trace?')"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                        <a href="process_trace.php?action=delete&id=<?= $row['trace_id'] ?>&status=<?= $filter_status ?>" class="btn btn-sm btn-outline-secondary" title="Delete" onclick="return confirm('Delete permanently?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No traces found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addTraceModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="process_trace.php">
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h6 class="modal-title">New Trace / Reminder</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label small fw-bold">Trace Date</label>
                <input type="date" name="trace_date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Department</label>
                <select name="department" class="form-select">
                    <option value="General">General</option>
                    <option value="Front Desk">Front Desk</option>
                    <option value="Housekeeping">Housekeeping</option>
                    <option value="F&B">F&amp;B</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Accounts">Accounts</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Related Reservation (Optional)</label>
                <select name="res_id" class="form-select">
                    <option value="">-- General Task (No Guest) --</option>
                    <?php
                    $reservations = $conn->query("SELECT res_id, guest_name, check_in FROM reservations WHERE status != 'Checked-Out' AND status != 'Cancelled' ORDER BY check_in ASC");
                    while ($r = $reservations->fetch_assoc()) {
                        echo '<option value="'.$r['res_id'].'">'.htmlspecialchars($r['guest_name']).' ('.date('d/m/Y', strtotime($r['check_in'])).')</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Description</label>
                <textarea name="description" class="form-control" rows="3" required placeholder="e.g. Arrange welcome fruit basket for VIP guest"></textarea>
            </div>
            <div class="mb-2">
                <label class="form-label small fw-bold">Assigned To (Optional)</label>
                <input type="text" name="assigned_to" class="form-control" placeholder="e.g. Housekeeping Supervisor">
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-sm btn-primary">Save Trace</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>