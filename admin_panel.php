<?php
include 'includes/session_check.php';
include 'includes/db.php';

// 🛡️ ආරක්ෂිත වැට: Admin ට පමණක් ප්‍රවේශ විය හැක
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Get logged-in user info
$user_name = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin';
$user_role = isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'admin';

$msg = "";
$u_id = $_SESSION['user_id']; 
$u_name = $_SESSION['username'];

// ==== 💾 DATABASE BACKUP PROCESS ====
if (isset($_POST['download_backup'])) {
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) { $tables[] = $row[0]; }
    $sql_script = "";
    foreach ($tables as $table) {
        $query = "SHOW CREATE TABLE $table";
        $res = $conn->query($query); $row = $res->fetch_row();
        $sql_script .= "\n\n" . $row[1] . ";\n\n";
        $query = "SELECT * FROM $table"; $res = $conn->query($query);
        $column_count = $res->field_count;
        for ($i = 0; $i < $column_count; $i++) {
            while ($row = $res->fetch_row()) {
                $sql_script .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < $column_count; $j++) {
                    $row[$j] = $conn->real_escape_string($row[$j]);
                    if (isset($row[$j])) { $sql_script .= '"' . $row[$j] . '"'; } else { $sql_script .= 'NULL'; }
                    if ($j < ($column_count - 1)) { $sql_script .= ','; }
                }
                $sql_script .= ");\n";
            }
        }
    }
    if(!empty($sql_script)) {
        $conn->query("INSERT INTO audit_logs (user_id, username, action, description) VALUES ('$u_id', '$u_name', 'BACKUP', 'Downloaded full database backup')");
        header("Content-Type: application/octet-stream");
        header("Content-disposition: attachment; filename=\"Araliya_PMS_Backup_".date('Y-m-d').".sql\"");
        echo $sql_script; exit;
    }
}

// ==== 🏨 ROOM RATE UPDATE PROCESS ====
if (isset($_POST['update_rates'])) {
    $type_id      = intval($_POST['type_id']);
    $base_price   = floatval($_POST['base_price']);
    $season_price = floatval($_POST['season_price']);
    
    $update = $conn->query("UPDATE room_types SET base_price = $base_price, season_price = $season_price WHERE type_id = $type_id");
    if ($update) {
        $conn->query("INSERT INTO audit_logs (user_id, username, action, description) VALUES ('$u_id', '$u_name', 'ROOM_RATE', 'Updated prices for Room Type ID: $type_id')");
        $msg = "<div class='alert alert-success alert-custom'><i class='fas fa-check-circle me-2'></i> Room rates updated successfully!</div>";
    }
}

// ==== ➕ ADD NEW ROOM CATEGORY PROCESS ====
if (isset($_POST['add_category'])) {
    $type_name    = mysqli_real_escape_string($conn, $_POST['type_name']);
    $base_price   = floatval($_POST['base_price']);
    $season_price = floatval($_POST['season_price']);
    $description  = mysqli_real_escape_string($conn, $_POST['description']);
    
    $insert = $conn->query("INSERT INTO room_types (type_name, base_price, season_price, description) VALUES ('$type_name', $base_price, $season_price, '$description')");
    if ($insert) {
        $conn->query("INSERT INTO audit_logs (user_id, username, action, description) VALUES ('$u_id', '$u_name', 'ADD_CATEGORY', 'Created new room category: $type_name')");
        $msg = "<div class='alert alert-success alert-custom'><i class='fas fa-check-circle me-2'></i> New room category added successfully!</div>";
    }
}

