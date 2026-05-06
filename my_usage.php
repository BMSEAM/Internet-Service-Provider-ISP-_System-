<?php
// pages/my_usage.php
session_start();
if (!isset($_SESSION['customer_id'])) { header('Location: ../login.php'); exit; }
require_once '../includes/db.php';

$cid = (int)$_SESSION['customer_id'];

// All usage records for this customer with excess calculation
$records = db_get_all($conn,
    "SELECT ur.month_year, ur.data_used_gb,
            p.bandwidth_limit_gb, p.plan_name,
            GREATEST(0, ur.data_used_gb - p.bandwidth_limit_gb) AS excess_gb,
            GREATEST(0, ur.data_used_gb - p.bandwidth_limit_gb) * 5 AS excess_charge
     FROM Usage_Record ur
     JOIN Subscription s ON ur.subscription_id = s.subscription_id
     JOIN Plan p ON s.plan_id = p.plan_id
     WHERE s.customer_id=$cid
     ORDER BY ur.month_year DESC"
);

$role = 'customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Usage — ISP</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-title">Internet Usage History</div>

    <div class="card">
        <h2>Monthly Usage</h2>
        <table>
            <thead>
                <tr>
                    <th>Month</th><th>Plan</th><th>Limit (GB)</th>
                    <th>Used (GB)</th><th>Excess (GB)</th><th>Extra Charge</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($records)): ?>
                <tr><td colspan="6" style="text-align:center; color:#aaa;">No usage data available yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($records as $r):
                $pct = min(100, round(($r['data_used_gb'] / $r['bandwidth_limit_gb']) * 100));
            ?>
                <tr>
                    <td><?= $r['month_year'] ?></td>
                    <td><?= $r['plan_name'] ?></td>
                    <td><?= $r['bandwidth_limit_gb'] ?></td>
                    <td>
                        <?= $r['data_used_gb'] ?>
                        <div style="background:#eee; border-radius:4px; height:6px; margin-top:4px; width:100px;">
                            <div style="background:<?= $pct>80?'#c0392b':'#2d5ea3' ?>;
                                        width:<?= $pct ?>%; height:6px; border-radius:4px;"></div>
                        </div>
                    </td>
                    <td style="color:<?= $r['excess_gb']>0?'#c0392b':'#27ae60' ?>;">
                        <?= $r['excess_gb'] ?>
                    </td>
                    <td style="color:<?= $r['excess_charge']>0?'#c0392b':'#27ae60' ?>;">
                        <?= $r['excess_charge'] > 0 ? '৳'.$r['excess_charge'] : 'None' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="font-size:12px; color:#aaa; margin-top:12px;">
            * Excess charge is calculated as: extra GB × ৳5 per GB
        </p>
    </div>
</div>
</body>
</html>