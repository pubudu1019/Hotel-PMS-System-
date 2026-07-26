<?php
include '../includes/session_check.php';
include '../includes/db.php';

$orders = $conn->query("
    SELECT o.*, r.room_number, res.guest_name,
           GROUP_CONCAT(CONCAT(i.item_name, ' x', oi.quantity) SEPARATOR ', ') as items
    FROM laundry_orders o
    LEFT JOIN rooms r ON o.room_id = r.room_id
    LEFT JOIN reservations res ON o.res_id = res.res_id
    LEFT JOIN laundry_order_items oi ON o.order_id = oi.order_id
    LEFT JOIN laundry_items i ON oi.item_id = i.item_id
    GROUP BY o.order_id
    ORDER BY o.posted_at DESC
    LIMIT 50
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laundry Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background:#f4f7f6; padding:20px;">
<div class="container" style="max-width:1000px; background:white; padding:30px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.06);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-list me-2" style="color:#fbbf24;"></i>Laundry Orders</h4>
        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
    
    <table class="table table-hover">
        <thead><tr><th>#</th><th>Type</th><th>Guest</th><th>Room</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
            <?php if($orders && $orders->num_rows > 0): $i=1; while($o = $orders->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><span class="badge <?= $o['order_type'] == 'Room' ? 'bg-primary' : 'bg-secondary' ?>"><?= $o['order_type'] ?></span></td>
                <td><?= htmlspecialchars($o['guest_name'] ?? $o['walk_in_name'] ?? '—') ?></td>
                <td><?= $o['room_number'] ?? '—' ?></td>
                <td><small><?= htmlspecialchars($o['items'] ?? '—') ?></small></td>
                <td><strong>LKR <?= number_format($o['total_amount'], 2) ?></strong></td>
                <td><span class="badge <?= $o['order_status'] == 'Pending' ? 'bg-warning' : ($o['order_status'] == 'Processing' ? 'bg-info' : 'bg-success') ?>"><?= $o['order_status'] ?></span></td>
                <td><small><?= date('d/m H:i', strtotime($o['posted_at'])) ?></small></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8" class="text-center text-muted">No orders yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>