<?php
// public/verify_sent.php - Verification Email Sent
session_start();
include '../includes/db.php';

$email = isset($_SESSION['verification_email']) ? $_SESSION['verification_email'] : '';

$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - <?php echo $hotel_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #082f42, #0d4b68);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verify-card {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
            text-align: center;
            animation: slideUp 0.6s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .verify-card .icon {
            font-size: 72px;
            color: #fbbf24;
            margin-bottom: 15px;
        }
        .verify-card h2 {
            color: #082f42;
            font-weight: 700;
        }
        .verify-card p {
            color: #64748b;
            font-size: 15px;
            line-height: 1.7;
        }
        .verify-card .email-highlight {
            background: #f8fafc;
            padding: 10px 20px;
            border-radius: 10px;
            display: inline-block;
            font-weight: 600;
            color: #0d4b68;
            margin: 10px 0;
        }
        .btn-resend {
            background: #0d4b68;
            color: white;
            padding: 10px 30px;
            border-radius: 25px;
            border: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-resend:hover {
            background: #082f42;
            transform: translateY(-2px);
        }
        .btn-login {
            background: #fbbf24;
            color: #082f42;
            padding: 10px 30px;
            border-radius: 25px;
            border: none;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-login:hover {
            background: #d97706;
            color: white;
            transform: translateY(-2px);
        }
        .back-home {
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
        }
        .back-home:hover { color: #fbbf24; }
        .alert {
            border-radius: 10px;
            border: none;
            font-size: 13px;
            padding: 10px 14px;
        }
        .alert-success { background: #f0fdf4; color: #065f46; border-left: 4px solid #10b981; }
        .alert-danger { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>

<div class="verify-card">
    <div class="icon">📧</div>
    <h2>Verify Your Email</h2>
    
    <p>We've sent a verification link to:</p>
    <div class="email-highlight">
        <i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($email); ?>
    </div>
    
    <p class="mt-2">
        Please check your inbox and click the verification link to activate your account.
    </p>
    
    <div class="alert alert-success mt-3">
        <i class="fas fa-clock me-2"></i> The link will expire in 24 hours.
    </div>
    
    <div class="mt-4">
        <a href="login.php" class="btn-login me-2"><i class="fas fa-sign-in-alt me-2"></i> Go to Login</a>
        <a href="index.php" class="back-home d-block mt-3"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
    </div>
    
    <hr class="my-4">
    
    <p class="text-muted small">
        Didn't receive the email? Check your spam folder or <a href="resend_verification.php?email=<?php echo urlencode($email); ?>" style="color: #0d4b68; font-weight:600;">Resend verification email</a>
    </p>
</div>

</body>
</html>