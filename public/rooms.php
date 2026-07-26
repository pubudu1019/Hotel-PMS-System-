<?php
// public/rooms.php - SUPER ADVANCED PROFESSIONAL VERSION
session_start();
include '../includes/db.php';

// ===== CHECK LOGIN =====
$is_logged_in = isset($_SESSION['online_user_id']) && !empty($_SESSION['online_user_id']);
$logged_in_user = $is_logged_in ? $_SESSION['online_user_name'] : '';

$current_date = date('Y-m-d');

// ===== GET SEARCH PARAMETERS =====
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : $current_date;
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));
$adults = isset($_GET['adults']) ? intval($_GET['adults']) : 2;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;
$room_type = isset($_GET['room_type']) ? $_GET['room_type'] : 'all';

// ===== CALCULATE NIGHTS =====
$nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
if ($nights <= 0) $nights = 1;

// ===== ROOM QUERY =====
$sql = "SELECT 
            r.room_id,
            r.room_number,
            r.room_type,
            r.status as room_status,
            rt.type_name,
            rt.base_price,
            rt.season_price,
            rt.description as room_desc,
            rt.image as room_image
        FROM rooms r
        LEFT JOIN room_types rt ON r.room_type = rt.type_name
        WHERE r.status = 'available'";

if ($room_type != 'all' && !empty($room_type)) {
    $sql .= " AND rt.type_name = '$room_type'";
}

$sql .= " ORDER BY rt.base_price ASC";

$room_query = $conn->query($sql);
$rooms = array();
while($room = $room_query->fetch_assoc()) {
    $rooms[] = $room;
}

// ===== GET ROOM TYPES FOR FILTER =====
$types = $conn->query("SELECT DISTINCT type_name FROM room_types ORDER BY type_name");

// ===== IMAGE MAPPING =====
$image_number_map = array(
    'standard' => 1,
    'deluxe' => 2,
    'suite' => 3,
    'luxury suite' => 4,
    'apartment' => 5,
    'executive' => 6,
    'family' => 7,
    'twin' => 8
);

// ===== GET HOTEL SETTINGS =====
$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1");
$hotel_data = $hotel->fetch_assoc();
$hotel_name = isset($hotel_data['hotel_name']) ? $hotel_data['hotel_name'] : 'Araliya Beach Resort';
$hotel_phone = isset($hotel_data['phone']) ? $hotel_data['phone'] : '+94 91 234 5678';
$hotel_address = isset($hotel_data['address']) ? $hotel_data['address'] : 'Galle Road, Unawatuna, Sri Lanka';

// ===== ROOM STATUS STATS =====
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                SUM(CASE WHEN status = 'cleaning' THEN 1 ELSE 0 END) as cleaning,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
              FROM rooms";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

