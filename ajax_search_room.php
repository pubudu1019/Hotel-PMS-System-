<?php
include 'includes/db.php';
header('Content-Type: application/json');

$q = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit();
}

$sql = "SELECT r.room_id, r.room_number, r.room_type, r.status, 
               res.res_id, res.guest_name 
        FROM rooms r 
        LEFT JOIN reservations res ON r.room_id = res.room_id AND res.status = 'Checked-In'
        WHERE r.room_number LIKE '%$q%'
        ORDER BY r.room_number ASC
        LIMIT 10";

$result = $conn->query($sql);
$rooms = [];
while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}
echo json_encode($rooms);
?>