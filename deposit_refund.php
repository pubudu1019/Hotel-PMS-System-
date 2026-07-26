<?php
include 'includes/session_check.php';
include 'includes/db.php';

$selected_res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
$message = '';
$msg_type = '';

// Process refund submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_refund'])) {
    $res_id = intval($_POST['res_id']);
    $amount = floatval($_POST['amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $reference = mysqli_real_escape_string($conn, $_POST['reference_no']);
    $created_by = $_SESSION['user_id'] ?? 0;
    
    if ($res_id > 0 && $amount > 0 && !empty($description)) {
        $insert = "INSERT INTO folio_transactions 
                    (res_id, amount, description, trans_type, reference_no, created_by, status) 
                   VALUES ($res_id, $amount, '$description', 'payment', '$reference', $created_by, 'active')";
        if ($conn->query($insert)) {
            $message = "Deposit refund processed successfully!";
            $msg_type = "success";
        } else {
            $message = "Database error: " . $conn->error;
            $msg_type = "danger";
        }
    } else {
        $message = "Please fill all required fields.";
        $msg_type = "warning";
    }
    header("Location: deposit_refund.php?res_id=$res_id&msg=" . urlencode($message) . "&type=$msg_type");
    exit();
}

// Handle messages
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $msg_type = $_GET['type'] ?? 'info';
}

