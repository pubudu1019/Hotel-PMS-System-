<?php
include 'includes/session_check.php';
include 'includes/db.php';

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// ================================================================
// ===== HELPER FUNCTION: Get folio balance =====
// ================================================================
function get_folio_balance($res_id, $conn) {
    $balance = 0;
    $trans_query = $conn->query("
        SELECT trans_type, amount FROM folio_transactions 
        WHERE res_id = $res_id AND status = 'active'
    ");
    if (!$trans_query) return 0;
    
    while ($t = $trans_query->fetch_assoc()) {
        switch($t['trans_type']) {
            case 'charge':   $balance += $t['amount']; break;
            case 'payment':  $balance -= $t['amount']; break;
            case 'advance':  $balance -= $t['amount']; break;
            case 'rebate':   $balance += $t['amount']; break;
        }
    }
    return $balance;
}

// ================================================================
// ===== SINGLE CHECK-OUT =====
// ================================================================
if (isset($_GET['checkout_id']) && is_numeric($_GET['checkout_id'])) {
    $res_id = intval($_GET['checkout_id']);
    
    $balance = get_folio_balance($res_id, $conn);
    if ($balance != 0) {
        $msg = "Balance is LKR " . number_format($balance, 2) . ". Please settle the balance before check-out.";
        header("Location: folio_management.php?open_folio=$res_id&msg=" . urlencode($msg));
        exit();
    }
    
    $room_query = $conn->query("
        SELECT r.room_id, rm.room_number 
        FROM reservations r 
        LEFT JOIN rooms rm ON r.room_id = rm.room_id 
        WHERE r.res_id = $res_id AND r.status = 'Checked-In'
    ");
    
    if ($room_query && $room_query->num_rows > 0) {
        $row = $room_query->fetch_assoc();
        $room_id = $row['room_id'];
        $room_number = $row['room_number'] ?? 'N/A';
        $user_id = $_SESSION['user_id'] ?? 0;
        $username = $_SESSION['username'] ?? 'System';
        
        $update_res = $conn->query("UPDATE reservations SET status = 'Checked-Out' WHERE res_id = $res_id");
        
        if ($update_res) {
            if (!empty($room_id)) {
                $conn->query("UPDATE rooms SET status = 'cleaning' WHERE room_id = $room_id");
            }
            
            $conn->query("INSERT INTO audit_logs (user_id, username, action, description) 
                         VALUES ($user_id, '$username', 'CHECKOUT', 'Checked out Guest from Room: $room_number')");
            
            header("Location: inhouse_rooms.php?success=checked_out");
            exit();
        } else {
            $error_msg = "Check-out failed: " . $conn->error;
        }
    } else {
        $error_msg = "Reservation not found or already checked out.";
    }
    
    if (isset($error_msg)) {
        header("Location: quick_checkout.php?error=" . urlencode($error_msg));
        exit();
    }
}

// ================================================================
// ===== BULK CHECK-OUT =====
// ================================================================
if (isset($_POST['bulk_checkout']) && isset($_POST['selected_ids'])) {
    $selected_ids = $_POST['selected_ids'];
    $errors = array();
    $success_ids = array();
    $room_ids = array();
    $user_id = $_SESSION['user_id'] ?? 0;
    $username = $_SESSION['username'] ?? 'System';
    
    if (empty($selected_ids)) {
        header("Location: quick_checkout.php?error=" . urlencode("No guests selected"));
        exit();
    }
    
    foreach ($selected_ids as $res_id) {
        $res_id = intval($res_id);
        
        $balance = get_folio_balance($res_id, $conn);
        if ($balance != 0) {
            $name_query = $conn->query("SELECT guest_name FROM reservations WHERE res_id = $res_id");
            $name = $name_query ? $name_query->fetch_assoc()['guest_name'] : "Guest #$res_id";
            $errors[] = "$name has balance LKR " . number_format($balance, 2);
            continue;
        }
        
        $room_q = $conn->query("SELECT room_id FROM reservations WHERE res_id = $res_id AND status = 'Checked-In'");
        if ($room_q && $room = $room_q->fetch_assoc()) {
            $room_ids[$res_id] = $room['room_id'];
            $success_ids[] = $res_id;
        }
    }
    
    if (!empty($success_ids)) {
        $ids_str = implode(',', $success_ids);
        $update_res = $conn->query("UPDATE reservations SET status = 'Checked-Out' WHERE res_id IN ($ids_str)");
        
        if ($update_res) {
            if (!empty($room_ids)) {
                $room_ids_str = implode(',', array_values($room_ids));
                $conn->query("UPDATE rooms SET status = 'cleaning' WHERE room_id IN ($room_ids_str)");
            }
            
            $conn->query("INSERT INTO audit_logs (user_id, username, action, description) 
                         VALUES ($user_id, '$username', 'BULK_CHECKOUT', 'Bulk checked out " . count($success_ids) . " guests')");
            
            if (empty($errors)) {
                header("Location: inhouse_rooms.php?success=bulk_checked_out");
            } else {
                $error_msg = "Some guests could not be checked out: " . implode("; ", $errors);
                header("Location: quick_checkout.php?error=" . urlencode($error_msg));
            }
            exit();
        } else {
            $error_msg = "Reservation update failed: " . $conn->error;
        }
    } else {
        $error_msg = "No guests could be checked out. All have pending balances: " . implode("; ", $errors);
        header("Location: quick_checkout.php?error=" . urlencode($error_msg));
        exit();
    }
}

// ================================================================
// ===== GET CHECKED-IN RESERVATIONS =====
// ================================================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$error_msg = isset($_GET['error']) ? $_GET['error'] : '';

$sql = "SELECT r.*, rm.room_number, rm.room_type 
        FROM reservations r 
        LEFT JOIN rooms rm ON r.room_id = rm.room_id 
        WHERE r.status = 'Checked-In'";

if (!empty($search)) {
    $sql .= " AND (r.guest_name LIKE '%$search%' OR r.res_no LIKE '%$search%' OR rm.room_number LIKE '%$search%')";
}

$sql .= " ORDER BY rm.room_number ASC, r.check_in ASC";

$query = $conn->query($sql);
if (!$query) {
    die("Query Error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Check-out</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0d4b68;
            --primary-blue-dark: #082f42;
            --gold: #fbbf24;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --success: #10b981;
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
        
        /* ===== FILTER PANEL ===== */
        .filter-panel {
            background: var(--surface);
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-subtle);
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .filter-panel .form-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-panel .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
            font-size: 13px;
            padding: 8px 12px;
            background: var(--surface-alt);
        }
        .filter-panel .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(13,75,104,0.1);
            background: var(--surface);
        }
        .btn-search {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-search:hover { background: var(--primary-blue-dark); color: white; transform: translateY(-1px); }
        .btn-reset {
            background: var(--surface-alt);
            color: var(--text-secondary);
            border: 1px solid var(--border-subtle);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-reset:hover { background: var(--border-subtle); color: var(--text-primary); }
        
        /* ===== TABLE ===== */
        .table-container {
            background: var(--surface);
            padding: 20px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-subtle);
            margin-top: 20px;
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
        
        /* ===== ROOM NUMBER ===== */
        .room-number-large {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-blue);
        }
        
        /* ===== CHECK-OUT BUTTON ===== */
        .btn-checkout-special {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 6px 16px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 6px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-checkout-special:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(239, 68, 68, 0.5);
            color: white;
        }
        .btn-checkout-special i { font-size: 14px; }
        
        /* ===== BULK CHECK-OUT ===== */
        .btn-bulk-checkout {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            border: none;
            padding: 8px 22px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
            text-transform: uppercase;
        }
        .btn-bulk-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(220, 38, 38, 0.5);
            color: white;
        }
        
        /* ===== STATUS BADGES ===== */
        .status-checkedin {
            background: #d1fae5;
            color: #065f46;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-checkout-today {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        /* ===== BULK ACTIONS ===== */
        .bulk-actions {
            background: var(--surface-alt);
            padding: 15px 20px;
            border-top: 3px solid #dc2626;
            display: none;
            border-radius: 0 0 var(--radius-md) var(--radius-md);
        }
        .bulk-actions.show { display: flex; }
        
        /* ===== ALERT ===== */
        .alert-custom {
            border-radius: 0;
            border: none;
            padding: 12px 20px;
            margin: 0;
            border-left: 4px solid transparent;
        }
        .alert-custom.alert-danger { border-left-color: #dc2626; }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .top-navbar { flex-direction: column; align-items: stretch; gap: 8px; }
            .top-navbar .brand { justify-content: center; }
            .top-navbar .brand h5 { font-size: 14px; }
            .table-responsive { font-size: 12px; }
            .btn-checkout-special { font-size: 11px; padding: 4px 12px; }
            .room-number-large { font-size: 15px; }
        }
    </style>
</head>
<body>

<!-- ===== TOP NAVBAR ===== -->
<div class="top-navbar">
    <div class="brand">
        <a href="inhouse_rooms.php" class="btn-back"><i class="fas fa-arrow-left me-1"></i> Back</a>
        <h5><i class="fas fa-sign-out-alt"></i> QUICK CHECK-OUT</h5>
        <span class="badge-clock"><i class="fas fa-clock me-1"></i> <?= date('d/m/Y h:i A') ?></span>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="inhouse_rooms.php" class="btn btn-sm btn-success" style="background:#10b981; border:none; font-weight:600;">
            <i class="fas fa-hotel me-1"></i> In-House
        </a>
        <span class="badge bg-danger" style="font-size:12px; padding:6px 14px;">
            <i class="fas fa-users me-1"></i> <?= $query->num_rows ?>
        </span>
        <button class="btn btn-sm btn-light" onclick="window.location.reload()" style="border:1px solid rgba(255,255,255,0.2); background:rgba(255,255,255,0.1); color:white;">
            <i class="fas fa-sync"></i>
        </button>
    </div>
</div>

<!-- ===== ERROR ALERT ===== -->
<?php if (isset($error_msg) && $error_msg != ''): ?>
    <div class="alert alert-danger alert-custom">
        <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars(urldecode($error_msg)) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ===== FILTER PANEL ===== -->
<div class="filter-panel">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label mb-1">Status</label>
            <select class="form-select form-select-sm" disabled style="background:#e9ecef; cursor:not-allowed;">
                <option selected>Checked-In Only</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label mb-1">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" 
                   value="<?= htmlspecialchars($search) ?>" placeholder="Guest, Room # or Res.#">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn-search w-100"><i class="fas fa-search me-1"></i> SEARCH</button>
        </div>
        <div class="col-md-2">
            <a href="quick_checkout.php" class="btn-reset w-100"><i class="fas fa-redo me-1"></i> RESET</a>
        </div>
        <div class="col-md-2 text-end">
            <span class="text-muted small">
                <i class="fas fa-building me-1"></i> Total: <strong><?= $query->num_rows ?></strong>
            </span>
        </div>
    </form>
</div>

<!-- ===== MAIN TABLE ===== -->
<div class="container-fluid mt-3">
    <div class="table-container">
        <form method="POST" action="" id="bulkCheckoutForm">
            <!-- Bulk Actions -->
            <div class="bulk-actions" id="bulkActions">
                <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
                    <div>
                        <i class="fas fa-sign-out-alt text-danger me-2"></i>
                        <span class="fw-bold text-danger" id="selectedCount">0</span>
                        <span class="text-muted"> selected</span>
                    </div>
                    <div>
                        <button type="submit" name="bulk_checkout" class="btn-bulk-checkout" 
                                onclick="return confirm('Check-out all selected guests?')">
                            <i class="fas fa-sign-out-alt me-2"></i> Check-out Selected
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                            <i class="fas fa-times me-1"></i> Clear
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle bg-white">
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleAll(this)"></th>
                            <th>Room</th>
                            <th>Res.#</th>
                            <th>Guest</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Nights</th>
                            <th>Rate</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query->num_rows > 0): ?>
                            <?php while($row = $query->fetch_assoc()): 
                                $is_today_checkout = (date('Y-m-d') == date('Y-m-d', strtotime($row['check_out'])));
                                $nights = 0;
                                if(!empty($row['check_in']) && !empty($row['check_out'])) {
                                    $checkin = new DateTime($row['check_in']);
                                    $checkout = new DateTime($row['check_out']);
                                    $nights = $checkin->diff($checkout)->days;
                                }
                                $res_number = !empty($row['res_no']) ? $row['res_no'] : 'ABR000' . $row['res_id'];
                            ?>
                            <tr class="<?= $is_today_checkout ? 'table-warning-row' : '' ?>">
                                <td><input type="checkbox" class="form-check-input row-checkbox" name="selected_ids[]" value="<?= $row['res_id'] ?>" onchange="updateCount()"></td>
                                <td>
                                    <span class="room-number-large"><?= $row['room_number'] ?? 'N/A' ?></span>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($row['room_type'] ?? '') ?></small>
                                </td>
                                <td class="fw-semibold text-muted"><?= htmlspecialchars($res_number) ?></td>
                                <td>
                                    <a href="reservation_details.php?id=<?= $row['res_id'] ?>" class="text-decoration-none fw-semibold" style="color:var(--primary-blue);">
                                        <?= htmlspecialchars($row['guest_name']) ?>
                                    </a>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-phone-alt me-1"></i> <?= htmlspecialchars($row['mobile'] ?? $row['contact_no'] ?? 'N/A') ?>
                                    </small>
                                </td>
                                <td class="text-success fw-bold"><?= date('d/m/Y', strtotime($row['check_in'])) ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($row['check_out'])) ?>
                                    <?php if($is_today_checkout): ?>
                                        <span class="status-checkout-today ms-1"><i class="fas fa-clock me-1"></i>TODAY</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold"><?= $nights ?></td>
                                <td class="fw-bold">
                                    <?= number_format($row['room_rate'] ?? 0, 2) ?>
                                    <small class="text-muted text-uppercase"><?= $row['currency'] ?? 'LKR' ?></small>
                                </td>
                                <td>
                                    <span class="status-checkedin">
                                        <i class="fas fa-check-circle me-1"></i> Checked-In
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                                        <a href="quick_checkout.php?checkout_id=<?= $row['res_id'] ?>" 
                                           class="btn-checkout-special" 
                                           onclick="return confirm('Check-out <?= addslashes($row['guest_name']) ?> from Room <?= $row['room_number'] ?>?')">
                                            <i class="fas fa-sign-out-alt"></i> CHECK OUT
                                        </a>
                                        <a href="print_grc.php?id=<?= $row['res_id'] ?>" class="btn btn-warning btn-sm" target="_blank" title="Print GRC" style="padding:4px 8px;">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="add_reservation.php?id=<?= $row['res_id'] ?>" class="btn btn-outline-primary btn-sm" title="Edit" style="padding:4px 8px;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="reservation_details.php?id=<?= $row['res_id'] ?>" class="btn btn-outline-info btn-sm" title="View" style="padding:4px 8px;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fas fa-bed fa-3x d-block mb-3 text-muted"></i>
                                    <h5 class="fw-bold">No Checked-In Guests</h5>
                                    <p>All rooms are currently available.</p>
                                    <a href="quick_checkin.php" class="btn btn-primary btn-sm"><i class="fas fa-sign-in-alt me-1"></i> Check-in Guests</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
        
        <?php if ($query->num_rows > 0): ?>
        <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center">
            <span class="text-muted"><i class="fas fa-building me-1"></i> <strong><?= $query->num_rows ?></strong> occupied rooms</span>
            <span class="text-muted small"><i class="fas fa-info-circle me-1"></i> Select guests and use bulk check-out</span>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleAll(master) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateCount();
}

function updateCount() {
    const checked = document.querySelectorAll('.row-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = checked;
    const actions = document.getElementById('bulkActions');
    if (checked > 0) actions.classList.add('show');
    else actions.classList.remove('show');
}

function clearSelection() {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateCount();
}

setTimeout(() => { if(!document.querySelector('.alert')) location.reload(); }, 60000);

document.addEventListener('DOMContentLoaded', updateCount);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>