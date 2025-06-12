<?php
// Bu dosya index.php tarafından çağrıldığı için $mysqli zaten tanımlı olacak.

// Katılabileceği aktif araştırma sayısı
// Bu kullanıcının henüz katılmadığı aktif araştırmaları say
$active_research_count = 0;
$currentUserIDForCount = $_SESSION['user_id'] ?? null; // CURRENT_USER_ID yerine $_SESSION['user_id']

$sql_active_research_count = "SELECT COUNT(r.id)
                              FROM researches r
                              LEFT JOIN user_participations up ON r.id = up.research_id AND up.user_id = ?
                              WHERE r.status = 'active' AND up.id IS NULL";

$stmt_arc = $conn->prepare($sql_active_research_count); // $mysqli yerine $conn kullanılmalı

if ($stmt_arc) {
    $stmt_arc->bind_param("i", $currentUserIDForCount); // Değişkeni kullan
    $stmt_arc->execute();
    $stmt_arc->bind_result($active_research_count);
    $stmt_arc->fetch();
    $stmt_arc->close();
} else {
    // Hata durumunda loglama veya varsayılan bir değer atama
    error_log("Dashboard aktif araştırma sayısı sorgusu hazırlanamadı: " . $conn->error);
    // $active_research_count = 0; // Hata durumunda 0 göster (zaten başlangıç değeri 0)
}

// Grafik için kazanç verilerini çekme
$earnings_data = [];
$labels = [];
$data_points = [];

$sql_earnings = "SELECT DATE_FORMAT(completion_date, '%b %e, %Y') as formatted_date, earnings
                 FROM user_participations
                 WHERE user_id = ?
                 ORDER BY completion_date ASC";

$currentUserIDForEarnings = $_SESSION['user_id'] ?? null; // CURRENT_USER_ID yerine $_SESSION['user_id']
$stmt_earnings = $conn->prepare($sql_earnings); // $mysqli yerine $conn kullanılmalı

if ($stmt_earnings) {
    // bind_param içinde line 34'ün olduğu satır burası
    $stmt_earnings->bind_param("i", $currentUserIDForEarnings); // Değişkeni kullan
    $stmt_earnings->execute();
    $result_earnings = $stmt_earnings->get_result();
    while ($row = $result_earnings->fetch_assoc()) {
        $labels[] = $row['formatted_date'];
        $data_points[] = $row['earnings'];
    }
    $stmt_earnings->close();
}

$earnings_chart_data = [
    'labels' => $labels,
    'data' => $data_points
];

// Kullanıcı adını session'dan alalım
$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
?>

<h1><?= htmlspecialchars($userFullName) ?> hoş geldiniz!</h1>

<div class="stats-card">
    <h2>Katılabileceğiniz Aktif Araştırma Sayısı</h2>
    <div class="value"><?= $active_research_count ?></div>
</div>

<div class="chart-container">
    <h2>Toplam Kazanç (₺)</h2>
    <canvas id="earningsChart"></canvas>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('earningsChart').getContext('2d');
    const earningsData = <?= json_encode($earnings_chart_data); ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: earningsData.labels,
            datasets: [{
                label: 'Kazanç (₺)',
                data: earningsData.data,
                backgroundColor: 'rgba(0, 123, 255, 0.7)', // Mavi tonu
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1,
                borderRadius: 5, // Barların köşelerini yuvarlatmak için
                barThickness: 100 // Bar kalınlığını ayarlayabilirsiniz
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Konteyner boyutuna uyması için
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            return '₺' + value;
                        }
                    }
                },
                x: {
                     grid: {
                        display: false // X eksenindeki grid çizgilerini kaldır
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // Grafik üzerindeki 'Kazanç (₺)' etiketini gizle
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += '₺' + context.parsed.y.toFixed(2);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>