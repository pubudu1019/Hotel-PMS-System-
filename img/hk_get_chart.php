<?php
include '../includes/db.php';

// ===== GET ROOM STATUS COUNTS =====
$status_counts = [];
$status_types = ['available', 'occupied', 'cleaning', 'maintenance'];
foreach ($status_types as $s) {
    $q = $conn->query("SELECT COUNT(*) as cnt FROM rooms WHERE status = '$s'");
    $status_counts[$s] = $q->fetch_assoc()['cnt'];
}

// ===== GET ROOMS =====
$rooms = $conn->query("
    SELECT r.room_id, r.room_number, r.room_type, r.status,
           res.guest_name, res.res_id
    FROM rooms r
    LEFT JOIN reservations res ON r.room_id = res.room_id AND res.status = 'Checked-In'
    ORDER BY r.room_number ASC
");
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        /* ===== ROOM GRID ===== */
        .hk-chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
        }
        .hk-room-card {
            padding: 10px 4px 8px;
            text-align: center;
            border-radius: 10px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            border: 2px solid transparent;
            min-height: 64px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            transition: all 0.25s ease;
        }
        .hk-room-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .hk-room-card:active { transform: scale(0.95); }
        .hk-room-card .room-number { font-size: 20px; font-weight: 800; line-height: 1.2; }
        .hk-room-card .room-type { font-size: 8px; opacity: 0.8; margin-top: 1px; }
        .hk-room-card .guest-name { font-size: 8px; opacity: 0.9; margin-top: 2px; background: rgba(0,0,0,0.15); padding: 0 6px; border-radius: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
        .hk-room-card .status-label { font-size: 8px; opacity: 0.9; margin-top: 3px; background: rgba(0,0,0,0.12); padding: 1px 8px; border-radius: 12px; }
        
        .hk-room-card.status-available { background: #10b981; }
        .hk-room-card.status-available:hover { background: #059669; }
        .hk-room-card.status-occupied { background: #ef4444; }
        .hk-room-card.status-occupied:hover { background: #dc2626; }
        .hk-room-card.status-cleaning { background: #f59e0b; color: #1e293b; }
        .hk-room-card.status-cleaning:hover { background: #d97706; color: #fff; }
        .hk-room-card.status-maintenance { background: #6b7280; }
        .hk-room-card.status-maintenance:hover { background: #4b5563; }

        /* ===== STATS ===== */
        .hk-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }
        .hk-stat {
            background: white;
            padding: 10px 12px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #94a3b8;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .hk-stat .stat-number { font-size: 24px; font-weight: 800; }
        .hk-stat .stat-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .hk-stat .stat-desc { font-size: 9px; color: #94a3b8; margin-top: 2px; }
        .hk-stat.available { border-left-color: #10b981; }
        .hk-stat.available .stat-number { color: #10b981; }
        .hk-stat.occupied { border-left-color: #ef4444; }
        .hk-stat.occupied .stat-number { color: #ef4444; }
        .hk-stat.cleaning { border-left-color: #f59e0b; }
        .hk-stat.cleaning .stat-number { color: #f59e0b; }
        .hk-stat.maintenance { border-left-color: #6b7280; }
        .hk-stat.maintenance .stat-number { color: #6b7280; }

        /* ============================================================ */
        /* ===== POPUP - BIG & MODERN ===== */
        /* ============================================================ */
        .hk-popup-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(6px);
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .hk-popup-overlay.active { display: flex; }

        .hk-popup {
            background: white;
            border-radius: 20px;
            padding: 0;
            max-width: 600px;
            width: 100%;
            max-height: 92vh;
            box-shadow: 0 30px 80px rgba(0,0,0,0.35);
            animation: popIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        @keyframes popIn {
            from { transform: scale(0.9) translateY(30px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }

        /* ===== POPUP HEADER ===== */
        .hk-popup-header {
            background: linear-gradient(135deg, #0d4b68, #1a6f8e);
            padding: 20px 28px 16px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        .hk-popup-header .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .hk-popup-header .header-left .icon-box {
            font-size: 28px;
            background: rgba(255,255,255,0.15);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
        }
        .hk-popup-header .header-left h4 {
            margin: 0;
            font-weight: 700;
            font-size: 19px;
            letter-spacing: -0.3px;
        }
        .hk-popup-header .header-left p {
            margin: 2px 0 0;
            opacity: 0.8;
            font-size: 13px;
        }
        .hk-popup-header .close-btn {
            background: rgba(255,255,255,0.12);
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            flex-shrink: 0;
        }
        .hk-popup-header .close-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: rotate(90deg);
        }

        /* ===== POPUP BODY (SCROLLABLE) ===== */
        .hk-popup-body {
            padding: 24px 28px 28px;
            overflow-y: auto;
            flex: 1;
            max-height: calc(92vh - 80px);
        }
        .hk-popup-body::-webkit-scrollbar {
            width: 6px;
        }
        .hk-popup-body::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        .hk-popup-body::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }
        .hk-popup-body::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* ===== ROOM INFO ===== */
        .hk-popup-room-info {
            background: #f1f5f9;
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid #0d4b68;
            flex-shrink: 0;
        }
        .hk-popup-room-info .room-number {
            font-size: 24px;
            font-weight: 800;
            color: #0d4b68;
        }
        .hk-popup-room-info .current-status {
            font-size: 14px;
            padding: 5px 20px;
            border-radius: 20px;
            font-weight: 700;
        }
        .hk-popup-room-info .current-status.available { background: #d1fae5; color: #065f46; }
        .hk-popup-room-info .current-status.occupied { background: #fee2e2; color: #991b1b; }
        .hk-popup-room-info .current-status.cleaning { background: #fef3c7; color: #92400e; }
        .hk-popup-room-info .current-status.maintenance { background: #f1f5f9; color: #4b5563; }

        /* ===== STATUS GRID ===== */
        .hk-popup-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 10px;
            letter-spacing: 0.3px;
        }
        .hk-popup-status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .hk-popup-status-btn {
            padding: 16px 10px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.25s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
        }
        .hk-popup-status-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }
        .hk-popup-status-btn:active { transform: scale(0.95); }
        .hk-popup-status-btn .icon { font-size: 28px; }
        .hk-popup-status-btn .label { font-size: 12px; font-weight: 600; }
        .hk-popup-status-btn .desc { font-size: 10px; color: #94a3b8; font-weight: 400; }

        .hk-popup-status-btn.available { border-color: #10b981; color: #065f46; }
        .hk-popup-status-btn.available:hover { background: #d1fae5; }
        .hk-popup-status-btn.cleaning { border-color: #f59e0b; color: #92400e; }
        .hk-popup-status-btn.cleaning:hover { background: #fef3c7; }
        .hk-popup-status-btn.occupied { border-color: #ef4444; color: #991b1b; }
        .hk-popup-status-btn.occupied:hover { background: #fee2e2; }
        .hk-popup-status-btn.maintenance { border-color: #6b7280; color: #4b5563; }
        .hk-popup-status-btn.maintenance:hover { background: #f1f5f9; }

        .hk-popup-status-btn.current {
            border-color: #0d4b68 !important;
            background: #e8f4f8 !important;
            opacity: 0.5;
            cursor: not-allowed;
        }
        .hk-popup-status-btn.current:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        .hk-popup-status-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* ============================================================ */
        /* ===== TOAST ===== */
        /* ============================================================ */
        #hkToast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999999;
            padding: 14px 28px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            gap: 10px;
            max-width: 420px;
            animation: slideUp 0.4s ease;
        }
        #hkToast.success { background: #10b981; }
        #hkToast.error { background: #ef4444; }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* ============================================================ */
        /* ===== RESPONSIVE ===== */
        /* ============================================================ */
        @media (max-width: 768px) {
            .hk-chart-grid { grid-template-columns: repeat(auto-fill, minmax(68px, 1fr)); gap: 5px; }
            .hk-room-card { padding: 8px 3px; min-height: 56px; }
            .hk-room-card .room-number { font-size: 16px; }
            .hk-stats { grid-template-columns: repeat(2, 1fr); gap: 6px; }
            .hk-stat .stat-number { font-size: 20px; }
            .hk-popup { max-width: 95%; max-height: 95vh; }
            .hk-popup-body { padding: 18px 20px 22px; }
            .hk-popup-header { padding: 16px 20px 14px; }
            .hk-popup-header .header-left h4 { font-size: 17px; }
            .hk-popup-status-btn { padding: 14px 8px; font-size: 13px; }
            .hk-popup-status-btn .icon { font-size: 24px; }
            .hk-popup-room-info .room-number { font-size: 20px; }
        }
        @media (max-width: 480px) {
            .hk-chart-grid { grid-template-columns: repeat(auto-fill, minmax(58px, 1fr)); gap: 4px; }
            .hk-room-card { padding: 6px 2px; min-height: 46px; border-radius: 6px; }
            .hk-room-card .room-number { font-size: 14px; }
            .hk-room-card .status-label { font-size: 7px; padding: 0 4px; }
            .hk-stat .stat-number { font-size: 17px; }
            .hk-popup { max-width: 100%; border-radius: 12px; margin: 8px; max-height: 98vh; }
            .hk-popup-body { padding: 14px 16px 18px; }
            .hk-popup-status-btn { padding: 12px 6px; font-size: 12px; }
            .hk-popup-status-btn .icon { font-size: 20px; }
            .hk-popup-status-btn .desc { display: none; }
            .hk-popup-header .header-left h4 { font-size: 15px; }
            .hk-popup-header .header-left p { font-size: 10px; }
            .hk-popup-header .header-left .icon-box { width: 40px; height: 40px; font-size: 22px; }
        }
    </style>
</head>
<body>

    <!-- ===== STATS ===== -->
    <div class="hk-stats" id="hkStats">
        <div class="hk-stat available">
            <div class="stat-number">🟢 <?= $status_counts['available'] ?></div>
            <div class="stat-label">Vacant / Clean</div>
            <div class="stat-desc">Ready for check-in</div>
        </div>
        <div class="hk-stat occupied">
            <div class="stat-number">🔴 <?= $status_counts['occupied'] ?></div>
            <div class="stat-label">Occupied</div>
            <div class="stat-desc">Guests in room</div>
        </div>
        <div class="hk-stat cleaning">
            <div class="stat-number">🟡 <?= $status_counts['cleaning'] ?></div>
            <div class="stat-label">Cleaning / Dirty</div>
            <div class="stat-desc">In cleaning process</div>
        </div>
        <div class="hk-stat maintenance">
            <div class="stat-number">⚫ <?= $status_counts['maintenance'] ?></div>
            <div class="stat-label">Maintenance</div>
            <div class="stat-desc">Out of order</div>
        </div>
    </div>

    <!-- ===== ROOM GRID ===== -->
    <div class="hk-chart-grid" id="roomChartGrid">
        <?php while($r = $rooms->fetch_assoc()): 
            $status = $r['status'] ?? 'available';
            $guest_name = $r['guest_name'] ?? '';
            $guest_short = strlen($guest_name) > 10 ? substr($guest_name, 0, 8) . '…' : $guest_name;
        ?>
            <div class="hk-room-card status-<?= $status ?>"
                 data-room-id="<?= $r['room_id'] ?>"
                 data-room-number="<?= $r['room_number'] ?>"
                 data-current-status="<?= $status ?>"
                 onclick="openStatusPopup(this)"
                 title="Click to change status">
                <div class="room-number"><?= $r['room_number'] ?></div>
                <div class="room-type"><?= $r['room_type'] ?? '' ?></div>
                <?php if($guest_name && $status === 'occupied'): ?>
                    <div class="guest-name"><i class="fas fa-user"></i> <?= $guest_short ?></div>
                <?php endif; ?>
                <div class="status-label"><?= ucfirst($status) ?></div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- ============================================================ -->
    <!-- ===== POPUP (BIG & MODERN) ===== -->
    <!-- ============================================================ -->
    <div class="hk-popup-overlay" id="statusPopup">
        <div class="hk-popup">
            
            <!-- Header -->
            <div class="hk-popup-header">
                <div class="header-left">
                    <span class="icon-box">🔄</span>
                    <div>
                        <h4>Change Status</h4>
                        <p>Update room availability instantly</p>
                    </div>
                </div>
                <button class="close-btn" onclick="closeStatusPopup()" title="Close (ESC)">✕</button>
            </div>
            
            <!-- Body (Scrollable) -->
            <div class="hk-popup-body">
                
                <!-- Room Info -->
                <div class="hk-popup-room-info">
                    <span class="room-number" id="popupRoomNumber">Room 101</span>
                    <span class="current-status" id="popupCurrentStatus">Available</span>
                </div>
                
                <!-- Status Buttons -->
                <div class="hk-popup-label">Select new status:</div>
                <div class="hk-popup-status-grid">
                    <button class="hk-popup-status-btn available" onclick="changeStatus('available')">
                        <span class="icon">🟢</span>
                        <span class="label">Vacant Clean</span>
                        <span class="desc">Ready for check-in</span>
                    </button>
                    <button class="hk-popup-status-btn cleaning" onclick="changeStatus('cleaning')">
                        <span class="icon">🟡</span>
                        <span class="label">Dirty / Cleaning</span>
                        <span class="desc">In cleaning process</span>
                    </button>
                    <button class="hk-popup-status-btn occupied" onclick="changeStatus('occupied')">
                        <span class="icon">🔴</span>
                        <span class="label">Occupied</span>
                        <span class="desc">Guest in room</span>
                    </button>
                    <button class="hk-popup-status-btn maintenance" onclick="changeStatus('maintenance')">
                        <span class="icon">⚫</span>
                        <span class="label">Maintenance</span>
                        <span class="desc">Out of order</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== TOAST ===== -->
    <div id="hkToast">
        <span class="toast-icon" id="toastIcon">✅</span>
        <span id="toastMessage">Success</span>
    </div>

    <!-- ============================================================ -->
    <!-- ===== JAVASCRIPT ===== -->
    <!-- ============================================================ -->
    <script>
    // ================================================================
    // ===== VARIABLES =====
    // ================================================================
    let selectedRoomId = null;
    let selectedRoomNumber = null;
    let currentStatus = null;
    let isProcessing = false;

    // ================================================================
    // ===== OPEN POPUP =====
    // ================================================================
    function openStatusPopup(element) {
        if (isProcessing) return;

        selectedRoomId = element.dataset.roomId;
        selectedRoomNumber = element.dataset.roomNumber;
        currentStatus = element.dataset.currentStatus;

        document.getElementById('popupRoomNumber').textContent = 'Room ' + selectedRoomNumber;
        document.getElementById('popupCurrentStatus').textContent = currentStatus;
        document.getElementById('popupCurrentStatus').className = 'current-status ' + currentStatus;

        const btnMap = {
            'available': 'Vacant Clean',
            'cleaning': 'Dirty / Cleaning',
            'occupied': 'Occupied',
            'maintenance': 'Maintenance'
        };
        document.querySelectorAll('.hk-popup-status-btn').forEach(btn => {
            btn.classList.remove('current');
            btn.disabled = false;
            btn.style.opacity = '1';
            if (btn.textContent.includes(btnMap[currentStatus] || '')) {
                btn.classList.add('current');
                btn.disabled = true;
                btn.style.opacity = '0.6';
            }
        });

        document.getElementById('statusPopup').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // ================================================================
    // ===== CLOSE POPUP =====
    // ================================================================
    function closeStatusPopup() {
        document.getElementById('statusPopup').classList.remove('active');
        document.body.style.overflow = '';
        selectedRoomId = null;
        selectedRoomNumber = null;
        currentStatus = null;
    }

    // ================================================================
    // ===== CHANGE STATUS =====
    // ================================================================
    function changeStatus(newStatus) {
        if (isProcessing) return;
        if (!selectedRoomId) {
            showToast('No room selected', 'error');
            return;
        }
        if (newStatus === currentStatus) {
            showToast('Status is already ' + newStatus, 'error');
            return;
        }

        isProcessing = true;
        const buttons = document.querySelectorAll('.hk-popup-status-btn');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = '0.4';
        });

        const formData = new FormData();
        formData.append('room_id', selectedRoomId);
        formData.append('new_status', newStatus);

        const apiUrl = '/hotel_management_structure/img/hk_change_status.php';

        fetch(apiUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            isProcessing = false;
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });

            if (data.success) {
                showToast('✅ ' + (data.message || 'Status updated successfully!'), 'success');

                const cards = document.querySelectorAll('.hk-room-card');
                cards.forEach(card => {
                    if (card.dataset.roomId == selectedRoomId) {
                        card.className = 'hk-room-card status-' + newStatus;
                        card.dataset.currentStatus = newStatus;
                        const labelEl = card.querySelector('.status-label');
                        if (labelEl) labelEl.textContent = newStatus;
                        
                        const guestEl = card.querySelector('.guest-name');
                        if (guestEl) {
                            guestEl.style.display = (newStatus === 'occupied') ? 'block' : 'none';
                        }
                    }
                });

                updateStats(newStatus, currentStatus);

                currentStatus = newStatus;
                document.getElementById('popupCurrentStatus').textContent = newStatus;
                document.getElementById('popupCurrentStatus').className = 'current-status ' + newStatus;

                const btnMap = {
                    'available': 'Vacant Clean',
                    'cleaning': 'Dirty / Cleaning',
                    'occupied': 'Occupied',
                    'maintenance': 'Maintenance'
                };
                document.querySelectorAll('.hk-popup-status-btn').forEach(btn => {
                    btn.classList.remove('current');
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    if (btn.textContent.includes(btnMap[newStatus] || '')) {
                        btn.classList.add('current');
                        btn.disabled = true;
                        btn.style.opacity = '0.6';
                    }
                });

                setTimeout(() => {
                    closeStatusPopup();
                }, 1200);

            } else {
                showToast('❌ ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            isProcessing = false;
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
            showToast('❌ Network error: ' + error.message, 'error');
        });
    }

    // ================================================================
    // ===== UPDATE STATS =====
    // ================================================================
    function updateStats(newStatus, oldStatus) {
        const oldStat = document.querySelector('.hk-stat.' + oldStatus + ' .stat-number');
        const newStat = document.querySelector('.hk-stat.' + newStatus + ' .stat-number');

        if (oldStat) {
            let text = oldStat.textContent;
            let val = parseInt(text.replace(/[^0-9]/g, '')) || 0;
            oldStat.textContent = text.replace(/[0-9]+/, Math.max(0, val - 1));
        }
        if (newStat) {
            let text = newStat.textContent;
            let val = parseInt(text.replace(/[^0-9]/g, '')) || 0;
            newStat.textContent = text.replace(/[0-9]+/, val + 1);
        }
    }

    // ================================================================
    // ===== SHOW TOAST =====
    // ================================================================
    function showToast(message, type = 'success') {
        const toast = document.getElementById('hkToast');
        const icon = document.getElementById('toastIcon');
        const msgEl = document.getElementById('toastMessage');

        icon.textContent = type === 'success' ? '✅' : '❌';
        msgEl.textContent = message;
        toast.className = type;
        toast.style.display = 'flex';
        toast.style.animation = 'none';
        requestAnimationFrame(() => {
            toast.style.animation = 'slideUp 0.4s ease';
        });

        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => {
            toast.style.display = 'none';
        }, 4000);
    }

    // ================================================================
    // ===== CLOSE ON ESCAPE =====
    // ================================================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeStatusPopup();
        }
    });

    // ================================================================
    // ===== CLOSE ON OUTSIDE CLICK =====
    // ================================================================
    document.getElementById('statusPopup').addEventListener('click', function(e) {
        if (e.target === this) {
            closeStatusPopup();
        }
    });

    console.log('✅ hk_get_chart.php loaded successfully!');
    console.log('📍 Room cards found:', document.querySelectorAll('.hk-room-card').length);
    </script>
</body>
</html>