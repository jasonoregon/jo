<?php
session_start();
require 'config.php'; // Includes $conn

$message = '';
$message_type = 'error';
$valid_token = false;
$user_id = null;

// Check if a token is provided in the URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Check if the token exists and is not expired
        $current_time = date('Y-m-d H:i:s');
        $sql = "SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $token, $current_time);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user) {
            $valid_token = true;
            $user_id = $user['id'];
        } else {
            $message = "Geçersiz veya süresi dolmuş şifre sıfırlama bağlantısı.";
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Database error: " . $e->getMessage());
        $message = "İşlem sırasında bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
    }
} else {
    $message = "Geçersiz istek. Şifre sıfırlama bağlantısı bulunamadı.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if (empty($password) || strlen($password) < 6) {
        $message = "Şifre en az 6 karakter uzunluğunda olmalıdır.";
    } elseif ($password !== $confirm_password) {
        $message = "Şifreler eşleşmiyor.";
    } else {
        try {
            // Hash the password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Update the user's password and remove reset token
            $update_sql = "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('si', $password_hash, $user_id);
            
            if ($update_stmt->execute()) {
                $message = "Şifreniz başarıyla sıfırlandı. Artık yeni şifrenizle giriş yapabilirsiniz.";
                $message_type = 'success';
                $valid_token = false; // Prevent showing the form after successful reset
            } else {
                $message = "Şifre güncellenirken bir hata oluştu. Lütfen tekrar deneyin.";
            }
            $update_stmt->close();
            
        } catch (mysqli_sql_exception $e) {
            error_log("Database error: " . $e->getMessage());
            $message = "İşlem sırasında bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırlama</title>
    <style>
        body { font-family: sans-serif; }
        .container { width: 350px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 3px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .links { margin-top: 15px; text-align: center; }
        .links a { color: #007bff; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Şifre Sıfırlama</h2>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($valid_token): ?>
            <p>Lütfen yeni şifrenizi belirleyin:</p>
            
            <form action="?token=<?php echo htmlspecialchars($token); ?>" method="post">
                <div class="form-group">
                    <label for="password">Yeni Şifre:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Yeni Şifre Tekrar:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit">Şifremi Sıfırla</button>
            </form>
        <?php else: ?>
            <?php if ($message_type == 'success'): ?>
                <div class="links">
                    <a href="login.php">Giriş Sayfasına Git</a>
                </div>
            <?php else: ?>
                <div class="links">
                    <a href="forgot_password.php">Şifremi Unuttum Sayfasına Dön</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html> 