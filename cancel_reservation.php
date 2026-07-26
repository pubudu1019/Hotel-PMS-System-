<?php
// cancel_reservation.php
include 'includes/session_check.php';
include 'includes/db.php';

if (!isset($_GET['id'])) {
    header('Location: arrivals.php?error=No+reservation+selected');
    exit;
}

$res_id = intval($_GET['id']);

// Update status to 'Cancelled'
$stmt = $conn->prepare("UPDATE reservations SET status = 'Cancelled' WHERE res_id = ?");
$stmt->bind_param("i", $res_id);

if ($stmt->execute()) {
    header('Location: arrivals.php?success=cancelled');
} else {
    header('Location: arrivals.php?error=' . urlencode($stmt->error));
}
exit;
?>