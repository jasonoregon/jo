<?php
session_start(); // Start the session to display potential error messages

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <style>
        body { font-family: sans-serif; }
        .container { width: 300px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .error { color: red; margin-bottom: 15px; }
        .links { margin-top: 15px; text-align: center; }
        .links a { color: #007bff; text-decoration: none; display: block; margin-bottom: 10px; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php
        // Display error message if login failed
        if (isset($_SESSION['login_error'])) {
            echo '<p class="error">' . htmlspecialchars($_SESSION['login_error']) . '</p>';
            unset($_SESSION['login_error']); // Clear the error message after displaying
        }
        ?>
        <form action="process_login.php" method="post">
            <div class="form-group">
                <label for="username">Kullanıcı adı:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Şifre:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Giriş</button>
        </form>
        
        <div class="links">
            <a href="forgot_password.php">Şifremi unuttum</a>
            <a href="register.php">Hesap oluştur</a>
        </div>
    </div>
</body>
</html>