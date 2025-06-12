<?php
// Gerekli dosyaları dahil et ve oturum kontrolü yap
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../config.php';
}

if (!function_exists('format_date_for_display')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Debug bilgisi
error_log("Profile sayfası yükleniyor. Conn: " . (isset($conn) ? "Var" : "Yok") . " Session: " . (isset($_SESSION['user_id']) ? "Var" : "Yok"));

$loggedInUserId = $_SESSION['user_id'] ?? null;
$page_error = '';
$page_success = '';

// Aktif ana sekme ve alt bölümü belirle
$active_main_tab = isset($_GET['tab']) ? $_GET['tab'] : 'bilgiler';
$active_section = isset($_GET['section']) && $active_main_tab == 'bilgiler' ? $_GET['section'] : 'cinsiyet';

$profile_sections_for_bilgiler_tab = [
    'cinsiyet' => 'Cinsiyet',
    'dogum_tarihi' => 'Doğum Tarihi',
    'egitim' => 'Eğitim',
    'calisma_durumu' => 'Çalışma Durumu',
    'gelir_duzeyi' => 'Gelir Düzeyi',
    'dil' => 'Dil',
    'inanc' => 'İnanç',
    'etnik_koken' => 'Etnik Köken',
    'ikametgah' => 'İkametgah',
    'medeni_hal' => 'Medeni Hali',
    'yasam_bicimi' => 'Yaşam Biçimi',
    'politik_gorus' => 'Politik Görüş'
];

// Kullanıcı ve profil verilerini çek
$user_data = [];
$user_profile_data = [];

if ($conn instanceof mysqli) {
    $stmt_get_user = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    if ($stmt_get_user) {
        $stmt_get_user->bind_param("i", $loggedInUserId);
        $stmt_get_user->execute();
        $result_user = $stmt_get_user->get_result();
        if ($result_user->num_rows > 0) {
            $user_data = $result_user->fetch_assoc();
            if(isset($user_data['name'])){
                $name_parts = explode(' ', $user_data['name'], 2);
                $user_data['first_name'] = $name_parts[0];
                $user_data['last_name'] = isset($name_parts[1]) ? $name_parts[1] : '';
            }
        }
        $stmt_get_user->close();
    } else {
        $page_error .= "Kullanıcı bilgileri çekilirken hata oluştu: " . $conn->error;
    }

    $profile_fields_to_select = "gender, DATE_FORMAT(birth_date, '%Y-%m-%d') as birth_date, education_level, tc_kimlik_no, phone_number, 
                                address_street, address_city, address_state_province, address_postal_code, address_country, iban";
    $stmt_get_profile = $conn->prepare("SELECT $profile_fields_to_select FROM user_profiles WHERE user_id = ?");
    if ($stmt_get_profile) {
        $stmt_get_profile->bind_param("i", $loggedInUserId);
        $stmt_get_profile->execute();
        $result_profile = $stmt_get_profile->get_result();
        if ($result_profile->num_rows > 0) {
            $user_profile_data = $result_profile->fetch_assoc();
        } else {
            $user_profile_data = [
                'gender' => null, 'birth_date' => null, 'education_level' => null, 'tc_kimlik_no' => null,
                'phone_number' => null, 'address_street' => null, 'address_city' => null, 
                'address_state_province' => null, 'address_postal_code' => null, 'address_country' => null, 'iban' => null
            ];
        }
        $stmt_get_profile->close();
    } else {
        $page_error .= "Profil verileri çekilirken hata oluştu: " . $conn->error;
    }
}

// Form gönderimini işle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    // Form işleme mantığı (güncelleme için)
    if ($active_main_tab == 'bilgiler') {
        // İlgili işlemler...
    } elseif ($active_main_tab == 'kisisel') {
        // İlgili işlemler...
    } elseif ($active_main_tab == 'iletisim') {
        // İlgili işlemler...
    } elseif ($active_main_tab == 'odeme') {
        // İlgili işlemler...
    }
}
?>

<!-- Basit HTML Yapısı -->
<h1>Profil</h1>

