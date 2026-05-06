<?php
// includes/nav.php
?>
<nav>
    <div class="logo">ISP <span>Manager</span></div>
    <ul>
    <?php if ($role === 'admin'): ?>
        <li><a href="admin_dashboard.php"  <?= basename($_SERVER['PHP_SELF'])==='admin_dashboard.php'  ?'class="active"':'' ?>>Dashboard</a></li>
        <li><a href="customers.php"        <?= basename($_SERVER['PHP_SELF'])==='customers.php'        ?'class="active"':'' ?>>Customers</a></li>
        <li><a href="plans.php"            <?= basename($_SERVER['PHP_SELF'])==='plans.php'            ?'class="active"':'' ?>>Plans</a></li>
        <li><a href="invoices.php"         <?= basename($_SERVER['PHP_SELF'])==='invoices.php'         ?'class="active"':'' ?>>Invoices</a></li>
        <li><a href="usage.php"            <?= basename($_SERVER['PHP_SELF'])==='usage.php'            ?'class="active"':'' ?>>Usage</a></li>
        <li><a href="tickets.php"          <?= basename($_SERVER['PHP_SELF'])==='tickets.php'          ?'class="active"':'' ?>>Tickets</a></li>
        <li><a href="outages.php"          <?= basename($_SERVER['PHP_SELF'])==='outages.php'          ?'class="active"':'' ?>>Outages</a></li>
    <?php else: ?>
        <li><a href="customer_dashboard.php" <?= basename($_SERVER['PHP_SELF'])==='customer_dashboard.php'?'class="active"':'' ?>>Dashboard</a></li>
        <li><a href="my_invoices.php"        <?= basename($_SERVER['PHP_SELF'])==='my_invoices.php'       ?'class="active"':'' ?>>My Bills</a></li>
        <li><a href="my_usage.php"           <?= basename($_SERVER['PHP_SELF'])==='my_usage.php'          ?'class="active"':'' ?>>Usage</a></li>
        <li><a href="my_tickets.php"         <?= basename($_SERVER['PHP_SELF'])==='my_tickets.php'        ?'class="active"':'' ?>>Support</a></li>
    <?php endif; ?>
    </ul>
    <a href="../logout.php" class="logout-btn">Logout</a>
</nav>