<?php
include 'includes/session_check.php';
include 'includes/db.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'add' && isset($_POST['company_name'])) {
    $company_name  = mysqli_real_escape_string($conn, $_POST['company_name']);
    $room_type     = mysqli_real_escape_string($conn, $_POST['room_type']);
    $date_from     = mysqli_real_escape_string($conn, $_POST['date_from']);
    $date_to       = mysqli_real_escape_string($conn, $_POST['date_to']);
    $release_date  = mysqli_real_escape_string($conn, $_POST['release_date']);
    $total_blocked = (int)$_POST['total_blocked'];
    $notes         = mysqli_real_escape_string($conn, $_POST['notes']);

    if ($total_blocked <= 0) {
        die("<script>alert('Rooms blocked count එක 0ට වඩා වැඩි විය යුතුයි!'); window.history.back();</script>");
    }

    $sql = "INSERT INTO allotments (company_name, room_type, total_blocked, date_from, date_to, release_date, notes, status)
            VALUES ('$company_name', '$room_type', $total_blocked, '$date_from', '$date_to', '$release_date', '$notes', 'Active')";

    if ($conn->query($sql)) {
        header("Location: manage_allotments.php?success=added");
        exit();
    } else {
        echo "Database Error: " . $conn->error;
    }

} elseif ($action === 'release' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE allotments SET status='Released' WHERE allotment_id = $id");
    header("Location: manage_allotments.php?success=released");
    exit();

} else {
    header("Location: manage_allotments.php");
    exit();
}
?>