<?php
// cron_job.php - Send Queued Emails (Run via cron every minute)
// URL: http://localhost/hotel_management_structure/cron_job.php

include 'includes/db.php';
include 'includes/email_functions.php';

// Get pending emails (limit 10 per run)
$queue = $conn->query("SELECT * FROM email_queue WHERE status = 'pending' LIMIT 10");

while ($item = $queue->fetch_assoc()) {
    // Get reservation data
    $res = $conn->query("SELECT r.*, rm.room_number, rm.room_type FROM reservations r LEFT JOIN rooms rm ON r.room_id = rm.room_id WHERE r.res_id = " . $item['reservation_id']);
    $data = $res->fetch_assoc();
    
    if ($data) {
        // Send email
        $result = sendWelcomeEmail($item['recipient_email'], $item['recipient_name'], $data);
        
        // Update queue status
        $status = $result ? 'sent' : 'failed';
        $conn->query("UPDATE email_queue SET status = '$status' WHERE id = " . $item['id']);
        
        // Log
        error_log("Queue email: $item[recipient_email] - $status");
    }
}

echo "✅ Queued emails processed";
?>