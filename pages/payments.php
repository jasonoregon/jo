<?php
// Bu dosya index.php tarafından çağrıldığı için $conn, session ve functions.php zaten tanımlı.

$loggedInUserId = $_SESSION['user_id'] ?? null;
$page_error = '';

// Ödemeler tablosu için veri çek
$payments_history = [];
$sql_payments = "SELECT
                    up.payment_status,
                    up.earnings,
                    up.completion_date, 
                    r.title AS research_title
                 FROM user_participations up
                 JOIN researches r ON up.research_id = r.id
                 WHERE up.user_id = ? AND (up.payment_status = 'paid' OR up.payment_status = 'Ödendi')
                 ORDER BY up.completion_date DESC";

$stmt_payments = $conn->prepare($sql_payments);
if ($stmt_payments) {
    $stmt_payments->bind_param("i", $loggedInUserId);
    $stmt_payments->execute();
    $result_payments_history = $stmt_payments->get_result();
    while ($row = $result_payments_history->fetch_assoc()) {
        $payments_history[] = $row;
    }
    $stmt_payments->close();
} else {
    $page_error .= "Ödeme geçmişi sorgusu hazırlanamadı: " . $conn->error;
}


// Kazanç Grafiği için veri çekme
$earnings_chart_data_payments_page = [];
$labels_payments = [];
$data_points_payments = [];

$sql_earnings_graph = "SELECT DATE_FORMAT(completion_date, '%e %b %Y') as formatted_date, earnings
                       FROM user_participations
                       WHERE user_id = ? AND earnings > 0 AND (payment_status = 'paid' OR payment_status = 'Ödendi')
                       ORDER BY completion_date ASC";

$stmt_earnings_graph = $conn->prepare($sql_earnings_graph);

if ($stmt_earnings_graph) {
    $stmt_earnings_graph->bind_param("i", $loggedInUserId);
    $stmt_earnings_graph->execute();
    $result_earnings_graph = $stmt_earnings_graph->get_result();
    while ($row = $result_earnings_graph->fetch_assoc()) {
        $labels_payments[] = $row['formatted_date'];
        $data_points_payments[] = $row['earnings'];
    }
    $stmt_earnings_graph->close();
} else {
    $page_error .= " Kazanç grafiği verisi sorgusu hazırlanamadı: " . $conn->error;
}

$earnings_chart_data_payments_page = [
    'labels' => $labels_payments,
    'data' => $data_points_payments
];

?>

<h1>Ödemeler</h1>

<?php if (!empty($page_error)): ?>
    <div class="alert alert-danger"><?= nl2br(htmlspecialchars(trim($page_error))) ?></div>
<?php endif; ?>

<div class="table-responsive stats-card" style="padding-bottom: 0;">
    <h2 style="padding: 0 0 15px 0; margin:0; font-size: 1.2em;">Ödeme Geçmişi</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Ödeme Durumu</th>
                <th>Araştırma</th>
                <th>Ödeme Miktarı</th>
                <th>Tarih</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($payments_history)): ?>
                <?php foreach ($payments_history as $payment): ?>
                    <tr>
                        <td>
                            <?php 
                            if ($payment['payment_status'] == 'paid' || $payment['payment_status'] == 'Ödendi'): 
                            ?>
                                <span class="payment-status-paid"><i class="fas fa-check-circle"></i> Ödendi</span>
                            <?php else: ?>
                                <span class="payment-status-pending"><?= htmlspecialchars(ucfirst($payment['payment_status'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($payment['research_title']) ?></td>
                        <td>₺<?= htmlspecialchars(number_format($payment['earnings'], 2, ',', '.')) ?></td>
                        <td><?= htmlspecialchars(time_ago($payment['completion_date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="no-data">Henüz tamamlanmış bir ödemeniz bulunmamaktadır.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if (count($payments_history) > 0): ?>
    <div class="table-summary" style="margin-top:0; border-top: 1px solid #e9ecef; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
        Toplam <?= count($payments_history) ?> adet ödeme kaydı.
    </div>
    <?php endif; ?>
</div>


<!-- CHART KONTEYNERİ - Inline style kaldırıldı (CSS dosyasından gelecek varsayılıyor) -->
<div class="chart-container"> 
    <h2>Alınan Ödemeler Grafiği (₺)</h2>
    <?php if (!empty($earnings_chart_data_payments_page['labels'])): ?>
        <canvas id="paymentsPageEarningsChart"></canvas>
    <?php else: ?>
        <p style="text-align:center; padding: 20px; color: #666;">Grafik için yeterli ödeme verisi bulunmamaktadır.</p>
    <?php endif; ?>
</div>

<?php if (!empty($earnings_chart_data_payments_page['labels'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctxPayments = document.getElementById('paymentsPageEarningsChart').getContext('2d');
    const earningsDataPayments = <?= json_encode($earnings_chart_data_payments_page); ?>;

    new Chart(ctxPayments, {
        type: 'bar',
        data: {
            labels: earningsDataPayments.labels,
            datasets: [{
                label: 'Alınan Ödeme (₺)',
                data: earningsDataPayments.data,
                backgroundColor: 'rgba(0, 123, 255, 0.7)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1,
                borderRadius: 5,
                barThickness: 'flex', 
                maxBarThickness: 80
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₺' + value;
                        }
                    }
                },
                x: {
                     grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(tooltipItems) {
                            return tooltipItems[0].label;
                        },
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
<?php endif; ?>