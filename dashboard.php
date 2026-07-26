<?php
include 'includes/session_check.php';
include 'includes/db.php';

// වත්මන් දිනය
$current_date = date('Y-m-d');

// ලොග් වූ පරිශීලකයාගේ නම ලබා ගැනීම
$user_name = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin';
$user_role = isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'user';

// 1. ARRIVALS COUNT
$arrivals_query = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE check_in = '$current_date' AND status = 'Pending'");
$arrivals_count = $arrivals_query->fetch_assoc()['total'];

// 2. DEPARTURES COUNT
$departures_query = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE check_out = '$current_date' AND status = 'Checked-In'");
$departures_count = $departures_query->fetch_assoc()['total'];

// 3. IN-HOUSED COUNT
$inhoused_query = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'Checked-In'");
$inhoused_count = $inhoused_query->fetch_assoc()['total'];

// 4. TOTAL ROOMS
$total_rooms_query = $conn->query("SELECT COUNT(*) as total FROM rooms");
$total_rooms = $total_rooms_query->fetch_assoc()['total'];
if($total_rooms == 0) { $total_rooms = 110; }

// 5. TODAY'S OCCUPANCY PERCENTAGE
$occupancy_percentage = ($total_rooms > 0) ? round(($inhoused_count / $total_rooms) * 100) : 0;

// 6. PENDING RESERVATIONS (Future)
$pending_future_query = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE check_in > '$current_date' AND status = 'Pending'");
$pending_future = $pending_future_query->fetch_assoc()['total'];

