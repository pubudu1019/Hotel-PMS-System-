<?php
include 'includes/session_check.php';
include 'includes/db.php';

$selected_res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
$message = '';
$msg_type = '';

// Process Schedule Posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_schedule'])) {
    $res_id = intval($_POST['res_id']);
    $amount = floatval($_POST['amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = !empty($_POST['end_date']) ? mysqli_real_escape_string($conn, $_POST['end_date']) : 'NULL';
    $created_by = $_SESSION['user_id'] ?? 0;
    
    if ($res_id > 0 && $amount > 0 && !empty($description) && !empty($start_date)) {
        $end_date_sql = ($end_date != 'NULL') ? "'$end_date'" : 'NULL';
        $insert = "INSERT INTO schedule_postings 
                    (res_id, amount, description, frequency, start_date, end_date, created_by, status) 
                    VALUES ($res_id, $amount, '$description', '$frequency', '$start_date', $end_date_sql, $created_by, 'active')";
        if ($conn->query($insert)) {
            $message = "Schedule posting created successfully!";
            $msg_type = "success";
        } else {
            $message = "Database error: " . $conn->error;
            $msg_type = "danger";
        }
    } else {
        $message = "Please fill all required fields.";
        $msg_type = "warning";
    }
    header("Location: schedule_posting.php?res_id=$res_id&msg=" . urlencode($message) . "&type=$msg_type");
    exit();
}

// Handle messages
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $msg_type = $_GET['type'] ?? 'info';
}

$guests = $conn->query("
    SELECT r.res_id, r.guest_name, rm.room_number 
    FROM reservations r 
    JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.status = 'Checked-In'
    ORDER BY rm.room_number ASC
");

// Get schedule postings for selected guest
$schedules = [];
if ($selected_res_id > 0) {
    $schedule_query = $conn->query("
        SELECT * FROM schedule_postings 
        WHERE res_id = $selected_res_id 
        ORDER BY created_at DESC
    ");
    while ($s = $schedule_query->fetch_assoc()) {
        $schedules[] = $s;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule Posting - Araliya PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .page-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); max-width: 1000px; margin: 0 auto; }
        .page-header { border-bottom: 2px solid #eef2f5; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .page-header h4 { color: #0d4b68; font-weight: 700; margin: 0; }
        .page-header h4 i { color: #fbbf24; margin-right: 10px; }
        .back-btn { background: none; border: none; color: #0d4b68; font-weight: 600; cursor: pointer; font-size: 14px; }
        .back-btn:hover { text-decoration: underline; }
        .btn-submit { background: #0d4b68; color: white; border: none; padding: 10px 30px; font-weight: 600; border-radius: 6px; transition: 0.2s; }
        .btn-submit:hover { background: #1a6f8e; transform: translateY(-2px); }
        .form-control:focus { border-color: #0d4b68; box-shadow: 0 0 0 3px rgba(13,75,104,0.1); }
        .schedule-table { width: 100%; font-size: 13px; border-collapse: collapse; }
        .schedule-table th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .schedule-table td { padding: 8px 10px; border-bottom: 1px solid #e9ecef; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-completed { background: #e5e7eb; color: #4b5563; }
        @media (max-width: 768px) { .page-container { padding: 15px; } }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h4><i class="fas fa-clock"></i> Schedule Posting</h4>
        <button class="back-btn" onclick="history.back()"><i class="fas fa-arrow-left me-2"></i>Back</button>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="GET" action="" class="row g-3 align-items-end mb-4">
        <div class="col-md-6">
            <label class="form-label fw-bold">Select Guest</label>
            <select name="res_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Choose a guest --</option>
                <?php if($guests && $guests->num_rows > 0): ?>
                    <?php while($g = $guests->fetch_assoc()): ?>
                        <option value="<?= $g['res_id'] ?>" <?= ($selected_res_id == $g['res_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['guest_name']) ?> (Room <?= $g['room_number'] ?>)
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="" disabled>No checked-in guests</option>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Load</button>
        </div>
        <div class="col-md-2">
            <a href="schedule_posting.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>

    <?php if($selected_res_id > 0): ?>
        <div class="card mt-3">
            <div class="card-header bg-light"><strong><i class="fas fa-calendar-plus me-2"></i>Create Schedule Posting</strong></div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="res_id" value="<?= $selected_res_id ?>">
                    <input type="hidden" name="submit_schedule" value="1">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Amount (LKR)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Frequency</label>
                            <select name="frequency" class="form-select" required>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="one-time">One-time</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="e.g. Daily room charge" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">End Date (Optional)</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">&nbsp;</label>
                            <button type="submit" class="btn-submit w-100"><i class="fas fa-check-circle me-2"></i>Create Schedule</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if(count($schedules) > 0): ?>
            <div class="mt-4">
                <h6><i class="fas fa-list-ul me-2"></i>Schedule Postings</h6>
                <div style="max-height:250px; overflow-y:auto;">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Frequency</th>
                                <th>Start</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($schedules as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['description']) ?></td>
                                    <td>LKR <?= number_format($s['amount'], 2) ?></td>
                                    <td><?= ucfirst($s['frequency']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($s['start_date'])) ?></td>
                                    <td><span class="badge badge-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php elseif($selected_res_id == 0): ?>
        <div class="text-center text-muted py-4">
            <i class="fas fa-user-search fa-3x d-block mb-3"></i>
            <h5>Select a guest to create schedule postings</h5>
        </div>
    <?php endif; ?>
</div>
</body>
</html>