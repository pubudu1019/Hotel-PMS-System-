<?php
include 'includes/session_check.php';
include 'includes/db.php';

// Get statistics
$total_checkouts = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'Checked-Out'")->fetch_assoc()['total'];
$today = date('Y-m-d');
$today_checkouts = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'Checked-Out' AND DATE(check_out) = '$today'")->fetch_assoc()['total'];
$total_revenue = $conn->query("SELECT SUM(room_rate) as total FROM reservations WHERE status = 'Checked-Out'")->fetch_assoc()['total'];

// Get checked-out reservations
$sql = "SELECT r.*, rm.room_number, rm.room_type 
        FROM reservations r 
        LEFT JOIN rooms rm ON r.room_id = rm.room_id 
        WHERE r.status = 'Checked-Out' 
        ORDER BY r.check_out DESC 
        LIMIT 50";

$query = $conn->query($sql);
if (!$query) die("Query Error: " . $conn->error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-out History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0d4b68;
            --primary-blue-dark: #082f42;
            --gold: #fbbf24;
            --success: #10b981;
            --danger: #ef4444;
            --bg-app: #f0f4f8;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --border-subtle: #e7ebf1;
            --text-primary: #17212e;
            --text-secondary: #64748b;
            --text-tertiary: #9aa7b8;
            --shadow-sm: 0 3px 10px rgba(15, 23, 42, .06);
            --shadow-md: 0 10px 28px rgba(15, 23, 42, .09);
            --radius-md: 12px;
            --radius-lg: 18px;
            --font-body: 'Inter', 'Segoe UI', sans-serif;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg-app);
            font-family: var(--font-body);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }
        
        /* ===== TOP NAVBAR ===== */
        .top-navbar {
            background: linear-gradient(135deg, #082f42 0%, #0d4b68 55%, #0f5a7d 100%);
            color: white;
            padding: 12px 24px;
            box-shadow: 0 4px 22px rgba(8, 47, 66, .35);
            border-bottom: 2px solid rgba(251, 191, 36, .35);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .top-navbar .brand {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .top-navbar .brand h5 {
            font-weight: 700;
            letter-spacing: 0.3px;
            margin: 0;
            font-size: 16px;
        }
        .top-navbar .brand h5 i { color: var(--gold); margin-right: 8px; }
        .top-navbar .badge-clock {
            background: rgba(255,255,255,0.12);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .top-navbar .btn-back {
            background: rgba(255,255,255,0.1);
            color: white;
            border: none;
            padding: 5px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }
        .top-navbar .btn-back:hover { background: rgba(255,255,255,0.2); color: white; }
        
        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin: 20px 24px;
        }
        .stat-card {
            background: var(--surface);
            padding: 16px 20px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-subtle);
            border-left: 4px solid var(--primary-blue);
            transition: all 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .stat-card .stat-number { font-size: 28px; font-weight: 800; color: var(--primary-blue); }
        .stat-card .stat-label { font-size: 12px; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .stat-icon { font-size: 20px; margin-right: 8px; }
        .stat-card.revenue { border-left-color: var(--gold); }
        .stat-card.revenue .stat-number { color: var(--gold); }
        .stat-card.today { border-left-color: var(--success); }
        .stat-card.today .stat-number { color: var(--success); }
        .stat-card.total { border-left-color: var(--primary-blue); }
        .stat-card.total .stat-number { color: var(--primary-blue); }
        
        /* ===== TABLE ===== */
        .table-container {
            background: var(--surface);
            padding: 20px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-subtle);
            margin: 0 24px 24px;
        }
        .table thead th {
            background: var(--surface-alt);
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-subtle);
            padding: 10px 8px;
        }
        .table tbody td {
            padding: 10px 8px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-subtle);
        }
        .table tbody tr:hover { background: var(--surface-alt); }
        
        /* ===== EMPTY STATE ===== */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }
        .empty-state i { font-size: 60px; color: var(--text-tertiary); margin-bottom: 16px; }
        .empty-state h5 { font-weight: 700; color: var(--text-primary); }
        .empty-state p { color: var(--text-secondary); }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .top-navbar { flex-direction: column; align-items: stretch; gap: 8px; }
            .top-navbar .brand { justify-content: center; }
            .top-navbar .brand h5 { font-size: 14px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; margin: 16px; }
            .stat-card .stat-number { font-size: 22px; }
            .table-container { margin: 0 12px 16px; padding: 12px; }
            .table-responsive { font-size: 12px; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; margin: 12px; }
            .stat-card { padding: 12px; }
            .stat-card .stat-number { font-size: 18px; }
        }
    </style>
</head>
<body>

