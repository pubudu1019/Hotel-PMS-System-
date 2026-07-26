<?php
include 'includes/session_check.php';
include 'includes/db.php';

// ආරක්ෂක පියවර: Admin කෙනෙකුට පමණක් අවසර දීම
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: Only Admins can access this page.");
}

$message = "";

// User Add Logic
if (isset($_POST['add_user'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $username  = mysqli_real_escape_string($conn, $_POST['username']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT); // ඉතාම ආරක්ෂිතයි
    $role      = $_POST['role'];

    // Username එක දැනටමත් පවතීදැයි පරීක්ෂා කිරීම
    $check = $conn->query("SELECT user_id FROM users WHERE username = '$username'");
    
    if ($check->num_rows > 0) {
        $message = "<div class='alert alert-danger'>Username already exists!</div>";
    } else {
        $sql = "INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $full_name, $username, $password, $role);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>User created successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
    }
}
?>

<div class="panel">
    <h5 class="text-white mb-3">Add New Staff</h5>
    <?= $message ?>
    <form method="POST">
        <input type="text" name="full_name" class="form-control mb-2" placeholder="Full Name" required>
        <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
        <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
        <select name="role" class="form-control mb-3">
            <option value="receptionist">Receptionist</option>
            <option value="manager">Manager</option>
            <option value="admin">Admin</option>
        </select>
        <button type="submit" name="add_user" class="btn btn-warning w-100">Create New Staff Member</button>
    </form>
</div>