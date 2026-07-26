<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pms_db"; // මෙතන හරියටම pms_db කියලා තියෙන්න ඕනේ

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>