<!-- Tab menüsü -->
<div class="profile-tabs-container">
    <a href="index.php?page=profile&tab=bilgiler" class="profile-tab-link <?= ($active_main_tab == 'bilgiler') ? 'active' : '' ?>">Bilgiler</a>
    <a href="index.php?page=profile&tab=kisisel" class="profile-tab-link <?= ($active_main_tab == 'kisisel') ? 'active' : '' ?>">Kişisel Bilgiler</a>
    <a href="index.php?page=profile&tab=iletisim" class="profile-tab-link <?= ($active_main_tab == 'iletisim') ? 'active' : '' ?>">İletişim Bilgileri</a>
    <a href="index.php?page=profile&tab=odeme" class="profile-tab-link <?= ($active_main_tab == 'odeme') ? 'active' : '' ?>">Ödeme Bilgileri</a>
</div>

<!-- Hata ve başarı mesajları -->
<?php if (!empty($page_error)): ?>
    <div class="alert alert-danger"><?= nl2br(htmlspecialchars(trim($page_error))) ?></div>
<?php endif; ?>
<?php if (!empty($page_success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars(trim($page_success)) ?></div>
<?php endif; ?>

<!-- BİLGİLER SEKMESİ -->
<?php if ($active_main_tab == 'bilgiler'): ?>
    <div class="profile-info-box">
        <span class="fas fa-info-circle"></span>
        Bu bilgiler, araştırmacılara istedikleri özelliklere sahip katılımcıları seçmeleri konusunda yardımcı olacaktır. İstenilen bilgileri eksiksiz bir şekilde sağlamanız, daha fazla araştırmaya katılma şansınızı artırarak elde edeceğiniz kazancı da artıracaktır.
    </div>
    
    <!-- Ana içerik alanı -->
    <div style="display: flex; background-color: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); min-height: 400px; margin: 20px 0;">
        <!-- Sol navigasyon paneli -->
        <div style="width: 220px; border-right: 1px solid #e9ecef; padding: 20px 0;">
            <ul style="list-style-type: none; padding: 0; margin: 0;">
                <?php foreach ($profile_sections_for_bilgiler_tab as $key => $value): ?>
                    <li>
                        <a href="index.php?page=profile&tab=bilgiler&section=<?= $key ?>" 
                           style="display: flex; align-items: center; padding: 10px 20px; text-decoration: none; color: #495057; font-size: 0.9em; border-left: 3px solid <?= ($active_section == $key) ? '#007bff' : 'transparent' ?>; 
                                  background-color: <?= ($active_section == $key) ? '#e9f5ff' : 'transparent' ?>;">
                            <span style="width: 8px; height: 8px; background-color: <?= ($active_section == $key) ? '#007bff' : '#adb5bd' ?>; border-radius: 50%; margin-right: 12px;"></span>
                            <?= $value ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Sağ form paneli -->
        <div style="flex-grow: 1; padding: 25px 30px;">
            <form method="POST" action="index.php?page=profile&tab=bilgiler&section=<?= $active_section ?>">
                <?php if ($active_section == 'cinsiyet'): ?>
                    <h3 style="font-size: 1.3em; color: #343a40; margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef;">
                        Cinsiyet
                    </h3>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 10px; font-size: 0.95em; color: #495057; cursor: pointer;">
                            <input type="radio" name="gender" value="kadin" <?= (isset($user_profile_data['gender']) && $user_profile_data['gender'] == 'kadin') ? 'checked' : '' ?>> Kadın
                        </label>
                        <label style="display: block; margin-bottom: 10px; font-size: 0.95em; color: #495057; cursor: pointer;">
                            <input type="radio" name="gender" value="erkek" <?= (isset($user_profile_data['gender']) && $user_profile_data['gender'] == 'erkek') ? 'checked' : '' ?>> Erkek
                        </label>
                        <label style="display: block; margin-bottom: 10px; font-size: 0.95em; color: #495057; cursor: pointer;">
                            <input type="radio" name="gender" value="diger" <?= (isset($user_profile_data['gender']) && $user_profile_data['gender'] == 'diger') ? 'checked' : '' ?>> Diğer
                        </label>
                        <label style="display: block; margin-bottom: 10px; font-size: 0.95em; color: #495057; cursor: pointer;">
                            <input type="radio" name="gender" value="belirtmek_istemiyorum" <?= (isset($user_profile_data['gender']) && $user_profile_data['gender'] == 'belirtmek_istemiyorum') ? 'checked' : '' ?>> Cevaplamak istemiyorum
                        </label>
                    </div>
                <?php elseif ($active_section == 'dogum_tarihi'): ?>
                    <h3 style="font-size: 1.3em; color: #343a40; margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef;">
                        Doğum Tarihi
                    </h3>
                    <div style="margin-bottom: 20px;">
                        <label for="birth_date_input" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">
                            Doğum Tarihiniz (YYYY-AA-GG):
                        </label>
                        <input type="date" id="birth_date_input" name="birth_date" 
                               style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;" 
                               value="<?= htmlspecialchars($user_profile_data['birth_date'] ?? '') ?>">
                        <small style="color: #6c757d; font-size: 0.85em;">Tarayıcınız tarihi farklı bir formatta gösterebilir, ancak YYYY-AA-GG formatında kaydedilecektir.</small>
                    </div>
                <?php elseif ($active_section == 'egitim'): ?>
                     <h3 style="font-size: 1.3em; color: #343a40; margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef;">
                         Eğitim Düzeyi
                     </h3>
                     <div style="margin-bottom: 20px;">
                        <label for="education_level_input" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">
                            Eğitim Düzeyiniz:
                        </label>
                        <select name="education_level" id="education_level_input" 
                                style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;">
                            <option value="">Seçiniz...</option>
                            <option value="ilkokul" <?= (isset($user_profile_data['education_level']) && $user_profile_data['education_level'] == 'ilkokul') ? 'selected' : '' ?>>İlkokul</option>
                            <option value="ortaokul" <?= (isset($user_profile_data['education_level']) && $user_profile_data['education_level'] == 'ortaokul') ? 'selected' : '' ?>>Ortaokul</option>
                            <option value="lise" <?= (isset($user_profile_data['education_level']) && $user_profile_data['education_level'] == 'lise') ? 'selected' : '' ?>>Lise</option>
                            <option value="onlisans" <?= (isset($user_profile_data['education_level']) && $user_profile_data['education_level'] == 'onlisans') ? 'selected' : '' ?>>Önlisans</option>
                            <option value="lisans" <?= (isset($user_profile_data['education_level']) && $user_profile_data['education_level'] == 'lisans') ? 'selected' : '' ?>>Lisans</option>
                            <option value="yukseklisans" <?= (isset($user_profile_data['education_level']) && $user_profile_data['education_level'] == 'yukseklisans') ? 'selected' : '' ?>>Yüksek Lisans</option>
                            <option value="doktora" <?= (isset($user_profile_data['education_level']) && $user_profile_data['education_level'] == 'doktora') ? 'selected' : '' ?>>Doktora</option>
                        </select>
                     </div>
                <?php else: ?>
                    <h3 style="font-size: 1.3em; color: #343a40; margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef;">
                        <?= htmlspecialchars($profile_sections_for_bilgiler_tab[$active_section] ?? 'Bölüm Seçin') ?>
                    </h3>
                    <p>Bu bölüm için düzenleme formu henüz hazırlanmamıştır.</p>
                <?php endif; ?>
                <div style="margin-top: 30px; text-align: right;">
                    <button type="submit" name="save_profile" style="background-color: #007bff; color: white; padding: 10px 25px; border: none;
                        border-radius: 5px; font-size: 0.95em; font-weight: 500; cursor: pointer;">
                        <span style="display: inline-block; width: 16px; height: 16px; background-color: #ffffff; border-radius: 50%; margin-right: 6px;"></span> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

<!-- KİŞİSEL BİLGİLER SEKMESİ -->
<?php elseif ($active_main_tab == 'kisisel'): ?>
    <div class="profile-info-box">
        <span class="fas fa-user-check"></span>
        Kişisel bilgileriniz araştırmacılar dahil üçüncü şahıslar ile kesinlikle paylaşılmayacaktır. Bu bilgiler sizin gerçek bir kişi olup olmadığınızı doğrulamak, 18 yaş ve üzerinde olduğunuzu teyit etmek ve şahsınıza ait IBAN numaranıza ödeme yapabilmemiz için gereklidir. Lütfen bilgilerinizi eksiksiz bir şekilde doldurunuz.
    </div>
    <div style="background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-top: 20px;">
        <form method="POST" action="index.php?page=profile&tab=kisisel">
            <h3 style="font-size: 1.3em; color: #343a40; margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef;">
                Kişisel Bilgiler
            </h3>
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label for="first_name" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">Ad</label>
                    <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user_data['first_name'] ?? '') ?>" required
                           style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div style="flex: 1;">
                    <label for="last_name" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">Soyad</label>
                    <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user_data['last_name'] ?? '') ?>" required
                           style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
            </div>
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label for="birth_date_kisisel" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">Doğum Tarihi</label>
                    <input type="date" id="birth_date_kisisel" name="birth_date_kisisel" value="<?= htmlspecialchars($user_profile_data['birth_date'] ?? '') ?>"
                           style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;">
                    <small style="color: #6c757d; font-size: 0.85em;">GG.AA.YYYY formatında görünse de YYYY-AA-GG olarak kaydedilir.</small>
                </div>
                <div style="flex: 1;">
                    <label for="tc_kimlik_no" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">TC Kimlik Numarası</label>
                    <input type="text" id="tc_kimlik_no" name="tc_kimlik_no" value="<?= htmlspecialchars($user_profile_data['tc_kimlik_no'] ?? '') ?>" 
                           pattern="[1-9]{1}[0-9]{9}[0,2,4,6,8]{1}" title="Geçerli bir TC Kimlik Numarası giriniz (11 haneli rakam)."
                           style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
            </div>
            <div style="margin-top: 30px; text-align: right;">
                <button type="submit" name="save_profile" style="background-color: #007bff; color: white; padding: 10px 25px; border: none;
                    border-radius: 5px; font-size: 0.95em; font-weight: 500; cursor: pointer;">
                    <span style="display: inline-block; width: 16px; height: 16px; background-color: #ffffff; border-radius: 50%; margin-right: 6px;"></span> Kaydet
                </button>
            </div>
        </form>
    </div>

