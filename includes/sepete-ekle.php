<?php
require_once 'db_connection.php';
session_start();

error_log("=== Starting cart addition process ===");
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION data: " . print_r($_SESSION, true));

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    error_log("No user session found");
    echo json_encode(['success' => false, 'message' => 'Ürün eklemek için giriş yapmalısınız']);
    exit();
}

error_log("User ID: " . $_SESSION['kullanici_id']); // Debug log

// POST verilerini al
$urun_id = isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0;
$miktar = isset($_POST['miktar']) ? (int)$_POST['miktar'] : 1;

error_log("Processing cart addition - User ID: {$_SESSION['kullanici_id']}, Product ID: {$urun_id}, Quantity: {$miktar}");

if (!$urun_id) {
    error_log("Invalid product ID");
    echo json_encode(['success' => false, 'message' => 'Geçersiz ürün']);
    exit();
}

try {
    // Transaction başlat
    $conn->beginTransaction();
    error_log("Transaction started");

    // 1. Adım: Kullanıcının aktif sepeti var mı kontrol et
    $stmt = $conn->prepare("SELECT sepet_id FROM sepet WHERE kullanici_id = :kullanici_id");
    $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
    $stmt->execute();
    $sepet = $stmt->fetch();
    
    error_log("Checking existing cart - Result: " . ($sepet ? "Found cart ID: {$sepet['sepet_id']}" : "No cart found"));
    
    // Sepet yoksa oluştur
    if (!$sepet) {
        $stmt = $conn->prepare("INSERT INTO sepet (kullanici_id) VALUES (:kullanici_id)");
        $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
        $success = $stmt->execute();
        $sepet_id = $conn->lastInsertId();
        error_log("Created new cart - Success: " . ($success ? "true" : "false") . ", New cart ID: " . $sepet_id);
    } else {
        $sepet_id = $sepet['sepet_id'];
        error_log("Using existing cart ID: " . $sepet_id);
    }

    // 2. Adım: Ürün ve stok bilgilerini kontrol et
    $stmt = $conn->prepare("
        SELECT us.stok_miktar, us.urun_fiyat, us.satici_id
        FROM urun_satici us 
        WHERE us.urun_id = :urun_id
        LIMIT 1
    ");
    $stmt->bindParam(':urun_id', $urun_id);
    $stmt->execute();
    $urun = $stmt->fetch();

    error_log("Product check - Found: " . ($urun ? "yes" : "no") . ", Stock: " . ($urun ? $urun['stok_miktar'] : "N/A"));

    if (!$urun) {
        error_log("Product not found in database");
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
        exit();
    }

    if ($urun['stok_miktar'] < $miktar) {
        error_log("Insufficient stock - Requested: {$miktar}, Available: {$urun['stok_miktar']}");
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Yeterli stok bulunmuyor']);
        exit();
    }

    // 3. Adım: Ürün sepette var mı kontrol et
    $stmt = $conn->prepare("
        SELECT urun_miktar 
        FROM sepet_urun 
        WHERE sepet_id = :sepet_id AND urun_id = :urun_id
    ");
    $stmt->bindParam(':sepet_id', $sepet_id);
    $stmt->bindParam(':urun_id', $urun_id);
    $stmt->execute();
    $sepet_urun = $stmt->fetch();

    error_log("Cart product check - Already in cart: " . ($sepet_urun ? "yes" : "no"));

    // 4. Adım: Sepete ekle veya güncelle
    if ($sepet_urun) {
        // Ürün zaten sepette varsa miktarını güncelle
        $yeni_miktar = $sepet_urun['urun_miktar'] + $miktar;
        
        if ($yeni_miktar > $urun['stok_miktar']) {
            error_log("Cannot update - New quantity {$yeni_miktar} exceeds stock {$urun['stok_miktar']}");
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Yeterli stok bulunmuyor']);
            exit();
        }

        $stmt = $conn->prepare("
            UPDATE sepet_urun 
            SET urun_miktar = :miktar 
            WHERE sepet_id = :sepet_id AND urun_id = :urun_id
        ");
        $stmt->bindParam(':miktar', $yeni_miktar);
        error_log("Updating existing cart item - New quantity: {$yeni_miktar}");
    } else {
        // Ürün sepette yoksa yeni ekle
        $stmt = $conn->prepare("
            INSERT INTO sepet_urun (sepet_id, urun_id, urun_miktar) 
            VALUES (:sepet_id, :urun_id, :miktar)
        ");
        $stmt->bindParam(':miktar', $miktar);
        error_log("Adding new item to cart - Quantity: {$miktar}");
    }

    $stmt->bindParam(':sepet_id', $sepet_id);
    $stmt->bindParam(':urun_id', $urun_id);
    
    if (!$stmt->execute()) {
        error_log("Failed to update cart");
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Sepet güncellenirken bir hata oluştu']);
        exit();
    }

    // İşlemleri onayla
    $conn->commit();
    error_log("Transaction committed successfully");
    echo json_encode([
        'success' => true, 
        'message' => 'Ürün sepete eklendi'
    ]);

} catch(PDOException $e) {
    // Hata durumunda işlemleri geri al
    $conn->rollBack();
    error_log("Database error occurred:");
    error_log("Error message: " . $e->getMessage());
    error_log("Error code: " . $e->getCode());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu']);
}

error_log("=== Cart addition process completed ===");
?> 