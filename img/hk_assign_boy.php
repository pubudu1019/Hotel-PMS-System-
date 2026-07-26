<?php
include '../includes/session_check.php';
include '../includes/db.php';

header('Content-Type: application/json');

$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if (!in_array($user_role, ['admin', 'manager', 'housekeeping'])) {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$room_id = intval($_POST['room_id'] ?? 0);
$staff_id = intval($_POST['staff_id'] ?? 0);
$task = mysqli_real_escape_string($conn, $_POST['task'] ?? 'Cleaning');
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'System';

if ($room_id <= 0 || $staff_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Room and Staff are required']);
    exit();
}

// Check room exists
$room_q = $conn->query("SELECT room_number FROM rooms WHERE room_id = $room_id");
if (!$room_q || $room_q->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Room not found']);
    exit();
}
$room = $room_q->fetch_assoc();

// Check staff exists
$staff_q = $conn->query("SELECT full_name FROM housekeeping_staff WHERE staff_id = $staff_id AND is_active = 1");
if (!$staff_q || $staff_q->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Staff not found or inactive']);
    exit();
}
$staff = $staff_q->fetch_assoc();

// Assign staff to room (update room_status_history or create assignment)
$assign = $conn->query("INSERT INTO room_status_history 
                         (room_id, assigned_staff_id, new_status, changed_by, changed_at) 
                         VALUES ($room_id, $staff_id, 'cleaning', '$username', NOW())");

if ($assign) {
    // Also update room status to cleaning if not already
    $conn->query("UPDATE rooms SET status = 'cleaning' WHERE room_id = $room_id");
    
    $conn->query("INSERT INTO audit_logs (user_id, username, action, description) 
                  VALUES ($user_id, '$username', 'ASSIGN_STAFF', 
                  'Assigned {$staff['full_name']} to Room {$room['room_number']} for $task')");
    
    echo json_encode([
        'success' => true,
        'message' => "{$staff['full_name']} assigned to Room {$room['room_number']} for $task",
        'staff_name' => $staff['full_name'],
        'room_number' => $room['room_number']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to assign: ' . $conn->error]);
}
?>