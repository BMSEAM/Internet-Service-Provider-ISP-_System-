<?php
// pages/usage.php
session_start();
if (!isset($_SESSION['staff_id'])) { header('Location: ../login.php?type=admin'); exit; }
require_once '../includes/db.php';

$success = $error = '';

// Add or update usage record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_usage'])) {
    $sub_id     = (int)$_POST['subscription_id'];
    $month_year = esc($conn, $_POST['month_year']);
    $data_used  = (float)$_POST['data_used_gb'];

    if ($sub_id <= 0) {
        $error = 'Please select a customer.';
    } elseif (empty($month_year)) {
        $error = 'Please select a month.';
    } elseif ($data_used < 0) {
        $error = 'Data used cannot be negative.';
    } else {
        // Check if record already exists for this subscription + month
        $exists = db_get_one($conn,
            "SELECT usage_id FROM Usage_Record
             WHERE subscription_id=$sub_id AND month_year='$month_year'"
        );

        if ($exists) {
            db_run($conn,
                "UPDATE Usage_Record SET data_used_gb=$data_used
                 WHERE subscription_id=$sub_id AND month_year='$month_year'"
            );
            $success = 'Usage record updated successfully.';
        } else {
            db_run($conn,
                "INSERT INTO Usage_Record (subscription_id, month_year, data_used_gb)
                 VALUES ($sub_id, '$month_year', $data_used)"
            );
            $success = 'Usage record added successfully.';
        }
    }
}

// Active subscriptions for dropdown
$subscriptions = db_get_all($conn,
    "SELECT s.subscription_id, c.name AS customer_name,
            p.plan_name, p.bandwidth_limit_gb
     FROM Subscription s
     JOIN Customer c ON s.customer_id = c.customer_id
     JOIN Plan p     ON s.plan_id     = p.plan_id
     WHERE s.status = 'active'
     ORDER BY c.name ASC"
);

// All usage records with excess calculation
$records = db_get_all($conn,
    "SELECT ur.usage_id, ur.month_year, ur.data_used_gb,
            c.name AS customer_name,
            p.plan_name, p.bandwidth_limit_gb,
            GREATEST(0, ur.data_used_gb - p.bandwidth_limit_gb) AS excess_gb,
            GREATEST(0, ur.data_used_gb - p.bandwidth_limit_gb) * 5 AS excess_charge
     FROM Usage_Record ur
     JOIN Subscription s ON ur.subscription_id = s.subscription_id
     JOIN Customer c     ON s.customer_id = c.customer_id
     JOIN Plan p         ON s.plan_id     = p.plan_id
     ORDER BY ur.month_year DESC, c.name ASC"
);

$role = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Usage Management — ISP Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-title">Bandwidth Usage Management</div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <!-- Add / Update Usage -->
    <div class="card">
        <h2>Add / Update Customer Usage</h2>
        <p style="font-size:13px; color:#888; margin-bottom:16px;">
            If a record already exists for that customer and month it will be updated automatically.
        </p>
        <form method="POST">
            <input type="hidden" name="add_usage" value="1">
            <div style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end;">

                <div class="form-group" style="flex:2; min-width:200px; margin:0;">
                    <label>Select Customer</label>
                    <select name="subscription_id" required>
                        <option value="">-- Choose customer --</option>
                        <?php foreach ($subscriptions as $s): ?>
                            <option value="<?= $s['subscription_id'] ?>">
                                <?= htmlspecialchars($s['customer_name']) ?>
                                — <?= $s['plan_name'] ?>
                                (<?= $s['bandwidth_limit_gb'] ?>GB limit)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="flex:1; min-width:150px; margin:0;">
                    <label>Month</label>
                    <input type="month" name="month_year" value="<?= date('Y-m') ?>" required>
                </div>

                <div class="form-group" style="flex:1; min-width:150px; margin:0;">
                    <label>Data Used (GB)</label>
                    <input type="number" name="data_used_gb" step="0.01" min="0" placeholder="e.g. 45.50" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width:auto; padding:11px 22px;">
                    Save Usage
                </button>
            </div>
        </form>
    </div>

    <!-- Usage Records Table -->
    <div class="card">
        <h2>All Usage Records</h2>
        <table>
            <thead>
                <tr>
                    <th>Customer</th><th>Plan</th><th>Month</th>
                    <th>Data Used (GB)</th><th>Plan Limit (GB)</th>
                    <th>Excess GB</th><th>Excess Charge</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:#aaa; padding:20px;">
                        No usage records yet. Add one above.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($records as $r):
                $over = $r['excess_gb'] > 0;
                $pct  = min(100, round(($r['data_used_gb'] / $r['bandwidth_limit_gb']) * 100));
            ?>
                <tr <?= $over ? 'style="background:rgba(239,68,68,0.1);"' : '' ?>>
                    <td><?= htmlspecialchars($r['customer_name']) ?></td>
                    <td><?= $r['plan_name'] ?></td>
                    <td><?= $r['month_year'] ?></td>
                    <td>
                        <?= $r['data_used_gb'] ?> GB
                        <div style="background:#eee; border-radius:4px; height:6px; margin-top:4px; width:120px;">
                            <div style="background:<?= $pct>80?'#c0392b':'#2d5ea3' ?>;
                                        width:<?= $pct ?>%; height:6px; border-radius:4px;"></div>
                        </div>
                        <span style="font-size:11px; color:#aaa;"><?= $pct ?>%</span>
                    </td>
                    <td><?= $r['bandwidth_limit_gb'] ?> GB</td>
                    <td style="color:<?= $over?'#c0392b':'#27ae60' ?>; font-weight:<?= $over?'600':'400' ?>;">
                        <?= $over ? $r['excess_gb'].' GB' : '—' ?>
                    </td>
                    <td style="color:<?= $over?'#c0392b':'#27ae60' ?>; font-weight:<?= $over?'600':'400' ?>;">
                        <?= $over ? '৳'.number_format($r['excess_charge'],2) : 'None' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>