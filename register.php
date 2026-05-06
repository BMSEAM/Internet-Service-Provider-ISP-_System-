<?php
// register.php
session_start();
require_once 'includes/db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone    = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $street   = mysqli_real_escape_string($conn, trim($_POST['street']));
    $city     = mysqli_real_escape_string($conn, trim($_POST['city']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // check duplicate email
    $check = mysqli_query($conn, "SELECT customer_id FROM Customer WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = 'This email is already registered.';
    } else {
        mysqli_query($conn,
            "INSERT INTO Customer (name, email, password, phone, street, city)
             VALUES ('$name','$email','$password','$phone','$street','$city')"
        );
        $success = 'Account created! You can now login.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ISP System — Register</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">

<div class="login-card" style="max-width:480px;">
    <div class="brand">
        <h1>ISP <span>Manager</span></h1>
        <p>Create your account</p>
    </div>

    <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="Your full name" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="email@example.com" required>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" placeholder="01XXXXXXXXX">
        </div>
        <div class="form-group">
            <label>Street</label>
            <input type="text" name="street" placeholder="House/Road">
        </div>
        <div class="form-group">
            <label>City</label>
            <input type="text" name="city" placeholder="Dhaka">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Min 6 characters" required>
        </div>
        <button type="submit" class="btn btn-primary">Create Account</button>
    </form>

    <p style="text-align:center; margin-top:14px; font-size:13px; color:#888;">
        Already registered? <a href="login.php" style="color:#2d5ea3;">Login here</a>
    </p>
</div>

</body>
</html>