<?php
include 'includes/session_check.php';
include 'includes/db.php';

// ================================================================
// ===== SINGLE CHECK-IN =====
// ================================================================
if (isset($_GET['checkin_id']) && is_numeric($_GET['checkin_id'])) {
    $res_id = intval($_GET['checkin_id']);
    
    // ===== GET RESERVATION WITH ROOM DETAILS =====
    $res_q = $conn->query("
        SELECT r.*, rm.room_number, rm.status as room_status 
        FROM reservations r 
        JOIN rooms rm ON r.room_id = rm.room_id 
        WHERE r.res_id = $res_id AND r.status = 'Pending'
    ");
    
    if ($res_q && $res_q->num_rows > 0) {
        $res_data = $res_q->fetch_assoc();
        $room_id = $res_data['room_id'];
        $room_number = $res_data['room_number'];
        $room_status = $res_data['room_status'];
        $guest_name = $res_data['guest_name'];
        $room_rate = $res_data['room_rate'] ?? 0;
        $check_in = $res_data['check_in'];
        $check_out = $res_data['check_out'];
        $user_id = $_SESSION['user_id'] ?? 0;
        $username = $_SESSION['username'] ?? 'System';
        
        // ================================================================
        // ===== ROOM STATUS CHECK =====
        // ================================================================
        if ($room_status !== 'available') {
            $status_map = array(
                'occupied' => '🔴 Occupied (Guest in room)',
                'cleaning' => '🟡 Cleaning / Dirty (Not ready)',
                'maintenance' => '⚫ Maintenance (Out of order)'
            );
            $status_text = isset($status_map[$room_status]) ? $status_map[$room_status] : ucfirst($room_status);
            
            // Show JavaScript Alert
            echo "<script>
                alert('❌ Room $room_number is NOT available for check-in!\\n\\nCurrent Status: $status_text\\n\\nPlease ensure the room is clean (available) before check-in.');
                window.history.back();
            </script>";
            exit();
        }
        
        // ================================================================
        // ===== PROCEED WITH CHECK-IN =====
        // ================================================================
        
        // 1. Update reservation status
        $conn->query("UPDATE reservations SET status = 'Checked-In' WHERE res_id = $res_id");
        
        // 2. Update room status
        $conn->query("UPDATE rooms SET status = 'occupied' WHERE room_id = $room_id");
        
        // 3. Room status history
        $conn->query("INSERT INTO room_status_history 
                      (room_id, old_status, new_status, changed_by, changed_at) 
                      VALUES ($room_id, 'available', 'occupied', '$username', NOW())");
        
        // 4. Update room rate
        if ($room_rate > 0) {
            $conn->query("UPDATE reservations SET room_rate = $room_rate WHERE res_id = $res_id");
        }
        
        // 5. Auto add room charge
        if ($room_rate > 0) {
            $checkin_date = new DateTime($check_in);
            $checkout_date = new DateTime($check_out);
            $nights = $checkin_date->diff($checkout_date)->days;
            if ($nights == 0) {
                $nights = 1;
            }
            
            $total_charge = $room_rate * $nights;
            $desc = "Room Charge (" . $nights . " nights @ LKR " . number_format($room_rate, 2) . ")";
            
            $conn->query("INSERT INTO folio_transactions 
                          (res_id, amount, description, trans_type, reference_no, created_by, status) 
                          VALUES ($res_id, $total_charge, '$desc', 'charge', 'AUTO', $user_id, 'active')");
        }
        
        // 6. Audit log
        $conn->query("INSERT INTO audit_logs (user_id, username, action, description) 
                      VALUES ($user_id, '$username', 'CHECKIN', 
                      'Checked in $guest_name to Room $room_number')");
        
        // 7. Success - JavaScript Alert
        echo "<script>
            alert('✅ Check-in Successful!\\n\\nGuest: $guest_name\\nRoom: $room_number');
            window.location.href = 'inhouse_rooms.php';
        </script>";
        exit();
        
    } else {
        echo "<script>
            alert('❌ Reservation not found or already checked in.');
            window.location.href = 'arrivals.php';
        </script>";
        exit();
    }
}

// ================================================================
// ===== IF DIRECT ACCESS =====
// ================================================================
header("Location: arrivals.php");
exit();
?>