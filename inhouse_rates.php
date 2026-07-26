<?php
// inhouse_rates.php - In-house Guests with Rates Report
include 'includes/session_check.php';
include 'includes/db.php';

if (!$conn) die("Database Connection Failed");

$page_title = 'In-house with Rates';
$current_date = date('Y-m-d');

// ===== GET FILTER PARAMETERS =====
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'room_number'; // room_number, rate, guest_name, check_in
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$min_rate = isset($_GET['min_rate']) ? floatval($_GET['min_rate']) : 0;
$max_rate = isset($_GET['max_rate']) ? floatval($_GET['max_rate']) : 999999;

// ===== CHECK IF CLEAR BUTTON CLICKED =====
if (isset($_GET['clear'])) {
    header('Location: inhouse_rates.php');
    exit;
}

// ===== GET ALL IN-HOUSE GUESTS (Checked-In) WITH RATES =====
$sql = "SELECT r.*, rm.room_number, rm.room_type, rm.status as room_status,
               rt.type_name, rt.base_price, rt.season_price,
               u.full_name AS created_by_name,
               n.name as nationality_name,
               (r.room_rate) as current_rate,
               (r.room_rate * r.num_of_nights) as total_room_charge,
               DATEDIFF(r.check_out, r.check_in) as stay_days
        FROM reservations r 
        LEFT JOIN rooms rm ON r.room_id = rm.room_id 
        LEFT JOIN room_types rt ON rm.room_type = rt.type_name
        LEFT JOIN users u ON r.created_by = u.user_id
        LEFT JOIN nationalities n ON r.nationality = n.name
        WHERE r.status = 'Checked-In'";

if (!empty($search)) {
    $sql .= " AND (r.guest_name LIKE '%$search%' OR r.res_no LIKE '%$search%' OR r.nationality LIKE '%$search%' OR rm.room_number LIKE '%$search%')";
}

if ($min_rate > 0) {
    $sql .= " AND r.room_rate >= $min_rate";
}
if ($max_rate < 999999) {
    $sql .= " AND r.room_rate <= $max_rate";
}

// Sorting
$allowed_sort = ['room_number', 'rate', 'guest_name', 'check_in', 'total_room_charge', 'stay_days'];
$sort_by = in_array($sort_by, $allowed_sort) ? $sort_by : 'room_number';
$sort_order = ($sort_order == 'DESC') ? 'DESC' : 'ASC';
$sql .= " ORDER BY $sort_by $sort_order";

$query = $conn->query($sql);

// ===== COLLECT DATA =====
$inhouse_guests = [];
$total_revenue = 0;
$total_rooms_occupied = 0;
$total_adults = 0;
$total_children = 0;
$total_stay_days = 0;
$rate_ranges = [
    '0-5000' => 0,
    '5001-10000' => 0,
    '10001-20000' => 0,
    '20001-50000' => 0,
    '50001+' => 0
];

while($row = $query->fetch_assoc()) {
    $inhouse_guests[] = $row;
    $total_revenue += floatval($row['room_rate'] ?? 0);
    $total_rooms_occupied++;
    $total_adults += intval($row['adults'] ?? 1);
    $total_children += intval($row['children'] ?? 0);
    $total_stay_days += intval($row['stay_days'] ?? 1);
    
    // Rate ranges
    $rate = floatval($row['room_rate'] ?? 0);
    if ($rate <= 5000) $rate_ranges['0-5000']++;
    elseif ($rate <= 10000) $rate_ranges['5001-10000']++;
    elseif ($rate <= 20000) $rate_ranges['10001-20000']++;
    elseif ($rate <= 50000) $rate_ranges['20001-50000']++;
    else $rate_ranges['50001+']++;
}

