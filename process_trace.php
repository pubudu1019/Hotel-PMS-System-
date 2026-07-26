<?php
include 'includes/session_check.php';
include 'includes/db.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$redirect_status = isset($_GET['status']) ? $_GET['status'] : 'Pending';

if ($action === 'add' && isset($_POST['description'])) {
    $trace_date   = mysqli_real_escape_string($conn, $_POST['trace_date']);
    $department   = mysqli_real_escape_string($conn, $_POST['department']);
    $description  = mysqli_real_escape_string($conn, $_POST['description']);
    $assigned_to  = mysqli_real_escape_string($conn, $_POST['assigned_to']);
    $res_id       = (!empty($_POST['res_id'])) ? (int)$_POST['res_id'] : null;
    $created_by   = isset($_SESSION['username']) ? $_SESSION['username'] : 'System';

    $res_id_sql = $res_id !== null ? $res_id : "NULL";

    $sql = "INSERT INTO traces (res_id, department, trace_date, description, assigned_to, status, created_by)
            VALUES ($res_id_sql, '$department', '$trace_date', '$description', '$assigned_to', 'Pending', '$created_by')";

    if ($conn->query($sql)) {
        header("Location: manage_traces.php?status=Pending");
        exit();
    } else {
        echo "Database Error: " . $conn->error;
    }

} elseif ($action === 'complete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE traces SET status='Completed' WHERE trace_id = $id");
    header("Location: manage_traces.php?status=$redirect_status");
    exit();

} elseif ($action === 'cancel' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE traces SET status='Cancelled' WHERE trace_id = $id");
    header("Location: manage_traces.php?status=$redirect_status");
    exit();

} elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM traces WHERE trace_id = $id");
    header("Location: manage_traces.php?status=$redirect_status");
    exit();

} else {
    header("Location: manage_traces.php");
    exit();
}
?>