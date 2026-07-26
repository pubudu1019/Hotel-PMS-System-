<?php
// public/resend_verification.php - Resend Verification Email
session_start();
include '../includes/db.php';

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$error = '';
$success = '';

if (empty($email)) {
    // Show form
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check user
            $stmt = $conn->prepare("
                SELECT user_id, full_name, email, is_verified FROM online_users WHERE email = ?
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if ($user) {
                if ($user['is_verified'] == 1) {
                    $error = 'This email is already verified. Please login.';
                } else {
                    // Generate new token
                    $new_token = bin2hex(random_bytes(32));
                    $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    
                    $update = $conn->prepare("
                        UPDATE online_users 
                        SET verification_token = ?, token_expiry = ? 
                        WHERE user_id = ?
                    ");
                    $update->bind_param("ssi", $new_token, $token_expiry, $user['user_id']);
                    $update->execute();
                    $update->close();
                    
                    // Send email
                    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/hotel_management_structure/public/verify_email.php?token=" . $new_token;
                    
                    $email_subject = "Verify Your Email - Araliya Beach Resort";
                    $email_body = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; background: #f4f6f8; padding: 20px; }
                                .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
                                .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #fbbf24; }
                                .header h1 { color: #0d4b68; font-size: 28px; font-weight: 800; }
                                .header h1 span { color: #fbbf24; }
                                .content { padding: 20px 0; }
                                .content p { color: #475569; font-size: 15px; line-height: 1.7; }
                                .btn { display: inline-block; background: #0d4b68; color: white; padding: 12px 35px; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 15px 0; }
                                .btn:hover { background: #082f42; }
                                .footer { text-align: center; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 13px; }
                                .warning { background: #fef3c7; padding: 12px 16px; border-radius: 8px; color: #92400e; font-size: 13px; margin: 15px 0; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h1>🏨 ARALIYA <span>Beach Resort</span></h1>
                                </div>
                                <div class='content'>
                                    <h2 style='color: #0d4b68;'>Verify Your Email</h2>
                                    <p>Please verify your email address to complete your registration.</p>
                                    <div style='text-align: center;'>
                                        <a href='$verification_link' class='btn'>✅ Verify Email Address</a>
                                    </div>
                                    <div class='warning'>
                                        <strong>⚠️ This link will expire in 24 hours.</strong>
                                    </div>
                                </div>
                                <div class='footer'>
                                    <p>&copy; 2026 Araliya Beach Resort &amp; Spa. All rights reserved.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ";
                    
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $headers .= "From: Araliya Beach Resort <info@araliyaresort.com>\r\n";
                    
                    if (mail($email, $email_subject, $email_body, $headers)) {
                        $success = '✅ Verification email has been resent successfully!';
                    } else {
                        $error = 'Failed to send verification email. Please try again.';
                    }
                }
            } else {
                $error = 'No account found with this email.';
            }
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
    <title>Resend Verification - <?php echo $hotel_name; ?></title>
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
        .resend-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
            animation: slideUp 0.6s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .resend-card h2 {
            color: #082f42;
            font-weight: 700;
            text-align: center;
        }
        .resend-card .sub-heading {
            color: #94a3b8;
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
        }
        .resend-card .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
        }
        .resend-card .form-control:focus {
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251,191,36,0.2);
        }
        .btn-resend {
            background: #0d4b68;
            color: white;
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 16px;
            width: 100%;
            transition: 0.3s;
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

<div class="resend-card">
    <h2><i class="fas fa-envelope me-2" style="color: #fbbf24;"></i> Resend Verification</h2>
    <p class="sub-heading">Enter your email to receive a new verification link</p>
    
    <?php if(!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if(empty($success)): ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-bold"><i class="fas fa-envelope me-2"></i> Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="your@email.com" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <button type="submit" class="btn-resend"><i class="fas fa-paper-plane me-2"></i> Send Verification Email</button>
        </form>
    <?php else: ?>
        <div class="text-center mt-3">
            <a href="login.php" class="btn btn-primary" style="background: #0d4b68; border: none; border-radius:25px; padding:10px 30px; color:white; text-decoration:none;">
                <i class="fas fa-sign-in-alt me-2"></i> Go to Login
            </a>
        </div>
    <?php endif; ?>
    
    <div class="text-center mt-3">
        <a href="index.php" class="back-home"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
    </div>
</div>

</body>
</html>