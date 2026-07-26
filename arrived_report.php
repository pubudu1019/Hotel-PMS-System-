<?php
// arrived_report.php - Arrived History Report
include 'includes/session_check.php';
include 'includes/db.php';

if (!$conn) die("Database Connection Failed");

$page_title = 'Arrived Report';
$current_date = date('Y-m-d');

// ===== GET FILTER PARAMETERS =====
$from_date = isset($_GET['from_date']) && !empty($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) && !empty($_GET['to_date']) ? $_GET['to_date'] : $current_date;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// ===== CHECK IF CLEAR BUTTON CLICKED =====
if (isset($_GET['clear'])) {
    header('Location: arrived_report.php');
    exit;
}

// ===== BUILD QUERY =====
$sql = "SELECT r.*, rm.room_number, rm.room_type, rm.status as room_status,
               u.full_name AS created_by_name
        FROM reservations r 
        LEFT JOIN rooms rm ON r.room_id = rm.room_id 
        LEFT JOIN users u ON r.created_by = u.user_id
        WHERE r.check_in BETWEEN '$from_date' AND '$to_date'
        AND r.status IN ('Checked-In', 'Checked-Out')";

// Add status filter
if ($status_filter != 'all') {
    $sql .= " AND r.status = '$status_filter'";
}

// Add search filter
if (!empty($search)) {
    $sql .= " AND (r.guest_name LIKE '%$search%' OR r.res_no LIKE '%$search%' OR r.nationality LIKE '%$search%' OR r.mobile LIKE '%$search%')";
}

$sql .= " ORDER BY r.check_in DESC";

$query = $conn->query($sql);

// ===== STATS =====
$total_arrived = $query->num_rows;

// Currently checked-in
$currently_in = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE status = 'Checked-In'")->fetch_assoc()['c'];

// Checked-out
$checked_out = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE status = 'Checked-Out' AND check_out BETWEEN '$from_date' AND '$to_date'")->fetch_assoc()['c'];

// Today's arrivals
$today_arrivals = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE check_in = '$current_date' AND status IN ('Checked-In', 'Checked-Out')")->fetch_assoc()['c'];

// Total revenue for period
$revenue_query = $conn->query("SELECT SUM(room_rate) as total FROM reservations WHERE check_in BETWEEN '$from_date' AND '$to_date' AND status IN ('Checked-In', 'Checked-Out')");
$total_revenue = $revenue_query->fetch_assoc()['total'] ?? 0;

