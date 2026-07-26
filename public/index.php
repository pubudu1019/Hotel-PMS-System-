<?php
// public/index.php - SUPER ADVANCED PROFESSIONAL VERSION
session_start();
include '../includes/db.php';

// ===== CHECK LOGIN =====
$is_logged_in = isset($_SESSION['online_user_id']) && !empty($_SESSION['online_user_id']);
$logged_in_user = $is_logged_in ? $_SESSION['online_user_name'] : '';

// ===== GET CURRENT DATE =====
$current_date = date('Y-m-d');
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : $current_date;
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));
$adults = isset($_GET['adults']) ? intval($_GET['adults']) : 2;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;

// ===== CALCULATE NIGHTS =====
$nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
if ($nights <= 0) $nights = 1;

// ===== GET AVAILABLE ROOMS =====
$rooms = [];
$room_query = $conn->query("
    SELECT r.*, rt.type_name, rt.base_price, rt.season_price, rt.description as room_desc, rt.image as room_image
    FROM rooms r
    LEFT JOIN room_types rt ON r.room_type = rt.type_name
    WHERE r.status = 'available'
    ORDER BY rt.base_price ASC
");
while($room = $room_query->fetch_assoc()) {
    $rooms[] = $room;
}

// ===== GET FEATURED ROOMS (FIRST 3) =====
$featured_rooms = array_slice($rooms, 0, 3);

// ===== IMAGE MAPPING FOR FEATURED ROOMS =====
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

// ===== GALLERY IMAGES =====
$gallery_images = array(
    array('image' => 'uploads/rooms/2.webp', 'title' => 'Deluxe Room', 'category' => 'Rooms'),
    array('image' => 'uploads/rooms/3.webp', 'title' => 'Suite', 'category' => 'Rooms'),
    array('image' => 'uploads/rooms/4.webp', 'title' => 'Luxury Suite', 'category' => 'Rooms'),
    array('image' => 'uploads/rooms/5.webp', 'title' => 'Apartment', 'category' => 'Rooms'),
    array('image' => 'uploads/rooms/6.webp', 'title' => 'Executive Room', 'category' => 'Rooms'),
    array('image' => 'uploads/rooms/7.webp', 'title' => 'Family Room', 'category' => 'Rooms'),
    array('image' => 'uploads/rooms/8.webp', 'title' => 'Twin Room', 'category' => 'Rooms'),
);

foreach ($gallery_images as $key => $img) {
    if (!file_exists($img['image'])) {
        $alt_path = str_replace('.webp', '.jpg', $img['image']);
        if (file_exists($alt_path)) {
            $gallery_images[$key]['image'] = $alt_path;
        }
    }
}

// ===== GET HOTEL SETTINGS =====
$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
$hotel_address = isset($hotel['address']) ? $hotel['address'] : 'Galle Road, Unawatuna, Sri Lanka';
$hotel_phone = isset($hotel['phone']) ? $hotel['phone'] : '+94 91 234 5678';

// ===== TESTIMONIALS =====
$testimonials = array(
    array(
        'name' => 'John Doe',
        'country' => 'United Kingdom',
        'text' => 'Amazing experience! The staff was incredibly friendly and the views were breathtaking.',
        'rating' => 5,
        'image' => 'https://ui-avatars.com/api/?name=John+Doe&background=0d4b68&color=fff&size=60'
    ),
    array(
        'name' => 'Sarah Johnson',
        'country' => 'Australia',
        'text' => 'Best resort in Unawatuna! The food was delicious and the room was luxurious.',
        'rating' => 5,
        'image' => 'https://ui-avatars.com/api/?name=Sarah+Johnson&background=0d4b68&color=fff&size=60'
    ),
    array(
        'name' => 'Michael Chen',
        'country' => 'China',
        'text' => 'Perfect location, beautiful beach, and excellent service. Highly recommend!',
        'rating' => 5,
        'image' => 'https://ui-avatars.com/api/?name=Michael+Chen&background=0d4b68&color=fff&size=60'
    ),
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hotel_name; ?> - Luxury Beach Resort & Spa</title>
    <meta name="description" content="Experience luxury at Araliya Beach Resort & Spa Unawatuna. Book your stay today and enjoy world-class amenities, stunning ocean views, and exceptional hospitality.">
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
            --gold-light: #fcd34d;
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
            overflow-x: hidden;
        }
        h1, h2, h3, h4, h5, h6 { font-family: 'Playfair Display', serif; }

        /* ===== SCROLLBAR ===== */
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

        /* ===== HERO SECTION ===== */
        .hero {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
            padding: 80px 0 90px;
            color: var(--white);
            position: relative;
            overflow: hidden;
            margin-top: 0;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 60%;
            height: 150%;
            background: radial-gradient(ellipse at center, rgba(251, 191, 36, 0.06) 0%, transparent 70%);
            border-radius: 50%;
            transform: rotate(15deg);
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -10%;
            width: 40%;
            height: 100%;
            background: radial-gradient(ellipse at center, rgba(251, 191, 36, 0.04) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero .container { position: relative; z-index: 2; }
        .hero .badge-top {
            display: inline-block;
            background: rgba(251, 191, 36, 0.15);
            color: var(--gold);
            padding: 4px 18px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: 1px solid rgba(251, 191, 36, 0.2);
            backdrop-filter: blur(4px);
        }
        .hero h1 {
            font-size: 52px;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 15px;
            font-family: 'Playfair Display', serif;
        }
        .hero h1 .highlight { color: var(--gold); position: relative; }
        .hero h1 .highlight::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gold);
            border-radius: 10px;
            opacity: 0.3;
        }
        .hero p {
            font-size: 18px;
            opacity: 0.85;
            max-width: 500px;
            line-height: 1.8;
            margin: 15px 0 25px;
        }
        .hero .stats {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }
        .hero .stats .stat-item {
            text-align: center;
        }
        .hero .stats .stat-item .number {
            font-size: 28px;
            font-weight: 800;
            color: var(--gold);
            display: block;
            font-family: 'Playfair Display', serif;
        }
        .hero .stats .stat-item .label {
            font-size: 12px;
            opacity: 0.6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hero .search-box {
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 25px 30px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.25);
            position: relative;
            z-index: 3;
        }
        .hero .search-box label {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .hero .search-box .form-control,
        .hero .search-box .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            transition: var(--transition);
            background: #f8fafc;
        }
        .hero .search-box .form-control:focus,
        .hero .search-box .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.15);
            background: var(--white);
        }
        .btn-search-hero {
            background: var(--gold);
            color: var(--primary-dark);
            font-weight: 700;
            padding: 14px 35px;
            border-radius: 12px;
            border: none;
            width: 100%;
            font-size: 16px;
            transition: var(--transition);
        }
        .btn-search-hero:hover {
            background: var(--gold-dark);
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(251, 191, 36, 0.35);
        }

        /* ===== SECTION TITLES ===== */
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        .section-title .highlight { color: var(--gold); }
        .section-subtitle {
            color: var(--text-light);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto 35px;
        }
        .section-divider {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
            margin: 0 auto 20px;
            border-radius: 10px;
        }

        /* ===== ROOM CARDS ===== */
        .room-card {
            background: var(--white);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            height: 100%;
            border: 1px solid #eef2f5;
            position: relative;
        }
        .room-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }
        .room-card .room-image {
            height: 220px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
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
            top: 14px;
            right: 14px;
            background: var(--gold);
            color: var(--primary-dark);
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            z-index: 2;
        }
        .room-card .room-image .featured-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: rgba(0,0,0,0.5);
            color: var(--white);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            backdrop-filter: blur(4px);
            z-index: 2;
        }
        .room-card .room-body { padding: 22px 24px; }
        .room-card .room-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
        }
        .room-card .room-desc {
            color: var(--text-light);
            font-size: 13px;
            margin-bottom: 12px;
        }
        .room-card .room-price {
            font-size: 24px;
            font-weight: 800;
            color: var(--gold-dark);
        }
        .room-card .room-price small {
            font-size: 13px;
            font-weight: 400;
            color: #94a3b8;
        }
        .room-card .room-amenities {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 10px 0;
        }
        .room-card .room-amenities span {
            font-size: 11px;
            color: var(--text-light);
            background: #f1f5f9;
            padding: 3px 12px;
            border-radius: 20px;
        }
        .room-card .room-amenities span i { margin-right: 4px; }
        .btn-book-room {
            background: var(--primary);
            color: var(--white);
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            display: inline-block;
        }
        .btn-book-room:hover {
            background: var(--primary-dark);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(13, 75, 104, 0.3);
        }

        /* ===== GALLERY ===== */
        .gallery-section { background: var(--white); }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .gallery-grid .gallery-item {
            position: relative;
            border-radius: var(--radius-sm);
            overflow: hidden;
            cursor: pointer;
            height: 180px;
            transition: var(--transition);
        }
        .gallery-grid .gallery-item:hover { transform: scale(1.03); box-shadow: var(--shadow-md); }
        .gallery-grid .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        .gallery-grid .gallery-item:hover img { transform: scale(1.05); }
        .gallery-grid .gallery-item .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            background: linear-gradient(transparent, rgba(0,0,0,0.6));
            color: var(--white);
            opacity: 0;
            transition: var(--transition);
        }
        .gallery-grid .gallery-item:hover .overlay { opacity: 1; }
        .gallery-grid .gallery-item .overlay h6 { font-size: 13px; font-weight: 700; margin: 0; }
        .gallery-grid .gallery-item .overlay small { font-size: 11px; opacity: 0.7; }

        /* ===== WHY US ===== */
        .why-us-section { background: var(--bg-light); }
        .why-item {
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 30px 25px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            height: 100%;
            border: 1px solid #eef2f5;
        }
        .why-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--gold);
        }
        .why-item .icon {
            font-size: 40px;
            color: var(--gold);
            margin-bottom: 15px;
        }
        .why-item h6 { font-weight: 700; color: var(--text-dark); font-size: 18px; }
        .why-item p { font-size: 14px; color: var(--text-light); }

        /* ===== TESTIMONIALS ===== */
        .testimonial-card {
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 25px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            transition: var(--transition);
            height: 100%;
            text-align: center;
        }
        .testimonial-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
        .testimonial-card .avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin: 0 auto 12px;
            border: 3px solid var(--gold);
            padding: 2px;
        }
        .testimonial-card .avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .testimonial-card .stars { color: var(--gold); font-size: 14px; margin-bottom: 10px; }
        .testimonial-card .text { font-size: 14px; color: var(--text-light); line-height: 1.7; }
        .testimonial-card .name { font-weight: 700; color: var(--text-dark); margin-top: 10px; }
        .testimonial-card .country { font-size: 12px; color: #94a3b8; }

        /* ===== NEWSLETTER ===== */
        .newsletter-section {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary), var(--primary-light));
            padding: 60px 0;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }
        .newsletter-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 50%;
            height: 200%;
            background: rgba(251, 191, 36, 0.04);
            border-radius: 50%;
        }
        .newsletter-section .container { position: relative; z-index: 2; }
        .newsletter-section h3 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 28px;
        }
        .newsletter-section h3 span { color: var(--gold); }
        .newsletter-section p { opacity: 0.7; }
        .newsletter-section .input-group {
            max-width: 450px;
            margin: 0 auto;
        }
        .newsletter-section .input-group input {
            border-radius: 25px 0 0 25px;
            padding: 12px 20px;
            border: none;
            font-size: 14px;
            background: rgba(255,255,255,0.1);
            color: var(--white);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .newsletter-section .input-group input::placeholder { color: rgba(255,255,255,0.5); }
        .newsletter-section .input-group input:focus {
            background: rgba(255,255,255,0.15);
            box-shadow: none;
            border-color: var(--gold);
        }
        .newsletter-section .input-group button {
            border-radius: 0 25px 25px 0;
            background: var(--gold);
            color: var(--primary-dark);
            font-weight: 700;
            padding: 12px 30px;
            border: none;
            transition: var(--transition);
        }
        .newsletter-section .input-group button:hover {
            background: var(--gold-dark);
            color: var(--white);
        }

        /* ===== FOOTER ===== */
        .footer {
            background: #0a1828;
            color: rgba(255,255,255,0.7);
            padding: 50px 0 25px;
        }
        .footer .brand {
            color: var(--gold);
            font-size: 24px;
            font-weight: 800;
            font-family: 'Playfair Display', serif;
        }
        .footer h5 {
            color: var(--white);
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 18px;
            font-family: 'Inter', sans-serif;
        }
        .footer a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: var(--transition);
            font-size: 14px;
            display: block;
            padding: 4px 0;
        }
        .footer a:hover { color: var(--gold); }
        .footer .social a {
            display: inline-block;
            width: 38px;
            height: 38px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            text-align: center;
            line-height: 38px;
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
            padding-top: 20px;
            margin-top: 30px;
            font-size: 13px;
            text-align: center;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .hero h1 { font-size: 38px; }
            .gallery-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .hero { padding: 50px 0 60px; }
            .hero h1 { font-size: 30px; }
            .hero .search-box { padding: 20px; }
            .gallery-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .gallery-grid .gallery-item { height: 140px; }
            .section-title { font-size: 28px; }
            .navbar-custom .brand { font-size: 20px; }
            .hero .stats { gap: 15px; flex-wrap: wrap; }
        }
        @media (max-width: 480px) {
            .hero h1 { font-size: 26px; }
            .gallery-grid { grid-template-columns: 1fr 1fr; gap: 6px; }
            .gallery-grid .gallery-item { height: 120px; }
        }
    </style>
