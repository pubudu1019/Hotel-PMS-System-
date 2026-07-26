<?php
include 'includes/session_check.php';
include 'includes/db.php';

$selected_res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
$message = '';
$msg_type = '';

// Process General Room Posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_posting'])) {
    $res_id = intval($_POST['res_id']);
    $amount = floatval($_POST['amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $reference = mysqli_real_escape_string($conn, $_POST['reference_no']);
    $created_by = $_SESSION['user_id'] ?? 0;
    $post_type = mysqli_real_escape_string($conn, $_POST['post_type']);
    
    if ($res_id > 0 && $amount > 0 && !empty($description)) {
        $trans_type = ($post_type == 'charge') ? 'charge' : 'payment';
        $insert = "INSERT INTO folio_transactions 
                    (res_id, amount, description, trans_type, reference_no, created_by, status) 
                   VALUES ($res_id, $amount, '$description', '$trans_type', '$reference', $created_by, 'active')";
        if ($conn->query($insert)) {
            $message = "General posting posted successfully!";
            $msg_type = "success";
        } else {
            $message = "Database error: " . $conn->error;
            $msg_type = "danger";
        }
    } else {
        $message = "Please fill all required fields.";
        $msg_type = "warning";
    }
    header("Location: general_posting_room.php?res_id=$res_id&msg=" . urlencode($message) . "&type=$msg_type");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>General Posting - Room - Araliya PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .page-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); max-width: 800px; margin: 0 auto; }
        .page-header { border-bottom: 2px solid #eef2f5; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .page-header h4 { color: #0d4b68; font-weight: 700; margin: 0; }
        .page-header h4 i { color: #fbbf24; margin-right: 10px; }
        .back-btn { background: none; border: none; color: #0d4b68; font-weight: 600; cursor: pointer; font-size: 14px; }
        .back-btn:hover { text-decoration: underline; }
        .btn-submit { background: #0d4b68; color: white; border: none; padding: 10px 30px; font-weight: 600; border-radius: 6px; transition: 0.2s; }
        .btn-submit:hover { background: #1a6f8e; transform: translateY(-2px); }
        .form-control:focus { border-color: #0d4b68; box-shadow: 0 0 0 3px rgba(13,75,104,0.1); }
        .btn-charge { background: #ef4444; color: white; border: none; padding: 8px 20px; font-weight: 600; border-radius: 6px; }
        .btn-charge:hover { background: #dc2626; }
        .btn-payment { background: #22c55e; color: white; border: none; padding: 8px 20px; font-weight: 600; border-radius: 6px; }
        .btn-payment:hover { background: #16a34a; }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h4><i class="fas fa-edit"></i> General Posting - Room</h4>
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
            <a href="general_posting_room.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>

    <?php if($selected_res_id > 0): ?>
        <div class="card mt-3">
            <div class="card-header bg-light">
                <strong><i class="fas fa-plus-circle me-2"></i>General Posting</strong>
                <small class="text-muted ms-2">Charge = Add to balance | Payment = Reduce balance</small>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="res_id" value="<?= $selected_res_id ?>">
                    <input type="hidden" name="submit_posting" value="1">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Posting Type</label>
                        <select name="post_type" class="form-select" required>
                            <option value="charge">Charge (Add to Bill)</option>
                            <option value="payment">Payment (Reduce Bill)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount (LKR)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Extra bed, Service charge" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reference (Optional)</label>
                        <input type="text" name="reference_no" class="form-control" placeholder="Ref #">
                    </div>
                    <button type="submit" class="btn-submit w-100"><i class="fas fa-check-circle me-2"></i>Post</button>
                </form>
            </div>
        </div>
    <?php elseif($selected_res_id == 0): ?>
        <div class="text-center text-muted py-4">
            <i class="fas fa-user-search fa-3x d-block mb-3"></i>
            <h5>Select a guest to post general charges</h5>
        </div>
    <?php endif; ?>
</div>
</body>
</html>