<?php
// pages/my_invoices.php
session_start();
if (!isset($_SESSION['customer_id'])) { header('Location: ../login.php'); exit; }
require_once '../includes/db.php';

$cid = (int)$_SESSION['customer_id'];

// All invoices for this customer with plan and payment info
$invoices = db_get_all($conn,
    "SELECT i.invoice_id, i.amount, i.due_date, i.payment_status, i.created_at,
            p.plan_name,
            pay.amount_paid, pay.method, pay.payment_date
     FROM Invoice i
     JOIN Subscription s ON i.subscription_id = s.subscription_id
     JOIN Plan p         ON s.plan_id = p.plan_id
     LEFT JOIN Payment pay ON i.invoice_id = pay.invoice_id
     WHERE s.customer_id=$cid
     ORDER BY i.created_at DESC"
);

$role = 'customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Bills — ISP</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-title">My Bills & Payments</div>

    <div class="card">
        <h2>Invoice History</h2>
        <table>
            <thead>
                <tr>
                    <th>Invoice#</th><th>Plan</th><th>Amount</th><th>Due Date</th>
                    <th>Status</th><th>Paid On</th><th>Method</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="7" style="text-align:center; color:#aaa;">No invoices yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td>#<?= $inv['invoice_id'] ?></td>
                    <td><?= $inv['plan_name'] ?></td>
                    <td>৳<?= number_format($inv['amount'], 2) ?></td>
                    <td><?= $inv['due_date'] ?></td>
                    <td>
                        <span class="badge badge-<?= $inv['payment_status'] ?>">
                            <?= $inv['payment_status'] ?>
                        </span>
                    </td>
                    <td><?= $inv['payment_date'] ?? '—' ?></td>
                    <td><?= $inv['method'] ?? '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>