<?php
// public/payment.php - SUPER ADVANCED PROFESSIONAL VERSION
session_start();
include '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['online_user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['online_user_id'];
$user_name = $_SESSION['online_user_name'];
$is_logged_in = isset($_SESSION['online_user_id']) && !empty($_SESSION['online_user_id']);
$logged_in_user = $is_logged_in ? $_SESSION['online_user_name'] : '';

$error = '';
$success = '';

// Get booking details
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) {
    header('Location: my_bookings.php');
    exit;
}

// Get booking data
$booking_query = $conn->prepare("
    SELECT ob.*, r.room_number, rt.type_name 
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

// If already paid, redirect
if ($booking['payment_status'] == 'paid') {
    header('Location: booking_success.php?id=' . $booking_id);
    exit;
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $card_number = isset($_POST['card_number']) ? preg_replace('/\s+/', '', $_POST['card_number']) : '';
    $card_name = isset($_POST['card_name']) ? trim($_POST['card_name']) : '';
    $card_expiry = isset($_POST['card_expiry']) ? $_POST['card_expiry'] : '';
    $card_cvv = isset($_POST['card_cvv']) ? $_POST['card_cvv'] : '';
    
    if (empty($card_number) || empty($card_name) || empty($card_expiry) || empty($card_cvv)) {
        $error = 'Please fill in all payment details.';
    } elseif (strlen($card_number) < 16) {
        $error = 'Please enter a valid card number.';
    } elseif (strlen($card_cvv) < 3) {
        $error = 'Please enter a valid CVV.';
    } else {
        // ================================================================
        // ===== UPDATE ONLINE BOOKING =====
        // ================================================================
        $update = $conn->prepare("
            UPDATE online_bookings 
            SET payment_status = 'paid', 
                payment_method = 'card',
                booking_status = 'confirmed'
            WHERE booking_id = ?
        ");
        $update->bind_param("i", $booking_id);
        
        if ($update->execute()) {
            // Insert payment transaction
            $amount = $booking['total_amount'];
            $trans_stmt = $conn->prepare("
                INSERT INTO payment_transactions (
                    booking_id, amount, payment_method, status, completed_at
                ) VALUES (?, ?, 'card', 'succeeded', NOW())
            ");
            $trans_stmt->bind_param("id", $booking_id, $amount);
            $trans_stmt->execute();
            $trans_stmt->close();
            
            // ================================================================
            // ===== SYNC TO PMS =====
            // ================================================================
            $room_id = $booking['room_id'];
            
            // Check if room is available
            $room_check = $conn->query("SELECT status FROM rooms WHERE room_id = $room_id");
            $room_status = $room_check->fetch_assoc()['status'] ?? 'available';
            
            if ($room_status != 'available') {
                $room_type = $booking['room_type'] ?? '';
                $alt_room = $conn->query("
                    SELECT room_id FROM rooms 
                    WHERE room_type = '$room_type' AND status = 'available' 
                    LIMIT 1
                ");
                if ($alt_room->num_rows > 0) {
                    $alt = $alt_room->fetch_assoc();
                    $room_id = $alt['room_id'];
                }
            }
            
            $res_no = 'RES' . date('ymd') . str_pad($booking_id, 4, '0', STR_PAD_LEFT);
            $status = 'Pending';
            $booking_source = 'Online Booking';
            $created_by = 1;
            $nationality = isset($booking['guest_nationality']) ? $booking['guest_nationality'] : 'International';
            $special_requests = isset($booking['special_requests']) ? $booking['special_requests'] : '';
            
            $pms_stmt = $conn->prepare("
                INSERT INTO reservations (
                    res_no, guest_name, room_id, check_in, check_out, num_of_nights,
                    status, email, mobile, nationality, booking_source,
                    room_rate, total_charge, adults, children, special_requests, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $pms_stmt->bind_param(
                "ssississsssdssssi",
                $res_no, $booking['guest_name'], $room_id, $booking['check_in'],
                $booking['check_out'], $booking['num_of_nights'], $status,
                $booking['guest_email'], $booking['guest_phone'], $nationality,
                $booking_source, $booking['room_rate'], $booking['total_amount'],
                $booking['adults'], $booking['children'], $special_requests, $created_by
            );
            
            if ($pms_stmt->execute()) {
                $pms_res_id = $conn->insert_id;
                
                // Update online booking with PMS reservation ID
                $conn->query("
                    UPDATE online_bookings 
                    SET pms_reservation_id = $pms_res_id, synced_to_pms = 1
                    WHERE booking_id = $booking_id
                ");
                
                // Update room status
                if ($room_id > 0) {
                    $conn->query("UPDATE rooms SET status = 'occupied' WHERE room_id = $room_id");
                }
                
                $pms_stmt->close();
                
                // ================================================================
                // ===== UPDATE PMS FOLIO =====
                // ================================================================
                
                // Check if folio exists
                $folio_check = $conn->query("
                    SELECT folio_id FROM folios WHERE res_id = $pms_res_id
                ");
                
                if ($folio_check->num_rows == 0) {
                    $conn->query("
                        INSERT INTO folios (res_id, status, created_at)
                        VALUES ($pms_res_id, 'Open', NOW())
                    ");
                    $folio_id = $conn->insert_id;
                } else {
                    $folio = $folio_check->fetch_assoc();
                    $folio_id = $folio['folio_id'];
                }
                
                // Add room charge
                $room_charge_desc = "Room Charge - " . $booking['num_of_nights'] . " nights @ LKR " . number_format($booking['room_rate'], 0);
                
                $folio_stmt = $conn->prepare("
                    INSERT INTO folio_transactions (
                        res_id, folio_id, amount, trans_type, description,
                        created_at, created_by, status
                    ) VALUES (?, ?, ?, 'charge', ?, NOW(), ?, 'active')
                ");
                $folio_stmt->bind_param("iidss", $pms_res_id, $folio_id, $booking['total_amount'], $room_charge_desc, $created_by);
                $folio_stmt->execute();
                $folio_stmt->close();
                
                // Add payment
                $payment_desc = "Online Payment - Card";
                
                $payment_stmt = $conn->prepare("
                    INSERT INTO folio_transactions (
                        res_id, folio_id, amount, trans_type, description,
                        created_at, created_by, status
                    ) VALUES (?, ?, ?, 'payment', ?, NOW(), ?, 'active')
                ");
                $payment_stmt->bind_param("iidss", $pms_res_id, $folio_id, $booking['total_amount'], $payment_desc, $created_by);
                $payment_stmt->execute();
                $payment_stmt->close();
                
                // Update folio status
                $conn->query("
                    UPDATE folios SET status = 'Settled' WHERE folio_id = $folio_id
                ");
                
                // Update reservation total_charge
                $conn->query("
                    UPDATE reservations 
                    SET total_charge = total_charge + " . $booking['total_amount'] . "
                    WHERE res_id = $pms_res_id
                ");
                
                // ================================================================
                // ===== REDIRECT TO SUCCESS =====
                // ================================================================
                header('Location: booking_success.php?id=' . $booking_id);
                exit;
                
            } else {
                $error = 'Payment successful but booking sync failed: ' . $conn->error;
            }
            
        } else {
            $error = 'Payment failed. Please try again.';
        }
        $update->close();
    }
}

$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
$hotel_address = isset($hotel['address']) ? $hotel['address'] : 'Galle Road, Unawatuna, Sri Lanka';
$hotel_phone = isset($hotel['phone']) ? $hotel['phone'] : '+94 91 234 5678';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo $hotel_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d4b68;
            --primary-dark: #082f42;
            --primary-light: #1a6f8e;
            --gold: #fbbf24;
            --gold-dark: #d97706;
            --text-dark: #082f42;
            --text-light: #64748b;
            --white: #ffffff;
            --bg-light: #f8fafc;
            --shadow-sm: 0 4px 20px rgba(0,0,0,0.06);
            --shadow-md: 0 10px 40px rgba(0,0,0,0.08);
            --shadow-lg: 0 20px 60px rgba(0,0,0,0.12);
            --radius-sm: 12px;
            --radius-md: 16px;
            --radius-lg: 24px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, h4, h5, h6 { font-family: 'Playfair Display', serif; }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-light); }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-dark); }

        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary), var(--primary-light));
            padding: 12px 0;
            box-shadow: 0 4px 30px rgba(8, 47, 66, 0.3);
            position: sticky;
            top: 0;
            z-index: 1050;
            backdrop-filter: blur(12px);
            border-bottom: 2px solid rgba(251, 191, 36, 0.2);
        }
        .navbar-custom .brand {
            color: var(--gold);
            font-size: 26px;
            font-weight: 800;
            text-decoration: none;
            letter-spacing: 1.5px;
            font-family: 'Playfair Display', serif;
        }
        .navbar-custom .brand i { color: var(--gold); }
        .navbar-custom .brand-sub {
            color: rgba(255,255,255,0.5);
            font-size: 10px;
            display: block;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 300;
        }
        .navbar-custom .nav-link {
            color: rgba(255,255,255,0.8);
            font-weight: 500;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 25px;
            transition: var(--transition);
            position: relative;
        }
        .navbar-custom .nav-link:hover {
            color: var(--gold);
            background: rgba(251, 191, 36, 0.1);
        }
        .navbar-custom .nav-link.active {
            color: var(--gold);
            background: rgba(251, 191, 36, 0.15);
        }
        .navbar-custom .nav-link::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--gold);
            transition: var(--transition);
            transform: translateX(-50%);
        }
        .navbar-custom .nav-link:hover::after,
        .navbar-custom .nav-link.active::after {
            width: 60%;
        }

        .btn-book-now-nav {
            background: var(--gold);
            color: var(--primary-dark);
            font-weight: 700;
            padding: 8px 25px;
            border-radius: 25px;
            text-decoration: none;
            transition: var(--transition);
            border: 2px solid transparent;
        }
        .btn-book-now-nav:hover {
            background: var(--gold-dark);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(251, 191, 36, 0.3);
        }

        .user-dropdown {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            color: var(--white);
            padding: 6px 12px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(8px);
        }
        .user-dropdown:hover {
            background: rgba(255,255,255,0.2);
            color: var(--gold);
        }
        .user-dropdown .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--gold);
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
        }

        .page-hero {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary), var(--primary-light));
            color: var(--white);
            padding: 40px 0 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 30px;
        }
        .page-hero::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 50%;
            height: 150%;
            background: rgba(251, 191, 36, 0.04);
            border-radius: 50%;
            transform: rotate(15deg);
        }
        .page-hero .container { position: relative; z-index: 2; }
        .page-hero h1 {
            font-size: 32px;
            font-weight: 800;
            font-family: 'Playfair Display', serif;
        }
        .page-hero h1 span { color: var(--gold); }
        .page-hero p {
            opacity: 0.7;
            font-size: 15px;
            max-width: 500px;
            margin: 8px auto 0;
        }
        .page-hero .breadcrumb {
            background: none;
            padding: 0;
            justify-content: center;
            margin-top: 8px;
        }
        .page-hero .breadcrumb-item a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: var(--transition);
        }
        .page-hero .breadcrumb-item a:hover { color: var(--gold); }
        .page-hero .breadcrumb-item.active { color: var(--gold); }
        .page-hero .breadcrumb-item::before { color: rgba(255,255,255,0.3); }

        .payment-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 30px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            transition: var(--transition);
        }
        .payment-card:hover {
            box-shadow: var(--shadow-md);
        }
        .payment-card .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            transition: var(--transition);
            background: #f8fafc;
        }
        .payment-card .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.1);
            background: var(--white);
        }
        .payment-card .form-label {
            font-weight: 600;
            color: var(--text-light);
            font-size: 13px;
        }
        .payment-card .form-label i { color: var(--gold); width: 18px; }

        .btn-pay {
            background: #10b981;
            color: var(--white);
            padding: 12px 30px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 16px;
            width: 100%;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-pay:hover {
            background: #059669;
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.3);
        }

        .summary-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #eef2f5;
        }
        .summary-box .row {
            padding: 6px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .summary-box .row:last-child { border-bottom: none; }
        .summary-box .label { color: var(--text-light); }
        .summary-box .value { font-weight: 600; color: var(--text-dark); }

        .card-icons { font-size: 28px; color: #94a3b8; margin-right: 8px; }
        .card-icons i:hover { color: var(--primary); }

        .footer {
            background: #0a1828;
            color: rgba(255,255,255,0.7);
            padding: 40px 0 20px;
            margin-top: 40px;
        }
        .footer .brand {
            color: var(--gold);
            font-size: 22px;
            font-weight: 800;
            font-family: 'Playfair Display', serif;
        }
        .footer h5 {
            color: var(--white);
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 15px;
        }
        .footer a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: var(--transition);
            font-size: 13px;
            display: block;
            padding: 4px 0;
        }
        .footer a:hover { color: var(--gold); }
        .footer .social a {
            display: inline-block;
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            text-align: center;
            line-height: 36px;
            margin-right: 6px;
            transition: var(--transition);
            font-size: 14px;
        }
        .footer .social a:hover {
            background: var(--gold);
            color: var(--primary-dark);
            transform: translateY(-3px);
        }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 15px;
            margin-top: 20px;
            font-size: 13px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .page-hero h1 { font-size: 26px; }
            .payment-card { padding: 20px; }
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
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="rooms.php" class="nav-link">Rooms</a></li>
                <li class="nav-item"><a href="gallery.php" class="nav-link">Gallery</a></li>
                <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
                <li class="nav-item"><a href="blog.php" class="nav-link">Blog</a></li>
                <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>

                <?php if($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a href="#" class="user-dropdown dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="user-avatar"><?php echo strtoupper(substr($logged_in_user, 0, 1)); ?></span>
                            <span><?php echo htmlspecialchars($logged_in_user); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="my_bookings.php"><i class="fas fa-list me-2"></i> My Bookings</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a href="login.php" class="nav-link"><i class="fas fa-user me-1"></i> Login</a></li>
                    <li class="nav-item"><a href="register.php" class="btn-book-now-nav"><i class="fas fa-user-plus me-1"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== PAGE HERO ===== -->
<section class="page-hero">
    <div class="container">
        <h1>Complete <span>Payment</span></h1>
        <p>Secure your booking with our safe payment system</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="my_bookings.php">My Bookings</a></li>
                <li class="breadcrumb-item active">Payment</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ===== PAYMENT ===== -->
<section class="py-2">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="payment-card">
                    <div class="row">
                        <div class="col-lg-7">
                            <!-- ===== PAYMENT FORM ===== -->
                            <form method="POST" action="">
                                <h6 class="fw-bold mb-3"><i class="fas fa-lock me-2" style="color: var(--gold);"></i> Card Details</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-user me-2"></i> Cardholder Name</label>
                                    <input type="text" name="card_name" class="form-control" placeholder="John Doe" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-credit-card me-2"></i> Card Number</label>
                                    <input type="text" name="card_number" class="form-control" placeholder="4242 4242 4242 4242" maxlength="19" required oninput="formatCardNumber(this)">
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label"><i class="far fa-calendar me-2"></i> Expiry Date</label>
                                        <input type="text" name="card_expiry" class="form-control" placeholder="MM/YY" maxlength="5" required oninput="formatExpiry(this)">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label"><i class="fas fa-lock me-2"></i> CVV</label>
                                        <input type="password" name="card_cvv" class="form-control" placeholder="***" maxlength="4" required>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn-pay">
                                        <i class="fas fa-lock"></i> Pay LKR <?php echo number_format($booking['total_amount'], 0); ?>
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-2">
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="fas fa-lock me-1"></i> Secured by SSL encryption
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-lg-5">
                            <!-- ===== ORDER SUMMARY ===== -->
                            <h6 class="fw-bold mb-3"><i class="fas fa-receipt me-2" style="color: var(--gold);"></i> Order Summary</h6>
                            
                            <div class="summary-box">
                                <div class="row">
                                    <span class="label">Booking #</span>
                                    <span class="value"><?php echo $booking['booking_reference'] ?? '#' . $booking['booking_id']; ?></span>
                                </div>
                                <div class="row">
                                    <span class="label">Room</span>
                                    <span class="value"><?php echo htmlspecialchars($booking['type_name'] ?? 'Standard'); ?></span>
                                </div>
                                <div class="row">
                                    <span class="label">Room #</span>
                                    <span class="value"><?php echo $booking['room_number'] ?? 'N/A'; ?></span>
                                </div>
                                <div class="row">
                                    <span class="label">Check In</span>
                                    <span class="value"><?php echo date('d M Y', strtotime($booking['check_in'])); ?></span>
                                </div>
                                <div class="row">
                                    <span class="label">Check Out</span>
                                    <span class="value"><?php echo date('d M Y', strtotime($booking['check_out'])); ?></span>
                                </div>
                                <div class="row">
                                    <span class="label">Nights</span>
                                    <span class="value"><?php echo $booking['num_of_nights']; ?></span>
                                </div>
                                <div class="row" style="border-bottom: 2px solid var(--gold-dark);">
                                    <span class="label">Rate / Night</span>
                                    <span class="value">LKR <?php echo number_format($booking['room_rate'], 0); ?></span>
                                </div>
                                <div class="row mt-2">
                                    <span class="fw-bold" style="font-size: 16px;">Total</span>
                                    <span class="fw-bold" style="color: var(--gold-dark); font-size: 22px;">LKR <?php echo number_format($booking['total_amount'], 0); ?></span>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <div class="card-icons">
                                    <i class="fab fa-cc-visa"></i>
                                    <i class="fab fa-cc-mastercard"></i>
                                    <i class="fab fa-cc-amex"></i>
                                    <i class="fab fa-cc-discover"></i>
                                </div>
                            </div>
                            
                            <div class="text-center mt-2">
                                <a href="my_bookings.php" class="text-muted text-decoration-none small">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Bookings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="brand">ARALIYA</div>
                <p style="font-size: 14px; opacity: 0.6; margin-top: 8px;">Beach Resort &amp; Spa Unawatuna</p>
                <p style="font-size: 13px; opacity: 0.6;">
                    <i class="fas fa-map-marker-alt me-2" style="color: var(--gold);"></i> <?php echo $hotel_address; ?><br>
                    <i class="fas fa-phone me-2" style="color: var(--gold);"></i> <?php echo $hotel_phone; ?><br>
                    <i class="fas fa-envelope me-2" style="color: var(--gold);"></i> info@araliyaresort.com
                </p>
                <div class="social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <h5>Quick Links</h5>
                <a href="index.php">Home</a>
                <a href="rooms.php">Rooms</a>
                <a href="gallery.php">Gallery</a>
                <a href="about.php">About</a>
            </div>
            <div class="col-lg-2 col-6">
                <h5>Info</h5>
                <a href="blog.php">Blog</a>
                <a href="faq.php">FAQ</a>
                <a href="contact.php">Contact</a>
                <a href="terms.php">Terms</a>
            </div>
            <div class="col-lg-2 col-6">
                <h5>Support</h5>
                <a href="privacy.php">Privacy Policy</a>
                <a href="#">Cancellation Policy</a>
            </div>
            <div class="col-lg-2 col-6">
                <h5>Booking</h5>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
                <?php if($is_logged_in): ?>
                    <a href="my_bookings.php">My Bookings</a>
                    <a href="logout.php">Logout</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo $hotel_name; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
function formatCardNumber(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 16) value = value.slice(0, 16);
    let formatted = '';
    for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) formatted += ' ';
        formatted += value[i];
    }
    input.value = formatted;
}

function formatExpiry(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 4) value = value.slice(0, 4);
    if (value.length > 2) {
        input.value = value.slice(0, 2) + '/' + value.slice(2);
    } else {
        input.value = value;
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>