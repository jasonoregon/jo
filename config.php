<?php
// --- Database Connection ---
$host = "localhost";
$dbname = "u759071558_blg_data";
$username = "u759071558_kmu";
$password = "TRduC6AD&U%/u=-+-"; // Your DB password


// Error reporting setup
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(hostname: $host, username: $username, password: $password, database: $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
     error_log("Database connection failed: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
     die("Database connection failed. Please check server logs.");
}

// --- PHPMailer Configuration ---
// ** IMPORTANT: Replace with your actual SMTP credentials! **
// ** Consider using environment variables for sensitive data in production **
define('MAIL_HOST', 'smtp.gmail.com');            // e.g., 'smtp.gmail.com' or your provider's host
define('MAIL_USERNAME', 'gezginntasarimci@gmail.com'); // Your SMTP username (often your email address)
define('MAIL_PASSWORD', 'lofd czjb pahq mfqk');     // Your SMTP password (e.g., Gmail App Password)
define('MAIL_PORT', 465);                          // 587 for TLS, 465 for SSL, or check provider
define('MAIL_ENCRYPTION', 'ssl');                  // 'tls' (PHPMailer::ENCRYPTION_STARTTLS) or 'ssl' (PHPMailer::ENCRYPTION_SMTPS)

// --- Email Content Configuration ---
define('SITE_URL', 'https://bilgedata.org'); // Example for local testing
define('EMAIL_FROM_ADDRESS', 'gezginntasarimci@gmail.com'); // Verified sender email address
define('EMAIL_FROM_NAME', 'Login Test App');         // Sender name (e.g., 'My Awesome App')
define('EMAIL_SUBJECT', 'Verify Your Email Address');

// No return needed if just including/requiring
?>