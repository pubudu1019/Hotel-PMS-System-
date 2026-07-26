<?php
include 'includes/session_check.php';
include 'includes/db.php';

$selected_res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
$message = '';
$msg_type = '';

// Process Service Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_service'])) {
    $res_id = intval($_POST['res_id']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $amount = floatval($_POST['amount']);
    $created_by = $_SESSION['user_id'] ?? 0;
    
    if ($res_id > 0 && !empty($service_type) && !empty($description)) {
        // Insert into guest_services table
        $insert = "INSERT INTO guest_services 
                    (res_id, room_id, service_type, description, priority, service_charge, created_by, status) 
                    SELECT $res_id, room_id, '$service_type', '$description', '$priority', $amount, $created_by, 'pending'
                    FROM reservations WHERE res_id = $res_id";
        if ($conn->query($insert)) {
            // Also post to folio if amount > 0
            if ($amount > 0) {
                $conn->query("INSERT INTO folio_transactions 
                              (res_id, amount, description, trans_type, reference_no, created_by, status) 
                              VALUES ($res_id, $amount, 'Service: $service_type', 'charge', 'SERVICE', $created_by, 'active')");
            }
            $message = "Service request posted successfully!";
            $msg_type = "success";
        } else {
            $message = "Database error: " . $conn->error;
            $msg_type = "danger";
        }
    } else {
        $message = "Please fill all required fields.";
        $msg_type = "warning";
    }
    header("Location: guest_service_desk.php?res_id=$res_id&msg=" . urlencode($message) . "&type=$msg_type");
    exit();
}

// Handle messages
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $msg_type = $_GET['type'] ?? 'info';
}

// Get guests
$guests = $conn->query("
    SELECT r.res_id, r.guest_name, rm.room_number 
    FROM reservations r 
    JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.status = 'Checked-In'
    ORDER BY rm.room_number ASC
");

// Get services for selected guest
$services = [];
if ($selected_res_id > 0) {
    $service_query = $conn->query("
        SELECT * FROM guest_services 
        WHERE res_id = $selected_res_id 
        ORDER BY created_at DESC
    ");
    while ($s = $service_query->fetch_assoc()) {
        $services[] = $s;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest Service Desk - Araliya PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .page-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); max-width: 1200px; margin: 0 auto; }
        .page-header { border-bottom: 2px solid #eef2f5; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .page-header h4 { color: #0d4b68; font-weight: 700; margin: 0; }
        .page-header h4 i { color: #fbbf24; margin-right: 10px; }
        .back-btn { background: none; border: none; color: #0d4b68; font-weight: 600; cursor: pointer; font-size: 14px; }
        .back-btn:hover { text-decoration: underline; }
        .form-control:focus { border-color: #0d4b68; box-shadow: 0 0 0 3px rgba(13,75,104,0.1); }
        .btn-submit { background: #0d4b68; color: white; border: none; padding: 10px 30px; font-weight: 600; border-radius: 6px; transition: 0.2s; }
        .btn-submit:hover { background: #1a6f8e; transform: translateY(-2px); }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-in_progress { background: #bfdbfe; color: #1e40af; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .service-table { width: 100%; font-size: 13px; border-collapse: collapse; }
        .service-table th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .service-table td { padding: 8px 10px; border-bottom: 1px solid #e9ecef; }
        @media (max-width: 768px) { .page-container { padding: 15px; } }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h4><i class="fas fa-headset"></i> Guest Service Desk</h4>
        <button class="back-btn" onclick="history.back()"><i class="fas fa-arrow-left me-2"></i>Back</button>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Guest Selection -->
    <form method="GET" action="" class="row g-3 align-items-end mb-4">
        <div class="col-md-5">
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
            <a href="guest_service_desk.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>

    <?php if($selected_res_id > 0): ?>
        <!-- Service Form -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <strong><i class="fas fa-plus-circle me-2"></i>New Service Request</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="res_id" value="<?= $selected_res_id ?>">
                    <input type="hidden" name="submit_service" value="1">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Service Type</label>
                            <select name="service_type" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="Room Service">Room Service (Food)</option>
                                <option value="Laundry">Laundry</option>
                                <option value="Spa">Spa / Massage</option>
                                <option value="Transport">Transport / Taxi</option>
                                <option value="Housekeeping">Housekeeping Request</option>
                                <option value="Maintenance">Maintenance Repair</option>
                                <option value="Other">Other Service</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Priority</label>
                            <select name="priority" class="form-select" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Charge (LKR)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">&nbsp;</label>
                            <button type="submit" class="btn btn-submit w-100">Post Service</button>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Description / Details</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Describe the service request..." required></textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Services -->
        <?php if(count($services) > 0): ?>
            <div class="mt-4">
                <h6><i class="fas fa-list-ul me-2"></i>Service Requests</h6>
                <div style="max-height:250px; overflow-y:auto;">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Charge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($services as $s): ?>
                                <tr>
                                    <td><?= date('d/m H:i', strtotime($s['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($s['service_type']) ?></td>
                                    <td><span class="badge <?= $s['priority'] == 'urgent' ? 'bg-danger' : ($s['priority'] == 'high' ? 'bg-warning' : 'bg-secondary') ?>"><?= ucfirst($s['priority']) ?></span></td>
                                    <td><span class="badge badge-<?= $s['status'] ?>"><?= ucfirst(str_replace('_', ' ', $s['status'])) ?></span></td>
                                    <td>LKR <?= number_format($s['service_charge'] ?? 0, 2) ?></td>
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
            <h5>Select a guest to manage services</h5>
            <p>Choose a checked-in guest from the dropdown above.</p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>