<?php
// information_summary.php - Advanced In-house Information Summary
include 'includes/session_check.php';
include 'includes/db.php';

if (!$conn) die("Database Connection Failed");

$page_title = 'Information Summary';
$current_date = date('Y-m-d');

// ===== GET FILTER PARAMETERS =====
$filter_by = isset($_GET['filter_by']) ? $_GET['filter_by'] : 'all'; // all, nationality, meal, room_type
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// ===== CHECK IF CLEAR BUTTON CLICKED =====
if (isset($_GET['clear'])) {
    header('Location: information_summary.php');
    exit;
}

// ===== GET ALL IN-HOUSE GUESTS (Checked-In) =====
$sql = "SELECT r.*, rm.room_number, rm.room_type, rm.status as room_status,
               rt.type_name, rt.base_price, rt.season_price,
               u.full_name AS created_by_name,
               n.name as nationality_name
        FROM reservations r 
        LEFT JOIN rooms rm ON r.room_id = rm.room_id 
        LEFT JOIN room_types rt ON rm.room_type = rt.type_name
        LEFT JOIN users u ON r.created_by = u.user_id
        LEFT JOIN nationalities n ON r.nationality = n.name
        WHERE r.status = 'Checked-In'";

if (!empty($search)) {
    $sql .= " AND (r.guest_name LIKE '%$search%' OR r.res_no LIKE '%$search%' OR r.nationality LIKE '%$search%' OR rm.room_number LIKE '%$search%')";
}

$sql .= " ORDER BY r.check_in DESC";

$query = $conn->query($sql);

// ===== COLLECT DATA =====
$inhouse_guests = [];
$total_adults = 0;
$total_children = 0;
$total_rooms_occupied = 0;
$nationality_stats = [];
$meal_plan_stats = [];
$room_type_stats = [];
$country_stats = [];
$gender_stats = ['Male' => 0, 'Female' => 0, 'Other' => 0];
$visit_purpose_stats = [];
$booking_source_stats = [];

while($row = $query->fetch_assoc()) {
    $inhouse_guests[] = $row;
    
    // Count adults and children
    $total_adults += intval($row['adults'] ?? 1);
    $total_children += intval($row['children'] ?? 0);
    $total_rooms_occupied++;
    
    // Nationality stats
    $nat = $row['nationality'] ?? 'Unknown';
    if (!isset($nationality_stats[$nat])) {
        $nationality_stats[$nat] = ['count' => 0, 'adults' => 0, 'children' => 0];
    }
    $nationality_stats[$nat]['count']++;
    $nationality_stats[$nat]['adults'] += intval($row['adults'] ?? 1);
    $nationality_stats[$nat]['children'] += intval($row['children'] ?? 0);
    
    // Meal plan stats
    $meal = $row['meal_plan'] ?? 'Not Specified';
    if (!isset($meal_plan_stats[$meal])) {
        $meal_plan_stats[$meal] = ['count' => 0, 'adults' => 0, 'children' => 0];
    }
    $meal_plan_stats[$meal]['count']++;
    $meal_plan_stats[$meal]['adults'] += intval($row['adults'] ?? 1);
    $meal_plan_stats[$meal]['children'] += intval($row['children'] ?? 0);
    
    // Room type stats
    $room_type = $row['room_type'] ?? $row['type_name'] ?? 'Standard';
    if (!isset($room_type_stats[$room_type])) {
        $room_type_stats[$room_type] = ['count' => 0, 'adults' => 0, 'children' => 0];
    }
    $room_type_stats[$room_type]['count']++;
    $room_type_stats[$room_type]['adults'] += intval($row['adults'] ?? 1);
    $room_type_stats[$room_type]['children'] += intval($row['children'] ?? 0);
    
    // Gender stats
    $gender = $row['gender'] ?? 'Not Specified';
    if ($gender == 'Male' || $gender == 'male') {
        $gender_stats['Male']++;
    } elseif ($gender == 'Female' || $gender == 'female') {
        $gender_stats['Female']++;
    } else {
        $gender_stats['Other']++;
    }
    
    // Visit purpose stats
    $purpose = $row['visit_purpose'] ?? 'Not Specified';
    if (!isset($visit_purpose_stats[$purpose])) {
        $visit_purpose_stats[$purpose] = 0;
    }
    $visit_purpose_stats[$purpose]++;
    
    // Booking source stats
    $source = $row['booking_source'] ?? 'Not Specified';
    if (!isset($booking_source_stats[$source])) {
        $booking_source_stats[$source] = 0;
    }
    $booking_source_stats[$source]++;
}