<!-- İLETİŞİM BİLGİLERİ SEKMESİ -->
<?php elseif ($active_main_tab == 'iletisim'): ?>
    <div class="profile-info-box">
        <span class="fas fa-address-card"></span>
        İletişim bilgileriniz, sizinle iletişime geçmemiz veya önemli duyuruları iletmemiz için kullanılacaktır. Bu bilgiler üçüncü şahıslarla paylaşılmaz.
    </div>
    <div style="background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-top: 20px;">
        <form method="POST" action="index.php?page=profile&tab=iletisim">
            <h3 style="font-size: 1.3em; color: #343a40; margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef;">
                İletişim Bilgileri
            </h3>
            <div style="margin-bottom: 15px;">
                <label for="email_display" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">E-posta Adresi</label>
                <input type="email" id="email_display" name="email_display" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" readonly
                       style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px; background-color: #f8f9fa;">
                <small style="color: #6c757d; font-size: 0.85em;">E-posta adresinizi değiştirmek için destek ile iletişime geçin.</small>
            </div>
            <div style="margin-bottom: 15px;">
                <label for="phone_number" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">Telefon Numarası</label>
                <input type="tel" id="phone_number" name="phone_number" value="<?= htmlspecialchars($user_profile_data['phone_number'] ?? '') ?>" placeholder="Örn: 5xxxxxxxxx"
                       style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;">
            </div>
            <div style="margin-top: 30px; text-align: right;">
                <button type="submit" name="save_profile" style="background-color: #007bff; color: white; padding: 10px 25px; border: none;
                    border-radius: 5px; font-size: 0.95em; font-weight: 500; cursor: pointer;">
                    <span style="display: inline-block; width: 16px; height: 16px; background-color: #ffffff; border-radius: 50%; margin-right: 6px;"></span> Kaydet
                </button>
            </div>
        </form>
    </div>

