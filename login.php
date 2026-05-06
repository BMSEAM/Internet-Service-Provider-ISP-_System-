<?php
// login.php
session_start();
require_once 'includes/db.php';

$error = '';
$type  = isset($_GET['type']) ? $_GET['type'] : 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $type     = $_POST['type'];

    if ($type === 'admin') {
        $result = mysqli_query($conn, "SELECT * FROM Staff WHERE email='$email'");
        $user   = mysqli_fetch_assoc($result);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['staff_id']   = $user['staff_id'];
            $_SESSION['staff_name'] = $user['name'];
            $_SESSION['role']       = $user['role'];
            header('Location: pages/admin_dashboard.php');
            exit;
        }
    } else {
        $result = mysqli_query($conn, "SELECT * FROM Customer WHERE email='$email'");
        $user   = mysqli_fetch_assoc($result);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['customer_id']   = $user['customer_id'];
            $_SESSION['customer_name'] = $user['name'];
            header('Location: pages/customer_dashboard.php');
            exit;
        }
    }
    $error = 'Invalid email or password. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ISP System — Login</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">

<div class="login-card">
    <div class="brand">
        <h1>ISP <span>Manager</span></h1>
        <p>Internet Service Provider System</p>
    </div>

    <div class="tab-switch">
        <a href="?type=customer" class="<?= $type==='customer' ? 'active' : '' ?>">Customer Login</a>
        <a href="?type=admin"    class="<?= $type==='admin'    ? 'active' : '' ?>">Admin / Staff</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="type" value="<?= $type ?>">

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-primary">
            <?= $type === 'admin' ? 'Login as Staff' : 'Login as Customer' ?>
        </button>
    </form>

    <?php if ($type === 'customer'): ?>
    <p style="text-align:center; margin-top:16px; font-size:13px; color:#888;">
        No account? <a href="register.php" style="color:#2d5ea3;">Register here</a>
    </p>
    <?php endif; ?>

    <div style="margin-top:20px; padding:12px; background:#f0f4fb; border-radius:8px; font-size:12px; color:#666;">
        <strong>Demo credentials:</strong><br>
        Admin: admin@isp.com / admin123<br>
        Customer: customer@isp.com / customer123
    </div>
</div>

</body>
</html>