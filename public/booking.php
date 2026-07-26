<?php
// public/booking.php - SUPER ADVANCED PROFESSIONAL VERSION
session_start();
include '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['online_user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['online_user_id'];
$user_name = $_SESSION['online_user_name'];
$user_email = $_SESSION['online_user_email'];

// Get user phone from database
$phone_query = $conn->prepare("SELECT phone FROM online_users WHERE user_id = ?");
$phone_query->bind_param("i", $user_id);
$phone_query->execute();
$phone_result = $phone_query->get_result();
$user_data = $phone_result->fetch_assoc();
$user_phone = isset($user_data['phone']) ? $user_data['phone'] : '';

$error = '';
$success = '';

// Get room details
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));
$adults = isset($_GET['adults']) ? intval($_GET['adults']) : 2;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;

// Calculate nights
$nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
if ($nights <= 0) $nights = 1;

if ($room_id <= 0) {
    header('Location: rooms.php');
    exit;
}

// Get room details
$room_query = $conn->prepare("
    SELECT r.*, rt.type_name, rt.base_price, rt.season_price, rt.description as room_desc, rt.image as room_image
    FROM rooms r
    LEFT JOIN room_types rt ON r.room_type = rt.type_name
    WHERE r.room_id = ?
");
$room_query->bind_param("i", $room_id);
$room_query->execute();
$result = $room_query->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    header('Location: rooms.php');
    exit;
}

$price = isset($room['base_price']) ? $room['base_price'] : 8000;
$total_price = $price * $nights;

// Image number mapping
$image_map = array(
    'standard' => 1,
    'deluxe' => 2,
    'suite' => 3,
    'luxury suite' => 4,
    'apartment' => 5,
    'executive' => 6,
    'family' => 7,
    'twin' => 8
);

$room_type_lower = strtolower(trim($room['room_type'] ?? 'standard'));
$image_num = 1;
foreach ($image_map as $key => $num) {
    if (strpos($room_type_lower, $key) !== false) {
        $image_num = $num;
        break;
    }
}

$image_path = 'uploads/rooms/' . $image_num . '.webp';
if (!file_exists($image_path)) {
    $image_path = 'uploads/rooms/' . $image_num . '.jpg';
    if (!file_exists($image_path)) {
        $image_path = '';
    }
}

