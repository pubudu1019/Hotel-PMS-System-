<?php
// public/blog.php - SUPER ADVANCED PROFESSIONAL VERSION
session_start();
include '../includes/db.php';

$is_logged_in = isset($_SESSION['online_user_id']) && !empty($_SESSION['online_user_id']);
$logged_in_user = $is_logged_in ? $_SESSION['online_user_name'] : '';

$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
$hotel_phone = isset($hotel['phone']) ? $hotel['phone'] : '+94 91 234 5678';
$hotel_address = isset($hotel['address']) ? $hotel['address'] : 'Galle Road, Unawatuna, Sri Lanka';

// ===== BLOG POSTS =====
$blog_posts = array(
    array(
        'id' => 1,
        'title' => 'Top 10 Things to Do in Unawatuna',
        'category' => 'Travel Guide',
        'date' => 'July 20, 2026',
        'image' => 'uploads/blog/blog1.jpg',
        'excerpt' => 'Discover the best attractions, activities, and hidden gems in Unawatuna, Sri Lanka\'s most beautiful coastal town.',
        'author' => 'Admin',
        'comments' => 12,
        'featured' => true
    ),
    array(
        'id' => 2,
        'title' => 'A Guide to Sri Lankan Cuisine',
        'category' => 'Food & Dining',
        'date' => 'July 18, 2026',
        'image' => 'uploads/blog/blog2.jpg',
        'excerpt' => 'Explore the rich flavors and traditional dishes of Sri Lanka, from aromatic curries to delicious street food.',
        'author' => 'Admin',
        'comments' => 8,
        'featured' => false
    ),
    array(
        'id' => 3,
        'title' => 'Why Choose Araliya for Your Next Vacation',
        'category' => 'Hotel News',
        'date' => 'July 15, 2026',
        'image' => 'uploads/blog/blog3.jpg',
        'excerpt' => 'Find out why Araliya Beach Resort is the perfect choice for your stay in Unawatuna.',
        'author' => 'Admin',
        'comments' => 15,
        'featured' => false
    ),
    array(
        'id' => 4,
        'title' => 'Wellness Retreat: Spa & Yoga at Araliya',
        'category' => 'Wellness',
        'date' => 'July 12, 2026',
        'image' => 'uploads/blog/blog4.jpg',
        'excerpt' => 'Rejuvenate your mind and body with our wellness programs, spa treatments, and yoga sessions.',
        'author' => 'Admin',
        'comments' => 6,
        'featured' => false
    ),
    array(
        'id' => 5,
        'title' => 'Best Beaches in Sri Lanka',
        'category' => 'Travel Guide',
        'date' => 'July 10, 2026',
        'image' => 'uploads/blog/blog5.jpg',
        'excerpt' => 'Explore the most beautiful beaches in Sri Lanka, from Unawatuna to Mirissa and beyond.',
        'author' => 'Admin',
        'comments' => 20,
        'featured' => false
    ),
    array(
        'id' => 6,
        'title' => 'Wedding at Araliya: Your Dream Destination Wedding',
        'category' => 'Events',
        'date' => 'July 8, 2026',
        'image' => 'uploads/blog/blog6.jpg',
        'excerpt' => 'Plan your perfect destination wedding at Araliya Beach Resort with our expert wedding planners.',
        'author' => 'Admin',
        'comments' => 9,
        'featured' => false
    ),
);

// ===== CHECK IMAGES =====
foreach ($blog_posts as $key => $post) {
    if (!file_exists($post['image'])) {
        $blog_posts[$key]['image'] = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="800" height="400" viewBox="0 0 800 400"%3E%3Crect width="800" height="400" fill="%230d4b68"/%3E%3Ctext x="400" y="200" font-family="Arial" font-size="32" fill="white" text-anchor="middle" opacity="0.6"%3EBlog Image%3C/text%3E%3C/svg%3E';
    }
}

// ===== GET CATEGORIES =====
$categories = array();
foreach ($blog_posts as $post) {
    if (!in_array($post['category'], $categories)) {
        $categories[] = $post['category'];
    }
}
sort($categories);

