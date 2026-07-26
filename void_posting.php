<?php
include 'includes/session_check.php';
include 'includes/db.php';

$selected_res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
$message = '';
$msg_type = '';

// ===== PROCESS VOID =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['void_transaction'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $res_id = intval($_POST['res_id']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $created_by = $_SESSION['user_id'] ?? 0;
    $username = $_SESSION['username'] ?? 'System';
    
    if ($transaction_id > 0 && !empty($reason)) {
        $check = $conn->query("SELECT res_id, amount, trans_type, description FROM folio_transactions 
                               WHERE transaction_id = $transaction_id AND status = 'active'");
        if ($check && $trans = $check->fetch_assoc()) {
            $update = "UPDATE folio_transactions SET status = 'voided' WHERE transaction_id = $transaction_id";
            if ($conn->query($update)) {
                $log = "INSERT INTO audit_logs (user_id, username, action, description) 
                        VALUES ($created_by, '$username', 'VOID_POSTING', 
                        'Voided transaction #$transaction_id (Amount: LKR {$trans['amount']}, Type: {$trans['trans_type']}) - Reason: $reason')";
                $conn->query($log);
                $message = "Transaction #$transaction_id voided successfully!";
                $msg_type = "success";
            } else {
                $message = "Database error: " . $conn->error;
                $msg_type = "danger";
            }
        } else {
            $message = "Transaction not found or already voided.";
            $msg_type = "warning";
        }
    } else {
        $message = "Please select a transaction and provide a reason.";
        $msg_type = "warning";
    }
    header("Location: void_posting.php?res_id=$res_id&msg=" . urlencode($message) . "&type=$msg_type");
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

// Get folio data
$folio_data = null;
$transactions = [];
$voided_transactions = [];
$total_charges = $total_payments = $total_advance = $total_rebate = 0;
$balance = 0;

if ($selected_res_id > 0) {
    $res_query = $conn->query("
        SELECT r.*, rm.room_number 
        FROM reservations r 
        JOIN rooms rm ON r.room_id = rm.room_id 
        WHERE r.res_id = $selected_res_id
    ");
    $folio_data = $res_query->fetch_assoc();
    
    if ($folio_data) {
        // ACTIVE transactions (for voiding)
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
        
        // VOIDED transactions (history only)
        $voided_query = $conn->query("
            SELECT * FROM folio_transactions 
            WHERE res_id = $selected_res_id AND status = 'voided'
            ORDER BY created_at DESC
            LIMIT 20
        ");
        while ($v = $voided_query->fetch_assoc()) {
            $voided_transactions[] = $v;
        }
        
        // 🔥 BALANCE CALCULATION (Active transactions only)
        $balance = $total_charges - ($total_payments + $total_advance) + $total_rebate;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Void Posting - Araliya PMS</title>
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
        .transaction-table td { padding: 8px 10px; border-bottom: 1px solid #e9ecef; vertical-align: middle; }
        .transaction-table tr:hover td { background: #f8fafc; }
        .transaction-table .voided-row td { background: #fef2f2; color: #991b1b; text-decoration: line-through; }
        
        .badge-charge { background: #fecaca; color: #991b1b; }
        .badge-payment { background: #bbf7d0; color: #166534; }
        .badge-advance { background: #bfdbfe; color: #1e40af; }
        .badge-rebate { background: #fde68a; color: #92400e; }
        .badge-voided { background: #e5e7eb; color: #4b5563; }
        
        .btn-void { background: #ef4444; color: white; border: none; padding: 5px 15px; font-size: 12px; font-weight: 600; border-radius: 6px; transition: 0.2s; }
        .btn-void:hover { background: #dc2626; transform: translateY(-1px); }
        .btn-void:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        
        .form-control:focus { border-color: #0d4b68; box-shadow: 0 0 0 3px rgba(13,75,104,0.1); }
        
        @media (max-width: 768px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
            .transaction-table { font-size: 12px; }
        }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h4><i class="fas fa-ban"></i> Void Posting</h4>
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
            <a href="void_posting.php" class="btn btn-outline-secondary w-100">Clear</a>
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

        <!-- SUMMARY CARDS -->
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
                <div class="value" style="color: #3b82f6;">LKR <?= number_format($total_advance, 2) ?></div>
            </div>
            <div class="summary-card balance <?= ($balance < 0) ? 'negative' : '' ?>">
                <div class="label">Current Balance</div>
                <div class="value">LKR <?= number_format($balance, 2) ?></div>
            </div>
        </div>

        <!-- Balance Calculation Info -->
        <div class="alert alert-light border" style="font-size:12px;">
            <strong>Balance Calculation:</strong>
            <?= number_format($total_charges, 2) ?> (Charges) 
            - <?= number_format($total_payments, 2) ?> (Payments) 
            - <?= number_format($total_advance, 2) ?> (Advance) 
            + <?= number_format($total_rebate, 2) ?> (Rebate) 
            = <strong>LKR <?= number_format($balance, 2) ?></strong>
            <small class="text-muted d-block">(Voided transactions are excluded from calculation)</small>
        </div>

        <!-- Active Transactions -->
        <?php if(count($transactions) > 0): ?>
            <div class="mt-3">
                <h6><i class="fas fa-list-ul me-2"></i>Active Transactions</h6>
                <div style="max-height:300px; overflow-y:auto;">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transactions as $t): ?>
                                <?php 
                                    $badge_class = '';
                                    if($t['trans_type'] == 'charge') $badge_class = 'badge-charge';
                                    elseif($t['trans_type'] == 'payment') $badge_class = 'badge-payment';
                                    elseif($t['trans_type'] == 'advance') $badge_class = 'badge-advance';
                                    elseif($t['trans_type'] == 'rebate') $badge_class = 'badge-rebate';
                                ?>
                                <tr>
                                    <td><?= date('d/m H:i', strtotime($t['created_at'])) ?></td>
                                    <td><span class="badge <?= $badge_class ?>"><?= ucfirst($t['trans_type']) ?></span></td>
                                    <td>LKR <?= number_format($t['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($t['description'] ?? '—') ?></td>
                                    <td>
                                        <button class="btn-void btn-sm" onclick="openVoidModal(<?= $t['transaction_id'] ?>, <?= $selected_res_id ?>)">
                                            <i class="fas fa-ban"></i> Void
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Void will cancel this transaction and update the balance</small>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-3">No active transactions to void.</div>
        <?php endif; ?>

        <!-- Voided Transactions -->
        <?php if(count($voided_transactions) > 0): ?>
            <div class="mt-4">
                <h6><i class="fas fa-history me-2" style="color:#ef4444;"></i>Voided Transactions (History)</h6>
                <div style="max-height:150px; overflow-y:auto;">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($voided_transactions as $vt): ?>
                                <tr class="voided-row">
                                    <td><?= date('d/m H:i', strtotime($vt['created_at'])) ?></td>
                                    <td><span class="badge badge-voided"><?= ucfirst($vt['trans_type']) ?></span></td>
                                    <td>LKR <?= number_format($vt['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($vt['description'] ?? '—') ?></td>
                                    <td><span class="badge bg-danger">VOIDED</span></td>
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
            <h5>Select a guest to view transactions</h5>
            <p>Choose a checked-in guest from the dropdown above.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Void Modal -->
<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#ef4444; color:white;">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Void Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body">
                <p class="fw-bold text-danger">Are you sure you want to void this transaction?</p>
                <p class="small text-muted">This action cannot be undone. The transaction will be marked as voided and will no longer affect the balance.</p>
                <form id="voidForm" method="POST" action="">
                    <input type="hidden" name="transaction_id" id="voidTransactionId">
                    <input type="hidden" name="res_id" id="voidResId">
                    <input type="hidden" name="void_transaction" value="1">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason for Void</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Incorrect amount, Wrong guest" required>
                    </div>
                    <button type="submit" class="btn btn-danger w-100"><i class="fas fa-ban me-2"></i>Confirm Void</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let voidModal = null;

document.addEventListener('DOMContentLoaded', function() {
    voidModal = new bootstrap.Modal(document.getElementById('voidModal'));
});

function openVoidModal(transaction_id, res_id) {
    document.getElementById('voidTransactionId').value = transaction_id;
    document.getElementById('voidResId').value = res_id;
    voidModal.show();
}
</script>
</body>
</html>