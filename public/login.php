<?php
// public/login.php - NEW DESIGN (Left/Right Split)
session_start();
include '../includes/db.php';

// If already logged in, redirect
if (isset($_SESSION['online_user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT user_id, full_name, email, password, user_type, is_verified FROM online_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['online_user_id'] = $user['user_id'];
                $_SESSION['online_user_name'] = $user['full_name'];
                $_SESSION['online_user_email'] = $user['email'];
                $_SESSION['online_user_type'] = $user['user_type'];
                $_SESSION['logged_in'] = true;
                
                if ($remember) {
                    setcookie('remember_email', $email, time() + (86400 * 30), '/');
                }
                
                $conn->query("UPDATE online_users SET last_login = NOW() WHERE user_id = " . $user['user_id']);
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'No account found with this email.';
        }
        $stmt->close();
    } else {
        $error = 'Please fill in all fields.';
    }
}

$remember_email = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';

$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
$hotel_address = isset($hotel['address']) ? $hotel['address'] : 'Galle Road, Unawatuna, Sri Lanka';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo $hotel_name; ?></title>
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
            max-height: 90vh;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ===== LEFT SIDE ===== */
        .auth-left {
            width: 45%;
            background: linear-gradient(135deg, #082f42, #0d4b68);
            padding: 50px 35px;
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
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        .auth-left h2 {
            font-size: 28px;
            font-weight: 700;
            position: relative;
            z-index: 1;
            margin-bottom: 15px;
        }
        .auth-left h2 span { color: var(--gold); }
        .auth-left p {
            opacity: 0.8;
            font-size: 15px;
            line-height: 1.7;
            position: relative;
            z-index: 1;
            margin-bottom: 25px;
        }
        .auth-left .features {
            position: relative;
            z-index: 1;
        }
        .auth-left .features li {
            list-style: none;
            padding: 6px 0;
            font-size: 14px;
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
            margin-top: 20px;
            font-size: 12px;
            opacity: 0.5;
        }
        
        /* ===== RIGHT SIDE ===== */
        .auth-right {
            width: 55%;
            padding: 40px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .auth-right h4 {
            color: #082f42;
            font-weight: 700;
            font-size: 22px;
        }
        .auth-right .sub-heading {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .auth-right .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
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
            font-size: 13px;
        }
        .auth-right .form-label i { color: var(--gold); width: 18px; }
        
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
        
        .btn-login {
            background: linear-gradient(135deg, #0d4b68, #1a6f8e);
            color: white;
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 16px;
            width: 100%;
            transition: 0.3s;
            margin-top: 8px;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #082f42, #0d4b68);
            transform: translateY(-2px);
            box-shadow: 0 10px 35px rgba(13,75,104,0.3);
        }
        
        .forgot-link {
            color: var(--primary);
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: 0.3s;
        }
        .forgot-link:hover { color: var(--gold-dark); text-decoration: underline; }
        .register-link {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
        }
        .register-link:hover { color: var(--gold-dark); text-decoration: underline; }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 18px 0;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        .divider span {
            padding: 0 18px;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            font-size: 13px;
            padding: 10px 14px;
        }
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                max-height: none;
                border-radius: 16px;
            }
            .auth-left {
                width: 100%;
                padding: 30px 25px;
                min-height: 200px;
            }
            .auth-left h2 { font-size: 22px; }
            .auth-left .brand { font-size: 26px; }
            .auth-right {
                width: 100%;
                padding: 25px 20px;
            }
        }
        @media (max-width: 480px) {
            .auth-left { padding: 20px; }
            .auth-right { padding: 20px; }
            .auth-left .features li { font-size: 13px; }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <!-- ===== LEFT SIDE ===== -->
    <div class="auth-left">
        <div class="brand"><i class="fas fa-hotel me-1"></i> ARALIYA</div>
        <div class="brand-sub">Beach Resort &amp; Spa Unawatuna</div>
        
        <h2>Welcome Back <br><span>to Paradise</span></h2>
        <p>Sign in to manage your bookings and enjoy exclusive offers.</p>
        
        <ul class="features">
            <li><i class="fas fa-check-circle"></i> Manage your reservations</li>
            <li><i class="fas fa-check-circle"></i> View booking history</li>
            <li><i class="fas fa-check-circle"></i> Exclusive member offers</li>
            <li><i class="fas fa-check-circle"></i> Quick & easy check-in</li>
        </ul>
        
        <div class="footer-text">
            <i class="fas fa-umbrella-beach me-1"></i> <?php echo $hotel_address; ?>
        </div>
    </div>

    <!-- ===== RIGHT SIDE ===== -->
    <div class="auth-right">
        <h4>Sign In</h4>
        <p class="sub-heading">Enter your credentials to access your account</p>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="your@email.com" value="<?php echo htmlspecialchars($remember_email); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember" <?php if(!empty($remember_email)) echo 'checked'; ?>>
                    <label class="form-check-label small" for="remember">Remember me</label>
                </div>
                <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt me-2"></i> Sign In</button>
        </form>
        
        <div class="divider">
            <span>New here?</span>
        </div>
        
        <p class="text-center mb-0 small text-muted">
            Don't have an account? <a href="register.php" class="register-link">Create Account</a>
        </p>
        
        <div class="text-center mt-2">
            <a href="index.php" class="text-muted small text-decoration-none" style="font-size:12px;">
                <i class="fas fa-arrow-left me-1"></i> Back to Home
            </a>
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