$total_rooms_count = $stats['total'];
$total_available = $stats['available'];
$occupied_count = $stats['occupied'];
$cleaning_count = $stats['cleaning'];
$maintenance_count = $stats['maintenance'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - <?php echo $hotel_name; ?></title>
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
            padding: 50px 0 40px;
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
            font-size: 36px;
            font-weight: 800;
            font-family: 'Playfair Display', serif;
        }
        .page-hero h1 span { color: var(--gold); }
        .page-hero p {
            opacity: 0.7;
            font-size: 16px;
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

        /* ===== FILTER BAR ===== */
        .filter-bar {
            background: var(--white);
            padding: 20px 0;
            box-shadow: var(--shadow-sm);
            border-bottom: 1px solid #eef2f5;
            margin-bottom: 30px;
        }
        .filter-bar label {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .filter-bar .form-control,
        .filter-bar .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 14px;
            transition: var(--transition);
            background: #f8fafc;
        }
        .filter-bar .form-control:focus,
        .filter-bar .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.12);
            background: var(--white);
        }
        .btn-filter {
            background: var(--gold);
            color: var(--primary-dark);
            font-weight: 700;
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            width: 100%;
            transition: var(--transition);
        }
        .btn-filter:hover {
            background: var(--gold-dark);
            color: var(--white);
            transform: translateY(-2px);
        }
        .btn-reset-filter {
            background: #e2e8f0;
            color: var(--text-light);
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            width: 100%;
            transition: var(--transition);
            text-align: center;
            text-decoration: none;
            display: block;
        }
        .btn-reset-filter:hover {
            background: #cbd5e1;
            color: var(--text-dark);
        }

        /* ===== SECTION TITLES ===== */
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
        }
        .section-title .highlight { color: var(--gold); }
        .section-divider {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
            margin: 0 auto 15px;
            border-radius: 10px;
        }

        /* ===== ROOM GRID ===== */
        .room-grid-5col {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }
        @media (max-width: 1200px) { .room-grid-5col { grid-template-columns: repeat(4, 1fr); } }
        @media (max-width: 992px) { .room-grid-5col { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .room-grid-5col { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .room-grid-5col { grid-template-columns: 1fr; } }

        .room-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            height: 100%;
            border: 1px solid #eef2f5;
            display: flex;
            flex-direction: column;
        }
        .room-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--gold);
        }
        .room-card .room-image {
            height: 180px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .room-card .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        .room-card:hover .room-image img { transform: scale(1.05); }
        .room-card .room-image .room-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--gold);
            color: var(--primary-dark);
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            z-index: 2;
        }
        .room-card .room-image .room-number-badge {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0,0,0,0.5);
            color: var(--white);
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            backdrop-filter: blur(4px);
            z-index: 2;
        }
        .room-card .room-image .view-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.5);
            color: var(--white);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: 600;
            backdrop-filter: blur(4px);
            z-index: 2;
            opacity: 0;
            transition: var(--transition);
        }
        .room-card:hover .room-image .view-icon { opacity: 1; }
        .room-card .room-image .no-image {
            font-size: 36px;
            color: rgba(255,255,255,0.3);
        }

        .room-card .room-body {
            padding: 14px 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .room-card .room-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 2px;
        }
        .room-card .room-desc {
            color: var(--text-light);
            font-size: 11px;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .room-card .room-price {
            font-size: 16px;
            font-weight: 800;
            color: var(--gold-dark);
        }
        .room-card .room-price small {
            font-size: 10px;
            font-weight: 400;
            color: #94a3b8;
        }
        .room-card .room-amenities {
            display: flex;
            gap: 3px;
            flex-wrap: wrap;
            margin: 4px 0;
        }
        .room-card .room-amenities span {
            font-size: 8px;
            color: var(--text-light);
            background: #f1f5f9;
            padding: 1px 6px;
            border-radius: 8px;
        }
        .room-card .room-amenities span i { margin-right: 2px; font-size: 7px; }
        .room-card .room-total {
            font-size: 10px;
            color: #94a3b8;
        }
        .btn-book-room {
            background: var(--primary);
            color: var(--white);
            padding: 4px 12px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 600;
            font-size: 10px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-book-room:hover {
            background: var(--primary-dark);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(13, 75, 104, 0.3);
        }

        .results-count {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 20px;
        }
        .results-count strong { color: var(--text-dark); }

        /* ===== IMAGE MODAL ===== */
        #imageModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.92);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }
        #imageModal.active { display: flex; }
        #imageModal .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }
        #imageModal .modal-content img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        #imageModal .close-btn {
            position: absolute;
            top: -40px;
            right: 0;
            background: none;
            border: none;
            color: var(--white);
            font-size: 32px;
            cursor: pointer;
            transition: var(--transition);
        }
        #imageModal .close-btn:hover { transform: rotate(90deg); color: var(--gold); }
        #imageModal .close-bottom {
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--white);
            padding: 8px 30px;
            border-radius: 20px;
            cursor: pointer;
            transition: var(--transition);
        }
        #imageModal .close-bottom:hover { background: rgba(255,255,255,0.25); }
        #imageModal .modal-label {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--white);
            font-size: 16px;
            font-weight: 600;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
            background: rgba(0,0,0,0.3);
            padding: 4px 20px;
            border-radius: 20px;
            backdrop-filter: blur(4px);
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
            .page-hero h1 { font-size: 28px; }
            .filter-bar .form-control, .filter-bar .form-select { font-size: 12px; padding: 6px 10px; }
            .room-card .room-image { height: 150px; }
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
                <li class="nav-item"><a href="rooms.php" class="nav-link active">Rooms</a></li>
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
        <h1>Our <span>Rooms</span></h1>
        <p>Find the perfect room for your stay</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Rooms</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ===== FILTER BAR ===== -->
