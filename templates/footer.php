    </div> <!-- .main-content sonu -->

    <!-- footer.php'de artık özel script yüklemesi yok (CDN header'da) -->
    <!-- Eğer custom.js dosyanız olsaydı ve header'da yüklenmesini istemeseydiniz burada olabilirdi -->

</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) { // $mysqli yerine $conn kullan
    $conn->close(); // Veritabanı bağlantısını kapat
}
?>