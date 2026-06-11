<?php
require_once 'db_connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['success' => false, 'message' => 'Lütfen giriş yapın']);
    exit;
}

if (!isset($_POST['urun_id'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];
$urun_id = (int)$_POST['urun_id'];

try {
    // Önce favorilerde var mı kontrol et
    $stmt = $conn->prepare("SELECT favori_id FROM favoriler WHERE kullanici_id = :kullanici_id AND urun_id = :urun_id");
    $stmt->bindParam(':kullanici_id', $kullanici_id);
    $stmt->bindParam(':urun_id', $urun_id);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        // Favori varsa kaldır
        $stmt = $conn->prepare("DELETE FROM favoriler WHERE kullanici_id = :kullanici_id AND urun_id = :urun_id");
        $stmt->bindParam(':kullanici_id', $kullanici_id);
        $stmt->bindParam(':urun_id', $urun_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Favori yoksa ekle
        $stmt = $conn->prepare("INSERT INTO favoriler (kullanici_id, urun_id) VALUES (:kullanici_id, :urun_id)");
        $stmt->bindParam(':kullanici_id', $kullanici_id);
        $stmt->bindParam(':urun_id', $urun_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} catch(PDOException $e) {
    error_log("Favori işlem hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu']);
} 