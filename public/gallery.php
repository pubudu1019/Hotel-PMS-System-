<?php
// public/gallery.php - SUPER ADVANCED PROFESSIONAL VERSION
session_start();
include '../includes/db.php';

$is_logged_in = isset($_SESSION['online_user_id']) && !empty($_SESSION['online_user_id']);
$logged_in_user = $is_logged_in ? $_SESSION['online_user_name'] : '';

// ================================================================
// ===== GALLERY IMAGES =====
// ================================================================
$gallery_images = array(
    // ===== ROOM PHOTOS (From uploads/rooms/) =====
    array(
        'image' => 'uploads/rooms/1.webp',
        'title' => 'Standard Room',
        'category' => 'Rooms'
    ),
    array(
        'image' => 'uploads/rooms/2.webp',
        'title' => 'Deluxe Room',
        'category' => 'Rooms'
    ),
    array(
        'image' => 'uploads/rooms/3.webp',
        'title' => 'Suite',
        'category' => 'Rooms'
    ),
    array(
        'image' => 'uploads/rooms/4.webp',
        'title' => 'Luxury Suite',
        'category' => 'Rooms'
    ),
    array(
        'image' => 'uploads/rooms/5.webp',
        'title' => 'Apartment',
        'category' => 'Rooms'
    ),
    array(
        'image' => 'uploads/rooms/6.webp',
        'title' => 'Executive Room',
        'category' => 'Rooms'
    ),
    array(
        'image' => 'uploads/rooms/7.webp',
        'title' => 'Family Room',
        'category' => 'Rooms'
    ),
    array(
        'image' => 'uploads/rooms/8.webp',
        'title' => 'Twin Room',
        'category' => 'Rooms'
    ),
    
    // ===== ADD MORE ROOM PHOTOS HERE =====
    // array(
    //     'image' => 'uploads/rooms/9.webp',
    //     'title' => 'Premium Room',
    //     'category' => 'Rooms'
    // ),
);

// ================================================================
// ===== CHECK IF IMAGES EXIST =====
// ================================================================
foreach ($gallery_images as $key => $img) {
    if (!file_exists($img['image'])) {
        $alt_path = str_replace('.webp', '.jpg', $img['image']);
        if (file_exists($alt_path)) {
            $gallery_images[$key]['image'] = $alt_path;
        } else {
            $gallery_images[$key]['image'] = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300"%3E%3Crect width="400" height="300" fill="%230d4b68"/%3E%3Ctext x="200" y="155" font-family="Arial" font-size="24" fill="white" text-anchor="middle" opacity="0.5"%3ENo Image%3C/text%3E%3C/svg%3E';
        }
    }
}

// ================================================================
// ===== GET CATEGORIES FOR FILTER =====
// ================================================================
$categories = array();
foreach ($gallery_images as $img) {
    if (!in_array($img['category'], $categories)) {
        $categories[] = $img['category'];
    }
}
sort($categories);

