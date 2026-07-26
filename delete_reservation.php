<?php
include 'includes/session_check.php';
include 'includes/db.php';

if (isset($_GET['id'])) {
    $res_id = intval($_GET['id']);
    
    // ඩේටාබේස් එකෙන් සම්පූර්ණයෙන්ම ඉවත් කිරීම
    $delete = $conn->query("DELETE FROM reservations WHERE res_id = $res_id");
    
    if ($delete) {
        header("Location: arrivals.php?success=deleted");
        exit();
    }
}
header("Location: arrivals.php?error=delete_failed");
exit();
?>