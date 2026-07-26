<?php
// public/about.php - SUPER ADVANCED PROFESSIONAL VERSION
session_start();
include '../includes/db.php';

$is_logged_in = isset($_SESSION['online_user_id']) && !empty($_SESSION['online_user_id']);
$logged_in_user = $is_logged_in ? $_SESSION['online_user_name'] : '';

$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
$hotel_phone = isset($hotel['phone']) ? $hotel['phone'] : '+94 91 234 5678';
$hotel_address = isset($hotel['address']) ? $hotel['address'] : 'Galle Road, Unawatuna, Sri Lanka';

// ===== GET ROOM STATS =====
$total_rooms = $conn->query("SELECT COUNT(*) as total FROM rooms")->fetch_assoc()['total'];
$total_bookings = $conn->query("SELECT COUNT(*) as total FROM reservations")->fetch_assoc()['total'];
$total_guests = $conn->query("SELECT COUNT(DISTINCT guest_name) as total FROM reservations")->fetch_assoc()['total'];

// ===== AWARDS =====
$awards = array(
    array(
        'icon' => 'fa-trophy',
        'title' => 'Best Beach Resort 2026',
        'desc' => 'Awarded by Sri Lanka Tourism Board'
    ),
    array(
        'icon' => 'fa-star',
        'title' => '5-Star Luxury Rating',
        'desc' => 'Certified by Global Hotel Association'
    ),
    array(
        'icon' => 'fa-leaf',
        'title' => 'Eco-Friendly Resort',
        'desc' => 'Green Tourism Certification'
    ),
    array(
        'icon' => 'fa-award',
        'title' => 'Excellence in Hospitality',
        'desc' => 'TripAdvisor Travellers Choice 2026'
    )
);

