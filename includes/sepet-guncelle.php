<?php
require_once 'db_connection.php';
session_start();

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
}

// POST verilerini al
$urun_id = isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$urun_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit();
}

try {
    // Kullanıcının sepetini bul veya oluştur
    $stmt = $conn->prepare("SELECT sepet_id FROM sepet WHERE kullanici_id = :kullanici_id");
    $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
    $stmt->execute();
    $sepet = $stmt->fetch();

    if (!$sepet) {
        $stmt = $conn->prepare("INSERT INTO sepet (kullanici_id) VALUES (:kullanici_id)");
        $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
        $stmt->execute();
        $sepet_id = $conn->lastInsertId();
    } else {
        $sepet_id = $sepet['sepet_id'];
    }

    // Ürün stok kontrolü
    $stmt = $conn->prepare("SELECT stok_miktar FROM urun_satici WHERE urun_id = :urun_id");
    $stmt->bindParam(':urun_id', $urun_id);
    $stmt->execute();
    $stok = $stmt->fetch();

    if (!$stok) {
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
        exit();
    }

    // Mevcut sepet ürün miktarını kontrol et
    $stmt = $conn->prepare("SELECT urun_miktar FROM sepet_urun WHERE sepet_id = :sepet_id AND urun_id = :urun_id");
    $stmt->bindParam(':sepet_id', $sepet_id);
    $stmt->bindParam(':urun_id', $urun_id);
    $stmt->execute();
    $sepet_urun = $stmt->fetch();
    $mevcut_miktar = $sepet_urun ? $sepet_urun['urun_miktar'] : 0;

    switch ($action) {
        case 'increase':
            if ($mevcut_miktar >= $stok['stok_miktar']) {
                echo json_encode(['success' => false, 'message' => 'Yeterli stok yok']);
                exit();
            }

            if ($mevcut_miktar > 0) {
                $stmt = $conn->prepare("UPDATE sepet_urun SET urun_miktar = urun_miktar + 1 
                                      WHERE sepet_id = :sepet_id AND urun_id = :urun_id");
            } else {
                $stmt = $conn->prepare("INSERT INTO sepet_urun (sepet_id, urun_id, urun_miktar) 
                                      VALUES (:sepet_id, :urun_id, 1)");
            }
            break;

        case 'decrease':
            if ($mevcut_miktar <= 1) {
                $stmt = $conn->prepare("DELETE FROM sepet_urun 
                                      WHERE sepet_id = :sepet_id AND urun_id = :urun_id");
            } else {
                $stmt = $conn->prepare("UPDATE sepet_urun SET urun_miktar = urun_miktar - 1 
                                      WHERE sepet_id = :sepet_id AND urun_id = :urun_id");
            }
            break;

        case 'remove':
            $stmt = $conn->prepare("DELETE FROM sepet_urun 
                                  WHERE sepet_id = :sepet_id AND urun_id = :urun_id");
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
            exit();
    }

    $stmt->bindParam(':sepet_id', $sepet_id);
    $stmt->bindParam(':urun_id', $urun_id);
    $stmt->execute();

    echo json_encode(['success' => true]);

} catch(PDOException $e) {
    error_log("Sepet güncelleme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu']);
}
?> 