// ===== ROOM TYPE SUMMARY =====
$room_type_summary = [];
$room_type_query = $conn->query("
    SELECT rm.room_type, COUNT(*) as count, SUM(r.room_rate) as total_revenue, AVG(r.room_rate) as avg_rate
    FROM reservations r 
    LEFT JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.status = 'Checked-In'
    GROUP BY rm.room_type
    ORDER BY count DESC
");
while($row = $room_type_query->fetch_assoc()) {
    $room_type_summary[] = $row;
}

// ===== NATIONALITY SUMMARY WITH RATES =====
$nationality_summary = [];
$nat_query = $conn->query("
    SELECT r.nationality, COUNT(*) as count, SUM(r.room_rate) as total_revenue, AVG(r.room_rate) as avg_rate
    FROM reservations r 
    WHERE r.status = 'Checked-In'
    GROUP BY r.nationality
    ORDER BY count DESC
");
while($row = $nat_query->fetch_assoc()) {
    $nationality_summary[] = $row;
}

// ===== HIGHEST RATE GUESTS =====
$highest_rate_query = $conn->query("
    SELECT r.guest_name, rm.room_number, r.room_rate, r.nationality
    FROM reservations r 
    LEFT JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.status = 'Checked-In'
    ORDER BY r.room_rate DESC
    LIMIT 5
");
$highest_rate_guests = [];
while($row = $highest_rate_query->fetch_assoc()) {
    $highest_rate_guests[] = $row;
}

// ===== LOWEST RATE GUESTS =====
$lowest_rate_query = $conn->query("
    SELECT r.guest_name, rm.room_number, r.room_rate, r.nationality
    FROM reservations r 
    LEFT JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.status = 'Checked-In' AND r.room_rate > 0
    ORDER BY r.room_rate ASC
    LIMIT 5
");
$lowest_rate_guests = [];
while($row = $lowest_rate_query->fetch_assoc()) {
    $lowest_rate_guests[] = $row;
}

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
    <title>In-house with Rates | Araliya</title>
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
        
        .room-badge {
            background: #e8f4f8; color: #0d4b68;
            padding: 2px 12px; border-radius: 12px; font-weight: 700; font-size: 12px;
            display: inline-block;
        }
        
        .rate-badge {
            padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;
            display: inline-block;
        }
        .rate-badge.high { background: #fee2e2; color: #991b1b; }
        .rate-badge.medium { background: #fef3c7; color: #92400e; }
        .rate-badge.low { background: #d1fae5; color: #065f46; }
        
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
        
        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
        }
        
        .rate-highlight {
            font-size: 20px; font-weight: 800;
        }
        .rate-highlight.high { color: #ef4444; }
        .rate-highlight.medium { color: #f59e0b; }
        .rate-highlight.low { color: #10b981; }
        
        .guest-card {
            background: white; border-radius: 12px; padding: 12px 15px;
            border: 1px solid #e2e8f0; transition: 0.3s;
            margin-bottom: 8px;
        }
        .guest-card:hover { border-color: var(--primary-blue); box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        
        .sort-link {
            color: var(--primary-blue); text-decoration: none;
            font-weight: 600;
        }
        .sort-link:hover { color: var(--gold); }
        .sort-link i { font-size: 11px; }
        
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
                <h1><i class="fas fa-dollar-sign me-2"></i> In-house with Rates</h1>
                <small><i class="far fa-calendar-alt me-1"></i> Generated: <?php echo date('Y-m-d H:i:s'); ?></small>
                <span class="badge bg-warning text-dark ms-2"><i class="fas fa-users me-1"></i> <?php echo $total_guests; ?> In-House</span>
            </div>
            <div>
                <a href="dashboard.php" class="back-btn me-2"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
                <a href="information_summary.php" class="back-btn me-2" style="background: rgba(139,92,246,0.3);">
                    <i class="fas fa-address-card me-1"></i> Summary
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
                <div class="label">Total In-House</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="number" style="color: #10b981;">LKR <?php echo number_format($total_revenue, 0); ?></div>
                <div class="label">Daily Revenue</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card purple">
                <div class="number" style="color: #8b5cf6;"><?php echo $total_rooms_occupied; ?></div>
                <div class="label">Rooms Occupied</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #f59e0b;">
                <div class="number" style="color: #f59e0b;"><?php echo round($total_revenue / ($total_guests > 0 ? $total_guests : 1), 0); ?></div>
                <div class="label">Avg. Rate per Guest</div>
            </div>
        </div>
    </div>

    <!-- ===== FILTER SECTION ===== -->
    <div class="filter-section">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label><i class="fas fa-search me-1"></i> Search</label>
                <input type="text" name="search" class="form-control" placeholder="Guest, Room, Nationality..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <label><i class="fas fa-dollar-sign me-1"></i> Min Rate</label>
                <input type="number" name="min_rate" class="form-control" placeholder="0" value="<?php echo $min_rate > 0 ? $min_rate : ''; ?>">
            </div>
            <div class="col-md-2">
                <label><i class="fas fa-dollar-sign me-1"></i> Max Rate</label>
                <input type="number" name="max_rate" class="form-control" placeholder="999999" value="<?php echo $max_rate < 999999 ? $max_rate : ''; ?>">
            </div>
            <div class="col-md-2">
                <label><i class="fas fa-sort me-1"></i> Sort By</label>
                <select name="sort_by" class="form-select">
                    <option value="room_number" <?php echo $sort_by == 'room_number' ? 'selected' : ''; ?>>Room #</option>
                    <option value="rate" <?php echo $sort_by == 'rate' ? 'selected' : ''; ?>>Rate</option>
                    <option value="guest_name" <?php echo $sort_by == 'guest_name' ? 'selected' : ''; ?>>Guest Name</option>
                    <option value="check_in" <?php echo $sort_by == 'check_in' ? 'selected' : ''; ?>>Check In</option>
                    <option value="total_room_charge" <?php echo $sort_by == 'total_room_charge' ? 'selected' : ''; ?>>Total Charge</option>
                    <option value="stay_days" <?php echo $sort_by == 'stay_days' ? 'selected' : ''; ?>>Stay Days</option>
                </select>
            </div>
            <div class="col-md-1">
                <label><i class="fas fa-arrow-up me-1"></i> Order</label>
                <select name="sort_order" class="form-select">
                    <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>ASC</option>
                    <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>DESC</option>
                </select>
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="?clear=1" class="btn btn-clear" title="Clear all filters">
                        <i class="fas fa-undo"></i> Clear
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Active Filters -->
        <?php if(!empty($search) || $min_rate > 0 || $max_rate < 999999 || $sort_by != 'room_number'): ?>
        <div class="mt-2 pt-2 border-top">
            <small class="text-muted">
                <i class="fas fa-filter me-1"></i> Active Filters:
                <?php if(!empty($search)): ?>
                <span class="badge bg-light text-dark border ms-1">Search: <?php echo htmlspecialchars($search); ?></span>
                <?php endif; ?>
                <?php if($min_rate > 0): ?>
                <span class="badge bg-light text-dark border ms-1">Min: LKR <?php echo number_format($min_rate, 0); ?></span>
                <?php endif; ?>
                <?php if($max_rate < 999999): ?>
                <span class="badge bg-light text-dark border ms-1">Max: LKR <?php echo number_format($max_rate, 0); ?></span>
                <?php endif; ?>
                <?php if($sort_by != 'room_number'): ?>
                <span class="badge bg-light text-dark border ms-1">Sort: <?php echo ucfirst(str_replace('_', ' ', $sort_by)); ?> (<?php echo $sort_order; ?>)</span>
                <?php endif; ?>
                <a href="?clear=1" class="text-danger ms-1" style="text-decoration:none; font-weight:600;">
                    <i class="fas fa-times-circle"></i> Clear All
                </a>
            </small>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== RATE RANGES & CHARTS ===== -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="section-title"><i class="fas fa-chart-bar"></i> Rate Distribution</div>
                    <div class="row">
                        <div class="col-md-7">
                            <?php foreach($rate_ranges as $range => $count): ?>
                            <div class="stat-item">
                                <span class="label">LKR <?php echo $range; ?></span>
                                <span class="value">
                                    <?php echo $count; ?> guests
                                    <span class="badge bg-secondary ms-1">
                                        <?php echo $total_guests > 0 ? round(($count / $total_guests) * 100, 1) : 0; ?>%
                                    </span>
                                </span>
                            </div>
                            <div class="progress-bar-custom mb-2" style="height: 8px; border-radius: 10px; background: #e2e8f0; overflow: hidden;">
                                <div class="fill" style="width: <?php echo $total_guests > 0 ? ($count / $total_guests) * 100 : 0; ?>%; height: 100%; border-radius: 10px; background: linear-gradient(90deg, #10b981, #f59e0b, #ef4444);"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-5">
                            <div class="chart-container">
                                <canvas id="rateChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="section-title"><i class="fas fa-trophy"></i> Top & Bottom Rates</div>
                    
                    <h6 class="fw-bold text-danger"><i class="fas fa-arrow-up me-1"></i> Highest Rates</h6>
                    <?php foreach($highest_rate_guests as $guest): ?>
                    <div class="guest-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($guest['guest_name']); ?></strong>
                                <br><small class="text-muted">Room <?php echo $guest['room_number']; ?></small>
                            </div>
                            <span class="rate-badge high">LKR <?php echo number_format($guest['room_rate'], 0); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <h6 class="fw-bold text-success mt-3"><i class="fas fa-arrow-down me-1"></i> Lowest Rates</h6>
                    <?php foreach($lowest_rate_guests as $guest): ?>
                    <div class="guest-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($guest['guest_name']); ?></strong>
                                <br><small class="text-muted">Room <?php echo $guest['room_number']; ?></small>
                            </div>
                            <span class="rate-badge low">LKR <?php echo number_format($guest['room_rate'], 0); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== ROOM TYPE & NATIONALITY SUMMARY ===== -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title"><i class="fas fa-bed"></i> Room Type Summary</div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Room Type</th>
                                    <th>Rooms</th>
                                    <th>Total Revenue</th>
                                    <th>Avg Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($room_type_summary as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['room_type'] ?? 'N/A'); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $row['count']; ?></span></td>
                                    <td>LKR <?php echo number_format($row['total_revenue'] ?? 0, 0); ?></td>
                                    <td>LKR <?php echo number_format($row['avg_rate'] ?? 0, 0); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title"><i class="fas fa-globe"></i> Nationality Summary</div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nationality</th>
                                    <th>Guests</th>
                                    <th>Total Revenue</th>
                                    <th>Avg Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($nationality_summary as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nationality'] ?? 'N/A'); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $row['count']; ?></span></td>
                                    <td>LKR <?php echo number_format($row['total_revenue'] ?? 0, 0); ?></td>
                                    <td>LKR <?php echo number_format($row['avg_rate'] ?? 0, 0); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== GUEST LIST TABLE ===== -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><i class="fas fa-list me-2" style="color: var(--gold);"></i> In-House Guests with Rates</h5>
                <div>
                    <span class="badge bg-primary me-1"><?php echo $total_guests; ?> Guests</span>
                    <span class="badge bg-success">Revenue: LKR <?php echo number_format($total_revenue, 0); ?></span>
                </div>
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
                            <th>Stay Days</th>
                            <th>Adults</th>
                            <th>Children</th>
                            <th>Nationality</th>
                            <th>Daily Rate</th>
                            <th>Total Charge</th>
                            <th>Room Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($inhouse_guests as $index => $row): 
                            $room_status = $row['room_status'] ?? 'unknown';
                            $rate = floatval($row['room_rate'] ?? 0);
                            $total_charge = floatval($row['total_room_charge'] ?? 0);
                            $stay_days = intval($row['stay_days'] ?? 1);
                            
                            // Rate badge class
                            $rate_class = 'low';
                            if ($rate > 20000) $rate_class = 'high';
                            elseif ($rate > 10000) $rate_class = 'medium';
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
                            <td class="text-center fw-bold"><?php echo $stay_days; ?></td>
                            <td class="text-center"><?php echo $row['adults'] ?? '1'; ?></td>
                            <td class="text-center"><?php echo $row['children'] ?? '0'; ?></td>
                            <td><?php echo htmlspecialchars($row['nationality'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="rate-badge <?php echo $rate_class; ?>">
                                    LKR <?php echo number_format($rate, 0); ?>
                                </span>
                            </td>
                            <td>
                                <strong>LKR <?php echo number_format($total_charge, 0); ?></strong>
                            </td>
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
                    <tfoot>
                        <tr style="font-weight: 700; background: #f8fafc;">
                            <td colspan="9" class="text-end">TOTAL:</td>
                            <td class="text-danger">LKR <?php echo number_format($total_revenue, 0); ?></td>
                            <td class="text-danger">LKR <?php echo number_format(array_sum(array_column($inhouse_guests, 'total_room_charge')), 0); ?></td>
                            <td colspan="2"><?php echo $total_guests; ?> Guests</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== SUMMARY FOOTER ===== -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="p-3 bg-white rounded-3 shadow-sm border">
                <div class="d-flex justify-content-between flex-wrap align-items-center">
                    <span><strong><i class="fas fa-users text-primary me-1"></i> Total Guests:</strong> <?php echo $total_guests; ?></span>
                    <span><strong><i class="fas fa-bed text-success me-1"></i> Rooms:</strong> <?php echo $total_rooms_occupied; ?></span>
                    <span><strong><i class="fas fa-dollar-sign text-warning me-1"></i> Daily Revenue:</strong> LKR <?php echo number_format($total_revenue, 0); ?></span>
                    <span><strong><i class="fas fa-user text-info me-1"></i> Adults/Children:</strong> <?php echo $total_adults; ?>/<?php echo $total_children; ?></span>
                    <span><strong><i class="fas fa-clock text-secondary me-1"></i> Avg Stay:</strong> <?php echo $total_guests > 0 ? round($total_stay_days / $total_guests, 1) : 0; ?> days</span>
                </div>
            </div>
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
            { orderable: false, targets: [0, 12] }
        ]
    });
    <?php endif; ?>

    // ===== RATE DISTRIBUTION CHART =====
    <?php if($total_guests > 0): ?>
    const rateLabels = <?php echo json_encode(array_keys($rate_ranges)); ?>;
    const rateData = <?php echo json_encode(array_values($rate_ranges)); ?>;
    new Chart(document.getElementById('rateChart'), {
        type: 'doughnut',
        data: {
            labels: rateLabels,
            datasets: [{
                data: rateData,
                backgroundColor: ['#10b981', '#34d399', '#fbbf24', '#f59e0b', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom', 
                    labels: { 
                        font: { size: 9 },
                        boxWidth: 12
                    } 
                }
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
    a.download = 'inhouse_rates_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    <?php else: ?>
    alert('No data to export!');
    <?php endif; ?>
}
</script>
</body>
</html>