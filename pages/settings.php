<?php
// Bu dosya index.php tarafından çağrıldığı için $conn, session ve functions.php zaten tanımlı.
$loggedInUserId = $_SESSION['user_id'] ?? null;
$page_error = '';
$page_success = '';

// Aktif sekme
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'bildirim_ayarlari'; // bildirim_ayarlari, hesap_silinmesi

// Kullanıcının mevcut ayarlarını çek
$user_settings = [ // Varsayılan değerler
    'notify_new_research' => true,
    'notify_research_approval' => true,
    'notify_product_updates' => true,
    'notify_support_responses' => true,
];

$stmt_get_settings = $conn->prepare("SELECT notify_new_research, notify_research_approval, notify_product_updates, notify_support_responses FROM user_settings WHERE user_id = ?");
if ($stmt_get_settings) {
    $stmt_get_settings->bind_param("i", $loggedInUserId);
    $stmt_get_settings->execute();
    $result_settings = $stmt_get_settings->get_result();
    if ($result_settings->num_rows > 0) {
        $db_settings = $result_settings->fetch_assoc();
        // Veritabanından gelen değerleri bool'a çevir (0/1'den true/false'a)
        foreach ($db_settings as $key => $value) {
            $user_settings[$key] = (bool)$value;
        }
    } else {
        // Kullanıcı için ayar kaydı yoksa, varsayılanlarla bir kayıt oluşturulabilir (opsiyonel)
        // $conn->query("INSERT INTO user_settings (user_id) VALUES ($loggedInUserId)");
        // Ya da form gönderildiğinde ON DUPLICATE KEY UPDATE ile halledilir.
    }
    $stmt_get_settings->close();
} else {
    $page_error .= "Ayarlar çekilirken hata oluştu: " . $conn->error;
}

// Form gönderimini işle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if ($active_tab == 'bildirim_ayarlari') {
        // Gelen değerleri al ve boolean'a çevir (checkbox işaretli değilse POST'ta gelmez)
        $notify_new_research = isset($_POST['notify_new_research']) ? 1 : 0;
        $notify_research_approval = isset($_POST['notify_research_approval']) ? 1 : 0;
        $notify_product_updates = isset($_POST['notify_product_updates']) ? 1 : 0;
        $notify_support_responses = isset($_POST['notify_support_responses']) ? 1 : 0;

        $stmt_update_settings = $conn->prepare("
            INSERT INTO user_settings (user_id, notify_new_research, notify_research_approval, notify_product_updates, notify_support_responses) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            notify_new_research = VALUES(notify_new_research),
            notify_research_approval = VALUES(notify_research_approval),
            notify_product_updates = VALUES(notify_product_updates),
            notify_support_responses = VALUES(notify_support_responses)
        ");

        if ($stmt_update_settings) {
            $stmt_update_settings->bind_param("iiiii", 
                $loggedInUserId, 
                $notify_new_research, 
                $notify_research_approval, 
                $notify_product_updates, 
                $notify_support_responses
            );

            if ($stmt_update_settings->execute()) {
                $page_success = "Bildirim ayarları başarıyla güncellendi.";
                // Sayfada anlık gösterim için ayarları güncelle
                $user_settings['notify_new_research'] = (bool)$notify_new_research;
                $user_settings['notify_research_approval'] = (bool)$notify_research_approval;
                $user_settings['notify_product_updates'] = (bool)$notify_product_updates;
                $user_settings['notify_support_responses'] = (bool)$notify_support_responses;
            } else {
                $page_error = "Bildirim ayarları güncellenirken hata: " . $stmt_update_settings->error;
            }
            $stmt_update_settings->close();
        } else {
            $page_error = "Ayarları güncelleme sorgusu hazırlanamadı: " . $conn->error;
        }
    }
    // Diğer sekmeler için işlemler buraya eklenecek (örn: Hesap Silinmesi)
}
?>

<h1>Ayarlar</h1>

<div class="profile-tabs-container"> <!-- Profil sayfasındakiyle aynı sınıfı kullanabiliriz -->
    <a href="index.php?page=settings&tab=bildirim_ayarlari" class="profile-tab-link <?= ($active_tab == 'bildirim_ayarlari') ? 'active' : '' ?>">Bildirim Ayarları</a>
    <a href="index.php?page=settings&tab=hesap_silinmesi" class="profile-tab-link <?= ($active_tab == 'hesap_silinmesi') ? 'active' : 'disabled' ?>">Hesap Silinmesi</a>
</div>

<?php if (!empty($page_error)): ?>
    <div class="alert alert-danger"><?= nl2br(htmlspecialchars(trim($page_error))) ?></div>
<?php endif; ?>
<?php if (!empty($page_success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($page_success) ?></div>
<?php endif; ?>


<?php if ($active_tab == 'bildirim_ayarlari'): ?>
    <form method="POST" action="index.php?page=settings&tab=bildirim_ayarlari" class="settings-form">
        <div class="settings-card">
            <h3>Eposta Bildirimleri</h3>
            
            <div class="setting-item">
                <div class="setting-label">Kriterlere Uygun Yeni Araştırma</div>
                <div class="setting-options">
                    <label class="radio-label">
                        <input type="radio" name="notify_new_research" value="1" <?= ($user_settings['notify_new_research']) ? 'checked' : '' ?>> Açık
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="notify_new_research" value="0" <?= (!$user_settings['notify_new_research']) ? 'checked' : '' ?>> Kapalı
                    </label>
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">Araştırma Onay Epostaları</div>
                <div class="setting-options">
                    <label class="radio-label">
                        <input type="radio" name="notify_research_approval" value="1" <?= ($user_settings['notify_research_approval']) ? 'checked' : '' ?>> Açık
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="notify_research_approval" value="0" <?= (!$user_settings['notify_research_approval']) ? 'checked' : '' ?>> Kapalı
                    </label>
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">Ürün Geliştirme Epostaları</div>
                <div class="setting-options">
                    <label class="radio-label">
                        <input type="radio" name="notify_product_updates" value="1" <?= ($user_settings['notify_product_updates']) ? 'checked' : '' ?>> Açık
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="notify_product_updates" value="0" <?= (!$user_settings['notify_product_updates']) ? 'checked' : '' ?>> Kapalı
                    </label>
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">Destek Epostaları</div>
                <div class="setting-options">
                    <label class="radio-label">
                        <input type="radio" name="notify_support_responses" value="1" <?= ($user_settings['notify_support_responses']) ? 'checked' : '' ?>> Açık
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="notify_support_responses" value="0" <?= (!$user_settings['notify_support_responses']) ? 'checked' : '' ?>> Kapalı
                    </label>
                </div>
            </div>
        </div>

        <div class="settings-form-actions">
            <button type="submit" name="save_settings" class="save-button"><i class="fas fa-save"></i> Kaydet</button>
        </div>
    </form>

<?php elseif ($active_tab == 'hesap_silinmesi'): ?>
    <div class="settings-card">
        <h3>Hesap Silinmesi</h3>
        <p>Hesabınızı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.</p>
        <p style="color:red; font-weight:bold;">Bu özellik henüz aktif değildir.</p>
        <!-- <button class="delete-account-button">Hesabımı Kalıcı Olarak Sil</button> -->
    </div>
<?php endif; ?>