</head>
<body>

<!-- ================================================================ -->
<!-- ===== NAVBAR ===== -->
<!-- ================================================================ -->
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
                <li class="nav-item"><a href="index.php" class="nav-link active">Home</a></li>
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

<!-- ================================================================ -->
<!-- ===== HERO SECTION ===== -->
<!-- ================================================================ -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="badge-top"><i class="fas fa-award me-1"></i> Award Winning Resort 2026</div>
                <h1>Welcome to <br><span class="highlight"><?php echo $hotel_name; ?></span></h1>
                <p>Experience unparalleled luxury and comfort at our beachfront paradise in Unawatuna, Sri Lanka.</p>

                <div class="stats">
                    <div class="stat-item">
                        <span class="number">4.8★</span>
                        <span class="label">Guest Rating</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">500+</span>
                        <span class="label">Happy Guests</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">50+</span>
                        <span class="label">Luxury Rooms</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">24/7</span>
                        <span class="label">Service</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="search-box">
                    <form method="GET" action="rooms.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label><i class="far fa-calendar me-1"></i> Check In</label>
                                <input type="date" name="check_in" class="form-control" value="<?php echo $check_in; ?>" min="<?php echo $current_date; ?>">
                            </div>
                            <div class="col-md-6">
                                <label><i class="far fa-calendar me-1"></i> Check Out</label>
                                <input type="date" name="check_out" class="form-control" value="<?php echo $check_out; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-user me-1"></i> Adults</label>
                                <select name="adults" class="form-select">
                                    <option value="1" <?php if($adults == 1) echo 'selected'; ?>>1</option>
                                    <option value="2" <?php if($adults == 2) echo 'selected'; ?>>2</option>
                                    <option value="3" <?php if($adults == 3) echo 'selected'; ?>>3</option>
                                    <option value="4" <?php if($adults == 4) echo 'selected'; ?>>4</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-child me-1"></i> Children</label>
                                <select name="children" class="form-select">
                                    <option value="0" <?php if($children == 0) echo 'selected'; ?>>0</option>
                                    <option value="1" <?php if($children == 1) echo 'selected'; ?>>1</option>
                                    <option value="2" <?php if($children == 2) echo 'selected'; ?>>2</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn-search-hero">
                                    <i class="fas fa-search me-2"></i> Check Availability
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================ -->
<!-- ===== FEATURED ROOMS ===== -->
<!-- ================================================================ -->
<section class="py-5">
    <div class="container">
        <div class="text-center">
            <div class="section-divider"></div>
            <h2 class="section-title">Our <span class="highlight">Featured Rooms</span></h2>
            <p class="section-subtitle">Luxurious accommodations designed for your ultimate comfort and relaxation.</p>
        </div>

        <div class="row g-4">
            <?php if(empty($featured_rooms)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-bed fa-4x text-muted mb-3"></i>
                    <h4>No rooms available right now</h4>
                    <p class="text-muted">Please check back later or contact us directly.</p>
                </div>
            <?php else: ?>
                <?php foreach($featured_rooms as $room):
                    $room_type_lower = strtolower(trim(isset($room['room_type']) ? $room['room_type'] : 'standard'));
                    $image_num = 1;
                    foreach($image_number_map as $key => $num) {
                        if(strpos($room_type_lower, $key) !== false) { $image_num = $num; break; }
                    }
                    $image_path = 'uploads/rooms/' . $image_num . '.webp';
                    if(!file_exists($image_path)) {
                        $image_path = 'uploads/rooms/' . $image_num . '.jpg';
                        if(!file_exists($image_path)) { $image_path = ''; }
                    }
                ?>
                <div class="col-md-4">
                    <div class="room-card">
                        <div class="room-image">
                            <?php if(!empty($image_path)): ?>
                                <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars(isset($room['room_type']) ? $room['room_type'] : 'Standard Room'); ?>" loading="lazy">
                            <?php else: ?>
                                <i class="fas fa-hotel" style="font-size: 56px; opacity: 0.3;"></i>
                            <?php endif; ?>
                            <span class="room-badge"><?php echo htmlspecialchars(isset($room['room_type']) ? $room['room_type'] : 'Standard'); ?></span>
                            <span class="featured-badge"><i class="fas fa-star me-1" style="color: var(--gold);"></i> Featured</span>
                        </div>
                        <div class="room-body">
                            <h5 class="room-name"><?php echo htmlspecialchars(isset($room['room_type']) ? $room['room_type'] : 'Standard Room'); ?></h5>
                            <p class="room-desc"><?php echo htmlspecialchars(isset($room['room_desc']) ? $room['room_desc'] : 'Comfortable room with modern amenities'); ?></p>
                            <div class="room-amenities">
                                <span><i class="fas fa-wifi"></i> WiFi</span>
                                <span><i class="fas fa-tv"></i> TV</span>
                                <span><i class="fas fa-snowflake"></i> AC</span>
                                <span><i class="fas fa-shower"></i> Bath</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <span class="room-price">LKR <?php echo number_format(isset($room['base_price']) ? $room['base_price'] : 8000, 0); ?></span>
                                    <small>/ night</small>
                                </div>
                                <a href="room-details.php?id=<?php echo $room['room_id']; ?>" class="btn-book-room">
                                    <i class="fas fa-arrow-right me-1"></i> Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4">
            <a href="rooms.php" class="btn btn-outline-primary px-5 py-2" style="border-radius: 25px; font-weight: 600; border-color: var(--primary); color: var(--primary); transition: var(--transition);">
                View All Rooms <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- ================================================================ -->
<!-- ===== GALLERY SECTION ===== -->
<!-- ================================================================ -->
<section class="gallery-section py-5">
    <div class="container">
        <div class="text-center">
            <div class="section-divider"></div>
            <h2 class="section-title">Our <span class="highlight">Gallery</span></h2>
            <p class="section-subtitle">Explore the beauty and elegance of <?php echo $hotel_name; ?>.</p>
        </div>

        <div class="gallery-grid">
            <?php foreach(array_slice($gallery_images, 0, 8) as $img): ?>
                <div class="gallery-item" onclick="window.location.href='gallery.php'">
                    <img src="<?php echo $img['image']; ?>" alt="<?php echo $img['title']; ?>" loading="lazy">
                    <div class="overlay">
                        <h6><?php echo $img['title']; ?></h6>
                        <small><?php echo $img['category']; ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <a href="gallery.php" class="btn btn-primary px-5 py-2" style="background: var(--primary); border: none; border-radius: 25px; font-weight: 600; transition: var(--transition);">
                View Full Gallery <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- ================================================================ -->
<!-- ===== WHY CHOOSE US ===== -->
<!-- ================================================================ -->
<section class="why-us-section py-5">
    <div class="container">
        <div class="text-center">
            <div class="section-divider"></div>
            <h2 class="section-title">Why <span class="highlight">Choose Us</span></h2>
            <p class="section-subtitle">We make your stay unforgettable with our exceptional services.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="why-item">
                    <div class="icon"><i class="fas fa-umbrella-beach"></i></div>
                    <h6>Beachfront Location</h6>
                    <p>Steps away from pristine Unawatuna beach</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="why-item">
                    <div class="icon"><i class="fas fa-utensils"></i></div>
                    <h6>Fine Dining</h6>
                    <p>Exquisite local & international cuisine</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="why-item">
                    <div class="icon"><i class="fas fa-spa"></i></div>
                    <h6>Spa & Wellness</h6>
                    <p>Rejuvenate with premium spa treatments</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="why-item">
                    <div class="icon"><i class="fas fa-concierge-bell"></i></div>
                    <h6>24/7 Service</h6>
                    <p>Dedicated staff ready to assist anytime</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================ -->
<!-- ===== TESTIMONIALS ===== -->
<!-- ================================================================ -->
<section class="py-5" style="background: var(--white);">
    <div class="container">
        <div class="text-center">
            <div class="section-divider"></div>
            <h2 class="section-title">What Our <span class="highlight">Guests Say</span></h2>
            <p class="section-subtitle">Real experiences from real guests at <?php echo $hotel_name; ?>.</p>
        </div>

        <div class="row g-4">
            <?php foreach($testimonials as $testimonial): ?>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="avatar">
                            <img src="<?php echo $testimonial['image']; ?>" alt="<?php echo $testimonial['name']; ?>">
                        </div>
                        <div class="stars">
                            <?php for($i=0; $i<$testimonial['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="text">"<?php echo $testimonial['text']; ?>"</p>
                        <div class="name"><?php echo $testimonial['name']; ?></div>
                        <div class="country"><?php echo $testimonial['country']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================================================================ -->
<!-- ===== NEWSLETTER ===== -->
<!-- ================================================================ -->
<section class="newsletter-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 text-center text-lg-start">
                <h3>Subscribe to <span>Our Newsletter</span></h3>
                <p>Get the latest offers, updates, and exclusive deals.</p>
            </div>
            <div class="col-lg-6">
                <div class="input-group">
                    <input type="email" class="form-control" placeholder="Your email address">
                    <button class="btn" type="button"><i class="fas fa-paper-plane me-2"></i> Subscribe</button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================ -->
<!-- ===== FOOTER ===== -->
<!-- ================================================================ -->
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
                    <a href="#"><i class="fab fa-tripadvisor"></i></a>
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
                <a href="#">Careers</a>
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