<?php
session_start();
include 'includes/db.php';

// 1. POST රික්වෙස්ට් එකක් නොවේ නම් ඉන්ඩෙක්ස් පිටුවට හරවා යැවීම
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// 2. හිස් දත්ත ඇත්නම් දෝෂයක් පෙන්වීම
if (empty($username) || empty($password)) {
    header("Location: index.php?error=1");
    exit();
}

$username_safe = $conn->real_escape_string($username);

// 3. ඩේටාබේස් එකෙන් පරිශීලකයා සෙවීම
$result = $conn->query("
    SELECT user_id, full_name, username, password, role
    FROM users
    WHERE username = '$username_safe'
    LIMIT 1
");

if (!$result || $result->num_rows === 0) {
    header("Location: index.php?error=1");
    exit();
}

// ඩේටාබේස් එකෙන් ආපු Row එක Array එකක් විදිහට ගැනීම
$user = $result->fetch_assoc();

// 4. 🔐 මැනේජ් යූසර්ස් පිටුවෙන් හෑෂ් (Hash) කරපු පාස්වර්ඩ් එක මෙතැනින් නිවැරදිව සසඳා බැලීම
if (password_verify($password, $user['password'])) {

    // 5. සෙෂන් (Session) එකට දත්ත ඇතුළත් කිරීම
    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = strtolower($user['role']); // 'admin', 'manager', 'receptionist' ලෙස සේව් වේ
    $_SESSION['logged_in'] = true;

    // 6. 📝 ආරක්ෂිතව Audit Log එකට දත්ත ඇතුළත් කිරීම (TypeError එක මඟහරවා ඇත)
    $u_id   = $_SESSION['user_id'];
    $u_name = $_SESSION['username'];
    
    $conn->query("INSERT INTO audit_logs (user_id, username, action, description) 
                  VALUES ('$u_id', '$u_name', 'LOGIN', 'User successfully logged in from login page')");

    // 7. ඩෑෂ්බෝඩ් එකට යැවීම
    header("Location: dashboard.php");
    exit();

} else {
    // පාස්වර්ඩ් එක වැරදි නම්
    header("Location: index.php?error=1");
    exit();
}
?>