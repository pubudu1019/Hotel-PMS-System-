<?php
// public/verify_email.php - Verify Email
session_start();
include '../includes/db.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';

$error = '';
$success = '';

if (empty($token)) {
    $error = 'Invalid verification link.';
} else {
    // Check token
    $stmt = $conn->prepare("
        SELECT user_id, email, full_name FROM online_users 
        WHERE verification_token = ? AND is_verified = 0 AND token_expiry > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        // Update user as verified
        $update = $conn->prepare("
            UPDATE online_users 
            SET is_verified = 1, verification_token = NULL, token_expiry = NULL 
            WHERE user_id = ?
        ");
        $update->bind_param("i", $user['user_id']);
        
        if ($update->execute()) {
            $success = '✅ Your email has been verified successfully! You can now login.';
        } else {
            $error = 'Verification failed. Please try again.';
        }
        $update->close();
    } else {
        // Check if already verified
        $check = $conn->prepare("SELECT is_verified FROM online_users WHERE verification_token = ?");
        $check->bind_param("s", $token);
        $check->execute();
        $result = $check->get_result();
        $user = $result->fetch_assoc();
        $check->close();
        
        if ($user && $user['is_verified'] == 1) {
            $error = 'This email is already verified. Please login.';
        } else {
            $error = 'Invalid or expired verification link. Please request a new one.';
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
    <title>Email Verification - <?php echo $hotel_name; ?></title>
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
            margin-bottom: 15px;
        }
        .verify-card .icon.success { color: #10b981; }
        .verify-card .icon.error { color: #ef4444; }
        .verify-card h2 {
            color: #082f42;
            font-weight: 700;
        }
        .verify-card p {
            color: #64748b;
            font-size: 15px;
            line-height: 1.7;
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
        .btn-resend {
            background: #0d4b68;
            color: white;
            padding: 10px 30px;
            border-radius: 25px;
            border: none;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-resend:hover {
            background: #082f42;
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
    <?php if($success): ?>
        <div class="icon success">✅</div>
        <h2>Email Verified!</h2>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
        </div>
        <p>Your account is now active. You can start booking your dream stay.</p>
        <div class="mt-4">
            <a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt me-2"></i> Login Now</a>
        </div>
    <?php elseif($error): ?>
        <div class="icon error">❌</div>
        <h2>Verification Failed</h2>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        </div>
        <?php if(strpos($error, 'expired') !== false || strpos($error, 'Invalid') !== false): ?>
            <p>Please request a new verification link.</p>
            <div class="mt-4">
                <a href="resend_verification.php" class="btn-resend"><i class="fas fa-redo me-2"></i> Resend Verification</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="mt-4">
        <a href="index.php" class="back-home"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
    </div>
</div>

</body>
</html>