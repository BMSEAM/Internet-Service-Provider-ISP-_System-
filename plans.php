<?php
// pages/plans.php
session_start();
if (!isset($_SESSION['staff_id'])) { header('Location: ../login.php?type=admin'); exit; }
require_once '../includes/db.php';

$success = '';

// Add new plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_plan'])) {
    $name  = esc($conn, $_POST['plan_name']);
    $price = (float)$_POST['price_monthly'];
    $bw    = (int)$_POST['bandwidth_limit_gb'];
    $speed = esc($conn, $_POST['speed_mbps']);

    db_run($conn,
        "INSERT INTO Plan (plan_name, price_monthly, bandwidth_limit_gb, speed_mbps)
         VALUES ('$name', $price, $bw, '$speed')"
    );
    $success = 'Plan added successfully.';
}

// All plans with active subscriber count
$plans = db_get_all($conn,
    "SELECT p.*, COUNT(s.subscription_id) AS subscribers
     FROM Plan p
     LEFT JOIN Subscription s ON p.plan_id = s.plan_id AND s.status = 'active'
     GROUP BY p.plan_id
     ORDER BY p.price_monthly ASC"
);

$role = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Plans — ISP Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-title">Internet Plans</div>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <!-- Add Plan Form -->
    <div class="form-card" style="margin-bottom:24px;">
        <h2 style="font-size:16px; font-weight:700; color:#1a3c6e; margin-bottom:16px;">Add New Plan</h2>
        <form method="POST" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <input type="hidden" name="add_plan" value="1">
            <div class="form-group" style="flex:1; margin:0; min-width:130px;">
                <label>Plan Name</label>
                <input type="text" name="plan_name" placeholder="e.g. Gold" required>
            </div>
            <div class="form-group" style="flex:1; margin:0; min-width:120px;">
                <label>Price (৳/month)</label>
                <input type="number" name="price_monthly" step="0.01" placeholder="1200" required>
            </div>
            <div class="form-group" style="flex:1; margin:0; min-width:120px;">
                <label>Bandwidth (GB)</label>
                <input type="number" name="bandwidth_limit_gb" placeholder="80" required>
            </div>
            <div class="form-group" style="flex:1; margin:0; min-width:110px;">
                <label>Speed</label>
                <input type="text" name="speed_mbps" placeholder="30 Mbps" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;">Add Plan</button>
        </form>
    </div>

    <!-- Plans Table -->
    <div class="card">
        <h2>All Plans</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Price/Month</th>
                    <th>Bandwidth</th><th>Speed</th><th>Active Subscribers</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($plans as $p): ?>
                <tr>
                    <td>#<?= $p['plan_id'] ?></td>
                    <td><strong><?= htmlspecialchars($p['plan_name']) ?></strong></td>
                    <td>৳<?= number_format($p['price_monthly'], 2) ?></td>
                    <td><?= $p['bandwidth_limit_gb'] ?> GB</td>
                    <td><?= $p['speed_mbps'] ?></td>
                    <td><?= $p['subscribers'] ?> customers</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>