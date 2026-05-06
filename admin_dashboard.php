<?php
// pages/admin_dashboard.php
session_start();
if (!isset($_SESSION['staff_id'])) { header('Location: ../login.php?type=admin'); exit; }
require_once '../includes/db.php';

// Stats — simple single-value queries
$total_customers = db_get_value($conn, "SELECT COUNT(*) AS n FROM Customer", 'n');
$active_subs     = db_get_value($conn, "SELECT COUNT(*) AS n FROM Subscription WHERE status='active'", 'n');
$unpaid_invoices = db_get_value($conn, "SELECT COUNT(*) AS n FROM Invoice WHERE payment_status='unpaid'", 'n');
$open_tickets    = db_get_value($conn, "SELECT COUNT(*) AS n FROM Support_Ticket WHERE status='open'", 'n');
$ongoing_outages = db_get_value($conn, "SELECT COUNT(*) AS n FROM Outage WHERE status='ongoing'", 'n');

// Recent 5 customers
$recent_customers = db_get_all($conn,
    "SELECT customer_id, name, email, status, created_at
     FROM Customer
     ORDER BY created_at DESC
     LIMIT 5"
);

// Recent 5 tickets with customer name
$recent_tickets = db_get_all($conn,
    "SELECT t.ticket_id, c.name AS customer, t.subject, t.priority, t.status
     FROM Support_Ticket t
     JOIN Customer c ON t.customer_id = c.customer_id
     ORDER BY t.created_at DESC
     LIMIT 5"
);

$role = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — ISP</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-title">Welcome, <?= htmlspecialchars($_SESSION['staff_name']) ?></div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Customers</div>
            <div class="value"><?= $total_customers ?></div>
        </div>
        <div class="stat-card green">
            <div class="label">Active Subscriptions</div>
            <div class="value"><?= $active_subs ?></div>
        </div>
        <div class="stat-card orange">
            <div class="label">Unpaid Invoices</div>
            <div class="value"><?= $unpaid_invoices ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Open Tickets</div>
            <div class="value"><?= $open_tickets ?></div>
        </div>
        <div class="stat-card red">
            <div class="label">Ongoing Outages</div>
            <div class="value"><?= $ongoing_outages ?></div>
        </div>
    </div>

    <!-- Recent Customers -->
    <div class="card">
        <h2>Recent Customers</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Joined</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recent_customers as $row): ?>
                <tr>
                    <td>#<?= $row['customer_id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><span class="badge badge-active"><?= $row['status'] ?></span></td>
                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top:12px;">
            <a href="customers.php" class="btn btn-primary btn-sm" style="width:auto;">View All Customers</a>
        </div>
    </div>

    <!-- Recent Tickets -->
    <div class="card">
        <h2>Recent Support Tickets</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Customer</th><th>Subject</th><th>Priority</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recent_tickets as $row): ?>
                <tr>
                    <td>#<?= $row['ticket_id'] ?></td>
                    <td><?= htmlspecialchars($row['customer']) ?></td>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
                    <td><?= $row['priority'] ?></td>
                    <td><span class="badge badge-<?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top:12px;">
            <a href="tickets.php" class="btn btn-primary btn-sm" style="width:auto;">View All Tickets</a>
        </div>
    </div>
</div>
</body>
</html>