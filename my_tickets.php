<?php
// pages/my_tickets.php
session_start();
if (!isset($_SESSION['customer_id'])) { header('Location: ../login.php'); exit; }
require_once '../includes/db.php';

$cid     = (int)$_SESSION['customer_id'];
$success = '';

// Submit new ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject  = esc($conn, $_POST['subject']);
    $priority = esc($conn, $_POST['priority']);

    db_run($conn,
        "INSERT INTO Support_Ticket (customer_id, subject, priority)
         VALUES ($cid, '$subject', '$priority')"
    );
    $success = 'Ticket submitted successfully. Our team will contact you soon.';
}

// All tickets for this customer
$tickets = db_get_all($conn,
    "SELECT ticket_id, subject, priority, status, created_at
     FROM Support_Ticket
     WHERE customer_id=$cid
     ORDER BY created_at DESC"
);

$role = 'customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Support Tickets — ISP</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-title">Support Tickets</div>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <!-- Raise New Ticket -->
    <div class="form-card" style="margin-bottom:24px;">
        <h2 style="font-size:16px; font-weight:700; color:#1a3c6e; margin-bottom:16px;">Raise a New Ticket</h2>
        <form method="POST">
            <div class="form-group">
                <label>Describe Your Problem</label>
                <textarea name="subject" rows="3" placeholder="e.g. My internet is very slow since morning" required></textarea>
            </div>
            <div class="form-group">
                <label>Priority</label>
                <select name="priority">
                    <option value="low">Low — minor issue</option>
                    <option value="medium" selected>Medium — affecting usage</option>
                    <option value="high">High — internet is down</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success" style="width:auto;">Submit Ticket</button>
        </form>
    </div>

    <!-- My Tickets List -->
    <div class="card">
        <h2>My Tickets</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Subject</th><th>Priority</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php if (empty($tickets)): ?>
                <tr><td colspan="5" style="text-align:center; color:#aaa;">No tickets yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td>#<?= $t['ticket_id'] ?></td>
                    <td><?= htmlspecialchars($t['subject']) ?></td>
                    <td><?= $t['priority'] ?></td>
                    <td><span class="badge badge-<?= $t['status'] ?>"><?= $t['status'] ?></span></td>
                    <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>