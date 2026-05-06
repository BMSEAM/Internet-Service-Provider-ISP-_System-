<?php
require_once 'includes/db.php';

// Insert Staff rows
$staff = [
    ['Hasan Mahmud',  'hasan@isp.com',  'technician'],
    ['Rafiq Islam',   'rafiq@isp.com',  'technician'],
    ['Nasrin Akter',  'nasrin@isp.com', 'support_agent'],
    ['Sadia Rahman',  'sadia@isp.com',  'support_agent'],
    ['Kamal Hossain', 'kamal@isp.com',  'billing_agent'],
    ['Roksana Begum', 'roksana@isp.com','billing_agent'],
];

$pass = password_hash('staff123', PASSWORD_DEFAULT);

foreach ($staff as $s) {
    $name  = $s[0];
    $email = $s[1];
    $role  = $s[2];

    // Skip if already exists
    $check = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT staff_id FROM Staff WHERE email='$email'"));
    if ($check) continue;

    mysqli_query($conn,
        "INSERT INTO Staff (name, email, password, role)
         VALUES ('$name','$email','$pass','$role')"
    );
    $id = mysqli_insert_id($conn);

    // Insert into subclass table
    if ($role === 'technician') {
        $spec = ($name === 'Hasan Mahmud') ? 'Fiber Optics & Cable' : 'Wireless & Router Setup';
        mysqli_query($conn,
            "INSERT INTO Technician (staff_id, specialization)
             VALUES ($id, '$spec')");

    } elseif ($role === 'support_agent') {
        $shift = ($name === 'Nasrin Akter') ? 'morning' : 'evening';
        mysqli_query($conn,
            "INSERT INTO Support_Agent (staff_id, shift)
             VALUES ($id, '$shift')");

    } elseif ($role === 'billing_agent') {
        $rate = ($name === 'Kamal Hossain') ? 5.00 : 4.50;
        mysqli_query($conn,
            "INSERT INTO Billing_Agent (staff_id, commission_rate)
             VALUES ($id, $rate)");
    }
}

echo "<h2 style='font-family:sans-serif; color:green;'>
    ✅ All staff added successfully!
</h2>
<p style='font-family:sans-serif;'>
    Login password for all staff: <strong>staff123</strong><br><br>
    Staff added:
    <ul>
        <li>Hasan Mahmud — Technician (Fiber Optics)</li>
        <li>Rafiq Islam — Technician (Wireless)</li>
        <li>Nasrin Akter — Support Agent (Morning)</li>
        <li>Sadia Rahman — Support Agent (Evening)</li>
        <li>Kamal Hossain — Billing Agent (5%)</li>
        <li>Roksana Begum — Billing Agent (4.5%)</li>
    </ul>
    <a href='pages/tickets.php'>Go to Tickets page</a>
</p>";
?>