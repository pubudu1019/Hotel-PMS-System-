<?php
include '../includes/session_check.php';
include '../includes/db.php';

header('Content-Type: application/json');

// ===== ONLY AUTHORIZED USERS =====
$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if (!in_array($user_role, ['admin', 'manager', 'receptionist', 'housekeeping'])) {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$room_id = intval($_POST['room_id'] ?? 0);
$new_status = mysqli_real_escape_string($conn, $_POST['new_status'] ?? '');
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'System';

// ===== VALIDATION =====
if ($room_id <= 0 || empty($new_status)) {
    echo json_encode(['success' => false, 'error' => 'Room ID and status are required']);
    exit();
}

$valid_statuses = ['available', 'occupied', 'cleaning', 'maintenance'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit();
}

// ===== CHECK ROOM =====
$check_room = $conn->query("SELECT room_number, status FROM rooms WHERE room_id = $room_id");
if (!$check_room || $check_room->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Room not found']);
    exit();
}
$room = $check_room->fetch_assoc();
$old_status = $room['status'];
$room_number = $room['room_number'];

if ($old_status == $new_status) {
    echo json_encode(['success' => true, 'message' => "Status already set to $new_status", 'no_change' => true]);
    exit();
}

// ===== UPDATE =====
$conn->begin_transaction();
try {
    $conn->query("UPDATE rooms SET status = '$new_status' WHERE room_id = $room_id");
    $conn->query("INSERT INTO room_status_history (room_id, old_status, new_status, changed_by, changed_at) 
                  VALUES ($room_id, '$old_status', '$new_status', '$username', NOW())");
    $conn->query("INSERT INTO audit_logs (user_id, username, action, description) 
                  VALUES ($user_id, '$username', 'ROOM_STATUS', 
                  'Room $room_number changed from $old_status to $new_status')");
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Room $room_number status changed from $old_status to $new_status",
        'old_status' => $old_status,
        'new_status' => $new_status,
        'room_number' => $room_number
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>