// Get all arrived reservations
$arrived_res = [];
while($row = $query->fetch_assoc()) {
    $arrived_res[] = $row;
}

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
    <title>Arrived Report | Araliya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root { --primary-blue: #0d4b68; --arrival-color: #2ec4b6; }
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
        .report-header h1 i { color: #10b981; }
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
            border-left: 4px solid #10b981;
            transition: 0.3s;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 20px rgba(0,0,0,0.12); }
        .stat-card .number { font-size: 32px; font-weight: 700; color: #10b981; }
        .stat-card .label { font-size: 13px; color: #6b7280; }
        .stat-card.primary { border-left-color: var(--primary-blue); }
        .stat-card.primary .number { color: var(--primary-blue); }
        .stat-card.warning { border-left-color: #f59e0b; }
        .stat-card.warning .number { color: #f59e0b; }
        .stat-card.danger { border-left-color: #ef4444; }
        .stat-card.danger .number { color: #ef4444; }
        .stat-card.gold { border-left-color: #fbbf24; }
        .stat-card.gold .number { color: #fbbf24; }
        
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
        
        .btn-month {
            background: #f59e0b; color: white; border: none;
            padding: 8px 16px; border-radius: 8px; font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-month:hover { 
            background: #d97706; color: white; 
            transform: translateY(-2px);
        }
        
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .table thead th {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;
            color: #6b7280; background: #f8fafc; border-bottom: 2px solid #e2e8f0;
            padding: 10px 8px; white-space: nowrap;
        }
        .table tbody td { padding: 10px 8px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .table tbody tr:hover { background: #f8fafc; }
        
        .guest-link { color: #0d4b68; text-decoration: none; font-weight: 600; }
        .guest-link:hover { color: #d97736; text-decoration: underline; }
        
        .status-badge {
            padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
            display: inline-block;
        }
        .status-badge.checked-in { background: #d1fae5; color: #065f46; }
        .status-badge.checked-in::before { content: "🟢 "; }
        .status-badge.checked-out { background: #e5e7eb; color: #374151; }
        .status-badge.checked-out::before { content: "📤 "; }
        
        .room-badge {
            background: #e8f4f8; color: #0d4b68;
            padding: 2px 12px; border-radius: 12px; font-weight: 700; font-size: 12px;
            display: inline-block;
        }
        
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
        .action-group .btn-edit { background: #6366f1; color: white; }
        .action-group .btn-edit:hover { box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        .action-group .btn-view { background: #8b5cf6; color: white; }
        .action-group .btn-view:hover { box-shadow: 0 4px 12px rgba(139,92,246,0.4); }
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
        
        .filter-badge {
            background: #e8f4f8; color: #0d4b68;
            padding: 2px 12px; border-radius: 12px; font-size: 11px;
            display: inline-block;
            margin-left: 5px;
        }
        
        .revenue-box {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #1e293b;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 18px;
            display: inline-block;
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
                <h1><i class="fas fa-check-circle me-2"></i> Arrived Report</h1>
                <small><i class="far fa-calendar-alt me-1"></i> Generated: <?php echo date('Y-m-d H:i:s'); ?></small>
            </div>
            <div>
                <a href="dashboard.php" class="back-btn me-2"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
                <a href="arrival_report.php" class="back-btn me-2" style="background: rgba(46,196,182,0.3);">
                    <i class="fas fa-plane-arrival me-1"></i> Today's Arrivals
                </a>
                <button onclick="window.print()" class="export-btn print me-1"><i class="fas fa-print"></i> Print</button>
                <button onclick="exportExcel()" class="export-btn excel"><i class="fas fa-file-excel"></i> Excel</button>
            </div>
        </div>
    </div>
</div>

<div class="container">

    <!-- ===== STATS CARDS ===== -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="number"><?php echo $total_arrived; ?></div>
                <div class="label">Total Arrived</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="number" style="color: var(--primary-blue);"><?php echo $currently_in; ?></div>
                <div class="label">Currently In-House</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="number" style="color: #f59e0b;"><?php echo $checked_out; ?></div>
                <div class="label">Checked Out</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card gold">
                <div class="number" style="color: #fbbf24;">LKR <?php echo number_format($total_revenue, 0); ?></div>
                <div class="label">Total Revenue</div>
            </div>
        </div>
    </div>

    <!-- ===== FILTER SECTION WITH CLEAR BUTTON ===== -->
    <div class="filter-section">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label><i class="far fa-calendar me-1"></i> From</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
            </div>
            <div class="col-md-3">
                <label><i class="far fa-calendar me-1"></i> To</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
            </div>
            <div class="col-md-2">
                <label><i class="fas fa-filter me-1"></i> Status</label>
                <select name="status" class="form-select">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="Checked-In" <?php echo $status_filter == 'Checked-In' ? 'selected' : ''; ?>>Checked-In</option>
                    <option value="Checked-Out" <?php echo $status_filter == 'Checked-Out' ? 'selected' : ''; ?>>Checked-Out</option>
                </select>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="?from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo $current_date; ?>" class="btn btn-month">
                        <i class="fas fa-calendar-alt me-1"></i> Month
                    </a>
                    <a href="?clear=1" class="btn btn-clear" title="Clear all filters">
                        <i class="fas fa-undo"></i> Clear
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Show active filters -->
        <?php if($from_date != date('Y-m-01') || $to_date != $current_date || !empty($search) || $status_filter != 'all'): ?>
        <div class="mt-2 pt-2 border-top">
            <small class="text-muted">
                <i class="fas fa-filter me-1"></i> Active Filters:
                <span class="filter-badge"><i class="far fa-calendar me-1"></i> <?php echo date('d/m/Y', strtotime($from_date)); ?> - <?php echo date('d/m/Y', strtotime($to_date)); ?></span>
                <?php if($status_filter != 'all'): ?>
                <span class="filter-badge"><i class="fas fa-tag me-1"></i> <?php echo $status_filter; ?></span>
                <?php endif; ?>
                <?php if(!empty($search)): ?>
                <span class="filter-badge"><i class="fas fa-search me-1"></i> <?php echo htmlspecialchars($search); ?></span>
                <?php endif; ?>
                <a href="?clear=1" class="text-danger ms-1" style="text-decoration:none; font-weight:600;">
                    <i class="fas fa-times-circle"></i> Clear All
                </a>
            </small>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== DATA TABLE ===== -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><i class="fas fa-list me-2" style="color: #10b981;"></i> Arrived Guests List</h5>
                <span class="badge bg-success"><?php echo $total_arrived; ?> Records</span>
            </div>

            <?php if(empty($arrived_res)): ?>
            <div class="no-data">
                <i class="fas fa-calendar-check" style="color: #10b981;"></i>
                <h4>No Arrived Guests Found</h4>
                <p>No checked-in guests found for the selected date range.</p>
                <a href="?from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo $current_date; ?>" class="btn btn-month mt-2">
                    <i class="fas fa-calendar-alt me-1"></i> View This Month
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="arrivedTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Guest Name</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Nights</th>
                            <th>Nationality</th>
                            <th>Status</th>
                            <th>Rate (LKR)</th>
                            <th>Room Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($arrived_res as $index => $row): 
                            $status = $row['status'] ?? 'Checked-In';
                            $status_class = strtolower(str_replace('-', '', $status));
                            $room_status = $row['room_status'] ?? 'unknown';
                            
                            // Calculate nights if not set
                            $nights = $row['num_of_nights'] ?? 0;
                            if($nights == 0) {
                                $check_in = new DateTime($row['check_in']);
                                $check_out = new DateTime($row['check_out']);
                                $nights = $check_in->diff($check_out)->days;
                            }
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <a href="reservation_details.php?id=<?php echo $row['res_id']; ?>" class="guest-link">
                                    <?php echo htmlspecialchars($row['guest_name'] ?? 'N/A'); ?>
                                </a>
                                <?php if(!empty($row['company_name'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($row['company_name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="room-badge"><?php echo $row['room_number'] ?? 'N/A'; ?></span>
                            </td>
                            <td>
                                <strong><?php echo date('d/m/Y', strtotime($row['check_in'])); ?></strong>
                                <br><small class="text-muted"><?php echo date('h:i A', strtotime($row['check_in'])); ?></small>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($row['check_out'])); ?>
                                <br><small class="text-muted"><?php echo date('h:i A', strtotime($row['check_out'])); ?></small>
                            </td>
                            <td class="text-center fw-bold"><?php echo $nights; ?></td>
                            <td><?php echo htmlspecialchars($row['nationality'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td><strong>LKR <?php echo number_format($row['room_rate'] ?? 0, 2); ?></strong></td>
                            <td>
                                <span class="room-status-badge <?php echo $room_status; ?>">
                                    <?php echo ucfirst($room_status); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="action-group">
                                    <?php if($status == 'Checked-In'): ?>
                                    <!-- Check Out -->
                                    <a href="checkout.php?checkout_id=<?php echo $row['res_id']; ?>" 
                                       class="btn btn-checkout" 
                                       onclick="return confirm('Check-out <?php echo addslashes($row['guest_name']); ?> from Room <?php echo $row['room_number'] ?? 'N/A'; ?>?')">
                                        <i class="fas fa-sign-out-alt"></i> Check Out
                                    </a>
                                    <?php endif; ?>
                                    
                                    <!-- View Details -->
                                    <a href="reservation_details.php?id=<?php echo $row['res_id']; ?>" class="btn btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    
                                    <!-- Edit -->
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
                            <td colspan="8" class="text-end">TOTAL ARRIVED:</td>
                            <td colspan="3"><?php echo $total_arrived; ?> Guests | Revenue: LKR <?php echo number_format($total_revenue, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== SUMMARY ===== -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="p-3 bg-white rounded-3 shadow-sm border">
                <div class="d-flex justify-content-between flex-wrap align-items-center">
                    <span><strong><i class="fas fa-check-circle text-success me-1"></i> Total Arrived:</strong> <?php echo $total_arrived; ?></span>
                    <span><strong><i class="fas fa-hotel text-primary me-1"></i> Currently In-House:</strong> <?php echo $currently_in; ?></span>
                    <span><strong><i class="fas fa-sign-out-alt text-warning me-1"></i> Checked Out:</strong> <?php echo $checked_out; ?></span>
                    <span><strong><i class="fas fa-calendar-day text-info me-1"></i> Today's Arrivals:</strong> <?php echo $today_arrivals; ?></span>
                    <span class="revenue-box">
                        <i class="fas fa-money-bill-wave me-2"></i> LKR <?php echo number_format($total_revenue, 2); ?>
                    </span>
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
    <?php if(!empty($arrived_res)): ?>
    $('#arrivedTable').DataTable({
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
            { orderable: false, targets: [0, 10] }
        ]
    });
    <?php endif; ?>
});

function exportExcel() {
    <?php if(!empty($arrived_res)): ?>
    const table = document.getElementById('arrivedTable');
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
    a.download = 'arrived_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    <?php else: ?>
    alert('No data to export!');
    <?php endif; ?>
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(el) {
            el.classList.remove('show');
            setTimeout(function() { el.remove(); }, 300);
        });
    }, 5000);
});
</script>
</body>
</html>