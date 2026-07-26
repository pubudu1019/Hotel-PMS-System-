<?php
// public/cancel_booking.php - Cancel Booking
session_start();
include '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['online_user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['online_user_id'];
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) {
    header('Location: my_bookings.php');
    exit;
}

// Check if booking belongs to user
$check = $conn->prepare("SELECT booking_id, booking_status, check_in FROM online_bookings WHERE booking_id = ? AND user_id = ?");
$check->bind_param("ii", $booking_id, $user_id);
$check->execute();
$result = $check->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header('Location: my_bookings.php');
    exit;
}

// Check if booking can be cancelled (only upcoming bookings)
$today = date('Y-m-d');
if ($booking['check_in'] < $today) {
    header('Location: my_bookings.php?error=Past bookings cannot be cancelled');
    exit;
}

if ($booking['booking_status'] == 'cancelled') {
    header('Location: my_bookings.php?error=Booking already cancelled');
    exit;
}

// Cancel booking
$update = $conn->prepare("UPDATE online_bookings SET booking_status = 'cancelled' WHERE booking_id = ?");
$update->bind_param("i", $booking_id);

if ($update->execute()) {
    header('Location: my_bookings.php?success=Booking cancelled successfully');
} else {
    header('Location: my_bookings.php?error=Failed to cancel booking');
}
$update->close();
?>