<?php
include 'includes/session_check.php';
include 'includes/db.php';

if (!$conn) die("Database Connection Failed");

// ============================================================
// 🆕 MOVE TO ARRIVALS (Checked-In → Pending)
// ============================================================
if (isset($_GET['move_to_arrivals']) && is_numeric($_GET['move_to_arrivals'])) {
    $res_id = intval($_GET['move_to_arrivals']);
    
    // Get room_id from reservation
    $room_query = $conn->query("SELECT room_id FROM reservations WHERE res_id = $res_id AND status = 'Checked-In'");
    if ($room_query && $room_query->num_rows > 0) {
        $row = $room_query->fetch_assoc();
        $room_id = $row['room_id'];
        
        // Update reservation status to Pending
        $update_res = "UPDATE reservations SET status = 'Pending' WHERE res_id = $res_id";
        if ($conn->query($update_res)) {
            // Update room status to available
            $update_room = "UPDATE rooms SET status = 'available' WHERE room_id = $room_id";
            if ($conn->query($update_room)) {
                // Log the action
                $user_id = $_SESSION['user_id'] ?? 0;
                $username = $_SESSION['username'] ?? 'System';
                $conn->query("INSERT INTO audit_logs (user_id, username, action, description) VALUES ($user_id, '$username', 'MOVE_TO_ARRIVALS', 'Moved reservation #$res_id back to Pending')");
                
                header("Location: inhouse_rooms.php?success=moved_to_arrivals");
                exit();
            } else {
                header("Location: inhouse_rooms.php?error=Room+update+failed");
                exit();
            }
        } else {
            header("Location: inhouse_rooms.php?error=Reservation+update+failed");
            exit();
        }
    } else {
        header("Location: inhouse_rooms.php?error=Reservation+not+found+or+not+checked+in");
        exit();
    }
}
// ============================================================

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

$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
$can_edit_rate = ($user_role === 'admin' || $user_role === 'manager');

// Success/Error Messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'checked_in') {
        echo '<div class="alert alert-success alert-dismissible fade show" style="border-radius:0;margin:0;padding:12px 20px;">
                <i class="fas fa-check-circle me-2"></i> Guest checked in successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
    if ($_GET['success'] == 'moved_to_arrivals') {
        echo '<div class="alert alert-warning alert-dismissible fade show" style="border-radius:0;margin:0;padding:12px 20px;">
                <i class="fas fa-undo me-2"></i> Guest moved back to Arrivals (Pending)!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
}
if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" style="border-radius:0;margin:0;padding:12px 20px;">
            <i class="fas fa-exclamation-circle me-2"></i> Error: ' . htmlspecialchars($_GET['error']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$sql = "SELECT r.*, rm.room_number, rm.room_type, rm.status as room_status
        FROM rooms rm
        LEFT JOIN reservations r ON rm.room_id = r.room_id 
        WHERE rm.status = 'occupied' AND r.status = 'Checked-In'";
