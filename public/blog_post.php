<?php
// public/blog_post.php - Single Blog Post
session_start();
include '../includes/db.php';

$is_logged_in = isset($_SESSION['online_user_id']) && !empty($_SESSION['online_user_id']);
$logged_in_user = $is_logged_in ? $_SESSION['online_user_name'] : '';

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Blog posts data (same as blog.php)
$blog_posts = array(
    1 => array(
        'id' => 1,
        'title' => 'Top 10 Things to Do in Unawatuna',
        'category' => 'Travel Guide',
        'date' => 'July 20, 2026',
        'image' => 'uploads/blog/blog1.jpg',
        'content' => '<p>Unawatuna is one of Sri Lanka\'s most beautiful coastal towns, known for its stunning beaches, vibrant culture, and rich history. Here are the top 10 things you must do when visiting Unawatuna:</p>
        <h5>1. Visit Unawatuna Beach</h5>
        <p>The main beach is a crescent-shaped stretch of golden sand with calm waters perfect for swimming and snorkeling.</p>
        <h5>2. Explore the Japanese Peace Pagoda</h5>
        <p>This stunning white stupa offers panoramic views of the ocean and surrounding area.</p>
        <h5>3. Snorkeling at Rumassala</h5>
        <p>The coral reefs around Rumassala are home to diverse marine life.</p>
        <h5>4. Visit Galle Fort</h5>
        <p>A UNESCO World Heritage site, this 17th-century Dutch fort is a must-visit.</p>
        <h5>5. Jungle Beach</h5>
        <p>A hidden gem surrounded by lush greenery, perfect for a quiet escape.</p>
        <h5>6. Whale Watching</h5>
        <p>Unawatuna is a great base for whale watching tours.</p>
        <h5>7. Try Local Cuisine</h5>
        <p>Don\'t miss the fresh seafood and traditional Sri Lankan dishes.</p>
        <h5>8. Visit the Turtle Hatchery</h5>
        <p>Learn about conservation efforts and see baby turtles.</p>
        <h5>9. Take a Boat Ride</h5>
        <p>Explore the coastline and nearby islands by boat.</p>
        <h5>10. Relax at Araliya Beach Resort</h5>
        <p>Enjoy world-class amenities and hospitality at Araliya.</p>',
        'author' => 'Admin',
        'comments' => 12
    ),
    2 => array(
        'id' => 2,
        'title' => 'A Guide to Sri Lankan Cuisine',
        'category' => 'Food & Dining',
        'date' => 'July 18, 2026',
        'image' => 'uploads/blog/blog2.jpg',
        'content' => '<p>Sri Lankan cuisine is a vibrant fusion of flavors influenced by centuries of trade and cultural exchange. Here\'s a guide to the must-try dishes:</p>
        <h5>Rice and Curry</h5>
        <p>The staple meal consisting of rice served with a variety of curries including vegetables, fish, or meat.</p>
        <h5>Hoppers (Appa)</h5>
        <p>Bowl-shaped pancakes made from fermented rice flour and coconut milk.</p>
        <h5>Kottu Roti</h5>
        <p>A popular street food made from chopped roti, vegetables, eggs, and meat stir-fried together.</p>
        <h5>Fish Ambul Thiyal</h5>
        <p>A sour fish curry cooked with goraka (garcinia) and spices.</p>
        <h5>Lamprais</h5>
        <p>A Dutch-influenced dish of rice cooked in meat stock, served with side dishes wrapped in a banana leaf.</p>
        <h5>Pol Sambol</h5>
        <p>A spicy coconut relish made with grated coconut, chili, and lime.</p>
        <p>At Araliya, our chefs prepare authentic Sri Lankan dishes using fresh, locally-sourced ingredients.</p>',
        'author' => 'Admin',
        'comments' => 8
    ),
    3 => array(
        'id' => 3,
        'title' => 'Why Choose Araliya for Your Next Vacation',
        'category' => 'Hotel News',
        'date' => 'July 15, 2026',
        'image' => 'uploads/blog/blog3.jpg',
        'content' => '<p>Planning your next vacation? Here\'s why Araliya Beach Resort should be your top choice:</p>
        <h5>Prime Location</h5>
        <p>Located on the stunning Unawatuna beachfront, offering breathtaking ocean views.</p>
        <h5>Luxury Accommodations</h5>
        <p>Our rooms and suites are designed for comfort and elegance, with modern amenities.</p>
        <h5>World-Class Dining</h5>
        <p>Enjoy a variety of cuisines at our restaurants, from local Sri Lankan to international favorites.</p>
        <h5>Wellness & Spa</h5>
        <p>Rejuvenate with our spa treatments, yoga sessions, and wellness programs.</p>
        <h5>Exceptional Service</h5>
        <p>Our dedicated staff ensures your stay is memorable and comfortable.</p>
        <p>Book your stay today and experience the best of Sri Lanka at Araliya.</p>',
        'author' => 'Admin',
        'comments' => 15
    ),
    4 => array(
        'id' => 4,
        'title' => 'Wellness Retreat: Spa & Yoga at Araliya',
        'category' => 'Wellness',
        'date' => 'July 12, 2026',
        'image' => 'uploads/blog/blog4.jpg',
        'content' => '<p>At Araliya, we believe in holistic wellness. Our spa and yoga programs are designed to rejuvenate your mind, body, and soul.</p>
        <h5>Our Spa Treatments</h5>
        <p>We offer a range of treatments including massages, facials, and body scrubs using natural ingredients.</p>
        <h5>Yoga Sessions</h5>
        <p>Daily yoga classes are available for all levels, from beginners to advanced practitioners.</p>
        <h5>Meditation</h5>
        <p>Guided meditation sessions help you find inner peace and relaxation.</p>
        <h5>Wellness Programs</h5>
        <p>Customized wellness programs include detox plans, nutritional guidance, and fitness activities.</p>
        <p>Reconnect with yourself and nature at Araliya\'s wellness retreat.</p>',
        'author' => 'Admin',
        'comments' => 6
    ),
    5 => array(
        'id' => 5,
        'title' => 'Best Beaches in Sri Lanka',
        'category' => 'Travel Guide',
        'date' => 'July 10, 2026',
        'image' => 'uploads/blog/blog5.jpg',
        'content' => '<p>Sri Lanka is home to some of the most beautiful beaches in the world. Here\'s our top 5 list:</p>
        <h5>1. Unawatuna Beach</h5>
        <p>A crescent-shaped beach with calm waters, perfect for swimming and snorkeling.</p>
        <h5>2. Mirissa Beach</h5>
        <p>Known for its stunning sunsets and whale watching opportunities.</p>
        <h5>3. Bentota Beach</h5>
        <p>A long stretch of golden sand with water sports activities.</p>
        <h5>4. Arugam Bay</h5>
        <p>A surfer\'s paradise with consistent waves and a laid-back vibe.</p>
        <h5>5. Hikkaduwa Beach</h5>
        <p>Famous for its coral reefs and vibrant marine life.</p>
        <p>Whichever beach you choose, you\'re guaranteed a memorable experience.</p>',
        'author' => 'Admin',
        'comments' => 20
    ),
    6 => array(
        'id' => 6,
        'title' => 'Wedding at Araliya: Your Dream Destination Wedding',
        'category' => 'Events',
        'date' => 'July 8, 2026',
        'image' => 'uploads/blog/blog6.jpg',
        'content' => '<p>Dreaming of a beach wedding? Araliya Beach Resort is the perfect venue for your special day.</p>
        <h5>Venue Options</h5>
        <p>Choose from our beachfront, garden, or indoor venues for your ceremony and reception.</p>
        <h5>Wedding Packages</h5>
        <p>Customizable packages include decoration, catering, photography, and more.</p>
        <h5>Catering</h5>
        <p>Our chefs create exquisite menus featuring local and international cuisine.</p>
        <h5>Accommodation</h5>
        <p>Special rates for wedding guests and honeymoon suites for the newlyweds.</p>
        <h5>Planning Support</h5>
        <p>Our wedding coordinators ensure everything runs smoothly on your big day.</p>
        <p>Contact us to start planning your dream wedding at Araliya.</p>',
        'author' => 'Admin',
        'comments' => 9
    ),
);

