<?php
// Session check and DB connection
include 'includes/session_check.php';
include 'includes/db.php';

// Get logged-in user info
$user_name = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin';
$user_role = isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'user';

// ─── Handle Check‑Out action ───
if (isset($_GET['action']) && $_GET['action'] === 'checkout' && isset($_GET['res_id'])) {
    $res_id = intval($_GET['res_id']);
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get room_id from reservation
        $roomQuery = "SELECT room_id FROM reservations WHERE res_id = ?";
        $stmt = $conn->prepare($roomQuery);
        $stmt->bind_param("i", $res_id);
        $stmt->execute();
        $roomResult = $stmt->get_result();
        
        if ($roomResult && $roomResult->num_rows > 0) {
            $row = $roomResult->fetch_assoc();
            $room_id = $row['room_id'];
            
            if ($room_id) {
                // Update room status to 'available'
                $updateRoom = "UPDATE rooms SET status = 'available' WHERE room_id = ?";
                $stmt = $conn->prepare($updateRoom);
                $stmt->bind_param("i", $room_id);
                $stmt->execute();
            }
            
            // Update reservation status to 'completed'
            $updateRes = "UPDATE reservations SET status = 'completed' WHERE res_id = ?";
            $stmt = $conn->prepare($updateRes);
            $stmt->bind_param("i", $res_id);
            $stmt->execute();
        }
        
        $conn->commit();
        $_SESSION['success_msg'] = "Guest checked out successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_msg'] = "Error processing checkout: " . $e->getMessage();
    }
    
    header("Location: departures.php");
    exit;
}

// ─── Handle Extend Stay action ───
if (isset($_GET['action']) && $_GET['action'] === 'extend' && isset($_GET['res_id'])) {
    $res_id = intval($_GET['res_id']);
    
    // Extend by 1 day
    $updateExtend = "UPDATE reservations SET check_out = DATE_ADD(check_out, INTERVAL 1 DAY) WHERE res_id = ?";
    $stmt = $conn->prepare($updateExtend);
    $stmt->bind_param("i", $res_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Stay extended successfully!";
    } else {
        $_SESSION['error_msg'] = "Error extending stay: " . $conn->error;
    }
    
    header("Location: departures.php");
    exit;
}

// ─── Fetch today's departures ───
$departures = [];
$sql = "SELECT 
            r.res_id AS id,
            r.guest_name AS guest,
            r.room_id,
            rm.room_number AS room,
            r.check_in,
            r.check_out,
            r.status AS res_status,
            rm.status AS room_status
        FROM reservations r
        LEFT JOIN rooms rm ON r.room_id = rm.room_id
        WHERE r.check_out = CURDATE()
          AND r.status != 'cancelled'
        ORDER BY r.check_out ASC";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Determine display status
        if ($row['res_status'] === 'completed') {
            $row['display_status'] = 'checked-out';
        } elseif ($row['room_status'] === 'occupied') {
            $row['display_status'] = 'pending';
        } else {
            $row['display_status'] = 'checked-out';
        }
        $departures[] = $row;
    }
}

// Get statistics
$total = count($departures);
$pending = 0;
$checkedOut = 0;
$extended = 0;
foreach ($departures as $d) {
    if ($d['display_status'] === 'pending') $pending++;
    elseif ($d['display_status'] === 'checked-out') $checkedOut++;
    else $extended++;
}

