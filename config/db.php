<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u759071558_kmu'); // Veritabanı kullanıcı adı
define('DB_PASSWORD', 'TRduC6AD&U%/u=-+-');     // Veritabanı şifresi
define('DB_NAME', 'u759071558_blg_data');

// MySQLi ile bağlantı kurma
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Bağlantıyı kontrol etme
if($mysqli === false){
    die("HATA: Veritabanı bağlantısı kurulamadı. " . $mysqli->connect_error);
}

// Karakter setini ayarla
$mysqli->set_charset("utf8mb4");

// Simüle edilmiş kullanıcı ID'si (Giriş sistemi yapıldığında session'dan alınacak)
// Gerçek bir uygulamada $_SESSION['user_id'] gibi bir değer olmalı
define('CURRENT_USER_ID', 1);
define('CURRENT_USER_NAME', 'Serhat Şamil'); // Normalde DB'den çekilir
define('CURRENT_USER_CODE', 'YRD00402E7AAAE'); // Normalde DB'den çekilir
?>