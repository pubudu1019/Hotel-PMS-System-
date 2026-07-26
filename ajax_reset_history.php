<?php
include 'includes/session_check.php';
include 'includes/db.php';

header('Content-Type: application/json');

function respond($success, $message = '', $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// ---- Admin-only guard ----
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

if (!$conn) {
    respond(false, 'Database connection failed.');
}

function is_valid_date($d) {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

$scope = $_POST['scope'] ?? 'range';
$deleted_by = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'unknown';

if ($scope === 'all') {
    $stmt = $conn->prepare(
        "UPDATE reservations
         SET is_deleted = 1, deleted_at = NOW(), deleted_by = ?
         WHERE status = 'Checked-Out' AND is_deleted = 0"
    );
    $stmt->bind_param("s", $deleted_by);
} else {
    $from_date = $_POST['from_date'] ?? '';
    $to_date = $_POST['to_date'] ?? '';

    if (!is_valid_date($from_date) || !is_valid_date($to_date)) {
        respond(false, 'Invalid date range.');
    }

    $stmt = $conn->prepare(
        "UPDATE reservations
         SET is_deleted = 1, deleted_at = NOW(), deleted_by = ?
         WHERE status = 'Checked-Out' AND is_deleted = 0
         AND check_out BETWEEN ? AND ?"
    );
    $stmt->bind_param("sss", $deleted_by, $from_date, $to_date);
}

$stmt->execute();

respond(true, 'History reset.', ['deleted_count' => $stmt->affected_rows]);