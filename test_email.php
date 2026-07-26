<?php
// test_email.php - Test PHPMailer
include 'includes/email_functions.php';

$test_email = 'your-email@gmail.com'; // ඔබගේ email එක දාන්න
$test_name = 'Test Guest';

$test_data = [
    'guest_name' => 'Test Guest',
    'check_in' => date('Y-m-d'),
    'check_out' => date('Y-m-d', strtotime('+2 days')),
    'num_of_nights' => 2,
    'room_number' => '101',
    'room_type' => 'Deluxe Room',
    'room_rate' => 15000,
    'meal_plan' => 'Half Board'
];

echo "<h2>📧 Testing PHPMailer</h2>";

// Check if PHPMailer exists
if (!file_exists(dirname(__DIR__) . '/PHPMailer/src/PHPMailer.php')) {
    echo "<p style='color:red;'>❌ PHPMailer not found! Please download PHPMailer and place it in the project root.</p>";
    echo "<p>Download from: <a href='https://github.com/PHPMailer/PHPMailer/releases' target='_blank'>PHPMailer Releases</a></p>";
    exit;
}

echo "<p>PHPMailer found! ✅</p>";

// Test Welcome Email
echo "<h3>Testing Welcome Email...</h3>";
echo "<p>Sending to: <strong>$test_email</strong></p>";

if (sendWelcomeEmail($test_email, $test_name, $test_data)) {
    echo "<p style='color:green;'>✅ Welcome email sent successfully to <strong>$test_email</strong></p>";
    echo "<p>Please check your inbox (and spam folder).</p>";
} else {
    echo "<p style='color:red;'>❌ Failed to send welcome email. Please check:</p>";
    echo "<ul>";
    echo "<li>Gmail App Password is correct in <code>includes/email_config.php</code></li>";
    echo "<li>Email address is valid</li>";
    echo "<li>Internet connection is active</li>";
    echo "</ul>";
}

// Show config
echo "<hr>";
echo "<h3>Configuration:</h3>";
echo "<pre>";
echo "SMTP Host: " . SMTP_HOST . "\n";
echo "SMTP Port: " . SMTP_PORT . "\n";
echo "SMTP Username: " . SMTP_USERNAME . "\n";
echo "SMTP Password: " . (SMTP_PASSWORD ? '✅ Set' : '❌ Not Set') . "\n";
echo "From Email: " . SMTP_FROM_EMAIL . "\n";
echo "From Name: " . SMTP_FROM_NAME . "\n";
echo "</pre>";
?>