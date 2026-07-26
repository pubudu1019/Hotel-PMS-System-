<?php
// Session check and get logged-in user
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'includes/db.php';

$id = intval($_GET['id']);

// First check if reservation exists
$check_query = $conn->query("SELECT res_id FROM reservations WHERE res_id=$id");
if (!$check_query || $check_query->num_rows == 0) {
    header("Location: arrivals.php");
    exit();
}

// Get reservation with room details - removed 'floor' column
$query = $conn->query("SELECT r.*, rm.room_number, rm.room_type 
                       FROM reservations r 
                       LEFT JOIN rooms rm ON r.room_id = rm.room_id 
                       WHERE r.res_id=$id");
$data = $query->fetch_assoc();

// If no data found, redirect back
if (!$data) {
    header("Location: arrivals.php");
    exit();
}

// Calculate nights
$checkin = new DateTime($data['check_in']);
$checkout = new DateTime($data['check_out']);
$nights = $checkin->diff($checkout)->format("%a");

// Format dates
$check_in_formatted = date('d M Y', strtotime($data['check_in']));
$check_out_formatted = date('d M Y', strtotime($data['check_out']));

// ABR Number
$abr_number = !empty($data['res_no']) ? $data['res_no'] : 'ABR' . str_pad($data['res_id'], 5, '0', STR_PAD_LEFT);

// Logged user
$logged_user = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 
               (isset($_SESSION['username']) ? $_SESSION['username'] : 'Duty Receptionist');

// Booking source with badge color
$source_colors = [
    'Walk-in' => '#10b981',
    'Online' => '#3b82f6',
    'Agoda' => '#f59e0b',
    'Booking.com' => '#2563eb',
    'Direct' => '#8b5cf6'
];
$source_color = $source_colors[$data['booking_source']] ?? '#6b7280';

// Gender icon
$gender_icon = $data['gender'] == 'Male' ? '♂' : ($data['gender'] == 'Female' ? '♀' : '');

// Get current date/time for display
$current_datetime = date('d M Y h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRC - <?= htmlspecialchars($data['guest_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background: #f0f4f8;
            color: #1a202c;
        }
        
        .grc-container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        /* HEADER */
        .header {
            background: linear-gradient(135deg, #1a2234 0%, #2d3748 100%);
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid #fbbf24;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-icon {
            font-size: 48px;
            line-height: 1;
        }
        
        .hotel-info h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        
        .hotel-info p {
            color: #a0aec0;
            font-size: 12px;
            letter-spacing: 2px;
        }
        
        .grc-badge {
            background: #fbbf24;
            color: #1a2234;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 1px;
        }
        
        .grc-badge i {
            margin-right: 6px;
        }
        
        /* BODY */
        .body-content {
            padding: 35px 40px 30px;
        }
        
        /* Guest Info Summary */
        .guest-summary {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #e2e8f0;
        }
        
        .guest-name-section h2 {
            font-size: 28px;
            font-weight: 700;
            color: #1a2234;
            margin-bottom: 5px;
        }
        
        .guest-name-section .guest-detail {
            color: #4a5568;
            font-size: 14px;
            margin-top: 3px;
        }
        
        .guest-name-section .guest-detail i {
            color: #fbbf24;
            width: 20px;
        }
        
        .guest-name-section .guest-detail span {
            margin-left: 15px;
        }
        
        .status-badge {
            background: #10b981;
            color: white;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-badge i {
            margin-right: 5px;
        }
        
        /* Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 30px;
            margin: 20px 0 25px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: #f7fafc;
            border-radius: 10px;
            border-left: 3px solid #fbbf24;
        }
        
        .detail-item .icon {
            width: 30px;
            color: #fbbf24;
            font-size: 16px;
            text-align: center;
        }
        
        .detail-item .label {
            font-size: 11px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .detail-item .value {
            font-size: 15px;
            font-weight: 600;
            color: #1a202c;
        }
        
        .detail-item.full-width {
            grid-column: 1 / -1;
        }
        
        /* Room Info Box */
        .room-info-box {
            background: linear-gradient(135deg, #fef9e7 0%, #fdf6e3 100%);
            border: 1px solid #fbbf24;
            border-radius: 12px;
            padding: 18px 25px;
            margin: 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .room-info-box .room-number {
            font-size: 32px;
            font-weight: 800;
            color: #1a2234;
        }
        
        .room-info-box .room-detail {
            font-size: 14px;
            color: #4a5568;
        }
        
        .room-info-box .room-detail i {
            margin-right: 5px;
        }
        
        .room-info-box .night-badge {
            background: #1a2234;
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .room-info-box .night-badge i {
            margin-right: 5px;
        }
        
        /* Agreement Text */
        .agreement-text {
            font-size: 13px;
            color: #4a5568;
            margin: 15px 0 10px;
            font-style: italic;
        }
        
        .agreement-text i {
            color: #fbbf24;
            margin-right: 5px;
        }
        
        /* Signature Section */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #e2e8f0;
        }
        
        .signature-box {
            flex: 1;
        }
        
        .signature-box .line {
            border-bottom: 1px solid #a0aec0;
            width: 80%;
            margin-top: 5px;
            margin-bottom: 5px;
            height: 30px;
        }
        
        .signature-box .label {
            font-size: 12px;
            color: #718096;
            font-weight: 600;
        }
        
        .signature-box .name {
            font-size: 14px;
            font-weight: 600;
            color: #1a202c;
            margin-top: 5px;
        }
        
        .signature-box .role {
            font-size: 11px;
            color: #718096;
        }
        
        .signature-box .role i {
            margin-right: 3px;
        }
        
        /* Policies Section */
        .policies-section {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px 25px;
            margin-top: 25px;
        }
        
        .policies-section h4 {
            color: #1a2234;
            margin-bottom: 12px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .policies-section h4 i {
            color: #fbbf24;
        }
        
        .policies-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 25px;
            font-size: 11px;
            color: #4a5568;
            line-height: 1.6;
            padding: 0;
        }
        
        .policies-grid li {
            list-style: none;
            padding: 4px 0;
        }
        
        .policies-grid li:before {
            content: "▸ ";
            color: #fbbf24;
            font-weight: bold;
        }
        
        /* Footer */
        .footer {
            background: #f7fafc;
            padding: 15px 40px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            color: #a0aec0;
        }
        
        .footer i {
            color: #fbbf24;
            margin: 0 3px;
        }
        
        /* Buttons */
        .action-buttons {
            text-align: center;
            margin-top: 25px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-print {
            background: #fbbf24;
            color: #1a2234;
        }
        
        .btn-print:hover {
            background: #f59e0b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(251, 191, 36, 0.3);
        }
        
        .btn-back {
            background: #e2e8f0;
            color: #1a2234;
        }
        
        .btn-back:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }
        
        .btn-edit {
            background: #3b82f6;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .grc-container {
                box-shadow: none;
                border-radius: 0;
                border: none;
            }
            .header {
                background: #1a2234 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .action-buttons,
            .no-print {
                display: none !important;
            }
            .detail-item {
                background: #f7fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .room-info-box {
                background: #fef9e7 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .policies-section {
                background: #f7fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .status-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .grc-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 20px;
            }
            .details-grid {
                grid-template-columns: 1fr;
            }
            .guest-summary {
                flex-direction: column;
                gap: 15px;
            }
            .signature-section {
                flex-direction: column;
                gap: 20px;
            }
            .body-content {
                padding: 20px;
            }
            .room-info-box {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            .policies-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }
            .guest-name-section .guest-detail span {
                display: block;
                margin-left: 0;
                margin-top: 3px;
            }
        }
    </style>
</head>
<body>

<div class="grc-container">
    <!-- HEADER -->
    <div class="header">
        <div class="header-left">
            <div class="logo-icon">🌺</div>
            <div class="hotel-info">
                <h1>ARALIYA BEACH RESORT</h1>
                <p>✦ PROPERTY MANAGEMENT SYSTEM ✦</p>
            </div>
        </div>
        <div class="grc-badge">
            <i class="fas fa-id-card"></i> GRC #<?= htmlspecialchars($abr_number) ?>
        </div>
    </div>

    <!-- BODY -->
    <div class="body-content">
        <!-- Guest Summary -->
        <div class="guest-summary">
            <div class="guest-name-section">
                <h2><?= htmlspecialchars($data['guest_name']) ?></h2>
                <div class="guest-detail">
                    <i class="fas fa-passport"></i> <?= htmlspecialchars($data['passport_no'] ?? 'N/A') ?>
                    <?php if ($gender_icon): ?>
                        <span><?= $gender_icon ?> <?= htmlspecialchars($data['gender'] ?? 'N/A') ?></span>
                    <?php endif; ?>
                    <span><i class="fas fa-globe"></i> <?= htmlspecialchars($data['nationality'] ?? 'N/A') ?></span>
                </div>
            </div>
            <div>
                <span class="status-badge"><i class="fas fa-check-circle"></i> Confirmed</span>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="details-grid">
            <div class="detail-item">
                <span class="icon"><i class="fas fa-calendar-check"></i></span>
                <div>
                    <div class="label">Check-in</div>
                    <div class="value"><?= $check_in_formatted ?></div>
                </div>
            </div>
            <div class="detail-item">
                <span class="icon"><i class="fas fa-calendar-times"></i></span>
                <div>
                    <div class="label">Check-out</div>
                    <div class="value"><?= $check_out_formatted ?></div>
                </div>
            </div>
            <div class="detail-item">
                <span class="icon"><i class="fas fa-users"></i></span>
                <div>
                    <div class="label">Guests</div>
                    <div class="value"><?= $data['adults'] ?> Adult<?= $data['adults'] > 1 ? 's' : '' ?> <?= $data['children'] > 0 ? '+ '.$data['children'].' Child'.($data['children'] > 1 ? 'ren' : '') : '' ?></div>
                </div>
            </div>
            <div class="detail-item">
                <span class="icon"><i class="fas fa-tag"></i></span>
                <div>
                    <div class="label">Booking Source</div>
                    <div class="value" style="color: <?= $source_color ?>;"><?= htmlspecialchars($data['booking_source'] ?? 'Walk-in') ?></div>
                </div>
            </div>
            <?php if (!empty($data['special_requests'])): ?>
            <div class="detail-item full-width">
                <span class="icon"><i class="fas fa-comment"></i></span>
                <div>
                    <div class="label">Special Requests</div>
                    <div class="value" style="font-weight: 400; font-size: 14px;"><?= htmlspecialchars($data['special_requests']) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Room Info -->
        <div class="room-info-box">
            <div>
                <div class="room-detail"><i class="fas fa-door-open"></i> Room Number</div>
                <div class="room-number"><?= !empty($data['room_number']) ? htmlspecialchars($data['room_number']) : 'Pending' ?></div>
                <?php if (!empty($data['room_type'])): ?>
                    <div class="room-detail" style="font-size: 12px; margin-top: 2px;">
                        <i class="fas fa-bed"></i> <?= htmlspecialchars($data['room_type']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="night-badge">
                <i class="fas fa-moon"></i> <?= $nights ?> Night<?= $nights > 1 ? 's' : '' ?>
            </div>
        </div>

        <!-- Agreement -->
        <div class="agreement-text">
            <i class="fas fa-check-circle"></i> 
            I hereby agree to the hotel policies and terms of stay as stated below.
        </div>

        <!-- Signature -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="label">Guest Signature</div>
                <div class="line"></div>
                <div class="name"><?= htmlspecialchars($data['guest_name']) ?></div>
            </div>
            <div class="signature-box" style="text-align: right;">
                <div class="label">Receptionist</div>
                <div class="line" style="margin-left: 20%;"></div>
                <div class="name"><?= htmlspecialchars($logged_user) ?></div>
                <div class="role"><i class="far fa-clock"></i> <?= $current_datetime ?></div>
            </div>
        </div>

        <!-- Policies -->
        <div class="policies-section">
            <h4><i class="fas fa-gavel"></i> Hotel Policies & Terms</h4>
            <ul class="policies-grid">
                <li>Check-in: 2:00 PM | Check-out: 11:00 AM</li>
                <li>Valid Passport/ID required upon check-in</li>
                <li>Full payment at time of check-in</li>
                <li>Smoking prohibited inside rooms</li>
                <li>External visitors not permitted in rooms</li>
                <li>Guest liable for any property damage</li>
                <li>Quiet hours: 10:00 PM - 7:00 AM</li>
                <li>By signing, guest accepts all rules</li>
            </ul>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <i class="fas fa-print"></i> Generated on <?= $current_datetime ?> | 
        <i class="fas fa-shield-alt"></i> GRC #<?= htmlspecialchars($abr_number) ?>
    </div>
</div>

<!-- ACTION BUTTONS -->
<div class="action-buttons no-print">
    <button onclick="window.print()" class="btn btn-print">
        <i class="fas fa-print"></i> Print GRC
    </button>
    <a href="edit_reservation.php?id=<?= $id ?>" class="btn btn-edit">
        <i class="fas fa-edit"></i> Edit
    </a>
    <a href="arrivals.php" class="btn btn-back">
        <i class="fas fa-arrow-left"></i> Back to Arrivals
    </a>
</div>

</body>
</html>