// ===== TEAM MEMBERS =====
$team = array(
    array(
        'name' => 'Dr. Anura Perera',
        'position' => 'General Manager',
        'image' => 'https://ui-avatars.com/api/?name=Dr.+Anura+Perera&background=0d4b68&color=fff&size=100',
        'bio' => '25+ years of experience in luxury hospitality'
    ),
    array(
        'name' => 'Mrs. Chamali Fernando',
        'position' => 'Operations Director',
        'image' => 'https://ui-avatars.com/api/?name=Chamali+Fernando&background=0d4b68&color=fff&size=100',
        'bio' => 'Expert in hotel operations & guest relations'
    ),
    array(
        'name' => 'Mr. Mahesh Wijesinghe',
        'position' => 'Executive Chef',
        'image' => 'https://ui-avatars.com/api/?name=Mahesh+Wijesinghe&background=0d4b68&color=fff&size=100',
        'bio' => 'Award-winning chef with international experience'
    ),
    array(
        'name' => 'Ms. Dilini Rathnayake',
        'position' => 'Spa & Wellness Manager',
        'image' => 'https://ui-avatars.com/api/?name=Dilini+Rathnayake&background=0d4b68&color=fff&size=100',
        'bio' => 'Certified wellness & spa specialist'
    )
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo $hotel_name; ?></title>
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

        /* ===== ABOUT CONTENT ===== */
        .about-content {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 35px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
        }
        .about-content p {
            color: var(--text-light);
            font-size: 15px;
            line-height: 1.8;
        }
        .about-content .highlight-text {
            color: var(--text-dark);
            font-weight: 600;
        }

        /* ===== STATS ===== */
        .stat-box {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            transition: var(--transition);
            height: 100%;
        }
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--gold);
        }
        .stat-box .number {
            font-size: 36px;
            font-weight: 800;
            color: var(--gold-dark);
            font-family: 'Playfair Display', serif;
        }
        .stat-box .label {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 4px;
        }
        .stat-box .icon {
            font-size: 32px;
            color: var(--gold);
            margin-bottom: 8px;
        }

        /* ===== AWARDS ===== */
        .award-box {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            transition: var(--transition);
            height: 100%;
        }
        .award-box:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--gold);
        }
        .award-box .icon {
            font-size: 40px;
            color: var(--gold);
            margin-bottom: 10px;
        }
        .award-box h6 {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 16px;
        }
        .award-box p {
            font-size: 13px;
            color: var(--text-light);
            margin: 0;
        }

        /* ===== TEAM ===== */
        .team-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            transition: var(--transition);
            height: 100%;
        }
        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--gold);
        }
        .team-card .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 12px;
            border: 3px solid var(--gold);
            padding: 3px;
        }
        .team-card .avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .team-card h6 {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 16px;
            margin-bottom: 2px;
        }
        .team-card .position {
            font-size: 13px;
            color: var(--gold-dark);
            font-weight: 600;
        }
        .team-card .bio {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 6px;
        }

        /* ===== WHY CHOOSE US ===== */
        .why-box {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            transition: var(--transition);
            height: 100%;
        }
        .why-box:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--gold);
        }
        .why-box .icon {
            font-size: 36px;
            color: var(--gold);
            margin-bottom: 10px;
        }
        .why-box h6 {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 16px;
        }
        .why-box p {
            font-size: 13px;
            color: var(--text-light);
            margin: 0;
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
            .about-content { padding: 20px; }
            .stat-box .number { font-size: 28px; }
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
                <li class="nav-item"><a href="about.php" class="nav-link active">About</a></li>
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
        <h1>About <span>Us</span></h1>
        <p>Discover the story behind <?php echo $hotel_name; ?></p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">About</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ===== ABOUT CONTENT ===== -->
<section class="py-2">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8 mx-auto">
                <div class="about-content">
                    <div class="text-center">
                        <div class="section-divider"></div>
                        <h2 class="section-title">Welcome to <span class="highlight">Paradise</span></h2>
                    </div>
                    <p>
                        <span class="highlight-text"><?php echo $hotel_name; ?></span> is a premier beachfront resort located in the heart of 
                        Unawatuna, Sri Lanka. Our resort offers a perfect escape for travelers seeking luxury, comfort, and 
                        authentic Sri Lankan hospitality.
                    </p>
                    <p>
                        Established with a vision to create a sanctuary where modern luxury meets tropical paradise, 
                        our resort has been welcoming guests from around the world since 2015. With pristine beaches, 
                        world-class amenities, and breathtaking ocean views, we provide an unforgettable experience 
                        for every guest.
                    </p>
                    <p>
                        At <span class="highlight-text">Araliya</span>, we believe in creating memories that last a lifetime. 
                        Our dedicated team is committed to providing exceptional service, ensuring that your stay with 
                        us is nothing short of extraordinary.
                    </p>
                    <div class="text-center mt-3">
                        <a href="rooms.php" class="btn btn-primary" style="background: var(--gold); border: none; color: var(--primary-dark); font-weight:700; padding:10px 35px; border-radius:25px; transition: var(--transition);">
                            <i class="fas fa-bed me-2"></i> Book Your Stay
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== STATS ===== -->
<section class="py-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <div class="icon"><i class="fas fa-hotel"></i></div>
                    <div class="number"><?php echo $total_rooms; ?>+</div>
                    <div class="label">Luxury Rooms</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <div class="number"><?php echo $total_guests; ?>+</div>
                    <div class="label">Happy Guests</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <div class="icon"><i class="fas fa-star"></i></div>
                    <div class="number">4.8★</div>
                    <div class="label">Guest Rating</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <div class="icon"><i class="fas fa-trophy"></i></div>
                    <div class="number">10+</div>
                    <div class="label">Awards Won</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== AWARDS ===== -->
<section class="py-4" style="background: var(--white);">
    <div class="container">
        <div class="text-center">
            <div class="section-divider"></div>
            <h2 class="section-title">Our <span class="highlight">Awards</span></h2>
        </div>
        <div class="row g-4">
            <?php foreach($awards as $award): ?>
                <div class="col-md-3 col-6">
                    <div class="award-box">
                        <div class="icon"><i class="fas <?php echo $award['icon']; ?>"></i></div>
                        <h6><?php echo $award['title']; ?></h6>
                        <p><?php echo $award['desc']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== WHY CHOOSE US ===== -->
<section class="py-4">
    <div class="container">
        <div class="text-center">
            <div class="section-divider"></div>
            <h2 class="section-title">Why <span class="highlight">Choose Us</span></h2>
        </div>
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="why-box">
                    <div class="icon"><i class="fas fa-umbrella-beach"></i></div>
                    <h6>Beachfront Location</h6>
                    <p>Steps from Unawatuna beach</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="why-box">
                    <div class="icon"><i class="fas fa-utensils"></i></div>
                    <h6>Fine Dining</h6>
                    <p>Exquisite cuisine</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="why-box">
                    <div class="icon"><i class="fas fa-spa"></i></div>
                    <h6>Spa & Wellness</h6>
                    <p>Premium spa treatments</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="why-box">
                    <div class="icon"><i class="fas fa-concierge-bell"></i></div>
                    <h6>24/7 Service</h6>
                    <p>Dedicated staff</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== TEAM ===== -->
<section class="py-4" style="background: var(--white);">
    <div class="container">
        <div class="text-center">
            <div class="section-divider"></div>
            <h2 class="section-title">Our <span class="highlight">Team</span></h2>
        </div>
        <div class="row g-4">
            <?php foreach($team as $member): ?>
                <div class="col-md-3 col-6">
                    <div class="team-card">
                        <div class="avatar">
                            <img src="<?php echo $member['image']; ?>" alt="<?php echo $member['name']; ?>">
                        </div>
                        <h6><?php echo $member['name']; ?></h6>
                        <div class="position"><?php echo $member['position']; ?></div>
                        <div class="bio"><?php echo $member['bio']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
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