<?php
// send_email_background.php - Background Email Sender
// This runs in background - no one waits for this

// Get parameters
$email = isset($_GET['email']) ? $_GET['email'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';
$res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;

if (empty($email) || $res_id <= 0) {
    exit;
}

// Include database and email functions
include 'includes/db.php';
include 'includes/email_functions.php';

// Get full reservation data
$full = $conn->query("SELECT r.*, rm.room_number, rm.room_type FROM reservations r LEFT JOIN rooms rm ON r.room_id = rm.room_id WHERE r.res_id = $res_id");
$data = $full->fetch_assoc();

if ($data) {
    // Send email
    sendWelcomeEmail($email, $name, $data);
}

// Log that email was sent
error_log("Background email sent to: $email for reservation: $res_id");
exit;
?>