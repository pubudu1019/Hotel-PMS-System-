<?php
include 'includes/session_check.php';
include 'includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$res_id = intval($_POST['res_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);
$card_holder = mysqli_real_escape_string($conn, $_POST['card_holder'] ?? '');
$card_number = preg_replace('/\s/', '', $_POST['card_number'] ?? '');
$expiry_date = mysqli_real_escape_string($conn, $_POST['expiry_date'] ?? '');
$cvv = mysqli_real_escape_string($conn, $_POST['cvv'] ?? '');
$created_by = $_SESSION['user_id'] ?? 0;

// ===== VALIDATION =====
if ($res_id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid reservation or amount']);
    exit();
}

if (empty($card_holder) || strlen($card_holder) < 3) {
    echo json_encode(['success' => false, 'error' => 'Please enter valid card holder name']);
    exit();
}

if (strlen($card_number) < 15 || strlen($card_number) > 16) {
    echo json_encode(['success' => false, 'error' => 'Please enter valid 16-digit card number']);
    exit();
}

if (empty($expiry_date) || strlen($expiry_date) != 5) {
    echo json_encode(['success' => false, 'error' => 'Please enter valid expiry date (MM/YY)']);
    exit();
}

list($month, $year) = explode('/', $expiry_date);
$year = 2000 + intval($year);
$expiry = new DateTime("$year-$month-01");
$now = new DateTime();
if ($expiry < $now) {
    echo json_encode(['success' => false, 'error' => 'Card has expired']);
    exit();
}

if (strlen($cvv) < 3 || strlen($cvv) > 4) {
    echo json_encode(['success' => false, 'error' => 'Please enter valid CVV']);
    exit();
}

// ===== MASK CARD NUMBER FOR LOGGING =====
$masked_card = '****' . substr($card_number, -4);

// ===== CHECK RESERVATION =====
$res_check = $conn->query("SELECT * FROM reservations WHERE res_id = $res_id");
if (!$res_check || $res_check->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Reservation not found']);
    exit();
}

// ===== POST PAYMENT =====
$desc = "Card Payment (" . $masked_card . ", $expiry_date) - " . $card_holder;
$insert = "INSERT INTO folio_transactions 
            (res_id, amount, description, trans_type, reference_no, created_by, status) 
            VALUES ($res_id, $amount, '$desc', 'payment', 'CARD', $created_by, 'active')";

if ($conn->query($insert)) {
    // ===== LOG AUDIT =====
    $username = $_SESSION['username'] ?? 'System';
    $conn->query("INSERT INTO audit_logs (user_id, username, action, description) 
                  VALUES ($created_by, '$username', 'CARD_PAYMENT', 
                  'Card payment of LKR $amount for reservation #$res_id. Card: $masked_card, Holder: $card_holder')");
    
    echo json_encode([
        'success' => true,
        'res_id' => $res_id,
        'amount' => $amount,
        'message' => 'Payment processed successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
}
?>