<?php
session_start();
require 'config.php'; // Includes $conn and mail configuration constants

// Include Composer's autoloader for PHPMailer
require 'vendor/autoload.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Lütfen geçerli bir e-posta adresi giriniz.";
        $message_type = 'error';
    } else {
        try {
            // Check if email exists in the database
            $sql = "SELECT id, username, first_name, last_name FROM users WHERE email = ? AND is_verified = 1 LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if ($user) {
                // Generate a unique reset token
                $reset_token = bin2hex(random_bytes(32));
                $token_expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
                
                // Update user record with the reset token and expiry
                $update_sql = "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('ssi', $reset_token, $token_expires, $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Send password reset email
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = MAIL_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = MAIL_USERNAME;
                    $mail->Password   = MAIL_PASSWORD;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
                    $mail->Port       = MAIL_PORT;
                    $mail->CharSet    = 'UTF-8';
                    
                    // Recipients
                    $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
                    $mail->addAddress($email, $user['first_name'] . ' ' . $user['last_name']);
                    
                    // Content
                    $reset_link = SITE_URL . "/reset_password.php?token=" . $reset_token;
                    $mail->isHTML(true);
                    $mail->Subject = 'Şifre Sıfırlama Talebi';
                    $mail->Body = "
                    <html>
                    <head><title>Şifre Sıfırlama</title></head>
                    <body>
                    <h2>Merhaba " . htmlspecialchars($user['first_name']) . ",</h2>
                    <p>Şifre sıfırlama talebiniz alınmıştır.</p>
                    <p>Şifrenizi sıfırlamak için aşağıdaki butonu kullanabilirsiniz:</p>
                    <p style='margin: 20px 0;'>
                        <a href='" . htmlspecialchars($reset_link) . "' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Şifremi Sıfırla</a>
                    </p>
                    <p>Ya da bu bağlantıyı tarayıcınıza kopyalayabilirsiniz:</p>
                    <p><a href='" . htmlspecialchars($reset_link) . "'>" . htmlspecialchars($reset_link) . "</a></p>
                    <p>Bu bağlantı 1 saat boyunca geçerlidir.</p>
                    <p>Eğer şifre sıfırlama talebinde bulunmadıysanız, lütfen bu e-postayı dikkate almayınız.</p>
                    <br>
                    <p>Saygılarımızla,<br>" . EMAIL_FROM_NAME . "</p>
                    </body>
                    </html>";
                    
                    // Alternative plain text version
                    $mail->AltBody = "Merhaba " . $user['first_name'] . ",\n\n" .
                                    "Şifre sıfırlama talebiniz alınmıştır.\n\n" .
                                    "Şifrenizi sıfırlamak için aşağıdaki bağlantıyı kullanabilirsiniz:\n" .
                                    $reset_link . "\n\n" .
                                    "Bu bağlantı 1 saat boyunca geçerlidir.\n\n" .
                                    "Eğer şifre sıfırlama talebinde bulunmadıysanız, lütfen bu e-postayı dikkate almayınız.\n\n" .
                                    "Saygılarımızla,\n" . EMAIL_FROM_NAME;
                    
                    $mail->send();
                    $message = "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi. Lütfen gelen kutunuzu kontrol edin.";
                    $message_type = 'success';
                    
                } catch (PHPMailerException $e) {
                    error_log("PHPMailer Error: " . $e->getMessage());
                    $message = "E-posta gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
                    $message_type = 'error';
                }
                
            } else {
                // Email not found, but don't reveal this for security
                // Instead, provide the same success message to avoid user enumeration
                $message = "Eğer bu e-posta adresi sistemde kayıtlıysa, şifre sıfırlama talimatları gönderilecektir.";
                $message_type = 'success';
            }
            
        } catch (mysqli_sql_exception $e) {
            error_log("Database error: " . $e->getMessage());
            $message = "İşlem sırasında bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum</title>
    <style>
        body { font-family: sans-serif; }
        .container { width: 350px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="email"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 3px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .links { margin-top: 15px; text-align: center; }
        .links a { color: #007bff; text-decoration: none; display: block; margin-bottom: 10px; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Şifremi Unuttum</h2>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <p>E-posta adresinizi girin ve size şifrenizi sıfırlamanız için bir bağlantı göndereceğiz.</p>
        
        <form action="" method="post">
            <div class="form-group">
                <label for="email">E-posta Adresi:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit">Şifre Sıfırlama Bağlantısı Gönder</button>
        </form>
        
        <div class="links">
            <a href="login.php">Giriş Sayfasına Dön</a>
        </div>
    </div>
</body>
</html> 