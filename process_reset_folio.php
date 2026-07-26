<?php
include 'includes/session_check.php';
include 'includes/db.php';

header('Content-Type: application/json');

// ===== ONLY ADMIN/MANAGER =====
$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if (!in_array($user_role, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'error' => 'Permission denied. Admin or Manager only.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['res_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$res_id = intval($_POST['res_id']);
if ($res_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid reservation ID']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'System';

// ===== CHECK RESERVATION =====
$res_check = $conn->query("SELECT * FROM reservations WHERE res_id = $res_id");
if (!$res_check || $res_check->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Reservation not found']);
    exit();
}
$res_data = $res_check->fetch_assoc();

// ===== STEP 1: SOFT DELETE =====
$update = "UPDATE folio_transactions SET status = 'voided' WHERE res_id = $res_id AND status = 'active'";
if (!$conn->query($update)) {
    echo json_encode(['success' => false, 'error' => 'Failed to void transactions: ' . $conn->error]);
    exit();
}

// ===== STEP 2: GET ROOM RATE =====
$room_rate = $res_data['room_rate'] ?? 0;

if ($room_rate <= 0 && !empty($res_data['room_id'])) {
    $room_q = $conn->query("SELECT room_rate FROM rooms WHERE room_id = " . intval($res_data['room_id']));
    if ($room_q && $room = $room_q->fetch_assoc()) {
        $room_rate = $room['room_rate'] ?? 0;
    }
}

if ($room_rate <= 0) {
    $room_q2 = $conn->query("SELECT room_type FROM rooms WHERE room_id = " . intval($res_data['room_id']));
    if ($room_q2 && $room2 = $room_q2->fetch_assoc()) {
        $room_type = $room2['room_type'] ?? '';
        if (!empty($room_type)) {
            $type_q = $conn->query("SELECT base_price FROM room_types WHERE type_name LIKE '%$room_type%' OR short_code LIKE '%$room_type%'");
            if ($type_q && $type = $type_q->fetch_assoc()) {
                $room_rate = $type['base_price'] ?? 0;
            }
        }
    }
}

if ($room_rate <= 0) $room_rate = 5000;

// ===== STEP 3: CALCULATE NIGHTS =====
$checkin = new DateTime($res_data['check_in']);
$checkout = new DateTime($res_data['check_out']);
$nights = $checkin->diff($checkout)->days;
if ($nights == 0) $nights = 1;

$total_charge = $room_rate * $nights;
$desc = "Room Charge (" . $nights . " nights @ LKR " . number_format($room_rate, 2) . ")";

// ===== STEP 4: RE-ADD ROOM CHARGE =====
$insert = "INSERT INTO folio_transactions 
            (res_id, amount, description, trans_type, reference_no, created_by, status) 
            VALUES ($res_id, $total_charge, '$desc', 'charge', 'AUTO_RESET', $user_id, 'active')";
if (!$conn->query($insert)) {
    echo json_encode(['success' => false, 'error' => 'Failed to re-add room charge: ' . $conn->error]);
    exit();
}

// ===== STEP 5: UPDATE RESERVATION RATE =====
if (($res_data['room_rate'] ?? 0) != $room_rate) {
    $conn->query("UPDATE reservations SET room_rate = $room_rate WHERE res_id = $res_id");
}

// ===== STEP 6: LOG AUDIT =====
$conn->query("INSERT INTO audit_logs (user_id, username, action, description) 
              VALUES ($user_id, '$username', 'FOLIO_RESET', 
              'Reset folio for reservation #$res_id, re-added room charge LKR $total_charge')");

// ===== STEP 7: RETURN SUCCESS =====
echo json_encode([
    'success' => true, 
    'message' => 'Folio reset successfully! Room charge re-added.',
    'res_id' => $res_id,
    'amount' => $total_charge
]);
?>