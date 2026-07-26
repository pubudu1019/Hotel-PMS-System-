<?php
include 'includes/session_check.php';
include 'includes/db.php';

if (!$conn) die("Database Connection Failed");

// ================================================================
// ===== SUCCESS & ERROR MESSAGES =====
// ================================================================

// --- RATE UPDATED ---
if (isset($_GET['success']) && $_GET['success'] == 'rate_updated') {
    echo '<div class="alert alert-success alert-dismissible fade show" style="border-radius:0;margin:0;padding:12px 20px; border-left: 4px solid #10b981;">
            <i class="fas fa-check-circle me-2"></i> Rate updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// --- CANCELLED ---
if (isset($_GET['success']) && $_GET['success'] == 'cancelled') {
    echo '<div class="alert alert-success alert-dismissible fade show" style="border-radius:0;margin:0;padding:12px 20px; border-left: 4px solid #10b981;">
            <i class="fas fa-check-circle me-2"></i> Reservation cancelled successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// --- CHECKED-IN WITH EMAIL STATUS ---
if (isset($_GET['success']) && $_GET['success'] == 'checked_in') {
    $email_status = isset($_GET['email']) ? $_GET['email'] : '';
    $email_message = '';
    
    if ($email_status == 'sent') {
        $email_message = '<br><small class="text-success"><i class="fas fa-envelope me-1"></i> ✅ Welcome email sent successfully to guest!</small>';
    } elseif ($email_status == 'failed') {
        $email_message = '<br><small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> ⚠️ Email could not be sent. Please check SMTP settings.</small>';
    } elseif ($email_status == 'noemail') {
        $email_message = '<br><small class="text-muted"><i class="fas fa-info-circle me-1"></i> ℹ️ No email address provided for this guest.</small>';
    }
    
    echo '<div class="alert alert-success alert-dismissible fade show" style="border-radius:0;margin:0;padding:15px 20px; border-left: 4px solid #10b981;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="font-size: 22px;">✅</span>
                <div>
                    <strong style="font-size: 15px;">Check-in Successful!</strong>
                    <p style="margin: 2px 0 0; font-size: 13px;">Guest checked in successfully!</p>
                    ' . $email_message . '
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%);"></button>
          </div>';
}

// --- ERROR ---
if (isset($_GET['error'])) {
    $error_msg = urldecode($_GET['error']);
    echo '<div class="alert alert-danger alert-dismissible fade show" style="border-radius:0;margin:0;padding:15px 20px; border-left: 4px solid #dc2626;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="font-size: 22px;">🚫</span>
                <div>
                    <strong style="font-size: 15px;">Check-in Failed!</strong>
                    <p style="margin: 2px 0 0; font-size: 13px; white-space: pre-wrap;">' . htmlspecialchars($error_msg) . '</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%);"></button>
          </div>';
}

$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
$can_edit_rate = ($user_role === 'admin' || $user_role === 'manager');

// Get all room types for rate fallback
$room_types = [];
$rt = $conn->query("SELECT type_name, base_price, season_price, short_code FROM room_types");
while ($r = $rt->fetch_assoc()) {
    $name = trim(strtolower($r['type_name']));
    $room_types[$name] = $r;
    if (!empty($r['short_code'])) {
        $room_types[strtolower($r['short_code'])] = $r;
    }
}

// Filters
$current_date = date('Y-m-d');
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : $current_date;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d', strtotime('+30 days'));
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// ===== QUERY WITH ROOM STATUS =====
$sql = "SELECT r.*, rm.room_number, rm.room_type, rm.status as room_status,
               u.full_name AS created_by_name
        FROM reservations r 
        LEFT JOIN rooms rm ON r.room_id = rm.room_id 
        LEFT JOIN users u ON r.created_by = u.user_id
        WHERE (r.status = 'Pending' OR r.status IS NULL)
        AND (r.check_in BETWEEN '$from_date' AND '$to_date')";
