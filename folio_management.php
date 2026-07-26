<?php
include 'includes/session_check.php';
include 'includes/db.php';

$open_folio = isset($_GET['open_folio']) ? intval($_GET['open_folio']) : 0;
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Folio Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; font-size: 13px; }
        .top-navbar { background: linear-gradient(135deg, #12536d, #1a6f8e); color: white; padding: 15px 25px; border-radius: 0; margin-bottom: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .top-navbar h5 { font-weight: 700; letter-spacing: 0.5px; }
        .page-header { background: white; padding: 20px 25px; border-bottom: 3px solid #12536d; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .page-header h3 { color: #12536d; font-weight: 700; margin: 0; }
        .stats-badge { background: #12536d; color: white; padding: 8px 18px; border-radius: 30px; font-size: 13px; font-weight: 600; }
        .table-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #e9ecef; }
        .table thead th { background: #f8fafc; color: #12536d; font-weight: 700; border-bottom: 2px solid #cbd5e1; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; padding: 12px 10px; }
        .table tbody td { padding: 12px 10px; vertical-align: middle; }
        .table tbody tr:hover { background: #f8fafc; }
        .guest-link { color: #12536d; text-decoration: none; font-weight: 600; }
        .guest-link:hover { color: #d97736; text-decoration: underline; }
        .room-badge { background: #e8f4f8; color: #12536d; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 14px; }
        .btn-manage { background: linear-gradient(135deg, #12536d, #1a6f8e); color: white; border: none; padding: 6px 18px; font-size: 12px; font-weight: 600; border-radius: 6px; transition: all 0.3s ease; }
        .btn-manage:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(18, 83, 109, 0.4); color: white; }
        .btn-refresh { background: white; color: #12536d; border: 1px solid #12536d; padding: 6px 15px; font-size: 12px; border-radius: 6px; }
        .btn-refresh:hover { background: #12536d; color: white; }
        .empty-state { padding: 60px 20px; text-align: center; }
        .empty-state i { font-size: 60px; color: #cbd5e1; margin-bottom: 20px; }
        .modal-content { border-radius: 12px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .modal-header { background: linear-gradient(135deg, #12536d, #1a6f8e); color: white; border-radius: 12px 12px 0 0; padding: 18px 24px; }
        .modal-header h5 { font-weight: 700; letter-spacing: 0.5px; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .modal-body { padding: 24px; max-height: 80vh; overflow-y: auto; }
        .stat-card { background: white; border-radius: 8px; padding: 15px 20px; border-left: 4px solid #12536d; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .stat-card .stat-number { font-size: 24px; font-weight: 700; color: #12536d; }
        .stat-card .stat-label { color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-print-folio { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 5px 14px; font-size: 12px; font-weight: 600; border-radius: 6px; transition: all 0.3s ease; }
        .btn-print-folio:hover { background: rgba(255,255,255,0.25); color: white; border-color: rgba(255,255,255,0.5); transform: translateY(-1px); }
        .btn-print-folio i { margin-right: 5px; }
        
        /* Card Payment Button */
        .btn-card-payment { background: linear-gradient(135deg, #1a56db, #1e40af); color: white; border: none; padding: 5px 14px; font-size: 12px; font-weight: 600; border-radius: 6px; transition: all 0.3s ease; }
        .btn-card-payment:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(26, 86, 219, 0.4); color: white; }
        
        /* Card Input Styles */
        .card-input-group { position: relative; margin-bottom: 15px; }
        .card-input-group .form-control { padding-left: 40px; }
        .card-input-group .input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; }
        .card-type-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 24px; }
        
        @media print { body * { visibility: hidden; } #printArea, #printArea * { visibility: visible; } #printArea { position: absolute; left: 0; top: 0; width: 100%; padding: 20px; background: white !important; } .modal-backdrop { display: none !important; } .modal { position: static !important; display: block !important; background: white !important; } .modal-dialog { max-width: 100% !important; margin: 0 !important; transform: none !important; } .modal-content { border: none !important; box-shadow: none !important; border-radius: 0 !important; } .modal-header { background: #12536d !important; color: white !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } .modal-header .btn-close, .modal-header .btn-print-folio { display: none !important; } .folio-form-section { display: none !important; } .btn-reset-folio { display: none !important; } .transaction-table-wrap { max-height: none !important; overflow: visible !important; } .summary-grid { break-inside: avoid; } .guest-header { break-inside: avoid; } .room-rate-badge { background: #12536d !important; color: white !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } .badge-type { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } .summary-card { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }
        @media (max-width: 768px) { .top-navbar h5 { font-size: 16px; } .page-header h3 { font-size: 18px; } .table-container { padding: 10px; } .table thead th { font-size: 10px; padding: 8px 5px; } .table tbody td { padding: 8px 5px; font-size: 12px; } }
    </style>
</head>
<body>

<div class="top-navbar d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <a href="dashboard.php" class="btn btn-sm btn-outline-light me-3 py-1 px-3" style="font-size: 11px; border-radius: 6px;"><i class="fas fa-arrow-left me-1"></i> Back</a>
        <h5 class="m-0"><i class="fas fa-file-invoice me-2"></i> FOLIO MANAGEMENT</h5>
        <span class="badge bg-light text-dark ms-3" style="font-size: 12px;"><i class="fas fa-clock me-1"></i> <?= date('d/m/Y h:i A') ?></span>
    </div>
    <div>
        <button class="btn btn-refresh" onclick="window.location.reload()"><i class="fas fa-sync me-1"></i> Refresh</button>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-warning alert-dismissible fade show" style="border-radius:0;margin:0;padding:12px 20px;">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= $msg ?>
        <?php if ($open_folio): ?>
            <a href="#" onclick="openFolio(<?= $open_folio ?>); return false;" class="btn btn-sm btn-primary ms-2"><i class="fas fa-credit-card me-1"></i> Open Folio</a>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h3><i class="fas fa-users me-2" style="color: #12536d;"></i>Checked-In Guests</h3>
        <p class="text-muted mb-0" style="font-size: 13px;"><i class="fas fa-info-circle me-1"></i> Manage guest folios and transactions</p>
    </div>
    <div>
        <?php 
        $count_res = $conn->query("SELECT COUNT(*) as total FROM reservations r JOIN rooms rm ON r.room_id = rm.room_id WHERE r.status = 'Checked-In'");
        $count = $count_res->fetch_assoc();
        ?>
        <span class="stats-badge"><i class="fas fa-hotel me-1"></i> <?= $count['total'] ?> Active Guests</span>
    </div>
</div>

<div class="container-fluid px-4">
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card"><div class="stat-number"><?= $count['total'] ?></div><div class="stat-label"><i class="fas fa-user-check me-1"></i> Checked-In</div></div></div>
        <div class="col-md-3"><div class="stat-card" style="border-left-color: #d97736;"><div class="stat-number" style="color: #d97736;"><?php $today = date('Y-m-d'); $today_res = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'Checked-In' AND DATE(check_in) = '$today'"); $today_count = $today_res->fetch_assoc(); echo $today_count['total']; ?></div><div class="stat-label"><i class="fas fa-calendar-check me-1"></i> Today's Check-ins</div></div></div>
        <div class="col-md-3"><div class="stat-card" style="border-left-color: #ef4444;"><div class="stat-number" style="color: #ef4444;"><?php $checkout_res = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'Checked-In' AND DATE(check_out) = '$today'"); $checkout_count = $checkout_res->fetch_assoc(); echo $checkout_count['total']; ?></div><div class="stat-label"><i class="fas fa-calendar-times me-1"></i> Today's Check-outs</div></div></div>
        <div class="col-md-3"><div class="stat-card" style="border-left-color: #10b981;"><div class="stat-number" style="color: #10b981;"><?php $total_rooms = $conn->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'occupied'"); $rooms_count = $total_rooms->fetch_assoc(); echo $rooms_count['total']; ?></div><div class="stat-label"><i class="fas fa-bed me-1"></i> Occupied Rooms</div></div></div>
    </div>

    <div class="table-container fade-in">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th><i class="fas fa-door-open me-1"></i> Room</th>
                        <th><i class="fas fa-user me-1"></i> Guest</th>
                        <th><i class="fas fa-ticket-alt me-1"></i> Reservation</th>
                        <th><i class="fas fa-calendar-plus me-1"></i> Check-In</th>
                        <th><i class="fas fa-calendar-minus me-1"></i> Check-Out</th>
                        <th><i class="fas fa-moon me-1"></i> Nights</th>
                        <th><i class="fas fa-users me-1"></i> Pax</th>
                        <th class="text-center"><i class="fas fa-cog me-1"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $res = $conn->query("SELECT r.*, rm.room_number FROM reservations r JOIN rooms rm ON r.room_id = rm.room_id WHERE r.status = 'Checked-In' ORDER BY rm.room_number ASC");
                if ($res->num_rows > 0) {
                    $counter = 1;
                    while($row = $res->fetch_assoc()):
                        $nights = isset($row['num_of_nights']) ? $row['num_of_nights'] : 0;
                        if($nights == 0 && !empty($row['check_in']) && !empty($row['check_out'])) {
                            $checkin = new DateTime($row['check_in']);
                            $checkout = new DateTime($row['check_out']);
                            $nights = $checkin->diff($checkout)->days;
                        }
                ?>
                    <tr>
                        <td class="text-center fw-bold text-muted"><?= $counter++ ?></td>
                        <td><span class="room-badge"><i class="fas fa-door-open me-1"></i> <?= htmlspecialchars($row['room_number']) ?></span></td>
                        <td>
                            <a href="reservation_details.php?id=<?= $row['res_id'] ?>" class="guest-link">
                                <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($row['guest_name']) ?>
                            </a>
                        </td>
                        <td><span class="fw-semibold text-muted" style="font-size: 12px;"><?= htmlspecialchars($row['res_no'] ?? 'RES' . str_pad($row['res_id'], 4, '0', STR_PAD_LEFT)) ?></span></td>
                        <td class="text-success fw-bold" style="font-size: 13px;"><?= date('d/m/Y', strtotime($row['check_in'])) ?></td>
                        <td><span style="font-size: 13px;"><?= date('d/m/Y', strtotime($row['check_out'])) ?></span></td>
                        <td class="text-center fw-bold"><?= $nights ?></td>
                        <td><span class="badge bg-light text-dark"><i class="fas fa-user"></i> <?= $row['adults'] ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-manage btn-sm" data-bs-toggle="modal" data-bs-target="#folioModal" onclick="openFolio(<?= $row['res_id'] ?>)">
                                <i class="fas fa-file-invoice-dollar"></i> Manage
                            </button>
                            <a href="quick_checkout.php?checkout_id=<?= $row['res_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Check-out?')" style="padding: 4px 10px; font-size: 11px;"><i class="fas fa-sign-out-alt"></i></a>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                } else {
                ?>
                    <tr><td colspan="9"><div class="empty-state"><i class="fas fa-bed"></i><h5>No Checked-In Guests</h5><p>All rooms are currently available.</p></div></td></tr>
                <?php 
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Folio Modal -->
<div class="modal fade" id="folioModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-file-invoice me-2"></i> Guest Folio</h5>
                <div>
                    <button class="btn-print-folio me-2" onclick="printFolio()" title="Print Folio">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading folio data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== CARD PAYMENT MODAL ===== -->
<div class="modal fade" id="cardPaymentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a56db, #1e40af); color: white;">
                <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i> Card Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="cardPaymentForm">
                    <input type="hidden" name="res_id" id="cardResId">
                    <input type="hidden" name="amount" id="cardAmount">
                    
                    <div class="card-input-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" name="card_holder" placeholder="Card Holder Name" required>
                        <small class="text-muted">Name as shown on card</small>
                    </div>
                    
                    <div class="card-input-group">
                        <span class="input-icon"><i class="fas fa-credit-card"></i></span>
                        <input type="text" class="form-control" name="card_number" placeholder="Card Number" maxlength="19" required id="cardNumber" oninput="formatCardNumber(this)">
                        <span class="card-type-icon" id="cardTypeIcon"><i class="far fa-credit-card"></i></span>
                        <small class="text-muted">16-digit card number</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card-input-group">
                                <span class="input-icon"><i class="fas fa-calendar-alt"></i></span>
                                <input type="text" class="form-control" name="expiry_date" placeholder="MM/YY" maxlength="5" required id="expiryDate" oninput="formatExpiry(this)">
                                <small class="text-muted">Expiry date</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card-input-group">
                                <span class="input-icon"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="cvv" placeholder="CVV" maxlength="4" required>
                                <small class="text-muted">3 or 4 digit security code</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Amount to Pay:</span>
                            <span class="fw-bold" id="displayAmount">LKR 0.00</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mt-3 py-2" id="payNowBtn">
                        <i class="fas fa-lock me-2"></i> Pay Now
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let currentResId = null;
let folioModal = null;
let resetAttached = false;

document.addEventListener('DOMContentLoaded', function() {
    folioModal = new bootstrap.Modal(document.getElementById('folioModal'));
    
    <?php if ($open_folio > 0): ?>
        setTimeout(function() {
            openFolio(<?= $open_folio ?>);
        }, 500);
    <?php endif; ?>
});

function openFolio(res_id) {
    currentResId = res_id;
    document.getElementById('modalContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading folio data...</p>
        </div>
    `;
    if (folioModal) folioModal.show();
    
    fetch('get_folio_data.php?res_id=' + res_id)
    .then(response => response.text())
    .then(data => { 
        document.getElementById('modalContent').innerHTML = data; 
        attachFormHandler();
        attachResetHandler();
        attachCardPaymentHandler();
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalContent').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> 
                Error loading folio data. Please try again.
            </div>
        `;
    });
}

function attachCardPaymentHandler() {
    // Card payment button in modal - use event delegation
    const modalBody = document.getElementById('modalContent');
    if (!modalBody) return;
    
    // Remove existing event listeners by using a mutation observer or simply re-attach
    // We'll use a click handler on the document for the card payment button
    document.querySelectorAll('.btn-card-payment').forEach(btn => {
        btn.removeEventListener('click', openCardPaymentModal);
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            // Get res_id from data attribute
            const resId = this.dataset.resId || currentResId;
            // Get balance from nearby element
            const balanceEl = this.closest('.folio-container')?.querySelector('.summary-card.balance .value');
            let balance = 0;
            if (balanceEl) {
                const text = balanceEl.textContent;
                const match = text.match(/[\d,]+\.\d{2}/);
                if (match) balance = parseFloat(match[0].replace(/,/g, ''));
            }
            openCardPaymentModal(resId, balance);
        });
    });
}

function openCardPaymentModal(res_id, amount) {
    if (amount <= 0) {
        alert('Balance is already zero. No payment needed.');
        return;
    }
    document.getElementById('cardResId').value = res_id;
    document.getElementById('cardAmount').value = amount;
    document.getElementById('displayAmount').textContent = 'LKR ' + amount.toFixed(2);
    document.getElementById('cardPaymentForm').reset();
    document.querySelector('#cardTypeIcon').innerHTML = '<i class="far fa-credit-card"></i>';
    const modal = new bootstrap.Modal(document.getElementById('cardPaymentModal'));
    modal.show();
}

// Format card number with spaces
function formatCardNumber(input) {
    let value = input.value.replace(/\s/g, '').replace(/\D/g, '');
    if (value.length > 16) value = value.slice(0, 16);
    let formatted = '';
    for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) formatted += ' ';
        formatted += value[i];
    }
    input.value = formatted;
    detectCardType(value);
}

function detectCardType(number) {
    const icon = document.getElementById('cardTypeIcon');
    const first = number.charAt(0);
    const firstTwo = number.slice(0, 2);
    const firstFour = number.slice(0, 4);
    
    if (first === '4') {
        icon.innerHTML = '<i class="fab fa-cc-visa" style="color: #1a1f71;"></i>';
    } else if (['51', '52', '53', '54', '55'].includes(firstTwo) || (parseInt(firstSix) >= 222100 && parseInt(firstSix) <= 272099)) {
        icon.innerHTML = '<i class="fab fa-cc-mastercard" style="color: #eb001b;"></i>';
    } else if (firstTwo === '34' || firstTwo === '37') {
        icon.innerHTML = '<i class="fab fa-cc-amex" style="color: #006fcf;"></i>';
    } else if (firstFour === '6011' || firstTwo === '65' || (parseInt(firstSix) >= 622126 && parseInt(firstSix) <= 622925)) {
        icon.innerHTML = '<i class="fab fa-cc-discover" style="color: #ff6000;"></i>';
    } else {
        icon.innerHTML = '<i class="far fa-credit-card"></i>';
    }
}

function formatExpiry(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 4) value = value.slice(0, 4);
    if (value.length > 2) {
        value = value.slice(0, 2) + '/' + value.slice(2);
    }
    input.value = value;
}

// Card Payment Form Submit
document.addEventListener('submit', function(e) {
    if (e.target && e.target.id === 'cardPaymentForm') {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('payNowBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
        
        const formData = new FormData(form);
        
        fetch('process_card_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
            
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('cardPaymentModal'));
                modal.hide();
                showAlert('✅ Payment successful! LKR ' + data.amount.toFixed(2) + ' posted to folio.', 'success');
                // Reload folio
                openFolio(data.res_id);
                form.reset();
            } else {
                showAlert('❌ Payment failed: ' + (data.error || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
            showAlert('❌ Error: ' + error.message, 'danger');
        });
    }
});

function attachFormHandler() {
    const form = document.getElementById('folioForm');
    if (form) {
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        newForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('postBtn');
            const btnText = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');
            if (btn) { btn.disabled = true; btnText.textContent = 'Processing...'; if(spinner) spinner.classList.remove('d-none'); }
            
            const formData = new FormData(this);
            
            fetch('process_folio.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (btn) { btn.disabled = false; btnText.textContent = 'Post'; if(spinner) spinner.classList.add('d-none'); }
                if (data.success) {
                    const resId = data.res_id || currentResId;
                    openFolio(resId);
                    showAlert('Transaction posted successfully!', 'success');
                } else {
                    showAlert('Error: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                if (btn) { btn.disabled = false; btnText.textContent = 'Post'; if(spinner) spinner.classList.add('d-none'); }
                showAlert('Error: ' + error.message, 'danger');
            });
        });
    }
}

function attachResetHandler() {
    const resetBtn = document.getElementById('resetFolioBtn');
    if (resetBtn && !resetAttached) {
        resetBtn.removeAttribute('onclick');
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Reset button clicked via event listener');
            resetFolio(currentResId);
        });
        resetAttached = true;
        console.log('Reset handler attached successfully');
    }
}

function resetFolio(res_id) {
    if (!confirm('⚠️ Are you sure you want to DELETE ALL active transactions for this folio? This cannot be undone!')) {
        return;
    }
    
    const resetBtn = document.getElementById('resetFolioBtn');
    if (resetBtn) {
        resetBtn.disabled = true;
        resetBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    const modalContent = document.getElementById('modalContent');
    modalContent.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-danger" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 fw-bold">Resetting folio...</p>
        </div>
    `;
    
    fetch('process_reset_folio.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'res_id=' + res_id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            openFolio(res_id);
            showAlert('✅ Folio has been reset successfully!', 'success');
        } else {
            showAlert('❌ Error: ' + (data.error || 'Unknown error'), 'danger');
            openFolio(res_id);
        }
    })
    .catch(error => {
        showAlert('❌ Error: ' + error.message, 'danger');
        openFolio(res_id);
    })
    .finally(() => {
        if (resetBtn) {
            resetBtn.disabled = false;
            resetBtn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Reset All';
        }
    });
}

function printFolio() {
    const modalContent = document.getElementById('modalContent');
    if (!modalContent) return;
    const printWrapper = document.createElement('div');
    printWrapper.id = 'printArea';
    printWrapper.innerHTML = modalContent.innerHTML;
    const formSection = printWrapper.querySelector('.folio-form-section');
    if (formSection) formSection.style.display = 'none';
    const resetBtn = printWrapper.querySelector('.btn-reset-folio');
    if (resetBtn) resetBtn.style.display = 'none';
    const allButtons = printWrapper.querySelectorAll('button');
    allButtons.forEach(btn => btn.style.display = 'none');
    document.body.appendChild(printWrapper);
    window.print();
    setTimeout(function() {
        if (printWrapper.parentNode) printWrapper.parentNode.removeChild(printWrapper);
    }, 1000);
}

function showAlert(message, type) {
    const modalBody = document.getElementById('modalContent');
    if (!modalBody) return;
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show m-2`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    modalBody.prepend(alertDiv);
    setTimeout(() => { if (alertDiv.parentNode) alertDiv.remove(); }, 5000);
}

document.getElementById('folioModal').addEventListener('hidden.bs.modal', function () {
    resetAttached = false;
});
</script>

</body>
</html>