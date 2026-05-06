<?php
// pages/tickets.php
session_start();
if (!isset($_SESSION['staff_id'])) { header('Location: ../login.php?type=admin'); exit; }
require_once '../includes/db.php';

$success = '';

// Assign staff to ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_staff'])) {
    $tid = (int)$_POST['ticket_id'];
    $sid = (int)$_POST['staff_id'];

    // Only insert if not already assigned
    $exists = db_get_one($conn,
        "SELECT 1 FROM Ticket_Staff WHERE ticket_id=$tid AND staff_id=$sid"
    );
    if (!$exists) {
        db_run($conn, "INSERT INTO Ticket_Staff (ticket_id, staff_id) VALUES ($tid, $sid)");
    }
    $success = 'Staff assigned to ticket.';
}

// Close a ticket
if (isset($_GET['close'])) {
    $tid = (int)$_GET['close'];
    db_run($conn, "UPDATE Support_Ticket SET status='closed' WHERE ticket_id=$tid");
    header('Location: tickets.php?msg=closed'); exit;
}

// All staff for dropdown
$staff_list = db_get_all($conn, "SELECT staff_id, name, role FROM Staff");

// All tickets with customer name
$tickets = db_get_all($conn,
    "SELECT t.ticket_id, t.customer_id, c.name AS customer,
            t.subject, t.priority, t.status, t.created_at
     FROM Support_Ticket t
     JOIN Customer c ON t.customer_id = c.customer_id
     ORDER BY t.created_at DESC"
);

$role = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Support Tickets — ISP Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-title">Support Tickets</div>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'closed'): ?>
        <div class="alert alert-success">Ticket closed.</div>
    <?php endif; ?>

    <div class="card">
        <h2>All Tickets</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Customer</th><th>Subject</th>
                    <th>Priority</th><th>Status</th><th>Date</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td>#<?= $t['ticket_id'] ?></td>
                    <td><?= htmlspecialchars($t['customer']) ?></td>
                    <td><?= htmlspecialchars($t['subject']) ?></td>
                    <td><?= $t['priority'] ?></td>
                    <td><span class="badge badge-<?= $t['status'] ?>"><?= $t['status'] ?></span></td>
                    <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                    <td style="display:flex; gap:6px; flex-wrap:wrap;">
                        <?php if ($t['status'] === 'open'): ?>
                            <button class="btn btn-success btn-sm"
                                onclick="openAssignStaff(<?= $t['ticket_id'] ?>)">Assign Staff</button>
                            <a href="?close=<?= $t['ticket_id'] ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Close this ticket?')">Close</a>
                        <?php else: ?>
                            <span style="color:#aaa; font-size:13px;">Closed</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assign Staff Modal -->
<div id="staff-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
     background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <div class="form-card" style="max-width:380px; width:90%;">
        <h2 style="margin-bottom:16px; color:#1a3c6e;">Assign Staff to Ticket</h2>
        <form method="POST">
            <input type="hidden" name="assign_staff" value="1">
            <input type="hidden" name="ticket_id"    id="tid_input">
            <div class="form-group">
                <label>Select Staff Member</label>
                <select name="staff_id" required>
                    <option value="">-- Choose staff --</option>
                    <?php foreach ($staff_list as $s): ?>
                        <option value="<?= $s['staff_id'] ?>">
                            <?= htmlspecialchars($s['name']) ?> (<?= $s['role'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-success" style="flex:1;">Assign</button>
                <button type="button" class="btn btn-danger"  style="flex:1;"
                    onclick="document.getElementById('staff-modal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAssignStaff(tid) {
    document.getElementById('tid_input').value = tid;
    document.getElementById('staff-modal').style.display = 'flex';
}
</script>
</body>
</html>