// ===== GET FEATURED POST =====
$featured_post = null;
foreach ($blog_posts as $post) {
    if (isset($post['featured']) && $post['featured'] === true) {
        $featured_post = $post;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - <?php echo $hotel_name; ?></title>
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

        /* ===== FEATURED POST ===== */
        .featured-post {
            background: var(--white);
            border-radius: var(--radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            transition: var(--transition);
            margin-bottom: 30px;
        }
        .featured-post:hover {
            box-shadow: var(--shadow-md);
        }
        .featured-post .image {
            height: 300px;
            overflow: hidden;
        }
        .featured-post .image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        .featured-post:hover .image img { transform: scale(1.03); }
        .featured-post .content {
            padding: 25px 30px;
        }
        .featured-post .category-badge {
            display: inline-block;
            background: var(--gold);
            color: var(--primary-dark);
            padding: 4px 16px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }
        .featured-post .title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 10px 0 8px;
        }
        .featured-post .title a {
            color: var(--text-dark);
            text-decoration: none;
            transition: var(--transition);
        }
        .featured-post .title a:hover { color: var(--gold); }
        .featured-post .excerpt {
            color: var(--text-light);
            font-size: 15px;
            line-height: 1.7;
        }
        .featured-post .meta {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #94a3b8;
            margin-top: 12px;
        }
        .featured-post .meta i { margin-right: 4px; }
        .featured-post .btn-read {
            background: var(--primary);
            color: var(--white);
            padding: 8px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            display: inline-block;
            margin-top: 10px;
        }
        .featured-post .btn-read:hover {
            background: var(--primary-dark);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(13, 75, 104, 0.3);
        }

        /* ===== BLOG POST CARD ===== */
        .blog-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            transition: var(--transition);
            height: 100%;
        }
        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .blog-card .image {
            height: 200px;
            overflow: hidden;
        }
        .blog-card .image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        .blog-card:hover .image img { transform: scale(1.05); }
        .blog-card .content {
            padding: 18px 20px 20px;
        }
        .blog-card .category-badge {
            display: inline-block;
            background: var(--gold);
            color: var(--primary-dark);
            padding: 2px 12px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
        }
        .blog-card .title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 8px 0 6px;
        }
        .blog-card .title a {
            color: var(--text-dark);
            text-decoration: none;
            transition: var(--transition);
        }
        .blog-card .title a:hover { color: var(--gold); }
        .blog-card .excerpt {
            color: var(--text-light);
            font-size: 13px;
            line-height: 1.6;
        }
        .blog-card .meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #94a3b8;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eef2f5;
        }
        .blog-card .meta i { margin-right: 3px; }
        .blog-card .btn-read-sm {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            transition: var(--transition);
        }
        .blog-card .btn-read-sm:hover {
            color: var(--gold);
        }

        /* ===== SIDEBAR ===== */
        .sidebar-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 20px;
            border: 1px solid #eef2f5;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }
        .sidebar-card h6 {
            font-weight: 700;
            color: var(--text-dark);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gold);
            font-family: 'Inter', sans-serif;
        }
        .sidebar-card h6 i { color: var(--gold); margin-right: 8px; }
        .sidebar-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-card ul li {
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .sidebar-card ul li:last-child { border-bottom: none; }
        .sidebar-card ul li a {
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
            font-size: 14px;
        }
        .sidebar-card ul li a:hover { color: var(--gold); }
        .sidebar-card ul li a i { font-size: 10px; margin-right: 6px; }

        .sidebar-newsletter {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary), var(--primary-light));
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            padding: 25px 20px;
            text-align: center;
        }
        .sidebar-newsletter h6 {
            color: var(--gold);
            border-bottom-color: rgba(251, 191, 36, 0.2);
        }
        .sidebar-newsletter p {
            font-size: 13px;
            opacity: 0.8;
        }
        .sidebar-newsletter input {
            border-radius: 25px;
            padding: 10px 16px;
            border: none;
            font-size: 13px;
            width: 100%;
            margin-bottom: 8px;
            background: rgba(255,255,255,0.1);
            color: var(--white);
        }
        .sidebar-newsletter input::placeholder { color: rgba(255,255,255,0.5); }
        .sidebar-newsletter input:focus {
            outline: none;
            background: rgba(255,255,255,0.15);
        }
        .sidebar-newsletter .btn-subscribe {
            background: var(--gold);
            color: var(--primary-dark);
            border: none;
            border-radius: 25px;
            padding: 10px;
            font-weight: 700;
            font-size: 14px;
            width: 100%;
            transition: var(--transition);
        }
        .sidebar-newsletter .btn-subscribe:hover {
            background: var(--gold-dark);
            color: var(--white);
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
            .featured-post .image { height: 200px; }
            .featured-post .content { padding: 20px; }
            .featured-post .title { font-size: 20px; }
            .blog-card .image { height: 180px; }
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
                <li class="nav-item"><a href="blog.php" class="nav-link active">Blog</a></li>
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
        <h1>Our <span>Blog</span></h1>
        <p>Stay updated with the latest news, travel tips, and stories</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Blog</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ===== BLOG CONTENT ===== -->
<section class="py-2">
    <div class="container">
        <div class="text-center">
            <div class="section-divider"></div>
            <h2 class="section-title">Latest <span class="highlight">Articles</span></h2>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Featured Post -->
                <?php if($featured_post): ?>
                    <div class="featured-post">
                        <div class="image">
                            <img src="<?php echo $featured_post['image']; ?>" alt="<?php echo $featured_post['title']; ?>" loading="lazy">
                        </div>
                        <div class="content">
                            <span class="category-badge">⭐ Featured</span>
                            <span class="category-badge" style="background: #e2e8f0; margin-left: 6px;"><?php echo $featured_post['category']; ?></span>
                            <h3 class="title"><a href="blog_post.php?id=<?php echo $featured_post['id']; ?>"><?php echo $featured_post['title']; ?></a></h3>
                            <p class="excerpt"><?php echo $featured_post['excerpt']; ?></p>
                            <div class="meta">
                                <span><i class="far fa-calendar-alt"></i> <?php echo $featured_post['date']; ?></span>
                                <span><i class="far fa-user"></i> <?php echo $featured_post['author']; ?></span>
                                <span><i class="far fa-comment"></i> <?php echo $featured_post['comments']; ?> Comments</span>
                            </div>
                            <a href="blog_post.php?id=<?php echo $featured_post['id']; ?>" class="btn-read"><i class="fas fa-arrow-right me-2"></i> Read More</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Blog Grid -->
                <div class="row g-4">
                    <?php foreach($blog_posts as $post): ?>
                        <?php if(isset($post['featured']) && $post['featured'] === true) continue; ?>
                        <div class="col-md-6">
                            <div class="blog-card">
                                <div class="image">
                                    <img src="<?php echo $post['image']; ?>" alt="<?php echo $post['title']; ?>" loading="lazy">
                                </div>
                                <div class="content">
                                    <span class="category-badge"><?php echo $post['category']; ?></span>
                                    <h5 class="title"><a href="blog_post.php?id=<?php echo $post['id']; ?>"><?php echo $post['title']; ?></a></h5>
                                    <p class="excerpt"><?php echo substr($post['excerpt'], 0, 80) . '...'; ?></p>
                                    <div class="meta">
                                        <span><i class="far fa-calendar-alt"></i> <?php echo $post['date']; ?></span>
                                        <a href="blog_post.php?id=<?php echo $post['id']; ?>" class="btn-read-sm">Read More →</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Search -->
                <div class="sidebar-card">
                    <h6><i class="fas fa-search"></i> Search</h6>
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search blog..." style="border-radius: 25px 0 0 25px; font-size: 13px; border: 2px solid #e2e8f0;">
                        <button class="btn" style="background: var(--gold); border-radius: 0 25px 25px 0; color: var(--primary-dark); font-weight: 600; font-size: 13px; border: none;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Categories -->
                <div class="sidebar-card">
                    <h6><i class="fas fa-folder"></i> Categories</h6>
                    <ul>
                        <?php foreach($categories as $cat): ?>
                            <li><a href="#"><i class="fas fa-chevron-right"></i> <?php echo $cat; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Recent Posts -->
                <div class="sidebar-card">
                    <h6><i class="fas fa-clock"></i> Recent Posts</h6>
                    <ul>
                        <?php foreach(array_slice($blog_posts, 0, 4) as $post): ?>
                            <li><a href="blog_post.php?id=<?php echo $post['id']; ?>"><i class="fas fa-chevron-right"></i> <?php echo $post['title']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div class="sidebar-newsletter">
                    <h6 style="color: var(--gold);"><i class="fas fa-envelope"></i> Newsletter</h6>
                    <p>Subscribe to get updates and offers</p>
                    <input type="email" placeholder="Your email address">
                    <button class="btn-subscribe"><i class="fas fa-paper-plane me-2"></i> Subscribe</button>
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