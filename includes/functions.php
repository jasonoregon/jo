<?php
// ... (mevcut functions.php içeriği, örneğin time_ago fonksiyonu) ...

if (!function_exists('format_date_for_display')) {
    /**
     * Veritabanından gelen YYYY-MM-DD veya DATETIME formatındaki tarihi
     * GG-AA-YYYY formatında gösterir.
     * @param string|null $date_str_from_db Veritabanı tarih string'i
     * @param string $format İstenen çıktı formatı
     * @return string Formatlanmış tarih veya 'Bilinmiyor'
     */
    function format_date_for_display($date_str_from_db, $format = 'd-m-Y') {
        if (empty($date_str_from_db) || $date_str_from_db === '0000-00-00' || $date_str_from_db === '0000-00-00 00:00:00') {
            return 'Bilinmiyor';
        }
        try {
            // DateTime'ın YYYY-MM-DD veya YYYY-MM-DD HH:II:SS formatlarını anlayacağını varsayıyoruz
            $date_obj = new DateTime($date_str_from_db);
            return $date_obj->format($format);
        } catch (Exception $e) {
            // Hata durumunda orijinal string'i veya bir hata mesajı döndür
            // error_log("Tarih formatlama hatası: " . $e->getMessage() . " - Girdi: " . $date_str_from_db);
            return $date_str_from_db; // Veya 'Geçersiz Tarih'
        }
    }
}


if (!function_exists('time_ago')) {
    function time_ago($datetime_str, $full = false) {
        if (empty($datetime_str)) {
            return 'Bilinmiyor';
        }
        try {
            $now = new DateTime;
            $ago = new DateTime($datetime_str);
            $diff = $now->diff($ago);

            // Hafta sayısını $diff->d (günler) üzerinden hesapla
            $weeks = floor($diff->d / 7);
            // Haftalar çıkarıldıktan sonra kalan gün sayısını hesapla
            $days_remaining_after_weeks = $diff->d % 7;

            // Kullanılacak zaman birimleri (Türkçe)
            $unit_strings = array(
                'y' => 'yıl',
                'm' => 'ay',
                'w' => 'hafta', // 'w' anahtarını burada tutuyoruz, değeri $weeks olacak
                'd' => 'gün',   // 'd' anahtarı, $days_remaining_after_weeks için kullanılacak
                'h' => 'saat',
                'i' => 'dakika',
                's' => 'saniye',
            );

            $output_parts = []; // Sonuçta birleştirilecek zaman parçalarını tutacak dizi

            // Değerleri $diff nesnesinden ve hesapladığımız değişkenlerden alarak $output_parts dizisini oluştur
            if ($diff->y > 0) {
                $output_parts['y'] = $diff->y . ' ' . $unit_strings['y'];
            }
            if ($diff->m > 0) {
                $output_parts['m'] = $diff->m . ' ' . $unit_strings['m'];
            }
            if ($weeks > 0) { // Hesaplanan hafta sayısını kullan
                $output_parts['w'] = $weeks . ' ' . $unit_strings['w'];
            }
            if ($days_remaining_after_weeks > 0) { // Kalan gün sayısını kullan
                $output_parts['d'] = $days_remaining_after_weeks . ' ' . $unit_strings['d'];
            }
            if ($diff->h > 0) {
                $output_parts['h'] = $diff->h . ' ' . $unit_strings['h'];
            }
            if ($diff->i > 0) {
                $output_parts['i'] = $diff->i . ' ' . $unit_strings['i'];
            }
            // Saniyeleri sadece $full true ise ve diğer büyük birimler yoksa göstermek mantıklı olabilir,
            // ya da her zaman göstermek. Orijinal kod saniyeleri de içeriyordu.
            // Eğer diğer parçalar boşsa ve sadece saniye varsa, saniyeler gösterilir.
            if ($diff->s > 0) {
                 // Eğer $full false ise ve zaten başka birimler varsa saniyeyi atla (isteğe bağlı iyileştirme)
                if (!$full && !empty($output_parts) && ($diff->y || $diff->m || $weeks || $days_remaining_after_weeks || $diff->h || $diff->i)) {
                    // Başka birimler varken ve $full false ise saniyeleri gösterme
                } else {
                    $output_parts['s'] = $diff->s . ' ' . $unit_strings['s'];
                }
            }
            
            // Eğer hiçbir zaman parçası yoksa (çok kısa bir süre ise)
            if (empty($output_parts)) {
                return 'az önce';
            }

            // Eğer $full false ise, sadece en büyük zaman birimini al
            if (!$full) {
                // $output_parts dizisindeki ilk elemanı (en büyük birimi) al
                $final_string_array = [array_shift($output_parts)];
            } else {
                // $full true ise, tüm parçaları al
                $final_string_array = $output_parts;
            }

            return implode(', ', $final_string_array) . ' önce';

        } catch (Exception $e) {
            // Hata durumunda loglama yapılabilir, örn: error_log($e->getMessage());
            return 'Geçersiz Tarih';
        }
    }
}
?>