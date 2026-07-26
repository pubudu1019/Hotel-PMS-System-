<?php
include 'includes/session_check.php';
include 'includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$res_id = intval($_POST['res_id']);
$trans_type = mysqli_real_escape_string($conn, $_POST['trans_type']);
$amount = floatval($_POST['amount']);
$description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
$reference_no = mysqli_real_escape_string($conn, $_POST['reference_no'] ?? '');
$created_by = $_SESSION['user_id'] ?? 0;

if ($res_id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid data provided']);
    exit();
}

$valid_types = ['charge', 'payment', 'advance', 'rebate', 'void'];
if (!in_array($trans_type, $valid_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid transaction type']);
    exit();
}

$insert = "INSERT INTO folio_transactions 
            (res_id, amount, description, trans_type, reference_no, created_by, status) 
           VALUES 
            ($res_id, $amount, '$description', '$trans_type', '$reference_no', $created_by, 'active')";

if ($conn->query($insert)) {
    $transaction_id = $conn->insert_id;
    
    $user_id = $_SESSION['user_id'] ?? 0;
    $username = $_SESSION['username'] ?? 'System';
    $log = "INSERT INTO audit_logs (user_id, username, action, description) 
            VALUES ($user_id, '$username', 'FOLIO_POST', 'Posted $trans_type of LKR $amount for reservation #$res_id')";
    $conn->query($log);
    
    echo json_encode([
        'success' => true, 
        'res_id' => $res_id, 
        'transaction_id' => $transaction_id,
        'message' => 'Transaction posted successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?>