<?php
include 'includes/db.php';
$res_id = $_GET['id'];

if(isset($_POST['allocate_btn'])) {
    $room_id = $_POST['room_id'];
    $conn->query("UPDATE reservations SET room_id=$room_id WHERE res_id=$res_id");
    $conn->query("UPDATE rooms SET status='occupied' WHERE room_id=$room_id");
    header("Location: arrivals.php");
}
?>

<form method="POST">
    <select name="room_id" class="form-control" required>
        <?php
        $rooms = $conn->query("SELECT * FROM rooms WHERE status='available'");
        while($r = $rooms->fetch_assoc()) {
            echo "<option value='".$r['room_id']."'>Room ".$r['room_number']."</option>";
        }
        ?>
    </select>
    <button type="submit" name="allocate_btn" class="btn btn-success mt-2">Assign Room</button>
</form>