<section class="filter-bar">
    <div class="container">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label><i class="far fa-calendar me-1"></i> Check In</label>
                <input type="date" name="check_in" class="form-control" value="<?php echo $check_in; ?>" min="<?php echo $current_date; ?>">
            </div>
            <div class="col-md-2">
                <label><i class="far fa-calendar me-1"></i> Check Out</label>
                <input type="date" name="check_out" class="form-control" value="<?php echo $check_out; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
            <div class="col-md-2">
                <label><i class="fas fa-user me-1"></i> Adults</label>
                <select name="adults" class="form-select">
                    <option value="1" <?php if($adults == 1) echo 'selected'; ?>>1</option>
                    <option value="2" <?php if($adults == 2) echo 'selected'; ?>>2</option>
                    <option value="3" <?php if($adults == 3) echo 'selected'; ?>>3</option>
                    <option value="4" <?php if($adults == 4) echo 'selected'; ?>>4</option>
                </select>
            </div>
            <div class="col-md-2">
                <label><i class="fas fa-child me-1"></i> Children</label>
                <select name="children" class="form-select">
                    <option value="0" <?php if($children == 0) echo 'selected'; ?>>0</option>
                    <option value="1" <?php if($children == 1) echo 'selected'; ?>>1</option>
                    <option value="2" <?php if($children == 2) echo 'selected'; ?>>2</option>
                </select>
            </div>
            <div class="col-md-2">
                <label><i class="fas fa-bed me-1"></i> Room Type</label>
                <select name="room_type" class="form-select">
                    <option value="all" <?php if($room_type == 'all') echo 'selected'; ?>>All Types</option>
                    <?php while($t = $types->fetch_assoc()): ?>
                    <option value="<?php echo $t['type_name']; ?>" <?php if($room_type == $t['type_name']) echo 'selected'; ?>>
                        <?php echo $t['type_name']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-filter"><i class="fas fa-search me-1"></i> Search</button>
                    <a href="rooms.php" class="btn-reset-filter"><i class="fas fa-redo me-1"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- ===== ROOM LIST ===== -->
