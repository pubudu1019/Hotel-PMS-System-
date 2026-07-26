<?php
include 'includes/session_check.php';
include 'includes/db.php';

if (!$conn) die("Database Connection Failed");

// ================================================================
// LOGGING FUNCTION
// ================================================================
function log_message($msg) {
    file_put_contents('auto_charge_log.txt', date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

// ================================================================
// 🔥 ROOM RATE SET FUNCTION
// ================================================================
function set_room_rate($res_id, $conn) {
    log_message("Res $res_id: Starting set_room_rate()");
    
    $res = $conn->query("SELECT r.*, rm.room_type, rm.room_id 
                         FROM reservations r 
                         LEFT JOIN rooms rm ON r.room_id = rm.room_id 
                         WHERE r.res_id = $res_id");
    if (!$res || $res->num_rows == 0) {
        log_message("Res $res_id: Reservation not found");
        return false;
    }
    $data = $res->fetch_assoc();
    
    if (!empty($data['room_rate']) && $data['room_rate'] > 0) {
        log_message("Res $res_id: Already has rate: " . $data['room_rate']);
        return true;
    }
    
    $room_type = trim($data['room_type'] ?? '');
    log_message("Res $res_id: Room type: '$room_type'");
    
    if (empty($room_type)) {
        log_message("Res $res_id: No room type found - using default rate 5000");
        $rate = 5000;
    } else {
        $types = $conn->query("SELECT type_name, short_code, base_price FROM room_types");
        $rate = 0;
        $search = strtolower($room_type);
        
        while ($t = $types->fetch_assoc()) {
            $type_name = strtolower($t['type_name']);
            $short = strtolower($t['short_code'] ?? '');
            
            if ($search == $type_name || $search == $short ||
                strpos($search, $type_name) !== false || strpos($type_name, $search) !== false ||
                (!empty($short) && strpos($search, $short) !== false)) {
                $rate = $t['base_price'];
                log_message("Res $res_id: Matched '$room_type' with '{$t['type_name']}' - Rate: $rate");
                break;
            }
        }
        
        if ($rate <= 0) {
            $first = $conn->query("SELECT base_price FROM room_types LIMIT 1");
            if ($first && $f = $first->fetch_assoc()) {
                $rate = $f['base_price'];
                log_message("Res $res_id: No match found - using first type rate: $rate");
            } else {
                $rate = 5000;
                log_message("Res $res_id: No room_types - using default: 5000");
            }
        }
    }
    
    $update = "UPDATE reservations SET room_rate = $rate WHERE res_id = $res_id";
    if ($conn->query($update)) {
        log_message("Res $res_id: Rate set to $rate");
        return true;
    } else {
        log_message("Res $res_id: Update failed: " . $conn->error);
        return false;
    }
}

// ================================================================
// 🔥 AUTO ROOM CHARGE ADD FUNCTION
// ================================================================
function auto_add_room_charge($res_id, $conn) {
    log_message("Res $res_id: Starting auto_add_room_charge()");
    
    $check = $conn->query("SELECT transaction_id FROM folio_transactions 
                           WHERE res_id = $res_id AND trans_type = 'charge' 
                           AND description LIKE 'Room Charge%' AND status = 'active'");
    if ($check && $check->num_rows > 0) {
        log_message("Res $res_id: Already has room charge - skipping");
        return;
    }
    
    $res = $conn->query("SELECT * FROM reservations WHERE res_id = $res_id");
    if (!$res || $res->num_rows == 0) {
        log_message("Res $res_id: Reservation not found");
        return;
    }
    $data = $res->fetch_assoc();
    
    if (empty($data['room_rate']) || $data['room_rate'] <= 0) {
        log_message("Res $res_id: Room rate is 0 or null - attempting to set");
        set_room_rate($res_id, $conn);
        $res2 = $conn->query("SELECT * FROM reservations WHERE res_id = $res_id");
        $data = $res2->fetch_assoc();
        if (empty($data['room_rate']) || $data['room_rate'] <= 0) {
            log_message("Res $res_id: Still no rate - using default 5000");
            $data['room_rate'] = 5000;
        }
    }
    
    $checkin = new DateTime($data['check_in']);
    $checkout = new DateTime($data['check_out']);
    $nights = $checkin->diff($checkout)->days;
    if ($nights == 0) $nights = 1;
    
    $total_charge = $data['room_rate'] * $nights;
    $desc = "Room Charge (" . $nights . " nights @ LKR " . number_format($data['room_rate'], 2) . ")";
    $user_id = $_SESSION['user_id'] ?? 0;
    
    $insert = "INSERT INTO folio_transactions 
                (res_id, amount, description, trans_type, reference_no, created_by, status) 
               VALUES ($res_id, $total_charge, '$desc', 'charge', 'AUTO', $user_id, 'active')";
    if ($conn->query($insert)) {
        log_message("Res $res_id: Room charge added - LKR $total_charge ($nights nights @ {$data['room_rate']})");
    } else {
        log_message("Res $res_id: Insert failed: " . $conn->error);
    }
}

// ================================================================
// 🔥 FULL CHECK-IN PROCESS (WITH ROOM STATUS CHECK)
// ================================================================
function process_checkin($res_id, $conn) {
    log_message("=== PROCESSING CHECK-IN FOR RES $res_id ===");
    
    // 1. Check if reservation exists and is pending, get room status
    $check = $conn->query("
        SELECT r.room_id, rm.room_number, rm.status as room_status, r.guest_name 
        FROM reservations r 
        JOIN rooms rm ON r.room_id = rm.room_id 
        WHERE r.res_id = $res_id AND r.status = 'Pending'
    ");
    if (!$check || $check->num_rows == 0) {
        log_message("Res $res_id: Not found or not pending");
        return false;
    }
    $row = $check->fetch_assoc();
    $room_id = $row['room_id'];
    $room_number = $row['room_number'];
    $room_status = $row['room_status'];
    $guest_name = $row['guest_name'];
    
    // ================================================================
    // ===== 🔥 ROOM STATUS CHECK =====
    // ================================================================
    if ($room_status !== 'available') {
        $status_map = array(
            'occupied' => '🔴 Occupied',
            'cleaning' => '🟡 Cleaning / Dirty',
            'maintenance' => '⚫ Maintenance'
        );
        $status_text = isset($status_map[$room_status]) ? $status_map[$room_status] : ucfirst($room_status);
        log_message("Res $res_id: Check-in failed - Room $room_number is $room_status");
        
        // Store error in session for display
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['checkin_error'] = "❌ Room $room_number is not available for check-in!\n\nCurrent Status: $status_text\n\nPlease ensure room is clean (available) before check-in.";
        return false;
    }
    
    // ================================================================
    // ===== ✅ PROCEED WITH CHECK-IN =====
    // ================================================================
    
    // 2. Update reservation status
    $conn->query("UPDATE reservations SET status = 'Checked-In' WHERE res_id = $res_id");
    log_message("Res $res_id: Status updated to Checked-In");
    
    // 3. Update room status
    $conn->query("UPDATE rooms SET status = 'occupied' WHERE room_id = $room_id");
    log_message("Res $res_id: Room $room_id set to occupied");
    
    // 4. Room status history (for housekeeping tracking)
    $conn->query("INSERT INTO room_status_history 
                  (room_id, old_status, new_status, changed_by, changed_at) 
                  VALUES ($room_id, 'available', 'occupied', 'System', NOW())");
    
    // 5. 🔥 SET ROOM RATE
    set_room_rate($res_id, $conn);
    
    // 6. 🔥 ADD ROOM CHARGE
    auto_add_room_charge($res_id, $conn);
    
    log_message("=== CHECK-IN COMPLETE FOR RES $res_id ===");
    return true;
}

// ================================================================
// PROCESS SINGLE CHECK-IN (GET)
// ================================================================
if (isset($_GET['checkin_id']) && is_numeric($_GET['checkin_id'])) {
    $res_id = intval($_GET['checkin_id']);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (process_checkin($res_id, $conn)) {
        unset($_SESSION['checkin_error']);
        header("Location: quick_checkin.php?success=checked_in");
    } else {
        $error_msg = isset($_SESSION['checkin_error']) ? $_SESSION['checkin_error'] : 'Check-in failed. Room may not be available.';
        unset($_SESSION['checkin_error']);
        header("Location: quick_checkin.php?error=" . urlencode($error_msg));
    }
    exit();
}

// ================================================================
// PROCESS BULK CHECK-IN (POST)
// ================================================================
if (isset($_POST['bulk_checkin']) && isset($_POST['selected_ids'])) {
    $selected_ids = $_POST['selected_ids'];
    $errors = array();
    $success = array();
    
    if (!empty($selected_ids)) {
        foreach ($selected_ids as $res_id) {
            $res_id = intval($res_id);
            
            // Check room status first
            $check = $conn->query("
                SELECT r.room_id, rm.room_number, rm.status as room_status, r.guest_name 
                FROM reservations r 
                JOIN rooms rm ON r.room_id = rm.room_id 
                WHERE r.res_id = $res_id AND r.status = 'Pending'
            ");
            
            if ($check && $check->num_rows > 0) {
                $row = $check->fetch_assoc();
                $room_status = $row['room_status'];
                $room_number = $row['room_number'];
                $guest_name = $row['guest_name'];
                
                if ($room_status !== 'available') {
                    $errors[] = "$guest_name (Room $room_number) - " . ucfirst($room_status);
                    continue;
                }
                
                // Process check-in
                if (process_checkin($res_id, $conn)) {
                    $success[] = $guest_name;
                }
            }
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!empty($errors) && empty($success)) {
            // All failed
            $error_msg = "❌ No guests could be checked in.\n\n" . implode("\n", $errors);
            header("Location: quick_checkin.php?error=" . urlencode($error_msg));
            exit();
        } elseif (!empty($errors) && !empty($success)) {
            // Some failed, some succeeded
            $error_msg = "✅ " . count($success) . " guests checked in.\n\n❌ Could not check in:\n" . implode("\n", $errors);
            header("Location: quick_checkin.php?error=" . urlencode($error_msg));
            exit();
        } else {
            // All succeeded
            header("Location: quick_checkin.php?success=bulk");
            exit();
        }
    } else {
        header("Location: quick_checkin.php?error=no_selected");
        exit();
    }
}

// ================================================================
// GET PENDING RESERVATIONS
// ================================================================
$current_date = date('Y-m-d');
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : $current_date;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d', strtotime('+30 days'));
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$sql = "SELECT r.*, rm.room_number, rm.room_type, rm.status as room_status, u.full_name AS created_by_name
        FROM reservations r 
        LEFT JOIN rooms rm ON r.room_id = rm.room_id 
        LEFT JOIN users u ON r.created_by = u.user_id
        WHERE (r.status = 'Pending' OR r.status IS NULL)
        AND (r.check_in BETWEEN '$from_date' AND '$to_date')";
if (!empty($search)) $sql .= " AND (r.guest_name LIKE '%$search%' OR r.res_no LIKE '%$search%')";
$sql .= " ORDER BY r.res_id DESC";
$query = $conn->query($sql);
if (!$query) die("Query Error: " . $conn->error);

// Get room types for rate display
$room_types = [];
$rt = $conn->query("SELECT type_name, base_price, short_code FROM room_types");
while ($r = $rt->fetch_assoc()) {
    $name = strtolower($r['type_name']);
    $room_types[$name] = $r['base_price'];
    if (!empty($r['short_code'])) {
        $room_types[strtolower($r['short_code'])] = $r['base_price'];
    }
}

// Success/Error messages
if (isset($_GET['success'])) {
    $msg = $_GET['success'] == 'checked_in' ? '✅ Guest checked in successfully with Room Charge!' : '✅ Bulk check-in completed!';
    echo '<div class="alert alert-success alert-dismissible fade show" style="border-radius:0;margin:0;padding:15px 20px; border-left: 4px solid #10b981;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 22px;">✅</span>
                <div>
                    <strong style="font-size: 15px;">Success!</strong>
                    <p style="margin: 2px 0 0; font-size: 13px;">' . $msg . '</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%);"></button>
          </div>';
}

if (isset($_GET['error'])) {
    $error_msg = urldecode($_GET['error']);
    $is_warning = strpos($error_msg, '✅') !== false;
    $type = $is_warning ? 'warning' : 'danger';
    $icon = $is_warning ? '⚠️' : '🚫';
    
    echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" style="border-radius:0;margin:0;padding:15px 20px; border-left: 4px solid ' . ($is_warning ? '#f59e0b' : '#dc2626') . ';">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="font-size: 22px;">' . $icon . '</span>
                <div>
                    <strong style="font-size: 15px;">' . ($is_warning ? '⚠️ Partial Check-in' : '🚫 Check-in Failed!') . '</strong>
                    <p style="margin: 2px 0 0; font-size: 13px; white-space: pre-wrap;">' . nl2br(htmlspecialchars($error_msg)) . '</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%);"></button>
          </div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quick Check-in</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; font-size: 13px; }
        .top-navbar { background: #12536d; color: white; padding: 10px 20px; }
        .filter-panel { background: #eee; padding: 15px; border-bottom: 2px solid #ccc; }
        .table-container { background: #fff; padding: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table thead th { background: #f8fafc; color: #12536d; font-weight: 600; font-size: 11px; }
        .guest-link { color: #333; text-decoration: none; font-weight: 500; }
        .guest-link:hover { color: #12536d; }
        .btn-checkin-special {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border: none; padding: 6px 14px; font-size: 13px; font-weight: 600;
            border-radius: 6px; transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }
        .btn-checkin-special:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(16, 185, 129, 0.6); color: white; }
        .btn-bulk-special {
            background: linear-gradient(135deg, #1a56db, #1e40af);
            color: white; border: none; padding: 8px 25px; font-size: 14px; font-weight: 700;
            border-radius: 8px; box-shadow: 0 4px 15px rgba(26, 86, 219, 0.4);
        }
        .btn-bulk-special:hover { transform: translateY(-2px); color: white; }
        .status-pending { background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-room { padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; }
        .status-room.available { background: #d1fae5; color: #065f46; }
        .status-room.occupied { background: #fee2e2; color: #991b1b; }
        .status-room.cleaning { background: #fef3c7; color: #92400e; }
        .status-room.maintenance { background: #f3f4f6; color: #4b5563; }
        .bulk-actions { background: #f8fafc; padding: 15px 20px; border-top: 3px solid #1a56db; display: none; }
        .bulk-actions.show { display: flex; }
        .btn-payment { background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; border: none; padding: 4px 10px; font-size: 11px; border-radius: 4px; }
        .btn-payment:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(13,110,253,0.4); color: white; }
        .rate-badge { background: #e8f4f8; color: #12536d; padding: 3px 10px; border-radius: 12px; font-weight: 600; font-size: 12px; }
        @media(max-width:768px){ .table-responsive { font-size:12px; } .btn-sm { padding:2px 6px; font-size:10px; } }
    </style>
</head>
<body>

<div class="top-navbar d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <a href="dashboard.php" class="btn btn-sm btn-outline-light me-3"><i class="fas fa-arrow-left me-1"></i> Back</a>
        <h5 class="m-0 fw-bold"><i class="fas fa-bolt me-2"></i> QUICK CHECK-IN</h5>
        <span class="badge bg-light text-dark ms-3"><i class="fas fa-clock"></i> <?= date('d/m/Y h:i A') ?></span>
    </div>
    <div>
        <span class="badge bg-warning text-dark me-2"><i class="fas fa-users me-1"></i> Pending: <?= $query->num_rows ?></span>
        <a href="add_reservation.php" class="btn btn-sm btn-light"><i class="fas fa-plus-circle"></i> New</a>
    </div>
</div>

<div class="filter-panel">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label mb-1 text-muted small">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($search) ?>" placeholder="Guest or Res.#">
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1 text-muted small">From</label>
            <input type="date" name="from_date" class="form-control form-control-sm" value="<?= $from_date ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1 text-muted small">To</label>
            <input type="date" name="to_date" class="form-control form-control-sm" value="<?= $to_date ?>">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-sm btn-primary w-100">SEARCH</button>
        </div>
        <div class="col-md-1">
            <a href="quick_checkin.php" class="btn btn-sm btn-outline-secondary w-100">RESET</a>
        </div>
        <div class="col-md-3 text-end">
            <small class="text-muted"><i class="fas fa-info-circle"></i> Room Charge auto-adds on check-in</small>
        </div>
    </form>
</div>

<div class="container-fluid mt-3">
    <div class="table-container">
        <form method="POST" action="" id="bulkCheckinForm">
            <div class="bulk-actions" id="bulkActions">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <i class="fas fa-check-double text-primary me-2"></i>
                        <span class="fw-bold text-primary" id="selectedCount">0</span>
                        <span class="text-muted"> selected</span>
                    </div>
                    <div>
                        <button type="submit" name="bulk_checkin" class="btn btn-bulk-special" onclick="return confirm('Check-in all selected guests?')">
                            <i class="fas fa-check-circle me-2"></i> Check-in Selected
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()"><i class="fas fa-times me-1"></i> Clear</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle bg-white">
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleAll(this)"></th>
                            <th>#</th><th>Arrival</th><th>Departure</th><th>Nights</th><th>Res.#</th>
                            <th>Guest</th><th>Room</th><th>Room Status</th><th>Rate</th><th>Created By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query->num_rows > 0): $cnt=1; while($row = $query->fetch_assoc()):
                            $nights = ($row['num_of_nights'] ?? 0) ?: (new DateTime($row['check_in']))->diff(new DateTime($row['check_out']))->days;
                            $res_no = !empty($row['res_no']) ? $row['res_no'] : 'RES' . str_pad($row['res_id'], 4, '0', STR_PAD_LEFT);
                            
                            $display_rate = $row['room_rate'] ?? 0;
                            if ($display_rate == 0) {
                                $cat = '';
                                if (!empty($row['room_category'])) {
                                    $cat = strtolower($row['room_category']);
                                } else if (!empty($row['room_type'])) {
                                    $cat = strtolower($row['room_type']);
                                }
                                if (!empty($cat) && isset($room_types[$cat])) {
                                    $display_rate = $room_types[$cat];
                                }
                            }
                            
                            $created_by = !empty($row['created_by_name']) ? $row['created_by_name'] : 'N/A';
                            
                            // Room status badge
                            $room_status = $row['room_status'] ?? 'unknown';
                            $status_class = $room_status;
                            $status_label = ucfirst($room_status);
                            $status_icon = match($room_status) {
                                'available' => '🟢',
                                'occupied' => '🔴',
                                'cleaning' => '🟡',
                                'maintenance' => '⚫',
                                default => '⚪'
                            };
                        ?>
                        <tr>
                            <td><input type="checkbox" class="form-check-input row-checkbox" name="selected_ids[]" value="<?= $row['res_id'] ?>" onchange="updateCount()"></td>
                            <td><?= $cnt++ ?></td>
                            <td><?= date('d/m/Y', strtotime($row['check_in'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($row['check_out'])) ?></td>
                            <td class="text-center"><?= $nights ?></td>
                            <td><small class="text-muted"><?= htmlspecialchars($res_no) ?></small></td>
                            <td>
                                <a href="reservation_details.php?id=<?= $row['res_id'] ?>" class="guest-link">
                                    <?= htmlspecialchars($row['guest_name']) ?>
                                </a>
                                <span class="status-pending ms-1">Pending</span>
                            </td>
                            <td><span class="fw-bold text-primary"><?= $row['room_number'] ?? '—' ?></span></td>
                            <td>
                                <span class="status-room <?= $status_class ?>">
                                    <?= $status_icon ?> <?= $status_label ?>
                                </span>
                            </td>
                            <td>
                                <span class="rate-badge">
                                    LKR <?= number_format($display_rate, 2) ?>
                                    <?php if ($row['room_rate'] > 0): ?>
                                        <i class="fas fa-check-circle text-success" title="Rate set"></i>
                                    <?php elseif ($display_rate > 0): ?>
                                        <i class="fas fa-circle text-warning" title="Fallback rate"></i>
                                    <?php else: ?>
                                        <i class="fas fa-circle text-danger" title="No rate"></i>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td><small><?= htmlspecialchars($created_by) ?></small></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    <a href="quick_checkin.php?checkin_id=<?= $row['res_id'] ?>" 
                                       class="btn btn-checkin-special" 
                                       onclick="return confirm('Check-in <?= addslashes($row['guest_name']) ?>?')">
                                        <i class="fas fa-sign-in-alt"></i> Check In
                                    </a>
                                    <button type="button" class="btn btn-payment btn-sm" data-bs-toggle="modal" data-bs-target="#folioModal" onclick="openFolio(<?= $row['res_id'] ?>)"><i class="fas fa-credit-card"></i> Pay</button>
                                    <a href="print_grc.php?id=<?= $row['res_id'] ?>" class="btn btn-warning btn-sm" target="_blank"><i class="fas fa-print"></i></a>
                                    <a href="add_reservation.php?id=<?= $row['res_id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i></a>
                                    <a href="delete_reservation.php?id=<?= $row['res_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="12" class="text-center py-5 text-muted">No pending reservations</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
        <?php if ($query->num_rows > 0): ?>
        <div class="p-2 bg-light border-top d-flex justify-content-between">
            <span class="text-muted"><i class="fas fa-calendar-check"></i> <strong><?= $query->num_rows ?></strong> pending</span>
            <span class="text-muted">From: <?= date('d/m/Y', strtotime($from_date)) ?> To: <?= date('d/m/Y', strtotime($to_date)) ?></span>
        </div>
        <?php endif; ?>
    </div>
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
                <div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3">Loading...</p></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let folioModal = new bootstrap.Modal(document.getElementById('folioModal'));

function openFolio(res_id) {
    document.getElementById('modalContent').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3">Loading...</p></div>';
    folioModal.show();
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
        const btn = document.getElementById('postBtn');
        if (btn) { btn.disabled = true; document.getElementById('btnText').textContent = 'Processing...'; document.getElementById('btnSpinner').classList.remove('d-none'); }
        fetch('process_folio.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(data => {
                if (btn) { btn.disabled = false; document.getElementById('btnText').textContent = 'Post'; document.getElementById('btnSpinner').classList.add('d-none'); }
                if (data.success) {
                    const resId = data.res_id || this.querySelector('input[name="res_id"]').value;
                    openFolio(resId);
                    showAlert('Posted successfully!', 'success');
                    this.reset();
                } else showAlert('Error: ' + (data.error || 'Unknown'), 'danger');
            })
            .catch(err => { if(btn){btn.disabled=false;document.getElementById('btnText').textContent='Post';document.getElementById('btnSpinner').classList.add('d-none');} showAlert('Error: '+err.message, 'danger'); });
    });
}

function showAlert(msg, type) {
    const div = document.createElement('div');
    div.className = `alert alert-${type} alert-dismissible fade show m-3`;
    div.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.getElementById('modalContent').prepend(div);
    setTimeout(() => div.remove(), 5000);
}

function toggleAll(m) { document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = m.checked); updateCount(); }
function updateCount() {
    const c = document.querySelectorAll('.row-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = c;
    document.getElementById('bulkActions').classList.toggle('show', c > 0);
}
function clearSelection() { document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false); document.getElementById('selectAll').checked = false; updateCount(); }
document.addEventListener('DOMContentLoaded', updateCount);

// Reset Folio function (for modal)
function resetFolio(res_id) {
    if (!confirm('Delete ALL transactions for this folio?')) return;
    const modalContent = document.getElementById('modalContent');
    modalContent.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-danger"></div><p>Resetting...</p></div>';
    fetch('process_reset_folio.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'res_id=' + res_id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            openFolio(res_id);
            showAlert('Folio reset successfully!', 'success');
        } else showAlert('Error: ' + data.error, 'danger');
    })
    .catch(err => { showAlert('Error: ' + err.message, 'danger'); openFolio(res_id); });
}
</script>
</body>
</html>