<?php
// Bu dosya index.php tarafından çağrıldığı için $conn ve session değişkenleri zaten tanımlı.
// functions.php'daki time_ago fonksiyonu gerekirse kullanılabilir, ancak bu sayfada direkt görünmüyor.

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'available'; // 'available' or 'completed'

$available_researches = [];
$total_researches = 0;

// Kullanıcı kimliği
$currentUserId = $_SESSION['user_id'] ?? null;

// Katılabileceği aktif araştırmaları çek
// Kullanıcının henüz katılmadığı aktif araştırmalar
$sql = "SELECT
            r.id AS research_id,
            r.title AS research_title,
            r.reward_amount,
            r.estimated_duration_minutes
        FROM researches r
        LEFT JOIN user_participations up ON r.id = up.research_id AND up.user_id = ?
        WHERE r.status = 'active'
          AND up.id IS NULL"; // Kullanıcının katılmadığı araştırmalar

$params = [$currentUserId];
$types = "i";

if (!empty($search_term)) {
    $sql .= " AND r.title LIKE ?";
    $params[] = "%" . $search_term . "%";
    $types .= "s";
}

$sql .= " ORDER BY r.start_date DESC"; // Veya r.id DESC

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $available_researches[] = $row;
    }
    $total_researches = count($available_researches);
    $stmt->close();
} else {
    error_log("available_researches.php SQL hazırlama hatası: " . $conn->error);
}

// "Tamamlanan Araştırmalar" için veri çekme (Bu kısım şu an için boş bırakıldı, gelecekte eklenebilir)
// if ($current_tab === 'completed') {
//    // Tamamlanmış araştırmaları çekmek için farklı bir SQL sorgusu
//    // $sql_completed = "SELECT ... FROM researches WHERE status = 'completed' ...";
// }

?>

<h1>Araştırmalar</h1>

<div class="filter-container">
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="available_researches">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($current_tab) ?>">
        <label for="search-input">Araştırma Başlığı</label>
        <div class="search-wrapper">
            <input type="text" id="search-input" name="search" placeholder="Başlık ile ara" value="<?= htmlspecialchars($search_term) ?>">
            <button type="submit" class="search-button">Ara</button>
            <?php if (!empty($search_term)): ?>
                <a href="index.php?page=available_researches&tab=<?= htmlspecialchars($current_tab) ?>" class="clear-search-button">Temizle</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="tabs-container">
    <a href="index.php?page=available_researches&tab=available<?= !empty($search_term) ? '&search='.urlencode($search_term) : '' ?>" 
       class="tab-link <?= ($current_tab == 'available') ? 'active' : '' ?>">
       Katılabileceğim Araştırmalar
    </a>
    <a href="index.php?page=available_researches&tab=completed<?= !empty($search_term) ? '&search='.urlencode($search_term) : '' ?>" 
       class="tab-link <?= ($current_tab == 'completed') ? 'active' : 'disabled' ?>">
       Tamamlanan Araştırmalar
    </a>
    <!-- 'disabled' class'ı, henüz implemente edilmemiş sekmeler için eklenebilir -->
</div>

<?php if ($current_tab == 'available'): ?>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Araştırma Başlığı</th>
                    <th>Ödeme Miktarı</th>
                    <th>Süre</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($available_researches)): ?>
                    <?php foreach ($available_researches as $research): ?>
                        <tr>
                            <td>
                                <!-- Detay sayfasına link eklenebilir: index.php?page=research_detail&id=<?= $research['research_id'] ?> -->
                                <a href="#"><?= htmlspecialchars($research['research_title']) ?></a>
                            </td>
                            <td>₺<?= htmlspecialchars(number_format($research['reward_amount'], 2, ',', '.')) ?></td>
                            <td><?= htmlspecialchars($research['estimated_duration_minutes']) ?> dakika</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="no-data">
                            <?php if(!empty($search_term)): ?>
                                Arama kriterlerinize uygun katılabileceğiniz araştırma bulunamadı.
                            <?php else: ?>
                                Şu anda katılabileceğiniz aktif bir araştırma bulunmamaktadır.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_researches > 0) : ?>
        <div class="table-summary">
            <?php if(!empty($search_term)): ?>
                Arama sonucu:
            <?php endif; ?>
            <?= $total_researches ?> adet araştırma
        </div>
    <?php endif; ?>

<?php elseif ($current_tab == 'completed'): ?>
    <div class="table-responsive">
        <p style="padding: 20px; text-align: center; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            "Tamamlanan Araştırmalar" bölümü yakında aktif olacaktır.
        </p>
        <!-- Buraya tamamlanan araştırmalar için tablo ve veri listeleme gelebilir -->
    </div>
<?php endif; ?>