<section class="py-2">
    <div class="container">
        <div class="text-center">
            <div class="section-divider"></div>
            <h2 class="section-title">Available <span class="highlight">Rooms</span></h2>
        </div>

        <!-- ===== RESULTS COUNT WITH STATS ===== -->
        <div class="results-count">
            <div class="row g-2 align-items-center">
                <div class="col-lg-7">
                    <i class="fas fa-hotel me-1"></i> 
                    <strong><?php echo count($rooms); ?></strong> rooms available 
                    for <strong><?php echo date('d M Y', strtotime($check_in)); ?></strong> - <strong><?php echo date('d M Y', strtotime($check_out)); ?></strong>
                    <span class="badge bg-primary ms-2"><?php echo $nights; ?> night<?php if($nights > 1) echo 's'; ?></span>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <span class="badge bg-success me-1">🟢 <?php echo $total_available; ?> Available</span>
                    <span class="badge bg-danger me-1">🔴 <?php echo $occupied_count; ?> Occupied</span>
                    <span class="badge bg-warning text-dark me-1">🟡 <?php echo $cleaning_count; ?> Cleaning</span>
                    <span class="badge bg-secondary me-1">⚫ <?php echo $maintenance_count; ?> Maintenance</span>
                    <span class="badge bg-dark">🏨 <?php echo $total_rooms_count; ?> Total</span>
                </div>
            </div>
        </div>

        <!-- ===== ROOM GRID - 5 COLUMNS ===== -->
        <div class="room-grid-5col">
            <?php if(empty($rooms)): ?>
                <div class="col-12 text-center py-5" style="grid-column: 1 / -1;">
                    <i class="fas fa-bed fa-4x text-muted mb-4"></i>
                    <h4>No rooms available</h4>
                    <p class="text-muted">Please try different dates or contact us directly.</p>
                    <a href="rooms.php" class="btn btn-primary mt-2" style="background: var(--primary); border: none; border-radius:25px; padding:8px 30px;">
                        <i class="fas fa-redo me-1"></i> Reset Search
                    </a>
                </div>
            <?php else: ?>
                <?php foreach($rooms as $room): 
                    $room_number = isset($room['room_number']) ? $room['room_number'] : 'N/A';
                    $category = isset($room['type_name']) ? $room['type_name'] : (isset($room['room_type']) ? $room['room_type'] : 'Standard');
                    $price = isset($room['base_price']) ? $room['base_price'] : 8000;
                    $total_price = $price * $nights;
                    $description = isset($room['room_desc']) ? $room['room_desc'] : 'Comfortable room with modern amenities';

                    // ===== IMAGE HANDLING - FIXED =====
                    $room_type_lower = strtolower(trim($category));
                    $image_num = 1;
                    foreach($image_number_map as $key => $num) {
                        if(strpos($room_type_lower, $key) !== false) {
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

                    // If still not found, try with document root
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
                ?>
                <div class="room-card">
                    <div class="room-image" onclick="openImageModal('<?php echo $final_image ? $final_image : ''; ?>', '<?php echo htmlspecialchars($category); ?> - Room <?php echo $room_number; ?>')">
                        <?php if(!empty($final_image)): ?>
                            <img src="<?php echo $final_image; ?>" alt="<?php echo htmlspecialchars($category); ?>" loading="lazy">
                        <?php else: ?>
                            <i class="fas fa-hotel no-image"></i>
                        <?php endif; ?>
                        <span class="room-badge"><?php echo htmlspecialchars($category); ?></span>
                        <span class="room-number-badge"><i class="fas fa-hashtag"></i> <?php echo $room_number; ?></span>
                        <span class="view-icon"><i class="fas fa-search-plus"></i> View</span>
                    </div>
                    <div class="room-body">
                        <h5 class="room-name"><?php echo htmlspecialchars($category); ?></h5>
                        <p class="room-desc"><?php echo htmlspecialchars($description); ?></p>
                        <div class="room-amenities">
                            <span><i class="fas fa-wifi"></i> WiFi</span>
                            <span><i class="fas fa-tv"></i> TV</span>
                            <span><i class="fas fa-snowflake"></i> AC</span>
                            <span><i class="fas fa-shower"></i> Bath</span>
                            <span><i class="fas fa-tshirt"></i> Linen</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-auto pt-1">
                            <div>
                                <span class="room-price">LKR <?php echo number_format($price, 0); ?></span>
                                <small>/ night</small>
                                <br>
                                <span class="room-total">Total: LKR <?php echo number_format($total_price, 0); ?></span>
                            </div>
                            <a href="room-details.php?id=<?php echo $room['room_id']; ?>&check_in=<?php echo $check_in; ?>&check_out=<?php echo $check_out; ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>" class="btn-book-room">
                                <i class="fas fa-arrow-right"></i> Book
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== IMAGE MODAL ===== -->
<div id="imageModal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeImageModal()">&times;</button>
        <img id="modalImage" src="" alt="Room Image">
        <div class="modal-label" id="modalLabel">Room Image</div>
        <button class="close-bottom" onclick="closeImageModal()"><i class="fas fa-times me-2"></i> Close</button>
    </div>
</div>

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
<script>
// ===== IMAGE MODAL =====
function openImageModal(imageUrl, roomName) {
    var modal = document.getElementById('imageModal');
    var img = document.getElementById('modalImage');
    var label = document.getElementById('modalLabel');
    
    if (imageUrl && imageUrl !== '') {
        img.src = imageUrl;
        label.textContent = roomName || 'Room Image';
    } else {
        img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300"%3E%3Crect width="400" height="300" fill="%230d4b68"/%3E%3Ctext x="200" y="155" font-family="Arial" font-size="24" fill="white" text-anchor="middle"%3ENo Image Available%3C/text%3E%3C/svg%3E';
        label.textContent = 'No Image Available';
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    document.getElementById('imageModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeImageModal();
});

document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) closeImageModal();
});
</script>
</body>
</html>