<?php
session_start();
require 'config.php'; // Includes $conn AND mail constants

// Include Composer's autoloader for PHPMailer
require 'vendor/autoload.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException; // Alias to avoid conflict with general Exception

// NVI SOAP API Endpoint
define('NVI_WSDL_URL', 'https://tckimlik.nvi.gov.tr/service/kpspublic.asmx?WSDL');

// Function to verify TCKN using NVI SOAP API
function verifyTCKN($tckn, $firstName, $lastName, $birthYear) {
    // NVI requires uppercase Turkish characters for names
    $firstNameUpper = mb_strtoupper($firstName, 'UTF-8');
    $lastNameUpper = mb_strtoupper($lastName, 'UTF-8');

    // Ensure TCKN is treated as a long integer or string for SOAP
    // Using explicit long cast might be safer if API expects number
    $tcknLong = (int)$tckn; // Let's try casting, adjust if API requires string

    $params = [
        'TCKimlikNo' => $tcknLong,
        'Ad' => $firstNameUpper,
        'Soyad' => $lastNameUpper,
        'DogumYili' => (int)$birthYear
    ];

    try {
        // Suppress errors during SoapClient creation and use explicit check
        $options = [
            'exceptions' => true, // Throw exceptions on SOAP faults
            'trace' => 1 // Optional: enable tracing for debugging WSDL issues
        ];
        // Ensure SoapClient is available
        if (!class_exists('SoapClient')) {
             throw new Exception("PHP SOAP eklentisi aktif değil.");
        }

        $client = new SoapClient(NVI_WSDL_URL, $options);
        $result = $client->TCKimlikNoDogrula($params);

        // Check the structure of the result based on WSDL (usually an object)
        if (isset($result->TCKimlikNoDogrulaResult)) {
            return (bool)$result->TCKimlikNoDogrulaResult;
        } else {
            // Unexpected response structure
            throw new Exception("NVI API'den beklenmedik tepki yapısı.");
        }
    } catch (SoapFault $e) {
        // Log detailed SOAP fault
        $request_info = isset($client) ? " | Request: " . $client->__getLastRequest() : "";
        $response_info = isset($client) ? " | Response: " . $client->__getLastResponse() : "";
        error_log("NVI API SOAP Fault: " . $e->getMessage() . $request_info . $response_info);
        // Kullanıcıya genel bir mesaj verin
        throw new Exception("Doğrulama servisine bağlanılamadı. Tekrar deneyiniz.");
    } catch (Exception $e) {
        // Catch other exceptions (like SoapClient not existing or connection issues)
        error_log("NVI API Hatası: " . $e->getMessage());
        throw new Exception("Doğrulama sırasında hata oluştu. Hata kodu: " . $e->getMessage()); // Include specific error for logging
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Input Retrieval & Basic Validation ---
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $birthYear = filter_var(trim($_POST['birth_year'] ?? ''), FILTER_VALIDATE_INT);
    $tckn = trim($_POST['tckn'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic Checks
    $errors = [];
    if (empty($firstName)) $errors[] = "Ad gerekli.";
    if (empty($lastName)) $errors[] = "Soyad gerekli.";
    if ($birthYear === false || $birthYear < 1900 || $birthYear > date('Y')) $errors[] = "Hatalı doğum yılı.";
    // Basic TCKN format check (11 digits, non-zero start, even end)
    if (empty($tckn) || !preg_match('/^[1-9]{1}[0-9]{9}[02468]{1}$/', $tckn)) $errors[] = "Geçersiz T.C. Kimlik No formatı.";
    if (empty($username)) $errors[] = "Kullanıcı adı gerekli.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Hatalı email biçimi.";
    if (empty($password)) $errors[] = "Şifre gerekiyor.";
    if ($password !== $confirm_password) $errors[] = "Şifre tekrarı uyuşmuyor.";
    // Consider adding password strength checks here

    if (!empty($errors)) {
        $_SESSION['register_message'] = implode("<br>", $errors);
        $_SESSION['register_success'] = false;
        header("Location: register.php");
        exit();
    }

    // --- NVI TCKN Verification ---
    try {
        $isVerified = verifyTCKN($tckn, $firstName, $lastName, $birthYear);

        if (!$isVerified) {
            $_SESSION['register_message'] = "T.C. Kimlik No. doğrulanamadı.";
            $_SESSION['register_success'] = false;
            header("Location: register.php");
            exit();
        }

        // --- If TCKN Verified, Proceed with Registration ---
        // (Database checks, insert, email sending logic from previous version)

        // Check if username or email already exists
        // Ensure $conn is available and not closed prematurely
        // The $conn->close() was inside this block, which is problematic if an error occurs later.
        $sql_check = "SELECT id FROM users WHERE username = ? OR email = ?"; // Use 'id' as per DB schema
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param('ss', $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
             $_SESSION['register_message'] = "Kullanıcı adı veya email zaten mevcut.";
             $_SESSION['register_success'] = false;
             $stmt_check->close();
             // $conn->close(); // Don't close connection here, finally block will handle it
             header("Location: register.php");
             exit();
        }
        $stmt_check->close();

        // --- Create User and Profile within a Transaction ---
        $conn->begin_transaction();

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(32));
        // Generate a user_code (example: 12-char uppercase hex)
        $user_code = strtoupper(bin2hex(random_bytes(6)));

        try {
            // Insert into users table
            $sql_insert_user = "INSERT INTO users (username, email, first_name, last_name, password_hash, verification_token, user_code, is_verified) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
            $stmt_insert_user = $conn->prepare($sql_insert_user);
            $stmt_insert_user->bind_param('sssssss',
                $username,
                $email,
                $firstName,
                $lastName,
                $password_hash,
                $verification_token,
                $user_code
            );

            if (!$stmt_insert_user->execute()) {
                throw new mysqli_sql_exception("Kullanıcı oluşturulamadı.", $stmt_insert_user->errno);
            }
            $new_user_id = $conn->insert_id;
            $stmt_insert_user->close();

            // Insert into user_profiles table
            // Convert birthYear to YYYY-MM-DD format for DATE type column
            $birth_date_for_db = $birthYear . "-01-01"; // Assuming birth_date is DATE

            $sql_insert_profile = "INSERT INTO user_profiles (user_id, birth_date, tc_kimlik_no) 
                                   VALUES (?, ?, ?)";
            $stmt_insert_profile = $conn->prepare($sql_insert_profile);
            // Assuming user_profiles.user_id is INT UNSIGNED, birth_date is DATE, tc_kimlik_no is VARCHAR
            $stmt_insert_profile->bind_param('iss', 
                $new_user_id,
                $birth_date_for_db,
                $tckn // Storing TCKN plain as per previous logic
            );

            if (!$stmt_insert_profile->execute()) {
                // If profile insert fails, user insert should also be rolled back.
                throw new mysqli_sql_exception("Kullanıcı profili oluşturulamadı.", $stmt_insert_profile->errno);
            }
            $stmt_insert_profile->close();

            // If both inserts are successful, commit the transaction
            $conn->commit();

            // --- Add a welcome message for the new user ---
            $welcome_subject = "Hoş Geldiniz!";
            $welcome_content = "BilgeData platformuna hoş geldiniz. Aktif araştırmalara katılabilmek için profil kısmını doldurmayı unutmayınız.";
            $sender_name_system = "Sistem";

            $sql_insert_welcome_message = "INSERT INTO messages (user_id, sender_name, subject, content, is_read, created_at) 
                                           VALUES (?, ?, ?, ?, 0, NOW())";
            $stmt_welcome_message = $conn->prepare($sql_insert_welcome_message);
            if ($stmt_welcome_message) {
                $stmt_welcome_message->bind_param('isss',
                    $new_user_id,
                    $sender_name_system,
                    $welcome_subject,
                    $welcome_content
                );
                if (!$stmt_welcome_message->execute()) {
                    error_log("Welcome message insertion failed for user_id {$new_user_id}: " . $stmt_welcome_message->error);
                }
                $stmt_welcome_message->close();
            } else {
                error_log("Failed to prepare welcome message statement: " . $conn->error);
            }

            // --- Send Verification Email using PHPMailer (after successful commit) ---
            $mail = new PHPMailer(true);
            try {
                // Use Port 465 / SSL as it worked before
                // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Keep commented out for production
                $mail->isSMTP();
                $mail->Host       = MAIL_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_USERNAME;
                $mail->Password   = MAIL_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
                $mail->Port       = 465;                     // SSL Port
                $mail->CharSet    = 'UTF-8'; // Good practice for Turkish chars

                // Recipients
                $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
                $mail->addAddress($email, $username);

                // Content
                $verification_link = SITE_URL . "/verify_email.php?token=" . $verification_token;
                $mail->isHTML(true);
                $mail->Subject = EMAIL_SUBJECT;


				// Construct HTML body - IS THIS BLOCK STILL HERE AND CORRECT?
				$mail->Body    = "
				<html>
				<head><title>" . EMAIL_SUBJECT . "</title></head>
				<body>
				<h2>Merhaba " . htmlspecialchars($username) . ",</h2>
				<p>Thank you for registering at " . EMAIL_FROM_NAME . ".</p>
				<p>Please click the button below to verify your email address:</p>
				<p style='margin: 20px 0;'>
					<a href='" . htmlspecialchars($verification_link) . "' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email</a>
				</p>
				<p>Or copy and paste this link into your browser:</p>
				<p><a href='" . htmlspecialchars($verification_link) . "'>" . htmlspecialchars($verification_link) . "</a></p>
				<p>If you did not register, please ignore this email.</p>
				<br>
				<p>Regards,<br>" . EMAIL_FROM_NAME . "</p>
				</body>
				</html>";
                
                $mail->AltBody = "Hello " . $username . ",\n\nThank you for registering.\nPlease visit this link to verify your email address:\n" . $verification_link . "\n\nIf you did not register, please ignore this email.\n\nRegards,\n" . EMAIL_FROM_NAME;

                $mail->send();
                 $_SESSION['register_message'] = "Kayıt başarılı! Lütfen ($email) email adresinizi doğrulama linki için kontrol ediniz.";
                 $_SESSION['register_success'] = true;
            } catch (PHPMailerException $e) {
                 error_log("Mailer Error [{$email}]: " . $mail->ErrorInfo);
                // Registration was successful in DB, but email failed. Inform user.
                 $_SESSION['register_message'] = "Email adresine doğrulama linki gönderilemedi.";
                 $_SESSION['register_success'] = true; 
            }

            header("Location: register.php");
            exit();

        } catch (mysqli_sql_exception $e) { // Catches exceptions from user or profile insert
            $conn->rollback(); // Rollback transaction on any DB error during user/profile creation
            error_log("Registration DB Transaction Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
            $_SESSION['register_message'] = "Kayıt sırasında veritabanı hatası oluştu. Lütfen tekrar deneyiniz.";
            $_SESSION['register_success'] = false;
            // Ensure statements are closed if they were prepared before exception
            if (isset($stmt_insert_user) && $stmt_insert_user instanceof mysqli_stmt) $stmt_insert_user->close();
            if (isset($stmt_insert_profile) && $stmt_insert_profile instanceof mysqli_stmt) $stmt_insert_profile->close();
            header("Location: register.php");
            exit();
        }

    } catch (mysqli_sql_exception $e) { // Catch DB errors from initial check (username/email exists)
        error_log("Registration DB Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
        $_SESSION['register_message'] = "Kayıt sırasında veritabanı hatası oluştu. Lütfen tekrar deneyiniz.";
        $_SESSION['register_success'] = false;
        // No redirect here, finally block will handle it.
    } catch (Exception $e) { // Catch other errors (like TCKN verification fail, SoapClient issues)
         error_log("Registration Error: " . $e->getMessage());
         $_SESSION['register_message'] = "Bir hata oluştu: " . htmlspecialchars($e->getMessage()); // Show specific error message from API function
         $_SESSION['register_success'] = false;
    } finally {
        // Ensure resources are closed if an exception occurred before normal closing
        if (isset($stmt_check) && $stmt_check instanceof mysqli_stmt) $stmt_check->close();
        // $stmt_insert is now $stmt_insert_user and $stmt_insert_profile, closed within their try-catch or if successful
        // The connection $conn should be closed here if it's still open
        if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) $conn->close(); // Check if connection is still valid
        // Redirect only if message is set (avoids headers already sent if exception echoed something)
        if(isset($_SESSION['register_message'])){
             header("Location: register.php");
             exit();
        }
    }

} else {
    header("Location: register.php");
    exit();
}
?>