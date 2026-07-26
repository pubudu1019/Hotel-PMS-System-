<?php
// arrival_report.php - Today's Arrival Report
include 'includes/session_check.php';
include 'includes/db.php';

if (!$conn) die("Database Connection Failed");

$page_title = 'Arrival Report';
$current_date = date('Y-m-d');

// ===== GET FILTER PARAMETERS =====
$date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : $current_date;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// ===== CHECK IF CLEAR BUTTON CLICKED =====
if (isset($_GET['clear'])) {
    header('Location: arrival_report.php');
    exit;
}

// ===== BUILD QUERY =====
$sql = "SELECT r.*, rm.room_number, rm.room_type, rm.status as room_status,
               u.full_name AS created_by_name
        FROM reservations r 
        LEFT JOIN rooms rm ON r.room_id = rm.room_id 
        LEFT JOIN users u ON r.created_by = u.user_id
        WHERE r.check_in = '$date'
        AND r.status != 'Cancelled'";

if (!empty($search)) {
    $sql .= " AND (r.guest_name LIKE '%$search%' OR r.res_no LIKE '%$search%' OR r.nationality LIKE '%$search%' OR r.mobile LIKE '%$search%')";
}

$sql .= " ORDER BY r.expected_arrival_time ASC, r.guest_name ASC";

$query = $conn->query($sql);

// ===== STATS =====
$total_arrivals = $query->num_rows;

// Checked-in count for today
$checked_in = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE check_in = '$date' AND status = 'Checked-In'")->fetch_assoc()['c'];

// Pending count for today
$pending_today = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE check_in = '$date' AND (status = 'Pending' OR status IS NULL)")->fetch_assoc()['c'];

// Cancelled count for today
$cancelled_today = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE check_in = '$date' AND status = 'Cancelled'")->fetch_assoc()['c'];

// No-show count (checked-out without checking in - for today)
$no_show = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE check_in = '$date' AND status = 'Checked-Out' AND check_in < '$current_date'")->fetch_assoc()['c'];

// Get all arrivals
$arrivals = [];
while($row = $query->fetch_assoc()) {
    $arrivals[] = $row;
}

// ===== SHOW SUCCESS/ERROR MESSAGES =====
if (isset($_GET['success']) && $_GET['success'] == 'checked_in') {
    echo '<div class="alert alert-success alert-dismissible fade show" style="border-radius:0;margin:0;padding:12px 20px; border-left: 4px solid #10b981;">
            <i class="fas fa-check-circle me-2"></i> Check-in completed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}