// ===== HOUSEKEEPING STATS =====
$vacant_count = $conn->query("SELECT COUNT(*) as c FROM rooms WHERE status = 'available'")->fetch_assoc()['c'];
$occupied_count = $conn->query("SELECT COUNT(*) as c FROM rooms WHERE status = 'occupied'")->fetch_assoc()['c'];
$cleaning_count = $conn->query("SELECT COUNT(*) as c FROM rooms WHERE status = 'cleaning'")->fetch_assoc()['c'];
$maintenance_count = $conn->query("SELECT COUNT(*) as c FROM rooms WHERE status = 'maintenance'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Araliya PMS | Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ================================================================
           CSS VARIABLES - LIGHT MODE (DEFAULT)
           ================================================================ */
        :root {
            /* Colors - Light */
            --primary-blue: #0d4b68;
            --primary-blue-dark: #082f42;
            --primary-blue-light: #1a6f8e;
            --gold: #fbbf24;
            --gold-dark: #d97706;
            --arrival-color: #2ec4b6;
            --departure-color: #e71d36;
            --inhoused-color: #ff9f1c;
            
            /* Backgrounds - Light */
            --bg-app: #eef1f6;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --surface-hover: #f1f5f9;
            --border-subtle: #e7ebf1;
            --border-hover: #d1d9e6;
            
            /* Text - Light */
            --text-primary: #17212e;
            --text-secondary: #475569;
            --text-tertiary: #94a3b8;
            --text-inverse: #ffffff;
            
            /* Shadows - Light */
            --shadow-xs: 0 1px 2px rgba(15, 23, 42, .05);
            --shadow-sm: 0 3px 10px rgba(15, 23, 42, .06);
            --shadow-md: 0 10px 28px rgba(15, 23, 42, .09);
            --shadow-lg: 0 20px 48px rgba(15, 23, 42, .14);
            --shadow-xl: 0 30px 60px rgba(15, 23, 42, .18);
            
            /* Misc */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --radius-xl: 24px;
            --font-display: 'Poppins', 'Segoe UI', sans-serif;
            --font-body: 'Inter', 'Segoe UI', sans-serif;
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Navbar */
            --navbar-bg: linear-gradient(135deg, #082f42 0%, #0d4b68 55%, #0f5a7d 100%);
            --navbar-text: #ffffff;
            --navbar-border: rgba(251, 191, 36, .35);
        }

        /* ================================================================
           DARK MODE VARIABLES
           ================================================================ */
        [data-theme="dark"] {
            --bg-app: #0f1724;
            --surface: #1a2332;
            --surface-alt: #1e293b;
            --surface-hover: #2d3a4f;
            --border-subtle: #2d3a4f;
            --border-hover: #3d4a5f;
            
            --text-primary: #e8edf5;
            --text-secondary: #b0c0d4;
            --text-tertiary: #6b7f98;
            --text-inverse: #0f1724;
            
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, .3);
            --shadow-sm: 0 3px 10px rgba(0, 0, 0, .4);
            --shadow-md: 0 10px 28px rgba(0, 0, 0, .5);
            --shadow-lg: 0 20px 48px rgba(0, 0, 0, .6);
            --shadow-xl: 0 30px 60px rgba(0, 0, 0, .7);
            
            --navbar-bg: linear-gradient(135deg, #0a1828 0%, #0d2a3d 55%, #0f3a52 100%);
            --navbar-text: #e8edf5;
            --navbar-border: rgba(251, 191, 36, .25);
        }

        /* ================================================================
           GLOBAL STYLES
           ================================================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg-app);
            font-family: var(--font-body);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            transition: background var(--transition), color var(--transition);
        }
        h1, h2, h3, h4, h5, h6 { font-family: var(--font-display); }
        a { outline-color: var(--gold); text-decoration: none; }
        ::selection { background: rgba(251, 191, 36, .35); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-app); }
        ::-webkit-scrollbar-thumb { background: var(--primary-blue); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-blue-light); }

        /* ================================================================
           TOP NAVBAR
           ================================================================ */
        .araliya-navbar {
            background: var(--navbar-bg);
            color: var(--navbar-text);
            position: sticky;
            top: 0;
            z-index: 1050;
            box-shadow: 0 4px 22px rgba(8, 47, 66, .35);
            border-bottom: 2px solid var(--navbar-border);
            transition: background var(--transition), border-color var(--transition);
        }
        .navbar-top {
            padding: 10px 24px 9px;
            flex-wrap: wrap;
            row-gap: 8px;
        }
        .navbar-right-section { margin-left: auto; }

        .navbar-tabs-row {
            padding: 8px 24px 10px;
            border-top: 1px solid rgba(255,255,255,.08);
            overflow-x: auto;
            scrollbar-width: thin;
        }
        .navbar-tabs-row::-webkit-scrollbar { height: 5px; }
        .navbar-tabs-row::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); border-radius: 10px; }

        .brand-title {
            font-family: var(--font-display);
            font-size: 21px;
            font-weight: 800;
            margin: 0;
            color: var(--gold);
            letter-spacing: 1.2px;
            transition: color var(--transition);
        }
        .brand-sub {
            font-size: 10px;
            display: block;
            color: #a8c8d8;
            letter-spacing: 0.6px;
            font-weight: 500;
        }

        /* ================================================================
           NAV TABS
           ================================================================ */
        .nav-tabs-custom {
            display: flex;
            gap: 4px;
            list-style: none;
            margin: 0;
            padding: 0;
            flex-wrap: nowrap;
        }
        .nav-tabs-custom li a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-family: var(--font-body);
            font-size: 13px;
            font-weight: 600;
            padding: 9px 14px;
            display: block;
            border-radius: 8px;
            border: 1px solid transparent;
            transition: all 0.25s ease;
            white-space: nowrap;
        }
        .nav-tabs-custom li a:hover {
            color: #fff;
            background: rgba(255,255,255,0.08);
        }
        .nav-tabs-custom li a.active {
            color: #fff;
            background: rgba(251, 191, 36, 0.16);
            border-color: rgba(251, 191, 36, 0.5);
            box-shadow: inset 0 -2px 0 var(--gold);
        }
        .nav-tabs-custom li a i { margin-right: 6px; }

        /* ================================================================
           LIVE CLOCK & DATE
           ================================================================ */
        .live-clock-badge {
            background: rgba(255,255,255,0.08);
            padding: 7px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.06);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .live-clock-badge i { color: var(--gold); }
        .live-clock-badge .date-part { opacity: 0.8; }
        .live-clock-badge .time-part { font-weight: 600; color: var(--gold); }

        /* ================================================================
           DARK MODE TOGGLE BUTTON
           ================================================================ */
        .theme-toggle {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            transition: all var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .theme-toggle:hover {
            background: rgba(255,255,255,0.18);
            transform: rotate(20deg);
        }
        .theme-toggle i { transition: transform var(--transition); }
        .theme-toggle:hover i { transform: scale(1.1); }

        /* ================================================================
           USER DROPDOWN
           ================================================================ */
        .user-dropdown {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.06);
            color: white;
            padding: 6px 12px 6px 6px;
            border-radius: 24px;
            font-size: 13px;
            font-weight: 500;
            transition: all var(--transition);
        }
        .user-dropdown:hover { background: rgba(255,255,255,0.18); }
        .user-dropdown .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--gold);
            color: var(--primary-blue-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
            margin-right: 8px;
            transition: transform var(--transition);
        }
        .user-dropdown:hover .user-avatar { transform: scale(1.05); }
        .badge-role {
            background: rgba(251, 191, 36, 0.2);
            color: var(--gold);
            font-size: 10px;
            padding: 2px 10px;
            border-radius: 12px;
            margin-left: 5px;
            font-weight: 600;
        }

        /* ================================================================
           MAIN CONTENT
           ================================================================ */
        .menu-container {
            background: var(--surface);
            padding: 26px 30px;
            min-height: 500px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-top: 22px;
            border: 1px solid var(--border-subtle);
            transition: background var(--transition), border-color var(--transition), box-shadow var(--transition);
        }

        /* ================================================================
           STAT CARDS
           ================================================================ */
        .stat-link {
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
        }
        .stat-link:hover { transform: translateY(-5px); }

        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 24px 16px 20px;
            box-shadow: var(--shadow-sm);
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-subtle);
            transition: all 0.3s ease;
            height: 100%;
        }
        .stat-card:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--border-hover);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
        }
        .stat-card.arrival::before { background: var(--arrival-color); }
        .stat-card.departure::before { background: var(--departure-color); }
        .stat-card.inhouse::before { background: var(--inhoused-color); }
        .stat-card.rooms::before { background: var(--primary-blue); }

        .stat-card .icon-circle {
            width: 52px;
            height: 52px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
            transition: transform .3s ease;
        }
        .stat-card:hover .icon-circle { transform: scale(1.08) rotate(-2deg); }
        .stat-card.arrival .icon-circle { background: rgba(46, 196, 182, 0.14); color: var(--arrival-color); }
        .stat-card.departure .icon-circle { background: rgba(231, 29, 54, 0.14); color: var(--departure-color); }
        .stat-card.inhouse .icon-circle { background: rgba(255, 159, 28, 0.14); color: var(--inhoused-color); }
        .stat-card.rooms .icon-circle { background: rgba(13, 75, 104, 0.14); color: var(--primary-blue); }

        .stat-card h5 {
            font-size: 11.5px;
            color: var(--text-tertiary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .stat-card h3 {
            font-family: var(--font-display);
            font-size: 34px;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.2;
        }
        .stat-card .stat-sub {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 8px;
            font-weight: 500;
        }

        /* ================================================================
           OCCUPANCY PANEL
           ================================================================ */
        .occupancy-meter {
            background: var(--surface-alt);
            border-radius: var(--radius-md);
            padding: 20px 24px;
            border: 1px solid var(--border-subtle);
            height: 100%;
            transition: background var(--transition), border-color var(--transition);
        }
        .occupancy-meter .panel-title {
            font-family: var(--font-display);
            font-weight: 700;
            color: var(--primary-blue);
            font-size: 14.5px;
        }
        .occupancy-donut {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }
        .occupancy-donut::after {
            content: '';
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--surface-alt);
        }
        .progress {
            height: 10px;
            border-radius: 10px;
            background: var(--border-subtle);
            overflow: visible;
        }
        .progress-bar {
            background: linear-gradient(90deg, var(--inhoused-color), var(--gold));
            border-radius: 10px;
            transition: width 1s ease;
            box-shadow: 0 0 0 3px rgba(255,159,28,.08);
        }

        /* ================================================================
           TAB PANEL STYLES
           ================================================================ */
        .tab-content-panel {
            animation: fadeInUp 0.4s ease;
        }

        .tab-section-title {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-subtle);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .tab-section-title i {
            color: var(--gold);
            font-size: 22px;
        }
        .tab-section-title small {
            font-family: var(--font-body);
            font-weight: 400;
            font-size: 13.5px;
            color: var(--text-secondary);
            margin-left: auto;
        }
        .tab-section-title .badge-count {
            background: var(--gold);
            color: var(--primary-blue-dark);
            font-weight: 700;
            font-size: 12px;
            padding: 4px 14px;
            border-radius: 20px;
        }

        /* ================================================================
           MENU CARDS
           ================================================================ */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }
        .menu-grid.priority-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
        .menu-grid.priority-grid .menu-card {
            min-height: 84px;
            padding: 15px 12px;
            border: 2px solid transparent;
        }
        .menu-grid.priority-grid .menu-card.priority {
            border-color: var(--gold);
            background: rgba(251, 191, 36, 0.08);
        }
        .menu-grid.priority-grid .menu-card.priority:hover {
            background: rgba(251, 191, 36, 0.15);
            border-color: var(--gold-dark);
        }

        .menu-card {
            background: var(--surface-alt);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 18px 14px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            min-height: 106px;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-blue);
            background: var(--surface);
        }
        .menu-card i {
            font-size: 20px;
            color: var(--primary-blue);
            margin-bottom: 4px;
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: color-mix(in srgb, currentColor 10%, transparent);
            transition: all .3s ease;
        }
        .menu-card:hover i {
            background: color-mix(in srgb, currentColor 20%, transparent);
            transform: scale(1.06);
        }
        .menu-card .menu-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .menu-card .menu-desc {
            font-size: 11px;
            color: var(--text-tertiary);
        }
        .menu-card .badge-count {
            background: var(--gold);
            color: var(--primary-blue-dark);
            font-size: 10px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 20px;
            margin-top: 4px;
        }
        .menu-card .badge-soon {
            background: var(--border-subtle);
            color: var(--text-tertiary);
            font-size: 9.5px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 20px;
            margin-top: 4px;
            letter-spacing: .3px;
            text-transform: uppercase;
        }
        .menu-card .badge-hot {
            background: #ef4444;
            color: white;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            margin-top: 4px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        .menu-card.disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }
        .menu-card.disabled:hover {
            transform: none;
            box-shadow: none;
            border-color: var(--border-subtle);
            background: var(--surface-alt);
        }
        .menu-card.disabled:hover i {
            background: color-mix(in srgb, currentColor 10%, transparent);
            transform: none;
        }

        /* ================================================================
           REPORT CATEGORIES
           ================================================================ */
        .report-category { margin-bottom: 24px; }
        .report-category-title {
            display: flex;
            align-items: center;
            gap: 9px;
            font-family: var(--font-display);
            font-weight: 700;
            color: var(--primary-blue);
            font-size: 14px;
            margin-bottom: 12px;
        }
        .cat-icon-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 4px rgba(0,0,0,.03);
        }

        /* ================================================================
           STATS SIDE BOX
           ================================================================ */
        .stats-side-box {
            background: var(--surface-alt);
            border-radius: var(--radius-md);
            padding: 20px;
            border: 1px solid var(--border-subtle);
            transition: background var(--transition), border-color var(--transition);
        }
        .stats-side-box h6 {
            font-family: var(--font-display);
        }
        .stats-side-box .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 9px 0;
            border-bottom: 1px solid var(--border-subtle);
        }
        .stats-side-box .stat-item:last-child {
            border-bottom: none;
        }
        .stats-side-box .stat-item .label {
            color: var(--text-secondary);
            font-size: 13px;
        }
        .stats-side-box .stat-item .value {
            font-weight: 700;
            font-family: var(--font-display);
            color: var(--primary-blue);
        }

        /* ================================================================
           QUICK ACTION BUTTONS
           ================================================================ */
        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            text-decoration: none;
            transition: all .25s ease;
            box-shadow: var(--shadow-xs);
        }
        .quick-action-btn:hover { transform: translateY(-3px); box-shadow: var(--shadow-sm); }
        .quick-action-btn.qa-primary { background: var(--primary-blue); color: #fff; }
        .quick-action-btn.qa-primary:hover { background: var(--primary-blue-dark); }
        .quick-action-btn.qa-success { background: #16a34a; color: #fff; }
        .quick-action-btn.qa-success:hover { background: #15803d; }
        .quick-action-btn.qa-danger { background: var(--departure-color); color: #fff; }
        .quick-action-btn.qa-danger:hover { background: #c4182e; }
        .quick-action-btn.qa-warning { background: var(--gold); color: var(--primary-blue-dark); }
        .quick-action-btn.qa-warning:hover { background: var(--gold-dark); color: #fff; }
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .quick-actions-grid .quick-action-btn { justify-content: center; }
        @media (max-width: 420px) {
            .quick-actions-grid { grid-template-columns: 1fr; }
        }

        /* ================================================================
           HOUSEKEEPING STATS CARDS
           ================================================================ */
        .hk-stat-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 15px 16px;
            text-align: center;
            border-left: 4px solid #94a3b8;
            box-shadow: var(--shadow-xs);
            transition: all 0.3s ease;
            border: 1px solid var(--border-subtle);
        }
        .hk-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-sm);
        }
        .hk-stat-card .stat-number {
            font-family: var(--font-display);
            font-size: 26px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .hk-stat-card .stat-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }
        .hk-stat-card .stat-desc {
            font-size: 10px;
            color: var(--text-tertiary);
            margin-top: 2px;
        }
        .hk-stat-card.vacant { border-left-color: #10b981; }
        .hk-stat-card.vacant .stat-number { color: #10b981; }
        .hk-stat-card.occupied { border-left-color: #ef4444; }
        .hk-stat-card.occupied .stat-number { color: #ef4444; }
        .hk-stat-card.cleaning { border-left-color: #f59e0b; }
        .hk-stat-card.cleaning .stat-number { color: #f59e0b; }
        .hk-stat-card.maintenance { border-left-color: #6b7280; }
        .hk-stat-card.maintenance .stat-number { color: #6b7280; }

        /* ================================================================
           MODAL STYLES
           ================================================================ */
        .hk-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(8, 20, 30, 0.65);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex; align-items: center; justify-content: center;
        }
        .hk-modal.hidden { display: none; }
        .hk-modal-content {
            background: var(--surface);
            padding: 30px;
            border-radius: var(--radius-lg);
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-subtle);
            transition: background var(--transition), border-color var(--transition);
        }
        .hk-modal-content select, .hk-modal-content input {
            width: 100%; padding: 10px 12px; margin-bottom: 12px;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            font-family: var(--font-body);
            background: var(--surface-alt);
            color: var(--text-primary);
            transition: border-color var(--transition), background var(--transition);
        }
        .hk-modal-content select:focus, .hk-modal-content input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(13,75,104,.12);
        }
        .hk-modal-content button {
            padding: 10px 20px; border-radius: var(--radius-sm); border: none;
            cursor: pointer; margin-right: 8px; font-weight: 600;
            transition: all .2s ease;
        }
        .hk-modal-content .btn-hk-success { background: #16a34a; color: white; }
        .hk-modal-content .btn-hk-success:hover { background: #128a3d; }
        .hk-modal-content .btn-secondary { background: var(--border-subtle); color: var(--text-secondary); }
        .hk-modal-content .btn-secondary:hover { background: var(--border-hover); }
        .hk-modal-content .modal-title-icon { color: var(--gold); margin-right: 10px; }

        /* ================================================================
           TOAST AREA
           ================================================================ */
        #toastArea {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
        #toastArea .alert {
            box-shadow: var(--shadow-md);
            margin-bottom: 10px;
            border: none;
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--text-primary);
            border-left: 4px solid var(--primary-blue);
        }
        #toastArea .alert-success { border-left-color: #10b981; }
        #toastArea .alert-danger { border-left-color: #ef4444; }

        /* ================================================================
           ANIMATIONS
           ================================================================ */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ================================================================
           RESPONSIVE
           ================================================================ */
        @media (max-width: 768px) {
            .menu-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            .menu-grid.priority-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
            .tab-section-title { font-size: 16px; }
            .navbar-top { flex-direction: column; align-items: stretch; }
            .navbar-right-section { margin-left: 0; justify-content: space-between; }
            .live-clock-badge { font-size: 11px; padding: 5px 12px; }
        }
        @media (max-width: 576px) {
            .menu-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .menu-grid.priority-grid { grid-template-columns: 1fr 1fr; }
            .menu-card { padding: 12px 8px; min-height: 90px; }
            .menu-card i { font-size: 18px; width: 40px; height: 40px; }
            .tab-section-title .badge-count { font-size: 10px; padding: 2px 10px; }
            .navbar-tabs-row { padding: 8px 12px; }
            .nav-tabs-custom li a { font-size: 12px; padding: 7px 10px; }
            .menu-container { padding: 16px; }
            .stat-card h3 { font-size: 28px; }
        }

        /* ================================================================
           TABLE STYLES
           ================================================================ */
        .table thead th {
            font-family: var(--font-display);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--text-secondary);
            border-bottom-width: 1px;
            background: var(--surface-alt);
        }
        .table tbody td {
            color: var(--text-primary);
        }
        .table {
            color: var(--text-primary);
        }
        .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: var(--surface-alt);
        }
    </style>
</head>
<body>

<!-- ============================================ -->
<!-- TOP NAVBAR -->
<!-- ============================================ -->
<div class="araliya-navbar">
    <div class="navbar-top d-flex justify-content-between align-items-center">
        <div class="me-3">
            <span class="brand-title"><i class="fas fa-hotel text-warning me-2"></i> ARALIYA</span>
            <span class="brand-sub">Beach Resort &amp; Spa Unawatuna</span>
        </div>

        <div class="d-flex align-items-center gap-2 navbar-right-section">
            <!-- Date & Time -->
            <div class="live-clock-badge">
                <i class="far fa-calendar-alt"></i>
                <span class="date-part" id="live-date"></span>
                <span class="mx-1">|</span>
                <i class="far fa-clock"></i>
                <span class="time-part" id="live-time"></span>
            </div>

            <!-- Theme Toggle Button -->
            <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>

            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="user-dropdown dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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

    <!-- Nav Tabs -->
    <div class="navbar-tabs-row">
        <ul class="nav-tabs-custom">
            <li><a href="#" class="active" data-tab="default-dashboard" onclick="switchTab(event, 'default-dashboard')"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="#" data-tab="front-desk" onclick="switchTab(event, 'front-desk')"><i class="fas fa-concierge-bell"></i> Front Desk</a></li>
            <li><a href="#" data-tab="cashier-desk" onclick="switchTab(event, 'cashier-desk')"><i class="fas fa-cash-register"></i> Cashier</a></li>
            <li><a href="#" data-tab="housekeeping" onclick="switchTab(event, 'housekeeping')"><i class="fas fa-broom"></i> Housekeeping</a></li>
            <li><a href="#" data-tab="reports" onclick="switchTab(event, 'reports')"><i class="fas fa-file-alt"></i> Reports</a></li>

            <?php if(isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                <li><a href="manage_users.php" style="color: var(--gold);"><i class="fas fa-users-cog"></i> Users</a></li>
                <li><a href="admin_panel.php" style="color: #ecd18c;"><i class="fas fa-sliders-h"></i> Admin</a></li>
            <?php else: ?>
                <li><a href="javascript:void(0);" style="opacity: 0.4; cursor: not-allowed;" title="Admin Only"><i class="fas fa-users-cog"></i> Users</a></li>
                <li><a href="javascript:void(0);" style="opacity: 0.4; cursor: not-allowed;" title="Admin Only"><i class="fas fa-sliders-h"></i> Admin</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- ============================================ -->
<!-- MAIN CONTENT -->
<!-- ============================================ -->
<div class="container-fluid">
    <div class="menu-container">

        <!-- ===== DASHBOARD TAB ===== -->
        <div id="default-dashboard" class="tab-content-panel">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <div>
                    <h4 class="fw-bold mb-0" style="color: var(--primary-blue);">
                        <i class="fas fa-home me-2" style="color: var(--gold);"></i>Hotel Status Overview
                    </h4>
                    <p class="text-muted small mb-0"><?= date('l, F j, Y') ?></p>
                </div>
                <div>
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="fas fa-sync-alt me-1 text-muted"></i>
                        <span id="last-refresh">Just now</span>
                    </span>
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="refreshData()">
                        <i class="fas fa-redo"></i> Refresh
                    </button>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <a href="arrivals.php" class="stat-link">
                        <div class="stat-card arrival">
                            <div class="icon-circle"><i class="fas fa-plane-arrival"></i></div>
                            <h5>Today's Arrivals</h5>
                            <h3><?= $arrivals_count ?></h3>
                            <div class="stat-sub"><i class="far fa-clock me-1"></i> Pending Check-in</div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <a href="departures.php" class="stat-link">
                        <div class="stat-card departure">
                            <div class="icon-circle"><i class="fas fa-plane-departure"></i></div>
                            <h5>Today's Departures</h5>
                            <h3><?= $departures_count ?></h3>
                            <div class="stat-sub"><i class="fas fa-door-open me-1"></i> Due Check-out</div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <a href="inhouse_rooms.php" class="stat-link">
                        <div class="stat-card inhouse">
                            <div class="icon-circle"><i class="fas fa-user-check"></i></div>
                            <h5>In-Housed Guests</h5>
                            <h3><?= $inhoused_count ?></h3>
                            <div class="stat-sub">
                                <i class="fas fa-percent me-1"></i> <?= $occupancy_percentage ?>% Occupied
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <a href="img/hk_get_chart.php" class="stat-link">
                        <div class="stat-card rooms">
                            <div class="icon-circle"><i class="fas fa-th"></i></div>
                            <h5>Total Rooms</h5>
                            <h3><?= $total_rooms ?></h3>
                            <div class="stat-sub">
                                <i class="fas fa-bed me-1"></i>
                                <?= $total_rooms - $inhoused_count ?> Available
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="occupancy-meter">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold panel-title" style="color: var(--primary-blue);">
                                <i class="fas fa-chart-bar me-2" style="color: var(--gold);"></i>Today's Occupancy
                            </span>
                            <div class="d-flex align-items-center gap-2">
                                <div class="occupancy-donut" style="background: conic-gradient(var(--gold) 0% <?= $occupancy_percentage ?>%, var(--border-subtle) <?= $occupancy_percentage ?>% 100%);"></div>
                                <span class="fw-bold" style="color: var(--primary-blue);"><?= $occupancy_percentage ?>%</span>
                            </div>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?= $occupancy_percentage ?>%;"
                                 aria-valuenow="<?= $occupancy_percentage ?>" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 small text-muted">
                            <span><i class="fas fa-bed text-success me-1"></i> <?= $total_rooms - $inhoused_count ?> Available</span>
                            <span><i class="fas fa-user-check text-warning me-1"></i> <?= $inhoused_count ?> Occupied</span>
                            <span><i class="fas fa-calendar-plus text-info me-1"></i> <?= $pending_future ?> Upcoming</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="occupancy-meter">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold panel-title" style="color: var(--primary-blue);">
                                <i class="fas fa-bell me-2" style="color: var(--gold);"></i>Quick Actions
                            </span>
                        </div>
                        <div class="quick-actions-grid">
                            <a href="add_reservation.php" class="quick-action-btn qa-primary"><i class="fas fa-plus"></i> New Booking</a>
                            <a href="quick_checkin.php" class="quick-action-btn qa-success"><i class="fas fa-sign-in-alt"></i> Quick Check-in</a>
                            <a href="quick_checkout.php" class="quick-action-btn qa-danger"><i class="fas fa-sign-out-alt"></i> Quick Check-out</a>
                            <a href="inhouse_rooms.php" class="quick-action-btn qa-warning"><i class="fas fa-edit"></i> Manage Rooms</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================== -->
        <!-- ===== FRONT DESK TAB ===== -->
        <!-- ========================================================== -->
        <div id="front-desk" class="tab-content-panel d-none">
            <div class="tab-section-title">
                <i class="fas fa-concierge-bell"></i> Front Desk
                <small>Manage reservations, check-ins, and guest services</small>
                <span class="badge-count"><i class="fas fa-users me-1"></i><?= $inhoused_count ?> In-House</span>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h6 class="fw-bold" style="color: var(--primary-blue); margin-bottom: 10px;">
                        <i class="fas fa-bolt me-2" style="color: var(--gold);"></i>Quick Actions
                    </h6>
                    <div class="menu-grid priority-grid">
                        <a href="quick_checkin.php" class="menu-card priority">
                            <i class="fas fa-sign-in-alt" style="color: #10b981;"></i>
                            <span class="menu-label">Quick Check-in</span>
                            <span class="menu-desc">Instant check-in</span>
                            <span class="badge-count"><?= $arrivals_count ?> Today</span>
                        </a>
                        <a href="quick_checkout.php" class="menu-card priority">
                            <i class="fas fa-sign-out-alt" style="color: #ef4444;"></i>
                            <span class="menu-label">Quick Check-out</span>
                            <span class="menu-desc">Instant check-out</span>
                            <span class="badge-count"><?= $departures_count ?> Today</span>
                        </a>
                        <a href="add_reservation.php" class="menu-card priority">
                            <i class="fas fa-plus-circle" style="color: #3b82f6;"></i>
                            <span class="menu-label">New Reservation</span>
                            <span class="menu-desc">Make a booking</span>
                        </a>
                        <a href="inhouse_rooms.php" class="menu-card priority">
                            <i class="fas fa-bed" style="color: #8b5cf6;"></i>
                            <span class="menu-label">In-House Rooms</span>
                            <span class="menu-desc">Manage in-house guests</span>
                            <span class="badge-count"><?= $inhoused_count ?> Occupied</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-8">
                    <h6 class="fw-bold" style="color: var(--primary-blue); margin-top: 10px;"><i class="fas fa-calendar-alt me-2" style="color: var(--gold);"></i>Reservation Desk</h6>
                    <div class="menu-grid">
                        <a href="arrivals.php" class="menu-card">
                            <i class="fas fa-list" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Reservations</span>
                            <span class="menu-desc">Manage all reservations</span>
                            <span class="badge-count"><?= $arrivals_count ?> Today</span>
                        </a>
                        <a href="manage_allotments.php" class="menu-card">
                            <i class="fas fa-layer-group" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Allotments</span>
                            <span class="menu-desc">Manage allotments</span>
                        </a>
                        <a href="manage_traces.php" class="menu-card">
                            <i class="fas fa-search-location" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Traces</span>
                            <span class="menu-desc">Manage traces</span>
                        </a>
                        <a href="blank_grc.php" class="menu-card">
                            <i class="fas fa-print" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">GRC Print</span>
                            <span class="menu-desc">Guest registration card</span>
                        </a>
                        <a href="#" class="menu-card disabled">
                            <i class="fas fa-history" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">History</span>
                            <span class="menu-desc">Reservation history</span>
                            <span class="badge-soon">Coming Soon</span>
                        </a>
                        <a href="#" class="menu-card disabled">
                            <i class="fas fa-globe" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">IBE List</span>
                            <span class="menu-desc">Online bookings</span>
                            <span class="badge-soon">Coming Soon</span>
                        </a>
                    </div>

                    <h6 class="fw-bold" style="color: var(--primary-blue); margin-top: 10px;"><i class="fas fa-concierge-bell me-2" style="color: var(--gold);"></i>Guest Services</h6>
                    <div class="menu-grid">
                        <a href="checkout_history.php" class="menu-card">
                            <i class="fas fa-history" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Check-out History</span>
                            <span class="menu-desc">Past check-outs</span>
                        </a>
                        <a href="#" class="menu-card disabled">
                            <i class="fas fa-sync-alt" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Update Traces</span>
                            <span class="menu-desc">Trace status update</span>
                            <span class="badge-soon">Coming Soon</span>
                        </a>
                        <a href="#" class="menu-card disabled">
                            <i class="fas fa-taxi" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Guest Transport</span>
                            <span class="menu-desc">Transport management</span>
                            <span class="badge-soon">Coming Soon</span>
                        </a>
                        <a href="#" class="menu-card disabled">
                            <i class="fas fa-utensils" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Meal Reservation</span>
                            <span class="menu-desc">Meal bookings</span>
                            <span class="badge-soon">Coming Soon</span>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-side-box">
                        <h6 class="fw-bold" style="color: var(--primary-blue);"><i class="fas fa-chart-line me-2" style="color: var(--gold);"></i>Quick Stats</h6>
                        <div class="stat-item"><span class="label">Arrivals Today</span><span class="value"><?= $arrivals_count ?></span></div>
                        <div class="stat-item"><span class="label">Departures Today</span><span class="value"><?= $departures_count ?></span></div>
                        <div class="stat-item"><span class="label">In-House Guests</span><span class="value"><?= $inhoused_count ?></span></div>
                        <div class="stat-item"><span class="label">Occupancy</span><span class="value"><?= $occupancy_percentage ?>%</span></div>
                        <div class="stat-item"><span class="label">Total Rooms</span><span class="value"><?= $total_rooms ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================== -->
        <!-- ===== CASHIER DESK TAB ===== -->
        <!-- ========================================================== -->
        <div id="cashier-desk" class="tab-content-panel d-none">
            <div class="tab-section-title">
                <i class="fas fa-cash-register"></i> Cashier Desk
                <small>Folio management, postings, and financial transactions</small>
                <span class="badge-count"><i class="fas fa-file-invoice me-1"></i><?= $inhoused_count ?> Active Folios</span>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h6 class="fw-bold" style="color: var(--primary-blue); margin-bottom: 10px;">
                        <i class="fas fa-bolt me-2" style="color: var(--gold);"></i>Quick Actions
                    </h6>
                    <div class="menu-grid priority-grid">
                        <a href="folio_management.php" class="menu-card priority">
                            <i class="fas fa-file-invoice" style="color: #0d4b68;"></i>
                            <span class="menu-label">Folio Management</span>
                            <span class="menu-desc">Guest folios & transactions</span>
                            <span class="badge-count">Active</span>
                        </a>
                        <a href="money_exchange.php" class="menu-card priority">
                            <i class="fas fa-money-bill-wave" style="color: #10b981;"></i>
                            <span class="menu-label">Money Exchange</span>
                            <span class="menu-desc">Currency exchange</span>
                        </a>
                        <a href="rebate.php" class="menu-card priority">
                            <i class="fas fa-percent" style="color: #f59e0b;"></i>
                            <span class="menu-label">Rebate</span>
                            <span class="menu-desc">Discounts & adjustments</span>
                        </a>
                        <a href="deposit_refund.php" class="menu-card priority">
                            <i class="fas fa-undo-alt" style="color: #3b82f6;"></i>
                            <span class="menu-label">Deposit Refund</span>
                            <span class="menu-desc">Refund deposits</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-8">
                    <h6 class="fw-bold" style="color: var(--primary-blue); margin-top: 10px;"><i class="fas fa-calculator me-2" style="color: var(--gold);"></i>Cashiering</h6>
                    <div class="menu-grid">
                        <a href="void_posting.php" class="menu-card">
                            <i class="fas fa-ban" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Void Posting</span>
                            <span class="menu-desc">Cancel transactions</span>
                        </a>
                        <a href="guest_service_desk.php" class="menu-card">
                            <i class="fas fa-headset" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Guest Service Desk</span>
                            <span class="menu-desc">Service requests</span>
                        </a>
                        <a href="credit_debit_note.php" class="menu-card">
                            <i class="fas fa-file-invoice-dollar" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Credit / Debit Note</span>
                            <span class="menu-desc">Credit/debit notes</span>
                        </a>
                    </div>

                    <h6 class="fw-bold" style="color: var(--primary-blue); margin-top: 10px;"><i class="fas fa-chart-line me-2" style="color: var(--gold);"></i>Profit Center</h6>
                    <div class="menu-grid">
                        <a href="postings_rooms.php" class="menu-card">
                            <i class="fas fa-bed" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Postings - Rooms</span>
                            <span class="menu-desc">Room revenue postings</span>
                        </a>
                        <a href="postings_walkin.php" class="menu-card">
                            <i class="fas fa-user-plus" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Postings - Walk-In</span>
                            <span class="menu-desc">Walk-in postings</span>
                        </a>
                    </div>

                    <h6 class="fw-bold" style="color: var(--primary-blue); margin-top: 10px;"><i class="fas fa-receipt me-2" style="color: var(--gold);"></i>Room Postings</h6>
                    <div class="menu-grid">
                        <a href="general_posting_room.php" class="menu-card">
                            <i class="fas fa-edit" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">General Posting - Room</span>
                            <span class="menu-desc">Post charges to room</span>
                        </a>
                        <a href="advance_payment.php" class="menu-card">
                            <i class="fas fa-hand-holding-usd" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Advance Payment</span>
                            <span class="menu-desc">Advance payments</span>
                        </a>
                        <a href="schedule_posting.php" class="menu-card">
                            <i class="fas fa-clock" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Schedule-Posting</span>
                            <span class="menu-desc">Recurring charges</span>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-side-box">
                        <h6 class="fw-bold" style="color: var(--primary-blue);"><i class="fas fa-info-circle me-2" style="color: var(--gold);"></i>Cashier Info</h6>
                        <div class="stat-item"><span class="label">Active Folios</span><span class="value"><?= $inhoused_count ?></span></div>
                        <div class="stat-item"><span class="label">Today's Payments</span><span class="value">LKR 0.00</span></div>
                        <div class="stat-item"><span class="label">Today's Charges</span><span class="value">LKR 0.00</span></div>
                        <div class="stat-item"><span class="label">Pending Invoices</span><span class="value">0</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================== -->
        <!-- ===== HOUSEKEEPING TAB ===== -->
        <!-- ========================================================== -->
        <div id="housekeeping" class="tab-content-panel d-none">
            <div class="tab-section-title">
                <i class="fas fa-broom"></i> Housekeeping
                <small>Room status, cleaning, laundry & staff assignments</small>
                <span class="badge-count"><i class="fas fa-building me-1"></i><?= $total_rooms ?> Rooms</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="hk-stat-card vacant">
                        <div class="stat-number">🟢 <?= $vacant_count ?></div>
                        <div class="stat-label">Vacant / Clean</div>
                        <div class="stat-desc">Ready for check-in</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="hk-stat-card occupied">
                        <div class="stat-number">🔴 <?= $occupied_count ?></div>
                        <div class="stat-label">Occupied</div>
                        <div class="stat-desc">Guests in room</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="hk-stat-card cleaning">
                        <div class="stat-number">🟡 <?= $cleaning_count ?></div>
                        <div class="stat-label">Cleaning / Dirty</div>
                        <div class="stat-desc">In cleaning process</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="hk-stat-card maintenance">
                        <div class="stat-number">⚫ <?= $maintenance_count ?></div>
                        <div class="stat-label">Maintenance</div>
                        <div class="stat-desc">Out of order</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <h6 class="fw-bold" style="color: var(--primary-blue); margin-bottom: 10px;">
                        <i class="fas fa-bolt me-2" style="color: var(--gold);"></i>Quick Actions
                    </h6>
                    <div class="menu-grid priority-grid">
                        <a href="javascript:void(0)" onclick="loadRoomChart()" class="menu-card">
                            <i class="fas fa-chart-pie" style="color: #8b5cf6;"></i>
                            <span class="menu-label">Room Status Chart</span>
                            <span class="menu-desc">View room status summary</span>
                            <span class="badge-hot">Live</span>
                        </a>
                        <a href="javascript:void(0)" onclick="openHKModal('statusModal')" class="menu-card">
                            <i class="fas fa-exchange-alt" style="color: #f59e0b;"></i>
                            <span class="menu-label">Status Change</span>
                            <span class="menu-desc">Update room status</span>
                        </a>
                        <a href="javascript:void(0)" onclick="openHKModal('historyModal')" class="menu-card">
                            <i class="fas fa-history" style="color: #3b82f6;"></i>
                            <span class="menu-label">Room History</span>
                            <span class="menu-desc">Status change history</span>
                        </a>
                        <a href="img/manage_housekeeping_staff.php" class="menu-card">
                            <i class="fas fa-users" style="color: #10b981;"></i>
                            <span class="menu-label">Staff Management</span>
                            <span class="menu-desc">Manage housekeeping staff</span>
                        </a>
                    </div>

                    <h6 class="fw-bold" style="color: var(--primary-blue); margin-top: 10px;"><i class="fas fa-tshirt me-2" style="color: var(--gold);"></i>Laundry</h6>
                    <div class="menu-grid">
                        <a href="javascript:void(0)" onclick="openHKModal('laundryWalkinModal')" class="menu-card">
                            <i class="fas fa-user-plus" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Laundry - Walk-In</span>
                            <span class="menu-desc">Walk-in laundry orders</span>
                        </a>
                        <a href="javascript:void(0)" onclick="openHKModal('laundryRoomModal')" class="menu-card">
                            <i class="fas fa-bed" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Laundry - Rooms</span>
                            <span class="menu-desc">Room laundry orders</span>
                        </a>
                        <a href="img/manage_laundry_items.php" class="menu-card">
                            <i class="fas fa-tshirt" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Laundry Items</span>
                            <span class="menu-desc">Manage items & prices</span>
                        </a>
                        <a href="img/laundry_orders.php" class="menu-card">
                            <i class="fas fa-list" style="color: var(--primary-blue);"></i>
                            <span class="menu-label">Laundry Orders</span>
                            <span class="menu-desc">View all orders</span>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-side-box">
                        <h6 class="fw-bold" style="color: var(--primary-blue);">
                            <i class="fas fa-clipboard-check me-2" style="color: var(--gold);"></i>Quick View
                        </h6>
                        <div class="stat-item"><span class="label">Total Rooms</span><span class="value"><?= $total_rooms ?></span></div>
                        <div class="stat-item"><span class="label">Vacant / Clean</span><span class="value" style="color:#10b981;"><?= $vacant_count ?></span></div>
                        <div class="stat-item"><span class="label">Occupied</span><span class="value" style="color:#ef4444;"><?= $occupied_count ?></span></div>
                        <div class="stat-item"><span class="label">Cleaning / Dirty</span><span class="value" style="color:#f59e0b;"><?= $cleaning_count ?></span></div>
                        <div class="stat-item"><span class="label">Maintenance</span><span class="value" style="color:#6b7280;"><?= $maintenance_count ?></span></div>
                        <div class="stat-item"><span class="label">Occupancy</span><span class="value"><?= round(($occupied_count / $total_rooms) * 100) ?>%</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================== -->
        <!-- ===== REPORTS TAB ===== -->
        <!-- ========================================================== -->
        <div id="reports" class="tab-content-panel d-none">
            <div class="tab-section-title">
                <i class="fas fa-file-alt"></i> Reports
                <small>View and export operational reports</small>
            </div>

            <div class="row">
                <div class="col-lg-9">

                    <div class="report-category">
                        <div class="report-category-title">
                            <span class="cat-icon-dot" style="background: var(--primary-blue);"></span>
                            Reservation Reports
                        </div>
                        <div class="menu-grid">
                            <a href="reservation_details.php" class="menu-card">
                                <i class="fas fa-list" style="color: var(--primary-blue);"></i>
                                <span class="menu-label">Reservation Details</span>
                                <span class="badge-count">View All</span>
                            </a>
                            <a href="cancelled_reservations.php" class="menu-card">
                                <i class="fas fa-times-circle" style="color: var(--departure-color);"></i>
                                <span class="menu-label">Cancelled Reservations</span>
                                <span class="badge-count">View All</span>
                            </a>
                            <a href="pending_reservations.php" class="menu-card">
                                <i class="fas fa-clock" style="color: #b45309;"></i>
                                <span class="menu-label">Pending Reservations</span>
                                <span class="badge-count"><?= $pending_future ?> Upcoming</span>
                            </a>
                        </div>
                    </div>

                    <div class="report-category">
                        <div class="report-category-title">
                            <span class="cat-icon-dot" style="background: var(--arrival-color);"></span>
                            Arrival Reports
                        </div>
                        <div class="menu-grid">
                            <a href="arrival_report.php" class="menu-card">
                                <i class="fas fa-plane-arrival" style="color: var(--arrival-color);"></i>
                                <span class="menu-label">Arrival Report</span>
                                <span class="badge-count"><?= $arrivals_count ?> Today</span>
                            </a>
                            <a href="arrived_report.php" class="menu-card">
                                <i class="fas fa-check-circle" style="color: var(--arrival-color);"></i>
                                <span class="menu-label">Arrived Report</span>
                                <span class="badge-count">History</span>
                            </a>
                        </div>
                    </div>

                    <div class="report-category">
                        <div class="report-category-title">
                            <span class="cat-icon-dot" style="background: var(--departure-color);"></span>
                            Departure Reports
                        </div>
                        <div class="menu-grid">
                            <a href="departure_report.php" class="menu-card">
                                <i class="fas fa-plane-departure" style="color: var(--departure-color);"></i>
                                <span class="menu-label">Departure Report</span>
                                <span class="badge-count"><?= $departures_count ?> Today</span>
                            </a>
                        </div>
                    </div>

                    <div class="report-category">
                        <div class="report-category-title">
                            <span class="cat-icon-dot" style="background: var(--inhoused-color);"></span>
                            In-house Reports
                        </div>
                        <div class="menu-grid">
                            <a href="information_summary.php" class="menu-card">
                                <i class="fas fa-address-card" style="color: var(--inhoused-color);"></i>
                                <span class="menu-label">Information Summary</span>
                                <span class="badge-count"><?= $inhoused_count ?> In-House</span>
                            </a>
                            <a href="inhouse_rates.php" class="menu-card">
                                <i class="fas fa-dollar-sign" style="color: var(--inhoused-color);"></i>
                                <span class="menu-label">In-house with Rates</span>
                                <span class="badge-count">Revenue View</span>
                            </a>
                        </div>
                    </div>

                </div>
                <div class="col-lg-3">
                    <div class="stats-side-box">
                        <h6 class="fw-bold" style="color: var(--primary-blue);"><i class="fas fa-chart-bar me-2" style="color: var(--gold);"></i>Quick Stats</h6>
                        <div class="stat-item"><span class="label">Total Reservations</span><span class="value"><?= $total_rooms ?></span></div>
                        <div class="stat-item"><span class="label">Active Guests</span><span class="value"><?= $inhoused_count ?></span></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ============================================ -->
<!-- HOUSKEEPING MODALS -->
<!-- ============================================ -->
<!-- Status Change Modal -->
<div id="statusModal" class="hk-modal hidden" onclick="if(event.target===this) closeHKModal('statusModal')">
    <div class="hk-modal-content" style="max-width: 420px; padding: 0; border-radius: 18px; overflow: hidden; box-shadow: 0 30px 80px rgba(0,0,0,0.3);">
        <div style="background: linear-gradient(135deg, #0d4b68, #1a6f8e); padding: 18px 24px 14px; color: white;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 26px; background: rgba(255,255,255,0.15); width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 12px;">🔄</span>
                <div>
                    <h4 style="margin: 0; font-weight: 700; font-size: 17px;">Change Status</h4>
                    <p style="margin: 2px 0 0; opacity: 0.8; font-size: 12px;">Update room availability</p>
                </div>
            </div>
            <button type="button" onclick="closeHKModal('statusModal')" style="position: absolute; top: 14px; right: 18px; background: rgba(255,255,255,0.12); border: none; color: white; font-size: 18px; cursor: pointer; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.3s;">
                ✕
            </button>
        </div>
        <div style="padding: 22px 24px 26px; background: var(--surface);">
            <form id="statusForm">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">🏨 Room</label>
                    <select name="room_id" class="form-select" required style="width: 100%; padding: 11px 14px; border: 2px solid var(--border-subtle); border-radius: 10px; font-size: 14px; background: var(--surface-alt); color: var(--text-primary); transition: 0.3s;">
                        <option value="">— Choose a room —</option>
                        <?php
                        $rooms = $conn->query("SELECT room_id, room_number, status FROM rooms ORDER BY room_number");
                        while($r = $rooms->fetch_assoc()) {
                            $icon = match($r['status']) {
                                'available' => '🟢',
                                'occupied' => '🔴',
                                'cleaning' => '🟡',
                                'maintenance' => '⚫',
                                default => '⚪'
                            };
                            $label = ucfirst($r['status']);
                            echo "<option value='{$r['room_id']}'>Room {$r['room_number']} {$icon} {$label}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">📌 Status</label>
                    <select name="new_status" class="form-select" required style="width: 100%; padding: 11px 14px; border: 2px solid var(--border-subtle); border-radius: 10px; font-size: 14px; background: var(--surface-alt); color: var(--text-primary); transition: 0.3s;">
                        <option value="">— Select new status —</option>
                        <option value="available" style="color:#10b981;">🟢 Vacant Clean</option>
                        <option value="cleaning" style="color:#f59e0b;">🟡 Dirty / Cleaning</option>
                        <option value="occupied" style="color:#ef4444;">🔴 Occupied</option>
                        <option value="maintenance" style="color:#6b7280;">⚫ Maintenance</option>
                    </select>
                </div>
                <div style="background: var(--surface-alt); padding: 10px 14px; border-radius: 10px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #0d4b68;">
                    <span style="font-size: 13px; color: var(--text-secondary); font-weight: 500;">📍 Current</span>
                    <span id="currentStatusDisplay" style="font-size: 14px; font-weight: 700; color: var(--primary-blue); background: var(--surface); padding: 2px 14px; border-radius: 20px;">
                        <span style="color: var(--text-tertiary); font-weight: 400;">Select a room</span>
                    </span>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" style="flex: 2; padding: 11px; font-size: 14px; font-weight: 700; background: linear-gradient(135deg, #0d4b68, #1a6f8e); color: white; border: none; border-radius: 10px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/><polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/></svg>
                        Update
                    </button>
                    <button type="button" onclick="closeHKModal('statusModal')" style="flex: 1; padding: 11px; font-size: 14px; font-weight: 600; background: var(--surface-alt); color: var(--text-secondary); border: 2px solid var(--border-subtle); border-radius: 10px; cursor: pointer; transition: 0.3s;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Room Chart Modal -->
<div id="chartModal" class="hk-modal hidden" onclick="if(event.target===this) closeHKModal('chartModal')">
    <div class="hk-modal-content" style="max-width: 950px; padding: 20px; max-height: 92vh; overflow-y: auto; border-radius: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid var(--border-subtle); padding-bottom: 12px;">
            <h4 class="mb-0" style="font-size: 20px; font-weight: 700; color: var(--primary-blue);">
                <i class="fas fa-th-large" style="color: var(--gold); margin-right: 10px;"></i> Room Status Chart
            </h4>
            <button class="btn btn-sm" onclick="closeHKModal('chartModal')" style="background: var(--surface-alt); border: 1px solid var(--border-subtle); padding: 6px 18px; border-radius: 8px; font-weight: 600; color: var(--text-secondary); font-size: 13px; transition: 0.3s;">
                <i class="fas fa-times me-1"></i> Close
            </button>
        </div>
        <div id="roomChartContainer">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading room status chart...</p>
            </div>
        </div>
    </div>
</div>

<!-- Room History Modal -->
<div id="historyModal" class="hk-modal hidden" onclick="if(event.target===this) closeHKModal('historyModal')">
    <div class="hk-modal-content" style="max-width:700px;">
        <h4 class="mb-3"><i class="fas fa-history modal-title-icon"></i> Room History</h4>
        <div style="max-height:400px; overflow-y:auto;">
            <table class="table table-sm table-hover">
                <thead><tr><th>Room</th><th>Old</th><th>New</th><th>Assigned</th><th>Time</th></tr></thead>
                <tbody>
                <?php
                $hist = $conn->query("SELECT r.room_number, h.old_status, h.new_status, s.full_name as assigned, h.changed_at 
                                       FROM room_status_history h 
                                       LEFT JOIN rooms r ON h.room_id = r.room_id 
                                       LEFT JOIN housekeeping_staff s ON h.assigned_staff_id = s.staff_id 
                                       ORDER BY h.changed_at DESC LIMIT 20");
                while($h = $hist->fetch_assoc()) {
                    echo "<tr><td>{$h['room_number']}</td><td>{$h['old_status']}</td><td><strong>{$h['new_status']}</strong></td><td>".($h['assigned'] ?? 'N/A')."</td><td>".date('d/m H:i', strtotime($h['changed_at']))."</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
        <button class="btn-secondary mt-2" onclick="closeHKModal('historyModal')">Close</button>
    </div>
</div>

<!-- Assign Room Boys Modal -->
<div id="assignModal" class="hk-modal hidden" onclick="if(event.target===this) closeHKModal('assignModal')">
    <div class="hk-modal-content">
        <h4 class="mb-3"><i class="fas fa-user-hard-hat modal-title-icon"></i> Assign Room Boy</h4>
        <form id="assignForm">
            <select name="room_id" class="form-select" required>
                <option value="">Select Room</option>
                <?php
                $rms = $conn->query("SELECT room_id, room_number FROM rooms ORDER BY room_number");
                while($r = $rms->fetch_assoc()) echo "<option value='{$r['room_id']}'>{$r['room_number']}</option>";
                ?>
            </select>
            <select name="staff_id" class="form-select" required>
                <option value="">Select Staff</option>
                <?php
                $stf = $conn->query("SELECT staff_id, full_name FROM housekeeping_staff WHERE is_active=1");
                while($s = $stf->fetch_assoc()) echo "<option value='{$s['staff_id']}'>{$s['full_name']}</option>";
                ?>
            </select>
            <button type="submit" class="btn-hk-success">Assign</button>
            <button type="button" class="btn-secondary" onclick="closeHKModal('assignModal')">Close</button>
        </form>
    </div>
</div>

<!-- Laundry Walk-In Modal -->
<div id="laundryWalkinModal" class="hk-modal hidden" onclick="if(event.target===this) closeHKModal('laundryWalkinModal')">
    <div class="hk-modal-content">
        <h4 class="mb-3"><i class="fas fa-user-plus modal-title-icon"></i> Walk-In Laundry</h4>
        <form id="laundryWalkinForm">
            <input type="text" name="walkin_name" class="form-control" placeholder="Customer Name" required>
            <select name="item_id" class="form-select" required>
                <option value="">Select Item</option>
                <?php
                $items = $conn->query("SELECT item_id, item_name, price FROM laundry_items");
                while($it = $items->fetch_assoc()) echo "<option value='{$it['item_id']}'>{$it['item_name']} - LKR {$it['price']}</option>";
                ?>
            </select>
            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
            <button type="submit" class="btn-hk-success">Post Laundry</button>
            <button type="button" class="btn-secondary" onclick="closeHKModal('laundryWalkinModal')">Close</button>
        </form>
    </div>
</div>

<!-- Laundry Rooms Modal -->
<div id="laundryRoomModal" class="hk-modal hidden" onclick="if(event.target===this) closeHKModal('laundryRoomModal')">
    <div class="hk-modal-content">
        <h4 class="mb-3"><i class="fas fa-door-open modal-title-icon"></i> Room Laundry</h4>
        <form id="laundryRoomForm">
            <select name="room_id" class="form-select" required>
                <option value="">Select Occupied Room</option>
                <?php
                $occ = $conn->query("SELECT r.room_id, r.room_number, res.guest_name FROM rooms r JOIN reservations res ON r.room_id = res.room_id WHERE r.status='occupied' AND res.status='Checked-In'");
                while($o = $occ->fetch_assoc()) echo "<option value='{$o['room_id']}'>{$o['room_number']} - {$o['guest_name']}</option>";
                ?>
            </select>
            <select name="item_id" class="form-select" required>
                <option value="">Select Item</option>
                <?php
                $items2 = $conn->query("SELECT item_id, item_name, price FROM laundry_items");
                while($it2 = $items2->fetch_assoc()) echo "<option value='{$it2['item_id']}'>{$it2['item_name']} - LKR {$it2['price']}</option>";
                ?>
            </select>
            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
            <button type="submit" class="btn-hk-success">Post to Room</button>
            <button type="button" class="btn-secondary" onclick="closeHKModal('laundryRoomModal')">Close</button>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- SCRIPTS -->
<!-- ============================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ================================================================
// ===== DARK/LIGHT MODE TOGGLE =====
// ================================================================
function toggleTheme() {
    const html = document.documentElement;
    const icon = document.getElementById('themeIcon');
    
    if (html.getAttribute('data-theme') === 'dark') {
        html.removeAttribute('data-theme');
        icon.className = 'fas fa-moon';
        localStorage.setItem('theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark');
        icon.className = 'fas fa-sun';
        localStorage.setItem('theme', 'dark');
    }
}

// Load saved theme
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    const icon = document.getElementById('themeIcon');
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        icon.className = 'fas fa-sun';
    } else {
        icon.className = 'fas fa-moon';
    }
});

// ================================================================
// ===== TAB SWITCHING =====
// ================================================================
const ARALIYA_ACTIVE_TAB_KEY = 'araliya_active_tab';

function switchTab(event, tabId) {
    if (event) event.preventDefault();

    document.querySelectorAll('.nav-tabs-custom a[data-tab]').forEach(a => {
        a.classList.toggle('active', a.getAttribute('data-tab') === tabId);
    });

    document.querySelectorAll('.tab-content-panel').forEach(panel => {
        panel.classList.add('d-none');
    });
    const targetPanel = document.getElementById(tabId);
    if (targetPanel) targetPanel.classList.remove('d-none');

    try { localStorage.setItem(ARALIYA_ACTIVE_TAB_KEY, tabId); } catch (e) { /* storage unavailable */ }
}

// Restore last active tab
document.addEventListener('DOMContentLoaded', function () {
    let savedTab = null;
    try { savedTab = localStorage.getItem(ARALIYA_ACTIVE_TAB_KEY); } catch (e) { /* storage unavailable */ }
    if (savedTab && document.getElementById(savedTab)) {
        switchTab(null, savedTab);
    }
});

// ================================================================
// ===== LIVE CLOCK =====
// ================================================================
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

// ================================================================
// ===== REFRESH DATA =====
// ================================================================
function refreshData() {
    const btn = document.querySelector('[onclick="refreshData()"]');
    if(!btn) return;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    btn.disabled = true;
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newArrivals = doc.querySelector('.stat-card.arrival h3');
            if (newArrivals) document.querySelector('.stat-card.arrival h3').textContent = newArrivals.textContent;
            const newDepartures = doc.querySelector('.stat-card.departure h3');
            if (newDepartures) document.querySelector('.stat-card.departure h3').textContent = newDepartures.textContent;
            const newInhouse = doc.querySelector('.stat-card.inhouse h3');
            if (newInhouse) document.querySelector('.stat-card.inhouse h3').textContent = newInhouse.textContent;
            const newOccupancy = doc.querySelector('.progress-bar');
            if (newOccupancy) {
                document.querySelector('.progress-bar').style.width = newOccupancy.style.width;
                document.querySelector('.progress-bar').setAttribute('aria-valuenow', newOccupancy.getAttribute('aria-valuenow'));
                const occText = doc.querySelector('.occupancy-meter .fw-bold:last-child');
                if (occText) document.querySelector('.occupancy-meter .fw-bold:last-child').textContent = occText.textContent;
            }
            document.getElementById('last-refresh').textContent = 'Just now';
            btn.innerHTML = '<i class="fas fa-redo"></i> Refresh';
            btn.disabled = false;
        })
        .catch(error => {
            console.error('Refresh failed:', error);
            btn.innerHTML = '<i class="fas fa-redo"></i> Retry';
            btn.disabled = false;
        });
}

// Auto-refresh every 60 seconds
setInterval(() => {
    const dashboard = document.getElementById('default-dashboard');
    if (dashboard && !dashboard.classList.contains('d-none')) refreshData();
}, 60000);

// ================================================================
// ===== HOUSKEEPING FUNCTIONS =====
// ================================================================

function openHKModal(id) {
    document.getElementById(id).classList.remove('hidden');
}
function closeHKModal(id) {
    document.getElementById(id).classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') {
        document.querySelectorAll('.hk-modal:not(.hidden)').forEach(el => el.classList.add('hidden'));
    }
});

// ================================================================
// ===== LOAD ROOM CHART =====
// ================================================================
function loadRoomChart() {
    console.log('🔄 loadRoomChart called');

    openHKModal('chartModal');

    const container = document.getElementById('roomChartContainer');
    if (!container) {
        console.error('❌ roomChartContainer not found!');
        return;
    }

    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading room chart...</p>
        </div>
    `;

    const url = '/hotel_management_structure/img/hk_get_chart.php';
    console.log('📤 Fetching:', url);

    fetch(url)
        .then(response => {
            console.log('📥 Response status:', response.status);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.text();
        })
        .then(data => {
            console.log('✅ Chart loaded, length:', data.length);
            container.innerHTML = data;

            const cards = container.querySelectorAll('.hk-room-card');
            console.log('📍 Room cards found:', cards.length);

            const popup = container.querySelector('#statusPopup');
            if (popup) {
                console.log('✅ Popup found in loaded content');
            } else {
                console.warn('⚠️ Popup not found in loaded content');
            }
        })
        .catch(error => {
            console.error('❌ Error loading chart:', error);
            container.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error loading room chart: ${error.message}
                    <br><small class="text-muted">Please check console for details.</small>
                    <button class="btn btn-sm btn-outline-danger mt-2" onclick="loadRoomChart()">
                        <i class="fas fa-redo me-1"></i> Retry
                    </button>
                </div>
            `;
        });
}

// ===== STATUS FORM SUBMIT =====
document.getElementById('statusForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const url = '/hotel_management_structure/img/hk_change_status.php';

    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Updating...';

    fetch(url, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = originalText;
            if (data.success) {
                showToast(data.message || 'Status updated successfully!', 'success');
                setTimeout(() => {
                    closeHKModal('statusModal');
                    location.reload();
                }, 1000);
            } else {
                showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.textContent = originalText;
            showToast('Error: ' + error.message, 'danger');
        });
});

// ===== ASSIGN FORM =====
document.getElementById('assignForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const url = '/hotel_management_structure/img/hk_assign_boy.php';
    fetch(url, { method: 'POST', body: formData })
        .then(response => response.text())
        .then(data => {
            showToast(data, 'success');
            closeHKModal('assignModal');
        })
        .catch(error => {
            showToast('Error: ' + error.message, 'danger');
        });
});

