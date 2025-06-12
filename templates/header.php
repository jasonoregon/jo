<?php
// session_start(); // Bu dosya dash/index.php tarafından çağrıldığı için session zaten başlamış olmalı.
// dash/index.php zaten session_start() çağırıyor ve kullanıcı giriş kontrolü yapıyor.

// Ana config dosyasını dahil et (veritabanı bağlantısı $conn ve diğer ayarlar için)
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../config.php'; // config.php index.php'de zaten dahil edilmediyse dahil et
}

if (!function_exists('format_date_for_display')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Oturumdan kullanıcı bilgilerini al
// Bu değişkenler process_login.php'de set ediliyor.
$loggedInUserId = $_SESSION['user_id'] ?? null;
$loggedInUserFirstName = $_SESSION['first_name'] ?? '';
$loggedInUserLastName = $_SESSION['last_name'] ?? '';
$loggedInUserFullName = trim($loggedInUserFirstName . ' ' . $loggedInUserLastName);
$loggedInUserCode = $_SESSION['user_code'] ?? null;

// Eğer kullanıcı ID'si yoksa (teorik olarak dash/index.php bunu engellemeli ama ek kontrol)
if (!$loggedInUserId) {
    // Güvenlik için veya hata durumunda login'e yönlendirilebilir.
    // header("Location: ../login.php");
    // exit();
    // Ya da en azından hata logla ve boş değerlerle devam et.
    error_log("header.php: Kullanıcı ID'si session'da bulunamadı.");
}

$currentPage = basename($_SERVER['PHP_SELF']);
if (isset($_GET['page'])) {
    $currentPage = $_GET['page'] . '.php';
} else {
    $currentPage = 'dashboard.php'; // Varsayılan sayfa
}

$unread_messages_count = 0;
if ($loggedInUserId && isset($conn) && $conn instanceof mysqli) { // CURRENT_USER_ID ve $mysqli yerine $loggedInUserId ve $conn kullan
    $stmt_msg_count = $conn->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ? AND is_read = FALSE");
    if ($stmt_msg_count) {
        $stmt_msg_count->bind_param("i", $loggedInUserId);
        $stmt_msg_count->execute();
        $stmt_msg_count->bind_result($unread_messages_count);
        $stmt_msg_count->fetch();
        $stmt_msg_count->close();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BilgeData Katılımcı Paneli</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome ikonları -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- Özel CSS düzeltmeleri -->
    <style>
        .main-content {
            padding: 25px;
            min-height: 100vh;
            overflow-y: auto;
            margin-left: 250px;
            width: calc(100% - 250px);
        }
        
        /* Profil Sayfası Düzeltmeleri */
        h1 { font-size: 1.8em; color: #333; margin-top: 0; margin-bottom: 20px; }
        .profile-tabs-container { margin-bottom: 25px; border-bottom: 1px solid #dee2e6; display: flex; }
        .profile-tab-link {
            padding: 12px 20px; cursor: pointer; text-decoration: none; color: #495057;
            font-size: 1em; font-weight: 500; border-bottom: 3px solid transparent;
            margin-right: 5px; margin-bottom: -1px;
            transition: color 0.2s ease, border-color 0.2s ease;
        }
        .profile-tab-link:hover { color: #007bff; border-bottom-color: #cce5ff; }
        .profile-tab-link.active { color: #007bff; border-bottom-color: #007bff; }
        
        .alert {
            padding: 10px 15px; 
            margin-bottom: 15px; 
            border: 1px solid transparent;
            border-radius: 4px; 
            font-size: 0.9em;
        }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        
        .profile-info-box {
            background-color: #e7f3fe; 
            border: 1px solid #b3d7fd; 
            color: #0c5460;
            padding: 15px; 
            border-radius: 6px; 
            margin-bottom: 25px;
            font-size: 0.9em; 
            display: flex; 
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            BilgeData
        </div>
        <nav class="sidebar-nav">
            <ul class="nav-item-single">
                 <li>
                    <a href="index.php?page=dashboard" class="<?= ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5V14.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-4a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v4a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5V7.5a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146Z"/>
                                <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5Z"/>
                            </svg>
                        </span>
                        Ana Sayfa
                    </a>
                </li>
            </ul>

            <div class="nav-group" data-group-color="#4A55E1"> <!-- Renk doğrudan hex olarak -->
                <div class="nav-group-header">
                    <span class="nav-group-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2zm1 12h2V2h-2v12zm-3 0V7H7v7h2zm-4 0V9H2v6h2z"/>
                        </svg>
                    </span>
                    <span class="nav-group-title">Araştırmalar Grubu</span>
                </div>
                <ul class="nav-group-items">
                    <li><a href="index.php?page=my_researches" class="<?= ($currentPage == 'my_researches.php') ? 'active' : ''; ?>">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                            </svg>
                        </span> Katıldığım araştırmalar
                    </a></li>
                    <li><a href="index.php?page=available_researches" class="<?= ($currentPage == 'available_researches.php') ? 'active' : ''; ?>">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                        </span> Katılabileceğim anketler
                    </a></li>
                     <li><a href="index.php?page=messages" class="<?= ($currentPage == 'messages.php') ? 'active' : ''; ?>">
                        <span class="icon">
                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/>
                            </svg>
                        </span>Mesajlar
                        <?php if ($unread_messages_count > 0): ?>
                            <span class="badge"><?= $unread_messages_count ?></span>
                        <?php endif; ?>
                    </a></li>
                </ul>
            </div>

            <div class="nav-group" data-group-color="#28a745"> <!-- Yeşil -->
                <div class="nav-group-header">
                    <span class="nav-group-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11 5.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1z"/>
                            <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2zm13 2v5H1V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1zm-1 9H2a1 1 0 0 1-1-1v-1h14v1a1 1 0 0 1-1 1z"/>
                        </svg>
                    </span>
                    <span class="nav-group-title">Finans Grubu</span>
                </div>
                <ul class="nav-group-items">
                    <li><a href="index.php?page=payments" class="<?= ($currentPage == 'payments.php') ? 'active' : ''; ?>">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v2h6a.5.5 0 0 1 .5.5c0 .276-.224.5-.5.5H0v2h6a.5.5 0 0 1 .5.5c0 .276-.224.5-.5.5H0v2A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13zM15 3.5A1.5 1.5 0 0 1 14.5 5h-13A1.5 1.5 0 0 1 0 3.5v-2A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v2zM3 8.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z"/>
                            </svg>
                        </span> Ödemeler
                    </a></li>
                </ul>
            </div>

            <div class="nav-group" data-group-color="#fd7e14"> <!-- Turuncu -->
                <div class="nav-group-header">
                    <span class="nav-group-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M11.5 2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM9.05 3a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0V3h9.05zM4.5 7a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM2.05 8a2.5 2.5 0 0 1 4.9 0H16v1H6.95a2.5 2.5 0 0 1-4.9 0H0V8h2.05zm9.45 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm-2.45 1a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0v-1h9.05z"/>
                        </svg>
                    </span>
                    <span class="nav-group-title">Genel Ayarlar</span>
                </div>
                <ul class="nav-group-items">
                    <li><a href="index.php?page=settings" class="<?= ($currentPage == 'settings.php') ? 'active' : ''; ?>">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311a1.464 1.464 0 0 1 .872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1-.872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1-.872-2.105l.34-.1c1.4-.413-1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1 .872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872l-.1-.34zM8 10.93a2.929 2.929 0 1 1 0-5.858 2.929 2.929 0 0 1 0 5.858z"/>
                            </svg>
                        </span> Ayarlar
                    </a></li>
                    <li><a href="index.php?page=profile" class="<?= ($currentPage == 'profile.php') ? 'active' : ''; ?>">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                            </svg>
                        </span> Profil
                    </a></li>
                </ul>
            </div>
        </nav>
        <div class="user-profile">
            <?php if (!empty($loggedInUserFullName) && !empty($loggedInUserCode)): ?>
                <div class="user-name"><?= htmlspecialchars($loggedInUserFullName) ?></div>
                <div class="user-id"><?= htmlspecialchars($loggedInUserCode) ?></div>
            <?php else: ?>
                <div class="user-name">Kullanıcı Bilgisi Yok</div>
                <div class="user-id">Giriş Yapılmamış</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="main-content"> <!-- Bu div footer.php'de kapatılacak -->