if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" style="border-radius:0;margin:0;padding:12px 20px; border-left: 4px solid #ef4444;">
            <i class="fas fa-exclamation-circle me-2"></i> ' . htmlspecialchars(urldecode($_GET['error'])) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arrival Report | Araliya</title>
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
        .report-header h1 i { color: #2ec4b6; }
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
            border-left: 4px solid var(--arrival-color);
            transition: 0.3s;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 20px rgba(0,0,0,0.12); }
        .stat-card .number { font-size: 32px; font-weight: 700; color: var(--arrival-color); }
        .stat-card .label { font-size: 13px; color: #6b7280; }
        .stat-card.danger { border-left-color: #ef4444; }
        .stat-card.danger .number { color: #ef4444; }
        .stat-card.success { border-left-color: #10b981; }
        .stat-card.success .number { color: #10b981; }
        .stat-card.warning { border-left-color: #f59e0b; }
        .stat-card.warning .number { color: #f59e0b; }
        
        .filter-section {
            background: white; padding: 16px 20px; border-radius: 12px;
            margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .filter-section label { font-weight: 600; font-size: 12px; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-section .form-control {
            border-radius: 8px; border: 1px solid #e2e8f0;
            font-size: 13px; padding: 8px 12px; background: #f8fafc;
        }
        .filter-section .form-control:focus {
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
        
        .btn-today {
            background: #2ec4b6; color: white; border: none;
            padding: 8px 16px; border-radius: 8px; font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-today:hover { 
            background: #1fa89a; color: white; 
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
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.pending::before { content: "⏳ "; }
        .status-badge.checked-in { background: #d1fae5; color: #065f46; }
        .status-badge.checked-in::before { content: "✅ "; }
        .status-badge.checked-out { background: #e5e7eb; color: #374151; }
        .status-badge.checked-out::before { content: "📤 "; }
        .status-badge.cancelled { background: #fee2e2; color: #991b1b; }
        .status-badge.cancelled::before { content: "❌ "; }
        
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
        .action-group .btn-checkin { background: #10b981; color: white; }
        .action-group .btn-checkin:hover { box-shadow: 0 4px 12px rgba(16,185,129,0.4); }
        .action-group .btn-cancel { background: #ef4444; color: white; }
        .action-group .btn-cancel:hover { box-shadow: 0 4px 12px rgba(239,68,68,0.4); }
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
        
        .filter-badge {
            background: #e8f4f8; color: #0d4b68;
            padding: 2px 12px; border-radius: 12px; font-size: 11px;
            display: inline-block;
            margin-left: 5px;
        }
        
        .time-badge {
            background: #e8f4f8; color: #0d4b68;
            padding: 2px 10px; border-radius: 12px; font-size: 11px;
            font-weight: 600;
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
                <h1><i class="fas fa-plane-arrival me-2"></i> Arrival Report</h1>
                <small><i class="far fa-calendar-alt me-1"></i> Generated: <?php echo date('Y-m-d H:i:s'); ?></small>
            </div>
            <div>
                <a href="dashboard.php" class="back-btn me-2"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
                <a href="arrivals.php" class="back-btn me-2" style="background: rgba(16,185,129,0.3);">
                    <i class="fas fa-list me-1"></i> All Arrivals
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
                <div class="number"><?php echo $total_arrivals; ?></div>
                <div class="label">Total Arrivals</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="number" style="color: #10b981;"><?php echo $checked_in; ?></div>
                <div class="label">Checked In</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="number" style="color: #f59e0b;"><?php echo $pending_today; ?></div>
                <div class="label">Pending Check-in</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card danger">
                <div class="number" style="color: #ef4444;"><?php echo $cancelled_today; ?></div>
                <div class="label">Cancelled</div>
            </div>
        </div>
    </div>

    <!-- ===== FILTER SECTION WITH CLEAR BUTTON ===== -->
    <div class="filter-section">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label><i class="far fa-calendar me-1"></i> Select Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
            </div>
            <div class="col-md-4">
                <label><i class="fas fa-search me-1"></i> Search</label>
                <input type="text" name="search" class="form-control" placeholder="Guest name, Res #, Nationality..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-5">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="?date=<?php echo $current_date; ?>" class="btn btn-today">
                        <i class="fas fa-calendar-day me-1"></i> Today
                    </a>
                    <a href="?clear=1" class="btn btn-clear" title="Clear all filters">
                        <i class="fas fa-undo"></i> Clear
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Show active filters -->
        <?php if(!empty($search) || $date != $current_date): ?>
        <div class="mt-2 pt-2 border-top">
            <small class="text-muted">
                <i class="fas fa-filter me-1"></i> Active Filters:
                <?php if($date != $current_date): ?>
                <span class="filter-badge"><i class="far fa-calendar me-1"></i> <?php echo date('d/m/Y', strtotime($date)); ?></span>
                <?php else: ?>
                <span class="filter-badge" style="background: #d1fae5; color: #065f46;"><i class="fas fa-calendar-day me-1"></i> Today</span>
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
                <h5 class="card-title mb-0"><i class="fas fa-list me-2" style="color: #2ec4b6;"></i> Arrivals List</h5>
                <span class="badge bg-info text-white"><?php echo $total_arrivals; ?> Records</span>
            </div>

            <?php if(empty($arrivals)): ?>
            <div class="no-data">
                <i class="fas fa-calendar-check" style="color: #2ec4b6;"></i>
                <h4>No Arrivals Found</h4>
                <p>No arrivals found for <?php echo date('d/m/Y', strtotime($date)); ?></p>
                <a href="?date=<?php echo $current_date; ?>" class="btn btn-today mt-2">
                    <i class="fas fa-calendar-day me-1"></i> View Today
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="arrivalTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Guest Name</th>
                            <th>Room</th>
                            <th>Expected Time</th>
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
                        <?php foreach($arrivals as $index => $row): 
                            $status = $row['status'] ?? 'Pending';
                            $status_class = strtolower(str_replace('-', '', $status));
                            
                            $expected_time = !empty($row['expected_arrival_time']) ? 
                                date('h:i A', strtotime($row['expected_arrival_time'])) : 
                                'N/A';
                            
                            $room_status = $row['room_status'] ?? 'unknown';
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
                                <span class="room-badge"><?php echo $row['room_number'] ?? 'Pending'; ?></span>
                            </td>
                            <td>
                                <span class="time-badge"><i class="far fa-clock me-1"></i> <?php echo $expected_time; ?></span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($row['check_out'])); ?></td>
                            <td class="text-center fw-bold"><?php echo $row['num_of_nights'] ?? '1'; ?></td>
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
                                    <?php if($status == 'Pending' || $status == ''): ?>
                                    <!-- Check In -->
                                    <a href="checking.php?checkin_id=<?php echo $row['res_id']; ?>" 
                                       class="btn btn-checkin" 
                                       onclick="return confirm('Check-in <?php echo addslashes($row['guest_name']); ?>?')">
                                        <i class="fas fa-sign-in-alt"></i> Check In
                                    </a>
                                    
                                    <!-- Cancel -->
                                    <a href="cancel_reservation.php?id=<?php echo $row['res_id']; ?>" 
                                       class="btn btn-cancel" 
                                       onclick="return confirm('⚠️ Cancel reservation for <?php echo addslashes($row['guest_name']); ?>?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <?php endif; ?>
                                    
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
                            <td colspan="8" class="text-end">TOTAL ARRIVALS:</td>
                            <td colspan="3"><?php echo $total_arrivals; ?> Guests</td>
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
                <div class="d-flex justify-content-between flex-wrap">
                    <span><strong><i class="fas fa-plane-arrival text-info me-1"></i> Total Arrivals:</strong> <?php echo $total_arrivals; ?></span>
                    <span><strong><i class="fas fa-check-circle text-success me-1"></i> Checked In:</strong> <?php echo $checked_in; ?></span>
                    <span><strong><i class="fas fa-clock text-warning me-1"></i> Pending:</strong> <?php echo $pending_today; ?></span>
                    <span><strong><i class="fas fa-times-circle text-danger me-1"></i> Cancelled:</strong> <?php echo $cancelled_today; ?></span>
                    <span><strong><i class="fas fa-calendar-day me-1"></i> Date:</strong> <?php echo date('d/m/Y', strtotime($date)); ?></span>
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
    <?php if(!empty($arrivals)): ?>
    $('#arrivalTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']],
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
    <?php if(!empty($arrivals)): ?>
    const table = document.getElementById('arrivalTable');
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
    a.download = 'arrival_report_<?php echo date('Y-m-d'); ?>.csv';
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