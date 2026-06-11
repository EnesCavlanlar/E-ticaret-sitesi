<?php
require_once 'includes/db_connection.php';
session_start();

// Satıcı girişi kontrolü
if (!isset($_SESSION['satici_id'])) {
    header("Location: index.php");
    exit();
}

// Satıcının ürünlerini getir
try {
    $stmt = $conn->prepare("
        SELECT u.urun_id, u.urun_adi, u.aciklama, u.resim_url, 
               us.urun_fiyat, us.stok_miktar,
               k.kategori_adi
        FROM urun u
        JOIN urun_satici us ON u.urun_id = us.urun_id
        JOIN kategori_urun ku ON u.urun_id = ku.urun_id
        JOIN kategori k ON ku.kategori_id = k.kategori_id
        WHERE us.satici_id = :satici_id
        ORDER BY u.urun_id DESC
    ");
    $stmt->bindParam(':satici_id', $_SESSION['satici_id']);
    $stmt->execute();
    $urunler = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Ürün listesi yükleme hatası: " . $e->getMessage());
    $urunler = [];
}

// Ürün silme işlemi
if (isset($_POST['sil']) && isset($_POST['urun_id'])) {
    $urun_id = $_POST['urun_id'];
    
    try {
        $conn->beginTransaction();
        
        // Önce ürünün resmini sil
        $stmt = $conn->prepare("SELECT resim_url FROM urun WHERE urun_id = :urun_id");
        $stmt->bindParam(':urun_id', $urun_id);
        $stmt->execute();
        $resim = $stmt->fetch();
        
        if ($resim && $resim['resim_url'] && file_exists($resim['resim_url'])) {
            unlink($resim['resim_url']);
        }
        
        // İlişkili kayıtları sil
        $stmt = $conn->prepare("DELETE FROM kategori_urun WHERE urun_id = :urun_id");
        $stmt->bindParam(':urun_id', $urun_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM urun_satici WHERE urun_id = :urun_id");
        $stmt->bindParam(':urun_id', $urun_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM urun WHERE urun_id = :urun_id");
        $stmt->bindParam(':urun_id', $urun_id);
        $stmt->execute();
        
        $conn->commit();
        $basarili = "Ürün başarıyla silindi.";
        
        // Sayfayı yenile
        header("Location: satici-urunler.php?basarili=silindi");
        exit();
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $hata = "Ürün silinirken bir hata oluştu: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürünlerim - GitmediGitmiyor</title>
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .products-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }
        .product-card {
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-details {
            padding: 20px;
        }
        .product-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        .product-category {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 10px;
        }
        .product-price {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        .product-stock {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 15px;
        }
        .product-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: #000;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="products-container">
        <div class="products-header">
            <h1>Ürünlerim</h1>
            <a href="satici-urun-ekle.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Yeni Ürün Ekle
            </a>
        </div>

        <?php if (isset($_GET['basarili']) && $_GET['basarili'] == 'silindi'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Ürün başarıyla silindi.
            </div>
        <?php endif; ?>

        <?php if (isset($hata)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $hata ?>
            </div>
        <?php endif; ?>

        <?php if (empty($urunler)): ?>
            <div style="text-align: center; padding: 50px;">
                <i class="fas fa-box-open" style="font-size: 48px; color: var(--text-muted); margin-bottom: 20px;"></i>
                <p>Henüz ürününüz bulunmuyor.</p>
                <a href="satici-urun-ekle.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i> İlk Ürününüzü Ekleyin
                </a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($urunler as $urun): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?= htmlspecialchars($urun['resim_url'] ?? 'images/placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($urun['urun_adi']) ?>"
                                 onerror="this.src='images/placeholder.jpg'">
                        </div>
                        <div class="product-details">
                            <h2 class="product-title"><?= htmlspecialchars($urun['urun_adi']) ?></h2>
                            <div class="product-category">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($urun['kategori_adi']) ?>
                            </div>
                            <div class="product-price">₺<?= number_format($urun['urun_fiyat'], 2, ',', '.') ?></div>
                            <div class="product-stock">
                                <i class="fas fa-cubes"></i> Stok: <?= $urun['stok_miktar'] ?> adet
                            </div>
                            <div class="product-actions">
                                <a href="satici-urun-duzenle.php?id=<?= $urun['urun_id'] ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Düzenle
                                </a>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Bu ürünü silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="urun_id" value="<?= $urun['urun_id'] ?>">
                                    <button type="submit" name="sil" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Sil
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 