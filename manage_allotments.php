<?php
include 'includes/session_check.php';
include 'includes/db.php';

// Release date expire වුණු Active allotments auto Expired කරනවා
$conn->query("UPDATE allotments SET status='Expired' WHERE status='Active' AND release_date < CURDATE()");

$result = $conn->query("SELECT * FROM allotments ORDER BY allotment_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Allotments - PMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { background:#f4f7f6; font-family:'Segoe UI',sans-serif; font-size:13px; }
    .top-navbar { background-color:#0d4b68; color:#fff; padding:10px 20px; }
    .panel { background:#fff; padding:20px; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,0.06); }
    .progress { height:18px; }
    .badge-active { background:#10b981; }
    .badge-released { background:#64748b; }
    .badge-expired { background:#ef4444; }
</style>
</head>
<body>

<div class="top-navbar d-flex justify-content-between align-items-center">
    <div>
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-light me-3 py-1 px-3" style="font-size: 11px;">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <strong>MANAGE ALLOTMENTS</strong>
    </div>
    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#addAllotmentModal">
        <i class="fas fa-plus me-1"></i> New Allotment
    </button>
</div>

<div class="container-fluid mt-4">
    <div class="panel">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Company / Agent</th>
                    <th>Room Type</th>
                    <th>Period</th>
                    <th>Release Date</th>
                    <th>Blocked</th>
                    <th>Picked Up</th>
                    <th>Remaining</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()):
                    $remaining = $row['total_blocked'] - $row['rooms_picked'];
                    $percent = $row['total_blocked'] > 0 ? round(($row['rooms_picked'] / $row['total_blocked']) * 100) : 0;
                    $badge_class = $row['status']=='Active' ? 'badge-active' : ($row['status']=='Released' ? 'badge-released' : 'badge-expired');
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['company_name']) ?></td>
                    <td><?= htmlspecialchars($row['room_type']) ?></td>
                    <td><?= date('d/m/Y', strtotime($row['date_from'])) ?> - <?= date('d/m/Y', strtotime($row['date_to'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($row['release_date'])) ?></td>
                    <td class="text-center"><?= $row['total_blocked'] ?></td>
                    <td class="text-center"><?= $row['rooms_picked'] ?></td>
                    <td class="text-center fw-bold <?= $remaining<=0 ? 'text-danger' : 'text-success' ?>"><?= $remaining ?></td>
                    <td style="width:140px;">
                        <div class="progress">
                            <div class="progress-bar bg-info" style="width:<?= $percent ?>%"><?= $percent ?>%</div>
                        </div>
                    </td>
                    <td><span class="badge <?= $badge_class ?>"><?= $row['status'] ?></span></td>
                    <td class="text-end">
                        <?php if($row['status']=='Active'): ?>
                        <a href="process_allotment.php?action=release&id=<?= $row['allotment_id'] ?>" 
                           class="btn btn-sm btn-outline-secondary" 
                           onclick="return confirm('Release this allotment manually?')">
                           <i class="fas fa-unlock"></i> Release
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No allotments created yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addAllotmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="process_allotment.php">
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h6 class="modal-title">New Room Allotment</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label small fw-bold">Company / Agent Name</label>
                <input type="text" name="company_name" class="form-control" required placeholder="e.g. Jetwing Travels">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Room Type</label>
                <select name="room_type" class="form-select" required>
                    <?php
                    $types = $conn->query("SELECT type_name FROM room_types");
                    while($t = $types->fetch_assoc()) echo "<option value='".$t['type_name']."'>".$t['type_name']."</option>";
                    ?>
                </select>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold">From Date</label>
                    <input type="date" name="date_from" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">To Date</label>
                    <input type="date" name="date_to" class="form-control" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold">Rooms Blocked</label>
                    <input type="number" name="total_blocked" class="form-control" min="1" value="5" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Release Date</label>
                    <input type="date" name="release_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+5 days')) ?>">
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label small fw-bold">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Optional contract notes..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-sm btn-primary">Save Allotment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>