// ===== LAUNDRY WALK-IN =====
document.getElementById('laundryWalkinForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const url = '/hotel_management_structure/img/hk_post_laundry.php?type=walkin';
    fetch(url, { method: 'POST', body: formData })
        .then(response => response.text())
        .then(data => {
            showToast(data, 'success');
            closeHKModal('laundryWalkinModal');
            this.reset();
        })
        .catch(error => {
            showToast('Error: ' + error.message, 'danger');
        });
});

// ===== LAUNDRY ROOM =====
document.getElementById('laundryRoomForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const url = '/hotel_management_structure/img/hk_post_laundry.php?type=room';
    fetch(url, { method: 'POST', body: formData })
        .then(response => response.text())
        .then(data => {
            showToast(data, 'success');
            closeHKModal('laundryRoomModal');
            this.reset();
        })
        .catch(error => {
            showToast('Error: ' + error.message, 'danger');
        });
});

// ================================================================
// ===== TOAST FUNCTION =====
// ================================================================
function showToast(message, type = 'success') {
    let toastArea = document.getElementById('toastArea');
    if (!toastArea) {
        toastArea = document.createElement('div');
        toastArea.id = 'toastArea';
        document.body.appendChild(toastArea);
    }
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    toastArea.appendChild(toast);
    setTimeout(() => {
        if (toast.parentNode) toast.remove();
    }, 5000);
}

// ================================================================
// ===== UPDATE CURRENT STATUS DISPLAY =====
// ================================================================
document.querySelector('select[name="room_id"]')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const text = selectedOption.textContent || '';
    const match = text.match(/(🟢|🔴|🟡|⚫)\s*(available|occupied|cleaning|maintenance)/i);
    const display = document.getElementById('currentStatusDisplay');
    if (display) {
        if (match) {
            display.innerHTML = `<span style="font-weight: 700;">${match[0]}</span>`;
        } else {
            display.innerHTML = `<span style="color: var(--text-tertiary);">Select a room</span>`;
        }
    }
});

console.log('✅ Dashboard loaded successfully!');
</script>
</body>
</html>