<!-- ===== TOP NAVBAR ===== -->
<div class="top-navbar">
    <div class="brand">
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left me-1"></i> Back</a>
        <h5><i class="fas fa-history"></i> CHECK-OUT HISTORY</h5>
        <span class="badge-clock"><i class="fas fa-clock me-1"></i> <?= date('d/m/Y h:i A') ?></span>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="inhouse_rooms.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1); color:white; border:none; font-weight:600;">
            <i class="fas fa-hotel me-1"></i> In-House
        </a>
        <a href="quick_checkout.php" class="btn btn-sm" style="background:rgba(239,68,68,0.2); color:#f87171; border:none; font-weight:600;">
            <i class="fas fa-sign-out-alt me-1"></i> Check-out
        </a>
        <button class="btn btn-sm btn-light" onclick="window.location.reload()" style="border:1px solid rgba(255,255,255,0.2); background:rgba(255,255,255,0.1); color:white;">
            <i class="fas fa-sync"></i>
        </button>
    </div>
</div>

<!-- ===== STATS CARDS ===== -->
<div class="stats-grid">
    <div class="stat-card total">
        <div class="stat-label"><i class="fas fa-users stat-icon"></i>Total Check-outs</div>
        <div class="stat-number"><?= number_format($total_checkouts) ?></div>
    </div>
    <div class="stat-card today">
        <div class="stat-label"><i class="fas fa-calendar-day stat-icon"></i>Today's Check-outs</div>
        <div class="stat-number"><?= number_format($today_checkouts) ?></div>
    </div>
    <div class="stat-card revenue">
        <div class="stat-label"><i class="fas fa-money-bill-wave stat-icon"></i>Total Revenue</div>
        <div class="stat-number">LKR <?= number_format($total_revenue ?? 0, 2) ?></div>
    </div>
    <div class="stat-card" style="border-left-color: #8b5cf6;">
        <div class="stat-label"><i class="fas fa-clock stat-icon" style="color:#8b5cf6;"></i>Avg. Stay</div>
        <div class="stat-number" style="color:#8b5cf6;">
            <?php 
            $avg = $conn->query("SELECT AVG(DATEDIFF(check_out, check_in)) as avg FROM reservations WHERE status = 'Checked-Out'");
            $avg_val = $avg->fetch_assoc()['avg'] ?? 0;
            echo number_format($avg_val, 1) . ' <small style="font-size:14px; font-weight:400;">nights</small>';
            ?>
        </div>
    </div>
</div>

<!-- ===== TABLE ===== -->
<div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <h6 class="fw-bold mb-0" style="color: var(--primary-blue);">
            <i class="fas fa-list-ul me-2" style="color: var(--gold);"></i>Recent Check-outs
            <span class="badge bg-light text-dark ms-2"><?= $query->num_rows ?> records</span>
        </h6>
        <div>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print
            </button>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Check-out Date</th>
                    <th>Nights</th>
                    <th>Rate</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if($query->num_rows > 0): $i=1; while($row = $query->fetch_assoc()): ?>
                <tr>
                    <td class="fw-bold text-muted"><?= $i++ ?></td>
                    <td>
                        <span class="fw-semibold"><?= htmlspecialchars($row['guest_name']) ?></span>
                        <?php if(!empty($row['res_no'])): ?>
                            <br><small class="text-muted">#<?= htmlspecialchars($row['res_no']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="fw-bold text-primary"><?= $row['room_number'] ?? 'N/A' ?></span>
                        <br><small class="text-muted"><?= htmlspecialchars($row['room_type'] ?? '') ?></small>
                    </td>
                    <td><?= date('d/m/Y', strtotime($row['check_out'])) ?></td>
                    <td class="text-center fw-bold"><?= $row['num_of_nights'] ?? 1 ?></td>
                    <td class="fw-bold text-success">LKR <?= number_format($row['room_rate'] ?? 0, 2) ?></td>
                    <td>
                        <span class="badge" style="background:#fef3c7; color:#92400e;">
                            <i class="fas fa-check-circle me-1"></i> Checked-Out
                        </span>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h5>No Check-out History</h5>
                            <p>No guests have checked out yet.</p>
                            <a href="inhouse_rooms.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-hotel me-1"></i> View In-House Guests
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if($query->num_rows > 0): ?>
    <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center">
        <span class="text-muted"><i class="fas fa-calendar-check me-1"></i> Showing last 50 check-outs</span>
        <span class="text-muted small"><i class="fas fa-info-circle me-1"></i> Total: <?= number_format($total_checkouts) ?> check-outs</span>
    </div>
    <?php endif; ?>
</div>

</body>
</html>