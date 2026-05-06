<?php
// pages/customers.php
session_start();
if (!isset($_SESSION['staff_id'])) { header('Location: ../login.php?type=admin'); exit; }
require_once '../includes/db.php';

$success = $error = '';

// Assign plan to customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_plan'])) {
    $cid   = (int)$_POST['customer_id'];
    $pid   = (int)$_POST['plan_id'];
    $start = esc($conn, $_POST['start_date']);

    // Get plan price
    $plan = db_get_one($conn, "SELECT * FROM Plan WHERE plan_id=$pid");

    if (!$plan) {
        $error = 'Selected plan not found.';
    } else {
        // Insert subscription
        $sub_id = db_insert($conn,
            "INSERT INTO Subscription (customer_id, plan_id, start_date, status)
             VALUES ($cid, $pid, '$start', 'active')"
        );

        if ($sub_id > 0) {
            // Auto-generate first invoice (due in 30 days)
            $due   = date('Y-m-d', strtotime('+30 days'));
            $price = $plan['price_monthly'];
            db_run($conn,
                "INSERT INTO Invoice (subscription_id, amount, due_date, payment_status)
                 VALUES ($sub_id, $price, '$due', 'unpaid')"
            );
            $success = 'Plan assigned and first invoice generated successfully!';
        } else {
            $error = 'Failed to create subscription: ' . mysqli_error($conn);
        }
    }
}

// All plans for dropdown
$plans = db_get_all($conn, "SELECT * FROM Plan ORDER BY price_monthly ASC");

// All customers with their active plan (if any)
$customers = db_get_all($conn,
    "SELECT c.customer_id, c.name, c.email, c.phone, c.city, c.status,
            p.plan_name
     FROM Customer c
     LEFT JOIN Subscription s ON c.customer_id = s.customer_id AND s.status = 'active'
     LEFT JOIN Plan p ON s.plan_id = p.plan_id
     ORDER BY c.created_at DESC"
);

$role = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers — ISP Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-title">Customer Management</div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="card">
        <h2>All Customers</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Email</th><th>Phone</th>
                    <th>City</th><th>Current Plan</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($customers)): ?>
                <tr><td colspan="8" style="text-align:center; color:#aaa; padding:20px;">No customers found.</td></tr>
            <?php endif; ?>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td>#<?= $c['customer_id'] ?></td>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?></td>
                    <td><?= $c['phone'] ?? '—' ?></td>
                    <td><?= $c['city']  ?? '—' ?></td>
                    <td>
                        <?php if ($c['plan_name']): ?>
                            <span class="badge badge-active"><?= $c['plan_name'] ?></span>
                        <?php else: ?>
                            <em style="color:#aaa;">No plan</em>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-active"><?= $c['status'] ?></span></td>
                    <td>
                        <button class="btn btn-success btn-sm"
                            onclick="openAssign(<?= $c['customer_id'] ?>, '<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>')">
                            Assign Plan
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assign Plan Modal -->
<div id="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
     background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <div class="form-card" style="max-width:420px; width:90%;">
        <h2 id="modal-title" style="font-size:16px; font-weight:700; color:#1a3c6e; margin-bottom:20px;">Assign Plan</h2>
        <form method="POST">
            <input type="hidden" name="assign_plan" value="1">
            <input type="hidden" name="customer_id" id="cid_input">
            <div class="form-group">
                <label>Select Internet Plan</label>
                <select name="plan_id" required>
                    <option value="">-- Choose a plan --</option>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= $p['plan_id'] ?>">
                            <?= htmlspecialchars($p['plan_name']) ?>
                            — ৳<?= number_format($p['price_monthly'], 0) ?>/month
                            — <?= $p['speed_mbps'] ?>
                            — <?= $p['bandwidth_limit_gb'] ?>GB
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div style="display:flex; gap:10px; margin-top:10px;">
                <button type="submit" class="btn btn-success" style="flex:1;">Assign Plan</button>
                <button type="button" class="btn btn-danger"  style="flex:1;"
                    onclick="document.getElementById('modal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAssign(id, name) {
    document.getElementById('cid_input').value         = id;
    document.getElementById('modal-title').textContent = 'Assign Plan to: ' + name;
    document.getElementById('modal').style.display     = 'flex';
}
document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>