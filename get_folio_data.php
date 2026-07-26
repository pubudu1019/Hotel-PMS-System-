<?php
include 'includes/session_check.php';
include 'includes/db.php';

$res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
if ($res_id == 0) die("Invalid reservation");

// ===== 1. GET RESERVATION =====
$res_query = $conn->query("SELECT r.*, rm.room_number, rm.room_type 
                           FROM reservations r 
                           LEFT JOIN rooms rm ON r.room_id = rm.room_id 
                           WHERE r.res_id = $res_id");
$reservation = $res_query->fetch_assoc();
if (!$reservation) die("Reservation not found");

// ===== 2. AUTO ADD ROOM CHARGE (IF NOT EXISTS) =====
$check_charge = $conn->query("SELECT transaction_id FROM folio_transactions 
                              WHERE res_id = $res_id 
                              AND trans_type = 'charge' 
                              AND description LIKE 'Room Charge%'
                              AND status = 'active'");

if ($check_charge->num_rows == 0 && !empty($reservation['room_rate']) && $reservation['room_rate'] > 0) {
    $checkin = new DateTime($reservation['check_in']);
    $checkout = new DateTime($reservation['check_out']);
    $nights = $checkin->diff($checkout)->days;
    if ($nights == 0) $nights = 1;
    
    $total_charge = $reservation['room_rate'] * $nights;
    $desc = "Room Charge (" . $nights . " nights @ LKR " . number_format($reservation['room_rate'], 2) . ")";
    $user_id = $_SESSION['user_id'] ?? 0;
    
    $conn->query("INSERT INTO folio_transactions 
                  (res_id, amount, description, trans_type, reference_no, created_by, status) 
                  VALUES ($res_id, $total_charge, '$desc', 'charge', 'AUTO', $user_id, 'active')");
}

// ===== 3. GET TRANSACTIONS =====
$trans_query = $conn->query("SELECT * FROM folio_transactions 
                             WHERE res_id = $res_id AND status = 'active'
                             ORDER BY created_at DESC");
$transactions = [];
$total_charges = $total_payments = $total_advance = $total_rebate = 0;
while ($trans = $trans_query->fetch_assoc()) {
    $transactions[] = $trans;
    switch($trans['trans_type']) {
        case 'charge':   $total_charges  += $trans['amount']; break;
        case 'payment':  $total_payments += $trans['amount']; break;
        case 'advance':  $total_advance  += $trans['amount']; break;
        case 'rebate':   $total_rebate   += $trans['amount']; break;
    }
}
$balance = $total_charges - ($total_payments + $total_advance) + $total_rebate;

// ===== 4. USER NAMES =====
$user_names = [];
if (!empty($transactions)) {
    $user_ids = array_unique(array_column($transactions, 'created_by'));
    if (!empty($user_ids)) {
        $ids_str = implode(',', array_filter($user_ids));
        if (!empty($ids_str)) {
            $user_query = $conn->query("SELECT user_id, username FROM users WHERE user_id IN ($ids_str)");
            while ($user = $user_query->fetch_assoc()) {
                $user_names[$user['user_id']] = $user['username'];
            }
        }
    }
}

// ===== 5. CHECK USER ROLE FOR RESET =====
$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
$can_reset = ($user_role === 'admin' || $user_role === 'manager');
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        * { box-sizing: border-box; }
        .folio-container { padding: 0; font-family: 'Segoe UI', 'Helvetica Neue', sans-serif; background: #f8fafc; border-radius: 12px; overflow: hidden; }
        .folio-header { background: linear-gradient(135deg, #0d4b68 0%, #1a6f8e 100%); padding: 18px 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .folio-header .guest-info h3 { color: #fff; margin: 0; font-weight: 700; font-size: 19px; }
        .folio-header .guest-info small { color: #b8d4e8; font-size: 12px; }
        .folio-header .room-rate-box { background: rgba(255,255,255,0.15); padding: 6px 18px; border-radius: 30px; color: #fbbf24; font-weight: 700; font-size: 17px; border: 1px solid rgba(255,255,255,0.1); }
        .folio-header .room-rate-box i { margin-right: 8px; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; padding: 18px 25px; background: #fff; border-bottom: 1px solid #e2e8f0; }
        .summary-card { background: #f8fafc; padding: 10px 12px; border-radius: 10px; text-align: center; border-left: 4px solid #94a3b8; transition: 0.2s; }
        .summary-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .summary-card .label { font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.5px; }
        .summary-card .value { font-size: 20px; font-weight: 800; margin-top: 2px; }
        .summary-card.charges { border-left-color: #ef4444; }
        .summary-card.charges .value { color: #ef4444; }
        .summary-card.payments { border-left-color: #22c55e; }
        .summary-card.payments .value { color: #22c55e; }
        .summary-card.advance { border-left-color: #3b82f6; }
        .summary-card.advance .value { color: #3b82f6; }
        .summary-card.balance { border-left-color: #8b5cf6; }
        .summary-card.balance .value { color: #8b5cf6; }
        .summary-card.balance.negative .value { color: #ef4444; }
        .trans-section { padding: 12px 25px; background: #fff; }
        .trans-section .trans-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px; }
        .trans-section .trans-header h6 { font-weight: 700; color: #1e293b; margin: 0; font-size: 13px; }
        .trans-section .trans-header h6 i { color: #0d4b68; margin-right: 8px; }
        .btn-reset-folio { background: #ef4444; color: #fff; border: none; padding: 5px 14px; font-size: 11px; font-weight: 600; border-radius: 6px; transition: 0.2s; cursor: pointer; }
        .btn-reset-folio:hover { background: #dc2626; }
        .btn-reset-folio:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-card-payment { background: linear-gradient(135deg, #1a56db, #1e40af); color: white; border: none; padding: 5px 14px; font-size: 11px; font-weight: 600; border-radius: 6px; transition: all 0.3s ease; cursor: pointer; }
        .btn-card-payment:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(26, 86, 219, 0.4); color: white; }
        .btn-card-payment i { margin-right: 4px; }
        .transaction-table-wrap { max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
        .transaction-table { width: 100%; font-size: 12px; border-collapse: collapse; }
        .transaction-table th { background: #f1f5f9; color: #1e293b; font-weight: 700; padding: 6px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 2px solid #e2e8f0; position: sticky; top: 0; z-index: 5; }
        .transaction-table td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .transaction-table tr:hover td { background: #f8fafc; }
        .badge-type { padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .badge-charge  { background: #fecaca; color: #991b1b; }
        .badge-payment { background: #bbf7d0; color: #166534; }
        .badge-advance { background: #bfdbfe; color: #1e40af; }
        .badge-rebate  { background: #fde68a; color: #92400e; }
        .amount-charge  { color: #ef4444; font-weight: 600; }
        .amount-payment { color: #22c55e; font-weight: 600; }
        .amount-advance { color: #3b82f6; font-weight: 600; }
        .amount-rebate  { color: #f59e0b; font-weight: 600; }
        .running-balance { font-weight: 700; font-size: 13px; }
        .running-balance.positive { color: #0d4b68; }
        .running-balance.negative { color: #ef4444; }
        .folio-form-section { padding: 12px 25px 18px; background: #f8fafc; border-top: 1px solid #e2e8f0; }
        .folio-form-section label { font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 2px; }
        .folio-form-section .form-control, .folio-form-section .form-select { background: #fff !important; border: 1px solid #e2e8f0; font-size: 12px; padding: 5px 10px; border-radius: 6px; }
        .folio-form-section .form-control:focus, .folio-form-section .form-select:focus { border-color: #0d4b68; box-shadow: 0 0 0 3px rgba(13,75,104,0.1); }
        .btn-post { background: #0d4b68; color: #fff; border: none; padding: 7px 20px; font-weight: 600; border-radius: 6px; transition: 0.2s; width: 100%; font-size: 12px; }
        .btn-post:hover { background: #1a6f8e; }
        .btn-post:disabled { opacity: 0.6; cursor: not-allowed; }
        .payment-methods { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
        .payment-method-btn { padding: 5px 20px; border: 2px solid #e2e8f0; border-radius: 25px; font-size: 12px; font-weight: 700; background: #fff; color: #475569; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 6px; }
        .payment-method-btn:hover { border-color: #0d4b68; color: #0d4b68; background: #f1f5f9; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .payment-method-btn.active { border-color: #0d4b68; background: #0d4b68; color: #fff; box-shadow: 0 2px 12px rgba(13,75,104,0.3); }
        .payment-method-btn i { font-size: 14px; }
        .payment-method-btn .pm-icon { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #f1f5f9; color: #0d4b68; transition: 0.3s; }
        .payment-method-btn.active .pm-icon { background: rgba(255,255,255,0.2); color: #fff; }
        @media print { .btn-reset-folio { display: none !important; } .btn-card-payment { display: none !important; } .folio-form-section { display: none !important; } .transaction-table-wrap { max-height: none !important; overflow: visible !important; } .summary-grid { break-inside: avoid; } .folio-header { break-inside: avoid; } .room-rate-box { background: #12536d !important; color: #fbbf24 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } .badge-type { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } .summary-card { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } .folio-header { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }
        @media (max-width: 768px) { .summary-grid { grid-template-columns: repeat(2, 1fr); padding: 12px 15px; gap: 8px; } .folio-header { flex-direction: column; align-items: flex-start; padding: 12px 15px; } .folio-header .room-rate-box { font-size: 14px; padding: 5px 14px; } .trans-section { padding: 10px 15px; } .folio-form-section { padding: 10px 15px; } .transaction-table { font-size: 11px; } .transaction-table th, .transaction-table td { padding: 4px 6px; } .summary-card .value { font-size: 17px; } .payment-method-btn { padding: 4px 14px; font-size: 11px; } }
        @media (max-width: 576px) { .summary-grid { grid-template-columns: 1fr 1fr; gap: 6px; } .payment-methods { gap: 5px; } .payment-method-btn { padding: 3px 12px; font-size: 10px; } .folio-header .guest-info h3 { font-size: 15px; } }
    </style>
</head>
<body>
<div class="folio-container">

    <!-- ===== HEADER ===== -->
    <div class="folio-header">
        <div class="guest-info">
            <h3><i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($reservation['guest_name']) ?></h3>
            <small>
                <i class="fas fa-hashtag"></i> <?= htmlspecialchars($reservation['res_no'] ?? 'RES' . str_pad($reservation['res_id'], 4, '0', STR_PAD_LEFT)) ?>
                &nbsp;|&nbsp; <i class="fas fa-door-open"></i> Room <?= htmlspecialchars($reservation['room_number'] ?? '—') ?>
                &nbsp;|&nbsp; <i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($reservation['check_in'])) ?> – <?= date('d/m/Y', strtotime($reservation['check_out'])) ?>
                &nbsp;(<?= $reservation['adults'] ?> adults)
            </small>
        </div>
        <div class="room-rate-box">
            <i class="fas fa-tag"></i> LKR <?= number_format($reservation['room_rate'] ?? 0, 2) ?>
        </div>
    </div>

    <!-- ===== SUMMARY CARDS ===== -->
    <div class="summary-grid">
        <div class="summary-card charges">
            <div class="label"><i class="fas fa-plus-circle me-1"></i> Charges</div>
            <div class="value">LKR <?= number_format($total_charges, 2) ?></div>
        </div>
        <div class="summary-card payments">
            <div class="label"><i class="fas fa-minus-circle me-1"></i> Payments</div>
            <div class="value">LKR <?= number_format($total_payments, 2) ?></div>
        </div>
        <div class="summary-card advance">
            <div class="label"><i class="fas fa-hand-holding-usd me-1"></i> Advance</div>
            <div class="value">LKR <?= number_format($total_advance, 2) ?></div>
        </div>
        <div class="summary-card balance <?= ($balance < 0) ? 'negative' : '' ?>">
            <div class="label"><i class="fas fa-balance-scale me-1"></i> Balance</div>
            <div class="value">LKR <?= number_format($balance, 2) ?></div>
        </div>
    </div>

    <!-- ===== TRANSACTIONS ===== -->
    <div class="trans-section">
        <div class="trans-header">
            <h6><i class="fas fa-list-ul"></i> Transactions</h6>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <?php if($balance > 0): ?>
                    <button class="btn-card-payment" id="cardPaymentBtn" onclick="openCardPaymentModalFromFolio(<?= $res_id ?>, <?= $balance ?>)">
                        <i class="fas fa-credit-card me-1"></i> Card Payment
                    </button>
                <?php endif; ?>
                <?php if($can_reset && count($transactions) > 0): ?>
                    <button class="btn-reset-folio" id="resetFolioBtn">
                        <i class="fas fa-trash-alt me-1"></i> Reset All
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="transaction-table-wrap">
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Ref</th>
                        <th>By</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) > 0): ?>
                        <?php $balance_running = 0; foreach (array_reverse($transactions) as $t): 
                            switch($t['trans_type']) {
                                case 'charge':   $balance_running += $t['amount']; break;
                                case 'payment':  $balance_running -= $t['amount']; break;
                                case 'advance':  $balance_running -= $t['amount']; break;
                                case 'rebate':   $balance_running += $t['amount']; break;
                            }
                        ?>
                        <tr>
                            <td><?= date('d/m H:i', strtotime($t['created_at'])) ?></td>
                            <td><span class="badge-type badge-<?= $t['trans_type'] ?>"><?= ucfirst($t['trans_type']) ?></span></td>
                            <td><?= htmlspecialchars($t['description'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($t['reference_no'] ?? '—') ?></td>
                            <td><?= isset($user_names[$t['created_by']]) ? htmlspecialchars($user_names[$t['created_by']]) : 'System' ?></td>
                            <td class="text-end amount-<?= $t['trans_type'] ?>">
                                <?php if ($t['trans_type'] == 'charge' || $t['trans_type'] == 'rebate'): ?>
                                    <span class="text-danger">+</span>
                                <?php else: ?>
                                    <span class="text-success">−</span>
                                <?php endif; ?>
                                LKR <?= number_format($t['amount'], 2) ?>
                            </td>
                            <td class="text-end running-balance <?= ($balance_running < 0) ? 'negative' : 'positive' ?>">
                                LKR <?= number_format($balance_running, 2) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No transactions yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== POST FORM ===== -->
    <div class="folio-form-section">
        <form id="folioForm" method="POST">
            <input type="hidden" name="res_id" value="<?= $res_id ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-2 col-6">
                    <label>Type</label>
                    <select name="trans_type" class="form-select form-select-sm" id="transType" required>
                        <option value="charge">Charge</option>
                        <option value="payment" selected>Payment</option>
                        <option value="advance">Advance</option>
                        <option value="rebate">Rebate</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label>Amount (LKR)</label>
                    <input type="number" step="0.01" name="amount" class="form-control form-control-sm" required id="transAmount" placeholder="0.00">
                </div>
                <div class="col-md-3 col-12">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control form-control-sm" placeholder="Enter description" id="transDesc">
                </div>
                <div class="col-md-2 col-6">
                    <label>Reference</label>
                    <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="Ref #" id="transRef">
                </div>
                <div class="col-md-2 col-6">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-post" id="postBtn">
                        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                        <span id="btnText"><i class="fas fa-pen me-1"></i> Post</span>
                    </button>
                </div>
            </div>
            
            <!-- ===== PAYMENT METHODS ===== -->
            <div class="row mt-3">
                <div class="col-12">
                    <label style="font-size:11px; color:#64748b; font-weight:600;">
                        <i class="fas fa-credit-card me-1"></i> Quick Payment Methods
                    </label>
                    <div class="payment-methods">
                        <button type="button" class="payment-method-btn" id="btnCash" onclick="setPayment('Cash', 'Cash Payment')">
                            <span class="pm-icon"><i class="fas fa-money-bill-wave"></i></span>
                            Cash
                        </button>
                        <button type="button" class="payment-method-btn" id="btnCard" onclick="setPayment('Card', 'Card Payment')">
                            <span class="pm-icon"><i class="fas fa-credit-card"></i></span>
                            Card
                        </button>
                    </div>
                    <small class="text-muted" style="font-size:10px; display:block; margin-top:4px;">
                        <i class="fas fa-info-circle"></i> Click a method to auto-fill Payment type & description
                    </small>
                </div>
            </div>
        </form>
    </div>

</div>

<script>
// ===== PAYMENT METHOD SETTER =====
function setPayment(method, desc) {
    document.getElementById('transType').value = 'payment';
    document.getElementById('transDesc').value = desc + ' (' + method + ')';
    document.getElementById('transRef').value = method;
    document.getElementById('transAmount').focus();
    
    document.querySelectorAll('.payment-method-btn').forEach(btn => btn.classList.remove('active'));
    if (method === 'Cash') {
        document.getElementById('btnCash').classList.add('active');
    } else if (method === 'Card') {
        document.getElementById('btnCard').classList.add('active');
    }
}

// ===== 🔥 OPEN CARD PAYMENT MODAL FROM FOLIO =====
function openCardPaymentModalFromFolio(res_id, balance) {
    console.log('🔄 Opening card payment modal for Res ID:', res_id, 'Balance:', balance);
    
    if (balance <= 0) {
        alert('Balance is already zero. No payment needed.');
        return;
    }
    
    // Try to call parent page's function
    try {
        if (typeof window.parent.openCardPaymentModal === 'function') {
            window.parent.openCardPaymentModal(res_id, balance);
            return;
        }
    } catch(e) {
        console.warn('Parent function not accessible:', e);
    }
    
    // Fallback: Directly set values and open modal in parent
    try {
        const parentDoc = window.parent.document;
        parentDoc.getElementById('cardResId').value = res_id;
        parentDoc.getElementById('cardAmount').value = balance;
        parentDoc.getElementById('displayAmount').textContent = 'LKR ' + balance.toFixed(2);
        
        // Reset form
        const form = parentDoc.getElementById('cardPaymentForm');
        if (form) form.reset();
        
        // Open modal
        const modalEl = parentDoc.getElementById('cardPaymentModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    } catch(e) {
        console.error('Failed to open card payment modal:', e);
        alert('Please open card payment from the main page.');
    }
}

// ===== FORM SUBMIT =====
(function() {
    const form = document.getElementById('folioForm');
    if (!form) return;
    const btn = document.getElementById('postBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('btnSpinner');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const amount = this.querySelector('input[name="amount"]');
        if (parseFloat(amount.value) <= 0) { 
            showAlert('Please enter a valid amount', 'danger'); 
            return; 
        }
        btn.disabled = true;
        btnText.textContent = 'Processing...';
        spinner.classList.remove('d-none');

        const formData = new FormData(this);
        fetch('process_folio.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btnText.textContent = 'Post';
            spinner.classList.add('d-none');
            if (data.success) {
                const resId = data.res_id || formData.get('res_id');
                if (typeof window.parent.openFolio === 'function') {
                    window.parent.openFolio(resId);
                } else {
                    location.reload();
                }
                showAlert('✅ Transaction posted successfully!', 'success');
                form.querySelector('input[name="amount"]').value = '';
                form.querySelector('input[name="description"]').value = '';
                form.querySelector('input[name="reference_no"]').value = '';
                document.querySelectorAll('.payment-method-btn').forEach(btn => btn.classList.remove('active'));
            } else {
                showAlert('❌ Error: ' + (data.error || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            btn.disabled = false;
            btnText.textContent = 'Post';
            spinner.classList.add('d-none');
            showAlert('❌ Error: ' + error.message, 'danger');
        });
    });
})();

// ===== SHOW ALERT =====
function showAlert(message, type) {
    let modalBody = document.getElementById('modalContent');
    if (!modalBody) {
        try {
            modalBody = window.parent.document.getElementById('modalContent');
        } catch(e) {}
    }
    if (!modalBody) return;
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show m-3`;
    alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    modalBody.prepend(alertDiv);
    setTimeout(() => { if (alertDiv.parentNode) alertDiv.remove(); }, 5000);
}

// ===== RESET FOLIO =====
function resetFolio(res_id) {
    if (!confirm('⚠️ Are you sure you want to DELETE ALL active transactions for this folio? This cannot be undone!')) {
        return;
    }
    
    const resetBtn = document.getElementById('resetFolioBtn');
    if (resetBtn) {
        resetBtn.disabled = true;
        resetBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    let modalBody = document.getElementById('modalContent');
    if (!modalBody) {
        try {
            modalBody = window.parent.document.getElementById('modalContent');
        } catch(e) {}
    }
    if (modalBody) {
        modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-danger" role="status"></div><p class="mt-2">Resetting folio...</p></div>';
    }
    
    fetch('process_reset_folio.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'res_id=' + res_id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof window.parent.openFolio === 'function') {
                window.parent.openFolio(res_id);
            } else {
                location.reload();
            }
            showAlert('✅ Folio has been reset successfully!', 'success');
        } else {
            showAlert('❌ Error: ' + (data.error || 'Unknown error'), 'danger');
            if (typeof window.parent.openFolio === 'function') {
                window.parent.openFolio(res_id);
            }
        }
    })
    .catch(error => {
        showAlert('❌ Error: ' + error.message, 'danger');
        if (typeof window.parent.openFolio === 'function') {
            window.parent.openFolio(res_id);
        }
    })
    .finally(() => {
        if (resetBtn) {
            resetBtn.disabled = false;
            resetBtn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Reset All';
        }
    });
}
</script>
</body>
</html>