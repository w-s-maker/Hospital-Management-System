<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AFYA Hospital</title>
    <link rel="stylesheet" href="loginstyling.css">
</head>
<body background="Backend\image7.png">
    <div class="auth-container">
        <h2>AFYA HOSPITAL</h2>
        <form action="loginprocess.php" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign In</button>
        </form>
        <p>Don't have an account? <a href="signuppage.php">Sign Up</a></p> 
    </div>
</body>
</html>
