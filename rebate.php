<?php
include 'includes/session_check.php';
include 'includes/db.php';

$selected_res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
$message = '';
$msg_type = '';

// Process rebate submission if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rebate'])) {
    $res_id = intval($_POST['res_id']);
    $amount = floatval($_POST['amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $reference = mysqli_real_escape_string($conn, $_POST['reference_no']);
    $created_by = $_SESSION['user_id'] ?? 0;
    
    if ($res_id > 0 && $amount > 0 && !empty($description)) {
        $insert = "INSERT INTO folio_transactions 
                    (res_id, amount, description, trans_type, reference_no, created_by, status) 
                   VALUES ($res_id, $amount, '$description', 'rebate', '$reference', $created_by, 'active')";
        if ($conn->query($insert)) {
            $message = "Rebate posted successfully!";
            $msg_type = "success";
        } else {
            $message = "Database error: " . $conn->error;
            $msg_type = "danger";
        }
    } else {
        $message = "Please fill all required fields.";
        $msg_type = "warning";
    }
    // Redirect to same page with selected guest to avoid resubmission
    header("Location: rebate.php?res_id=$res_id&msg=" . urlencode($message) . "&type=$msg_type");
    exit();
}

// Handle messages from redirect
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

// If a guest is selected, fetch their folio data
$folio_data = null;
$transactions = [];
$rebates = [];
$total_charges = $total_payments = $total_advance = $total_rebate = 0;
$balance = 0;

if ($selected_res_id > 0) {
    // Fetch reservation
    $res_query = $conn->query("
        SELECT r.*, rm.room_number 
        FROM reservations r 
        JOIN rooms rm ON r.room_id = rm.room_id 
        WHERE r.res_id = $selected_res_id
    ");
    $folio_data = $res_query->fetch_assoc();
    
    if ($folio_data) {
        // Fetch all active transactions
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
        
        // Separate rebates only
        $rebates = array_filter($transactions, function($t) {
            return $t['trans_type'] === 'rebate';
        });
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rebate - Araliya PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ===== MAIN STYLES ===== */
        body { 
            background: #f4f7f6; 
            font-family: 'Segoe UI', sans-serif; 
            padding: 20px; 
        }
        .page-container { 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.06); 
            max-width: 1200px; 
            margin: 0 auto; 
        }
        .page-header { 
            border-bottom: 2px solid #eef2f5; 
            padding-bottom: 15px; 
            margin-bottom: 25px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 10px; 
        }
        .page-header h4 { 
            color: #0d4b68; 
            font-weight: 700; 
            margin: 0; 
        }
        .page-header h4 i { 
            color: #fbbf24; 
            margin-right: 10px; 
        }
        .back-btn { 
            background: none; 
            border: none; 
            color: #0d4b68; 
            font-weight: 600; 
            cursor: pointer; 
            font-size: 14px; 
        }
        .back-btn:hover { 
            text-decoration: underline; 
        }
        
        /* ===== SUMMARY CARDS ===== */
        .summary-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 12px; 
            margin: 15px 0; 
        }
        .summary-card { 
            background: #f8fafc; 
            padding: 12px; 
            border-radius: 8px; 
            border-left: 4px solid #94a3b8; 
            text-align: center; 
        }
        .summary-card .label { 
            font-size: 11px; 
            text-transform: uppercase; 
            color: #64748b; 
            font-weight: 600; 
        }
        .summary-card .value { 
            font-size: 22px; 
            font-weight: 700; 
            margin-top: 2px; 
        }
        .summary-card.charges { border-left-color: #ef4444; }
        .summary-card.charges .value { color: #ef4444; }
        .summary-card.payments { border-left-color: #22c55e; }
        .summary-card.payments .value { color: #22c55e; }
        .summary-card.advance { border-left-color: #3b82f6; }
        .summary-card.advance .value { color: #3b82f6; }
        .summary-card.balance { border-left-color: #8b5cf6; }
        .summary-card.balance .value { color: #8b5cf6; }
        .summary-card.balance.negative .value { color: #ef4444; }
        
        /* ===== TRANSACTION TABLE ===== */
        .transaction-table { 
            width: 100%; 
            font-size: 13px; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        .transaction-table th { 
            background: #f1f5f9; 
            padding: 8px 10px; 
            text-align: left; 
            font-size: 11px; 
            text-transform: uppercase; 
            border-bottom: 2px solid #e2e8f0; 
        }
        .transaction-table td { 
            padding: 6px 10px; 
            border-bottom: 1px solid #e9ecef; 
        }
        
        /* ===== BADGE STYLES ===== */
        .badge-charge { 
            background: #fecaca; 
            color: #991b1b; 
            padding: 3px 10px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 600; 
        }
        .badge-payment { 
            background: #bbf7d0; 
            color: #166534; 
            padding: 3px 10px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 600; 
        }
        .badge-advance { 
            background: #bfdbfe; 
            color: #1e40af; 
            padding: 3px 10px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 600; 
        }
        .badge-rebate { 
            background: #fde68a; 
            color: #92400e; 
            padding: 3px 10px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 600; 
        }
        .badge-void { 
            background: #e5e7eb; 
            color: #4b5563; 
            padding: 3px 10px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 600; 
        }
        
        /* ===== OTHER ===== */
        .btn-rebate { 
            background: #f59e0b; 
            color: white; 
            border: none; 
            padding: 10px 25px; 
            font-weight: 600; 
            border-radius: 6px; 
            transition: 0.2s; 
        }
        .btn-rebate:hover { 
            background: #d97706; 
            transform: translateY(-2px); 
            color: white; 
        }
        .form-control:focus { 
            border-color: #0d4b68; 
            box-shadow: 0 0 0 3px rgba(13,75,104,0.1); 
        }
        .preview-box { 
            background: #f0f4f8; 
            padding: 10px 15px; 
            border-radius: 6px; 
            margin-top: 10px; 
        }
        .guest-info-box { 
            background: #f0f7fa; 
            padding: 12px 18px; 
            border-radius: 8px; 
            margin-bottom: 15px; 
        }
        .guest-info-box h5 { margin-bottom: 3px; }
        .guest-info-box small { color: #475569; }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 576px) {
            .summary-grid { grid-template-columns: 1fr 1fr; }
            .page-container { padding: 15px; }
        }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h4><i class="fas fa-percent"></i> Rebate Management</h4>
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
            <a href="rebate.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>

    <?php if($folio_data): ?>
        <!-- Guest Info -->
        <div class="guest-info-box">
            <h5 class="mb-1"><i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($folio_data['guest_name']) ?></h5>
            <small>
                Room <?= htmlspecialchars($folio_data['room_number']) ?> &nbsp;|&nbsp;
                Res #: <?= htmlspecialchars($folio_data['res_no'] ?? 'RES' . str_pad($folio_data['res_id'], 4, '0', STR_PAD_LEFT)) ?> &nbsp;|&nbsp;
                Check-in: <?= date('d/m/Y', strtotime($folio_data['check_in'])) ?> &nbsp;|&nbsp;
                Room Rate: LKR <?= number_format($folio_data['room_rate'] ?? 0, 2) ?>
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
                <div class="label">Advance</div>
                <div class="value">LKR <?= number_format($total_advance, 2) ?></div>
            </div>
            <div class="summary-card balance <?= ($balance < 0) ? 'negative' : '' ?>">
                <div class="label">Current Balance</div>
                <div class="value">LKR <?= number_format($balance, 2) ?></div>
            </div>
        </div>

        <!-- Rebate Form -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <strong><i class="fas fa-edit me-2"></i>Apply Rebate / Discount</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="rebateForm">
                    <input type="hidden" name="res_id" value="<?= $selected_res_id ?>">
                    <input type="hidden" name="submit_rebate" value="1">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Amount (LKR)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required id="rebateAmount">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Reason / Description</label>
                            <input type="text" name="description" class="form-control" placeholder="e.g. Discount, Complimentary" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Reference</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="preview-box" id="previewBox">
                                <strong>Preview:</strong> Current balance <span id="previewCurrent">LKR <?= number_format($balance, 2) ?></span> → 
                                After rebate: <span id="previewAfter">LKR <?= number_format($balance, 2) ?></span>
                                <small class="text-muted ms-2">(Rebate reduces the balance)</small>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-rebate mt-3"><i class="fas fa-check-circle me-2"></i>Post Rebate</button>
                </form>
            </div>
        </div>

        <!-- Recent Rebates -->
        <?php if(count($rebates) > 0): ?>
            <div class="mt-4">
                <h6><i class="fas fa-history me-2"></i>Recent Rebates</h6>
                <table class="transaction-table">
                    <thead><tr><th>Date</th><th>Amount</th><th>Description</th><th>Reference</th><th>By</th></tr></thead>
                    <tbody>
                        <?php foreach($rebates as $r): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                                <td><span class="badge-rebate">LKR <?= number_format($r['amount'], 2) ?></span></td>
                                <td><?= htmlspecialchars($r['description'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($r['reference_no'] ?? '—') ?></td>
                                <td><?= $r['created_by'] ? 'User' : 'System' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Recent Transactions -->
        <?php if(count($transactions) > 0): ?>
            <div class="mt-4">
                <h6><i class="fas fa-list-ul me-2"></i>All Recent Transactions (last 10)</h6>
                <div style="max-height:200px; overflow-y:auto;">
                    <table class="transaction-table">
                        <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th></tr></thead>
                        <tbody>
                            <?php foreach(array_slice($transactions, 0, 10) as $t): ?>
                                <tr>
                                    <td><?= date('d/m H:i', strtotime($t['created_at'])) ?></td>
                                    <td>
                                        <span class="<?php 
                                            switch($t['trans_type']) {
                                                case 'charge': echo 'badge-charge'; break;
                                                case 'payment': echo 'badge-payment'; break;
                                                case 'advance': echo 'badge-advance'; break;
                                                case 'rebate': echo 'badge-rebate'; break;
                                                default: echo 'badge-void';
                                            }
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
            <h5>Select a guest to view folio details</h5>
            <p>Choose a checked-in guest from the dropdown above.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Live preview script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('rebateAmount');
    const previewCurrent = document.getElementById('previewCurrent');
    const previewAfter = document.getElementById('previewAfter');
    const currentBalance = <?= $balance ?? 0 ?>;
    
    if (amountInput) {
        amountInput.addEventListener('input', function() {
            let val = parseFloat(this.value) || 0;
            let newBalance = currentBalance - val;
            previewAfter.textContent = 'LKR ' + newBalance.toFixed(2);
        });
    }
});
</script>
</body>
</html>