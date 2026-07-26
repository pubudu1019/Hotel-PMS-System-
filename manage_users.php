<?php
include 'includes/session_check.php';
include 'includes/db.php';

// 🛡️ ආරක්ෂිත වැට: ලොග් වී සිටින පරිශීලකයා 'admin' නොවේ නම් ඔහුව හරවා යැවීම
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Get logged-in user info
$user_name = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin';
$user_role = isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'admin';

$msg = "";

// ==== 1. පරිශීලකයෙක් ඉවත් කිරීම (DELETE PROCESS) ====
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // තමන්වම Delete කරගැනීම වැළැක්වීම (Security)
    if ($delete_id == $_SESSION['user_id']) {
        $msg = "<div class='alert alert-danger alert-custom'><i class='fas fa-exclamation-circle me-2'></i> You cannot delete your own logged-in account!</div>";
    } else {
        $delete = $conn->query("DELETE FROM users WHERE user_id = $delete_id");
        if ($delete) {
            $msg = "<div class='alert alert-success alert-custom'><i class='fas fa-check-circle me-2'></i> User deleted successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger alert-custom'><i class='fas fa-exclamation-circle me-2'></i> Failed to delete user.</div>";
        }
    }
}

// ==== 2. අලුත් පරිශීලකයෙක් ඇතුළත් කිරීම හෝ සංස්කරණය කිරීම (SAVE / UPDATE PROCESS) ====
if (isset($_POST['save_user'])) {
    $user_id    = intval($_POST['user_id']);
    $full_name  = mysqli_real_escape_string($conn, $_POST['full_name']);
    $role       = mysqli_real_escape_string($conn, $_POST['role']);
    $username   = mysqli_real_escape_string($conn, $_POST['username']);
    $nic        = mysqli_real_escape_string($conn, $_POST['nic']);
    $phone      = mysqli_real_escape_string($conn, $_POST['phone']);
    $gender     = mysqli_real_escape_string($conn, $_POST['gender']);

    if ($user_id == 0) {
        // ---- NEW USER INSERTION ----
        $password_plain  = $_POST['password'];
        $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

        $check = $conn->query("SELECT * FROM users WHERE username = '$username'");
        if ($check->num_rows > 0) {
            $msg = "<div class='alert alert-danger alert-custom'><i class='fas fa-exclamation-circle me-2'></i> Username already exists!</div>";
        } else {
            $insert = $conn->query("INSERT INTO users (full_name, role, username, password, nic, phone, gender) 
                                    VALUES ('$full_name', '$role', '$username', '$password_hashed', '$nic', '$phone', '$gender')");
            if ($insert) {
                $msg = "<div class='alert alert-success alert-custom'><i class='fas fa-check-circle me-2'></i> User created successfully!</div>";
            } else {
                $msg = "<div class='alert alert-danger alert-custom'><i class='fas fa-exclamation-circle me-2'></i> Error creating user.</div>";
            }
        }
    } else {
        // ---- UPDATE EXISTING USER ----
        $sql = "UPDATE users SET full_name='$full_name', role='$role', username='$username', nic='$nic', phone='$phone', gender='$gender'";
        
        if (!empty($_POST['password'])) {
            $password_hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql .= ", password='$password_hashed'";
        }
        
        $sql .= " WHERE user_id = $user_id";
        
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success alert-custom'><i class='fas fa-check-circle me-2'></i> User details updated successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger alert-custom'><i class='fas fa-exclamation-circle me-2'></i> Error updating user.</div>";
        }
    }
}

// ==== 3. EDIT බටන් එක ක්ලික් කළ විට එම දත්ත Form එකට ගැනීම ====
$edit_data = [
    'user_id' => 0, 'full_name' => '', 'role' => 'receptionist', 
    'username' => '', 'nic' => '', 'phone' => '', 'gender' => 'Male'
];
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $res = $conn->query("SELECT * FROM users WHERE user_id = $edit_id");
    if ($res && $res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}

// සියලුම පරිශීලකයන් ලැයිස්තුව ලබා ගැනීම
$users_list = $conn->query("SELECT * FROM users ORDER BY user_id DESC");