// ===== SORT STATS =====
arsort($nationality_stats);
arsort($meal_plan_stats);
arsort($room_type_stats);
arsort($visit_purpose_stats);
arsort($booking_source_stats);

$total_guests = count($inhouse_guests);

// ===== SHOW SUCCESS/ERROR MESSAGES =====
if (isset($_GET['success']) && $_GET['success'] == 'checked_out') {
    echo '<div class="alert alert-success alert-dismissible fade show" style="border-radius:0;margin:0;padding:12px 20px; border-left: 4px solid #10b981;">
            <i class="fas fa-check-circle me-2"></i> Check-out completed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Information Summary | Araliya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary-blue: #0d4b68; --gold: #fbbf24; }
        body { 
            background: #f0f4f8; 
            font-family: 'Inter', 'Segoe UI', sans-serif; 
            font-size: 13px; 
        }
        
        .report-header {
            background: linear-gradient(135deg, #0d4b68, #1a6f8e);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .report-header h1 { font-weight: 700; font-size: 28px; }
        .report-header h1 i { color: var(--gold); }
        .report-header small { opacity: 0.8; font-size: 14px; }
        
        .back-btn {
            color: white; text-decoration: none; padding: 8px 20px;
            border-radius: 8px; background: rgba(255,255,255,0.15);
            transition: 0.3s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.25); color: white; }
        
        .stat-card {
            background: white; border-radius: 12px; padding: 20px;
            text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-blue);
            transition: 0.3s;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 20px rgba(0,0,0,0.12); }
        .stat-card .number { font-size: 32px; font-weight: 700; color: var(--primary-blue); }
        .stat-card .label { font-size: 13px; color: #6b7280; }
        .stat-card.gold { border-left-color: var(--gold); }
        .stat-card.gold .number { color: var(--gold); }
        .stat-card.success { border-left-color: #10b981; }
        .stat-card.success .number { color: #10b981; }
        .stat-card.danger { border-left-color: #ef4444; }
        .stat-card.danger .number { color: #ef4444; }
        .stat-card.purple { border-left-color: #8b5cf6; }
        .stat-card.purple .number { color: #8b5cf6; }
        
        .filter-section {
            background: white; padding: 16px 20px; border-radius: 12px;
            margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .filter-section label { font-weight: 600; font-size: 12px; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-section .form-control, .filter-section .form-select {
            border-radius: 8px; border: 1px solid #e2e8f0;
            font-size: 13px; padding: 8px 12px; background: #f8fafc;
        }
        .filter-section .form-control:focus, .filter-section .form-select:focus {
            border-color: #0d4b68; box-shadow: 0 0 0 3px rgba(13,75,104,0.1);
        }
        .btn-primary { background: #0d4b68; border: none; }
        .btn-primary:hover { background: #082f42; }
        
        .btn-clear {
            background: #6b7280; color: white; border: none;
            padding: 8px 16px; border-radius: 8px; font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-clear:hover { 
            background: #4b5563; color: white; 
            transform: translateY(-2px);
        }
        .btn-clear i { margin-right: 6px; }
        
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        
        .stat-item {
            display: flex; justify-content: space-between;
            padding: 6px 0; border-bottom: 1px solid #f1f5f9;
        }
        .stat-item:last-child { border-bottom: none; }
        .stat-item .label { color: #6b7280; }
        .stat-item .value { font-weight: 600; color: #1e293b; }
        .stat-item .badge-count {
            background: #e8f4f8; color: #0d4b68;
            padding: 1px 10px; border-radius: 12px; font-size: 11px;
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        
        .guest-card {
            background: white; border-radius: 12px; padding: 15px;
            border: 1px solid #e2e8f0; transition: 0.3s;
        }
        .guest-card:hover { border-color: var(--primary-blue); box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .guest-card .guest-name { font-weight: 700; color: var(--primary-blue); }
        .guest-card .room-badge {
            background: #e8f4f8; color: #0d4b68;
            padding: 2px 12px; border-radius: 12px; font-weight: 700; font-size: 12px;
        }
        
        .status-badge {
            padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
            display: inline-block;
        }
        .status-badge.checked-in { background: #d1fae5; color: #065f46; }
        .status-badge.checked-in::before { content: "🟢 "; }
        
        .room-status-badge {
            padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
            display: inline-block;
        }
        .room-status-badge.available { background: #d1fae5; color: #065f46; }
        .room-status-badge.available::before { content: "🟢 "; }
        .room-status-badge.occupied { background: #fee2e2; color: #991b1b; }
        .room-status-badge.occupied::before { content: "🔴 "; }
        .room-status-badge.cleaning { background: #fef3c7; color: #92400e; }
        .room-status-badge.cleaning::before { content: "🟡 "; }
        .room-status-badge.maintenance { background: #f3f4f6; color: #4b5563; }
        .room-status-badge.maintenance::before { content: "⚫ "; }
        
        .action-group {
            display: flex; flex-wrap: wrap; gap: 3px;
            justify-content: center; align-items: center;
        }
        .action-group .btn {
            padding: 3px 10px; font-size: 10px; border-radius: 5px;
            border: none; font-weight: 600; transition: all 0.2s ease;
        }
        .action-group .btn:hover { transform: translateY(-2px); }
        .action-group .btn-checkout { background: #3b82f6; color: white; }
        .action-group .btn-checkout:hover { box-shadow: 0 4px 12px rgba(59,130,246,0.4); }
        .action-group .btn-view { background: #8b5cf6; color: white; }
        .action-group .btn-view:hover { box-shadow: 0 4px 12px rgba(139,92,246,0.4); }
        .action-group .btn-edit { background: #6366f1; color: white; }
        .action-group .btn-edit:hover { box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        .action-group .btn i { font-size: 10px; margin-right: 2px; }
        
        .export-btn {
            padding: 6px 16px; border-radius: 8px; font-size: 13px;
            font-weight: 600; border: none; transition: 0.3s;
        }
        .export-btn.excel { background: #1e7e34; color: white; }
        .export-btn.excel:hover { background: #146c2e; }
        .export-btn.print { background: #6c757d; color: white; }
        .export-btn.print:hover { background: #5a6268; }
        
        .no-data {
            padding: 50px 20px; text-align: center; background: #f9fafb; border-radius: 12px;
        }
        .no-data i { font-size: 48px; color: #d1d5db; margin-bottom: 15px; }
        .no-data h4 { color: #6b7280; }
        .no-data p { color: #9ca3af; }
        
        .section-title {
            font-weight: 700; color: var(--primary-blue);
            font-size: 16px; margin-bottom: 15px;
            padding-bottom: 10px; border-bottom: 2px solid #e2e8f0;
        }
        .section-title i { color: var(--gold); margin-right: 8px; }
        
        .summary-box {
            background: #f8fafc; border-radius: 12px; padding: 15px;
            border: 1px solid #e2e8f0;
        }
        
        .progress-bar-custom {
            height: 8px; border-radius: 10px; background: #e2e8f0; overflow: hidden;
        }
        .progress-bar-custom .fill {
            height: 100%; border-radius: 10px; transition: width 1s ease;
        }
        
        @media print {
            .report-header, .filter-section, .export-btn, .back-btn, .action-group { display: none !important; }
            body { background: white; }
            .stat-card { box-shadow: none !important; border: 1px solid #ddd; }
            .card { box-shadow: none !important; border: 1px solid #ddd; }
        }
        @media(max-width:768px){
            .table-responsive { font-size: 12px; }
            .action-group .btn { padding: 2px 6px; font-size: 9px; }
        }
    </style>
</head>
<body>

<!-- ===== REPORT HEADER ===== -->
<div class="report-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-address-card me-2"></i> Information Summary</h1>
                <small><i class="far fa-calendar-alt me-1"></i> Generated: <?php echo date('Y-m-d H:i:s'); ?></small>
                <span class="badge bg-warning text-dark ms-2"><i class="fas fa-users me-1"></i> <?php echo $total_guests; ?> In-House</span>
            </div>
            <div>
                <a href="dashboard.php" class="back-btn me-2"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
                <a href="inhouse_rooms.php" class="back-btn me-2" style="background: rgba(16,185,129,0.3);">
                    <i class="fas fa-bed me-1"></i> In-House Rooms
                </a>
                <button onclick="window.print()" class="export-btn print me-1"><i class="fas fa-print"></i> Print</button>
                <button onclick="exportExcel()" class="export-btn excel"><i class="fas fa-file-excel"></i> Excel</button>
            </div>
        </div>
    </div>
</div>

<div class="container">

    <!-- ===== TOP STATS ===== -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card gold">
                <div class="number"><?php echo $total_guests; ?></div>
                <div class="label">Total In-House Guests</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="number" style="color: #10b981;"><?php echo $total_adults; ?></div>
                <div class="label">Total Adults</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #f59e0b;">
                <div class="number" style="color: #f59e0b;"><?php echo $total_children; ?></div>
                <div class="label">Total Children</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card purple">
                <div class="number" style="color: #8b5cf6;"><?php echo $total_rooms_occupied; ?></div>
                <div class="label">Rooms Occupied</div>
            </div>
        </div>
    </div>

    <!-- ===== FILTER SECTION ===== -->
    <div class="filter-section">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label><i class="fas fa-search me-1"></i> Search</label>
                <input type="text" name="search" class="form-control" placeholder="Guest name, Room #, Nationality..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="?clear=1" class="btn btn-clear" title="Clear all filters">
                        <i class="fas fa-undo"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- ===== NATIONALITY & MEAL PLAN STATS ===== -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title"><i class="fas fa-globe"></i> Nationality Breakdown</div>
                    <?php if(empty($nationality_stats)): ?>
                    <p class="text-muted text-center py-3">No data available</p>
                    <?php else: ?>
                    <div class="row">
                        <div class="col-md-7">
                            <?php 
                            $max_nat = !empty($nationality_stats) ? max(array_column($nationality_stats, 'count')) : 1;
                            foreach($nationality_stats as $nat => $data): 
                            ?>
                            <div class="stat-item">
                                <span class="label"><?php echo htmlspecialchars($nat); ?> 
                                    <span class="badge-count"><?php echo $data['count']; ?> guests</span>
                                </span>
                                <span class="value"><?php echo $data['adults']; ?> A / <?php echo $data['children']; ?> C</span>
                            </div>
                            <div class="progress-bar-custom mb-2">
                                <div class="fill" style="width: <?php echo ($data['count'] / $max_nat) * 100; ?>%; background: linear-gradient(90deg, #0d4b68, #1a6f8e);"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-5">
                            <div class="chart-container">
                                <canvas id="nationalityChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title"><i class="fas fa-utensils"></i> Meal Plan Breakdown</div>
                    <?php if(empty($meal_plan_stats)): ?>
                    <p class="text-muted text-center py-3">No data available</p>
                    <?php else: ?>
                    <div class="row">
                        <div class="col-md-7">
                            <?php 
                            $max_meal = !empty($meal_plan_stats) ? max(array_column($meal_plan_stats, 'count')) : 1;
                            foreach($meal_plan_stats as $meal => $data): 
                            ?>
                            <div class="stat-item">
                                <span class="label"><?php echo htmlspecialchars($meal); ?> 
                                    <span class="badge-count"><?php echo $data['count']; ?> guests</span>
                                </span>
                                <span class="value"><?php echo $data['adults']; ?> A / <?php echo $data['children']; ?> C</span>
                            </div>
                            <div class="progress-bar-custom mb-2">
                                <div class="fill" style="width: <?php echo ($data['count'] / $max_meal) * 100; ?>%; background: linear-gradient(90deg, #f59e0b, #fbbf24);"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-5">
                            <div class="chart-container">
                                <canvas id="mealChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== ROOM TYPE & GENDER STATS ===== -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title"><i class="fas fa-bed"></i> Room Type Breakdown</div>
                    <?php if(empty($room_type_stats)): ?>
                    <p class="text-muted text-center py-3">No data available</p>
                    <?php else: ?>
                    <div class="row">
                        <div class="col-md-7">
                            <?php 
                            $max_room = !empty($room_type_stats) ? max(array_column($room_type_stats, 'count')) : 1;
                            foreach($room_type_stats as $type => $data): 
                            ?>
                            <div class="stat-item">
                                <span class="label"><?php echo htmlspecialchars($type); ?> 
                                    <span class="badge-count"><?php echo $data['count']; ?> rooms</span>
                                </span>
                                <span class="value"><?php echo $data['adults']; ?> A / <?php echo $data['children']; ?> C</span>
                            </div>
                            <div class="progress-bar-custom mb-2">
                                <div class="fill" style="width: <?php echo ($data['count'] / $max_room) * 100; ?>%; background: linear-gradient(90deg, #8b5cf6, #a78bfa);"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-5">
                            <div class="chart-container">
                                <canvas id="roomTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title"><i class="fas fa-venus-mars"></i> Gender & Visit Purpose</div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-muted">Gender</h6>
                            <?php foreach($gender_stats as $gender => $count): ?>
                            <div class="stat-item">
                                <span class="label"><?php echo $gender; ?></span>
                                <span class="value"><?php echo $count; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-muted">Visit Purpose</h6>
                            <?php foreach($visit_purpose_stats as $purpose => $count): ?>
                            <div class="stat-item">
                                <span class="label"><?php echo htmlspecialchars($purpose); ?></span>
                                <span class="value"><?php echo $count; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== BOOKING SOURCE STATS ===== -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title"><i class="fas fa-chart-pie"></i> Booking Source</div>
                    <?php if(empty($booking_source_stats)): ?>
                    <p class="text-muted text-center py-3">No data available</p>
                    <?php else: ?>
                    <div class="row">
                        <div class="col-md-7">
                            <?php 
                            $max_source = !empty($booking_source_stats) ? max($booking_source_stats) : 1;
                            foreach($booking_source_stats as $source => $count): 
                            ?>
                            <div class="stat-item">
                                <span class="label"><?php echo htmlspecialchars($source); ?></span>
                                <span class="value"><?php echo $count; ?></span>
                            </div>
                            <div class="progress-bar-custom mb-2">
                                <div class="fill" style="width: <?php echo ($count / $max_source) * 100; ?>%; background: linear-gradient(90deg, #10b981, #34d399);"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-5">
                            <div class="chart-container">
                                <canvas id="bookingSourceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title"><i class="fas fa-users"></i> Quick Summary</div>
                    <div class="summary-box">
                        <div class="row text-center">
                            <div class="col-4">
                                <h3 class="text-primary"><?php echo $total_guests; ?></h3>
                                <small class="text-muted">Total Guests</small>
                            </div>
                            <div class="col-4">
                                <h3 class="text-success"><?php echo $total_adults; ?></h3>
                                <small class="text-muted">Adults</small>
                            </div>
                            <div class="col-4">
                                <h3 class="text-warning"><?php echo $total_children; ?></h3>
                                <small class="text-muted">Children</small>
                            </div>
                        </div>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <h5 class="text-info"><?php echo $total_rooms_occupied; ?></h5>
                                <small class="text-muted">Rooms Occupied</small>
                            </div>
                            <div class="col-6">
                                <h5 class="text-purple"><?php echo count($nationality_stats); ?></h5>
                                <small class="text-muted">Nationalities</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== GUEST LIST TABLE ===== -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><i class="fas fa-list me-2" style="color: var(--gold);"></i> In-House Guests List</h5>
                <span class="badge bg-primary"><?php echo $total_guests; ?> Guests</span>
            </div>

            <?php if(empty($inhouse_guests)): ?>
            <div class="no-data">
                <i class="fas fa-bed" style="color: #10b981;"></i>
                <h4>No In-House Guests</h4>
                <p>There are currently no checked-in guests.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="guestTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Guest Name</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Adults</th>
                            <th>Children</th>
                            <th>Nationality</th>
                            <th>Meal Plan</th>
                            <th>Rate (LKR)</th>
                            <th>Room Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($inhouse_guests as $index => $row): 
                            $room_status = $row['room_status'] ?? 'unknown';
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <a href="reservation_details.php?id=<?php echo $row['res_id']; ?>" class="guest-link">
                                    <?php echo htmlspecialchars($row['guest_name'] ?? 'N/A'); ?>
                                </a>
                            </td>
                            <td>
                                <span class="room-badge"><?php echo $row['room_number'] ?? 'N/A'; ?></span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($row['check_in'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['check_out'])); ?></td>
                            <td class="text-center fw-bold"><?php echo $row['adults'] ?? '1'; ?></td>
                            <td class="text-center"><?php echo $row['children'] ?? '0'; ?></td>
                            <td><?php echo htmlspecialchars($row['nationality'] ?? 'N/A'); ?></td>
                            <td><span class="badge bg-warning bg-opacity-10 text-warning"><?php echo htmlspecialchars($row['meal_plan'] ?? 'N/A'); ?></span></td>
                            <td><strong>LKR <?php echo number_format($row['room_rate'] ?? 0, 2); ?></strong></td>
                            <td>
                                <span class="room-status-badge <?php echo $room_status; ?>">
                                    <?php echo ucfirst($room_status); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="action-group">
                                    <a href="checkout.php?checkout_id=<?php echo $row['res_id']; ?>" 
                                       class="btn btn-checkout" 
                                       onclick="return confirm('Check-out <?php echo addslashes($row['guest_name']); ?>?')">
                                        <i class="fas fa-sign-out-alt"></i> Check Out
                                    </a>
                                    <a href="reservation_details.php?id=<?php echo $row['res_id']; ?>" class="btn btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="add_reservation.php?id=<?php echo $row['res_id']; ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ===== SCRIPTS ===== -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    <?php if(!empty($inhouse_guests)): ?>
    $('#guestTable').DataTable({
        pageLength: 25,
        order: [[3, 'desc']],
        language: {
            search: "<i class='fas fa-search me-1'></i> Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries found",
            infoFiltered: "(filtered from _MAX_ total entries)"
        },
        columnDefs: [
            { orderable: false, targets: [0, 11] }
        ]
    });
    <?php endif; ?>

    // ===== CHARTS =====
    <?php if(!empty($nationality_stats)): ?>
    // Nationality Chart
    const natLabels = <?php echo json_encode(array_keys($nationality_stats)); ?>;
    const natData = <?php echo json_encode(array_column($nationality_stats, 'count')); ?>;
    new Chart(document.getElementById('nationalityChart'), {
        type: 'doughnut',
        data: {
            labels: natLabels,
            datasets: [{
                data: natData,
                backgroundColor: ['#0d4b68', '#1a6f8e', '#2ec4b6', '#fbbf24', '#f59e0b', '#ef4444', '#8b5cf6', '#10b981', '#3b82f6', '#e71d36']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 10 } } }
            }
        }
    });
    <?php endif; ?>

    <?php if(!empty($meal_plan_stats)): ?>
    // Meal Plan Chart
    const mealLabels = <?php echo json_encode(array_keys($meal_plan_stats)); ?>;
    const mealData = <?php echo json_encode(array_column($meal_plan_stats, 'count')); ?>;
    new Chart(document.getElementById('mealChart'), {
        type: 'pie',
        data: {
            labels: mealLabels,
            datasets: [{
                data: mealData,
                backgroundColor: ['#fbbf24', '#f59e0b', '#d97706', '#fcd34d', '#fde68a']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 10 } } }
            }
        }
    });
    <?php endif; ?>

    <?php if(!empty($room_type_stats)): ?>
    // Room Type Chart
    const roomLabels = <?php echo json_encode(array_keys($room_type_stats)); ?>;
    const roomData = <?php echo json_encode(array_column($room_type_stats, 'count')); ?>;
    new Chart(document.getElementById('roomTypeChart'), {
        type: 'bar',
        data: {
            labels: roomLabels,
            datasets: [{
                label: 'Rooms',
                data: roomData,
                backgroundColor: ['#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe', '#ede9fe']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
    <?php endif; ?>

    <?php if(!empty($booking_source_stats)): ?>
    // Booking Source Chart
    const sourceLabels = <?php echo json_encode(array_keys($booking_source_stats)); ?>;
    const sourceData = <?php echo json_encode(array_values($booking_source_stats)); ?>;
    new Chart(document.getElementById('bookingSourceChart'), {
        type: 'doughnut',
        data: {
            labels: sourceLabels,
            datasets: [{
                data: sourceData,
                backgroundColor: ['#10b981', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 10 } } }
            }
        }
    });
    <?php endif; ?>
});

function exportExcel() {
    <?php if(!empty($inhouse_guests)): ?>
    const table = document.getElementById('guestTable');
    if(!table) return;
    
    let csv = [];
    const headers = [];
    const ths = table.querySelectorAll('thead th');
    ths.forEach(th => {
        let text = th.textContent.trim();
        if(text === 'Actions') return;
        headers.push(text);
    });
    csv.push(headers.join(','));
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('td');
        cells.forEach((cell, idx) => {
            if(idx === cells.length - 1) return;
            let text = cell.textContent.trim();
            if(text.includes(',')) text = `"${text}"`;
            rowData.push(text);
        });
        if(rowData.length > 0) csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'information_summary_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    <?php else: ?>
    alert('No data to export!');
    <?php endif; ?>
}
</script>
</body>
</html>