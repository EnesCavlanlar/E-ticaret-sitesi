<?php
$serverName = "DESKTOP-68HV2A8\SQLEXPRESS01";
$database = "eTicaret";

try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", null, null);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    echo "Veritabanına başarıyla bağlanıldı!";
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>
