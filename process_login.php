<?php
session_start();
require 'config.php'; // Includes $conn

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Kullanıcı adı ve şifre gerekli.";
        header("Location: login.php");
        exit();
    }

    try {
        // Select more user details for session, use `id` as the primary key
        $sql = "SELECT id, username, email, first_name, last_name, user_code, password_hash, is_verified FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Password is correct, now check if verified

            // *** MODIFICATION: Check is_verified status ***
            if ($user['is_verified']) {
                // --- Login Successful & Verified ---
                session_regenerate_id(true); // Regenerate session ID for security
                $_SESSION['user_id'] = $user['id']; // Use 'id' from the database
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['user_code'] = $user['user_code'];
                $_SESSION['logged_in_time'] = time();

                $stmt->close();
                $conn->close();
                // Redirect to the main index.php in the root directory
                header("Location: index.php"); 
                exit();
            } else {
                // --- Account Exists but Not Verified ---
                $_SESSION['login_error'] = "Hesabınız onaylanmadı. Doğrulama linki için mailinizi kontrol ediniz.";
                $stmt->close();
                $conn->close();
                header("Location: login.php");
                exit();
            }
        } else {
            // Invalid credentials (username not found or password mismatch)
            $_SESSION['login_error'] = "Hatalı kullanıcı adı veya şifre.";
            if ($stmt) $stmt->close(); // Close statement if it was prepared
            $conn->close();
            header("Location: login.php");
            exit();
        }

    } catch (mysqli_sql_exception $e) {
        error_log("Login query failed: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
        $_SESSION['login_error'] = "Giriş sırasında bir hata oluştu. Lütfen tekrar deneyiniz.";
        if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
        if (isset($conn) && $conn instanceof mysqli) $conn->close();
        header("Location: login.php");
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}
?>