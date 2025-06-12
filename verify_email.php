<?php
session_start();
require 'config.php'; // Includes $conn

$message = '';
$message_type = 'error'; // Default message type

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    try {
        // 1. Prepare statement to find the token and ensure user is not already verified
        $sql = "SELECT id, is_verified FROM users WHERE verification_token = ? LIMIT 1"; // 'user_id' yerine 'id' kullanıldı
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close(); // Close select statement

        if ($user) {
            if ($user['is_verified']) {
                // User already verified
                $message = "This account has already been verified. You can <a href='login.php'>login here</a>.";
                $message_type = 'success'; // Or 'info'
            } else {
                // Token found, user not verified yet - Proceed with verification
                $sql_update = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?"; // 'user_id' yerine 'id' kullanıldı
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param('i', $user['id']); // Bulunan kullanıcı verisinden 'id' kullanıldı

                if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
                    $message = "Email successfully verified! You can now <a href='login.php'>log in</a>.";
                    $message_type = 'success';
                } else {
                    // Update failed for some reason
                    throw new mysqli_sql_exception("Failed to update verification status.", $stmt_update->errno);
                }
                $stmt_update->close(); // Close update statement
            }
        } else {
            // Token not found in the database
            $message = "Invalid or expired verification token.";
            $message_type = 'error';
        }

    } catch (mysqli_sql_exception $e) {
        // Log database errors
        error_log("Email Verification Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
        $message = "An error occurred during verification. Please try again later or contact support.";
        $message_type = 'error';
    } finally {
        // Ensure connection is always closed
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
    }

} else {
    $message = "No verification token provided.";
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
     <style>
        /* Add simple styling */
        body { font-family: sans-serif; padding: 40px; text-align: center; }
        .message { padding: 15px; margin: 20px auto; max-width: 500px; border-radius: 5px; border: 1px solid; }
        .success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Email Verification</h1>
    <div class="message <?php echo $message_type; ?>">
        <?php echo $message; // Output HTML message including link ?>
    </div>
</body>
</html>