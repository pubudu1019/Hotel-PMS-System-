<?php
include '../includes/session_check.php';
include '../includes/db.php';

header('Content-Type: application/json');

$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if (!in_array($user_role, ['admin', 'manager', 'receptionist', 'housekeeping'])) {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$type = $_GET['type'] ?? '';
$item_id = intval($_POST['item_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);
$created_by = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'System';

if ($item_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid item or quantity']);
    exit();
}

// Get item price
$price_q = $conn->query("SELECT item_name, price FROM laundry_items WHERE item_id = $item_id");
if (!$price_q || $price_q->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Laundry item not found']);
    exit();
}
$item = $price_q->fetch_assoc();
$price = $item['price'];
$total = $price * $quantity;

$conn->begin_transaction();

try {
    if ($type == 'walkin') {
        $name = mysqli_real_escape_string($conn, $_POST['walkin_name'] ?? '');
        if (empty($name)) throw new Exception("Walk-in customer name is required");
        
        $insert = $conn->query("INSERT INTO laundry_orders 
                                 (order_type, walk_in_name, total_amount, posted_at, order_status) 
                                 VALUES ('Walk-In', '$name', $total, NOW(), 'Pending')");
        if (!$insert) throw new Exception("Failed to create order: " . $conn->error);
        $order_id = $conn->insert_id;
        
    } else { // Room
        $room_id = intval($_POST['room_id'] ?? 0);
        if ($room_id <= 0) throw new Exception("Room is required");
        
        $res_q = $conn->query("SELECT res_id FROM reservations WHERE room_id = $room_id AND status = 'Checked-In' LIMIT 1");
        if ($res_q->num_rows == 0) {
            throw new Exception("No checked-in guest found for this room");
        }
        $res_row = $res_q->fetch_assoc();
        $res_id = $res_row['res_id'];
        
        $insert = $conn->query("INSERT INTO laundry_orders 
                                 (order_type, room_id, res_id, total_amount, posted_at, order_status) 
                                 VALUES ('Room', $room_id, $res_id, $total, NOW(), 'Pending')");
        if (!$insert) throw new Exception("Failed to create order: " . $conn->error);
        $order_id = $conn->insert_id;
        
        // ===== POST TO FOLIO =====
        $desc = "Laundry: " . $item['item_name'] . " x " . $quantity . " @ LKR " . $price;
        $folio_insert = $conn->query("INSERT INTO folio_transactions 
                                       (res_id, amount, description, trans_type, reference_no, created_by, status) 
                                       VALUES ($res_id, $total, '$desc', 'charge', 'LAUNDRY', $created_by, 'active')");
        if (!$folio_insert) throw new Exception("Failed to post to folio: " . $conn->error);
    }
    
    // Insert order items
    $item_insert = $conn->query("INSERT INTO laundry_order_items (order_id, item_id, quantity, price) 
                                  VALUES ($order_id, $item_id, $quantity, $price)");
    if (!$item_insert) throw new Exception("Failed to add items: " . $conn->error);
    
    // Log audit
    $conn->query("INSERT INTO audit_logs (user_id, username, action, description) 
                  VALUES ($created_by, '$username', 'LAUNDRY', 
                  'Laundry order #$order_id - {$item['item_name']} x $quantity = LKR $total')");
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Laundry posted! Total: LKR " . number_format($total, 2),
        'order_id' => $order_id,
        'total' => $total,
        'item' => $item['item_name']
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>