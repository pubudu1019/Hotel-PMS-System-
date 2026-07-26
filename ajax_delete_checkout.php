<?php
include 'includes/session_check.php';
include 'includes/db.php';

header('Content-Type: application/json');

function respond($success, $message = '', $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// ---- Admin-only guard ----
// NOTE: adjust to match your real session role variable.
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
if (!$is_admin) {
    http_response_code(403);
    respond(false, 'Permission denied. Admin access required.');
}

// ---- CSRF check ----
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    respond(false, 'Invalid request token.');
}

// ---- Validate input ----
$res_id = isset($_POST['res_id']) ? (int)$_POST['res_id'] : 0;
if ($res_id <= 0) {
    respond(false, 'Invalid record id.');
}

if (!$conn) {
    respond(false, 'Database connection failed.');
}

// Who is deleting (adjust to your session's username field)
$deleted_by = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'unknown';

$stmt = $conn->prepare(
    "UPDATE reservations
     SET is_deleted = 1, deleted_at = NOW(), deleted_by = ?
     WHERE res_id = ? AND status = 'Checked-Out' AND is_deleted = 0"
);
$stmt->bind_param("si", $deleted_by, $res_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    respond(true, 'Record moved to Trash.');
} else {
    respond(false, 'Record not found or already deleted.');
}