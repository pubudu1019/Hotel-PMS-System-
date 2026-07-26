<?php
include 'includes/session_check.php';
include 'includes/db.php';

$message = '';
$msg_type = '';

// Process Walk-in Posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_walkin'])) {
    $guest_name = mysqli_real_escape_string($conn, $_POST['guest_name']);
    $amount = floatval($_POST['amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $reference = mysqli_real_escape_string($conn, $_POST['reference_no']);
    $created_by = $_SESSION['user_id'] ?? 0;
    
    if (!empty($guest_name) && $amount > 0 && !empty($description)) {
        // Create a temporary reservation for walk-in
        $insert_res = "INSERT INTO reservations 
                        (guest_name, status, created_by) 
                        VALUES ('$guest_name', 'Pending', $created_by)";
        if ($conn->query($insert_res)) {
            $res_id = $conn->insert_id;
            // Post to folio
            $insert = "INSERT INTO folio_transactions 
                        (res_id, amount, description, trans_type, reference_no, created_by, status) 
                        VALUES ($res_id, $amount, '$description', 'charge', '$reference', $created_by, 'active')";
            if ($conn->query($insert)) {
                $message = "Walk-in posting created successfully! (Res #: $res_id)";
                $msg_type = "success";
            } else {
                $message = "Database error: " . $conn->error;
                $msg_type = "danger";
            }
        } else {
            $message = "Database error: " . $conn->error;
            $msg_type = "danger";
        }
    } else {
        $message = "Please fill all required fields.";
        $msg_type = "warning";
    }
    header("Location: postings_walkin.php?msg=" . urlencode($message) . "&type=$msg_type");
    exit();
}

// Handle messages
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $msg_type = $_GET['type'] ?? 'info';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Postings - Walk-In - Araliya PMS</title>
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
        .info-box { background: #f0f7fa; padding: 15px; border-radius: 8px; border-left: 4px solid #0d4b68; }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h4><i class="fas fa-user-plus"></i> Postings - Walk-In</h4>
        <button class="back-btn" onclick="history.back()"><i class="fas fa-arrow-left me-2"></i>Back</button>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="info-box mb-4">
        <i class="fas fa-info-circle me-2" style="color:#0d4b68;"></i>
        <strong>Walk-In Posting:</strong> Create a charge for a guest without a reservation. This will create a temporary reservation and post the charge to a new folio.
    </div>

    <div class="card mt-3">
        <div class="card-header bg-light"><strong><i class="fas fa-plus-circle me-2"></i>Walk-In Charge</strong></div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="submit_walkin" value="1">
                <div class="mb-3">
                    <label class="form-label fw-bold">Guest Name</label>
                    <input type="text" name="guest_name" class="form-control" placeholder="Enter guest name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Amount (LKR)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g. Walk-in Room Charge" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Reference (Optional)</label>
                    <input type="text" name="reference_no" class="form-control" placeholder="Ref #">
                </div>
                <button type="submit" class="btn-submit w-100"><i class="fas fa-check-circle me-2"></i>Post Walk-in Charge</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>