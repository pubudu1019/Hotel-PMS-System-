<?php
// Session එක පරීක්ෂා කර ලොග් වී ඇති අයගේ නම ගැනීමට
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log වී සිටින පරිශීලකයාගේ නම (නැත්නම් Default ලෙස Receptionist ලෙස වැටේ)
$logged_user = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Duty Receptionist');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Blank GRC - Araliya Beach Resort</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; color: #000; }
        .grc-container { width: 750px; margin: auto; border: 2px solid #333; padding: 40px; background: #fff; }

        /* HEADER SECTION */
        .header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding-left: 140px;
            border-bottom: 2px solid #fbbf24;
            padding-bottom: 20px;
            gap: 25px;
        }
        .logo-container { font-size: 50px; }
        .hotel-name h2 { margin:0; color: #1a2234; }
        .hotel-name p { margin:0; font-size: 12px; color: #555; }

        .details-table { width: 100%; margin: 25px 0; border-collapse: collapse; }
        .details-table td { padding: 12px; border: 1px solid #eee; }
        .blank-line {
            display: inline-block;
            border-bottom: 1px solid #333;
            min-width: 180px;
            height: 16px;
        }
        .policy-section { font-size: 9px; color: #555; border-top: 1px solid #ccc; padding-top: 10px; margin-top: 30px; line-height: 1.4; }
        .signature-section { margin-top: 50px; display: flex; justify-content: space-between; }

        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<div class="grc-container">
    <div class="header">
        <div class="logo-container">🌺</div>
        <div class="hotel-name">
            <h2>ARALIYA BEACH RESORT</h2>
            <p>Property Management System | GRC #<span class="blank-line" style="min-width:100px;"></span></p>
        </div>
    </div>

    <table class="details-table">
        <tr><td><strong>Guest Name:</strong> <span class="blank-line"></span></td><td><strong>Nationality:</strong> <span class="blank-line"></span></td></tr>
        <tr><td><strong>Passport/ID:</strong> <span class="blank-line"></span></td><td><strong>Gender:</strong> <span class="blank-line"></span></td></tr>
        <tr><td><strong>Check-in:</strong> <span class="blank-line"></span></td><td><strong>Check-out:</strong> <span class="blank-line"></span></td></tr>
        <tr><td><strong>Room No:</strong> <span class="blank-line"></span></td><td><strong>Total Nights:</strong> <span class="blank-line"></span></td></tr>
        <tr><td><strong>Guests:</strong> <span class="blank-line"></span> Adults, <span class="blank-line"></span> Children</td><td><strong>Source:</strong> <span class="blank-line"></span></td></tr>
    </table>

    <p style="font-size: 13px;"><em>I hereby agree to the hotel policies and terms of stay as stated below.</em></p>

    <div class="signature-section">
        <p>__________________________<br>Guest Signature</p>
        <p>__________________________<br>Receptionist (<?= htmlspecialchars($logged_user) ?>)</p>
    </div>

    <div class="policy-section">
        <strong>Hotel Policies & Terms:</strong>
        <p>1. Check-in: 2:00 PM | Check-out: 11:00 AM. 2. A valid Passport or ID is required upon check-in. 3. Full payment is expected at the time of check-in. 4. Smoking is prohibited inside the rooms. 5. External visitors are not permitted in guest rooms. 6. The guest is liable for any damage to hotel property. 7. Quiet hours: 10:00 PM to 7:00 AM. 8. By signing, the guest accepts all hotel rules and regulations.</p>
    </div>
</div>

<div style="text-align: center; margin-top: 20px;" class="no-print">
    <button onclick="window.print()" style="padding: 12px 25px; cursor: pointer; background: #fbbf24; border: none; border-radius: 5px; font-weight: bold;">Print Blank GRC</button>
    <a href="arrivals.php" style="padding: 12px 25px; text-decoration: none; background: #64748b; color: white; border-radius: 5px; margin-left: 10px;">Back to Arrivals</a>
</div>

</body>
</html>