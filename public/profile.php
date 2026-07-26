<?php
// public/profile.php - User Profile
session_start();
include '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['online_user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['online_user_id'];
$user_name = $_SESSION['online_user_name'];
$user_email = $_SESSION['online_user_email'];
$user_type = $_SESSION['online_user_type'];

$success = '';
$error = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM online_users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $country = isset($_POST['country']) ? trim($_POST['country']) : '';
    
    if (!empty($full_name)) {
        $update = $conn->prepare("UPDATE online_users SET full_name = ?, phone = ?, country = ? WHERE user_id = ?");
        $update->bind_param("sssi", $full_name, $phone, $country, $user_id);
        if ($update->execute()) {
            $_SESSION['online_user_name'] = $full_name;
            $success = 'Profile updated successfully!';
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM online_users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $error = 'Failed to update profile.';
        }
        $update->close();
    } else {
        $error = 'Name is required.';
    }
}

// Change password
if (isset($_POST['change_password'])) {
    $current = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($current) || empty($new) || empty($confirm)) {
        $error = 'Please fill in all password fields.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } else {
        // Verify current password
        if (password_verify($current, $user['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE online_users SET password = ? WHERE user_id = ?");
            $update->bind_param("si", $hashed, $user_id);
            if ($update->execute()) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password.';
            }
            $update->close();
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo $hotel_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #0d4b68; --gold: #fbbf24; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f8; }
        
        .navbar-custom {
            background: linear-gradient(135deg, #082f42, #0d4b68);
            padding: 12px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .navbar-custom .brand {
            color: var(--gold);
            font-size: 22px;
            font-weight: 800;
            text-decoration: none;
        }
        .navbar-custom .brand i { color: var(--gold); }
        .navbar-custom .brand-sub {
            color: rgba(255,255,255,0.6);
            font-size: 10px;
            display: block;
            letter-spacing: 0.5px;
        }
        .navbar-custom .nav-link {
            color: rgba(255,255,255,0.8);
            font-weight: 600;
            font-size: 14px;
            padding: 8px 18px;
            transition: 0.3s;
        }
        .navbar-custom .nav-link:hover { color: var(--gold); }
        .btn-logout {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 6px 18px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-logout:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }
        
        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid #eef2f5;
        }
        .profile-card .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--gold);
            color: #082f42;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            margin: 0 auto 15px;
        }
        .profile-card .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            transition: 0.3s;
        }
        .profile-card .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(251,191,36,0.2);
        }
        .profile-card .form-label {
            font-weight: 600;
            color: #475569;
            font-size: 13px;
        }
        .btn-save {
            background: var(--primary);
            color: white;
            padding: 10px 30px;
            border-radius: 25px;
            border: none;
            font-weight: 700;
            transition: 0.3s;
        }
        .btn-save:hover {
            background: #082f42;
            transform: translateY(-2px);
        }
        
        .footer { background: #0a1828; color: rgba(255,255,255,0.7); padding: 30px 0 15px; margin-top: 40px; }
        .footer .brand { color: var(--gold); font-size: 20px; font-weight: 800; }
        .footer a { color: rgba(255,255,255,0.6); text-decoration: none; transition: 0.3s; }
        .footer a:hover { color: var(--gold); }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; margin-top: 20px; font-size: 13px; }
        
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .form-control {
            padding-right: 50px;
        }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            transition: 0.3s;
        }
        .password-toggle:hover { color: var(--primary); }
        
        @media (max-width: 768px) {
            .profile-card { padding: 20px; }
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a href="index.php" class="brand">
            <i class="fas fa-hotel me-1"></i> ARALIYA
            <span class="brand-sub">Beach Resort &amp; Spa Unawatuna</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home me-1"></i> Home</a></li>
                <li class="nav-item"><a href="my_bookings.php" class="nav-link"><i class="fas fa-list me-1"></i> My Bookings</a></li>
                <li class="nav-item"><a href="rooms.php" class="nav-link"><i class="fas fa-search me-1"></i> Book Now</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link active"><i class="fas fa-user me-1"></i> Profile</a></li>
                <li class="nav-item"><a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== MAIN CONTENT ===== -->
<section class="py-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h4 class="fw-bold mb-4" style="color: #082f42;">
                    <i class="fas fa-user-circle me-2" style="color: var(--gold);"></i> My Profile
                </h4>

                <?php if(!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- ===== PROFILE CARD ===== -->
                <div class="profile-card">
                    <div class="text-center">
                        <div class="avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
                        <h5 class="fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge bg-primary"><?php echo ucfirst($user['user_type']); ?></span>
                    </div>

                    <hr>

                    <!-- ===== UPDATE PROFILE FORM ===== -->
                    <form method="POST" action="">
                        <h6 class="fw-bold mb-3"><i class="fas fa-edit me-2" style="color: var(--gold);"></i> Edit Profile</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-user me-2"></i> Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-envelope me-2"></i> Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-phone me-2"></i> Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="0712345678">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-globe me-2"></i> Country</label>
                                <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>" placeholder="Sri Lanka">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn-save"><i class="fas fa-save me-2"></i> Update Profile</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ===== CHANGE PASSWORD ===== -->
                <div class="profile-card mt-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-lock me-2" style="color: var(--gold);"></i> Change Password</h6>
                    <form method="POST" action="">
                        <input type="hidden" name="change_password" value="1">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Current Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="current_password" id="current_pass" class="form-control" placeholder="Enter current password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('current_pass', 'current_icon')">
                                        <i class="fas fa-eye" id="current_icon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="new_password" id="new_pass" class="form-control" placeholder="Min 6 characters">
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_pass', 'new_icon')">
                                        <i class="fas fa-eye" id="new_icon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="confirm_password" id="confirm_pass" class="form-control" placeholder="Confirm new password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_pass', 'confirm_icon')">
                                        <i class="fas fa-eye" id="confirm_icon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn-save" style="background: #f59e0b;"><i class="fas fa-key me-2"></i> Change Password</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ===== ACCOUNT INFO ===== -->
                <div class="profile-card mt-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2" style="color: var(--gold);"></i> Account Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Account Type:</strong> <?php echo ucfirst($user['user_type']); ?></p>
                            <p class="mb-1"><strong>Joined:</strong> <?php echo date('d M Y', strtotime($user['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Last Login:</strong> <?php echo isset($user['last_login']) ? date('d M Y h:i A', strtotime($user['last_login'])) : 'Never'; ?></p>
                            <p class="mb-1"><strong>Verified:</strong> <?php echo $user['is_verified'] ? '✅ Yes' : '❌ No'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="brand">ARALIYA</div>
                <p style="font-size:13px;">Beach Resort &amp; Spa Unawatuna</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="index.php">Home</a> | 
                <a href="rooms.php">Rooms</a> | 
                <a href="logout.php">Logout</a>
            </div>
        </div>
        <div class="footer-bottom text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo $hotel_name; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
function togglePassword(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>