// Get all checked-in guests
$guests = $conn->query("
    SELECT r.res_id, r.guest_name, rm.room_number, r.room_rate 
    FROM reservations r 
    JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.status = 'Checked-In'
    ORDER BY rm.room_number ASC
");

// Folio data
$folio_data = null;
$transactions = [];
$total_charges = $total_payments = $total_advance = $total_rebate = 0;
$balance = 0;
$advance_amount = 0;

if ($selected_res_id > 0) {
    $res_query = $conn->query("
        SELECT r.*, rm.room_number 
        FROM reservations r 
        JOIN rooms rm ON r.room_id = rm.room_id 
        WHERE r.res_id = $selected_res_id
    ");
    $folio_data = $res_query->fetch_assoc();
    
    if ($folio_data) {
        $trans_query = $conn->query("
            SELECT * FROM folio_transactions 
            WHERE res_id = $selected_res_id AND status = 'active'
            ORDER BY created_at DESC
        ");
        while ($t = $trans_query->fetch_assoc()) {
            $transactions[] = $t;
            switch($t['trans_type']) {
                case 'charge':   $total_charges += $t['amount']; break;
                case 'payment':  $total_payments += $t['amount']; break;
                case 'advance':  $total_advance += $t['amount']; break;
                case 'rebate':   $total_rebate += $t['amount']; break;
            }
        }
        $balance = $total_charges - ($total_payments + $total_advance) + $total_rebate;
        $advance_amount = $total_advance;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Refund - Araliya PMS</title>
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
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin: 15px 0; }
        .summary-card { background: #f8fafc; padding: 12px; border-radius: 8px; border-left: 4px solid #94a3b8; text-align: center; }
        .summary-card .label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; }
        .summary-card .value { font-size: 22px; font-weight: 700; margin-top: 2px; }
        .summary-card.charges { border-left-color: #ef4444; }
        .summary-card.charges .value { color: #ef4444; }
        .summary-card.payments { border-left-color: #22c55e; }
        .summary-card.payments .value { color: #22c55e; }
        .summary-card.advance { border-left-color: #3b82f6; }
        .summary-card.advance .value { color: #3b82f6; }
        .summary-card.balance { border-left-color: #8b5cf6; }
        .summary-card.balance .value { color: #8b5cf6; }
        .summary-card.balance.negative .value { color: #ef4444; }
        .transaction-table { width: 100%; font-size: 13px; border-collapse: collapse; margin-top: 10px; }
        .transaction-table th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .transaction-table td { padding: 6px 10px; border-bottom: 1px solid #e9ecef; }
        .badge-charge { background: #fecaca; color: #991b1b; }
        .badge-payment { background: #bbf7d0; color: #166534; }
        .badge-advance { background: #bfdbfe; color: #1e40af; }
        .badge-rebate { background: #fde68a; color: #92400e; }
        .badge-refund { background: #dbeafe; color: #1e40af; }
        .btn-refund { background: #3b82f6; color: white; border: none; padding: 10px 25px; font-weight: 600; border-radius: 6px; transition: 0.2s; }
        .btn-refund:hover { background: #2563eb; transform: translateY(-2px); }
        .btn-refund:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .form-control:focus { border-color: #0d4b68; box-shadow: 0 0 0 3px rgba(13,75,104,0.1); }
        .preview-box { background: #f0f4f8; padding: 10px 15px; border-radius: 6px; margin-top: 10px; }
        @media (max-width: 768px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h4><i class="fas fa-undo-alt"></i> Deposit Refund</h4>
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
            <a href="deposit_refund.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>

    <?php if($folio_data): ?>
        <!-- Guest Info -->
        <div class="p-3 mb-3" style="background:#f0f7fa; border-radius:8px;">
            <h5 class="mb-1"><i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($folio_data['guest_name']) ?></h5>
            <small class="text-muted">
                Room <?= htmlspecialchars($folio_data['room_number']) ?> &nbsp;|&nbsp;
                Res #: <?= htmlspecialchars($folio_data['res_no'] ?? 'RES' . str_pad($folio_data['res_id'], 4, '0', STR_PAD_LEFT)) ?> &nbsp;|&nbsp;
                Check-in: <?= date('d/m/Y', strtotime($folio_data['check_in'])) ?>
            </small>
        </div>

        <!-- Summary -->
        <div class="summary-grid">
            <div class="summary-card charges">
                <div class="label">Total Charges</div>
                <div class="value">LKR <?= number_format($total_charges, 2) ?></div>
            </div>
            <div class="summary-card payments">
                <div class="label">Payments</div>
                <div class="value">LKR <?= number_format($total_payments, 2) ?></div>
            </div>
            <div class="summary-card advance">
                <div class="label">Advance Available</div>
                <div class="value" style="color: #3b82f6;">LKR <?= number_format($advance_amount, 2) ?></div>
            </div>
            <div class="summary-card balance <?= ($balance < 0) ? 'negative' : '' ?>">
                <div class="label">Current Balance</div>
                <div class="value">LKR <?= number_format($balance, 2) ?></div>
            </div>
        </div>

        <!-- Refund Form -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <strong><i class="fas fa-hand-holding-usd me-2"></i>Process Deposit Refund</strong>
                <?php if($advance_amount > 0): ?>
                    <span class="badge bg-primary ms-2">Advance: LKR <?= number_format($advance_amount, 2) ?></span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark ms-2">No Advance Available</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="refundForm">
                    <input type="hidden" name="res_id" value="<?= $selected_res_id ?>">
                    <input type="hidden" name="submit_refund" value="1">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Refund Amount (LKR)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" 
                                   placeholder="0.00" required id="refundAmount" min="0">
                            <?php if($advance_amount > 0): ?>
                                <small class="text-muted">Max: LKR <?= number_format($advance_amount, 2) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Description</label>
                            <input type="text" name="description" class="form-control" 
                                   value="Deposit Refund" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Reference</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="Ref #">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="preview-box" id="previewBox">
                                <strong>Preview:</strong> Current balance <span id="previewCurrent">LKR <?= number_format($balance, 2) ?></span> → 
                                After refund: <span id="previewAfter">LKR <?= number_format($balance, 2) ?></span>
                                <small class="text-muted ms-2">(Refund reduces the balance)</small>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-refund mt-3" <?= ($advance_amount <= 0) ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle me-2"></i>Process Refund
                    </button>
                    <?php if($advance_amount <= 0): ?>
                        <small class="text-danger d-block mt-1">No advance payment available to refund.</small>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Recent Transactions -->
        <?php if(count($transactions) > 0): ?>
            <div class="mt-4">
                <h6><i class="fas fa-list-ul me-2"></i>Recent Transactions</h6>
                <div style="max-height:200px; overflow-y:auto;">
                    <table class="transaction-table">
                        <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th></tr></thead>
                        <tbody>
                            <?php foreach(array_slice($transactions, 0, 10) as $t): ?>
                                <tr>
                                    <td><?= date('d/m H:i', strtotime($t['created_at'])) ?></td>
                                    <td>
                                        <span class="badge <?= 
                                            $t['trans_type'] == 'charge' ? 'badge-charge' : 
                                            ($t['trans_type'] == 'payment' ? 'badge-payment' : 
                                            ($t['trans_type'] == 'advance' ? 'badge-advance' : 'badge-rebate')) 
                                        ?>"><?= ucfirst($t['trans_type']) ?></span>
                                    </td>
                                    <td>LKR <?= number_format($t['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($t['description'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php elseif($selected_res_id > 0 && !$folio_data): ?>
        <div class="alert alert-warning">Guest not found or not checked in.</div>
    <?php elseif($selected_res_id == 0): ?>
        <div class="text-center text-muted py-4">
            <i class="fas fa-user-search fa-3x d-block mb-3"></i>
            <h5>Select a guest to view advance details</h5>
            <p>Choose a checked-in guest from the dropdown above.</p>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('refundAmount');
    const previewAfter = document.getElementById('previewAfter');
    const currentBalance = <?= $balance ?? 0 ?>;
    const maxAmount = <?= $advance_amount ?? 0 ?>;
    
    if (amountInput) {
        amountInput.addEventListener('input', function() {
            let val = parseFloat(this.value) || 0;
            
            // Don't allow negative
            if (val < 0) {
                this.value = 0;
                val = 0;
            }
            
            // Don't allow more than advance amount
            if (val > maxAmount && maxAmount > 0) {
                this.value = maxAmount;
                val = maxAmount;
            }
            
            let newBalance = currentBalance - val;
            previewAfter.textContent = 'LKR ' + newBalance.toFixed(2);
        });
    }
});
</script>
</body>
</html>