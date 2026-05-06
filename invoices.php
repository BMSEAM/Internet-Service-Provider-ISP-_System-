<?php
// pages/invoices.php
session_start();
if (!isset($_SESSION['staff_id'])) { header('Location: ../login.php?type=admin'); exit; }
require_once '../includes/db.php';

$success = '';

// Mark invoice as paid — insert payment + update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $inv_id = (int)$_POST['invoice_id'];
    $sub_id = (int)$_POST['subscription_id'];
    $amount = (float)$_POST['amount'];
    $method = esc($conn, $_POST['method']);

    db_run($conn,
        "INSERT INTO Payment (invoice_id, amount_paid, method, payment_date)
         VALUES ($inv_id, $amount, '$method', CURDATE())"
    );
    db_run($conn,
        "UPDATE Invoice SET payment_status='paid'
         WHERE invoice_id=$inv_id AND subscription_id=$sub_id"
    );
    $success = 'Payment recorded successfully.';
}

// All invoices with customer and plan info
$invoices = db_get_all($conn,
    "SELECT i.invoice_id, i.subscription_id, i.amount, i.due_date,
            i.payment_status, i.created_at,
            c.name AS customer_name, p.plan_name
     FROM Invoice i
     JOIN Subscription s ON i.subscription_id = s.subscription_id
     JOIN Customer c     ON s.customer_id = c.customer_id
     JOIN Plan p         ON s.plan_id = p.plan_id
     ORDER BY i.created_at DESC"
);

$role = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoices — ISP Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-title">Invoices & Payments</div>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div class="card">
        <h2>All Invoices</h2>
        <table>
            <thead>
                <tr>
                    <th>Invoice#</th><th>Customer</th><th>Plan</th>
                    <th>Amount</th><th>Due Date</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td>#<?= $inv['invoice_id'] ?></td>
                    <td><?= htmlspecialchars($inv['customer_name']) ?></td>
                    <td><?= $inv['plan_name'] ?></td>
                    <td>৳<?= number_format($inv['amount'], 2) ?></td>
                    <td><?= $inv['due_date'] ?></td>
                    <td>
                        <span class="badge badge-<?= $inv['payment_status'] ?>">
                            <?= $inv['payment_status'] ?>
                        </span>
                    </td>
                    <td>
                    <?php if ($inv['payment_status'] === 'unpaid'): ?>
                        <button class="btn btn-success btn-sm"
                            onclick="openPay(<?= $inv['invoice_id'] ?>, <?= $inv['subscription_id'] ?>, <?= $inv['amount'] ?>)">
                            Record Payment
                        </button>
                    <?php else: ?>
                        <span style="color:#aaa; font-size:13px;">Paid</span>
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payment Modal -->
<div id="pay-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
     background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <div class="form-card" style="max-width:380px; width:90%;">
        <h2 style="margin-bottom:16px; color:#1a3c6e;">Record Payment</h2>
        <form method="POST">
            <input type="hidden" name="mark_paid"       value="1">
            <input type="hidden" name="invoice_id"      id="pay_inv_id">
            <input type="hidden" name="subscription_id" id="pay_sub_id">
            <input type="hidden" name="amount"          id="pay_amount">
            <div class="form-group">
                <label>Amount (৳)</label>
                <input type="text" id="show_amount" readonly style="background:#eee;">
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="method" required>
                    <option value="cash">Cash</option>
                    <option value="bkash">bKash</option>
                    <option value="nagad">Nagad</option>
                    <option value="card">Card</option>
                    <option value="bank">Bank Transfer</option>
                </select>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-success" style="flex:1;">Confirm</button>
                <button type="button" class="btn btn-danger"  style="flex:1;"
                    onclick="document.getElementById('pay-modal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPay(inv_id, sub_id, amount) {
    document.getElementById('pay_inv_id').value  = inv_id;
    document.getElementById('pay_sub_id').value  = sub_id;
    document.getElementById('pay_amount').value  = amount;
    document.getElementById('show_amount').value = '৳' + amount;
    document.getElementById('pay-modal').style.display = 'flex';
}
</script>
</body>
</html>