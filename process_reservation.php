<?php
// ඩේටාබේස් සම්බන්ධතාවය එකතු කිරීම
include 'includes/db.php';

if (isset($_POST['save_reservation'])) {
    
    // ===== NEW: Get created_by from session =====
    session_start();
    $created_by = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : NULL;
    
    // 1. Step 2: Guest Details
    $guest_name  = mysqli_real_escape_string($conn, $_POST['guest_name']);
    $gender      = mysqli_real_escape_string($conn, $_POST['gender']);
    $passport    = mysqli_real_escape_string($conn, $_POST['passport_no']);
    $nationality = mysqli_real_escape_string($conn, $_POST['nationality']);
    $email       = mysqli_real_escape_string($conn, $_POST['email']);
    $mobile      = mysqli_real_escape_string($conn, $_POST['mobile']);

    // 2. Step 1: Stay & Accommodation Details
    $room_id               = (int)$_POST['room_id'];
    $check_in              = mysqli_real_escape_string($conn, $_POST['check_in']);
    $check_out             = mysqli_real_escape_string($conn, $_POST['check_out']);
    $num_of_nights         = (int)$_POST['num_of_nights'];
    $expected_arrival      = mysqli_real_escape_string($conn, $_POST['expected_arrival_time']);
    $expected_departure    = mysqli_real_escape_string($conn, $_POST['expected_departure_time']);
    
    // 3. Step 3: Company & Profile Details
    $booking_source   = isset($_POST['booking_source']) ? mysqli_real_escape_string($conn, $_POST['booking_source']) : 'Direct';
    $company_name     = isset($_POST['company_name']) ? mysqli_real_escape_string($conn, $_POST['company_name']) : 'INDIVIDUAL / FIT';
    $rate_code        = isset($_POST['rate_code']) ? mysqli_real_escape_string($conn, $_POST['rate_code']) : 'AB - FIT LOCAL';
    $package_name     = isset($_POST['package_name']) ? mysqli_real_escape_string($conn, $_POST['package_name']) : 'STANDARD';
    $market_segment   = isset($_POST['market_segment']) ? mysqli_real_escape_string($conn, $_POST['market_segment']) : '';
    $business_segment = isset($_POST['business_segment']) ? mysqli_real_escape_string($conn, $_POST['business_segment']) : '';
    $sales_person     = isset($_POST['sales_person']) ? mysqli_real_escape_string($conn, $_POST['sales_person']) : 'NA';
    $voucher_no       = isset($_POST['voucher_no']) ? mysqli_real_escape_string($conn, $_POST['voucher_no']) : '';
    $tour_no          = isset($_POST['tour_no']) ? mysqli_real_escape_string($conn, $_POST['tour_no']) : '';

    // 4. Step 4: Meal Plans & Confirmation Details
    $meal_plan          = isset($_POST['meal_plan']) ? mysqli_real_escape_string($conn, $_POST['meal_plan']) : 'Room Only';
    $arrive_for         = isset($_POST['arrive_for']) ? mysqli_real_escape_string($conn, $_POST['arrive_for']) : '';
    $leave_after        = isset($_POST['leave_after']) ? mysqli_real_escape_string($conn, $_POST['leave_after']) : '';
    $guest_payment_mode = isset($_POST['guest_payment_mode']) ? mysqli_real_escape_string($conn, $_POST['guest_payment_mode']) : 'Guest';
    $visit_purpose      = isset($_POST['visit_purpose']) ? mysqli_real_escape_string($conn, $_POST['visit_purpose']) : 'LEISURE';
    
    // Checkboxes
    $bill_payment_lkr_sscl = isset($_POST['bill_payment_lkr_sscl']) ? 1 : 0;
    $company_credit_enable = isset($_POST['company_credit_enable']) ? 1 : 0;
    
    $special_req = isset($_POST['special_requests']) ? mysqli_real_escape_string($conn, $_POST['special_requests']) : '';

    // Default Values
    $adults   = 1;
    $children = 0;

    // --- Room Validation ---
    if ($room_id <= 0) {
        die("<script>alert('කරුණාකර වලංගු කාමරයක් තෝරන්න!'); window.history.back();</script>");
    }

    $check_room = $conn->query("SELECT room_id FROM rooms WHERE room_id = $room_id");
    if ($check_room->num_rows === 0) {
        die("<script>alert('ඔබ තෝරාගත් Room ID ($room_id) එක නොපවතී.'); window.history.back();</script>");
    }

    // ===== INSERT RESERVATION =====
    $sql = "INSERT INTO reservations (
                guest_name, passport_no, email, mobile, gender, nationality, 
                room_id, check_in, check_out, num_of_nights, expected_arrival_time, expected_departure_time, 
                adults, children, booking_source, company_name, rate_code, package_name, 
                market_segment, business_segment, sales_person, voucher_no, tour_no, 
                meal_plan, arrive_for, leave_after, guest_payment_mode, visit_purpose, 
                bill_payment_lkr_sscl, company_credit_enable, special_requests, status,
                created_by
            ) VALUES (
                '$guest_name', '$passport', '$email', '$mobile', '$gender', '$nationality', 
                $room_id, '$check_in', '$check_out', $num_of_nights, '$expected_arrival', '$expected_departure', 
                $adults, $children, '$booking_source', '$company_name', '$rate_code', '$package_name', 
                '$market_segment', '$business_segment', '$sales_person', '$voucher_no', '$tour_no', 
                '$meal_plan', '$arrive_for', '$leave_after', '$guest_payment_mode', '$visit_purpose', 
                $bill_payment_lkr_sscl, $company_credit_enable, '$special_req', 'Pending',
                $created_by
            )";
    
    if ($conn->query($sql)) {
        $res_id = $conn->insert_id;
        
        // Update room status
        $conn->query("UPDATE rooms SET status='occupied' WHERE room_id=$room_id");
        
        // ============================================================
        // 🔥 AUTO ADD ROOM RATE (FULLY FIXED + DEBUG LOGGING)
        // ============================================================
        $log_file = 'rate_set_log.txt';
        file_put_contents($log_file, "\n" . date('Y-m-d H:i:s') . " ===== START: Res $res_id, Room $room_id =====\n", FILE_APPEND);
        
        if ($room_id > 0 && $res_id > 0) {
            
            // Step 1: Get room type from rooms table
            $room_query = $conn->query("SELECT room_type FROM rooms WHERE room_id = $room_id");
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Room query executed\n", FILE_APPEND);
            
            if ($room_query && $room = $room_query->fetch_assoc()) {
                $room_type = trim($room['room_type']);
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Room type found: '$room_type'\n", FILE_APPEND);
                
                $rate = 0;
                
                // Step 2: Get all room types to find match
                if (!empty($room_type)) {
                    $escaped_type = mysqli_real_escape_string($conn, $room_type);
                    
                    // Try Exact Match
                    $rate_query = $conn->query("
                        SELECT base_price FROM room_types 
                        WHERE type_name = '$escaped_type' 
                           OR short_code = '$escaped_type'
                        LIMIT 1
                    ");
                    if ($rate_query && $rate_row = $rate_query->fetch_assoc()) {
                        $rate = $rate_row['base_price'];
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " - EXACT MATCH: Rate = $rate\n", FILE_APPEND);
                    }
                    
                    // If no exact match, try LIKE match
                    if ($rate <= 0) {
                        $rate_query2 = $conn->query("
                            SELECT base_price FROM room_types 
                            WHERE type_name LIKE '%$escaped_type%' 
                               OR '$escaped_type' LIKE CONCAT('%', type_name, '%')
                            LIMIT 1
                        ");
                        if ($rate_query2 && $rate_row2 = $rate_query2->fetch_assoc()) {
                            $rate = $rate_row2['base_price'];
                            file_put_contents($log_file, date('Y-m-d H:i:s') . " - LIKE MATCH: Rate = $rate\n", FILE_APPEND);
                        }
                    }
                    
                    // If still no match, try by short_code
                    if ($rate <= 0) {
                        $rate_query3 = $conn->query("
                            SELECT base_price FROM room_types 
                            WHERE short_code LIKE '%$escaped_type%'
                            LIMIT 1
                        ");
                        if ($rate_query3 && $rate_row3 = $rate_query3->fetch_assoc()) {
                            $rate = $rate_row3['base_price'];
                            file_put_contents($log_file, date('Y-m-d H:i:s') . " - SHORT_CODE MATCH: Rate = $rate\n", FILE_APPEND);
                        }
                    }
                }
                
                // Step 3: Fallback - use first room type's price
                if ($rate <= 0) {
                    $fallback = $conn->query("SELECT base_price FROM room_types LIMIT 1");
                    if ($fallback && $fb = $fallback->fetch_assoc()) {
                        $rate = $fb['base_price'];
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " - FALLBACK: Rate = $rate\n", FILE_APPEND);
                    } else {
                        // Ultimate fallback
                        $rate = 5000;
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " - ULTIMATE FALLBACK: Rate = 5000\n", FILE_APPEND);
                    }
                }
                
                // Step 4: Update reservation with room_rate
                if ($rate > 0) {
                    $update = $conn->query("UPDATE reservations SET room_rate = $rate WHERE res_id = $res_id");
                    if ($update) {
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " ✅ SUCCESS: Room rate set to $rate for Res $res_id\n", FILE_APPEND);
                    } else {
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " ❌ UPDATE FAILED: " . $conn->error . "\n", FILE_APPEND);
                    }
                } else {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " ❌ NO RATE FOUND (rate = 0)\n", FILE_APPEND);
                }
                
            } else {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " ❌ Room not found for room_id = $room_id\n", FILE_APPEND);
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Query error: " . $conn->error . "\n", FILE_APPEND);
            }
        } else {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " ❌ Invalid room_id ($room_id) or res_id ($res_id)\n", FILE_APPEND);
        }
        file_put_contents($log_file, date('Y-m-d H:i:s') . " ===== END =====\n", FILE_APPEND);
        // ============================================================
        
        header("Location: print_grc.php?id=$res_id");
        exit();
    } else {
        echo "Database Error: " . $conn->error;
    }
} else {
    header("Location: add_reservation.php");
    exit();
}
?>