if (!empty($search)) $sql .= " AND (r.guest_name LIKE '%$search%' OR rm.room_number LIKE '%$search%' OR r.res_no LIKE '%$search%')";
$sql .= " ORDER BY rm.room_number ASC";
$query = $conn->query($sql);
if (!$query) die("Query Error: " . $conn->error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>In-House Rooms</title>
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
        .action-icons { display: flex; gap: 3px; flex-wrap: wrap; justify-content: center; align-items: center; }
        .btn-payment { background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; border: none; padding: 4px 10px; font-size: 11px; border-radius: 4px; }
        .btn-payment:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(13,110,253,0.4); color: white; }
        .btn-new-reservation { background: #10b981; color: white; border: none; padding: 6px 16px; border-radius: 6px; font-weight: 600; text-decoration: none; }
        .btn-new-reservation:hover { background: #059669; color: white; transform: scale(1.03); }
        .btn-move-arrivals { background: #f59e0b; color: white; border: none; padding: 4px 10px; font-size: 11px; border-radius: 4px; }
        .btn-move-arrivals:hover { background: #d97706; transform: translateY(-2px); color: white; }
        .btn-move-arrivals i { margin-right: 2px; }
        .rate-clickable { cursor: pointer; padding: 2px 6px; border-radius: 4px; }
        .rate-clickable:hover { background: rgba(18,83,109,0.1); }
        .rate-clickable i { font-size: 11px; color: #fbbf24; margin-left: 4px; }
        .status-occupied { background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 11px; }
        .rate-edit-popup { display: none; position: fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:30px; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,0.3); z-index:9999; width:400px; max-width:90%; }
        .rate-edit-popup.active { display: block; }
        .rate-edit-overlay { display: none; position: fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9998; }
        .rate-edit-overlay.active { display: block; }
        .rate-edit-popup .close-btn { position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer; color:#999; }
        .rate-edit-popup .close-btn:hover { color:#333; }
        .rate-edit-popup .btn-save-rate { background:#12536d; color:#fff; border:none; padding:10px; border-radius:8px; font-weight:600; width:100%; margin-top:10px; }
        .rate-edit-popup .btn-save-rate:hover { background:#0d4b68; }
        .rate-edit-popup .current-rate { background:#f0f4f8; padding:10px; border-radius:6px; margin-bottom:15px; text-align:center; font-weight:600; color:#12536d; }
        .modal-loading { text-align:center; padding:40px 0; }
        .modal-loading .spinner { width:40px; height:40px; border:4px solid #f3f3f3; border-top:4px solid #12536d; border-radius:50%; animation:spin 1s linear infinite; margin:0 auto 15px; }
        @keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
        /* Tooltip for move button */
        .move-tooltip { position: relative; cursor: pointer; }
        .move-tooltip:hover::after { 
            content: "Move to Arrivals"; 
            position: absolute; 
            bottom: 100%; 
            left: 50%; 
            transform: translateX(-50%);
            background: #333; 
            color: white; 
            padding: 2px 8px; 
            border-radius: 4px; 
            font-size: 10px;
            white-space: nowrap;
            z-index: 100;
        }
        @media(max-width:768px){ .table-responsive { font-size:12px; } .action-icons { gap:2px; } .btn-sm { padding:2px 6px; font-size:10px; } }
    </style>
</head>
<body>

<div class="top-navbar d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <a href="dashboard.php" class="btn btn-sm btn-outline-light me-3"><i class="fas fa-arrow-left me-1"></i> Back</a>
        <h5 class="m-0 fw-bold"><i class="fas fa-hotel me-2"></i>IN-HOUSE ROOMS</h5>
        <span class="badge bg-light text-dark ms-3"><i class="fas fa-clock"></i> <?= date('d/m/Y h:i A') ?></span>
        <span class="badge bg-danger ms-2"><i class="fas fa-users"></i> <?= $query->num_rows ?> Occupied</span>
    </div>
    <a href="add_reservation.php" class="btn-new-reservation"><i class="fas fa-plus-circle"></i> New Reservation</a>
</div>

<div class="filter-panel">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label mb-1 text-muted small">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($search) ?>" placeholder="Guest, Room #, Res.#...">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">SEARCH</button>
        </div>
        <div class="col-md-2">
            <a href="inhouse_rooms.php" class="btn btn-sm btn-outline-secondary w-100">RESET</a>
        </div>
        <div class="col-md-3 text-end">
            <a href="quick_checkout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Quick Check-out</a>
        </div>
    </form>
</div>

<div class="container-fluid mt-3">
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle bg-white">
                <thead>
                    <tr>
                        <th>#</th><th>Room</th><th>Guest</th><th>Res.#</th><th>Check-in</th><th>Check-out</th>
                        <th>Nights</th><th>Adults</th><th>Children</th>
                        <th>Rate</th><th>Status</th><th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query->num_rows > 0): $cnt=1; while($row = $query->fetch_assoc()):
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
                    ?>
                    <tr>
                        <td><?= $cnt++ ?></td>
                        <td><strong><?= htmlspecialchars($row['room_number']) ?></strong><br><small><?= htmlspecialchars($row['room_type']) ?></small></td>
                        <td><a href="reservation_details.php?id=<?= $row['res_id'] ?>" class="guest-link"><?= htmlspecialchars($row['guest_name']) ?></a></td>
                        <td><?= htmlspecialchars($res_no) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['check_in'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['check_out'])) ?></td>
                        <td class="text-center"><?= $nights ?></td>
                        <td><?= $row['adults'] ?></td>
                        <td><?= $row['children'] ?? 0 ?></td>
                        <td>
                            <?php if($can_edit_rate): ?>
                                <span class="rate-clickable" onclick="openRateEdit(<?= $row['res_id'] ?>, <?= $display_rate ?>, '<?= addslashes($row['guest_name']) ?>')">
                                    <i class="fas fa-edit"></i> LKR <?= number_format($display_rate, 2) ?>
                                </span>
                            <?php else: ?>
                                <strong>LKR <?= number_format($display_rate, 2) ?></strong>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-occupied"><i class="fas fa-check-circle"></i> Occupied</span></td>
                        <td class="text-center">
                            <div class="action-icons">
                                <button type="button" class="btn btn-payment btn-sm" data-bs-toggle="modal" data-bs-target="#folioModal" onclick="openFolio(<?= $row['res_id'] ?>)"><i class="fas fa-credit-card"></i> Pay</button>
                                
                                <!-- 🆕 MOVE TO ARRIVALS BUTTON -->
                                <a href="inhouse_rooms.php?move_to_arrivals=<?= $row['res_id'] ?>" 
                                   class="btn btn-move-arrivals btn-sm move-tooltip" 
                                   onclick="return confirm('Move <?= addslashes($row['guest_name']) ?> (Room <?= $row['room_number'] ?>) back to Arrivals?')"
                                   title="Move to Arrivals">
                                    <i class="fas fa-undo"></i>
                                </a>
                                
                                <a href="print_grc.php?id=<?= $row['res_id'] ?>" class="btn btn-warning btn-sm" target="_blank"><i class="fas fa-print"></i></a>
                                <a href="add_reservation.php?id=<?= $row['res_id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="quick_checkout.php?checkout_id=<?= $row['res_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Check-out <?= addslashes($row['guest_name']) ?>?')"><i class="fas fa-sign-out-alt"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="12" class="text-center py-5 text-muted"><i class="fas fa-bed fa-2x d-block mb-2"></i>No occupied rooms</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($query->num_rows > 0): ?>
        <div class="p-2 bg-light border-top d-flex justify-content-between">
            <span class="text-muted"><i class="fas fa-building"></i> <strong><?= $query->num_rows ?></strong> occupied rooms</span>
            <span class="text-muted"><small><i class="fas fa-info-circle"></i> Use <span class="badge bg-warning text-dark"><i class="fas fa-undo"></i></span> to move guest back to Arrivals</small></span>
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