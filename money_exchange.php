<?php
include 'includes/session_check.php';
include 'includes/db.php';

// Get all rooms for dropdown (ajax search)
$rooms_query = $conn->query("SELECT room_id, room_number, status FROM rooms ORDER BY room_number ASC");

// Get exchange rates from database
$rates_query = $conn->query("SELECT * FROM exchange_rates WHERE status = 'active' ORDER BY currency_code ASC");
$exchange_rates = [];
while($r = $rates_query->fetch_assoc()) {
    $exchange_rates[] = $r;
}

// Process exchange transaction
$message = '';
$msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exchange'])) {
    $res_id = intval($_POST['res_id']);
    $room_id = intval($_POST['room_id']);
    $currency = mysqli_real_escape_string($conn, $_POST['currency']);
    $amount_given = floatval($_POST['amount_given']);
    $exchange_rate = floatval($_POST['exchange_rate']);
    $amount_received = floatval($_POST['amount_received']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $created_by = $_SESSION['user_id'] ?? 0;
    $username = $_SESSION['username'] ?? 'System';
    
    if ($res_id > 0 && $currency && $amount_given > 0 && $exchange_rate > 0) {
        // Insert exchange transaction
        $insert = "INSERT INTO exchange_transactions 
                    (res_id, room_id, currency, amount_given, exchange_rate, amount_received, notes, created_by, created_at) 
                    VALUES ($res_id, $room_id, '$currency', $amount_given, $exchange_rate, $amount_received, '$notes', $created_by, NOW())";
        if ($conn->query($insert)) {
            $exchange_id = $conn->insert_id;
            
            // Also post to folio as payment (LKR amount received)
            $desc = "Currency Exchange: $amount_given $currency @ $exchange_rate = LKR $amount_received";
            $conn->query("INSERT INTO folio_transactions 
                          (res_id, amount, description, trans_type, reference_no, created_by, status) 
                          VALUES ($res_id, $amount_received, '$desc', 'payment', 'EXCHANGE', $created_by, 'active')");
            
            // Log audit
            $conn->query("INSERT INTO audit_logs (user_id, username, action, description) 
                          VALUES ($created_by, '$username', 'CURRENCY_EXCHANGE', 
                          'Exchanged $amount_given $currency to LKR $amount_received for reservation #$res_id')");
            
            $message = "Currency exchange completed successfully!";
            $msg_type = "success";
            
            // Redirect to receipt
            header("Location: print_exchange_receipt.php?id=$exchange_id");
            exit();
        } else {
            $message = "Database error: " . $conn->error;
            $msg_type = "danger";
        }
    } else {
        $message = "Please fill all required fields.";
        $msg_type = "warning";
    }
}

// Get exchange rates for dropdown
$currencies = $conn->query("SELECT * FROM exchange_rates WHERE status = 'active' ORDER BY currency_code ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Exchange - Araliya PMS</title>
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
        
        .search-box { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
        .guest-info-box { background: #f0f7fa; padding: 15px; border-radius: 8px; border-left: 4px solid #0d4b68; display: none; }
        .guest-info-box.show { display: block; }
        .guest-info-box .label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600; }
        .guest-info-box .value { font-size: 16px; font-weight: 700; color: #1e293b; }
        
        .exchange-form { margin-top: 20px; }
        .exchange-form .form-control, .exchange-form .form-select { background: #fff !important; border: 1px solid #e2e8f0; font-size: 13px; padding: 8px 12px; border-radius: 6px; }
        .exchange-form .form-control:focus, .exchange-form .form-select:focus { border-color: #0d4b68; box-shadow: 0 0 0 3px rgba(13,75,104,0.1); }
        .btn-exchange { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 12px 30px; font-weight: 700; border-radius: 8px; transition: all 0.3s ease; font-size: 16px; }
        .btn-exchange:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(16,185,129,0.4); color: white; }
        
        .rate-display { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 10px 15px; border-radius: 8px; }
        .rate-display .rate-value { font-size: 24px; font-weight: 700; color: #065f46; }
        
        .recent-exchanges { margin-top: 25px; }
        .recent-exchanges table { font-size: 13px; }
        .recent-exchanges th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; }
        .recent-exchanges td { padding: 8px 10px; border-bottom: 1px solid #e9ecef; }
        @media (max-width: 768px) { .page-container { padding: 15px; } }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h4><i class="fas fa-money-bill-wave"></i> Money Exchange</h4>
        <button class="back-btn" onclick="history.back()"><i class="fas fa-arrow-left me-2"></i>Back</button>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Room Search -->
    <div class="search-box">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Search Room Number</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="roomSearch" placeholder="Enter room number..." oninput="searchRoom(this.value)">
                    <button class="btn btn-primary" onclick="searchRoom(document.getElementById('roomSearch').value)"><i class="fas fa-search"></i></button>
                </div>
                <div id="roomSuggestions" class="dropdown-menu show" style="width:100%; display:none; max-height:200px; overflow-y:auto;"></div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Or Select Room</label>
                <select class="form-select" id="roomSelect" onchange="loadRoomData(this.value)">
                    <option value="">-- Select Room --</option>
                    <?php 
                    $rooms = $conn->query("SELECT r.room_id, r.room_number, r.room_type, res.guest_name, res.res_id 
                                            FROM rooms r 
                                            LEFT JOIN reservations res ON r.room_id = res.room_id AND res.status = 'Checked-In'
                                            ORDER BY r.room_number ASC");
                    while($r = $rooms->fetch_assoc()): 
                    ?>
                        <option value="<?= $r['room_id'] ?>" data-res-id="<?= $r['res_id'] ?>" data-guest="<?= htmlspecialchars($r['guest_name']) ?>">
                            Room <?= $r['room_number'] ?> <?= $r['guest_name'] ? ' - ' . htmlspecialchars($r['guest_name']) : '' ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Guest Details</label>
                <div id="guestInfoDisplay" class="p-2 bg-light rounded text-muted" style="min-height:40px;">
                    Select a room to view guest details
                </div>
            </div>
        </div>
    </div>

    <!-- Exchange Form -->
    <div class="exchange-form" id="exchangeForm" style="display:none;">
        <form method="POST" action="" id="exchangeFormSubmit">
            <input type="hidden" name="res_id" id="exchangeResId">
            <input type="hidden" name="room_id" id="exchangeRoomId">
            <input type="hidden" name="submit_exchange" value="1">
            
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Currency</label>
                    <select name="currency" class="form-select" id="currencySelect" onchange="updateExchangeRate()" required>
                        <option value="">-- Select --</option>
                        <?php while($c = $currencies->fetch_assoc()): ?>
                            <option value="<?= $c['currency_code'] ?>" data-rate="<?= $c['rate_to_lkr'] ?>">
                                <?= $c['currency_code'] ?> (<?= $c['currency_name'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Amount Given</label>
                    <input type="number" step="0.01" name="amount_given" class="form-control" id="amountGiven" placeholder="0.00" oninput="calculateExchange()" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Exchange Rate (1 <?= $currency ?? 'USD' ?> = LKR)</label>
                    <input type="number" step="0.01" name="exchange_rate" class="form-control" id="exchangeRate" placeholder="0.00" oninput="calculateExchange()" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Amount Received (LKR)</label>
                    <input type="number" step="0.01" name="amount_received" class="form-control" id="amountReceived" placeholder="0.00" readonly required>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Notes / Remarks</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Guest exchange, Counter exchange">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="rate-display">
                        <span class="text-muted">Exchange Rate:</span> 
                        <span class="rate-value" id="rateDisplay">1.00</span>
                        <span class="text-muted">LKR</span>
                        <span class="ms-3 text-muted">You will receive:</span>
                        <span class="rate-value" id="receiveDisplay">0.00</span>
                        <span class="text-muted">LKR</span>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <button type="submit" class="btn-exchange w-100"><i class="fas fa-exchange-alt me-2"></i>Complete Exchange</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Recent Exchanges -->
    <div class="recent-exchanges mt-4">
        <h6><i class="fas fa-history me-2"></i>Recent Exchanges</h6>
        <div style="max-height:200px; overflow-y:auto;">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Room</th>
                        <th>Guest</th>
                        <th>Currency</th>
                        <th>Amount</th>
                        <th>Rate</th>
                        <th>Received (LKR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent = $conn->query("
                        SELECT e.*, r.room_number, res.guest_name 
                        FROM exchange_transactions e
                        LEFT JOIN rooms r ON e.room_id = r.room_id
                        LEFT JOIN reservations res ON e.res_id = res.res_id
                        ORDER BY e.created_at DESC LIMIT 10
                    ");
                    if($recent && $recent->num_rows > 0):
                        while($ex = $recent->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= date('d/m H:i', strtotime($ex['created_at'])) ?></td>
                        <td>Room <?= $ex['room_number'] ?></td>
                        <td><?= htmlspecialchars($ex['guest_name'] ?? 'N/A') ?></td>
                        <td><strong><?= $ex['currency'] ?></strong></td>
                        <td><?= number_format($ex['amount_given'], 2) ?></td>
                        <td><?= number_format($ex['exchange_rate'], 2) ?></td>
                        <td><strong>LKR <?= number_format($ex['amount_received'], 2) ?></strong></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="7" class="text-center text-muted">No exchanges yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let selectedResId = 0;
let selectedRoomId = 0;
let selectedGuest = '';

function searchRoom(query) {
    const suggestions = document.getElementById('roomSuggestions');
    if (query.length < 2) {
        suggestions.style.display = 'none';
        return;
    }
    
    fetch('ajax_search_room.php?q=' + encodeURIComponent(query))
    .then(response => response.json())
    .then(data => {
        if (data.length > 0) {
            suggestions.style.display = 'block';
            suggestions.innerHTML = data.map(r => 
                `<div class="dropdown-item" onclick="selectRoom(${r.room_id}, ${r.res_id || 0}, '${r.guest_name || ''}', '${r.room_number}')">
                    Room ${r.room_number} - ${r.guest_name || 'Vacant'} (${r.room_type || ''})
                    ${r.res_id ? '<span class="badge bg-success ms-2">Occupied</span>' : '<span class="badge bg-secondary ms-2">Vacant</span>'}
                </div>`
            ).join('');
        } else {
            suggestions.style.display = 'block';
            suggestions.innerHTML = '<div class="dropdown-item text-muted">No rooms found</div>';
        }
    })
    .catch(err => console.error(err));
}

function selectRoom(roomId, resId, guestName, roomNumber) {
    document.getElementById('roomSearch').value = roomNumber;
    document.getElementById('roomSuggestions').style.display = 'none';
    
    if (resId > 0 && guestName) {
        loadRoomDataById(roomId, resId, guestName, roomNumber);
    } else {
        document.getElementById('guestInfoDisplay').innerHTML = `
            <span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> Room ${roomNumber} is vacant. No guest to exchange.</span>
        `;
        document.getElementById('exchangeForm').style.display = 'none';
    }
}

function loadRoomData(roomId) {
    if (!roomId) {
        document.getElementById('exchangeForm').style.display = 'none';
        document.getElementById('guestInfoDisplay').innerHTML = 'Select a room to view guest details';
        return;
    }
    const select = document.getElementById('roomSelect');
    const option = select.options[select.selectedIndex];
    const resId = option.dataset.resId || 0;
    const guest = option.dataset.guest || '';
    const roomNumber = option.text.split(' - ')[0].replace('Room ', '');
    
    loadRoomDataById(roomId, resId, guest, roomNumber);
}

function loadRoomDataById(roomId, resId, guestName, roomNumber) {
    selectedResId = resId;
    selectedRoomId = roomId;
    selectedGuest = guestName;
    
    if (resId > 0 && guestName) {
        document.getElementById('guestInfoDisplay').innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="label">Guest Name</div>
                    <div class="value"><i class="fas fa-user-circle me-1"></i>${guestName}</div>
                </div>
                <div class="col-md-6">
                    <div class="label">Room Number</div>
                    <div class="value"><i class="fas fa-door-open me-1"></i>Room ${roomNumber}</div>
                </div>
            </div>
        `;
        document.getElementById('exchangeResId').value = resId;
        document.getElementById('exchangeRoomId').value = roomId;
        document.getElementById('exchangeForm').style.display = 'block';
    } else {
        document.getElementById('guestInfoDisplay').innerHTML = `
            <span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> Room ${roomNumber} is vacant. No guest to exchange.</span>
        `;
        document.getElementById('exchangeForm').style.display = 'none';
    }
}

function updateExchangeRate() {
    const select = document.getElementById('currencySelect');
    const rate = select.options[select.selectedIndex]?.dataset?.rate || 0;
    document.getElementById('exchangeRate').value = rate;
    calculateExchange();
}

function calculateExchange() {
    const amount = parseFloat(document.getElementById('amountGiven').value) || 0;
    const rate = parseFloat(document.getElementById('exchangeRate').value) || 0;
    const received = amount * rate;
    document.getElementById('amountReceived').value = received.toFixed(2);
    document.getElementById('receiveDisplay').textContent = received.toFixed(2);
    document.getElementById('rateDisplay').textContent = rate.toFixed(2);
}

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#roomSearch') && !e.target.closest('#roomSuggestions')) {
        document.getElementById('roomSuggestions').style.display = 'none';
    }
});

// Auto-select first exchange rate when form loads
document.addEventListener('DOMContentLoaded', function() {
    updateExchangeRate();
});
</script>
</body>
</html>