<?php
include 'includes/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 0;
        }
        
        .modal-box {
            background: white;
            max-width: 500px;
            width: 100%;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.35);
            max-height: 95vh;
            display: flex;
            flex-direction: column;
            margin: 0 auto;
        }
        
        /* Header */
        .modal-header {
            background: linear-gradient(135deg, #0d4b68, #1a6f8e);
            padding: 18px 24px 14px;
            color: white;
            position: relative;
            flex-shrink: 0;
        }
        .modal-header .header-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .modal-header .icon-box {
            font-size: 24px;
            background: rgba(255,255,255,0.15);
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }
        .modal-header h4 {
            margin: 0;
            font-weight: 700;
            font-size: 17px;
        }
        .modal-header p {
            margin: 2px 0 0;
            opacity: 0.8;
            font-size: 12px;
        }
        .modal-header .close-btn {
            position: absolute;
            top: 12px;
            right: 14px;
            background: rgba(255,255,255,0.12);
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }
        .modal-header .close-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: rotate(90deg);
        }
        
        /* Body */
        .modal-body {
            padding: 20px 24px 24px;
            background: #ffffff;
            overflow-y: auto;
            flex: 1;
        }
        .modal-body::-webkit-scrollbar {
            width: 5px;
        }
        .modal-body::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .modal-body::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }
        
        /* Search */
        .search-box {
            margin-bottom: 16px;
            position: relative;
        }
        .search-box input {
            width: 100%;
            padding: 10px 14px 10px 40px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: 0.3s;
            background: #f8fafc;
            font-family: inherit;
        }
        .search-box input:focus {
            border-color: #0d4b68;
            box-shadow: 0 0 0 4px rgba(13,75,104,0.1);
            background: #ffffff;
            outline: none;
        }
        .search-box .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
        }
        .search-box .search-count {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 11px;
            color: #94a3b8;
            background: #f1f5f9;
            padding: 2px 10px;
            border-radius: 12px;
        }
        
        /* Room Grid */
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(85px, 1fr));
            gap: 8px;
            max-height: 250px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .room-grid::-webkit-scrollbar {
            width: 4px;
        }
        .room-grid::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }
        
        .room-card {
            padding: 10px 4px;
            text-align: center;
            border-radius: 10px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.25s ease;
            min-height: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .room-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            border-color: rgba(255,255,255,0.5);
        }
        .room-card:active { transform: scale(0.95); }
        .room-card .room-number { font-size: 15px; font-weight: 800; }
        .room-card .room-type { font-size: 8px; opacity: 0.85; }
        .room-card .guest-name { 
            font-size: 7px; 
            opacity: 0.9; 
            margin-top: 2px; 
            background: rgba(0,0,0,0.15); 
            padding: 0 6px; 
            border-radius: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        .room-card .status-label {
            font-size: 7px;
            opacity: 0.9;
            margin-top: 2px;
            background: rgba(0,0,0,0.12);
            padding: 0 6px;
            border-radius: 10px;
        }
        
        .room-card.available { background: #10b981; }
        .room-card.available:hover { background: #059669; }
        .room-card.occupied { background: #ef4444; }
        .room-card.occupied:hover { background: #dc2626; }
        .room-card.cleaning { background: #f59e0b; color: #1e293b; }
        .room-card.cleaning:hover { background: #d97706; color: #fff; }
        .room-card.maintenance { background: #6b7280; }
        .room-card.maintenance:hover { background: #4b5563; }
        
        /* Status Popup */
        .status-popup-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }
        .status-popup-overlay.active { display: flex; }
        
        .status-popup {
            background: white;
            border-radius: 16px;
            padding: 24px 28px 20px;
            max-width: 400px;
            width: 92%;
            box-shadow: 0 24px 60px rgba(0,0,0,0.35);
            animation: popIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes popIn {
            from { transform: scale(0.8) translateY(20px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }
        
        .status-popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eef2f5;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .status-popup-header h4 { margin: 0; color: #0d4b68; font-weight: 700; font-size: 17px; }
        .status-popup-header .close-btn {
            background: none; border: none; font-size: 22px; cursor: pointer;
            color: #94a3b8; transition: 0.3s; padding: 0 4px;
        }
        .status-popup-header .close-btn:hover { color: #ef4444; transform: rotate(90deg); }
        
        .status-popup-room-info {
            background: #f1f5f9;
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-popup-room-info .room-number { font-size: 20px; font-weight: 800; color: #0d4b68; }
        .status-popup-room-info .current-status {
            font-size: 12px;
            padding: 3px 14px;
            border-radius: 20px;
            font-weight: 600;
        }
        .status-popup-room-info .current-status.available { background: #d1fae5; color: #065f46; }
        .status-popup-room-info .current-status.occupied { background: #fee2e2; color: #991b1b; }
        .status-popup-room-info .current-status.cleaning { background: #fef3c7; color: #92400e; }
        .status-popup-room-info .current-status.maintenance { background: #f1f5f9; color: #4b5563; }
        
        .status-popup-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .status-popup-btn {
            padding: 10px 6px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.25s ease;
            text-align: center;
        }
        .status-popup-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .status-popup-btn:active { transform: scale(0.95); }
        .status-popup-btn .icon { font-size: 22px; display: block; }
        .status-popup-btn .label { font-size: 10px; font-weight: 600; display: block; }
        
        .status-popup-btn.available { border-color: #10b981; color: #065f46; }
        .status-popup-btn.available:hover { background: #d1fae5; }
        .status-popup-btn.cleaning { border-color: #f59e0b; color: #92400e; }
        .status-popup-btn.cleaning:hover { background: #fef3c7; }
        .status-popup-btn.occupied { border-color: #ef4444; color: #991b1b; }
        .status-popup-btn.occupied:hover { background: #fee2e2; }
        .status-popup-btn.maintenance { border-color: #6b7280; color: #4b5563; }
        .status-popup-btn.maintenance:hover { background: #f1f5f9; }
        
        .status-popup-btn.current {
            border-color: #0d4b68 !important;
            background: #e8f4f8 !important;
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Toast */
        #toastMessage {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999999;
            padding: 12px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 13px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            display: none;
            animation: slideUp 0.4s ease;
            max-width: 400px;
        }
        #toastMessage.success { background: #10b981; }
        #toastMessage.error { background: #ef4444; }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .spinner-small {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .no-results {
            text-align: center;
            padding: 20px 0;
            color: #94a3b8;
            font-size: 13px;
        }
        .no-results i { font-size: 24px; display: block; margin-bottom: 6px; }
        
        /* ===== CLOSE BUTTON FOR IFRAME ===== */
        .iframe-close-btn {
            display: none;
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 99999;
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 20px;
            cursor: pointer;
            transition: 0.3s;
        }
        .iframe-close-btn:hover {
            background: rgba(0,0,0,0.9);
            transform: rotate(90deg);
        }
        .modal-box .close-btn {
            display: block;
        }
    </style>
</head>
<body>

<div class="modal-box">
    <!-- Header -->
    <div class="modal-header">
        <div class="header-content">
            <span class="icon-box">🔄</span>
            <div>
                <h4>Change Room Status</h4>
                <p>Click a room to update status</p>
            </div>
        </div>
        <button class="close-btn" onclick="closeModal()">✕</button>
    </div>
    
    <!-- Body -->
    <div class="modal-body">
        
        <!-- Search -->
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" id="roomSearch" placeholder="Search room number..." oninput="filterRooms()">
            <span class="search-count" id="roomCount">0 rooms</span>
        </div>
        
        <!-- Room Grid -->
        <div class="room-grid" id="roomGrid">
            <?php
            $rooms = $conn->query("
                SELECT r.*, res.guest_name 
                FROM rooms r
                LEFT JOIN reservations res ON r.room_id = res.room_id AND res.status = 'Checked-In'
                ORDER BY r.room_number ASC
            ");
            if($rooms && $rooms->num_rows > 0):
                while($r = $rooms->fetch_assoc()):
                    $status = $r['status'] ?? 'available';
                    $guest_name = $r['guest_name'] ?? '';
                    $guest_short = strlen($guest_name) > 8 ? substr($guest_name, 0, 6) . '…' : $guest_name;
            ?>
            <div class="room-card <?= $status ?>" 
                 data-room-id="<?= $r['room_id'] ?>"
                 data-room-number="<?= $r['room_number'] ?>"
                 data-status="<?= $status ?>"
                 onclick="openStatusPopup(this)">
                <div class="room-number"><?= $r['room_number'] ?></div>
                <div class="room-type"><?= $r['room_type'] ?? '' ?></div>
                <?php if($guest_name && $status === 'occupied'): ?>
                    <div class="guest-name">👤 <?= $guest_short ?></div>
                <?php endif; ?>
                <div class="status-label"><?= ucfirst($status) ?></div>
            </div>
            <?php endwhile; else: ?>
            <div class="no-results" style="display:block; grid-column:1/-1;">
                <i class="fas fa-bed"></i>
                No rooms found
            </div>
            <?php endif; ?>
        </div>
        
        <div class="no-results" id="noResults" style="display:none;">
            <i class="fas fa-search"></i>
            No rooms found
        </div>
    </div>
</div>

<!-- Status Popup -->
<div class="status-popup-overlay" id="statusPopupOverlay">
    <div class="status-popup">
        <div class="status-popup-header">
            <h4>🔄 Change Status</h4>
            <button class="close-btn" onclick="closeStatusPopup()">✕</button>
        </div>
        <div class="status-popup-room-info">
            <span class="room-number" id="popupRoomNumber">Room 101</span>
            <span class="current-status" id="popupCurrentStatus">Available</span>
        </div>
        <div class="status-popup-grid">
            <button class="status-popup-btn available" onclick="changeStatus('available')">
                <span class="icon">🟢</span>
                <span class="label">Vacant Clean</span>
            </button>
            <button class="status-popup-btn cleaning" onclick="changeStatus('cleaning')">
                <span class="icon">🟡</span>
                <span class="label">Dirty / Cleaning</span>
            </button>
            <button class="status-popup-btn occupied" onclick="changeStatus('occupied')">
                <span class="icon">🔴</span>
                <span class="label">Occupied</span>
            </button>
            <button class="status-popup-btn maintenance" onclick="changeStatus('maintenance')">
                <span class="icon">⚫</span>
                <span class="label">Maintenance</span>
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toastMessage"></div>

<script>
// ===== SEARCH =====
function filterRooms() {
    const query = document.getElementById('roomSearch').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.room-card');
    let count = 0;
    
    cards.forEach(card => {
        const roomNumber = card.dataset.roomNumber || '';
        if (roomNumber.includes(query) || query === '') {
            card.style.display = '';
            count++;
        } else {
            card.style.display = 'none';
        }
    });
    
    document.getElementById('roomCount').textContent = count + ' rooms';
    document.getElementById('noResults').style.display = count === 0 ? 'block' : 'none';
}

// ===== STATUS POPUP =====
let selectedRoomId = null;
let selectedRoomNumber = null;
let currentStatus = null;
let isProcessing = false;

function openStatusPopup(element) {
    if (isProcessing) return;
    
    selectedRoomId = element.dataset.roomId;
    selectedRoomNumber = element.dataset.roomNumber;
    currentStatus = element.dataset.status;

    document.getElementById('popupRoomNumber').textContent = 'Room ' + selectedRoomNumber;
    const statusEl = document.getElementById('popupCurrentStatus');
    statusEl.textContent = currentStatus;
    statusEl.className = 'current-status ' + currentStatus;

    // Highlight current status
    const btnMap = {
        'available': 'Vacant Clean',
        'cleaning': 'Dirty / Cleaning',
        'occupied': 'Occupied',
        'maintenance': 'Maintenance'
    };
    document.querySelectorAll('.status-popup-btn').forEach(btn => {
        btn.classList.remove('current');
        btn.disabled = false;
        btn.style.opacity = '1';
        if (btn.textContent.includes(btnMap[currentStatus] || '')) {
            btn.classList.add('current');
            btn.disabled = true;
            btn.style.opacity = '0.6';
        }
    });

    document.getElementById('statusPopupOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeStatusPopup() {
    document.getElementById('statusPopupOverlay').classList.remove('active');
    document.body.style.overflow = '';
    selectedRoomId = null;
    selectedRoomNumber = null;
    currentStatus = null;
}

// ===== CHANGE STATUS =====
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
    const buttons = document.querySelectorAll('.status-popup-btn');
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
    .then(response => response.json())
    .then(data => {
        isProcessing = false;
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });

        if (data.success) {
            showToast('✅ ' + data.message, 'success');

            // Update card
            const cards = document.querySelectorAll('.room-card');
            cards.forEach(card => {
                if (card.dataset.roomId == selectedRoomId) {
                    card.className = 'room-card ' + newStatus;
                    card.dataset.status = newStatus;
                    const labelEl = card.querySelector('.status-label');
                    if (labelEl) labelEl.textContent = ucfirst(newStatus);
                    const guestEl = card.querySelector('.guest-name');
                    if (guestEl) {
                        guestEl.style.display = (newStatus === 'occupied') ? '' : 'none';
                    }
                }
            });

            currentStatus = newStatus;
            const statusEl = document.getElementById('popupCurrentStatus');
            statusEl.textContent = newStatus;
            statusEl.className = 'current-status ' + newStatus;

            const btnMap = {
                'available': 'Vacant Clean',
                'cleaning': 'Dirty / Cleaning',
                'occupied': 'Occupied',
                'maintenance': 'Maintenance'
            };
            document.querySelectorAll('.status-popup-btn').forEach(btn => {
                btn.classList.remove('current');
                btn.disabled = false;
                btn.style.opacity = '1';
                if (btn.textContent.includes(btnMap[newStatus] || '')) {
                    btn.classList.add('current');
                    btn.disabled = true;
                    btn.style.opacity = '0.6';
                }
            });

            filterRooms();

            setTimeout(() => {
                closeStatusPopup();
                // Refresh parent page
                if (window.parent && window.parent.location) {
                    window.parent.location.reload();
                }
            }, 1000);

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

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// ===== CLOSE MODAL =====
function closeModal() {
    if (window.parent && typeof window.parent.closeStatusModalOverlay === 'function') {
        window.parent.closeStatusModalOverlay();
    } else {
        window.close();
    }
}

// ===== TOAST =====
function showToast(message, type = 'success') {
    const toast = document.getElementById('toastMessage');
    toast.textContent = message;
    toast.className = type;
    toast.style.display = 'block';
    toast.style.animation = 'none';
    requestAnimationFrame(() => {
        toast.style.animation = 'slideUp 0.4s ease';
    });
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
        toast.style.display = 'none';
    }, 4000);
}

// ===== CLOSE ON ESCAPE =====
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('statusPopupOverlay').classList.contains('active')) {
            closeStatusPopup();
        } else {
            closeModal();
        }
    }
});

// ===== INIT =====
document.addEventListener('DOMContentLoaded', function() {
    filterRooms();
});

console.log('✅ Status Change Modal loaded successfully!');
</script>
</body>
</html>