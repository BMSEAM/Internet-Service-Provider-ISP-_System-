<?php
// pages/customer_dashboard.php
session_start();
if (!isset($_SESSION['customer_id'])) { header('Location: ../login.php'); exit; }
require_once '../includes/db.php';

$cid     = (int)$_SESSION['customer_id'];
$success = $error = '';

// Customer selects a plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_plan'])) {
    $pid = (int)$_POST['plan_id'];

    // Check if already has active subscription
    $existing = db_get_one($conn,
        "SELECT subscription_id FROM Subscription
         WHERE customer_id=$cid AND status='active'"
    );

    if ($existing) {
        $error = 'You already have an active plan. Please contact support to change it.';
    } else {
        $plan = db_get_one($conn, "SELECT * FROM Plan WHERE plan_id=$pid");

        if (!$plan) {
            $error = 'Selected plan not found.';
        } else {
            $start  = date('Y-m-d');
            $sub_id = db_insert($conn,
                "INSERT INTO Subscription (customer_id, plan_id, start_date, status)
                 VALUES ($cid, $pid, '$start', 'active')"
            );

            if ($sub_id > 0) {
                $due   = date('Y-m-d', strtotime('+30 days'));
                $price = $plan['price_monthly'];
                db_run($conn,
                    "INSERT INTO Invoice (subscription_id, amount, due_date, payment_status)
                     VALUES ($sub_id, $price, '$due', 'unpaid')"
                );
                $success = 'Plan selected successfully! Your first invoice has been generated.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}

// Current active subscription
$sub = db_get_one($conn,
    "SELECT s.*, p.plan_name, p.price_monthly, p.bandwidth_limit_gb, p.speed_mbps
     FROM Subscription s
     JOIN Plan p ON s.plan_id = p.plan_id
     WHERE s.customer_id=$cid AND s.status='active'
     LIMIT 1"
);

// All available plans
$plans = db_get_all($conn, "SELECT * FROM Plan ORDER BY price_monthly ASC");

// Unpaid invoice count
$unpaid = db_get_value($conn,
    "SELECT COUNT(*) AS n FROM Invoice i
     JOIN Subscription s ON i.subscription_id = s.subscription_id
     WHERE s.customer_id=$cid AND i.payment_status='unpaid'",
    'n'
);

// Open ticket count
$open_t = db_get_value($conn,
    "SELECT COUNT(*) AS n FROM Support_Ticket
     WHERE customer_id=$cid AND status='open'",
    'n'
);

// This month's usage
$this_month = date('Y-m');
$usage = null;
if ($sub) {
    $usage = db_get_one($conn,
        "SELECT ur.data_used_gb, p.bandwidth_limit_gb
         FROM Usage_Record ur
         JOIN Subscription s ON ur.subscription_id = s.subscription_id
         JOIN Plan p ON s.plan_id = p.plan_id
         WHERE s.customer_id=$cid AND ur.month_year='$this_month'
         LIMIT 1"
    );
}

// Ongoing outages
$outages = db_get_all($conn,
    "SELECT title, type, start_time FROM Outage
     WHERE status='ongoing' ORDER BY start_time DESC"
);
$outage_count = count($outages);

$role = 'customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard — ISP</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.outage-alert { background:#fff5f5; border:1.5px solid #e74c3c; border-left:5px solid #e74c3c; border-radius:8px; padding:16px 20px; margin-bottom:22px; }
.outage-alert .alert-title { font-size:15px; font-weight:700; color:#c0392b; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
.dot { display:inline-block; width:10px; height:10px; background:#e74c3c; border-radius:50%; animation:blink 1.2s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }
.outage-item { background:#fff; border:1px solid #f5c6cb; border-radius:6px; padding:10px 14px; margin-bottom:8px; font-size:13px; }
.outage-item .o-title { font-weight:600; color:#c0392b; margin-bottom:3px; }
.outage-item .o-meta  { font-size:12px; color:#888; }
.outage-type { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; margin-right:6px; }
.type-emergency   { background:#f8d7da; color:#721c24; }
.type-maintenance { background:#fff3cd; color:#856404; }
.no-outage { background:#f0fdf4; border:1.5px solid #27ae60; border-left:5px solid #27ae60; border-radius:8px; padding:12px 18px; margin-bottom:22px; font-size:13px; color:#155724; font-weight:500; display:flex; align-items:center; gap:10px; }
.plans-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-top:12px; }
.plan-card { border:2px solid #dce8f8; border-radius:12px; padding:20px; text-align:center; background:#fff; transition:all 0.2s; }
.plan-card:hover { border-color:#2d5ea3; box-shadow:0 4px 16px rgba(45,94,163,0.15); transform:translateY(-2px); }
.plan-card.selected { border-color:#27ae60; background:#f0fdf4; }
.plan-card .plan-name  { font-size:16px; font-weight:700; color:#1a3c6e; margin-bottom:6px; }
.plan-card .plan-price { font-size:22px; font-weight:700; color:#2d5ea3; margin-bottom:10px; }
.plan-card .plan-price span { font-size:13px; font-weight:400; color:#888; }
.plan-card .plan-detail { font-size:12px; color:#666; margin:3px 0; }
.plan-card .select-btn { margin-top:14px; background:#1a3c6e; color:#fff; border:none; padding:8px 20px; border-radius:8px; cursor:pointer; font-size:13px; font-weight:600; width:100%; transition:background 0.2s; }
.plan-card .select-btn:hover { background:#2d5ea3; }
.current-plan-badge { display:inline-block; background:#27ae60; color:#fff; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; margin-top:10px; }
</style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-title">Welcome, <?= htmlspecialchars($_SESSION['customer_name']) ?></div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <!-- Outage Notifications -->
    <?php if ($outage_count > 0): ?>
        <div class="outage-alert">
            <div class="alert-title">
                <span class="dot"></span>
                Service Outage Alert — <?= $outage_count ?> Ongoing Issue<?= $outage_count > 1 ? 's' : '' ?>
            </div>
            <?php foreach ($outages as $o): ?>
                <div class="outage-item">
                    <div class="o-title">
                        <span class="outage-type type-<?= $o['type'] ?>"><?= ucfirst($o['type']) ?></span>
                        <?= htmlspecialchars($o['title']) ?>
                    </div>
                    <div class="o-meta">
                        Started: <?= date('d M Y, h:i A', strtotime($o['start_time'])) ?>
                        — Our team is working to resolve this. We apologise for the inconvenience.
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-outage">✅ &nbsp; All services are running normally. No outages reported.</div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card green">
            <div class="label">Current Plan</div>
            <div class="value" style="font-size:20px;"><?= $sub ? $sub['plan_name'] : 'No Plan' ?></div>
        </div>
        <?php if ($sub): ?>
        <div class="stat-card">
            <div class="label">Monthly Price</div>
            <div class="value">৳<?= number_format($sub['price_monthly'], 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Speed</div>
            <div class="value" style="font-size:20px;"><?= $sub['speed_mbps'] ?></div>
        </div>
        <?php endif; ?>
        <div class="stat-card orange">
            <div class="label">Unpaid Bills</div>
            <div class="value"><?= $unpaid ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Open Tickets</div>
            <div class="value"><?= $open_t ?></div>
        </div>
    </div>

    <!-- Plan Selection -->
    <div class="card">
        <h2><?= $sub ? 'Your Current Plan' : '📶 Select an Internet Plan' ?></h2>
        <?php if (!$sub): ?>
            <p style="font-size:13px; color:#888; margin-bottom:16px;">
                You have not selected a plan yet. Choose one below to get started.
            </p>
        <?php endif; ?>
        <div class="plans-grid">
        <?php foreach ($plans as $p):
            $is_current = $sub && $sub['plan_name'] === $p['plan_name'];
        ?>
            <div class="plan-card <?= $is_current ? 'selected' : '' ?>">
                <div class="plan-name"><?= $p['plan_name'] ?></div>
                <div class="plan-price">৳<?= number_format($p['price_monthly'], 0) ?><span>/month</span></div>
                <div class="plan-detail">📶 <?= $p['speed_mbps'] ?></div>
                <div class="plan-detail">💾 <?= $p['bandwidth_limit_gb'] ?> GB / month</div>
                <?php if ($is_current): ?>
                    <div class="current-plan-badge">✓ Current Plan</div>
                <?php elseif (!$sub): ?>
                    <form method="POST">
                        <input type="hidden" name="select_plan" value="1">
                        <input type="hidden" name="plan_id" value="<?= $p['plan_id'] ?>">
                        <button type="submit" class="select-btn">Select This Plan</button>
                    </form>
                <?php else: ?>
                    <button class="select-btn" style="background:#aaa; cursor:not-allowed;" disabled>
                        Contact Support
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- Usage This Month -->
    <?php if ($usage): ?>
    <div class="card">
        <h2>Internet Usage — <?= date('F Y') ?></h2>
        <?php
        $used   = $usage['data_used_gb'];
        $limit  = $usage['bandwidth_limit_gb'];
        $pct    = min(100, round(($used / $limit) * 100));
        $excess = max(0, $used - $limit);
        ?>
        <p style="font-size:14px; color:#555; margin-bottom:10px;">
            <?= $used ?> GB used of <?= $limit ?> GB
            <?php if ($excess > 0): ?>
                — <strong style="color:#c0392b;">Excess: <?= $excess ?> GB (extra charge applies)</strong>
            <?php endif; ?>
        </p>
        <div style="background:#eee; border-radius:8px; height:18px; overflow:hidden;">
            <div style="background:<?= $pct>80?'#c0392b':'#2d5ea3' ?>; width:<?= $pct ?>%; height:100%; border-radius:8px; transition:width 0.4s;"></div>
        </div>
        <p style="font-size:12px; color:#aaa; margin-top:6px;"><?= $pct ?>% used</p>
    </div>
    <?php elseif ($sub): ?>
    <div class="card">
        <h2>Internet Usage — <?= date('F Y') ?></h2>
        <p style="color:#aaa; font-size:14px;">No usage data recorded for this month yet.</p>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="card">
        <h2>Quick Actions</h2>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <a href="my_invoices.php" class="btn btn-primary" style="width:auto;">View My Bills</a>
            <a href="my_tickets.php"  class="btn btn-success" style="width:auto;">Raise a Support Ticket</a>
            <a href="my_usage.php"    class="btn" style="width:auto; background:#7f8c8d; color:#fff;">View Usage History</a>
        </div>
    </div>
</div>
</body>
</html>