<!-- ÖDEME BİLGİLERİ SEKMESİ -->
<?php elseif ($active_main_tab == 'odeme'): ?>
    <div class="profile-info-box">
        <span class="fas fa-credit-card"></span>
        Adres ve IBAN bilgileri araştırmacılar dahil üçüncü şahıslar ile kesinlikle paylaşılmayacaktır. Tarafınıza ödeme yapılacağı için bu bilgiler kanuni bir gerekliliktir. Bu yüzden gireceğiniz IBAN şahsınıza ait olmalıdır.
    </div>
    <div style="background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-top: 20px;">
        <form method="POST" action="index.php?page=profile&tab=odeme">
            <h3 style="font-size: 1.3em; color: #343a40; margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef;">
                Adres Bilgileri
            </h3>
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label for="address_country" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">Ülke</label>
                    <select id="address_country" name="address_country" 
                            style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;">
                        <option value="">Seçiniz...</option>
                        <option value="Türkiye" <?= (isset($user_profile_data['address_country']) && $user_profile_data['address_country'] == 'Türkiye') ? 'selected' : '' ?>>Türkiye</option>
                        <option value="KKTC" <?= (isset($user_profile_data['address_country']) && $user_profile_data['address_country'] == 'KKTC') ? 'selected' : '' ?>>KKTC</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label for="address_city" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">İl</label>
                    <input type="text" id="address_city" name="address_city" value="<?= htmlspecialchars($user_profile_data['address_city'] ?? '') ?>"
                           style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
            </div>
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label for="address_state_province" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">İlçe</label>
                    <input type="text" id="address_state_province" name="address_state_province" value="<?= htmlspecialchars($user_profile_data['address_state_province'] ?? '') ?>"
                           style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div style="flex: 1;">
                    <label for="address_postal_code" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">Posta Kodu</label>
                    <input type="text" id="address_postal_code" name="address_postal_code" value="<?= htmlspecialchars($user_profile_data['address_postal_code'] ?? '') ?>" pattern="[0-9]*" title="Sadece rakam giriniz"
                           style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <label for="address_street" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">Açık Adres</label>
                <textarea id="address_street" name="address_street" rows="2" 
                          style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;"><?= htmlspecialchars($user_profile_data['address_street'] ?? '') ?></textarea>
            </div>

            <h3 style="font-size: 1.3em; color: #343a40; margin-top: 30px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef;">
                Banka Bilgileri
            </h3>
            <div style="margin-bottom: 15px;">
                <label for="iban" style="display: block; font-weight: 500; color: #495057; margin-bottom: 8px; font-size: 0.9em;">IBAN Numarası</label>
                <input type="text" id="iban" name="iban" value="<?= htmlspecialchars($user_profile_data['iban'] ?? '') ?>" placeholder="TR-- ---- ---- ---- ---- ---- --"
                       style="width: 100%; padding: 10px 12px; font-size: 0.95em; border: 1px solid #ced4da; border-radius: 4px;">
                <small style="color: #6c757d; font-size: 0.85em;">Lütfen size ait bir IBAN numarası giriniz. IBAN formatı ülkeye göre değişiklik gösterebilir (örn: TR için 26 haneli).</small>
            </div>
            <div style="margin-top: 30px; text-align: right;">
                <button type="submit" name="save_profile" style="background-color: #007bff; color: white; padding: 10px 25px; border: none;
                    border-radius: 5px; font-size: 0.95em; font-weight: 500; cursor: pointer;">
                    <span style="display: inline-block; width: 16px; height: 16px; background-color: #ffffff; border-radius: 50%; margin-right: 6px;"></span> Kaydet
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- HTML yapısı kapanış -->
<!-- Profile sayfası tamamlandı -->