// Get post data
$post = isset($blog_posts[$post_id]) ? $blog_posts[$post_id] : $blog_posts[1];

// Check if image exists
if (!file_exists($post['image'])) {
    $post['image'] = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="800" height="400" viewBox="0 0 800 400"%3E%3Crect width="800" height="400" fill="%230d4b68"/%3E%3Ctext x="400" y="200" font-family="Arial" font-size="32" fill="white" text-anchor="middle" opacity="0.6"%3EBlog Image%3C/text%3E%3C/svg%3E';
}

$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
$hotel_phone = isset($hotel['phone']) ? $hotel['phone'] : '+94 91 234 5678';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $post['title']; ?> - <?php echo $hotel_name; ?></title>
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
        
        .post-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .post-content {
            background: white;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid #eef2f5;
        }
        .post-content h1 {
            font-size: 28px;
            font-weight: 800;
            color: #082f42;
        }
        .post-content .category-badge {
            display: inline-block;
            background: var(--gold);
            color: #082f42;
            padding: 4px 16px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }
        .post-content .meta {
            color: #94a3b8;
            font-size: 14px;
        }
        .post-content .meta i { margin-right: 4px; }
        .post-content .body {
            color: #475569;
            font-size: 15px;
            line-height: 1.8;
        }
        .post-content .body h5 {
            color: #082f42;
            font-weight: 700;
            margin-top: 20px;
        }
        .post-content .body p {
            margin-bottom: 15px;
        }
        
        .sidebar-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #eef2f5;
            margin-bottom: 20px;
        }
        .sidebar-card h6 {
            font-weight: 700;
            color: #082f42;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gold);
        }
        .sidebar-card ul {
            list-style: none;
            padding: 0;
        }
        .sidebar-card ul li {
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .sidebar-card ul li a {
            color: #475569;
            text-decoration: none;
            transition: 0.3s;
            font-size: 14px;
        }
        .sidebar-card ul li a:hover { color: var(--gold); }
        .sidebar-card ul li:last-child { border-bottom: none; }
        
        .btn-back {
            background: #e2e8f0;
            color: #475569;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-back:hover {
            background: #cbd5e1;
            color: #1e293b;
        }
        
        .footer { background: #0a1828; color: rgba(255,255,255,0.7); padding: 30px 0 15px; margin-top: 40px; }
        .footer .brand { color: var(--gold); font-size: 20px; font-weight: 800; }
        .footer a { color: rgba(255,255,255,0.6); text-decoration: none; transition: 0.3s; }
        .footer a:hover { color: var(--gold); }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; margin-top: 20px; font-size: 13px; }
        
        @media (max-width: 768px) {
            .post-image { height: 250px; }
            .post-content { padding: 20px; }
            .post-content h1 { font-size: 22px; }
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
                    <li class="nav-item"><a href="register.php" class="btn-book-now"><i class="fas fa-user-plus me-1"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== BLOG POST ===== -->
<section class="py-4">
    <div class="container">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="blog.php" class="btn-back"><i class="fas fa-arrow-left me-2"></i> Back to Blog</a>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="post-content">
                    <!-- Image -->
                    <img src="<?php echo $post['image']; ?>" alt="<?php echo $post['title']; ?>" class="post-image mb-4">
                    
                    <!-- Category & Title -->
                    <span class="category-badge"><?php echo $post['category']; ?></span>
                    <h1 class="mt-3"><?php echo $post['title']; ?></h1>
                    
                    <!-- Meta -->
                    <div class="meta mt-2">
                        <span><i class="far fa-calendar-alt"></i> <?php echo $post['date']; ?></span>
                        <span><i class="far fa-user"></i> <?php echo $post['author']; ?></span>
                        <span><i class="far fa-comment"></i> <?php echo $post['comments']; ?> Comments</span>
                    </div>
                    
                    <hr>
                    
                    <!-- Content -->
                    <div class="body">
                        <?php echo $post['content']; ?>
                    </div>
                    
                    <hr>
                    
                    <!-- Share -->
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="fw-bold">Share this post:</span>
                        <a href="#" class="btn btn-sm btn-outline-primary" style="border-radius:50%; width:36px; height:36px; display:flex; align-items:center; justify-content:center;"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-info" style="border-radius:50%; width:36px; height:36px; display:flex; align-items:center; justify-content:center;"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-danger" style="border-radius:50%; width:36px; height:36px; display:flex; align-items:center; justify-content:center;"><i class="fab fa-pinterest"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-success" style="border-radius:50%; width:36px; height:36px; display:flex; align-items:center; justify-content:center;"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-secondary" style="border-radius:50%; width:36px; height:36px; display:flex; align-items:center; justify-content:center;"><i class="fas fa-envelope"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Search -->
                <div class="sidebar-card">
                    <h6><i class="fas fa-search me-2" style="color: var(--gold);"></i> Search</h6>
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search blog..." style="border-radius:25px 0 0 25px; font-size:13px;">
                        <button class="btn" style="background: var(--gold); border-radius:0 25px 25px 0; color:#082f42; font-weight:600; font-size:13px;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Recent Posts -->
                <div class="sidebar-card">
                    <h6><i class="fas fa-clock me-2" style="color: var(--gold);"></i> Recent Posts</h6>
                    <ul>
                        <li><a href="blog_post.php?id=1"><i class="fas fa-chevron-right me-2" style="font-size:10px;"></i> Top 10 Things to Do in Unawatuna</a></li>
                        <li><a href="blog_post.php?id=2"><i class="fas fa-chevron-right me-2" style="font-size:10px;"></i> A Guide to Sri Lankan Cuisine</a></li>
                        <li><a href="blog_post.php?id=3"><i class="fas fa-chevron-right me-2" style="font-size:10px;"></i> Why Choose Araliya</a></li>
                        <li><a href="blog_post.php?id=4"><i class="fas fa-chevron-right me-2" style="font-size:10px;"></i> Wellness Retreat</a></li>
                    </ul>
                </div>
                
                <!-- Book a Room -->
                <div class="sidebar-card" style="background: linear-gradient(135deg, #082f42, #0d4b68); color: white; border: none;">
                    <h6 style="color: var(--gold); border-bottom-color: rgba(251,191,36,0.3);"><i class="fas fa-bed me-2"></i> Book Your Stay</h6>
                    <p style="font-size:13px; opacity:0.8;">Experience luxury at Araliya Beach Resort.</p>
                    <a href="rooms.php" class="btn w-100" style="background: var(--gold); border-radius:25px; color:#082f42; font-weight:700; font-size:14px;">
                        <i class="fas fa-arrow-right me-2"></i> Check Availability
                    </a>
                </div>
            </div>
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
                <a href="blog.php">Blog</a> | 
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