// Count total users
$total_users = $users_list ? $users_list->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Araliya PMS</title>
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
        .stat-card.blue::before { background: var(--primary-blue); }
        .stat-card.gold::before { background: var(--gold); }
        .stat-card.green::before { background: var(--arrival-color); }
        .stat-card.orange::before { background: var(--inhoused-color); }
        
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
        
        /* ===== CARDS ===== */
        .card-custom {
            background: white;
            border-radius: 12px;
            padding: 24px 28px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
            height: 100%;
        }
        .card-custom .card-title {
            color: var(--primary-blue);
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f2f5;
        }
        .card-custom .card-title i { color: var(--gold); margin-right: 8px; }
        
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
        .form-control::placeholder { color: #94a3b8; }
        
        .btn-primary-custom {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 8px 24px;
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
        
        .btn-secondary-custom {
            background: #eef2f7;
            color: #4a5568;
            border: none;
            padding: 8px 24px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .btn-secondary-custom:hover {
            background: #e2e8f0;
        }
        
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
            padding: 12px 16px; 
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
            padding: 12px 16px; 
            border: none; 
            color: #4a5568; 
            vertical-align: middle; 
        }
        
        /* ===== BADGES ===== */
        .badge-role-custom {
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-role-custom.admin { background: rgba(13, 75, 104, 0.15); color: var(--primary-blue); }
        .badge-role-custom.manager { background: rgba(255, 159, 28, 0.15); color: var(--inhoused-color); }
        .badge-role-custom.receptionist { background: rgba(46, 196, 182, 0.15); color: var(--arrival-color); }
        
        /* ===== ACTION BUTTONS ===== */
        .btn-action-sm {
            padding: 4px 10px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-action-sm i { margin-right: 4px; }
        .btn-action-sm.edit { 
            background: rgba(13, 75, 104, 0.1); 
            color: var(--primary-blue);
        }
        .btn-action-sm.edit:hover { 
            background: var(--primary-blue); 
            color: white;
            transform: scale(1.05);
        }
        .btn-action-sm.delete { 
            background: rgba(231, 29, 54, 0.1); 
            color: var(--departure-color);
        }
        .btn-action-sm.delete:hover { 
            background: var(--departure-color); 
            color: white;
            transform: scale(1.05);
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
        
        /* ===== EMPTY STATE ===== */
        .empty-state {
            padding: 40px 20px;
            text-align: center;
        }
        .empty-state i {
            font-size: 3rem;
            color: #e2e8f0;
            margin-bottom: 12px;
        }
        .empty-state h6 { color: var(--primary-blue); font-weight: 600; }
        .empty-state p { color: #94a3b8; font-size: 0.9rem; }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .araliya-navbar { flex-direction: column; gap: 10px; }
            .nav-tabs-custom { justify-content: center; }
            .brand-title { font-size: 16px; }
            .main-content { padding: 16px; }
            .card-custom { padding: 16px; }
        }
        
        @media (max-width: 768px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
            .page-header h2 { font-size: 1.4rem; }
            .card-custom { padding: 12px; }
            .table-custom thead th, .table-custom tbody td { padding: 8px 10px; font-size: 0.8rem; }
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
            <li><a href="manage_users.php" class="active" style="color: var(--gold);"><i class="fas fa-users-cog"></i> Users</a></li>
            <li><a href="admin_panel.php" style="color: #ecd18c;"><i class="fas fa-sliders-h"></i> Admin</a></li>
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
                <i class="fas fa-users-cog me-2"></i>User Management
                <small>Manage system users and roles · <span id="userCount"><?= $total_users; ?></span> registered users</small>
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
    <div class="stat-grid">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-number"><?= $total_users; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
            <div class="stat-number">
                <?php 
                    $admin_count = 0;
                    if ($users_list) {
                        $users_list->data_seek(0);
                        while($u = $users_list->fetch_assoc()) {
                            if(strtolower($u['role']) === 'admin') $admin_count++;
                        }
                        $users_list->data_seek(0);
                    }
                    echo $admin_count;
                ?>
            </div>
            <div class="stat-label">Admins</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
            <div class="stat-number">
                <?php 
                    $manager_count = 0;
                    if ($users_list) {
                        $users_list->data_seek(0);
                        while($u = $users_list->fetch_assoc()) {
                            if(strtolower($u['role']) === 'manager') $manager_count++;
                        }
                        $users_list->data_seek(0);
                    }
                    echo $manager_count;
                ?>
            </div>
            <div class="stat-label">Managers</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fas fa-user"></i></div>
            <div class="stat-number">
                <?php 
                    $receptionist_count = 0;
                    if ($users_list) {
                        $users_list->data_seek(0);
                        while($u = $users_list->fetch_assoc()) {
                            if(strtolower($u['role']) === 'receptionist') $receptionist_count++;
                        }
                        $users_list->data_seek(0);
                    }
                    echo $receptionist_count;
                ?>
            </div>
            <div class="stat-label">Receptionists</div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Left: Form -->
        <div class="col-lg-4">
            <div class="card-custom">
                <div class="card-title">
                    <i class="fas <?= ($edit_data['user_id'] > 0) ? 'fa-user-edit' : 'fa-user-plus'; ?>"></i>
                    <?= ($edit_data['user_id'] > 0) ? "Modify User Details" : "Create New User" ?>
                </div>
                
                <form method="POST" action="manage_users.php">
                    <input type="hidden" name="user_id" value="<?= $edit_data['user_id'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required 
                               value="<?= htmlspecialchars($edit_data['full_name']) ?>" 
                               placeholder="Ex: John Doe">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">NIC / Passport No <span class="text-danger">*</span></label>
                        <input type="text" name="nic" class="form-control" required 
                               value="<?= htmlspecialchars($edit_data['nic']) ?>" 
                               placeholder="Ex: 199912345678">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" required 
                               value="<?= htmlspecialchars($edit_data['phone']) ?>" 
                               placeholder="Ex: 0771234567">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="Male" <?= ($edit_data['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($edit_data['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">User Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="receptionist" <?= ($edit_data['role'] == 'receptionist') ? 'selected' : '' ?>>Receptionist</option>
                            <option value="manager" <?= ($edit_data['role'] == 'manager') ? 'selected' : '' ?>>Manager</option>
                            <option value="admin" <?= ($edit_data['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required 
                               value="<?= htmlspecialchars($edit_data['username']) ?>" 
                               placeholder="Ex: john99" autocomplete="off">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            Password 
                            <?php if ($edit_data['user_id'] > 0): ?>
                                <span class="text-muted" style="font-size: 11px; font-weight: 400;">(Leave blank if unchanged)</span>
                            <?php endif; ?>
                        </label>
                        <input type="password" name="password" class="form-control" 
                               <?= ($edit_data['user_id'] == 0) ? 'required' : '' ?> 
                               placeholder="******" autocomplete="new-password">
                    </div>
                    
                    <button type="submit" name="save_user" class="btn btn-primary-custom w-100">
                        <i class="fas <?= ($edit_data['user_id'] > 0) ? 'fa-edit' : 'fa-save'; ?> me-1"></i> 
                        <?= ($edit_data['user_id'] > 0) ? "UPDATE USER" : "CREATE USER" ?>
                    </button>
                    
                    <?php if($edit_data['user_id'] > 0): ?>
                        <a href="manage_users.php" class="btn btn-secondary-custom w-100 mt-2">
                            <i class="fas fa-times me-1"></i> Cancel Edit
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Right: User List -->
        <div class="col-lg-8">
            <div class="card-custom">
                <div class="card-title">
                    <i class="fas fa-list"></i>
                    Registered System Users
                    <span class="badge bg-light text-dark ms-2"><?= $total_users; ?></span>
                </div>
                
                <?php if ($users_list && $users_list->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Name / Contact</th>
                                    <th>Username</th>
                                    <th>NIC & Gender</th>
                                    <th>Role</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $users_list->data_seek(0);
                                while($user = $users_list->fetch_assoc()): 
                                    $role_class = 'receptionist';
                                    if(strtolower($user['role']) === 'admin') $role_class = 'admin';
                                    elseif(strtolower($user['role']) === 'manager') $role_class = 'manager';
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($user['full_name']) ?></div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            <i class="fas fa-phone-alt me-1"></i> <?= htmlspecialchars($user['phone'] ?? '-') ?>
                                        </div>
                                    </td>
                                    <td class="text-secondary">@<?= htmlspecialchars($user['username']) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($user['nic'] ?? '-') ?></div>
                                        <div class="text-muted small" style="font-size: 11px;">
                                            <i class="fas fa-<?= ($user['gender'] == 'Male') ? 'mars' : 'venus'; ?> me-1"></i>
                                            <?= htmlspecialchars($user['gender'] ?? '-') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-role-custom <?= $role_class ?>">
                                            <i class="fas <?= ($role_class == 'admin') ? 'fa-user-shield' : (($role_class == 'manager') ? 'fa-user-tie' : 'fa-user'); ?> me-1"></i>
                                            <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="manage_users.php?edit_id=<?= $user['user_id'] ?>" class="btn-action-sm edit" title="Edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <a href="manage_users.php?delete_id=<?= $user['user_id'] ?>" 
                                               class="btn-action-sm delete" 
                                               onclick="return confirm('Are you sure you want to delete this user?');" 
                                               title="Delete">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small" title="Cannot delete your own account">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h6>No Users Registered</h6>
                        <p>Start by creating your first system user using the form.</p>
                    </div>
                <?php endif; ?>
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
</script>

</body>
</html>