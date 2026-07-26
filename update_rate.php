<?php
include 'includes/session_check.php';
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['res_id'], $_POST['new_rate'])) {
    $res_id = intval($_POST['res_id']);
    $new_rate = floatval($_POST['new_rate']);
    if ($res_id > 0 && $new_rate >= 0) {
        $conn->query("UPDATE reservations SET room_rate = $new_rate WHERE res_id = $res_id");
        $user_id = $_SESSION['user_id'] ?? 0;
        $username = $_SESSION['username'] ?? 'System';
        $conn->query("INSERT INTO audit_logs (user_id, username, action, description) VALUES ($user_id, '$username', 'RATE_UPDATE', 'Updated rate for reservation #$res_id to LKR $new_rate')");
        header("Location: quick_checkin.php?success=rate_updated");
    } else {
        header("Location: quick_checkin.php?error=invalid+rate");
    }
} else {
    header("Location: quick_checkin.php");
}
exit();
?>