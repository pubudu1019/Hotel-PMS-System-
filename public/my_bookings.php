<?php
// public/my_bookings.php - SUPER ADVANCED PROFESSIONAL VERSION
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

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all user bookings
$bookings = array();
$booking_query = $conn->prepare("
    SELECT ob.*, r.room_number, rt.type_name 
    FROM online_bookings ob
    LEFT JOIN rooms r ON ob.room_id = r.room_id
    LEFT JOIN room_types rt ON r.room_type = rt.type_name
    WHERE ob.user_id = ?
");
$booking_query->bind_param("i", $user_id);
$booking_query->execute();
$result = $booking_query->get_result();
while($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

// Filter bookings
$filtered_bookings = array();
foreach($bookings as $b) {
    $status = $b['booking_status'] ?? 'pending';
    $is_upcoming = strtotime($b['check_in']) >= strtotime(date('Y-m-d'));
    
    // Status filter
    if ($status_filter != 'all') {
        if ($status_filter == 'upcoming' && !$is_upcoming) continue;
        if ($status_filter == 'past' && $is_upcoming) continue;
        if ($status_filter == $status) {
            // exact match
        } else {
            continue;
        }
    }
    
    // Search filter
    if (!empty($search)) {
        $search_lower = strtolower($search);
        $name_match = strpos(strtolower($b['guest_name']), $search_lower) !== false;
        $ref_match = strpos(strtolower($b['booking_reference'] ?? ''), $search_lower) !== false;
        $type_match = strpos(strtolower($b['type_name'] ?? ''), $search_lower) !== false;
        if (!$name_match && !$ref_match && !$type_match) {
            continue;
        }
    }
    
    $filtered_bookings[] = $b;
}

$total_bookings = count($bookings);
$filtered_count = count($filtered_bookings);

// Get counts
$upcoming = 0;
$past = 0;
$confirmed = 0;
$pending = 0;
$cancelled = 0;
$checked_in = 0;

foreach($bookings as $b) {
    $status = $b['booking_status'] ?? 'pending';
    if(strtotime($b['check_in']) >= strtotime(date('Y-m-d'))) {
        $upcoming++;
    } else {
        $past++;
    }
    if($status == 'confirmed') $confirmed++;
    elseif($status == 'pending') $pending++;
    elseif($status == 'cancelled') $cancelled++;
    elseif($status == 'checked_in') $checked_in++;
}

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
    <title>My Bookings - <?php echo $hotel_name; ?></title>
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

        .stat-box {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 18px 15px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            transition: var(--transition);
            height: 100%;
        }
        .stat-box:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        .stat-box .number {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            font-family: 'Playfair Display', serif;
        }
        .stat-box .label {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 2px;
        }
        .stat-box .icon { font-size: 20px; color: var(--gold); margin-bottom: 4px; }

        .filter-bar {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 15px 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }
        .filter-bar .filter-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .filter-bar .filter-btns .btn-filter {
            padding: 4px 16px;
            border-radius: 20px;
            border: 2px solid #e2e8f0;
            background: transparent;
            color: var(--text-light);
            font-weight: 600;
            font-size: 12px;
            transition: var(--transition);
            text-decoration: none;
            cursor: pointer;
        }
        .filter-bar .filter-btns .btn-filter:hover {
            border-color: var(--gold);
            color: var(--primary);
        }
        .filter-bar .filter-btns .btn-filter.active {
            background: var(--gold);
            border-color: var(--gold);
            color: var(--primary-dark);
        }
        .filter-bar .search-box {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .filter-bar .search-box input {
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 13px;
            outline: none;
            transition: var(--transition);
            background: #f8fafc;
            width: 200px;
        }
        .filter-bar .search-box input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
        }
        .filter-bar .search-box button {
            background: var(--gold);
            border: none;
            border-radius: 20px;
            padding: 5px 16px;
            font-weight: 600;
            font-size: 13px;
            color: var(--primary-dark);
            transition: var(--transition);
        }
        .filter-bar .search-box button:hover {
            background: var(--gold-dark);
            color: var(--white);
        }

        .booking-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eef2f5;
            transition: var(--transition);
            margin-bottom: 16px;
        }
        .booking-card:hover {
            box-shadow: var(--shadow-md);
        }
        .booking-card .status-badge {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-badge.confirmed { background: #d1fae5; color: #065f46; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.cancelled { background: #fee2e2; color: #991b1b; }
        .status-badge.checked_in { background: #dbeafe; color: #1e40af; }
        .status-badge.checked_out { background: #e5e7eb; color: #374151; }
        .status-badge.no_show { background: #fef3c7; color: #92400e; }

        .btn-cancel {
            background: #ef4444;
            color: var(--white);
            padding: 4px 14px;
            border-radius: 16px;
            border: none;
            font-weight: 600;
            font-size: 11px;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }
        .btn-cancel:hover {
            background: #dc2626;
            color: var(--white);
            transform: translateY(-2px);
        }

        .btn-view {
            background: var(--primary);
            color: var(--white);
            padding: 4px 14px;
            border-radius: 16px;
            border: none;
            font-weight: 600;
            font-size: 11px;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }
        .btn-view:hover {
            background: var(--primary-dark);
            color: var(--white);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i { font-size: 64px; color: #d1d5db; margin-bottom: 20px; }
        .empty-state h5 { color: #6b7280; font-weight: 600; }
        .empty-state p { color: #9ca3af; }

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
            .filter-bar { flex-direction: column; align-items: stretch; }
            .filter-bar .search-box input { width: 100%; }
            .filter-bar .search-box { flex-wrap: wrap; }
            .stat-box .number { font-size: 20px; }
            .booking-card .row > div { margin-bottom: 8px; }
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
                <li class="nav-item"><a href="my_bookings.php" class="nav-link active">My Bookings</a></li>
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
        <h1>My <span>Bookings</span></h1>
        <p>View and manage all your reservations</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">My Bookings</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ===== MY BOOKINGS ===== -->
<section class="py-2">
    <div class="container">
        <!-- Messages -->
        <?php if(!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <div class="icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="number"><?php echo $total_bookings; ?></div>
                    <div class="label">Total Bookings</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <div class="icon"><i class="fas fa-clock" style="color: #f59e0b;"></i></div>
                    <div class="number" style="color: #f59e0b;"><?php echo $upcoming; ?></div>
                    <div class="label">Upcoming</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <div class="icon"><i class="fas fa-check-circle" style="color: #10b981;"></i></div>
                    <div class="number" style="color: #10b981;"><?php echo $confirmed; ?></div>
                    <div class="label">Confirmed</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <div class="icon"><i class="fas fa-times-circle" style="color: #ef4444;"></i></div>
                    <div class="number" style="color: #ef4444;"><?php echo $cancelled; ?></div>
                    <div class="label">Cancelled</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-btns">
                <a href="my_bookings.php" class="btn-filter <?php if($status_filter == 'all') echo 'active'; ?>">All</a>
                <a href="my_bookings.php?status=upcoming" class="btn-filter <?php if($status_filter == 'upcoming') echo 'active'; ?>">Upcoming</a>
                <a href="my_bookings.php?status=past" class="btn-filter <?php if($status_filter == 'past') echo 'active'; ?>">Past</a>
                <a href="my_bookings.php?status=confirmed" class="btn-filter <?php if($status_filter == 'confirmed') echo 'active'; ?>">Confirmed</a>
                <a href="my_bookings.php?status=pending" class="btn-filter <?php if($status_filter == 'pending') echo 'active'; ?>">Pending</a>
                <a href="my_bookings.php?status=cancelled" class="btn-filter <?php if($status_filter == 'cancelled') echo 'active'; ?>">Cancelled</a>
            </div>
            <div class="search-box">
                <form method="GET" action="" class="d-flex gap-2">
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    <input type="text" name="search" placeholder="Search bookings..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                    <?php if(!empty($search)): ?>
                        <a href="my_bookings.php?status=<?php echo $status_filter; ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Bookings List -->
        <?php if(empty($filtered_bookings)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-calendar-plus"></i>
                        <h5>No bookings found</h5>
                        <p>
                            <?php if(!empty($search)): ?>
                                No results found for "<strong><?php echo htmlspecialchars($search); ?></strong>"
                            <?php else: ?>
                                You haven't made any bookings yet. Start your first booking now!
                            <?php endif; ?>
                        </p>
                        <a href="rooms.php" class="btn btn-primary" style="background: var(--primary); border: none; border-radius:25px; padding:10px 30px;">
                            <i class="fas fa-search me-1"></i> Browse Rooms
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <p class="text-muted small mb-3">Showing <strong><?php echo $filtered_count; ?></strong> of <strong><?php echo $total_bookings; ?></strong> bookings</p>
                </div>
            </div>

            <?php foreach($filtered_bookings as $b): 
                $status = isset($b['booking_status']) ? $b['booking_status'] : 'pending';
                $status_class = $status == 'confirmed' ? 'confirmed' : ($status == 'cancelled' ? 'cancelled' : ($status == 'checked_in' ? 'checked_in' : ($status == 'checked_out' ? 'checked_out' : 'pending')));
                $is_upcoming = strtotime($b['check_in']) >= strtotime(date('Y-m-d'));
                $can_cancel = ($is_upcoming && $status != 'cancelled' && $status != 'checked_in' && $status != 'checked_out');
            ?>
            <div class="booking-card">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <h6 class="fw-bold mb-1"><?php echo isset($b['type_name']) ? htmlspecialchars($b['type_name']) : 'Standard'; ?></h6>
                        <small class="text-muted">Room #<?php echo isset($b['room_number']) ? $b['room_number'] : 'N/A'; ?></small>
                        <br>
                        <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Check In</small>
                        <strong><?php echo date('d M Y', strtotime($b['check_in'])); ?></strong>
                        <br>
                        <small class="text-muted d-block mt-1">Check Out</small>
                        <strong><?php echo date('d M Y', strtotime($b['check_out'])); ?></strong>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted d-block">Guests</small>
                        <strong><?php echo isset($b['adults']) ? $b['adults'] : 1; ?> Adults</strong>
                        <br>
                        <small><?php echo isset($b['children']) ? $b['children'] : 0; ?> Children</small>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted d-block">Total</small>
                        <strong class="text-primary">LKR <?php echo number_format(isset($b['total_amount']) ? $b['total_amount'] : 0, 0); ?></strong>
                        <br>
                        <small class="text-muted"><?php echo $b['num_of_nights']; ?> nights</small>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <div class="d-flex flex-wrap gap-1 justify-content-md-end">
                            <a href="booking_details.php?id=<?php echo $b['booking_id']; ?>" class="btn-view">
                                <i class="fas fa-eye me-1"></i> View
                            </a>
                            <?php if($can_cancel): ?>
                                <a href="cancel_booking.php?id=<?php echo $b['booking_id']; ?>" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
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