// Process booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $guest_name = isset($_POST['guest_name']) ? trim($_POST['guest_name']) : '';
    $guest_email = isset($_POST['guest_email']) ? trim($_POST['guest_email']) : '';
    $guest_phone = isset($_POST['guest_phone']) ? trim($_POST['guest_phone']) : '';
    $guest_nationality = isset($_POST['guest_nationality']) ? trim($_POST['guest_nationality']) : '';
    $special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
    
    if (empty($guest_name) || empty($guest_email) || empty($guest_phone)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Generate booking reference
        $ref = 'BK' . date('ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert into online_bookings
        $stmt = $conn->prepare("
            INSERT INTO online_bookings (
                booking_reference, user_id, room_id, guest_name, guest_email, guest_phone,
                guest_nationality, check_in, check_out, num_of_nights, adults, children,
                room_rate, total_amount, booking_status, special_requests, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'pending')
        ");
        $stmt->bind_param(
            "siissssssiiidss",
            $ref, $user_id, $room_id, $guest_name, $guest_email, $guest_phone,
            $guest_nationality, $check_in, $check_out, $nights, $adults, $children,
            $price, $total_price, $special_requests
        );
        
        if ($stmt->execute()) {
            $booking_id = $conn->insert_id;
            header('Location: payment.php?id=' . $booking_id);
            exit;
        } else {
            $error = 'Booking failed. Please try again.';
        }
        $stmt->close();
    }
}

$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
$hotel_phone = isset($hotel['phone']) ? $hotel['phone'] : '+94 91 234 5678';
$hotel_address = isset($hotel['address']) ? $hotel['address'] : 'Galle Road, Unawatuna, Sri Lanka';

$is_logged_in = isset($_SESSION['online_user_id']) && !empty($_SESSION['online_user_id']);
$logged_in_user = $is_logged_in ? $_SESSION['online_user_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Now - <?php echo $hotel_name; ?></title>
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

        .booking-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 30px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            transition: var(--transition);
        }
        .booking-card:hover {
            box-shadow: var(--shadow-md);
        }
        .booking-card .form-control,
        .booking-card .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            transition: var(--transition);
            background: #f8fafc;
        }
        .booking-card .form-control:focus,
        .booking-card .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.1);
            background: var(--white);
        }
        .booking-card .form-label {
            font-weight: 600;
            color: var(--text-light);
            font-size: 13px;
        }
        .booking-card .form-label i { color: var(--gold); width: 18px; }

        .btn-book {
            background: var(--gold);
            color: var(--primary-dark);
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
        .btn-book:hover {
            background: var(--gold-dark);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(251, 191, 36, 0.3);
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

        .room-preview {
            background: var(--white);
            border-radius: var(--radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            margin-bottom: 20px;
        }
        .room-preview .image {
            height: 120px;
            overflow: hidden;
        }
        .room-preview .image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .room-preview .image .placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 32px;
        }
        .room-preview .info {
            padding: 12px 16px;
        }
        .room-preview .info .name {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 15px;
        }
        .room-preview .info .desc {
            color: var(--text-light);
            font-size: 12px;
        }

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
            .booking-card { padding: 20px; }
            .room-preview .image { height: 100px; }
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
        <h1>Complete <span>Booking</span></h1>
        <p>Fill in your details to complete the booking</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="rooms.php">Rooms</a></li>
                <li class="breadcrumb-item"><a href="room-details.php?id=<?php echo $room_id; ?>">Room Details</a></li>
                <li class="breadcrumb-item active">Booking</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ===== BOOKING FORM ===== -->
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

                <div class="booking-card">
                    <div class="row">
                        <div class="col-lg-7">
                            <!-- ===== ROOM PREVIEW ===== -->
                            <div class="room-preview">
                                <div class="image">
                                    <?php if(!empty($image_path) && file_exists($image_path)): ?>
                                        <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($room['room_type'] ?? 'Room'); ?>">
                                    <?php else: ?>
                                        <div class="placeholder">
                                            <i class="fas fa-hotel"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="info">
                                    <div class="name"><?php echo htmlspecialchars($room['room_type'] ?? 'Standard Room'); ?></div>
                                    <div class="desc"><?php echo htmlspecialchars($room['room_desc'] ?? 'Comfortable room with modern amenities'); ?></div>
                                </div>
                            </div>

                            <!-- ===== BOOKING FORM ===== -->
                            <form method="POST" action="">
                                <h6 class="fw-bold mb-3"><i class="fas fa-user me-2" style="color: var(--gold);"></i> Guest Details</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-user me-2"></i> Full Name *</label>
                                    <input type="text" name="guest_name" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-envelope me-2"></i> Email *</label>
                                    <input type="email" name="guest_email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-phone me-2"></i> Phone Number *</label>
                                    <input type="text" name="guest_phone" class="form-control" value="<?php echo htmlspecialchars($user_phone); ?>" placeholder="0712345678" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-globe me-2"></i> Nationality</label>
                                    <select name="guest_nationality" class="form-select">
                                        <option value="Sri Lankan">Sri Lankan</option>
                                        <option value="Indian">Indian</option>
                                        <option value="British">British</option>
                                        <option value="American">American</option>
                                        <option value="Australian">Australian</option>
                                        <option value="German">German</option>
                                        <option value="French">French</option>
                                        <option value="Chinese">Chinese</option>
                                        <option value="Japanese">Japanese</option>
                                        <option value="Russian">Russian</option>
                                        <option value="Canadian">Canadian</option>
                                        <option value="Maldivian">Maldivian</option>
                                        <option value="Italian">Italian</option>
                                        <option value="Spanish">Spanish</option>
                                        <option value="Dutch">Dutch</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-comment me-2"></i> Special Requests</label>
                                    <textarea name="special_requests" class="form-control" rows="2" placeholder="Any special requirements..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn-book">
                                    <i class="fas fa-credit-card"></i> Proceed to Payment
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-lg-5">
                            <!-- ===== ORDER SUMMARY ===== -->
                            <h6 class="fw-bold mb-3"><i class="fas fa-receipt me-2" style="color: var(--gold);"></i> Order Summary</h6>
                            
                            <div class="summary-box">
                                <div class="row">
                                    <span class="label">Room</span>
                                    <span class="value"><?php echo htmlspecialchars($room['type_name'] ?? 'Standard'); ?></span>
                                </div>
                                <div class="row">
                                    <span class="label">Room Number</span>
                                    <span class="value"><?php echo $room['room_number'] ?? 'N/A'; ?></span>
                                </div>
                                <div class="row">
                                    <span class="label">Check In</span>
                                    <span class="value"><?php echo date('d M Y', strtotime($check_in)); ?></span>
                                </div>
                                <div class="row">
                                    <span class="label">Check Out</span>
                                    <span class="value"><?php echo date('d M Y', strtotime($check_out)); ?></span>
                                </div>
                                <div class="row">
                                    <span class="label">Nights</span>
                                    <span class="value"><?php echo $nights; ?></span>
                                </div>
                                <div class="row">
                                    <span class="label">Guests</span>
                                    <span class="value"><?php echo $adults; ?> Adults, <?php echo $children; ?> Children</span>
                                </div>
                                <div class="row" style="border-bottom: 2px solid var(--gold-dark);">
                                    <span class="label">Rate per Night</span>
                                    <span class="value">LKR <?php echo number_format($price, 0); ?></span>
                                </div>
                                <div class="row mt-2">
                                    <span class="fw-bold" style="font-size: 16px;">Total Amount</span>
                                    <span class="fw-bold" style="color: var(--gold-dark); font-size: 22px;">LKR <?php echo number_format($total_price, 0); ?></span>
                                </div>
                            </div>
                            
                            <p class="text-muted text-center mt-2 small">
                                <i class="fas fa-lock me-1"></i> Secure payment processing
                            </p>
                            
                            <div class="text-center mt-2">
                                <a href="room-details.php?id=<?php echo $room_id; ?>" class="text-muted text-decoration-none small">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Room Details
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>