// Check for session messages
$success_msg = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : '';
$error_msg = isset($_SESSION['error_msg']) ? $_SESSION['error_msg'] : '';
unset($_SESSION['success_msg']);
unset($_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departures | Araliya PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ===== GLOBAL STYLES (EXACTLY MATCH DASHBOARD) ===== */
        :root {
            --primary-blue: #0d4b68;
            --gold: #fbbf24;
            --arrival-color: #2ec4b6;
            --departure-color: #e71d36;
            --inhoused-color: #ff9f1c;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #f4f7f6; 
            font-family: 'Segoe UI', 'Poppins', sans-serif; 
            color: #333; 
        }
        
        /* ===== TOP NAVBAR (EXACTLY MATCH DASHBOARD) ===== */
        .araliya-navbar { 
            background: linear-gradient(135deg, #0a3a4f 0%, #0d4b68 100%);
            color: white; 
            padding: 8px 20px;
            position: sticky;
            top: 0;
            z-index: 1050;
            box-shadow: 0 2px 15px rgba(13, 75, 104, 0.3);
        }
        
        .brand-title { 
            font-size: 20px; 
            font-weight: 700; 
            margin: 0; 
            color: var(--gold); 
            letter-spacing: 1px;
        }
        .brand-sub { 
            font-size: 10px; 
            display: block; 
            color: #a8c8d8; 
            letter-spacing: 0.5px;
        }
        
        .nav-tabs-custom {
            display: flex;
            gap: 5px;
            list-style: none;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
        }
        .nav-tabs-custom li a { 
            color: #a8c8d8; 
            text-decoration: none; 
            font-size: 13px; 
            font-weight: 500; 
            padding: 8px 12px; 
            display: block; 
            border-radius: 6px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .nav-tabs-custom li a:hover { 
            color: #fff; 
            background: rgba(255,255,255,0.1);
        }
        .nav-tabs-custom li a.active { 
            color: #fff; 
            background: rgba(251, 191, 36, 0.2);
            border: 1px solid var(--gold);
        }
        .nav-tabs-custom li a i { margin-right: 5px; }
        
        .live-clock-badge {
            background: rgba(255,255,255,0.12);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(5px);
        }
        .live-clock-badge i { color: var(--gold); }
        
        .user-dropdown {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
        }
        .user-dropdown:hover { background: rgba(255,255,255,0.2); }
        .user-dropdown .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--gold);
            color: var(--primary-blue);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            margin-right: 6px;
        }
        .badge-role {
            background: rgba(251, 191, 36, 0.2);
            color: var(--gold);
            font-size: 10px;
            padding: 2px 10px;
            border-radius: 12px;
            margin-left: 5px;
        }
        
        /* ===== MAIN CONTENT (DASHBOARD STYLE) ===== */
        .main-content { 
            padding: 24px 30px 40px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 28px; 
            flex-wrap: wrap; 
        }
        .page-header h2 { 
            font-weight: 700; 
            font-size: 1.8rem; 
            color: var(--primary-blue);
            letter-spacing: -0.3px;
        }
        .page-header h2 i { color: var(--gold); }
        .page-header h2 small { 
            font-weight: 400; 
            font-size: 0.9rem; 
            color: #6c757d; 
            display: block; 
            margin-top: 4px; 
        }
        
        /* ===== STAT CARDS (DASHBOARD STYLE) ===== */
        .stat-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-bottom: 28px; 
        }
        .stat-card { 
            background: white;
            border-radius: 12px;
            padding: 20px 18px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid #eef2f7;
            transition: all 0.3s ease;
        }
        .stat-card:hover { 
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        .stat-card.gold::before { background: var(--gold); }
        .stat-card.green::before { background: #4ade80; }
        .stat-card.orange::before { background: var(--inhoused-color); }
        .stat-card.blue::before { background: var(--primary-blue); }
        
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }
        .stat-card.gold .stat-icon { background: rgba(251, 191, 36, 0.15); color: var(--gold); }
        .stat-card.green .stat-icon { background: rgba(74, 222, 128, 0.15); color: #4ade80; }
        .stat-card.orange .stat-icon { background: rgba(255, 159, 28, 0.15); color: var(--inhoused-color); }
        .stat-card.blue .stat-icon { background: rgba(13, 75, 104, 0.15); color: var(--primary-blue); }
        
        .stat-card .stat-number { 
            font-size: 32px; 
            font-weight: 800; 
            color: #1a1a2e; 
            line-height: 1.2;
        }
        .stat-card .stat-label { 
            font-size: 12px; 
            color: #8898aa; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        
        /* ===== TABLE WRAPPER (DASHBOARD STYLE) ===== */
        .table-wrap { 
            background: white; 
            border-radius: 12px;
            padding: 24px 28px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
        }
        .table-wrap .table-title { 
            color: var(--primary-blue); 
            font-size: 1.1rem; 
            font-weight: 700; 
            margin-bottom: 20px; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            flex-wrap: wrap; 
        }
        .table-wrap .table-title i { color: var(--gold); }
        
        .table-controls { 
            display: flex; 
            gap: 12px; 
            align-items: center; 
            margin-left: auto; 
            flex-wrap: wrap; 
        }
        .table-controls input, 
        .table-controls select { 
            background: #f8fafc; 
            border: 1px solid #e2e8f0; 
            border-radius: 40px; 
            padding: 6px 16px; 
            color: #333; 
            font-size: 0.8rem; 
            outline: none; 
            transition: all 0.3s ease;
        }
        .table-controls input::placeholder { color: #94a3b8; }
        .table-controls input:focus, 
        .table-controls select:focus { 
            border-color: var(--gold); 
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
        }
        .table-controls select option { color: #333; }
        
        /* ===== TABLE (DASHBOARD STYLE) ===== */
        .table { 
            color: #333; 
            border-collapse: separate; 
            border-spacing: 0 6px; 
            margin-bottom: 0; 
        }
        .table thead th { 
            background: rgba(13, 75, 104, 0.05); 
            color: var(--primary-blue); 
            font-weight: 700; 
            font-size: 0.7rem; 
            text-transform: uppercase; 
            letter-spacing: 0.8px; 
            padding: 12px 16px; 
            border: none; 
        }
        .table thead th:first-child { border-radius: 10px 0 0 10px; }
        .table thead th:last-child { border-radius: 0 10px 10px 0; }
        
        .table tbody tr { 
            background: #f8fafc; 
            transition: all 0.2s; 
        }
        .table tbody tr:hover { background: #f0f7fa; }
        .table tbody td { 
            padding: 12px 16px; 
            border: none; 
            color: #4a5568; 
            vertical-align: middle; 
        }
        
        /* ===== BADGES (DASHBOARD STYLE) ===== */
        .badge-status { 
            padding: 4px 14px; 
            border-radius: 40px; 
            font-size: 0.7rem; 
            font-weight: 600; 
            text-transform: uppercase; 
            display: inline-block;
        }
        .badge-status.pending { 
            background: rgba(255, 159, 28, 0.15); 
            color: var(--inhoused-color); 
        }
        .badge-status.checked-out { 
            background: rgba(46, 196, 182, 0.15); 
            color: var(--arrival-color); 
        }
        .badge-status.extended { 
            background: rgba(13, 75, 104, 0.1); 
            color: var(--primary-blue); 
        }
        
        /* ===== BUTTONS (DASHBOARD STYLE) ===== */
        .btn-action { 
            padding: 4px 14px; 
            border-radius: 40px; 
            font-size: 0.7rem; 
            font-weight: 600; 
            border: none; 
            cursor: pointer; 
            transition: all 0.2s; 
            color: #fff; 
            text-decoration: none;
            display: inline-block;
            margin: 2px 4px;
        }
        .btn-action.checkout { 
            background: var(--departure-color); 
        }
        .btn-action.checkout:hover { 
            background: #c0392b; 
            transform: scale(1.05);
            color: #fff;
        }
        .btn-action.extend { 
            background: var(--gold); 
            color: #1a1a2e; 
        }
        .btn-action.extend:hover { 
            background: #fcd34d; 
            transform: scale(1.05);
            color: #1a1a2e;
        }
        .btn-action i { margin-right: 4px; }
        
        .status-done { 
            color: var(--arrival-color); 
            font-size: 0.75rem; 
            font-weight: 600;
        }
        .status-done i { margin-right: 4px; }
        
        .status-extended { 
            color: var(--primary-blue); 
            font-size: 0.75rem; 
            font-weight: 600;
        }
        .status-extended i { margin-right: 4px; }
        
        /* ===== ALERTS ===== */
        .alert-custom {
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        .alert-success-custom {
            background: rgba(46, 196, 182, 0.1);
            color: var(--arrival-color);
            border-color: rgba(46, 196, 182, 0.15);
        }
        .alert-error-custom {
            background: rgba(231, 29, 54, 0.1);
            color: var(--departure-color);
            border-color: rgba(231, 29, 54, 0.15);
        }
        
        /* ===== MODAL (DASHBOARD STYLE) ===== */
        .modal-content { 
            background: white; 
            border: none; 
            border-radius: 16px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .modal-header { 
            border-bottom: 1px solid #eef2f7; 
            padding: 20px 24px;
        }
        .modal-header .modal-title { 
            color: var(--primary-blue); 
            font-weight: 700;
        }
        .modal-header .btn-close { 
            background: transparent;
        }
        .modal-body { 
            color: #4a5568; 
            padding: 24px;
        }
        .modal-body strong { color: var(--primary-blue); }
        .modal-footer { 
            border-top: 1px solid #eef2f7; 
            padding: 16px 24px;
        }
        
        .btn-secondary-custom {
            background: #eef2f7;
            color: #4a5568;
            border: none;
            padding: 8px 24px;
            border-radius: 40px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-secondary-custom:hover { background: #e2e8f0; }
        
        .btn-danger-custom {
            background: var(--departure-color);
            color: white;
            border: none;
            padding: 8px 24px;
            border-radius: 40px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-danger-custom:hover { 
            background: #c0392b; 
            transform: scale(1.05);
        }
        
        .btn-warning-custom {
            background: var(--gold);
            color: #1a1a2e;
            border: none;
            padding: 8px 24px;
            border-radius: 40px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-warning-custom:hover { 
            background: #fcd34d; 
            transform: scale(1.05);
        }
        
        /* ===== EMPTY STATE ===== */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }
        .empty-state i {
            font-size: 4rem;
            color: #e2e8f0;
            margin-bottom: 16px;
        }
        .empty-state h5 { color: var(--primary-blue); font-weight: 600; }
        .empty-state p { color: #94a3b8; }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .araliya-navbar { flex-direction: column; gap: 10px; }
            .nav-tabs-custom { justify-content: center; }
            .brand-title { font-size: 16px; }
            .main-content { padding: 16px; }
            .table-wrap { padding: 16px; }
        }
        
        @media (max-width: 768px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
            .page-header h2 { font-size: 1.4rem; }
            .table-wrap { padding: 12px; overflow-x: auto; }
            .table thead th, .table tbody td { padding: 8px 10px; font-size: 0.8rem; }
            .table-controls { flex-direction: column; align-items: stretch; width: 100%; }
            .table-controls input, .table-controls select { width: 100%; }
            .stat-card .stat-number { font-size: 26px; }
        }
        
        @media (max-width: 576px) {
            .stat-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-card { padding: 14px 12px; }
            .stat-card .stat-number { font-size: 22px; }
            .stat-card .stat-icon { width: 40px; height: 40px; font-size: 16px; }
        }
        
        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .main-content { animation: fadeInUp 0.4s ease; }
        .stat-card { animation: fadeInUp 0.3s ease; }
        .table tbody tr { animation: fadeInUp 0.3s ease; }
        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.2s; }
    </style>
</head>
<body>

<!-- ============================================ -->
<!-- TOP NAVBAR (EXACTLY MATCH DASHBOARD) -->
<!-- ============================================ -->
<div class="araliya-navbar d-flex justify-content-between align-items-center flex-wrap">
    <!-- Brand -->
    <div class="d-flex align-items-center">
        <div class="me-3">
            <span class="brand-title"><i class="fas fa-hotel text-warning me-2"></i> ARALIYA</span>
            <span class="brand-sub">Beach Resort & Spa Unawatuna</span>
        </div>
        
        <!-- Navigation Tabs -->
        <ul class="nav-tabs-custom">
            <li><a href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="arrivals.php"><i class="fas fa-plane-arrival"></i> Arrivals</a></li>
            <li><a href="departures.php" class="active"><i class="fas fa-plane-departure"></i> Departures</a></li>
            <li><a href="inhouse_rooms.php"><i class="fas fa-user-check"></i> In-House</a></li>
            
            <?php if(isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                <li><a href="manage_users.php" style="color: var(--gold);"><i class="fas fa-users-cog"></i> Users</a></li>
                <li><a href="admin_panel.php" style="color: #ecd18c;"><i class="fas fa-sliders-h"></i> Admin</a></li>
            <?php else: ?>
                <li><a href="javascript:void(0);" style="opacity: 0.4; cursor: not-allowed;" title="Admin Only"><i class="fas fa-users-cog"></i> Users</a></li>
            <?php endif; ?>
        </ul>
    </div>
    
    <!-- Right Side: Clock + User -->
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="live-clock-badge">
            <i class="far fa-calendar-alt me-1"></i> <span id="live-date"></span>
            <span class="mx-1">|</span>
            <i class="far fa-clock me-1"></i> <span id="live-time"></span>
        </div>
        
        <div class="dropdown">
            <button class="user-dropdown dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                <span class="user-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></span>
                <span><?= $user_name ?></span>
                <span class="badge-role"><?= ucfirst($user_role) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> My Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MAIN CONTENT -->
<!-- ============================================ -->
<div class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2>
                <i class="fas fa-plane-departure me-2"></i>Today's Departures
                <small><?= date('l, d F Y'); ?> · <span id="departureCount"><?= $total; ?></span> guests departing</small>
            </h2>
        </div>
        <div>
            <button class="btn btn-sm btn-outline-primary me-2" onclick="refreshData()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <a href="quick_checkout.php" class="btn btn-sm" style="background: var(--departure-color); color: #fff;">
                <i class="fas fa-sign-out-alt me-1"></i> Quick Check-out
            </a>
        </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if ($success_msg): ?>
        <div class="alert alert-success-custom alert-custom">
            <i class="fas fa-check-circle me-2"></i> <?= $success_msg; ?>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error-custom alert-custom">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg; ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="stat-grid">
        <div class="stat-card gold">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-number"><?= $total; ?></div>
            <div class="stat-label">Total Departures</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-number"><?= $pending; ?></div>
            <div class="stat-label">Pending Check-out</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number"><?= $checkedOut; ?></div>
            <div class="stat-label">Checked Out</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-calendar-plus"></i></div>
            <div class="stat-number"><?= $extended; ?></div>
            <div class="stat-label">Extended Stay</div>
        </div>
    </div>
    
    <!-- Table -->
    <div class="table-wrap">
        <div class="table-title">
            <i class="fas fa-list"></i> Departure List
            <div class="table-controls">
                <input type="text" id="searchDepartures" placeholder="🔍 Search guest, room..." onkeyup="filterDepartures()">
                <select id="statusFilter" onchange="filterDepartures()">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="checked-out">Checked Out</option>
                    <option value="extended">Extended</option>
                </select>
            </div>
        </div>
        
        <?php if (empty($departures)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-check"></i>
                <h5>No Departures Today</h5>
                <p>All guests are staying or no check-outs scheduled for today.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table" id="departureTable">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Guest Name</th>
                            <th>Reservation ID</th>
                            <th>Check‑in</th>
                            <th>Check‑out</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="departureTableBody">
                        <?php foreach ($departures as $d): ?>
                        <tr data-status="<?= $d['display_status']; ?>" data-search="<?= strtolower($d['guest'] . ' ' . $d['room'] . ' ' . $d['id']); ?>">
                            <td><strong><?= $d['room'] ?? '—'; ?></strong></td>
                            <td><?= htmlspecialchars($d['guest']); ?></td>
                            <td>#<?= $d['id']; ?></td>
                            <td><?= date('d M Y', strtotime($d['check_in'])); ?></td>
                            <td><?= date('d M Y', strtotime($d['check_out'])); ?></td>
                            <td>
                                <span class="badge-status <?= $d['display_status']; ?>">
                                    <?php if ($d['display_status'] === 'pending'): ?>
                                        <i class="fas fa-clock me-1"></i> Pending
                                    <?php elseif ($d['display_status'] === 'checked-out'): ?>
                                        <i class="fas fa-check-circle me-1"></i> Checked Out
                                    <?php else: ?>
                                        <i class="fas fa-calendar-plus me-1"></i> Extended
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($d['display_status'] === 'pending'): ?>
                                    <button class="btn-action checkout" onclick="openCheckoutModal(<?= $d['id']; ?>, '<?= htmlspecialchars($d['guest']); ?>')">
                                        <i class="fas fa-sign-out-alt me-1"></i> Check Out
                                    </button>
                                    <button class="btn-action extend" onclick="openExtendModal(<?= $d['id']; ?>, '<?= htmlspecialchars($d['guest']); ?>')">
                                        <i class="fas fa-clock me-1"></i> Extend
                                    </button>
                                <?php elseif ($d['display_status'] === 'checked-out'): ?>
                                    <span class="status-done"><i class="fas fa-check-circle"></i> Completed</span>
                                <?php else: ?>
                                    <span class="status-extended"><i class="fas fa-calendar-plus"></i> Extended</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================ -->
<!-- CHECKOUT MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-sign-out-alt me-2" style="color: var(--departure-color);"></i> Confirm Check-out</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to check out <strong id="checkoutGuestName"></strong>?</p>
                <p class="text-muted small">This action will mark the room as available and complete the reservation.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="checkoutConfirmBtn" class="btn-danger-custom">Yes, Check Out</a>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- EXTEND MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="extendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clock me-2" style="color: var(--gold);"></i> Extend Stay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Extend stay for <strong id="extendGuestName"></strong> by <strong>1 day</strong>?</p>
                <p class="text-muted small">New check-out date will be tomorrow.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="extendConfirmBtn" class="btn-warning-custom">Yes, Extend</a>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SCRIPTS -->
<!-- ============================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ===== LIVE CLOCK =====
    function updateClock() {
        const now = new Date();
        const day = String(now.getDate()).padStart(2, '0');
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const year = now.getFullYear();
        document.getElementById('live-date').innerText = `${day}/${month}/${year}`;
        
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        document.getElementById('live-time').innerText = 
            String(hours).padStart(2, '0') + ':' + minutes + ':' + seconds + ' ' + ampm;
    }
    setInterval(updateClock, 1000);
    updateClock();
    
    // ===== FILTER FUNCTION =====
    function filterDepartures() {
        const search = document.getElementById('searchDepartures').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('#departureTableBody tr');
        let visible = 0;
        
        rows.forEach(row => {
            const searchData = row.dataset.search || '';
            const rowStatus = row.dataset.status || '';
            const match = searchData.includes(search) && (status === 'all' || rowStatus === status);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        
        document.getElementById('departureCount').textContent = visible;
    }
    
    // ===== MODAL FUNCTIONS =====
    let checkoutModalInstance = null;
    let extendModalInstance = null;
    
    function openCheckoutModal(resId, guestName) {
        document.getElementById('checkoutGuestName').textContent = guestName;
        document.getElementById('checkoutConfirmBtn').href = `?action=checkout&res_id=${resId}`;
        
        if (!checkoutModalInstance) {
            checkoutModalInstance = new bootstrap.Modal(document.getElementById('checkoutModal'));
        }
        checkoutModalInstance.show();
    }
    
    function openExtendModal(resId, guestName) {
        document.getElementById('extendGuestName').textContent = guestName;
        document.getElementById('extendConfirmBtn').href = `?action=extend&res_id=${resId}`;
        
        if (!extendModalInstance) {
            extendModalInstance = new bootstrap.Modal(document.getElementById('extendModal'));
        }
        extendModalInstance.show();
    }
    
    // ===== REFRESH FUNCTION =====
    function refreshData() {
        const btn = document.querySelector('[onclick="refreshData()"]');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            btn.disabled = true;
        }
        
        setTimeout(() => {
            location.reload();
        }, 500);
    }
</script>

</body>
</html>