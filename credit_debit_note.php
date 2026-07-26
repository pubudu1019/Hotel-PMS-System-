<?php
include 'includes/session_check.php';
include 'includes/db.php';

$selected_res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
$message = '';
$msg_type = '';

// Process Credit/Debit Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_note'])) {
    $res_id = intval($_POST['res_id']);
    $note_type = mysqli_real_escape_string($conn, $_POST['note_type']);
    $amount = floatval($_POST['amount']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $reference = mysqli_real_escape_string($conn, $_POST['reference_no']);
    $created_by = $_SESSION['user_id'] ?? 0;
    
    if ($res_id > 0 && $amount > 0 && !empty($reason)) {
        
        // ===== STEP 1: Get folio_id for this reservation =====
        $folio_query = $conn->query("SELECT folio_id FROM folios WHERE res_id = $res_id");
        if ($folio_query && $folio = $folio_query->fetch_assoc()) {
            $folio_id = $folio['folio_id'];
        } else {
            // If no folio exists, create one
            $conn->query("INSERT INTO folios (res_id, status) VALUES ($res_id, 'Open')");
            $folio_id = $conn->insert_id;
        }
        
        // ===== STEP 2: Post to folio_transactions =====
        $trans_type = ($note_type == 'credit') ? 'rebate' : 'charge';
        $desc = ($note_type == 'credit') ? 'Credit Note: ' . $reason : 'Debit Note: ' . $reason;
        
        $insert_trans = "INSERT INTO folio_transactions 
                        (res_id, folio_id, amount, description, trans_type, reference_no, created_by, status) 
                        VALUES ($res_id, $folio_id, $amount, '$desc', '$trans_type', '$reference', $created_by, 'active')";
        
        if ($conn->query($insert_trans)) {
            $transaction_id = $conn->insert_id;
            
            // ===== STEP 3: Insert into credit_debit_notes =====
            $insert_note = "INSERT INTO credit_debit_notes 
                            (res_id, folio_id, note_type, amount, reason, reference_trans_id, created_by, status) 
                            VALUES ($res_id, $folio_id, '$note_type', $amount, '$reason', $transaction_id, $created_by, 'active')";
            
            if ($conn->query($insert_note)) {
                $message = ucfirst($note_type) . " note posted successfully!";
                $msg_type = "success";
            } else {
                $message = "Note insert error: " . $conn->error;
                $msg_type = "danger";
            }
        } else {
            $message = "Transaction insert error: " . $conn->error;
            $msg_type = "danger";
        }
    } else {
        $message = "Please fill all required fields.";
        $msg_type = "warning";
    }
    header("Location: credit_debit_note.php?res_id=$res_id&msg=" . urlencode($message) . "&type=$msg_type");
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

// Get existing notes for selected guest
$notes = [];
if ($selected_res_id > 0) {
    $note_query = $conn->query("
        SELECT * FROM credit_debit_notes 
        WHERE res_id = $selected_res_id 
        ORDER BY created_at DESC
    ");
    while ($n = $note_query->fetch_assoc()) {
        $notes[] = $n;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Credit / Debit Note - Araliya PMS</title>
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
        .btn-credit { background: #22c55e; color: white; border: none; padding: 10px 25px; font-weight: 600; border-radius: 6px; transition: 0.2s; }
        .btn-credit:hover { background: #16a34a; transform: translateY(-2px); }
        .btn-debit { background: #ef4444; color: white; border: none; padding: 10px 25px; font-weight: 600; border-radius: 6px; transition: 0.2s; }
        .btn-debit:hover { background: #dc2626; transform: translateY(-2px); }
        .form-control:focus { border-color: #0d4b68; box-shadow: 0 0 0 3px rgba(13,75,104,0.1); }
        .badge-credit { background: #d1fae5; color: #065f46; }
        .badge-debit { background: #fee2e2; color: #991b1b; }
        .note-table { width: 100%; font-size: 13px; border-collapse: collapse; }
        .note-table th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .note-table td { padding: 8px 10px; border-bottom: 1px solid #e9ecef; }
        @media (max-width: 768px) { .page-container { padding: 15px; } }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h4><i class="fas fa-file-invoice-dollar"></i> Credit / Debit Note</h4>
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
            <a href="credit_debit_note.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>

    <?php if($selected_res_id > 0): ?>
        <div class="card mt-3">
            <div class="card-header bg-light">
                <strong><i class="fas fa-plus-circle me-2"></i>Create Note</strong>
                <small class="text-muted ms-2">Credit = Balance reduces | Debit = Balance increases</small>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="res_id" value="<?= $selected_res_id ?>">
                    <input type="hidden" name="submit_note" value="1">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Note Type</label>
                            <select name="note_type" class="form-select" required>
                                <option value="credit">Credit Note (Discount)</option>
                                <option value="debit">Debit Note (Additional Charge)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Amount (LKR)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reason</label>
                            <input type="text" name="reason" class="form-control" placeholder="e.g. Compensation, Extra charge" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Reference</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="Ref #">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="submit" class="btn-credit me-2"><i class="fas fa-check-circle me-2"></i>Post Credit Note</button>
                            <button type="button" class="btn-debit" onclick="document.querySelector('select[name=note_type]').value='debit'; this.form.submit();"><i class="fas fa-check-circle me-2"></i>Post Debit Note</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if(count($notes) > 0): ?>
            <div class="mt-4">
                <h6><i class="fas fa-list-ul me-2"></i>Recent Notes</h6>
                <div style="max-height:250px; overflow-y:auto;">
                    <table class="note-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($notes as $n): ?>
                                <tr>
                                    <td><?= date('d/m H:i', strtotime($n['created_at'])) ?></td>
                                    <td><span class="badge badge-<?= $n['note_type'] ?>"><?= ucfirst($n['note_type']) ?></span></td>
                                    <td>LKR <?= number_format($n['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($n['reason']) ?></td>
                                    <td><span class="badge bg-success">Active</span></td>
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
            <h5>Select a guest to create credit/debit notes</h5>
        </div>
    <?php endif; ?>
</div>
</body>
</html>