<?php
// pages/outages.php
session_start();
if (!isset($_SESSION['staff_id'])) { header('Location: ../login.php?type=admin'); exit; }
require_once '../includes/db.php';

$success = '';

// Log new outage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_outage'])) {
    $title = esc($conn, $_POST['title']);
    $type  = esc($conn, $_POST['type']);
    $start = esc($conn, $_POST['start_time']);
    $sid   = (int)$_SESSION['staff_id'];

    db_run($conn,
        "INSERT INTO Outage (staff_id, title, type, start_time, status)
         VALUES ($sid, '$title', '$type', '$start', 'ongoing')"
    );
    $success = 'Outage logged successfully.';
}

// Resolve an outage
if (isset($_GET['resolve'])) {
    $oid = (int)$_GET['resolve'];
    db_run($conn, "UPDATE Outage SET status='resolved', end_time=NOW() WHERE outage_id=$oid");
    header('Location: outages.php?msg=resolved'); exit;
}

// All outages with staff name
$outages = db_get_all($conn,
    "SELECT o.*, s.name AS logged_by
     FROM Outage o
     JOIN Staff s ON o.staff_id = s.staff_id
     ORDER BY o.start_time DESC"
);

$role = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Outages — ISP Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-title">Outage Management</div>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if (isset($_GET['msg'])): ?><div class="alert alert-success">Outage marked as resolved.</div><?php endif; ?>

    <!-- Log New Outage -->
    <div class="card">
        <h2>Log New Outage</h2>
        <form method="POST" style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end;">
            <input type="hidden" name="log_outage" value="1">
            <div class="form-group" style="flex:2; margin:0;">
                <label>Title / Description</label>
                <input type="text" name="title" placeholder="e.g. Fiber cut in Dhanmondi" required>
            </div>
            <div class="form-group" style="flex:1; margin:0;">
                <label>Type</label>
                <select name="type" required>
                    <option value="emergency">Emergency</option>
                    <option value="maintenance">Scheduled Maintenance</option>
                </select>
            </div>
            <div class="form-group" style="flex:1; margin:0;">
                <label>Start Time</label>
                <input type="datetime-local" name="start_time" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto; padding:11px 20px;">Log Outage</button>
        </form>
    </div>

    <!-- Outage List -->
    <div class="card">
        <h2>All Outages</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Title</th><th>Type</th><th>Logged By</th>
                    <th>Start</th><th>End</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($outages as $o): ?>
                <tr>
                    <td>#<?= $o['outage_id'] ?></td>
                    <td><?= htmlspecialchars($o['title']) ?></td>
                    <td><?= $o['type'] ?></td>
                    <td><?= htmlspecialchars($o['logged_by']) ?></td>
                    <td><?= $o['start_time'] ?></td>
                    <td><?= $o['end_time'] ?? '—' ?></td>
                    <td><span class="badge badge-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
                    <td>
                    <?php if ($o['status'] === 'ongoing'): ?>
                        <a href="?resolve=<?= $o['outage_id'] ?>" class="btn btn-success btn-sm"
                           onclick="return confirm('Mark as resolved?')">Resolve</a>
                    <?php else: ?>
                        <span style="color:#aaa; font-size:13px;">Resolved</span>
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>