<?php
// admin_dashboard_content.php
// KPI Queries
$total_employees = $conn->query("SELECT COUNT(*) as c FROM employees")->fetch_assoc()['c'];
$monthly_payroll = $conn->query("SELECT SUM(net_salary) as total FROM payroll WHERE month = '" . date('F') . "'")->fetch_assoc()['total'];
$pending_leaves = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE status='leave'")->fetch_assoc()['c'];
?>

<div class="row">
    <div class="col-md-3"><div class="panel"><h6>Total Employees</h6><h3><?= $total_employees ?></h3></div></div>
    <div class="col-md-3"><div class="panel"><h6>Monthly Payroll</h6><h3>$<?= number_format($monthly_payroll, 2) ?></h3></div></div>
    <div class="col-md-3"><div class="panel"><h6>Pending Leaves</h6><h3><?= $pending_leaves ?></h3></div></div>
    <div class="col-md-3"><div class="panel"><h6>System Status</h6><h3 class="text-success">Operational</h3></div></div>
</div>

<div class="panel mt-4">
    <h5>Salary Expenditure Trends</h5>
    <canvas id="salaryChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salaryChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Salary Expenditure ($)',
                data: [12000, 19000, 15000, 25000, 22000, 30000],
                borderColor: '#c9a84c',
                tension: 0.1
            }]
        }
    });
</script>