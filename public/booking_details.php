<?php
// public/booking_details.php - Booking Details
session_start();
include '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['online_user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['online_user_id'];
$user_name = $_SESSION['online_user_name'];

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) {
    header('Location: my_bookings.php');
    exit;
}

// Get booking details
$booking_query = $conn->prepare("
    SELECT ob.*, r.room_number, rt.type_name, rt.description as room_desc
    FROM online_bookings ob
    LEFT JOIN rooms r ON ob.room_id = r.room_id
    LEFT JOIN room_types rt ON r.room_type = rt.type_name
    WHERE ob.booking_id = ? AND ob.user_id = ?
");
$booking_query->bind_param("ii", $booking_id, $user_id);
$booking_query->execute();
$result = $booking_query->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header('Location: my_bookings.php');
    exit;
}

$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
$hotel_phone = isset($hotel['phone']) ? $hotel['phone'] : '+94 91 234 5678';
$hotel_address = isset($hotel['address']) ? $hotel['address'] : 'Galle Road, Unawatuna';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - <?php echo $hotel_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #0d4b68; --gold: #fbbf24; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f8; }
        
        .navbar-custom {
            background: linear-gradient(135deg, #082f42, #0d4b68);
            padding: 12px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .navbar-custom .brand {
            color: var(--gold);
            font-size: 22px;
            font-weight: 800;
            text-decoration: none;
        }
        .navbar-custom .brand i { color: var(--gold); }
        .navbar-custom .brand-sub {
            color: rgba(255,255,255,0.6);
            font-size: 10px;
            display: block;
            letter-spacing: 0.5px;
        }
        .navbar-custom .nav-link {
            color: rgba(255,255,255,0.8);
            font-weight: 600;
            font-size: 14px;
            padding: 8px 18px;
            transition: 0.3s;
        }
        .navbar-custom .nav-link:hover { color: var(--gold); }
        .btn-logout {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 6px 18px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-logout:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }
        
        .detail-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid #eef2f5;
        }
        .detail-card .status-badge {
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-badge.confirmed { background: #d1fae5; color: #065f46; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.cancelled { background: #fee2e2; color: #991b1b; }
        .status-badge.checked_in { background: #dbeafe; color: #1e40af; }
        .status-badge.checked_out { background: #e5e7eb; color: #374151; }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: #64748b; font-weight: 500; }
        .info-row .value { font-weight: 600; color: #082f42; }
        
        .btn-back {
            background: #e2e8f0;
            color: #475569;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-back:hover {
            background: #cbd5e1;
            color: #1e293b;
        }
        .btn-cancel {
            background: #ef4444;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-cancel:hover {
            background: #dc2626;
            color: white;
        }
        
        .footer { background: #0a1828; color: rgba(255,255,255,0.7); padding: 30px 0 15px; margin-top: 40px; }
        .footer .brand { color: var(--gold); font-size: 20px; font-weight: 800; }
        .footer a { color: rgba(255,255,255,0.6); text-decoration: none; transition: 0.3s; }
        .footer a:hover { color: var(--gold); }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; margin-top: 20px; font-size: 13px; }
        
        @media (max-width: 768px) {
            .detail-card { padding: 20px; }
            .info-row { flex-direction: column; gap: 4px; }
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a href="index.php" class="brand">
            <i class="fas fa-hotel me-1"></i> ARALIYA
            <span class="brand-sub">Beach Resort &amp; Spa Unawatuna</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="rooms.php" class="nav-link">Rooms</a></li>
                <li class="nav-item"><a href="my_bookings.php" class="nav-link active"><i class="fas fa-list me-1"></i> My Bookings</a></li>
                <li class="nav-item"><a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== BOOKING DETAILS ===== -->
<section class="py-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Back Button -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="my_bookings.php" class="btn-back"><i class="fas fa-arrow-left me-2"></i> Back to Bookings</a>
                    <?php 
                    $can_cancel = ($booking['booking_status'] != 'cancelled' && $booking['booking_status'] != 'checked_in' && $booking['booking_status'] != 'checked_out');
                    $is_upcoming = strtotime($booking['check_in']) >= strtotime(date('Y-m-d'));
                    if($can_cancel && $is_upcoming): 
                    ?>
                    <a href="cancel_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel this booking?')">
                        <i class="fas fa-times me-2"></i> Cancel Booking
                    </a>
                    <?php endif; ?>
                </div>

                <div class="detail-card">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div>
                            <h4 class="fw-bold" style="color: #082f42;">Booking Details</h4>
                            <p class="text-muted small">Booking #<?php echo $booking['booking_reference'] ?? $booking['booking_id']; ?></p>
                        </div>
                        <div>
                            <?php 
                            $status = $booking['booking_status'] ?? 'pending';
                            $status_class = $status == 'confirmed' ? 'confirmed' : ($status == 'cancelled' ? 'cancelled' : ($status == 'checked_in' ? 'checked_in' : ($status == 'checked_out' ? 'checked_out' : 'pending')));
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </div>
                    </div>

                    <hr>

                    <!-- Room Info -->
                    <h6 class="fw-bold mb-3"><i class="fas fa-bed me-2" style="color: var(--gold);"></i> Room Information</h6>
                    <div class="info-row">
                        <span class="label">Room Type</span>
                        <span class="value"><?php echo htmlspecialchars($booking['type_name'] ?? 'Standard'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Room Number</span>
                        <span class="value"><?php echo $booking['room_number'] ?? 'Not Assigned Yet'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Description</span>
                        <span class="value"><?php echo htmlspecialchars($booking['room_desc'] ?? 'Comfortable room with modern amenities'); ?></span>
                    </div>

                    <hr>

                    <!-- Guest Info -->
                    <h6 class="fw-bold mb-3"><i class="fas fa-user me-2" style="color: var(--gold);"></i> Guest Information</h6>
                    <div class="info-row">
                        <span class="label">Full Name</span>
                        <span class="value"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email</span>
                        <span class="value"><?php echo htmlspecialchars($booking['guest_email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Phone</span>
                        <span class="value"><?php echo htmlspecialchars($booking['guest_phone']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Nationality</span>
                        <span class="value"><?php echo htmlspecialchars($booking['guest_nationality'] ?? 'Not Specified'); ?></span>
                    </div>

                    <hr>

                    <!-- Stay Info -->
                    <h6 class="fw-bold mb-3"><i class="fas fa-calendar-alt me-2" style="color: var(--gold);"></i> Stay Information</h6>
                    <div class="info-row">
                        <span class="label">Check In</span>
                        <span class="value"><?php echo date('l, d M Y', strtotime($booking['check_in'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Check Out</span>
                        <span class="value"><?php echo date('l, d M Y', strtotime($booking['check_out'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Nights</span>
                        <span class="value"><?php echo $booking['num_of_nights']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Guests</span>
                        <span class="value"><?php echo $booking['adults']; ?> Adults, <?php echo $booking['children']; ?> Children</span>
                    </div>

                    <hr>

                    <!-- Payment Info -->
                    <h6 class="fw-bold mb-3"><i class="fas fa-credit-card me-2" style="color: var(--gold);"></i> Payment Information</h6>
                    <div class="info-row">
                        <span class="label">Rate per Night</span>
                        <span class="value">LKR <?php echo number_format($booking['room_rate'], 0); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Total Amount</span>
                        <span class="value" style="color: #d97706; font-size: 18px;">LKR <?php echo number_format($booking['total_amount'], 0); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Payment Status</span>
                        <span class="value">
                            <?php if($booking['payment_status'] == 'paid'): ?>
                                <span style="color: #10b981;">✅ Paid</span>
                            <?php else: ?>
                                <span style="color: #f59e0b;">⏳ Pending</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if(!empty($booking['payment_method'])): ?>
                    <div class="info-row">
                        <span class="label">Payment Method</span>
                        <span class="value"><?php echo ucfirst($booking['payment_method']); ?></span>
                    </div>
                    <?php endif; ?>

                    <hr>

                    <!-- Special Requests -->
                    <?php if(!empty($booking['special_requests'])): ?>
                    <h6 class="fw-bold mb-3"><i class="fas fa-comment me-2" style="color: var(--gold);"></i> Special Requests</h6>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                    <hr>
                    <?php endif; ?>

                    <!-- Booking Date -->
                    <div class="text-muted small text-center">
                        Booked on: <?php echo date('d M Y h:i A', strtotime($booking['created_at'])); ?>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="mt-3 text-center">
                    <p class="text-muted small">
                        <i class="fas fa-phone me-1"></i> Need help? Call us: <strong><?php echo $hotel_phone; ?></strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="brand">ARALIYA</div>
                <p style="font-size:13px;">Beach Resort &amp; Spa Unawatuna</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="index.php">Home</a> | 
                <a href="rooms.php">Rooms</a> | 
                <a href="logout.php">Logout</a>
            </div>
        </div>
        <div class="footer-bottom text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo $hotel_name; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>