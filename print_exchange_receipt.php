<?php
include 'includes/session_check.php';
include 'includes/db.php';

$exchange_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($exchange_id == 0) die("Invalid exchange ID");

$query = $conn->query("
    SELECT e.*, r.room_number, res.guest_name, u.full_name as staff_name, u.username
    FROM exchange_transactions e
    LEFT JOIN rooms r ON e.room_id = r.room_id
    LEFT JOIN reservations res ON e.res_id = res.res_id
    LEFT JOIN users u ON e.created_by = u.user_id
    WHERE e.exchange_id = $exchange_id
");

if (!$query || $query->num_rows == 0) die("Exchange record not found");
$data = $query->fetch_assoc();

$hotel_name = "Araliya Beach Resort & Spa";
$hotel_address = "Galle Road, Unawatuna, Sri Lanka";
$hotel_phone = "+94 91 234 5678";

// Get hotel settings if exists
$settings = $conn->query("SELECT hotel_name, address, phone FROM hotel_settings LIMIT 1");
if ($settings && $settings->num_rows > 0) {
    $s = $settings->fetch_assoc();
    $hotel_name = $s['hotel_name'] ?? $hotel_name;
    $hotel_address = $s['address'] ?? $hotel_address;
    $hotel_phone = $s['phone'] ?? $hotel_phone;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Exchange Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 20px !important; }
            .receipt-container { box-shadow: none !important; border: none !important; }
        }
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .receipt-container { background: white; max-width: 600px; margin: 0 auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .receipt-header { text-align: center; border-bottom: 2px dashed #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
        .receipt-header h2 { color: #0d4b68; font-weight: 700; }
        .receipt-header small { color: #64748b; }
        .receipt-details { margin-bottom: 20px; }
        .receipt-details .row { margin-bottom: 8px; }
        .receipt-details .label { color: #64748b; font-size: 12px; font-weight: 600; }
        .receipt-details .value { font-size: 15px; font-weight: 600; color: #1e293b; }
        .receipt-amount { background: #f0fdf4; padding: 15px; border-radius: 8px; text-align: center; margin: 15px 0; border: 1px solid #bbf7d0; }
        .receipt-amount .amount { font-size: 28px; font-weight: 800; color: #065f46; }
        .receipt-amount .label { font-size: 12px; color: #64748b; }
        .receipt-footer { border-top: 2px dashed #e2e8f0; padding-top: 20px; margin-top: 20px; text-align: center; }
        .signature-area { margin-top: 20px; display: flex; justify-content: space-between; }
        .signature-area .sig-box { text-align: center; width: 45%; }
        .signature-area .sig-line { border-bottom: 2px solid #1e293b; height: 50px; }
        .btn-print { background: #0d4b68; color: white; border: none; padding: 10px 30px; border-radius: 8px; font-weight: 600; }
        .btn-print:hover { background: #1a6f8e; color: white; }
    </style>
</head>
<body>
<div class="receipt-container" id="receiptContent">
    <!-- Header -->
    <div class="receipt-header">
        <h2><i class="fas fa-hotel me-2"></i><?= htmlspecialchars($hotel_name) ?></h2>
        <small><?= htmlspecialchars($hotel_address) ?></small><br>
        <small><i class="fas fa-phone me-1"></i><?= htmlspecialchars($hotel_phone) ?></small>
        <h5 class="mt-2">CURRENCY EXCHANGE RECEIPT</h5>
        <span class="badge bg-primary">#EX-<?= str_pad($data['exchange_id'], 6, '0', STR_PAD_LEFT) ?></span>
    </div>

    <!-- Details -->
    <div class="receipt-details">
        <div class="row">
            <div class="col-6"><span class="label">Date & Time</span></div>
            <div class="col-6"><span class="value"><?= date('d/m/Y h:i A', strtotime($data['created_at'])) ?></span></div>
        </div>
        <div class="row">
            <div class="col-6"><span class="label">Guest Name</span></div>
            <div class="col-6"><span class="value"><?= htmlspecialchars($data['guest_name'] ?? 'N/A') ?></span></div>
        </div>
        <div class="row">
            <div class="col-6"><span class="label">Room Number</span></div>
            <div class="col-6"><span class="value"><?= htmlspecialchars($data['room_number'] ?? 'N/A') ?></span></div>
        </div>
        <div class="row">
            <div class="col-6"><span class="label">Currency</span></div>
            <div class="col-6"><span class="value"><?= htmlspecialchars($data['currency']) ?></span></div>
        </div>
        <div class="row">
            <div class="col-6"><span class="label">Amount Given</span></div>
            <div class="col-6"><span class="value"><?= number_format($data['amount_given'], 2) ?> <?= htmlspecialchars($data['currency']) ?></span></div>
        </div>
        <div class="row">
            <div class="col-6"><span class="label">Exchange Rate</span></div>
            <div class="col-6"><span class="value">1 <?= htmlspecialchars($data['currency']) ?> = LKR <?= number_format($data['exchange_rate'], 2) ?></span></div>
        </div>
        <div class="row">
            <div class="col-6"><span class="label">Processed By</span></div>
            <div class="col-6"><span class="value"><?= htmlspecialchars($data['staff_name'] ?? $data['username'] ?? 'System') ?></span></div>
        </div>
        <?php if($data['notes']): ?>
        <div class="row">
            <div class="col-6"><span class="label">Notes</span></div>
            <div class="col-6"><span class="value small"><?= htmlspecialchars($data['notes']) ?></span></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Amount Received -->
    <div class="receipt-amount">
        <div class="label">AMOUNT RECEIVED (LKR)</div>
        <div class="amount">LKR <?= number_format($data['amount_received'], 2) ?></div>
    </div>

    <!-- Footer -->
    <div class="receipt-footer">
        <p class="small text-muted">Thank you for choosing <?= htmlspecialchars($hotel_name) ?></p>
        <p class="small text-muted">This is a system generated receipt</p>
    </div>

    <!-- Signature Area -->
    <div class="signature-area">
        <div class="sig-box">
            <div class="sig-line"></div>
            <span class="small text-muted">Guest Signature</span>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <span class="small text-muted">Cashier Signature</span>
        </div>
    </div>
</div>

<div class="text-center mt-3 no-print">
    <button class="btn-print" onclick="window.print()"><i class="fas fa-print me-2"></i>Print Receipt</button>
    <a href="money_exchange.php" class="btn btn-secondary ms-2"><i class="fas fa-arrow-left me-2"></i>Back to Exchange</a>
</div>

<script>
window.onload = function() {
    setTimeout(function() {
        window.print();
    }, 500);
};
</script>
</body>
</html>