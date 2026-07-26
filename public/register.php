<?php
// public/register.php - WITH SCROLL
session_start();
include '../includes/db.php';

// If already logged in, redirect
if (isset($_SESSION['online_user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : 'guest';
    
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $check = $conn->prepare("SELECT user_id FROM online_users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = 'This email is already registered. Please login.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO online_users (full_name, email, phone, password, user_type, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssss", $full_name, $email, $phone, $hashed_password, $user_type);
            
            if ($stmt->execute()) {
                $success = '✅ Registration successful! You can now <a href="login.php">login</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
        $check->close();
    }
}

$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
$hotel_address = isset($hotel['address']) ? $hotel['address'] : 'Galle Road, Unawatuna, Sri Lanka';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo $hotel_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #0d4b68; --gold: #fbbf24; --gold-dark: #d97706; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.15);
            animation: slideUp 0.6s ease;
            max-height: 92vh;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .auth-left {
            width: 45%;
            background: linear-gradient(135deg, #082f42, #0d4b68);
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .auth-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 80%;
            height: 200%;
            background: rgba(251,191,36,0.06);
            border-radius: 50%;
            transform: rotate(15deg);
        }
        .auth-left .brand {
            font-size: 32px;
            font-weight: 800;
            color: var(--gold);
            letter-spacing: 1.5px;
            position: relative;
            z-index: 1;
        }
        .auth-left .brand i { color: var(--gold); }
        .auth-left .brand-sub {
            font-size: 13px;
            opacity: 0.7;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        .auth-left h2 {
            font-size: 26px;
            font-weight: 700;
            position: relative;
            z-index: 1;
            margin-bottom: 10px;
        }
        .auth-left h2 span { color: var(--gold); }
        .auth-left p {
            opacity: 0.8;
            font-size: 14px;
            line-height: 1.6;
            position: relative;
            z-index: 1;
            margin-bottom: 15px;
        }
        .auth-left .features {
            position: relative;
            z-index: 1;
        }
        .auth-left .features li {
            list-style: none;
            padding: 4px 0;
            font-size: 13px;
            opacity: 0.85;
        }
        .auth-left .features li i {
            color: var(--gold);
            margin-right: 10px;
            width: 20px;
        }
        .auth-left .footer-text {
            position: relative;
            z-index: 1;
            margin-top: 15px;
            font-size: 12px;
            opacity: 0.5;
        }
        
        .auth-right {
            width: 55%;
            padding: 20px 30px 20px 30px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow-y: auto;
            max-height: 92vh;
        }
        .auth-right::-webkit-scrollbar {
            width: 4px;
        }
        .auth-right::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .auth-right::-webkit-scrollbar-thumb {
            background: var(--gold);
            border-radius: 10px;
        }
        
        .auth-right h4 {
            color: #082f42;
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 2px;
        }
        .auth-right .sub-heading {
            color: #94a3b8;
            font-size: 13px;
            margin-bottom: 12px;
        }
        .auth-right .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 13px;
            transition: 0.3s;
            background: #f8fafc;
        }
        .auth-right .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(251,191,36,0.1);
            background: white;
        }
        .auth-right .form-label {
            font-weight: 600;
            color: #475569;
            font-size: 12px;
            margin-bottom: 3px;
        }
        .auth-right .form-label i { color: var(--gold); width: 16px; }
        .auth-right .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 13px;
            background: #f8fafc;
        }
        .auth-right .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(251,191,36,0.1);
        }
        
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .form-control {
            padding-right: 45px;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            transition: 0.3s;
        }
        .password-toggle:hover { color: var(--primary); }
        
        .btn-register {
            background: linear-gradient(135deg, #0d4b68, #1a6f8e);
            color: white;
            padding: 9px;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            font-size: 15px;
            width: 100%;
            transition: 0.3s;
            margin-top: 5px;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #082f42, #0d4b68);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13,75,104,0.3);
        }
        
        .login-link {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
            font-size: 13px;
        }
        .login-link:hover { color: var(--gold-dark); text-decoration: underline; }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        .divider span {
            padding: 0 14px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            font-size: 12px;
            padding: 6px 12px;
            margin-bottom: 8px;
        }
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 3px solid #ef4444;
        }
        .alert-success {
            background: #f0fdf4;
            color: #065f46;
            border-left: 3px solid #10b981;
        }
        
        .form-check-label { font-size: 12px; }
        
        .back-home {
            color: #94a3b8;
            text-decoration: none;
            font-size: 12px;
            transition: 0.3s;
        }
        .back-home:hover { color: var(--gold); }
        
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                max-height: none;
                border-radius: 16px;
            }
            .auth-left {
                width: 100%;
                padding: 20px;
                min-height: 120px;
            }
            .auth-left h2 { font-size: 20px; }
            .auth-left .brand { font-size: 24px; }
            .auth-left .features li { font-size: 12px; padding: 3px 0; }
            .auth-right {
                width: 100%;
                padding: 15px;
                max-height: none;
                overflow-y: visible;
            }
        }
        @media (max-width: 480px) {
            .auth-left { padding: 15px; min-height: 100px; }
            .auth-left h2 { font-size: 17px; }
            .auth-left .brand { font-size: 20px; }
            .auth-left .brand-sub { font-size: 11px; }
            .auth-right { padding: 12px; }
            .auth-right .form-control { font-size: 12px; padding: 6px 10px; }
            .btn-register { font-size: 14px; padding: 8px; }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <!-- ===== LEFT SIDE ===== -->
    <div class="auth-left">
        <div class="brand"><i class="fas fa-hotel me-1"></i> ARALIYA</div>
        <div class="brand-sub">Beach Resort &amp; Spa Unawatuna</div>
        
        <h2>Join Us <br><span>Start Your Journey</span></h2>
        <p>Create your account and experience luxury at its finest.</p>
        
        <ul class="features">
            <li><i class="fas fa-check-circle"></i> Easy online booking</li>
            <li><i class="fas fa-check-circle"></i> Manage your reservations</li>
            <li><i class="fas fa-check-circle"></i> Exclusive member benefits</li>
            <li><i class="fas fa-check-circle"></i> Special offers &amp; discounts</li>
        </ul>
        
        <div class="footer-text">
            <i class="fas fa-umbrella-beach me-1"></i> <?php echo $hotel_address; ?>
        </div>
    </div>

    <!-- ===== RIGHT SIDE ===== -->
    <div class="auth-right">
        <h4>Create Account</h4>
        <p class="sub-heading">Fill in the details to get started</p>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="John Doe" required>
                </div>
                
                <div class="col-12">
                    <label class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                </div>
                
                <div class="col-6">
                    <label class="form-label"><i class="fas fa-phone"></i> Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="0712345678">
                </div>
                
                <div class="col-6">
                    <label class="form-label"><i class="fas fa-user-tag"></i> Account Type</label>
                    <select name="user_type" class="form-select">
                        <option value="guest">Guest</option>
                        <option value="travel_agent">Travel Agent</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Min 6 characters" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-12">
                    <label class="form-label"><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                </div>
                
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">I agree to the <a href="#">Terms &amp; Conditions</a></label>
                    </div>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn-register"><i class="fas fa-user-plus me-2"></i> Create Account</button>
                </div>
            </div>
        </form>
        
        <div class="divider">
            <span>Already a member?</span>
        </div>
        
        <p class="text-center mb-0 small text-muted">
            <a href="login.php" class="login-link">Sign In to your account</a>
        </p>
        
        <div class="text-center mt-1">
            <a href="index.php" class="back-home"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    var password = document.getElementById('password');
    var icon = document.getElementById('toggleIcon');
    if (password.type === 'password') {
        password.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        password.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>