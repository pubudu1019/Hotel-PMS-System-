<?php
// public/terms.php - Terms & Conditions
session_start();
include '../includes/db.php';

$is_logged_in = isset($_SESSION['online_user_id']) && !empty($_SESSION['online_user_id']);
$logged_in_user = $is_logged_in ? $_SESSION['online_user_name'] : '';

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
    <title>Terms & Conditions - <?php echo $hotel_name; ?></title>
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
        .btn-book-now {
            background: var(--gold);
            color: #082f42;
            font-weight: 700;
            padding: 8px 25px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-book-now:hover {
            background: #d97706;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(251,191,36,0.3);
        }
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
        .user-dropdown {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .user-dropdown:hover {
            background: rgba(255,255,255,0.2);
            color: var(--gold);
        }
        .user-dropdown .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--gold);
            color: #082f42;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 12px;
        }
        
        .terms-hero {
            background: linear-gradient(135deg, #082f42, #0d4b68);
            color: white;
            padding: 40px 0 30px;
            text-align: center;
            border-radius: 16px;
            margin-bottom: 30px;
        }
        .terms-hero h1 {
            font-size: 28px;
            font-weight: 800;
        }
        .terms-hero h1 span { color: var(--gold); }
        .terms-hero p {
            opacity: 0.8;
            font-size: 15px;
            max-width: 600px;
            margin: 8px auto 0;
        }
        
        .terms-content {
            background: white;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid #eef2f5;
        }
        .terms-content h4 {
            color: #082f42;
            font-weight: 700;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        .terms-content h4:first-of-type { margin-top: 0; }
        .terms-content h4 i { color: var(--gold); margin-right: 10px; }
        .terms-content p {
            color: #475569;
            font-size: 14px;
            line-height: 1.7;
        }
        .terms-content ul {
            padding-left: 20px;
        }
        .terms-content ul li {
            color: #475569;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 6px;
        }
        .terms-content ul li i {
            color: var(--gold);
            margin-right: 8px;
        }
        
        .last-updated {
            color: #94a3b8;
            font-size: 13px;
            text-align: right;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer { background: #0a1828; color: rgba(255,255,255,0.7); padding: 30px 0 15px; margin-top: 40px; }
        .footer .brand { color: var(--gold); font-size: 20px; font-weight: 800; }
        .footer a { color: rgba(255,255,255,0.6); text-decoration: none; transition: 0.3s; }
        .footer a:hover { color: var(--gold); }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; margin-top: 20px; font-size: 13px; }
        
        @media (max-width: 768px) {
            .terms-hero { padding: 25px 20px; }
            .terms-hero h1 { font-size: 22px; }
            .terms-content { padding: 20px; }
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
                <li class="nav-item"><a href="gallery.php" class="nav-link">Gallery</a></li>
                <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
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
                    <li class="nav-item"><a href="register.php" class="btn-book-now"><i class="fas fa-user-plus me-1"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== TERMS HERO ===== -->
<section class="container">
    <div class="terms-hero">
        <h1>Terms & <span>Conditions</span></h1>
        <p>Please read these terms carefully before using our services.</p>
    </div>
</section>

<!-- ===== TERMS CONTENT ===== -->
<section class="py-2">
    <div class="container">
        <div class="terms-content">
            <h4><i class="fas fa-info-circle"></i> 1. Introduction</h4>
            <p>
                Welcome to <?php echo $hotel_name; ?> ("we", "our", "us"). These Terms and Conditions govern your use of our website and services. By accessing our website or making a reservation, you agree to comply with these terms.
            </p>
            
            <h4><i class="fas fa-calendar-check"></i> 2. Reservations</h4>
            <ul>
                <li><i class="fas fa-check"></i> All reservations are subject to availability.</li>
                <li><i class="fas fa-check"></i> A valid credit card is required to secure a reservation.</li>
                <li><i class="fas fa-check"></i> Check-in time is from 2:00 PM and check-out is by 12:00 PM.</li>
                <li><i class="fas fa-check"></i> Early check-in and late check-out are subject to availability and may incur additional charges.</li>
                <li><i class="fas fa-check"></i> You must be at least 18 years old to make a reservation.</li>
            </ul>
            
            <h4><i class="fas fa-credit-card"></i> 3. Payment & Cancellation</h4>
            <ul>
                <li><i class="fas fa-check"></i> Full payment is required at the time of booking.</li>
                <li><i class="fas fa-check"></i> Cancellations made within 24 hours of check-in may incur a cancellation fee.</li>
                <li><i class="fas fa-check"></i> No-shows will be charged the full amount of the reservation.</li>
                <li><i class="fas fa-check"></i> Refunds will be processed within 7-10 business days.</li>
            </ul>
            
            <h4><i class="fas fa-user"></i> 4. Guest Responsibilities</h4>
            <ul>
                <li><i class="fas fa-check"></i> Guests are responsible for any damages caused to the property.</li>
                <li><i class="fas fa-check"></i> Quiet hours are from 10:00 PM to 7:00 AM.</li>
                <li><i class="fas fa-check"></i> Smoking is prohibited in all indoor areas.</li>
                <li><i class="fas fa-check"></i> Pets are not allowed unless prior arrangement has been made.</li>
                <li><i class="fas fa-check"></i> Guests must present valid identification at check-in.</li>
            </ul>
            
            <h4><i class="fas fa-lock"></i> 5. Privacy & Security</h4>
            <p>
                We take your privacy seriously. All personal information provided during the booking process is encrypted and stored securely. We do not share your information with third parties except as required to process your reservation.
            </p>
            
            <h4><i class="fas fa-gavel"></i> 6. Limitation of Liability</h4>
            <p>
                <?php echo $hotel_name; ?> is not liable for any loss, damage, or injury arising from the use of our facilities or services, except as required by law. Guests are advised to secure their personal belongings at all times.
            </p>
            
            <h4><i class="fas fa-file-signature"></i> 7. Changes to Terms</h4>
            <p>
                We reserve the right to modify these Terms and Conditions at any time. Any changes will be effective immediately upon posting on this page. It is your responsibility to review these terms periodically.
            </p>
            
            <h4><i class="fas fa-phone"></i> 8. Contact Us</h4>
            <p>
                If you have any questions about these Terms and Conditions, please contact us:
            </p>
            <ul>
                <li><i class="fas fa-phone"></i> Phone: <?php echo $hotel_phone; ?></li>
                <li><i class="fas fa-envelope"></i> Email: info@araliyaresort.com</li>
                <li><i class="fas fa-map-marker-alt"></i> Address: <?php echo $hotel_address; ?></li>
            </ul>
            
            <div class="last-updated">
                <i class="far fa-calendar-alt me-1"></i> Last Updated: <?php echo date('F d, Y'); ?>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="index.php" class="btn btn-primary" style="background: var(--primary); border: none; border-radius:25px; padding:8px 30px;">
                <i class="fas fa-arrow-left me-2"></i> Back to Home
            </a>
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
                <a href="gallery.php">Gallery</a> | 
                <a href="about.php">About</a> | 
                <a href="contact.php">Contact</a>
                <?php if($is_logged_in): ?> | <a href="logout.php">Logout</a><?php endif; ?>
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