// ==== 🔑 ADD NEW ROOM NUMBER PROCESS ====
if (isset($_POST['add_room'])) {
    $room_number = mysqli_real_escape_string($conn, $_POST['room_number']);
    $room_type   = mysqli_real_escape_string($conn, $_POST['room_type']);
    $status      = mysqli_real_escape_string($conn, $_POST['status']);
    
    $check = $conn->query("SELECT * FROM rooms WHERE room_number = '$room_number'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger alert-custom'><i class='fas fa-exclamation-triangle me-2'></i> Room Number $room_number already exists!</div>";
    } else {
        $insert = $conn->query("INSERT INTO rooms (room_number, room_type, status) VALUES ('$room_number', '$room_type', '$status')");
        if ($insert) {
            $conn->query("INSERT INTO audit_logs (user_id, username, action, description) VALUES ('$u_id', '$u_name', 'ADD_ROOM', 'Added room number: $room_number')");
            $msg = "<div class='alert alert-success alert-custom'><i class='fas fa-check-circle me-2'></i> Room $room_number added successfully!</div>";
        }
    }
}

// ==== ⚙️ UPDATE HOTEL SETTINGS PROCESS ====
if (isset($_POST['update_settings'])) {
    $h_name  = mysqli_real_escape_string($conn, $_POST['hotel_name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone   = mysqli_real_escape_string($conn, $_POST['phone']);
    $tax     = floatval($_POST['tax_percentage']);
    $service = floatval($_POST['service_charge_percentage']);
    
    $update_settings = $conn->query("UPDATE hotel_settings SET hotel_name='$h_name', address='$address', phone='$phone', tax_percentage=$tax, service_charge_percentage=$service WHERE id=1");
    if ($update_settings) {
        $conn->query("INSERT INTO audit_logs (user_id, username, action, description) VALUES ('$u_id', '$u_name', 'SETTINGS', 'Updated Hotel Profile & Taxes')");
        $msg = "<div class='alert alert-success alert-custom'><i class='fas fa-check-circle me-2'></i> Hotel settings saved successfully!</div>";
    }
}

// ==== 🎟️ ADD NEW PROMO CODE PROCESS ====
if (isset($_POST['add_promo'])) {
    $code_name = strtoupper(mysqli_real_escape_string($conn, $_POST['code_name']));
    $discount  = floatval($_POST['discount_percentage']);
    $expiry    = mysqli_real_escape_string($conn, $_POST['expiry_date']);
    
    $check_promo = $conn->query("SELECT * FROM promo_codes WHERE code_name = '$code_name'");
    if ($check_promo->num_rows > 0) {
        $msg = "<div class='alert alert-danger alert-custom'><i class='fas fa-exclamation-triangle me-2'></i> Promo code '$code_name' already exists!</div>";
    } else {
        $insert_promo = $conn->query("INSERT INTO promo_codes (code_name, discount_percentage, expiry_date) VALUES ('$code_name', $discount, '$expiry')");
        if ($insert_promo) {
            $conn->query("INSERT INTO audit_logs (user_id, username, action, description) VALUES ('$u_id', '$u_name', 'ADD_PROMO', 'Created promo code: $code_name ($discount%)')");
            $msg = "<div class='alert alert-success alert-custom'><i class='fas fa-check-circle me-2'></i> Promo code added successfully!</div>";
        }
    }
}

// දත්ත ලබා ගැනීම්
$logs_list = $conn->query("SELECT * FROM audit_logs ORDER BY log_id DESC LIMIT 30");
$room_types_list = $conn->query("SELECT * FROM room_types ORDER BY type_id ASC");
$rooms_full_list = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
$promo_list = $conn->query("SELECT * FROM promo_codes ORDER BY expiry_date ASC");

$settings_res = $conn->query("SELECT * FROM hotel_settings WHERE id=1");
$settings = $settings_res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | Araliya PMS</title>
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
        
        /* ===== MAIN CONTENT ===== */
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
        
        /* ===== STAT CARDS ===== */
        .stat-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
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
        .stat-card.blue::before { background: var(--primary-blue); }
        .stat-card.gold::before { background: var(--gold); }
        .stat-card.green::before { background: var(--arrival-color); }
        .stat-card.orange::before { background: var(--inhoused-color); }
        .stat-card.red::before { background: var(--departure-color); }
        
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
        .stat-card.blue .stat-icon { background: rgba(13, 75, 104, 0.15); color: var(--primary-blue); }
        .stat-card.gold .stat-icon { background: rgba(251, 191, 36, 0.15); color: var(--gold); }
        .stat-card.green .stat-icon { background: rgba(46, 196, 182, 0.15); color: var(--arrival-color); }
        .stat-card.orange .stat-icon { background: rgba(255, 159, 28, 0.15); color: var(--inhoused-color); }
        .stat-card.red .stat-icon { background: rgba(231, 29, 54, 0.15); color: var(--departure-color); }
        
        .stat-card .stat-number { 
            font-size: 28px; 
            font-weight: 800; 
            color: #1a1a2e; 
            line-height: 1.2;
        }
        .stat-card .stat-label { 
            font-size: 11px; 
            color: #8898aa; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        
        /* ===== SIDEBAR TABS ===== */
        .sidebar-menu { 
            background: white;
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
        }
        
        .nav-pills-custom .nav-link { 
            color: #4a5568; 
            font-weight: 500; 
            border-radius: 8px; 
            padding: 10px 14px; 
            text-align: left; 
            font-size: 13px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        .nav-pills-custom .nav-link i { 
            margin-right: 10px;
            width: 18px;
            text-align: center;
        }
        .nav-pills-custom .nav-link:hover { 
            background: #f0f7fa;
            color: var(--primary-blue);
        }
        .nav-pills-custom .nav-link.active { 
            background: rgba(13, 75, 104, 0.08);
            color: var(--primary-blue);
            border-color: rgba(13, 75, 104, 0.15);
        }
        .nav-pills-custom .nav-link.active i { color: var(--gold); }
        
        /* ===== CONTENT CARD ===== */
        .content-card { 
            background: white; 
            padding: 24px 28px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
            min-height: 500px;
        }
        
        .content-card .section-title {
            color: var(--primary-blue);
            font-size: 0.95rem;
            font-weight: 700;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f2f5;
            margin-bottom: 20px;
        }
        .content-card .section-title i { color: var(--gold); margin-right: 8px; }
        
        /* ===== FORM ===== */
        .form-label { 
            font-weight: 600; 
            color: #4a5568; 
            font-size: 0.8rem;
            margin-bottom: 4px;
        }
        .form-control, .form-select {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 0.85rem;
            color: #333;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
        }
        .form-control-sm { font-size: 0.8rem; padding: 6px 12px; }
        
        .btn-primary-custom {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .btn-primary-custom:hover {
            background: #0a3a4f;
            transform: scale(1.02);
            color: white;
        }
        .btn-primary-custom i { margin-right: 6px; }
        
        /* ===== TABLE ===== */
        .table-custom { 
            color: #333; 
            border-collapse: separate; 
            border-spacing: 0 6px; 
            margin-bottom: 0; 
            width: 100%;
        }
        .table-custom thead th { 
            background: rgba(13, 75, 104, 0.05); 
            color: var(--primary-blue); 
            font-weight: 700; 
            font-size: 0.7rem; 
            text-transform: uppercase; 
            letter-spacing: 0.8px; 
            padding: 10px 14px; 
            border: none; 
        }
        .table-custom thead th:first-child { border-radius: 10px 0 0 10px; }
        .table-custom thead th:last-child { border-radius: 0 10px 10px 0; }
        
        .table-custom tbody tr { 
            background: #f8fafc; 
            transition: all 0.2s; 
        }
        .table-custom tbody tr:hover { background: #f0f7fa; }
        .table-custom tbody td { 
            padding: 10px 14px; 
            border: none; 
            color: #4a5568; 
            vertical-align: middle; 
        }
        
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
        .alert-danger-custom {
            background: rgba(231, 29, 54, 0.1);
            color: var(--departure-color);
            border-color: rgba(231, 29, 54, 0.15);
        }
        
        /* ===== BACKUP CARD ===== */
        .backup-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            border: 2px dashed #e2e8f0;
        }
        .backup-card i { font-size: 3rem; color: var(--gold); margin-bottom: 12px; }
        .backup-card h6 { color: var(--primary-blue); font-weight: 600; }
        .backup-card p { color: #94a3b8; font-size: 0.85rem; }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .araliya-navbar { flex-direction: column; gap: 10px; }
            .nav-tabs-custom { justify-content: center; }
            .brand-title { font-size: 16px; }
            .main-content { padding: 16px; }
            .content-card { padding: 16px; }
        }
        
        @media (max-width: 768px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
            .page-header h2 { font-size: 1.4rem; }
            .sidebar-menu { margin-bottom: 16px; }
            .nav-pills-custom .nav-link { font-size: 12px; padding: 8px 10px; }
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
        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.2s; }
        
        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
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
            <li><a href="departures.php"><i class="fas fa-plane-departure"></i> Departures</a></li>
            <li><a href="inhouse_rooms.php"><i class="fas fa-user-check"></i> In-House</a></li>
            <li><a href="manage_users.php"><i class="fas fa-users-cog"></i> Users</a></li>
            <li><a href="admin_panel.php" class="active" style="color: var(--gold);"><i class="fas fa-sliders-h"></i> Admin</a></li>
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
                <i class="fas fa-sliders-h me-2"></i>Admin Control Panel
                <small>System configuration & management · <span class="badge bg-danger text-white px-2 py-1">Super Admin Mode</span></small>
            </h2>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if (!empty($msg)): ?>
        <div class="mb-3">
            <?= $msg; ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <?php
        $total_categories = $conn->query("SELECT COUNT(*) as total FROM room_types")->fetch_assoc()['total'];
        $total_rooms = $conn->query("SELECT COUNT(*) as total FROM rooms")->fetch_assoc()['total'];
        $total_promos = $conn->query("SELECT COUNT(*) as total FROM promo_codes")->fetch_assoc()['total'];
        $total_logs = $conn->query("SELECT COUNT(*) as total FROM audit_logs")->fetch_assoc()['total'];
    ?>
    <div class="stat-grid">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-tags"></i></div>
            <div class="stat-number"><?= $total_categories; ?></div>
            <div class="stat-label">Room Categories</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-icon"><i class="fas fa-bed"></i></div>
            <div class="stat-number"><?= $total_rooms; ?></div>
            <div class="stat-label">Total Rooms</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
            <div class="stat-number"><?= $total_promos; ?></div>
            <div class="stat-label">Promo Codes</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fas fa-history"></i></div>
            <div class="stat-number"><?= $total_logs; ?></div>
            <div class="stat-label">Audit Logs</div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Left: Sidebar Tabs -->
        <div class="col-lg-3">
            <div class="sidebar-menu">
                <div class="nav flex-column nav-pills-custom" id="adminTabs" role="tablist">
                    <button class="nav-link active mb-1" id="rates-tab" data-bs-toggle="pill" data-bs-target="#rates-content" type="button" role="tab">
                        <i class="fas fa-tags text-primary"></i>Room Rates
                    </button>
                    <button class="nav-link mb-1" id="cat-tab" data-bs-toggle="pill" data-bs-target="#cat-content" type="button" role="tab">
                        <i class="fas fa-folder-plus text-success"></i>Room Categories
                    </button>
                    <button class="nav-link mb-1" id="rooms-tab" data-bs-toggle="pill" data-bs-target="#rooms-content" type="button" role="tab">
                        <i class="fas fa-bed text-info"></i>Manage Rooms
                    </button>
                    <button class="nav-link mb-1" id="promo-tab" data-bs-toggle="pill" data-bs-target="#promo-content" type="button" role="tab">
                        <i class="fas fa-ticket-alt text-warning"></i>Promo Codes
                    </button>
                    <button class="nav-link mb-1" id="settings-tab" data-bs-toggle="pill" data-bs-target="#settings-content" type="button" role="tab">
                        <i class="fas fa-cogs text-secondary"></i>Hotel Settings
                    </button>
                    <button class="nav-link mb-1" id="audit-tab" data-bs-toggle="pill" data-bs-target="#audit-content" type="button" role="tab">
                        <i class="fas fa-history text-danger"></i>Audit Logs
                    </button>
                    <button class="nav-link" id="backup-tab" data-bs-toggle="pill" data-bs-target="#backup-content" type="button" role="tab">
                        <i class="fas fa-database text-warning"></i>System Backup
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Right: Tab Content -->
        <div class="col-lg-9">
            <div class="content-card tab-content" id="adminTabsContent">
                
                <!-- ===== TAB 1: ROOM RATES ===== -->
                <div class="tab-pane fade show active" id="rates-content" role="tabpanel">
                    <div class="section-title"><i class="fas fa-money-bill-wave"></i> Modify Room Rates</div>
                    <div class="row g-4">
                        <div class="col-md-5">
                            <div class="bg-light border p-4 rounded-3">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Room Category</label>
                                        <select name="type_id" class="form-select" required>
                                            <?php 
                                            $rt_copy = $conn->query("SELECT * FROM room_types");
                                            while($rt = $rt_copy->fetch_assoc()) { 
                                                echo "<option value='{$rt['type_id']}'>{$rt['type_name']}</option>"; 
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Standard Price (LKR)</label>
                                        <input type="number" step="0.01" name="base_price" class="form-control" required placeholder="0.00">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Peak Season Price (LKR)</label>
                                        <input type="number" step="0.01" name="season_price" class="form-control" required placeholder="0.00">
                                    </div>
                                    <button type="submit" name="update_rates" class="btn btn-primary-custom w-100">
                                        <i class="fas fa-save me-1"></i> Update Prices
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="table-responsive">
                                <table class="table-custom">
                                    <thead>
                                        <tr><th>Category</th><th class="text-end">Standard</th><th class="text-end">Peak Season</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $rt_list = $conn->query("SELECT * FROM room_types");
                                        while($row = $rt_list->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td class="fw-bold"><?= $row['type_name'] ?></td>
                                            <td class="text-end text-success fw-bold">LKR <?= number_format($row['base_price'], 2) ?></td>
                                            <td class="text-end text-danger fw-bold">LKR <?= number_format($row['season_price'], 2) ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ===== TAB 2: ROOM CATEGORIES ===== -->
                <div class="tab-pane fade" id="cat-content" role="tabpanel">
                    <div class="section-title"><i class="fas fa-folder-plus"></i> Add New Room Category</div>
                    <div class="row g-4">
                        <div class="col-md-5">
                            <div class="bg-light border p-4 rounded-3">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Category Name</label>
                                        <input type="text" name="type_name" class="form-control" required placeholder="Ex: Family Suite">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Standard Rate (LKR)</label>
                                        <input type="number" step="0.01" name="base_price" class="form-control" required placeholder="0.00">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Peak Season Rate (LKR)</label>
                                        <input type="number" step="0.01" name="season_price" class="form-control" required placeholder="0.00">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Short Description</label>
                                        <textarea name="description" class="form-control" rows="2" placeholder="Brief details..."></textarea>
                                    </div>
                                    <button type="submit" name="add_category" class="btn btn-primary-custom w-100" style="background: #2ec4b6;">
                                        <i class="fas fa-plus me-1"></i> Save Category
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="table-responsive">
                                <table class="table-custom">
                                    <thead>
                                        <tr><th>Category Name</th><th>Description</th><th class="text-end">Standard</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $rt_list2 = $conn->query("SELECT * FROM room_types");
                                        while($r2 = $rt_list2->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td class="fw-bold"><?= $r2['type_name'] ?></td>
                                            <td class="text-muted small"><?= $r2['description'] ?: '—' ?></td>
                                            <td class="text-end fw-bold">LKR <?= number_format($r2['base_price'], 2) ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ===== TAB 3: MANAGE ROOMS ===== -->
                <div class="tab-pane fade" id="rooms-content" role="tabpanel">
                    <div class="section-title"><i class="fas fa-bed"></i> Add & Manage Hotel Rooms</div>
                    <div class="row g-4">
                        <div class="col-md-5">
                            <div class="bg-light border p-4 rounded-3">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Room Number / ID</label>
                                        <input type="text" name="room_number" class="form-control" required placeholder="Ex: 104">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Assigned Category</label>
                                        <select name="room_type" class="form-select" required>
                                            <?php 
                                            $rt_list3 = $conn->query("SELECT * FROM room_types");
                                            while($rt3 = $rt_list3->fetch_assoc()) { 
                                                echo "<option value='{$rt3['type_name']}'>{$rt3['type_name']}</option>"; 
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Initial Status</label>
                                        <select name="status" class="form-select">
                                            <option value="available">Available</option>
                                            <option value="occupied">Occupied</option>
                                            <option value="cleaning">Cleaning</option>
                                            <option value="maintenance">Under Maintenance</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="add_room" class="btn btn-primary-custom w-100" style="background: #0ea5e9;">
                                        <i class="fas fa-plus me-1"></i> Add Room
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table-custom">
                                    <thead>
                                        <tr><th>Room No</th><th>Category</th><th>Status</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $rooms_list = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
                                        while($rm = $rooms_list->fetch_assoc()): 
                                            $st_color = 'bg-secondary';
                                            if($rm['status'] == 'available') { $st_color = 'bg-success'; }
                                            elseif($rm['status'] == 'occupied') { $st_color = 'bg-danger'; }
                                            elseif($rm['status'] == 'cleaning') { $st_color = 'bg-info text-white'; }
                                            elseif($rm['status'] == 'maintenance') { $st_color = 'bg-warning text-dark'; }
                                        ?>
                                        <tr>
                                            <td class="fw-bold text-primary"><?= $rm['room_number'] ?></td>
                                            <td><?= $rm['room_type'] ?></td>
                                            <td><span class="badge <?= $st_color ?> text-uppercase" style="font-size: 10px;"><?= $rm['status'] ?></span></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ===== TAB 4: PROMO CODES ===== -->
                <div class="tab-pane fade" id="promo-content" role="tabpanel">
                    <div class="section-title"><i class="fas fa-ticket-alt"></i> Discount Coupon & Promo Code Rules</div>
                    <div class="row g-4">
                        <div class="col-md-5">
                            <div class="bg-light border p-4 rounded-3">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Promo Code Name</label>
                                        <input type="text" name="code_name" class="form-control" required placeholder="Ex: SUMMER20" style="text-transform: uppercase;">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Discount Value (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" name="discount_percentage" class="form-control" required placeholder="0.00">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Expiration Date</label>
                                        <input type="date" name="expiry_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <button type="submit" name="add_promo" class="btn btn-primary-custom w-100" style="background: #f59e0b; color: #1a1a2e;">
                                        <i class="fas fa-plus-circle me-1"></i> Activate Promo Code
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="table-responsive">
                                <table class="table-custom">
                                    <thead>
                                        <tr><th>Code</th><th>Discount</th><th>Expires On</th><th>Status</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $promo_list_data = $conn->query("SELECT * FROM promo_codes ORDER BY expiry_date ASC");
                                        while($pm = $promo_list_data->fetch_assoc()): 
                                            $is_expired = (strtotime($pm['expiry_date']) < time());
                                            $p_badge = $is_expired ? 'bg-danger' : 'bg-success';
                                            $p_status = $is_expired ? 'EXPIRED' : 'ACTIVE';
                                        ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><i class="fas fa-ticket-alt text-warning me-1"></i> <?= $pm['code_name'] ?></td>
                                            <td class="text-success fw-bold"><?= number_format($pm['discount_percentage'], 0) ?>% Off</td>
                                            <td class="text-muted"><?= date('d M Y', strtotime($pm['expiry_date'])) ?></td>
                                            <td><span class="badge <?= $p_badge ?>"><?= $p_status ?></span></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ===== TAB 5: HOTEL SETTINGS ===== -->
                <div class="tab-pane fade" id="settings-content" role="tabpanel">
                    <div class="section-title"><i class="fas fa-hotel"></i> Hotel Profile & Tax Configuration</div>
                    <form method="POST" action="">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Hotel Name</label>
                                    <input type="text" name="hotel_name" class="form-control" value="<?= htmlspecialchars($settings['hotel_name'] ?? 'Araliya Beach Resort & Spa') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contact Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['phone'] ?? '+94 91 123 4567') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Hotel Address</label>
                                    <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($settings['address'] ?? 'Unawatuna, Galle, Sri Lanka') ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-light border p-4 rounded-3">
                                    <h6 class="fw-bold text-secondary mb-3 small text-uppercase"><i class="fas fa-percentage me-1 text-warning"></i> Tax & Service Charges</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Government Tax / VAT (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" name="tax_percentage" class="form-control" value="<?= $settings['tax_percentage'] ?? '0.00' ?>" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Hotel Service Charge (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" name="service_charge_percentage" class="form-control" value="<?= $settings['service_charge_percentage'] ?? '0.00' ?>" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <button type="submit" name="update_settings" class="btn btn-primary-custom w-100">
                                        <i class="fas fa-save me-1"></i> Save Configurations
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- ===== TAB 6: AUDIT LOGS ===== -->
                <div class="tab-pane fade" id="audit-content" role="tabpanel">
                    <div class="section-title"><i class="fas fa-history"></i> System Audit Trail</div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table-custom">
                            <thead>
                                <tr><th>Timestamp</th><th>User</th><th>Action</th><th>Description</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $logs_data = $conn->query("SELECT * FROM audit_logs ORDER BY log_id DESC LIMIT 30");
                                while($log = $logs_data->fetch_assoc()): 
                                    $b = 'bg-secondary'; 
                                    if($log['action']=='LOGIN') $b='bg-success'; 
                                    elseif($log['action']=='ROOM_RATE') $b='bg-primary'; 
                                    elseif($log['action']=='ADD_ROOM') $b='bg-info'; 
                                    elseif($log['action']=='SETTINGS') $b='bg-dark'; 
                                    elseif($log['action']=='ADD_PROMO') $b='bg-warning text-dark';
                                ?>
                                <tr>
                                    <td class="text-muted small"><?= date('d M Y H:i', strtotime($log['timestamp'])) ?></td>
                                    <td class="fw-bold">@<?= $log['username'] ?></td>
                                    <td><span class="badge <?= $b ?> text-uppercase" style="font-size: 9px;"><?= $log['action'] ?></span></td>
                                    <td><?= $log['description'] ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- ===== TAB 7: SYSTEM BACKUP ===== -->
                <div class="tab-pane fade" id="backup-content" role="tabpanel">
                    <div class="section-title"><i class="fas fa-database"></i> Database Core Backup</div>
                    <div class="backup-card">
                        <i class="fas fa-server"></i>
                        <h6>Secure Data Snapshot</h6>
                        <p class="text-muted small">Download complete SQL schemas and data rows instantly.</p>
                        <form method="POST" action="">
                            <button type="submit" name="download_backup" class="btn btn-primary-custom px-4" style="background: #1a1a2e;">
                                <i class="fas fa-download me-1"></i> Download SQL Backup
                            </button>
                        </form>
                        <p class="text-muted small mt-3"><i class="fas fa-info-circle me-1"></i> Backup includes all tables with full data</p>
                    </div>
                </div>
                
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
    
    // ===== TAB PERSISTENCE (LocalStorage) =====
    document.addEventListener("DOMContentLoaded", function() {
        var activeTab = localStorage.getItem('adminActiveTab');
        
        if (activeTab) {
            var tabTriggerEl = document.querySelector('#' + activeTab);
            if (tabTriggerEl) {
                var tab = new bootstrap.Tab(tabTriggerEl);
                tab.show();
            }
        }
        
        var tabButtons = document.querySelectorAll('#adminTabs button');
        tabButtons.forEach(function(button) {
            button.addEventListener('shown.bs.tab', function(event) {
                localStorage.setItem('adminActiveTab', event.target.id);
            });
        });
    });
</script>

</body>
</html>