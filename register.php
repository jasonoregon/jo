<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt ol</title>
    <style>
        /* Your existing CSS */
        body { font-family: sans-serif; }
        .container { width: 400px; margin: 30px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold;}
        input[type="text"], input[type="email"], input[type="password"], input[type="number"] { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 3px;}
        button { padding: 10px 15px; background-color: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 1em;}
        button:hover { background-color: #218838; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 3px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .login-link { margin-top: 15px; display: block; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Kayıt ol</h2>

        <?php
        // Display feedback messages
        if (isset($_SESSION['register_message'])) {
            $msg_type = ($_SESSION['register_success'] ?? false) ? 'başarılı' : 'hatalı';
            echo '<p class="Mesaj ' . $msg_type . '">' . htmlspecialchars($_SESSION['register_message']) . '</p>';
            unset($_SESSION['register_message']);
            unset($_SESSION['register_success']);
        }
        ?>

        <form action="process_registration.php" method="post">
            <div class="form-group">
                <label for="first_name">Ad:</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Soyad:</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
             <div class="form-group">
                <label for="birth_year">Doğum Yılı:</label>
                <input type="number" id="birth_year" name="birth_year" placeholder="YYYY" min="1900" max="<?php echo date('Y'); ?>" required>
            </div>
             <div class="form-group">
                <label for="tckn">T.C. Kimlik No:</label>
                <input type="text" id="tckn" name="tckn" pattern="[1-9]{1}[0-9]{9}[02468]{1}" title="Please enter a valid 11-digit T.C. Kimlik No." maxlength="11" required>
            </div>
            <hr style="margin: 20px 0;">
            <div class="form-group">
                <label for="username">Kullanıcı adı:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">E-posta:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Şifre:</label>
                <input type="password" id="password" name="password" required>
            </div>
             <div class="form-group">
                <label for="confirm_password">Şifre tekrar:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Kayıt ol</button>
        </form>
        <a href="login.php" class="login-link">Daha önceden hesabınız varsa giriş yapın.</a>
    </div>
</body>
</html>