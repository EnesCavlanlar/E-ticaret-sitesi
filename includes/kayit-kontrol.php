<?php
require_once 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = $_POST['kullanici_adi'] ?? '';
    $soyad = $_POST['kullanici_soyadi'] ?? '';
    $eposta = $_POST['kullanici_eposta'] ?? '';
    $tel = $_POST['kullanici_tel'] ?? '';
    $adres = $_POST['kullanici_adres'] ?? '';
    $sifre = $_POST['kullanici_sifre'] ?? '';

    try {
        // Transaction başlat
        $conn->beginTransaction();

    // E-posta daha önce kayıtlı mı?
    $stmt = $conn->prepare("SELECT * FROM kullanici WHERE kullanici_eposta = :eposta");
    $stmt->bindParam(':eposta', $eposta);
    $stmt->execute();
    if ($stmt->fetch()) {
            $conn->rollBack();
        header("Location: ../index.php?kayit=eposta_var");
        exit;
    }

        // Kullanıcı kaydı
        $stmt = $conn->prepare("INSERT INTO kullanici (kullanici_adi, kullanici_soyadi, kullanici_sifre, kullanici_eposta, kullanici_tel, kullanici_adres) VALUES (:ad, :soyad, :sifre, :eposta, :tel, :adres)");
        $stmt->bindParam(':ad', $ad);
        $stmt->bindParam(':soyad', $soyad);
        $stmt->bindParam(':sifre', $sifre);
        $stmt->bindParam(':eposta', $eposta);
        $stmt->bindParam(':tel', $tel);
        $stmt->bindParam(':adres', $adres);
        $stmt->execute();
        $kullanici_id = $conn->lastInsertId();

        // Kullanıcı için sepet oluştur
        $stmt = $conn->prepare("INSERT INTO sepet (kullanici_id) VALUES (:kullanici_id)");
        $stmt->bindParam(':kullanici_id', $kullanici_id);
        $stmt->execute();
        $sepet_id = $conn->lastInsertId();

        // Transaction'ı onayla
        $conn->commit();

        // Session'ı başlat
        $_SESSION['kullanici_id'] = $kullanici_id;
        $_SESSION['kullanici_adi'] = $ad;
        
        error_log("New user registered - User ID: $kullanici_id, Cart ID: $sepet_id");
        
        header("Location: ../index.php?kayit=basarili");
        exit;
        
    } catch (PDOException $e) {
        // Hata durumunda transaction'ı geri al
        $conn->rollBack();
        error_log("Registration error: " . $e->getMessage());
        header("Location: ../index.php?kayit=hata");
        exit;
    }
}
?> 