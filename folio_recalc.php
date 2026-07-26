<?php
include 'includes/session_check.php';
include 'includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['res_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$res_id = intval($_POST['res_id']);
if ($res_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid reservation ID']);
    exit();
}

// Check if room charge already exists
$check = $conn->query("SELECT transaction_id FROM folio_transactions 
                       WHERE res_id = $res_id 
                       AND trans_type = 'charge' 
                       AND description LIKE 'Room Charge%'
                       AND status = 'active'");
if ($check && $check->num_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Room charge already exists', 'res_id' => $res_id]);
    exit();
}

// Get reservation data
$res_check = $conn->query("SELECT r.*, rm.room_rate as room_rate_from_room 
                           FROM reservations r 
                           LEFT JOIN rooms rm ON r.room_id = rm.room_id 
                           WHERE r.res_id = $res_id");
if (!$res_check || $res_check->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Reservation not found']);
    exit();
}
$res_data = $res_check->fetch_assoc();

// Get room rate
$room_rate = $res_data['room_rate'] ?? 0;
if ($room_rate <= 0 && !empty($res_data['room_rate_from_room'])) {
    $room_rate = $res_data['room_rate_from_room'];
}
if ($room_rate <= 0) {
    $room_type = $res_data['room_type'] ?? '';
    if (!empty($room_type)) {
        $type_q = $conn->query("SELECT base_price FROM room_types WHERE type_name LIKE '%$room_type%' OR short_code LIKE '%$room_type%'");
        if ($type_q && $type_q->num_rows > 0) {
            $type = $type_q->fetch_assoc();
            $room_rate = $type['base_price'] ?? 0;
        }
    }
}
if ($room_rate <= 0) {
    echo json_encode(['success' => false, 'error' => 'No room rate found']);
    exit();
}

// Update reservation rate if needed
if (($res_data['room_rate'] ?? 0) != $room_rate) {
    $conn->query("UPDATE reservations SET room_rate = $room_rate WHERE res_id = $res_id");
}

// Calculate and add charge
$checkin = new DateTime($res_data['check_in']);
$checkout = new DateTime($res_data['check_out']);
$nights = $checkin->diff($checkout)->days;
if ($nights == 0) $nights = 1;

$total_charge = $room_rate * $nights;
$description = "Room Charge (" . $nights . " nights @ LKR " . number_format($room_rate, 2) . ")";
$user_id = $_SESSION['user_id'] ?? 0;

$insert = "INSERT INTO folio_transactions 
            (res_id, amount, description, trans_type, reference_no, created_by, status) 
           VALUES 
            ($res_id, $total_charge, '$description', 'charge', 'RECALC', $user_id, 'active')";
if ($conn->query($insert)) {
    echo json_encode(['success' => true, 'message' => 'Room charge added', 'res_id' => $res_id]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?>