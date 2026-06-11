<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: ../index.php");
    exit();
}

try {
    $conn->beginTransaction();

    // Kullanıcının sepetini al
    $stmt = $conn->prepare("
        SELECT s.sepet_id, su.urun_id, su.urun_miktar, us.satici_id, us.stok_miktar, us.urun_fiyat
        FROM sepet s
        JOIN sepet_urun su ON s.sepet_id = su.sepet_id
        JOIN urun_satici us ON su.urun_id = us.urun_id
        WHERE s.kullanici_id = :kullanici_id
    ");
    $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
    $stmt->execute();
    $sepet_urunler = $stmt->fetchAll();

    if (empty($sepet_urunler)) {
        $_SESSION['error_message'] = "Sepetiniz boş";
        header("Location: ../kullanici-sepet.php");
        exit();
    }

    // Toplam tutarı hesapla
    $toplam_tutar = 0;
    foreach ($sepet_urunler as $urun) {
        $toplam_tutar += $urun['urun_fiyat'] * $urun['urun_miktar'];
    }

    // Her ürün için stok kontrolü ve güncelleme
    foreach ($sepet_urunler as $urun) {
        // Stok kontrolü
        if ($urun['stok_miktar'] < $urun['urun_miktar']) {
            $_SESSION['error_message'] = "Üzgünüz, bazı ürünler için yeterli stok bulunmuyor.";
            header("Location: ../kullanici-sepet.php");
            exit();
        }

        // Stok güncelleme
        $yeni_stok = $urun['stok_miktar'] - $urun['urun_miktar'];
        $stmt = $conn->prepare("
            UPDATE urun_satici 
            SET stok_miktar = :yeni_stok 
            WHERE urun_id = :urun_id AND satici_id = :satici_id
        ");
        $stmt->bindParam(':yeni_stok', $yeni_stok);
        $stmt->bindParam(':urun_id', $urun['urun_id']);
        $stmt->bindParam(':satici_id', $urun['satici_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Stok güncellenirken bir hata oluştu");
        }
    }

    // Siparişi kaydet
    $stmt = $conn->prepare("
        INSERT INTO siparis (sepet_id, siparis_durumu, toplam_tutar, siparis_tarihi)
        VALUES (:sepet_id, 'Onaylandı', :toplam_tutar, NOW())
    ");
    $stmt->bindParam(':sepet_id', $sepet_urunler[0]['sepet_id']);
    $stmt->bindParam(':toplam_tutar', $toplam_tutar);
    $stmt->execute();
    $siparis_id = $conn->lastInsertId();

    // Sipariş detaylarını kaydet
    foreach ($sepet_urunler as $urun) {
        $stmt = $conn->prepare("
            INSERT INTO siparis_detay (siparis_id, urun_id, urun_miktar)
            VALUES (:siparis_id, :urun_id, :miktar)
        ");
        $stmt->bindParam(':siparis_id', $siparis_id);
        $stmt->bindParam(':urun_id', $urun['urun_id']);
        $stmt->bindParam(':miktar', $urun['urun_miktar']);
        $stmt->execute();
    }

    // Sepeti temizle
    $stmt = $conn->prepare("DELETE FROM sepet_urun WHERE sepet_id = :sepet_id");
    $stmt->bindParam(':sepet_id', $sepet_urunler[0]['sepet_id']);
    $stmt->execute();

    $conn->commit();
    
    // Başarılı sipariş sayfasına yönlendir
    header("Location: ../siparis-basarili.php?order_id=" . $siparis_id);
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Sipariş tamamlama hatası: " . $e->getMessage());
    $_SESSION['error_message'] = "Bir hata oluştu: " . $e->getMessage();
    header("Location: ../kullanici-sepet.php");
    exit();
}
?> 