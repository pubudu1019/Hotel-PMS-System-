<?php
// cancelled_reservations.php - Fixed Version
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if session check file exists
if (!file_exists('includes/session_check.php')) {
    die('Error: includes/session_check.php file not found!');
}
include 'includes/session_check.php';

// Check if db file exists
if (!file_exists('includes/db.php')) {
    die('Error: includes/db.php file not found!');
}
include 'includes/db.php';

// Check database connection
if (!$conn) {
    die('Error: Database connection failed!');
}

$page_title = 'Cancelled Reservations';
$current_date = date('Y-m-d');

// Get filter parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Debug: Check if data exists
$test_query = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'Cancelled'");
$test_result = $test_query->fetch_assoc();
$total_cancelled_in_db = $test_result['total'];

// Build query with error handling
$sql = "SELECT r.*, rm.room_number 
        FROM reservations r
        LEFT JOIN rooms rm ON r.room_id = rm.room_id
        WHERE r.status = 'Cancelled'
        AND r.check_in BETWEEN ? AND ?
        ORDER BY r.check_in DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Error preparing statement: ' . $conn->error);
}

$stmt->bind_param("ss", $from_date, $to_date);

if (!$stmt->execute()) {
    die('Error executing query: ' . $stmt->error);
}

$result = $stmt->get_result();

$cancelled_res = [];
while($row = $result->fetch_assoc()) {
    $cancelled_res[] = $row;
}

$total_cancelled = count($cancelled_res);
$total_lost = 0;
foreach($cancelled_res as $row) {
    $total_lost += floatval($row['room_rate'] ?? 0);
}

