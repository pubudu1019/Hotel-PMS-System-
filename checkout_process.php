<?php
include 'includes/session_check.php';
include 'includes/db.php';

// ===== HELPER FUNCTION: Get folio balance =====
function get_folio_balance($res_id, $conn) {
    $balance = 0;
    $trans_query = $conn->query("
        SELECT trans_type, amount FROM folio_transactions 
        WHERE res_id = $res_id AND status = 'active'
    ");
    while ($t = $trans_query->fetch_assoc()) {
        switch($t['trans_type']) {
            case 'charge':   $balance += $t['amount']; break;
            case 'payment':  $balance -= $t['amount']; break;
            case 'advance':  $balance -= $t['amount']; break;
            case 'rebate':   $balance += $t['amount']; break;
        }
    }
    return $balance;
}
// =============================================

if (isset($_GET['id'])) {
    $res_id = intval($_GET['id']);
    
    // 1. මුලින්ම මේ Reservation එක තියෙනවද කියලා බලනවා
    $res_query = $conn->query("SELECT room_id, status FROM reservations WHERE res_id = $res_id");
    
    if ($res_query && $res_query->num_rows > 0) {
        $res_data = $res_query->fetch_assoc();
        $room_id = $res_data['room_id'];
        $current_status = $res_data['status'];
        
        // 2. Reservation එක Checked-In status එකේ නැත්නම් check-out කරන්න බෑ
        if ($current_status !== 'Checked-In') {
            header("Location: inhouse_rooms.php?error=not_checked_in");
            exit();
        }
        
        // ===== 🔥 NEW: Check folio balance =====
        $balance = get_folio_balance($res_id, $conn);
        if ($balance != 0) {
            // Balance not zero - prevent checkout
            header("Location: inhouse_rooms.php?error=balance_not_zero&balance=" . urlencode(number_format($balance, 2)));
            exit();
        }
        // =======================================
        
        // 3. Reservation status එක 'Checked-Out' බවට update කිරීම
        $update_res = $conn->query("UPDATE reservations SET status = 'Checked-Out' WHERE res_id = $res_id");
        
        // 4. Room status එක 'cleaning' බවට පත් කිරීම
        if (!empty($room_id) && $update_res) {
            $conn->query("UPDATE rooms SET status = 'cleaning' WHERE room_id = $room_id");
        }
        
        // සාර්ථකව නිම වූ පසු කෙලින්ම In-House පිටුවට යනවා
        header("Location: inhouse_rooms.php?success=checkedout");
        exit();
    } else {
        header("Location: inhouse_rooms.php?error=notfound");
        exit();
    }
} else {
    header("Location: inhouse_rooms.php?error=invalid_id");
    exit();
}
?>