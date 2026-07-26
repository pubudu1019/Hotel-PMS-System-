<?php
include 'includes/session_check.php';
include 'includes/db.php';

// ===== NEW: Get logged-in user ID for created_by =====
$created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make a Reservation - Araliya PMS</title> <!-- UPDATED: PMS -> Araliya -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* Light Blue / Grey Theme Overhaul */
        body { background: #f0f4f8; color: #334155; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .pms-container { max-width: 1200px; margin: 20px auto; background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #cbd5e1; }
        .pms-header { background: #074e67; padding: 12px 20px; margin: -20px -20px 20px -20px; border-top-left-radius: 8px; border-top-right-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .pms-header h5 { margin: 0; color: #fff; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; font-size: 16px; }
        
        /* Sidebar Steps Links (Light Theme) */
        .step-box { background: #f8fafc; border-radius: 6px; padding: 15px; height: 100%; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 10px; }
        .step-nav-link { background: none; border: none; width: 100%; text-align: center; padding: 15px 5px; border-bottom: 1px solid #e2e8f0; opacity: 0.5; transition: 0.3s; color: #475569; pointer-events: none; }
        .step-nav-link.active { opacity: 1; }
        .step-nav-link.completed { opacity: 0.9; }
        .step-nav-link:last-child { border-bottom: none; }
        .step-number { width: 35px; height: 35px; line-height: 35px; border-radius: 50%; background: #94a3b8; color: #fff; margin: 0 auto 8px auto; font-weight: bold; display: block; transition: 0.3s; }
         
        .step-nav-link.active .step-number { background: #f59e0b; color: #fff; box-shadow: 0 0 8px rgba(245, 158, 11, 0.4); }
        .step-nav-link.completed .step-number { background: #10b981; color: #fff; }
        .step-title { font-size: 12px; font-weight: 600; text-transform: uppercase; color: #1e293b; }
        .step-sub { font-size: 11px; color: #64748b; }

        /* Tab Content Control */
        .tab-content > .tab-pane { display: none; }
        .tab-content > .active { display: block !important; }

        /* Panel Content Layout */
        .panel-section { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 25px; min-height: 440px; display: flex; flex-direction: column; justify-content: space-between; }
        .panel-title { font-size: 14px; font-weight: 600; color: #074e67; border-bottom: 2px solid #cbd5e1; padding-bottom: 6px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Light Theme Forms */
        label { font-size: 12px; color: #475569; margin-bottom: 4px; font-weight: 600; }
        label::after { content: " *"; color: #ef4444; }
        label.no-req::after { content: ""; }
        .form-control, .form-select { background: #f8fafc !important; color: #1e293b !important; border: 1px solid #cbd5e1; font-size: 13px; padding: 8px 12px; border-radius: 4px; }
        .form-control:focus, .form-select:focus { border-color: #074e67; box-shadow: 0 0 0 2px rgba(7, 78, 103, 0.15); background: #fff !important; }
        
        /* Checkboxes styling */
        .form-check-input { background-color: #f8fafc; border-color: #cbd5e1; width: 16px; height: 16px; }
        .form-check-input:checked { background-color: #074e67; border-color: #074e67; }
        .form-check-label { font-size: 12px; color: #334155; font-weight: 500; }

        /* Footer & Buttons */
        .action-footer { display: flex; justify-content: space-between; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 15px; }
        .btn-pms { font-weight: 600; border: none; padding: 8px 25px; font-size: 13px; border-radius: 4px; text-transform: uppercase; transition: 0.2s; }
        .btn-pms-back { background: #64748b; color: #fff; }
        .btn-pms-back:hover { background: #475569; }
        .btn-pms-next { background: #10b981; color: #fff; margin-left: auto; }
        .btn-pms-next:hover { background: #059669; }
        .btn-pms-submit { background: #074e67; color: #fff; margin-left: auto; }
        .btn-pms-submit:hover { background: #053a4d; }
        
        /* Top Action Icons Override */
        .btn-light-pms { background: #ffffff; color: #074e67; border: none; }
        .btn-light-pms:hover { background: #e2e8f0; }
        
        /* ===== NEW: Room status badge in dropdown ===== */
        .room-status-badge {
            font-size: 10px;
            padding: 1px 8px;
            border-radius: 10px;
            margin-left: 5px;
        }
        .status-available { background: #d1fae5; color: #065f46; }
        .status-occupied { background: #fee2e2; color: #991b1b; }
        .status-cleaning { background: #fef3c7; color: #92400e; }
        .status-maintenance { background: #e5e7eb; color: #4b5563; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="pms-container">
        
<div class="pms-header">
    <div class="d-flex align-items-center gap-2">
        <a href="javascript:history.back()" class="btn btn-sm btn-light-pms" title="Go Back">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <a href="dashboard.php" class="btn btn-sm btn-light-pms" title="Go to Dashboard">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <h5 class="ms-2">Make a Reservation</h5>
    </div>
    <div>
        <button type="button" onclick="resetReservationForm()" class="btn btn-sm btn-light-pms me-1" title="New Reservation"><i class="bi bi-plus-lg"></i></button>
        <button type="button" class="btn btn-sm btn-secondary text-white border-0" style="background:#0b5b75;"><i class="bi bi-list-task"></i></button>
    </div>
</div>

        <form id="reservationForm" method="POST" action="process_reservation.php">
            
            <!-- ===== NEW: Hidden field for created_by ===== -->
            <input type="hidden" name="created_by" value="<?php echo $created_by; ?>">
            
            <div class="row g-3">
                
                <div class="col-md-3">
                    <div class="step-box" id="stepTabs">
                        
                        <div class="step-nav-link active" id="step1-tab">
                            <span class="step-number">1</span>
                            <div class="step-title">Select Stay</div>
                            <div class="step-sub">Dates & Room</div>
                        </div>

                        <div class="step-nav-link" id="step2-tab">
                            <span class="step-number">2</span>
                            <div class="step-title">Guest Details</div>
                            <div class="step-sub">Personal Info</div>
                        </div>

                        <div class="step-nav-link" id="step3-tab">
                            <span class="step-number">3</span>
                            <div class="step-title">Company Details</div>
                            <div class="step-sub">Profiles & Segments</div>
                        </div>

                        <div class="step-nav-link" id="step4-tab">
                            <span class="step-number">4</span>
                            <div class="step-title">Confirmation</div>
                            <div class="step-sub">Meal Plan & Finish</div>
                        </div>

                    </div>
                </div>

                <div class="col-md-9">
                    <div class="tab-content" id="stepTabContent">
                        
                        <div class="tab-pane active" id="step1">
                            <div class="panel-section">
                                <div>
                                    <div class="panel-title">1. Accommodation & Stay Details</div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-5">
                                            <label>Arrival Date</label>
                                            <input type="date" name="check_in" id="check_in" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-5">
                                            <label>Departure Date</label>
                                            <input type="date" name="check_out" id="check_out" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label>No Of Nights</label>
                                            <!-- ===== UPDATED: Added disabled attribute ===== -->
                                            <input type="number" name="num_of_nights" id="num_of_nights" class="form-control" value="1" min="1" readonly disabled required>
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label>Expected Arrival Time</label>
                                            <input type="time" name="expected_arrival_time" class="form-control" value="14:00" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Expected Departure Time</label>
                                            <input type="time" name="expected_departure_time" class="form-control" value="12:00" required>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="no-req">Select Available Room</label>
                                            <select name="room_id" class="form-select">
                                                <option value="">-- Assign Room Later (Hold Booking) --</option>

                                                <?php
                                                // ===== UPDATED: Show room status with color badges =====
                                                $rooms = $conn->query("
                                                    SELECT room_id, room_number, room_type, status
                                                    FROM rooms
                                                    ORDER BY room_number ASC
                                                ");

                                                if($rooms && $rooms->num_rows > 0){
                                                    while($r = $rooms->fetch_assoc()){
                                                        $status = $r['status'];
                                                        $badge_class = '';
                                                        if($status == 'available') { $badge_class = 'status-available'; }
                                                        elseif($status == 'occupied') { $badge_class = 'status-occupied'; }
                                                        elseif($status == 'cleaning') { $badge_class = 'status-cleaning'; }
                                                        else { $badge_class = 'status-maintenance'; }
                                                        
                                                        echo '<option value="'.$r['room_id'].'">
                                                                Room '.$r['room_number'].' ('.$r['room_type'].')
                                                                <span class="room-status-badge '.$badge_class.'">'.$status.'</span>
                                                              </option>';
                                                    }
                                                } else {
                                                    echo '<option value="">No Available Rooms</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="action-footer">
                                    <button type="button" class="btn btn-pms btn-pms-next" onclick="goToStep(2)">Next: Guest Details <i class="bi bi-arrow-right"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane" id="step2">
                            <div class="panel-section">
                                <div>
                                    <div class="panel-title">2. Guest Personal Information</div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label>Full Name</label>
                                            <input type="text" name="guest_name" class="form-control" required placeholder="Guest Profile Name">
                                        </div>
                                        <div class="col-md-3">
                                            <label>Gender</label>
                                            <select name="gender" class="form-select"><option>Male</option><option>Female</option></select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Passport / National ID No</label>
                                            <input type="text" name="passport_no" class="form-control" required placeholder="ID Number">
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label>Nationality</label>
                                            <select name="nationality" class="form-select" required>
                                                <option value="">-- Select Country --</option>
                                                <?php
                                                $nations = $conn->query("SELECT name FROM nationalities");
                                                if ($nations && $nations->num_rows > 0) {
                                                    while($n = $nations->fetch_assoc()) {
                                                        echo "<option value='".$n['name']."'>".$n['name']."</option>";
                                                    }
                                                } else {
                                                    echo "<option value='Sri Lankan'>Sri Lankan</option>";
                                                    echo "<option value='Indian'>Indian</option>";
                                                    echo "<option value='British'>British</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="no-req">Email Address</label>
                                            <input type="email" name="email" class="form-control" placeholder="name@domain.com">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="no-req">Mobile Number</label>
                                            <input type="text" name="mobile" class="form-control" placeholder="+94...">
                                        </div>
                                    </div>
                                </div>
                                <div class="action-footer">
                                    <button type="button" class="btn btn-pms btn-pms-back" onclick="goToStep(1)"><i class="bi bi-arrow-left"></i> Back</button>
                                    <button type="button" class="btn btn-pms btn-pms-next" onclick="goToStep(3)">Next: Profiles & Setup <i class="bi bi-arrow-right"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane" id="step3">
                            <div class="panel-section">
                                <div>
                                    <div class="panel-title">3. Market Profile & Company Contracts</div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label>Booking Source</label>
                                            <select name="booking_source" class="form-select" required>
                                                <option value="Direct">Direct / Walk-In</option>
                                                <option value="Booking.com">Booking.com OTA</option>
                                                <option value="Agoda">Agoda Network</option>
                                                <option value="Expedia">Expedia Group</option>
                                                <option value="Travel Agent">Local Corporate Agent</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Company / Agent Name</label>
                                            <select name="company_name" class="form-select" required>
                                                <option value="INDIVIDUAL / FIT">INDIVIDUAL / WALK-IN GUEST</option>
                                                <option value="BANK PROMO 24 - AMEX">BANK PROMO 24 - AMEX CONTRACT</option>
                                                <option value="Aitken Spence Travels">Aitken Spence Travels PLC</option>
                                                <option value="Jetwing Travels">Jetwing Travels (Pvt) Ltd</option>
                                                <option value="Dialog Axiata Corporate">Dialog Axiata Corporate Account</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label>Rate Code</label>
                                            <select name="rate_code" class="form-select" required>
                                                <option value="AB - FIT LOCAL">AB - FIT LOCAL (Sri Lankan Resident)</option>
                                                <option value="CORP - L3">CORP - L3 (Standard Corporate Rate)</option>
                                                <option value="RAC - 2026">RAC - 2026 (Published Rack Rate)</option>
                                                <option value="PROMO - EBD">PROMO - EBD (Early Bird Promotion)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Package Assigned</label>
                                            <select name="package_name" class="form-select" required>
                                                <option value="STANDARD">STANDARD ROOM CHARGES ONLY</option>
                                                <option value="HONEYMOON SPECIAL">HONEYMOON AMENITIES INCLUDED</option>
                                                <option value="SPA INCLUSIVE">SPA & WELCOME INCLUSIVE PACK</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label>Market Segment</label>
                                            <select name="market_segment" class="form-select" required>
                                                <option value="Sri Lankan">Sri Lankan Resident Market</option>
                                                <option value="European Leisure">European Leisure Inbound</option>
                                                <option value="Middle East Luxury">Middle East Luxury Segment</option>
                                                <option value="Asian Corporate">Asian Corporate Executive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Business Segment</label>
                                            <select name="business_segment" class="form-select" required>
                                                <option value="FIT LOCAL">FIT LOCAL (Free Independent Traveler)</option>
                                                <option value="GIT">GIT (Group Inclusive Tour)</option>
                                                <option value="CORPORATE">CORPORATE CONTRACT CONTRACTED</option>
                                                <option value="COMPLIMENTARY">COMPLIMENTARY / FOC STAY</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label>Sales Person</label>
                                            <input type="text" name="sales_person" class="form-control" value="NA" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="no-req">Voucher No</label>
                                            <input type="text" name="voucher_no" class="form-control" placeholder="Optional">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="no-req">Tour No</label>
                                            <input type="text" name="tour_no" class="form-control" placeholder="Optional">
                                        </div>
                                    </div>
                                </div>
                                <div class="action-footer">
                                    <button type="button" class="btn btn-pms btn-pms-back" onclick="goToStep(2)"><i class="bi bi-arrow-left"></i> Back</button>
                                    <button type="button" class="btn btn-pms btn-pms-next" onclick="goToStep(4)">Next: Meal Plan & Rates <i class="bi bi-arrow-right"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane" id="step4">
                            <div class="panel-section">
                                <div>
                                    <div class="panel-title">4. Meal plans & Complete Reservation</div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-4">
                                            <label>Meal Plan Setup</label>
                                            <select name="meal_plan" class="form-select" required>
                                                <option value="Half Board">Half Board (HB)</option>
                                                <option value="Full Board">Full Board (FB)</option>
                                                <option value="Bed & Breakfast">Bed & Breakfast (BB)</option>
                                                <option value="Room Only">Room Only (RO)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Arrive For First Meal</label>
                                            <select name="arrive_for" class="form-select">
                                                <option value="Dinner">Dinner</option>
                                                <option value="Lunch">Lunch</option>
                                                <option value="Breakfast">Breakfast</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Leave After Last Meal</label>
                                            <select name="leave_after" class="form-select">
                                                <option value="Breakfast">Breakfast</option>
                                                <option value="Lunch">Lunch</option>
                                                <option value="Dinner">Dinner</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label>Guest Payment Mode</label>
                                            <select name="guest_payment_mode" class="form-select">
                                                <option value="Guest">Guest Directly Pays</option>
                                                <option value="Company">Company / Agent Invoice Billing</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Visit Purpose</label>
                                            <input type="text" name="visit_purpose" class="form-control" value="LEISURE" required>
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-12 d-flex gap-4 p-2 bg-light rounded border border-secondary-subtle justify-content-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="bill_payment_lkr_sscl" value="1" id="ssclCheck" checked>
                                                <label class="form-check-label no-req" for="ssclCheck">Bill Payment LKR (For SSCL)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="company_credit_enable" value="1" id="creditCheck">
                                                <label class="form-check-label no-req" for="creditCheck">Company Credit Settlement Enable</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="no-req">Remarks & Front Desk Internal Notes</label>
                                        <textarea name="special_requests" class="form-control" rows="3" placeholder="Add custom request tags (e.g., 1 DBL DLX HB, Sea view...)"></textarea>
                                    </div>
                                </div>
                                <div class="action-footer">
                                    <button type="button" class="btn btn-pms btn-pms-back" onclick="goToStep(3)"><i class="bi bi-arrow-left"></i> Back</button>
                                    <button type="submit" name="save_reservation" class="btn btn-pms btn-pms-submit"><i class="bi bi-shield-check"></i> Complete Reservation</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
    // Nights counter calculation logic
    function calculateNights() {
        const checkInVal = document.getElementById('check_in').value;
        const checkOutVal = document.getElementById('check_out').value;
        
        if(checkInVal && checkOutVal) {
            const date1 = new Date(checkInVal);
            const date2 = new Date(checkOutVal);
            const diffTime = date2 - date1;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            document.getElementById('num_of_nights').value = diffDays > 0 ? diffDays : 1;
        }
    }
    document.getElementById('check_in').addEventListener('change', calculateNights);
    document.getElementById('check_out').addEventListener('change', calculateNights);

    // Javascript Custom Wizard Engine
    function goToStep(stepNumber) {
        // Hide all tab panes
        const allPanes = document.querySelectorAll('.tab-content .tab-pane');
        allPanes.forEach(pane => {
            pane.classList.remove('active');
        });

        // Show the current step pane
        const targetPane = document.getElementById('step' + stepNumber);
        if(targetPane) {
            targetPane.classList.add('active');
        }

        // Sidebar Highlights updates
        for (let i = 1; i <= 4; i++) {
            const sidebarTab = document.getElementById('step' + i + '-tab');
            if (sidebarTab) {
                if (i < stepNumber) {
                    sidebarTab.classList.add('completed');
                    sidebarTab.classList.remove('active');
                } else if (i === stepNumber) {
                    sidebarTab.classList.remove('completed');
                    sidebarTab.classList.add('active');
                } else {
                    sidebarTab.classList.remove('completed', 'active');
                }
            }
        }
    }

    // '+' Plus Action Trigger Reset Form
    function resetReservationForm() {
        if(confirm("Are you sure you want to clear and open a new reservation screen?")) {
            document.getElementById('reservationForm').reset();
            calculateNights();
            goToStep(1);
        }
    }
</script>
</body>
</html>