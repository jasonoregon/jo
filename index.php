<?php
session_start(); // Gerçek bir uygulamada oturum yönetimi için gerekli

// Veritabanı bağlantısını ve diğer ayarları içeren config.php dosyasını doğrudan dahil et
require_once 'config.php';
require_once 'includes/functions.php';

// Üst kısmı (header ve sidebar) dahil et
require_once 'templates/header.php';

// Hangi sayfanın yükleneceğini belirle
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Güvenlik için sayfa adını temizle (sadece harf ve alt çizgiye izin ver)
$page = preg_replace('/[^a-zA-Z0-9_]/', '', $page);

$page_file = "pages/{$page}.php";

// Kullanıcı giriş yapmışsa sayfayı yükle
if (isset($_SESSION['user_id'])) {
    if (file_exists($page_file)) {
        require_once $page_file;
    } else {
        // Sayfa bulunamazsa 404 veya varsayılan bir sayfa göster
        echo "<h1>Sayfa Bulunamadı</h1><p>İstenen içerik mevcut değil.</p>";
    }
} else {
    // Kullanıcı giriş yapmamışsa, login.php'ye yönlendir
    header("Location: login.php");
    exit();
}

// Alt kısmı (footer) dahil et
require_once 'templates/footer.php';
?>