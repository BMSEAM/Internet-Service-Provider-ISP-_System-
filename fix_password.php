<?php
require_once 'includes/db.php';

// Generate correct hashes
$admin_pass    = password_hash('admin123',    PASSWORD_DEFAULT);
$customer_pass = password_hash('customer123', PASSWORD_DEFAULT);

// Update Staff table
mysqli_query($conn, "UPDATE Staff SET password='$admin_pass' WHERE email='admin@isp.com'");

// Update Customer table
mysqli_query($conn, "UPDATE Customer SET password='$customer_pass' WHERE email='customer@isp.com'");

echo "<h2 style='font-family:sans-serif; color:green;'>✅ Passwords fixed successfully!</h2>";
echo "<p style='font-family:sans-serif;'>Now go to: <a href='login.php'>login.php</a></p>";
?>