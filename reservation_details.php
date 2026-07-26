<?php
// reservation_details.php
include 'includes/session_check.php';
include 'includes/db.php';

$page_title = 'Reservation Details';
$current_date = date('Y-m-d');

// Get filter parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$sql = "SELECT r.*, rm.room_number, rt.type_name 
        FROM reservations r
        LEFT JOIN rooms rm ON r.room_id = rm.room_id
        LEFT JOIN room_types rt ON rm.room_type = rt.type_name
        WHERE r.check_in BETWEEN ? AND ?";

$params = [$from_date, $to_date];
$types = "ss";

if ($status_filter != 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (r.guest_name LIKE ? OR r.res_id LIKE ? OR r.nationality LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql .= " ORDER BY r.check_in DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}

// Calculate totals
$total_records = count($reservations);
$total_revenue = array_sum(array_column($reservations, 'room_rate'));
$checked_in_count = count(array_filter($reservations, fn($r) => ($r['status'] ?? '') == 'Checked-In'));
$pending_count = count(array_filter($reservations, fn($r) => ($r['status'] ?? '') == 'Pending'));
$cancelled_count = count(array_filter($reservations, fn($r) => ($r['status'] ?? '') == 'Cancelled'));

// Get status counts for all reservations (without filter)
$status_counts = [];
$status_query = $conn->query("SELECT status, COUNT(*) as count FROM reservations GROUP BY status");
while($row = $status_query->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details | Araliya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-blue: #0d4b68;
            --gold: #fbbf24;
        }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
        .report-header {
            background: linear-gradient(135deg, #082f42, #0d4b68);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .report-header h1 { font-weight: 700; font-size: 28px; }
        .back-btn {
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
        }
        .back-btn:hover { background: rgba(255,255,255,0.25); color: white; }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-blue);
        }
        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-blue);
        }
        .stat-card .label { font-size: 13px; color: #6b7280; }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .filter-section label { font-weight: 600; font-size: 13px; color: #4b5563; }
        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-status.Pending { background: #fef3c7; color: #92400e; }
        .badge-status.Checked-In { background: #d1fae5; color: #065f46; }
        .badge-status.Checked-Out { background: #e5e7eb; color: #374151; }
        .badge-status.Cancelled { background: #fee2e2; color: #991b1b; }
        .table thead th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            background: #f8fafc;
        }
        .export-btn {
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            border: none;
        }
        .export-btn.excel { background: #1e7e34; color: white; }
        .export-btn.excel:hover { background: #146c2e; }
        .export-btn.print { background: #6c757d; color: white; }
        .export-btn.print:hover { background: #5a6268; }
        .btn-primary { background: var(--primary-blue); border: none; }
        .btn-primary:hover { background: #082f42; }
        @media print {
            .report-header, .filter-section, .export-btn, .back-btn { display: none !important; }
            body { background: white; }
            .stat-card { box-shadow: none !important; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="report-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-list me-2"></i> Reservation Details</h1>
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

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="number"><?php echo $total_records; ?></div>
                <div class="label">Total Records</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #10b981;">
                <div class="number" style="color: #10b981;"><?php echo $checked_in_count; ?></div>
                <div class="label">Checked In</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #f59e0b;">
                <div class="number" style="color: #f59e0b;"><?php echo $pending_count; ?></div>
                <div class="label">Pending</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #ef4444;">
                <div class="number" style="color: #ef4444;"><?php echo $cancelled_count; ?></div>
                <div class="label">Cancelled</div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="filter-section">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label>From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
            </div>
            <div class="col-md-3">
                <label>To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
            </div>
            <div class="col-md-2">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Checked-In" <?php echo $status_filter == 'Checked-In' ? 'selected' : ''; ?>>Checked-In</option>
                    <option value="Checked-Out" <?php echo $status_filter == 'Checked-Out' ? 'selected' : ''; ?>>Checked-Out</option>
                    <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>Search</label>
                <input type="text" name="search" class="form-control" placeholder="Guest name..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="reservationTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Guest Name</th>
                            <th>Room</th>
                            <th>Room Type</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Nights</th>
                            <th>Status</th>
                            <th>Nationality</th>
                            <th>Rate (LKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($reservations)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle me-2"></i> No reservations found
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($reservations as $index => $row): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($row['guest_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $row['room_number'] ?? 'N/A'; ?></td>
                            <td><?php echo $row['type_name'] ?? 'N/A'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['check_in'])); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['check_out'])); ?></td>
                            <td><?php echo $row['num_of_nights'] ?? '1'; ?></td>
                            <td>
                                <span class="badge-status <?php echo $row['status'] ?? 'Pending'; ?>">
                                    <?php echo $row['status'] ?? 'Pending'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['nationality'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($row['room_rate'] ?? 0, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Summary Footer -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="p-3 bg-white rounded shadow-sm">
                <div class="d-flex justify-content-between flex-wrap">
                    <span><strong>Total Records:</strong> <?php echo $total_records; ?></span>
                    <span><strong>Total Revenue:</strong> LKR <?php echo number_format($total_revenue, 2); ?></span>
                    <span><strong>Checked In:</strong> <?php echo $checked_in_count; ?></span>
                    <span><strong>Pending:</strong> <?php echo $pending_count; ?></span>
                    <span><strong>Cancelled:</strong> <?php echo $cancelled_count; ?></span>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#reservationTable').DataTable({
        pageLength: 25,
        order: [[4, 'desc']],
        language: {
            search: "<i class='fas fa-search me-1'></i> Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });
});

function exportExcel() {
    const table = document.getElementById('reservationTable');
    if(!table) return;
    
    let csv = [];
    const headers = [];
    const ths = table.querySelectorAll('thead th');
    ths.forEach(th => headers.push(th.textContent.trim()));
    csv.push(headers.join(','));
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('td');
        cells.forEach(cell => {
            let text = cell.textContent.trim();
            const badge = cell.querySelector('.badge-status');
            if(badge) text = badge.textContent.trim();
            if(text.includes(',')) text = `"${text}"`;
            rowData.push(text);
        });
        if(rowData.length > 0) csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'reservation_details_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>
</body>
</html>