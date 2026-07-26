<?php
include '../includes/session_check.php';
include '../includes/db.php';

// Only admin/manager can manage staff
$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if (!in_array($user_role, ['admin', 'manager'])) {
    die("Access denied. Admin/Manager only.");
}

$message = '';
$msg_type = '';

// Add staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    if (!empty($name)) {
        $conn->query("INSERT INTO housekeeping_staff (full_name, contact, is_active) VALUES ('$name', '$contact', 1)");
        $message = "Staff added!";
        $msg_type = "success";
    }
}

// Toggle staff status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE housekeeping_staff SET is_active = NOT is_active WHERE staff_id = $id");
    $message = "Staff status updated!";
    $msg_type = "success";
}

// Delete staff
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM housekeeping_staff WHERE staff_id = $id");
    $message = "Staff deleted!";
    $msg_type = "success";
}

$staff = $conn->query("SELECT * FROM housekeeping_staff ORDER BY full_name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Housekeeping Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background:#f4f7f6; padding:20px;">
<div class="container" style="max-width:800px; background:white; padding:30px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.06);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-users me-2" style="color:#fbbf24;"></i>Housekeeping Staff</h4>
        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= $message ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-light"><strong>Add Staff Member</strong></div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2">
                    <div class="col-md-5"><input type="text" name="full_name" class="form-control" placeholder="Full Name" required></div>
                    <div class="col-md-4"><input type="text" name="contact" class="form-control" placeholder="Contact Number"></div>
                    <div class="col-md-3"><button type="submit" name="add_staff" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Add</button></div>
                </div>
            </form>
        </div>
    </div>
    
    <table class="table table-hover">
        <thead><tr><th>#</th><th>Name</th><th>Contact</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            <?php if($staff && $staff->num_rows > 0): $i=1; while($s = $staff->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['contact'] ?? '—') ?></td>
                <td><span class="badge <?= $s['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                    <a href="?toggle=<?= $s['staff_id'] ?>" class="btn btn-sm btn-outline-warning" title="Toggle Status"><i class="fas fa-sync"></i></a>
                    <a href="?delete=<?= $s['staff_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="5" class="text-center text-muted">No staff members</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>