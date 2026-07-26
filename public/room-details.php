<?php
// public/room-details.php - SUPER ADVANCED PROFESSIONAL VERSION
session_start();
include '../includes/db.php';

$is_logged_in = isset($_SESSION['online_user_id']) && !empty($_SESSION['online_user_id']);
$logged_in_user = $is_logged_in ? $_SESSION['online_user_name'] : '';

$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));
$adults = isset($_GET['adults']) ? intval($_GET['adults']) : 2;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;

// Calculate nights
$nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
if ($nights <= 0) $nights = 1;

// Get room details
$room_query = $conn->query("
    SELECT r.*, rt.type_name, rt.base_price, rt.season_price, rt.description as room_desc, rt.image as room_image
    FROM rooms r
    LEFT JOIN room_types rt ON r.room_type = rt.type_name
    WHERE r.room_id = $room_id
");
$room = $room_query->fetch_assoc();

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

// Check all possible paths
$final_image = '';
$paths_to_check = array(
    'uploads/rooms/' . $image_num . '.webp',
    'uploads/rooms/' . $image_num . '.jpg',
    '../uploads/rooms/' . $image_num . '.webp',
    '../uploads/rooms/' . $image_num . '.jpg',
    './uploads/rooms/' . $image_num . '.webp',
    './uploads/rooms/' . $image_num . '.jpg'
);

foreach($paths_to_check as $path) {
    if(file_exists($path)) {
        $final_image = $path;
        break;
    }
}

if(empty($final_image)) {
    $doc_root = $_SERVER['DOCUMENT_ROOT'];
    $webp_path = $doc_root . '/hotel_management_structure/public/uploads/rooms/' . $image_num . '.webp';
    $jpg_path = $doc_root . '/hotel_management_structure/public/uploads/rooms/' . $image_num . '.jpg';
    
    if(file_exists($webp_path)) {
        $final_image = '/hotel_management_structure/public/uploads/rooms/' . $image_num . '.webp';
    } elseif(file_exists($jpg_path)) {
        $final_image = '/hotel_management_structure/public/uploads/rooms/' . $image_num . '.jpg';
    }
}

// Get hotel settings
$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
$hotel_phone = isset($hotel['phone']) ? $hotel['phone'] : '+94 91 234 5678';
$hotel_address = isset($hotel['address']) ? $hotel['address'] : 'Galle Road, Unawatuna, Sri Lanka';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['room_type'] ?? 'Room'); ?> - <?php echo $hotel_name; ?></title>
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

        /* ===== NAVBAR ===== */
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

        /* ===== PAGE HERO ===== */
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

        /* ===== ROOM DETAILS ===== */
        .room-detail-image {
            width: 100%;
            height: 420px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-md);
        }
        .room-detail-image-placeholder {
            width: 100%;
            height: 420px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            color: var(--white);
            font-size: 64px;
        }

        .booking-summary {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            position: sticky;
            top: 20px;
            transition: var(--transition);
        }
        .booking-summary:hover {
            box-shadow: var(--shadow-md);
        }
        .booking-summary .room-name {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            font-family: 'Playfair Display', serif;
        }
        .booking-summary .room-desc {
            color: var(--text-light);
            font-size: 13px;
        }
        .booking-summary .info-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .booking-summary .info-row:last-child {
            border-bottom: none;
        }
        .booking-summary .info-row .label {
            color: var(--text-light);
        }
        .booking-summary .info-row .value {
            font-weight: 600;
            color: var(--text-dark);
        }
        .room-price-large {
            font-size: 32px;
            font-weight: 800;
            color: var(--gold-dark);
            font-family: 'Playfair Display', serif;
        }
        .room-price-large small {
            font-size: 14px;
            font-weight: 400;
            color: #94a3b8;
        }
        .btn-book-now-large {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            padding: 14px 30px;
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
        .btn-book-now-large:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(13, 75, 104, 0.3);
        }

        .amenity-tag {
            display: inline-block;
            background: #f1f5f9;
            color: var(--text-light);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            margin: 3px;
            transition: var(--transition);
        }
        .amenity-tag:hover {
            background: var(--gold);
            color: var(--primary-dark);
        }
        .amenity-tag i { margin-right: 5px; }

        .policy-item {
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: var(--text-light);
        }
        .policy-item:last-child { border-bottom: none; }
        .policy-item i { color: var(--gold); margin-right: 10px; }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
        }
        .section-title .highlight { color: var(--gold); }
        .section-divider {
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
            border-radius: 10px;
            margin-bottom: 12px;
        }

        /* ===== FOOTER ===== */
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
            .room-detail-image { height: 250px; }
            .room-detail-image-placeholder { height: 250px; font-size: 40px; }
            .room-price-large { font-size: 26px; }
            .booking-summary { position: relative; top: 0; margin-top: 20px; }
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
        <h1>Room <span>Details</span></h1>
        <p>Discover luxury and comfort</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="rooms.php">Rooms</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($room['room_type'] ?? 'Room'); ?></li>
            </ol>
        </nav>
    </div>
