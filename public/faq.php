<?php
// public/faq.php - Frequently Asked Questions
session_start();
include '../includes/db.php';

$is_logged_in = isset($_SESSION['online_user_id']) && !empty($_SESSION['online_user_id']);
$logged_in_user = $is_logged_in ? $_SESSION['online_user_name'] : '';

$hotel = $conn->query("SELECT * FROM hotel_settings LIMIT 1")->fetch_assoc();
$hotel_name = isset($hotel['hotel_name']) ? $hotel['hotel_name'] : 'Araliya Beach Resort';
$hotel_phone = isset($hotel['phone']) ? $hotel['phone'] : '+94 91 234 5678';
$hotel_address = isset($hotel['address']) ? $hotel['address'] : 'Galle Road, Unawatuna, Sri Lanka';

// FAQ Categories
$faq_categories = array(
    'Booking' => array(
        array(
            'question' => 'How do I make a reservation?',
            'answer' => 'You can make a reservation directly on our website by selecting your desired dates, room type, and completing the booking form. You can also call us at ' . $hotel_phone . ' for assistance.'
        ),
        array(
            'question' => 'Do I need a credit card to book?',
            'answer' => 'Yes, a valid credit card is required to secure your reservation. Your card will be charged at the time of booking.'
        ),
        array(
            'question' => 'Can I modify or cancel my booking?',
            'answer' => 'Yes, you can modify or cancel your booking up to 24 hours before check-in. Please contact us directly for any changes.'
        )
    ),
    'Check-in & Check-out' => array(
        array(
            'question' => 'What time is check-in and check-out?',
            'answer' => 'Check-in is from 2:00 PM onwards. Check-out is at 12:00 PM. Early check-in and late check-out are subject to availability.'
        ),
        array(
            'question' => 'What do I need to bring for check-in?',
            'answer' => 'Please bring a valid government-issued ID or passport and your booking confirmation. A credit card may be required for incidental charges.'
        ),
        array(
            'question' => 'Can I check-in early or check-out late?',
            'answer' => 'Early check-in and late check-out are subject to availability and may incur additional charges. Please contact the front desk for more information.'
        )
    ),
    'Rooms & Facilities' => array(
        array(
            'question' => 'What room types do you offer?',
            'answer' => 'We offer Standard Rooms, Deluxe Rooms, Suites, Luxury Suites, and Apartments. Each room is designed for comfort and luxury.'
        ),
        array(
            'question' => 'Are there any amenities in the room?',
            'answer' => 'All rooms include free WiFi, flat-screen TV, air conditioning, private bathroom, tea/coffee maker, and daily housekeeping.'
        ),
        array(
            'question' => 'Do you have a swimming pool?',
            'answer' => 'Yes, we have a beautiful outdoor swimming pool with a pool bar. Towels are provided at the pool area.'
        )
    ),
    'Dining' => array(
        array(
            'question' => 'Is breakfast included in the room rate?',
            'answer' => 'Breakfast availability depends on your room package. Please check your booking details or contact us for more information.'
        ),
        array(
            'question' => 'What dining options are available?',
            'answer' => 'We offer a fine dining restaurant, a pool bar, and 24/7 room service. Our restaurant serves local and international cuisine.'
        )
    ),
    'Policies' => array(
        array(
            'question' => 'What is your cancellation policy?',
            'answer' => 'Cancellations made within 24 hours of check-in may incur a cancellation fee. No-shows will be charged the full amount.'
        ),
        array(
            'question' => 'Are pets allowed?',
            'answer' => 'Pets are not allowed unless prior arrangement has been made. Please contact us for more information.'
        ),
        array(
            'question' => 'Is smoking allowed?',
            'answer' => 'Smoking is prohibited in all indoor areas. Designated smoking areas are available outside the building.'
        )
    ),
    'General' => array(
        array(
            'question' => 'Do you offer airport transfers?',
            'answer' => 'Yes, we offer airport transfer services at an additional charge. Please contact us to arrange pickup.'
        ),
        array(
            'question' => 'Is there free parking available?',
            'answer' => 'Yes, we offer free parking for all our guests. Parking is secure and monitored by CCTV.'
        ),
        array(
            'question' => 'Do you offer spa services?',
            'answer' => 'Yes, we have a full-service spa offering massages, facials, and wellness treatments. Advance booking is recommended.'
        )
    )
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - <?php echo $hotel_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #0d4b68; --gold: #fbbf24; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f8; }
        
        .navbar-custom {
            background: linear-gradient(135deg, #082f42, #0d4b68);
            padding: 12px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .navbar-custom .brand {
            color: var(--gold);
            font-size: 22px;
            font-weight: 800;
            text-decoration: none;
        }
        .navbar-custom .brand i { color: var(--gold); }
        .navbar-custom .brand-sub {
            color: rgba(255,255,255,0.6);
            font-size: 10px;
            display: block;
            letter-spacing: 0.5px;
        }
        .navbar-custom .nav-link {
            color: rgba(255,255,255,0.8);
            font-weight: 600;
            font-size: 14px;
            padding: 8px 18px;
            transition: 0.3s;
        }
        .navbar-custom .nav-link:hover { color: var(--gold); }
        .btn-book-now {
            background: var(--gold);
            color: #082f42;
            font-weight: 700;
            padding: 8px 25px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-book-now:hover {
            background: #d97706;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(251,191,36,0.3);
        }
        .btn-logout {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 6px 18px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-logout:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }
        .user-dropdown {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .user-dropdown:hover {
            background: rgba(255,255,255,0.2);
            color: var(--gold);
        }
        .user-dropdown .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--gold);
            color: #082f42;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 12px;
        }
        
        .faq-hero {
            background: linear-gradient(135deg, #082f42, #0d4b68);
            color: white;
            padding: 40px 0 30px;
            text-align: center;
            border-radius: 16px;
            margin-bottom: 30px;
        }
        .faq-hero h1 {
            font-size: 28px;
            font-weight: 800;
        }
        .faq-hero h1 span { color: var(--gold); }
        .faq-hero p {
            opacity: 0.8;
            font-size: 15px;
            max-width: 600px;
            margin: 8px auto 0;
        }
        .faq-hero .search-box {
            max-width: 500px;
            margin: 15px auto 0;
        }
        .faq-hero .search-box input {
            border-radius: 25px;
            padding: 10px 20px;
            border: none;
            font-size: 14px;
        }
        .faq-hero .search-box button {
            border-radius: 25px;
            padding: 10px 25px;
            background: var(--gold);
            border: none;
            font-weight: 700;
            color: #082f42;
        }
        .faq-hero .search-box button:hover {
            background: #d97706;
            color: white;
        }
        
        .faq-category {
            margin-bottom: 30px;
        }
        .faq-category h4 {
            color: #082f42;
            font-weight: 700;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gold);
            margin-bottom: 15px;
        }
        .faq-category h4 i { color: var(--gold); margin-right: 10px; }
        
        .faq-item {
            background: white;
            border-radius: 12px;
            margin-bottom: 10px;
            border: 1px solid #eef2f5;
            overflow: hidden;
            transition: 0.3s;
            cursor: pointer;
        }
        .faq-item:hover {
            border-color: var(--gold);
        }
        .faq-item .question {
            padding: 15px 20px;
            font-weight: 600;
            color: #082f42;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            transition: 0.3s;
        }
        .faq-item .question:hover {
            background: #f1f5f9;
        }
        .faq-item .question i {
            color: var(--gold);
            transition: 0.3s;
        }
        .faq-item .question i.open {
            transform: rotate(180deg);
        }
        .faq-item .answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: 0.3s;
            color: #475569;
            font-size: 14px;
            line-height: 1.7;
        }
        .faq-item .answer.show {
            padding: 15px 20px;
            max-height: 300px;
        }
        
        .footer { background: #0a1828; color: rgba(255,255,255,0.7); padding: 30px 0 15px; margin-top: 40px; }
        .footer .brand { color: var(--gold); font-size: 20px; font-weight: 800; }
        .footer a { color: rgba(255,255,255,0.6); text-decoration: none; transition: 0.3s; }
        .footer a:hover { color: var(--gold); }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; margin-top: 20px; font-size: 13px; }
        
        @media (max-width: 768px) {
            .faq-hero { padding: 25px 20px; }
            .faq-hero h1 { font-size: 22px; }
            .faq-item .question { font-size: 14px; padding: 12px 15px; }
            .faq-item .answer.show { padding: 12px 15px; }
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a href="index.php" class="brand">
            <i class="fas fa-hotel me-1"></i> ARALIYA
            <span class="brand-sub">Beach Resort &amp; Spa Unawatuna</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="rooms.php" class="nav-link">Rooms</a></li>
                <li class="nav-item"><a href="gallery.php" class="nav-link">Gallery</a></li>
                <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
                <li class="nav-item"><a href="faq.php" class="nav-link active">FAQ</a></li>
                <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
                
                <?php if($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a href="#" class="user-dropdown dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="user-avatar"><?php echo strtoupper(substr($logged_in_user, 0, 1)); ?></span>
                            <span><?php echo htmlspecialchars($logged_in_user); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="my_bookings.php"><i class="fas fa-list me-2"></i> My Bookings</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a href="login.php" class="nav-link"><i class="fas fa-user me-1"></i> Login</a></li>
                    <li class="nav-item"><a href="register.php" class="btn-book-now"><i class="fas fa-user-plus me-1"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== FAQ HERO ===== -->
<section class="container">
    <div class="faq-hero">
        <h1>Frequently Asked <span>Questions</span></h1>
        <p>Find answers to the most common questions about our hotel and services.</p>
    </div>
</section>

<!-- ===== FAQ CONTENT ===== -->
<section class="py-2">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <?php foreach($faq_categories as $category => $faqs): ?>
                    <div class="faq-category">
                        <h4><i class="fas fa-tag"></i> <?php echo $category; ?></h4>
                        <?php foreach($faqs as $faq): ?>
                            <div class="faq-item" onclick="toggleFAQ(this)">
                                <div class="question">
                                    <span><i class="fas fa-question-circle me-2" style="color: var(--gold);"></i> <?php echo $faq['question']; ?></span>
                                    <i class="fas fa-chevron-down" id="faqIcon"></i>
                                </div>
                                <div class="answer">
                                    <?php echo $faq['answer']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <i class="fas fa-phone me-2"></i> Can't find your answer? <a href="contact.php" style="color: var(--primary); font-weight:600;">Contact Us</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="brand">ARALIYA</div>
                <p style="font-size:13px;">Beach Resort &amp; Spa Unawatuna</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="index.php">Home</a> | 
                <a href="rooms.php">Rooms</a> | 
                <a href="gallery.php">Gallery</a> | 
                <a href="about.php">About</a> | 
                <a href="faq.php">FAQ</a> | 
                <a href="contact.php">Contact</a>
                <?php if($is_logged_in): ?> | <a href="logout.php">Logout</a><?php endif; ?>
            </div>
        </div>
        <div class="footer-bottom text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo $hotel_name; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
function toggleFAQ(element) {
    var answer = element.querySelector('.answer');
    var icon = element.querySelector('#faqIcon');
    
    // Close all other FAQ items
    var allItems = document.querySelectorAll('.faq-item');
    allItems.forEach(function(item) {
        if (item !== element) {
            var otherAnswer = item.querySelector('.answer');
            var otherIcon = item.querySelector('#faqIcon');
            otherAnswer.classList.remove('show');
            otherIcon.classList.remove('open');
        }
    });
    
    // Toggle current item
    answer.classList.toggle('show');
    icon.classList.toggle('open');
}

// Open first FAQ item by default
document.addEventListener('DOMContentLoaded', function() {
    var firstFaq = document.querySelector('.faq-item');
    if (firstFaq) {
        var answer = firstFaq.querySelector('.answer');
        var icon = firstFaq.querySelector('#faqIcon');
        answer.classList.add('show');
        icon.classList.add('open');
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>