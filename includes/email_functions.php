<?php
// includes/email_functions.php - PHPMailer Version
// ============================================================

require_once 'email_config.php';

// PHPMailer include කරන්න
require_once dirname(__DIR__) . '/PHPMailer/src/PHPMailer.php';
require_once dirname(__DIR__) . '/PHPMailer/src/SMTP.php';
require_once dirname(__DIR__) . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ============================================================
// EMAIL SEND FUNCTION - Using PHPMailer
// ============================================================
function sendEmail($to_email, $to_name, $subject, $html_body) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = strip_tags($html_body);
        
        $mail->send();
        error_log("✅ Email sent successfully to: " . $to_email);
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

// ============================================================
// WELCOME EMAIL
// ============================================================
function sendWelcomeEmail($guest_email, $guest_name, $reservation_data) {
    if (!SEND_WELCOME_EMAIL) {
        error_log("SEND_WELCOME_EMAIL is disabled");
        return true;
    }
    
    if (empty($guest_email)) {
        error_log("Email is empty for: " . $guest_name);
        return false;
    }
    
    if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email format: " . $guest_email);
        return false;
    }
    
    $subject = "Welcome to Araliya Beach Resort & Spa! 🏖️";
    $body = buildWelcomeEmailHTML($guest_name, $reservation_data);
    
    return sendEmail($guest_email, $guest_name, $subject, $body);
}

// ============================================================
// WELCOME EMAIL HTML
// ============================================================
function buildWelcomeEmailHTML($guest_name, $data) {
    $check_in = date('F d, Y', strtotime($data['check_in']));
    $check_out = date('F d, Y', strtotime($data['check_out']));
    $nights = $data['num_of_nights'] ?? 1;
    $room_number = $data['room_number'] ?? 'N/A';
    $room_type = $data['room_type'] ?? 'Standard';
    $rate = number_format($data['room_rate'] ?? 0, 2);
    $meal_plan = $data['meal_plan'] ?? 'Half Board';
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Welcome to Araliya</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
            .header { background: linear-gradient(135deg, #0d4b68, #1a6f8e); padding: 30px; text-align: center; }
            .header h1 { color: #fbbf24; font-size: 28px; margin: 0; }
            .header p { color: rgba(255,255,255,0.8); margin: 5px 0 0; }
            .content { padding: 30px; }
            .content h2 { color: #0d4b68; }
            .info-box { background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; border-left: 4px solid #fbbf24; }
            .info-box .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e7ebf1; }
            .info-box .row:last-child { border-bottom: none; }
            .info-box .label { color: #64748b; }
            .info-box .value { color: #0d4b68; font-weight: 600; }
            .badge { display: inline-block; background: #d1fae5; color: #065f46; padding: 4px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
            .footer { background: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e7ebf1; }
            .footer p { margin: 5px 0; color: #94a3b8; font-size: 13px; }
            @media (max-width: 480px) { .info-box .row { flex-direction: column; gap: 4px; } }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🏨 ARALIYA</h1>
                <p>Beach Resort &amp; Spa Unawatuna</p>
            </div>
            <div class="content">
                <h2>Welcome, ' . $guest_name . '! 🌴</h2>
                <p style="color: #475569;">We are delighted to welcome you to Araliya Beach Resort &amp; Spa.</p>
                <div class="info-box">
                    <div class="row"><span class="label">📅 Check-In</span><span class="value">' . $check_in . '</span></div>
                    <div class="row"><span class="label">📅 Check-Out</span><span class="value">' . $check_out . '</span></div>
                    <div class="row"><span class="label">🌙 Nights</span><span class="value">' . $nights . '</span></div>
                    <div class="row"><span class="label">🏠 Room</span><span class="value">' . $room_number . ' (' . $room_type . ')</span></div>
                    <div class="row"><span class="label">🍽️ Meal Plan</span><span class="value">' . $meal_plan . '</span></div>
                    <div class="row"><span class="label">💰 Rate</span><span class="value">LKR ' . $rate . ' / night</span></div>
                </div>
                <div style="text-align: center;">
                    <span class="badge">✅ Checked In Successfully</span>
                </div>
                <p style="color: #475569; margin-top: 20px; font-size: 14px;">
                    💡 Dial <strong>0</strong> from your room phone for 24/7 concierge service.
                </p>
            </div>
            <div class="footer">
                <p>📍 Galle Road, Unawatuna, Sri Lanka</p>
                <p>📞 +94 91 234 5678 | ✉️ info@araliyaresort.com</p>
                <p style="margin-top: 12px; font-size: 11px; color: #cbd5e1;">&copy; 2026 Araliya Beach Resort &amp; Spa.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}
?>