if (!empty($search)) $sql .= " AND (r.guest_name LIKE '%$search%' OR r.res_no LIKE '%$search%')";
$sql .= " ORDER BY r.res_id DESC";
$query = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Arrivals - Reservation List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ===== PROFESSIONAL UI ===== */
        body { 
            background: #f0f4f8; 
            font-family: 'Inter', 'Segoe UI', sans-serif; 
            font-size: 13px; 
        }
        
        .top-navbar { 
            background: linear-gradient(135deg, #0d4b68, #1a6f8e); 
            color: white; 
            padding: 12px 24px;
            box-shadow: 0 2px 12px rgba(13,75,104,0.2);
        }
        .top-navbar h5 { 
            font-weight: 700; 
            letter-spacing: 0.3px; 
            font-size: 16px;
        }
        .top-navbar .badge-clock {
            background: rgba(255,255,255,0.15);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .btn-new-reservation { 
            background: #10b981; 
            color: white; 
            border: none; 
            padding: 8px 20px; 
            border-radius: 8px; 
            font-weight: 600; 
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 13px;
        }
        .btn-new-reservation:hover { 
            background: #059669; 
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16,185,129,0.3);
        }
        
        .filter-panel { 
            background: #ffffff; 
            padding: 16px 20px; 
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .filter-panel .form-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-panel .form-control, .filter-panel .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            padding: 8px 12px;
            background: #f8fafc;
        }
        .filter-panel .form-control:focus, .filter-panel .form-select:focus {
            border-color: #0d4b68;
            box-shadow: 0 0 0 3px rgba(13,75,104,0.1);
            background: #ffffff;
        }
        .btn-search { 
            background: #0d4b68; 
            color: white; 
            border: none; 
            padding: 8px 16px; 
            border-radius: 8px; 
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-search:hover { 
            background: #1a6f8e; 
            color: white; 
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(13,75,104,0.2);
        }
        .btn-reset { 
            background: #f1f5f9; 
            color: #475569; 
            border: 1px solid #e2e8f0; 
            padding: 8px 16px; 
            border-radius: 8px; 
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-reset:hover { 
            background: #e2e8f0; 
            color: #1e293b;
        }
        
        .table-container { 
            background: #ffffff; 
            padding: 20px; 
            border-radius: 12px; 
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            border: 1px solid #e9ecef;
            margin-top: 20px;
        }
        .table thead th { 
            background: #f8fafc; 
            color: #0d4b68; 
            font-weight: 700; 
            font-size: 10px; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            padding: 10px 8px;
            white-space: nowrap;
        }
        .table tbody td { 
            padding: 10px 8px; 
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .table tbody tr:hover { 
            background: #f8fafc; 
        }
        
        .guest-link { 
            color: #0d4b68; 
            text-decoration: none; 
            font-weight: 600;
        }
        .guest-link:hover { 
            color: #d97736; 
            text-decoration: underline;
        }
        
        .action-icons { 
            display: flex; 
            gap: 4px; 
            flex-wrap: wrap; 
            justify-content: center; 
            align-items: center;
        }
        
        .btn-checkin-arrival { 
            background: linear-gradient(135deg, #10b981, #059669); 
            color: white; 
            border: none; 
            padding: 5px 14px; 
            font-size: 11px; 
            font-weight: 600;
            border-radius: 6px; 
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-checkin-arrival:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 15px rgba(16,185,129,0.4); 
            color: white;
        }
        .btn-checkin-arrival i { font-size: 10px; }
        
        /* ===== CANCEL BUTTON ===== */
        .btn-cancel-reservation { 
            background: linear-gradient(135deg, #ef4444, #dc2626); 
            color: white; 
            border: none; 
            padding: 5px 12px; 
            font-size: 11px; 
            font-weight: 600;
            border-radius: 6px; 
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-cancel-reservation:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 15px rgba(239,68,68,0.4); 
            color: white;
        }
        .btn-cancel-reservation i { font-size: 10px; }
        
        .btn-payment { 
            background: linear-gradient(135deg, #3b82f6, #2563eb); 
            color: white; 
            border: none; 
            padding: 4px 12px; 
            font-size: 11px; 
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .btn-payment:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 15px rgba(59,130,246,0.4); 
            color: white;
        }
        
        .btn-action-sm { 
            padding: 4px 10px; 
            font-size: 11px; 
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .btn-action-sm:hover { transform: translateY(-2px); }
        
        .rate-clickable { 
            cursor: pointer; 
            padding: 3px 8px; 
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: inline-block;
            font-weight: 600;
            color: #0d4b68;
            transition: all 0.2s ease;
        }
        .rate-clickable:hover { 
            background: #e8f4f8; 
            border-color: #0d4b68;
        }
        .rate-clickable i { 
            font-size: 11px; 
            color: #fbbf24; 
            margin-right: 4px;
        }
        
        .created-by-badge {
            background: #eef2ff;
            color: #4338ca;
            font-size: 11px;
            padding: 2px 12px;
            border-radius: 12px;
            display: inline-block;
            font-weight: 500;
        }
        .created-by-badge i { margin-right: 4px; font-size: 10px; }
        
        .room-badge {
            background: #e8f4f8;
            color: #0d4b68;
            padding: 2px 12px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 12px;
            display: inline-block;
        }
        .status-pending-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        
        /* ===== ROOM STATUS BADGE ===== */
        .room-status-badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
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
        
        .rate-edit-popup { display: none; position: fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:30px; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,0.3); z-index:9999; width:400px; max-width:90%; }
        .rate-edit-popup.active { display: block; }
        .rate-edit-overlay { display: none; position: fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9998; }
        .rate-edit-overlay.active { display: block; }
        .rate-edit-popup .close-btn { position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer; color:#999; }
        .rate-edit-popup .close-btn:hover { color:#333; }
        .rate-edit-popup .btn-save-rate { background:#0d4b68; color:#fff; border:none; padding:10px; border-radius:8px; font-weight:600; width:100%; margin-top:10px; }
        .rate-edit-popup .btn-save-rate:hover { background:#0d3f54; }
        .rate-edit-popup .current-rate { background:#f0f4f8; padding:10px; border-radius:6px; margin-bottom:15px; text-align:center; font-weight:600; color:#0d4b68; }
        
        .modal-loading { text-align:center; padding:40px 0; }
        .modal-loading .spinner { width:40px; height:40px; border:4px solid #f3f3f3; border-top:4px solid #0d4b68; border-radius:50%; animation:spin 1s linear infinite; margin:0 auto 15px; }
        @keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
        
        .modal-content { border-radius: 12px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .modal-header { background: linear-gradient(135deg, #0d4b68, #1a6f8e); color: white; border-radius: 12px 12px 0 0; padding: 16px 24px; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .modal-title { font-weight: 700; font-size: 18px; }
        
        /* ===== Compact Action Buttons Group ===== */
        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
            justify-content: center;
            align-items: center;
        }
        .action-group .btn {
            padding: 3px 10px;
            font-size: 10px;
            border-radius: 5px;
            border: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .action-group .btn:hover { transform: translateY(-2px); }
        .action-group .btn-checkin { background: #10b981; color: white; }
        .action-group .btn-checkin:hover { box-shadow: 0 4px 12px rgba(16,185,129,0.4); }
        .action-group .btn-cancel { background: #ef4444; color: white; }
        .action-group .btn-cancel:hover { box-shadow: 0 4px 12px rgba(239,68,68,0.4); }
        .action-group .btn-pay { background: #3b82f6; color: white; }
        .action-group .btn-pay:hover { box-shadow: 0 4px 12px rgba(59,130,246,0.4); }
        .action-group .btn-print { background: #f59e0b; color: white; }
        .action-group .btn-print:hover { box-shadow: 0 4px 12px rgba(245,158,11,0.4); }
        .action-group .btn-edit { background: #6366f1; color: white; }
        .action-group .btn-edit:hover { box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        .action-group .btn-delete { background: #6b7280; color: white; }
        .action-group .btn-delete:hover { box-shadow: 0 4px 12px rgba(107,114,128,0.4); }
        
        .action-group .btn i { font-size: 10px; margin-right: 2px; }
        
        @media(max-width:768px){ 
            .table-responsive { font-size: 12px; } 
            .action-group { gap: 2px; } 
            .action-group .btn { padding: 2px 6px; font-size: 9px; }
            .top-navbar { flex-direction: column; gap: 8px; }
            .top-navbar .d-flex { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>

<!-- Top Navbar -->
<div class="top-navbar d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <a href="dashboard.php" class="btn btn-sm btn-outline-light px-3 py-1" style="font-size: 12px; border-radius: 6px;">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <h5 class="m-0 fw-bold"><i class="fas fa-list-ul me-2"></i> RESERVATION LIST</h5>
        <span class="badge-clock"><i class="fas fa-clock me-1"></i> <?= date('d/m/Y h:i A') ?></span>
    </div>
    <a href="add_reservation.php" class="btn-new-reservation"><i class="fas fa-plus-circle me-2"></i> New Reservation</a>
</div>

<!-- Filter Panel -->
<div class="filter-panel">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label for="searchInput" class="form-label mb-1">Search</label>
            <input type="text" id="searchInput" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($search) ?>" placeholder="Guest or Res.#...">
        </div>
        <div class="col-md-2">
            <label for="fromDate" class="form-label mb-1">From</label>
            <input type="date" id="fromDate" name="from_date" class="form-control form-control-sm" value="<?= $from_date ?>">
        </div>
        <div class="col-md-2">
            <label for="toDate" class="form-label mb-1">To</label>
            <input type="date" id="toDate" name="to_date" class="form-control form-control-sm" value="<?= $to_date ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn-search w-100"><i class="fas fa-search me-1"></i> SEARCH</button>
        </div>
        <div class="col-md-1">
            <a href="arrivals.php" class="btn-reset w-100"><i class="fas fa-redo me-1"></i> RESET</a>
        </div>
        <div class="col-md-2 text-end">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                <i class="fas fa-users me-1"></i> <?= $query->num_rows ?> Pending
            </span>
        </div>
    </form>
</div>

<!-- Main Table -->
<div class="container-fluid mt-3">
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle bg-white">
                <thead>
                    <tr>
                        <th>Arrival</th>
                        <th>Departure</th>
                        <th>Nights</th>
                        <th>Res.#</th>
                        <th>Guest</th>
                        <th>Company</th>
                        <th>Cat.</th>
                        <th>Type</th>
                        <th>A#</th>
                        <th>K#</th>
                        <th>Basis</th>
                        <th>Rate</th>
                        <th>Room</th>
                        <th>Room Status</th>
                        <th>Created By</th>
                        <th class="text-center" style="min-width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query->num_rows > 0): while($row = $query->fetch_assoc()):
                        $nights = ($row['num_of_nights'] ?? 0) ?: (new DateTime($row['check_in']))->diff(new DateTime($row['check_out']))->days;
                        $res_no = !empty($row['res_no']) ? $row['res_no'] : 'ABR000' . $row['res_id'];
                        
                        $display_rate = $row['room_rate'] ?? 0;
                        if ($display_rate == 0) {
                            $cat = '';
                            if (!empty($row['room_category'])) {
                                $cat = trim(strtolower($row['room_category']));
                            } else if (!empty($row['room_type'])) {
                                $cat = trim(strtolower($row['room_type']));
                            }
                            if (!empty($cat)) {
                                if (isset($room_types[$cat])) {
                                    $display_rate = $room_types[$cat]['base_price'];
                                } else {
                                    foreach ($room_types as $key => $rt) {
                                        if (strpos($cat, $key) !== false || strpos($key, $cat) !== false) {
                                            $display_rate = $rt['base_price'];
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        
                        $created_by_display = 'N/A';
                        if (!empty($row['created_by_name'])) {
                            $created_by_display = '<span class="created-by-badge"><i class="fas fa-user-circle"></i> ' . htmlspecialchars($row['created_by_name']) . '</span>';
                        }
                        
                        $room_status = $row['room_status'] ?? 'unknown';
                        $status_class = $room_status;
                        $status_label = ucfirst($room_status);
                    ?>
                    <tr>
                        <td><span class="fw-bold text-success"><?= date('d/m', strtotime($row['check_in'])) ?></span><br><small class="text-muted"><?= date('Y', strtotime($row['check_in'])) ?></small></td>
                        <td><?= date('d/m/Y', strtotime($row['check_out'])) ?></td>
                        <td class="text-center fw-bold"><?= $nights ?></td>
                        <td><span class="text-muted fw-semibold"><?= htmlspecialchars($res_no) ?></span></td>
                        <td>
                            <a href="reservation_details.php?id=<?= $row['res_id'] ?>" class="guest-link">
                                <?= htmlspecialchars($row['guest_name']) ?>
                            </a>
                            <br>
                            <span class="status-pending-badge"><i class="fas fa-clock me-1"></i>Pending</span>
                        </td>
                        <td><small><?= htmlspecialchars($row['company_name'] ?? 'N/A') ?></small></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['room_category'] ?? $row['room_type'] ?? 'DLK') ?></span></td>
                        <td><?= htmlspecialchars($row['bed_type'] ?? 'DBL') ?></td>
                        <td class="text-center fw-bold"><?= $row['adults'] ?></td>
                        <td class="text-center"><?= $row['children'] ?></td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= htmlspecialchars($row['meal_plan'] ?? 'HB') ?></span></td>
                        <td>
                            <?php if($can_edit_rate): ?>
                                <span class="rate-clickable" onclick="openRateEdit(<?= $row['res_id'] ?>, <?= $display_rate ?>, '<?= addslashes($row['guest_name']) ?>')">
                                    <i class="fas fa-edit"></i> LKR <?= number_format($display_rate, 2) ?>
                                </span>
                            <?php else: ?>
                                <strong class="text-primary">LKR <?= number_format($display_rate, 2) ?></strong>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="room-badge"><?= $row['room_number'] ?? 'Pending' ?></span>
                            <br><small class="text-muted"><?= htmlspecialchars($row['room_type'] ?? '') ?></small>
                        </td>
                        <td>
                            <span class="room-status-badge <?= $status_class ?>"><?= $status_label ?></span>
                        </td>
                        <td><?= $created_by_display ?></td>
                        <td class="text-center">
                            <div class="action-group">
                                <!-- ===== CHECK-IN BUTTON ===== -->
                                <a href="checking.php?checkin_id=<?= $row['res_id'] ?>" 
                                   class="btn btn-checkin" 
                                   onclick="return confirm('Check-in <?= addslashes($row['guest_name']) ?> from Room <?= $row['room_number'] ?? 'Pending' ?>?')">
                                    <i class="fas fa-sign-in-alt"></i> Check In
                                </a>
                                
                                <!-- ===== CANCEL BUTTON ===== -->
                                <a href="cancel_reservation.php?id=<?= $row['res_id'] ?>" 
                                   class="btn btn-cancel" 
                                   onclick="return confirm('⚠️ Are you sure you want to CANCEL this reservation for <?= addslashes($row['guest_name']) ?>?')">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                
                                <!-- ===== PAYMENT BUTTON ===== -->
                                <button type="button" class="btn btn-pay" data-bs-toggle="modal" data-bs-target="#folioModal" onclick="openFolio(<?= $row['res_id'] ?>)">
                                    <i class="fas fa-credit-card"></i> Pay
                                </button>
                                
                                <!-- ===== PRINT GRC ===== -->
                                <a href="print_grc.php?id=<?= $row['res_id'] ?>" class="btn btn-print" target="_blank" title="Print GRC">
                                    <i class="fas fa-print"></i> Print
                                </a>
                                
                                <!-- ===== EDIT ===== -->
                                <a href="add_reservation.php?id=<?= $row['res_id'] ?>" class="btn btn-edit" title="Edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                
                                <!-- ===== DELETE ===== -->
                                <a href="delete_reservation.php?id=<?= $row['res_id'] ?>" class="btn btn-delete" onclick="return confirm('Delete this reservation?')" title="Delete">
                                    <i class="fas fa-trash"></i> Del
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="16" class="text-center py-5 text-muted">
                        <i class="fas fa-calendar-check fa-3x d-block mb-3 text-muted"></i>
                        <h5 class="fw-bold">No Upcoming Reservations</h5>
                        <p>All reservations are checked in or there are no pending bookings in this date range.</p>
                        <a href="add_reservation.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> New Reservation</a>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($query->num_rows > 0): ?>
        <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center">
            <span class="text-muted"><i class="fas fa-calendar-check me-1"></i> <strong><?= $query->num_rows ?></strong> pending reservations</span>
            <span class="text-muted">From: <?= date('d/m/Y', strtotime($from_date)) ?> To: <?= date('d/m/Y', strtotime($to_date)) ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Rate Edit Popup -->
<div class="rate-edit-overlay" id="rateOverlay" onclick="closeRateEdit()"></div>
<div class="rate-edit-popup" id="ratePopup">
    <span class="close-btn" onclick="closeRateEdit()">&times;</span>
    <h5><i class="fas fa-edit text-warning"></i> Edit Room Rate</h5>
    <div class="current-rate" id="currentRateDisplay">Current: LKR 0.00</div>
    <form id="rateEditForm" action="update_rate.php" method="POST">
        <input type="hidden" name="res_id" id="rateResId">
        <div class="mb-3">
            <label class="form-label">Guest</label>
            <input type="text" id="rateGuestName" class="form-control" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">New Rate (LKR)</label>
            <input type="number" step="0.01" name="new_rate" id="rateNewRate" class="form-control" required>
        </div>
        <button type="submit" class="btn-save-rate"><i class="fas fa-save me-2"></i> Update Rate</button>
    </form>
</div>

<!-- Folio Modal -->
<div class="modal fade" id="folioModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-credit-card text-primary me-2"></i>Guest Folio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="modal-loading"><div class="spinner"></div><p>Loading...</p></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentResId = null;
let folioModal = null;
document.addEventListener('DOMContentLoaded', function() {
    folioModal = new bootstrap.Modal(document.getElementById('folioModal'));
});

function openFolio(res_id) {
    currentResId = res_id;
    document.getElementById('modalContent').innerHTML = '<div class="modal-loading"><div class="spinner"></div><p>Loading...</p></div>';
    if (folioModal) folioModal.show();
    fetch('get_folio_data.php?res_id=' + res_id)
        .then(res => res.text())
        .then(data => {
            document.getElementById('modalContent').innerHTML = data;
            attachFormHandler();
        })
        .catch(err => {
            document.getElementById('modalContent').innerHTML = `<div class="alert alert-danger">Error: ${err.message}</div>`;
        });
}

function attachFormHandler() {
    const form = document.getElementById('folioForm');
    if (!form) return;
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    newForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const amount = this.querySelector('input[name="amount"]');
        if (parseFloat(amount.value) <= 0) { showAlert('Enter valid amount', 'danger'); return; }
        setLoading(true);
        fetch('process_folio.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(data => {
                setLoading(false);
                if (data.success) { openFolio(data.res_id || currentResId); showAlert('Posted!', 'success'); this.reset(); }
                else showAlert('Error: ' + data.error, 'danger');
            })
            .catch(err => { setLoading(false); showAlert('Error: '+err.message, 'danger'); });
    });
}

function showAlert(msg, type) {
    const div = document.createElement('div');
    div.className = `alert alert-${type} alert-dismissible fade show m-3`;
    div.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.getElementById('modalContent').prepend(div);
    setTimeout(() => div.remove(), 4000);
}

function setLoading(loading) {
    const btn = document.getElementById('postBtn');
    if (!btn) return;
    btn.disabled = loading;
    document.getElementById('btnText').innerText = loading ? 'Processing...' : 'Post';
    document.getElementById('btnSpinner').classList.toggle('d-none', !loading);
}

function openRateEdit(res_id, rate, guest) {
    document.getElementById('rateResId').value = res_id;
    document.getElementById('rateGuestName').value = guest;
    document.getElementById('rateNewRate').value = rate;
    document.getElementById('currentRateDisplay').innerHTML = 'Current: LKR ' + Number(rate).toFixed(2);
    document.getElementById('ratePopup').classList.add('active');
    document.getElementById('rateOverlay').classList.add('active');
}
function closeRateEdit() {
    document.getElementById('ratePopup').classList.remove('active');
    document.getElementById('rateOverlay').classList.remove('active');
}
</script>
</body>
</html>