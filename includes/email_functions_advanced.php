<?php
// includes/email_functions_advanced.php - Using PHPMailer
// ============================================================

require_once 'vendor/autoload.php'; // Composer autoload
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'email_config.php';

function sendEmailAdvanced($to_email, $to_name, $subject, $html_body) {
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
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendWelcomeEmailAdvanced($guest_email, $guest_name, $reservation_data) {
    if (!SEND_WELCOME_EMAIL) return true;
    
    $subject = "Welcome to Araliya Beach Resort & Spa! 🏖️";
    $body = buildWelcomeEmailHTML($guest_name, $reservation_data);
    
    return sendEmailAdvanced($guest_email, $guest_name, $subject, $body);
}
?>