// ================================================================
// ===== GET HOTEL SETTINGS =====
// ================================================================
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
    <title>Gallery - <?php echo $hotel_name; ?></title>
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

        /* ===== GALLERY FILTER ===== */
        .gallery-filter {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .gallery-filter .btn-filter {
            padding: 6px 18px;
            border-radius: 20px;
            border: 2px solid #e2e8f0;
            background: transparent;
            color: var(--text-light);
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition);
            cursor: pointer;
        }
        .gallery-filter .btn-filter:hover {
            border-color: var(--gold);
            color: var(--primary);
        }
        .gallery-filter .btn-filter.active {
            background: var(--gold);
            border-color: var(--gold);
            color: var(--primary-dark);
        }

        /* ===== GALLERY GRID ===== */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        @media (max-width: 992px) { .gallery-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .gallery-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .gallery-grid { grid-template-columns: 1fr; } }

        .gallery-item {
            position: relative;
            border-radius: var(--radius-sm);
            overflow: hidden;
            cursor: pointer;
            height: 250px;
            transition: var(--transition);
        }
        .gallery-item:hover {
            transform: scale(1.03);
            box-shadow: var(--shadow-md);
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        .gallery-item:hover img { transform: scale(1.05); }
        .gallery-item .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            color: var(--white);
            opacity: 0;
            transition: var(--transition);
        }
        .gallery-item:hover .overlay { opacity: 1; }
        .gallery-item .overlay h6 {
            font-size: 15px;
            font-weight: 700;
            margin: 0;
        }
        .gallery-item .overlay small {
            font-size: 12px;
            opacity: 0.7;
        }
        .gallery-item .category-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--gold);
            color: var(--primary-dark);
            padding: 3px 14px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            z-index: 2;
        }
        .gallery-item .view-icon {
            position: absolute;
            bottom: 70px;
            left: 50%;
            transform: translateX(-50%) scale(0);
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(4px);
            color: var(--white);
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            transition: var(--transition);
            z-index: 2;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .gallery-item:hover .view-icon {
            transform: translateX(-50%) scale(1);
        }

        /* ===== LIGHTBOX ===== */
        #lightbox {
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
        #lightbox.active { display: flex; }
        #lightbox .lightbox-content {
            position: relative;
            max-width: 80%;
            max-height: 85%;
        }
        #lightbox .lightbox-content img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        #lightbox .close-btn {
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
        #lightbox .close-btn:hover {
            transform: rotate(90deg);
            color: var(--gold);
        }
        #lightbox .lightbox-title {
            position: absolute;
            bottom: -40px;
            left: 0;
            color: var(--white);
            font-size: 18px;
            font-weight: 600;
        }
        #lightbox .lightbox-close-bottom {
            position: absolute;
            bottom: -45px;
            right: 0;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--white);
            padding: 6px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 13px;
        }
        #lightbox .lightbox-close-bottom:hover {
            background: rgba(255,255,255,0.25);
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
            .gallery-item { height: 200px; }
        }
        @media (max-width: 480px) {
            .gallery-item { height: 180px; }
            .gallery-filter .btn-filter { font-size: 11px; padding: 4px 12px; }
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
                <li class="nav-item"><a href="gallery.php" class="nav-link active">Gallery</a></li>
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
        <h1>Our <span>Gallery</span></h1>
        <p>Explore the beauty and elegance of <?php echo $hotel_name; ?></p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Gallery</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ===== GALLERY ===== -->
<section class="py-2">
    <div class="container">
        <div class="text-center">
            <div class="section-divider"></div>
            <h2 class="section-title">Photo <span class="highlight">Collection</span></h2>
        </div>

        <!-- Filter Buttons -->
        <div class="gallery-filter">
            <button class="btn-filter active" data-filter="all">All</button>
            <?php foreach($categories as $cat): ?>
                <button class="btn-filter" data-filter="<?php echo $cat; ?>"><?php echo $cat; ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Gallery Grid -->
        <div class="gallery-grid" id="galleryGrid">
            <?php foreach($gallery_images as $index => $img): ?>
                <div class="gallery-col" data-category="<?php echo $img['category']; ?>">
                    <div class="gallery-item" onclick="openLightbox(<?php echo $index; ?>)">
                        <img src="<?php echo $img['image']; ?>" alt="<?php echo $img['title']; ?>" loading="lazy">
                        <span class="category-badge"><?php echo $img['category']; ?></span>
                        <span class="view-icon"><i class="fas fa-search-plus me-1"></i> View</span>
                        <div class="overlay">
                            <h6><?php echo $img['title']; ?></h6>
                            <small><i class="fas fa-tag me-1"></i> <?php echo $img['category']; ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if(empty($gallery_images)): ?>
            <div class="text-center py-5">
                <i class="fas fa-images fa-4x text-muted mb-3"></i>
                <h4>No images found</h4>
                <p class="text-muted">Please add images to the gallery.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== LIGHTBOX ===== -->
<div id="lightbox">
    <div class="lightbox-content">
        <button class="close-btn" onclick="closeLightbox()">&times;</button>
        <img id="lightboxImage" src="" alt="Gallery Image">
        <div class="lightbox-title" id="lightboxTitle">Image Title</div>
        <button class="lightbox-close-bottom" onclick="closeLightbox()"><i class="fas fa-times me-2"></i> Close</button>
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

<script>
// ===== LIGHTBOX =====
function openLightbox(index) {
    var images = <?php echo json_encode($gallery_images); ?>;
    var img = document.getElementById('lightboxImage');
    var title = document.getElementById('lightboxTitle');
    var lightbox = document.getElementById('lightbox');
    
    img.src = images[index].image;
    title.textContent = images[index].title + ' - ' + images[index].category;
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});

// Close on click outside
document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) closeLightbox();
});

// ===== FILTER =====
document.querySelectorAll('.btn-filter').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.btn-filter').forEach(function(b) {
            b.classList.remove('active');
        });
        this.classList.add('active');
        
        var filter = this.getAttribute('data-filter');
        var items = document.querySelectorAll('.gallery-col');
        
        items.forEach(function(item) {
            if (filter === 'all' || item.getAttribute('data-category') === filter) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>