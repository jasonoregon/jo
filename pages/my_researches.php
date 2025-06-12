<?php
// Bu dosya index.php tarafından çağrıldığı için $conn ve session değişkenleri zaten tanımlı.

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$participations = [];
$total_participations = 0;

// Oturum kontrolü
$currentUserId = $_SESSION['user_id'] ?? null;

// Kullanıcının katıldığı araştırmaları çek
$sql = "SELECT
            r.title AS research_title,
            r.start_date AS research_start_date,
            up.participation_status,
            up.payment_status,
            up.earnings,
            up.completion_date,
            up.participation_date
        FROM user_participations up
        JOIN researches r ON up.research_id = r.id
        WHERE up.user_id = ?";

$params = [$currentUserId];
$types = "i";

if (!empty($search_term)) {
    $sql .= " AND r.title LIKE ?";
    $params[] = "%" . $search_term . "%";
    $types .= "s";
}

$sql .= " ORDER BY up.participation_date DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Parametreleri dinamik olarak bind et
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $participations[] = $row;
    }
    $total_participations = count($participations); // Arama sonucuna göre toplam
    $stmt->close();
} else {
    // Hata durumunda loglama veya mesaj gösterme
    error_log("my_researches.php SQL hazırlama hatası: " . $conn->error);
}
?>

<h1>Katıldığım Araştırmalar</h1>

<div class="filter-container">
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="my_researches">
        <label for="search-input">Araştırma Başlığı</label>
        <div class="search-wrapper">
            <input type="text" id="search-input" name="search" placeholder="Başlık ile ara" value="<?= htmlspecialchars($search_term) ?>">
            <button type="submit" class="search-button">Ara</button>
            <?php if (!empty($search_term)): ?>
                <a href="index.php?page=my_researches" class="clear-search-button">Temizle</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Araştırma Başlığı</th>
                <th>Durum</th>
                <th>Ödeme Durumu</th>
                <th>Ödeme Miktarı</th>
                <th>Tamamlanma</th>
                <th>Başlama</th>
                <th>Katılma</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($participations)): ?>
                <?php foreach ($participations as $p): ?>
                    <tr>
                        <td><a href="#"><?= htmlspecialchars($p['research_title']) ?></a></td>
                        <td><?= htmlspecialchars($p['participation_status']) ?></td>
                        <td><?= htmlspecialchars($p['payment_status']) ?></td>
                        <td>₺<?= htmlspecialchars(number_format($p['earnings'], 2, ',', '.')) ?></td>
                        <td><?= htmlspecialchars(time_ago($p['completion_date'])) ?></td>
                        <td><?= htmlspecialchars(time_ago($p['research_start_date'])) ?></td>
                        <td><?= htmlspecialchars(time_ago($p['participation_date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="no-data">
                        <?php if(!empty($search_term)): ?>
                            Arama kriterlerinize uygun katıldığınız araştırma bulunamadı.
                        <?php else: ?>
                            Henüz katıldığınız bir araştırma bulunmamaktadır.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (empty($search_term) && $total_participations > 0) : // Sadece arama yokken genel toplamı göster ?>
    <div class="table-summary">
        <?= $total_participations ?> adet katıldığınız araştırma
    </div>
<?php elseif (!empty($search_term)): // Arama varsa, arama sonucundaki sayıyı göster ?>
     <div class="table-summary">
        Arama sonucu: <?= $total_participations ?> adet araştırma bulundu.
    </div>
<?php endif; ?>