// Debug info (remove after testing)
$debug_info = [
    'total_in_db' => $total_cancelled_in_db,
    'filtered_count' => $total_cancelled,
    'from_date' => $from_date,
    'to_date' => $to_date
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelled Reservations | Araliya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root { --primary-blue: #0d4b68; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
        .report-header {
            background: linear-gradient(135deg, #7f1d1d, #991b1b);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .report-header h1 { font-weight: 700; font-size: 28px; }
        .back-btn {
            color: white; text-decoration: none; padding: 8px 20px;
            border-radius: 8px; background: rgba(255,255,255,0.15);
            transition: 0.3s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.25); color: white; }
        .stat-card {
            background: white; border-radius: 12px; padding: 20px;
            text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #dc2626;
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 20px rgba(0,0,0,0.12); }
        .stat-card .number { font-size: 32px; font-weight: 700; color: #dc2626; }
        .stat-card .label { font-size: 13px; color: #6b7280; }
        .filter-section {
            background: white; padding: 20px; border-radius: 12px;
            margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .filter-section label { font-weight: 600; font-size: 13px; color: #4b5563; }
        .btn-primary { background: #dc2626; border: none; }
        .btn-primary:hover { background: #b91c1c; }
        .badge-status { 
            padding: 4px 12px; border-radius: 20px; font-size: 11px; 
            font-weight: 600; background: #fee2e2; color: #991b1b; 
        }
        .export-btn { 
            padding: 6px 16px; border-radius: 8px; font-size: 13px; 
            font-weight: 600; border: none; transition: 0.3s; 
        }
        .export-btn.excel { background: #1e7e34; color: white; }
        .export-btn.excel:hover { background: #146c2e; }
        .export-btn.print { background: #6c757d; color: white; }
        .export-btn.print:hover { background: #5a6268; }
        .table thead th {
            font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;
            color: #6b7280; background: #f8fafc;
        }
        .debug-box {
            background: #fef3c7; padding: 10px 15px; border-radius: 8px;
            margin-bottom: 15px; border-left: 4px solid #f59e0b;
            font-size: 13px;
        }
        @media print {
            .report-header, .filter-section, .export-btn, .back-btn, .debug-box { display: none !important; }
            body { background: white; }
            .stat-card { box-shadow: none !important; border: 1px solid #ddd; }
        }
        .no-data {
            padding: 50px 20px;
            text-align: center;
            background: #f9fafb;
            border-radius: 12px;
        }
        .no-data i { font-size: 48px; color: #d1d5db; margin-bottom: 15px; }
        .no-data h4 { color: #6b7280; }
        .no-data p { color: #9ca3af; }
    </style>
</head>
<body>

<div class="report-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-times-circle me-2"></i> Cancelled Reservations</h1>
                <small><i class="far fa-calendar-alt me-1"></i> Generated: <?php echo date('Y-m-d H:i:s'); ?></small>
            </div>
            <div>
                <a href="dashboard.php" class="back-btn me-2"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
                <button onclick="window.print()" class="export-btn print me-1"><i class="fas fa-print"></i> Print</button>
                <button onclick="exportExcel()" class="export-btn excel"><i class="fas fa-file-excel"></i> Excel</button>
            </div>
        </div>
    </div>
</div>

<div class="container">

    <!-- Debug Info (Remove after testing) -->
    <div class="debug-box">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Debug Info:</strong>
        Total Cancelled in DB: <?php echo $debug_info['total_in_db']; ?> |
        Filtered: <?php echo $debug_info['filtered_count']; ?> |
        From: <?php echo $debug_info['from_date']; ?> |
        To: <?php echo $debug_info['to_date']; ?>
        <?php if($debug_info['total_in_db'] == 0): ?>
        <span class="text-danger ms-2"><i class="fas fa-exclamation-triangle"></i> No cancelled reservations found in database!</span>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="number"><?php echo $total_cancelled; ?></div>
                <div class="label">Total Cancelled Reservations</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card" style="border-left-color: #f59e0b;">
                <div class="number" style="color: #f59e0b;">LKR <?php echo number_format($total_lost, 2); ?></div>
                <div class="label">Estimated Lost Revenue</div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="filter-section">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label><i class="far fa-calendar me-1"></i> From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
            </div>
            <div class="col-md-4">
                <label><i class="far fa-calendar me-1"></i> To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Generate Report
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i> Cancelled List</h5>
                <span class="badge bg-danger"><?php echo $total_cancelled; ?> Records</span>
            </div>
            
            <?php if(empty($cancelled_res)): ?>
            <div class="no-data">
                <i class="fas fa-calendar-times"></i>
                <h4>No Cancelled Reservations</h4>
                <p>No cancelled reservations found for the selected date range.</p>
                <?php if($debug_info['total_in_db'] > 0): ?>
                <p class="text-muted">Try changing the date range. There are <?php echo $debug_info['total_in_db']; ?> cancelled reservations in the database.</p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="cancelledTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Guest Name</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Nights</th>
                            <th>Nationality</th>
                            <th>Rate (LKR)</th>
                            <th>Booking Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cancelled_res as $index => $row): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['guest_name'] ?? 'N/A'); ?></strong></td>
                            <td><?php echo $row['room_number'] ?? 'N/A'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['check_in'])); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['check_out'])); ?></td>
                            <td><?php echo $row['num_of_nights'] ?? '1'; ?></td>
                            <td><?php echo htmlspecialchars($row['nationality'] ?? 'N/A'); ?></td>
                            <td class="text-danger"><?php echo number_format($row['room_rate'] ?? 0, 2); ?></td>
                            <td><?php echo htmlspecialchars($row['booking_source'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: 700; background: #f8fafc;">
                            <td colspan="7" class="text-end">TOTAL:</td>
                            <td class="text-danger"><?php echo number_format($total_lost, 2); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    <?php if(!empty($cancelled_res)): ?>
    $('#cancelledTable').DataTable({
        pageLength: 25,
        order: [[3, 'desc']],
        language: {
            search: "<i class='fas fa-search me-1'></i> Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries found",
            infoFiltered: "(filtered from _MAX_ total entries)"
        }
    });
    <?php endif; ?>
});

function exportExcel() {
    <?php if(!empty($cancelled_res)): ?>
    window.location.href = 'exports/export_cancelled.php?from=<?php echo $from_date; ?>&to=<?php echo $to_date; ?>';
    <?php else: ?>
    alert('No data to export!');
    <?php endif; ?>
}
</script>
</body>
</html>