</section>

<!-- ===== ROOM DETAILS ===== -->
<section class="py-2">
    <div class="container">
        <div class="row g-4">
            <!-- Room Image -->
            <div class="col-lg-8">
                <?php if(!empty($final_image) && file_exists(str_replace('../', '', $final_image))): ?>
                    <img src="<?php echo $final_image; ?>" alt="<?php echo htmlspecialchars($room['room_type'] ?? 'Room'); ?>" class="room-detail-image">
                <?php else: ?>
                    <div class="room-detail-image-placeholder">
                        <i class="fas fa-hotel"></i>
                    </div>
                <?php endif; ?>
                
                <!-- Room Description -->
                <div class="mt-4">
                    <div class="section-divider"></div>
                    <h3 class="section-title">About This <span class="highlight">Room</span></h3>
                    <p class="text-muted" style="font-size: 15px; line-height: 1.8;">
                        <?php echo htmlspecialchars($room['room_desc'] ?? 'Experience comfort and luxury in our well-appointed room. Designed with your comfort in mind, this room offers a perfect blend of modern amenities and elegant decor.'); ?>
                    </p>
                </div>
            </div>
            
            <!-- Booking Summary -->
            <div class="col-lg-4">
                <div class="booking-summary">
                    <h4 class="room-name"><?php echo htmlspecialchars($room['room_type'] ?? 'Standard Room'); ?></h4>
                    <p class="room-desc"><?php echo htmlspecialchars($room['room_desc'] ?? 'Comfortable room with modern amenities'); ?></p>
                    
                    <hr>
                    
                    <div class="info-row">
                        <span class="label"><i class="fas fa-hashtag me-1"></i> Room Number</span>
                        <span class="value"><?php echo $room['room_number'] ?? 'N/A'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label"><i class="fas fa-bed me-1"></i> Room Type</span>
                        <span class="value"><?php echo htmlspecialchars($room['room_type'] ?? 'Standard'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label"><i class="fas fa-calendar-check me-1"></i> Check In</span>
                        <span class="value"><?php echo date('d M Y', strtotime($check_in)); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label"><i class="fas fa-calendar-times me-1"></i> Check Out</span>
                        <span class="value"><?php echo date('d M Y', strtotime($check_out)); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label"><i class="fas fa-moon me-1"></i> Nights</span>
                        <span class="value"><?php echo $nights; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label"><i class="fas fa-user me-1"></i> Guests</span>
                        <span class="value"><?php echo $adults; ?> Adults, <?php echo $children; ?> Children</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold">Total</span>
                        <span class="room-price-large">LKR <?php echo number_format($total_price, 0); ?></span>
                    </div>
                    
                    <a href="booking.php?id=<?php echo $room_id; ?>&check_in=<?php echo $check_in; ?>&check_out=<?php echo $check_out; ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>" class="btn-book-now-large">
                        <i class="fas fa-credit-card"></i> Book Now
                    </a>
                    
                    <p class="text-muted text-center mt-2 small">
                        <i class="fas fa-lock me-1"></i> Secure payment
                    </p>
                </div>
            </div>
        </div>
        
        <!-- ===== AMENITIES & POLICIES ===== -->
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold"><i class="fas fa-list-ul me-2" style="color: var(--gold);"></i> Room Amenities</h5>
                        <div class="mt-3">
                            <span class="amenity-tag"><i class="fas fa-wifi"></i> Free WiFi</span>
                            <span class="amenity-tag"><i class="fas fa-tv"></i> Flat Screen TV</span>
                            <span class="amenity-tag"><i class="fas fa-snowflake"></i> Air Conditioning</span>
                            <span class="amenity-tag"><i class="fas fa-shower"></i> Private Bathroom</span>
                            <span class="amenity-tag"><i class="fas fa-tshirt"></i> Linen & Towels</span>
                            <span class="amenity-tag"><i class="fas fa-coffee"></i> Tea/Coffee Maker</span>
                            <span class="amenity-tag"><i class="fas fa-bell"></i> Room Service</span>
                            <span class="amenity-tag"><i class="fas fa-tools"></i> Daily Housekeeping</span>
                            <span class="amenity-tag"><i class="fas fa-wind"></i> Sea View</span>
                            <span class="amenity-tag"><i class="fas fa-tshirt"></i> Ironing Facilities</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold"><i class="fas fa-clock me-2" style="color: var(--gold);"></i> Policies</h5>
                        <div class="policy-item"><i class="fas fa-check-circle"></i> Check-in: 2:00 PM</div>
                        <div class="policy-item"><i class="fas fa-check-circle"></i> Check-out: 12:00 PM</div>
                        <div class="policy-item"><i class="fas fa-check-circle"></i> Free Cancellation (24hrs)</div>
                        <div class="policy-item"><i class="fas fa-check-circle"></i> No Smoking Rooms</div>
                        <div class="policy-item"><i class="fas fa-check-circle"></i> Pets Not Allowed</div>
                        <div class="policy-item"><i class="fas fa-check-circle"></i> Extra Bed Available</div>
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