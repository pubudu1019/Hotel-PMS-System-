<?php
// includes/email_config.php
// ============================================================

// ===== GMAIL SMTP SETTINGS =====
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'harshanapubudusathsara@gmail.com');  // ඔබගේ Gmail
define('SMTP_PASSWORD', 'ofrm xhsd jsrm kwng');     // Gmail App Password
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Araliya Beach Resort & Spa');

// ===== EMAIL SETTINGS =====
define('SEND_WELCOME_EMAIL', true);
define('SEND_CONFIRMATION_EMAIL', true);

// ============================================================
// Gmail App Password හදන හැටි:
// 1. Google Account එකට login වෙන්න
// 2. Security → 2-Step Verification ON කරන්න
// 3. Security → App Passwords යන්න
// 4. Select App: "Mail", Select Device: "Other (custom name)"
// 5. "Generate" කියන එක click කරන්න
// 6. එන 16-character password එක copy කරලා SMTP_PASSWORD එකට දාන්න
// ============================================================
?>