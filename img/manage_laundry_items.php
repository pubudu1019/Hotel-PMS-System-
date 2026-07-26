<?php
include '../includes/session_check.php';
include '../includes/db.php';

$message = '';
$msg_type = '';

// ===== ADD ITEM =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $price = floatval($_POST['price']);
    if (!empty($name) && $price > 0) {
        $conn->query("INSERT INTO laundry_items (item_name, price) VALUES ('$name', $price)");
        $message = "Item added successfully!";
        $msg_type = "success";
    } else {
        $message = "Please fill all fields";
        $msg_type = "danger";
    }
}

// ===== DELETE ITEM =====
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM laundry_items WHERE item_id = $id");
    $message = "Item deleted!";
    $msg_type = "success";
}

// ===== GET ITEMS =====
$items = $conn->query("SELECT * FROM laundry_items ORDER BY item_name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Laundry Items</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; padding: 20px; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        .back-btn { color: #0d4b68; text-decoration: none; font-weight: 600; }
        .back-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-tshirt me-2" style="color:#fbbf24;"></i>Laundry Items</h4>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    
    <!-- Add Form -->
    <div class="card mb-4">
        <div class="card-header bg-light"><strong>Add New Laundry Item</strong></div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="item_name" class="form-control" placeholder="Item Name" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="Price (LKR)" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="add_item" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i>Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Items List -->
    <table class="table table-hover">
        <thead><tr><th>#</th><th>Item Name</th><th>Price (LKR)</th><th>Action</th></tr></thead>
        <tbody>
            <?php if($items && $items->num_rows > 0): $i=1; while($it = $items->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($it['item_name']) ?></td>
                <td><?= number_format($it['price'], 2) ?></td>
                <td><a href="?delete=<?= $it['item_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="4" class="text-center text-muted">No items yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>