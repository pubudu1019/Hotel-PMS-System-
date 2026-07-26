<?php
// checking.php - FINAL (Background Email - No Delay + Room Status Check)
include 'includes/session_check.php';
include 'includes/db.php';

$res_id = isset($_GET['checkin_id']) ? intval($_GET['checkin_id']) : 0;

if ($res_id <= 0) {
    header('Location: arrivals.php?error=Invalid+ID');
    exit;
}

// ===== GET ROOM ID AND ROOM STATUS =====
$room_data = $conn->query("SELECT r.room_id, r.status as room_status, res.guest_name, res.email 
                           FROM reservations res 
                           LEFT JOIN rooms r ON res.room_id = r.room_id 
                           WHERE res.res_id = $res_id")->fetch_assoc();

if (!$room_data) {
    header('Location: arrivals.php?error=Reservation+not+found');
    exit;
}

$room_id = $room_data['room_id'] ?? 0;
$room_status = $room_data['room_status'] ?? '';

// ===== CHECK ROOM STATUS BEFORE CHECK-IN =====
if ($room_status != 'available' && $room_status != 'cleaning') {
    // Room is not available for check-in
    $error_msg = 'Room is currently ' . ucfirst($room_status) . '. Cannot check-in.';
    header('Location: arrivals.php?error=' . urlencode($error_msg));
    exit;
}

// ===== UPDATE RESERVATION =====
$conn->query("UPDATE reservations SET status = 'Checked-In' WHERE res_id = $res_id");

// ===== UPDATE ROOM =====
if ($room_id > 0) {
    $conn->query("UPDATE rooms SET status = 'occupied' WHERE room_id = $room_id");
}

// ===== SEND EMAIL IN BACKGROUND (No delay) =====
$guest = $conn->query("SELECT guest_name, email FROM reservations WHERE res_id = $res_id")->fetch_assoc();

$email_status = 'noemail';
if ($guest && !empty($guest['email']) && filter_var($guest['email'], FILTER_VALIDATE_EMAIL)) {
    // Get full data for email
    $full = $conn->query("SELECT r.*, rm.room_number, rm.room_type FROM reservations r LEFT JOIN rooms rm ON r.room_id = rm.room_id WHERE r.res_id = $res_id");
    $full_data = $full->fetch_assoc();
    
    // Send email using fsockopen (NO WAIT)
    $email_params = http_build_query([
        'email' => $guest['email'],
        'name' => $guest['guest_name'],
        'res_id' => $res_id
    ]);
    
    $fp = fsockopen($_SERVER['HTTP_HOST'], 80, $errno, $errstr, 1);
    if ($fp) {
        $out = "GET /hotel_management_structure/send_email_background.php?$email_params HTTP/1.1\r\n";
        $out .= "Host: " . $_SERVER['HTTP_HOST'] . "\r\n";
        $out .= "Connection: Close\r\n\r\n";
        fwrite($fp, $out);
        fclose($fp);
        $email_status = 'sent_background';
    } else {
        $email_status = 'failed';
    }
}

// ===== REDIRECT IMMEDIATELY =====
header('Location: arrivals.php?success=checked_in&email=' . $email_status);
exit;
?>