<?php
// public/booking_success.php - SUPER ADVANCED PROFESSIONAL VERSION
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

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) {
    header('Location: my_bookings.php');
    exit;
}

// Get booking details
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

// Get PMS reservation ID
$pms_res_id = $booking['pms_reservation_id'] ?? 0;

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
    <title>Booking Confirmed - <?php echo $hotel_name; ?></title>
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

        .success-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 40px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            text-align: center;
            transition: var(--transition);
        }
        .success-card:hover {
            box-shadow: var(--shadow-md);
        }
        .success-card .icon {
            font-size: 80px;
            color: #10b981;
            margin-bottom: 15px;
            animation: successBounce 0.8s ease;
        }
        @keyframes successBounce {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        .success-card h3 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: #065f46;
            font-size: 28px;
        }
        .success-card .booking-ref {
            background: var(--bg-light);
            padding: 10px 25px;
            border-radius: 10px;
            display: inline-block;
            font-weight: 700;
            font-size: 18px;
            color: var(--text-dark);
            border: 1px solid #eef2f5;
            margin: 10px 0;
        }
        .success-card .booking-ref small {
            font-weight: 400;
            color: var(--text-light);
            font-size: 13px;
        }
        .success-card .divider {
            border-top: 1px solid #eef2f5;
            margin: 20px 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            text-align: left;
        }
        .info-grid .info-item {
            background: var(--bg-light);
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #eef2f5;
        }
        .info-grid .info-item .label {
            font-size: 11px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .info-grid .info-item .value {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 15px;
            margin-top: 2px;
        }

        .btn-view-bookings {
            background: var(--primary);
            color: var(--white);
            padding: 10px 30px;
            border-radius: 25px;
            border: none;
            font-weight: 700;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }
        .btn-view-bookings:hover {
            background: var(--primary-dark);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(13, 75, 104, 0.3);
        }

        .btn-home {
            background: var(--gold);
            color: var(--primary-dark);
            padding: 10px 30px;
            border-radius: 25px;
            border: none;
            font-weight: 700;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }
        .btn-home:hover {
            background: var(--gold-dark);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(251, 191, 36, 0.3);
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
            .success-card { padding: 25px; }
            .success-card .icon { font-size: 60px; }
            .info-grid { grid-template-columns: 1fr; }
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
        <h1>Booking <span>Confirmed</span></h1>
        <p>Your reservation has been successfully confirmed</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="my_bookings.php">My Bookings</a></li>
                <li class="breadcrumb-item active">Success</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ===== SUCCESS ===== -->
<section class="py-2">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="success-card">
                    <!-- Icon -->
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    
                    <h3>Booking Confirmed!</h3>
                    <p class="text-muted">Your reservation has been successfully confirmed.</p>
                    
                    <!-- Booking Reference -->
                    <div class="booking-ref">
                        <small>Booking Reference</small><br>
                        <?php echo $booking['booking_reference'] ?? '#' . $booking['booking_id']; ?>
                    </div>

                    <div class="divider"></div>

                    <!-- Booking Details -->
                    <div class="row text-start">
                        <div class="col-md-6">
                            <p class="mb-1"><strong><i class="fas fa-user me-2" style="color: var(--gold);"></i> Guest</strong></p>
                            <p class="text-muted"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                            
                            <p class="mb-1 mt-3"><strong><i class="fas fa-bed me-2" style="color: var(--gold);"></i> Room</strong></p>
                            <p class="text-muted"><?php echo htmlspecialchars($booking['type_name'] ?? 'Standard'); ?> (Room #<?php echo $booking['room_number'] ?? 'N/A'; ?>)</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong><i class="fas fa-calendar-check me-2" style="color: var(--gold);"></i> Check In</strong></p>
                            <p class="text-muted"><?php echo date('l, d M Y', strtotime($booking['check_in'])); ?></p>
                            
                            <p class="mb-1 mt-3"><strong><i class="fas fa-calendar-times me-2" style="color: var(--gold);"></i> Check Out</strong></p>
                            <p class="text-muted"><?php echo date('l, d M Y', strtotime($booking['check_out'])); ?></p>
                        </div>
                    </div>

                    <div class="row text-start mt-2">
                        <div class="col-md-6">
                            <p class="mb-1"><strong><i class="fas fa-moon me-2" style="color: var(--gold);"></i> Nights</strong></p>
                            <p class="text-muted"><?php echo $booking['num_of_nights']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong><i class="fas fa-user me-2" style="color: var(--gold);"></i> Guests</strong></p>
                            <p class="text-muted"><?php echo $booking['adults']; ?> Adults, <?php echo $booking['children']; ?> Children</p>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Help & Contact -->
                    <div class="row text-start">
                        <div class="col-md-6">
                            <h6 class="fw-bold"><i class="fas fa-headset me-2" style="color: var(--gold);"></i> Need Help?</h6>
                            <p class="text-muted small">
                                <i class="fas fa-phone me-2"></i> <?php echo $hotel_phone; ?><br>
                                <i class="fas fa-envelope me-2"></i> info@araliyaresort.com<br>
                                <i class="fas fa-map-marker-alt me-2"></i> <?php echo $hotel_address; ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="fw-bold"><i class="fas fa-clock me-2" style="color: var(--gold);"></i> Policies</h6>
                            <p class="text-muted small">
                                <i class="fas fa-check me-1"></i> Check-in: 2:00 PM<br>
                                <i class="fas fa-check me-1"></i> Check-out: 12:00 PM
                            </p>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Buttons -->
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="my_bookings.php" class="btn-view-bookings">
                            <i class="fas fa-list me-2"></i> View My Bookings
                        </a>
                        <a href="index.php" class="btn-home">
                            <i class="fas fa-home me-2"></i> Back to Home
                        </a>
                    </div>
                    
                    <p class="text-muted text-center mt-3 small">
                        <i class="fas fa-envelope me-1"></i> A confirmation email has been sent to your email address.
                    </p>
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