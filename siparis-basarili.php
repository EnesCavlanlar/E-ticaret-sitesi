<?php
require_once 'includes/db_connection.php';
session_start();

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['kullanici_id']) || !isset($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}

// Sipariş bilgilerini getir
try {
    $stmt = $conn->prepare("
        SELECT s.*, sd.urun_miktar, u.urun_adi, us.urun_fiyat,
        COUNT(DISTINCT sd.urun_id) as toplam_urun_sayisi,
        SUM(sd.urun_miktar) as toplam_urun_adedi
        FROM siparis s
        JOIN siparis_detay sd ON s.siparis_id = sd.siparis_id
        JOIN urun u ON sd.urun_id = u.urun_id
        JOIN urun_satici us ON u.urun_id = us.urun_id
        WHERE s.siparis_id = :siparis_id
        GROUP BY s.siparis_id
    ");
    $stmt->bindParam(':siparis_id', $_GET['order_id']);
    $stmt->execute();
    $siparis = $stmt->fetch();

    // Sipariş ürünlerinin detaylarını getir
    $stmt = $conn->prepare("
        SELECT u.urun_adi, sd.urun_miktar, us.urun_fiyat
        FROM siparis_detay sd
        JOIN urun u ON sd.urun_id = u.urun_id
        JOIN urun_satici us ON u.urun_id = us.urun_id
        WHERE sd.siparis_id = :siparis_id
    ");
    $stmt->bindParam(':siparis_id', $_GET['order_id']);
    $stmt->execute();
    $siparis_urunler = $stmt->fetchAll();

    if (!$siparis) {
        header("Location: index.php");
        exit();
    }
} catch(PDOException $e) {
    error_log("Sipariş bilgileri yükleme hatası: " . $e->getMessage());
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Başarılı - GitmediGitmiyor</title>
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            text-align: center;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }

        .success-icon {
            color: #28a745;
            font-size: 64px;
            margin-bottom: 20px;
        }

        .success-title {
            font-size: 24px;
            color: var(--text-color);
            margin-bottom: 15px;
        }

        .order-details {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .order-info {
            margin: 10px 0;
            color: var(--text-color);
        }

        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .action-button {
            padding: 12px 24px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
        }

        .primary-button {
            background: var(--primary-color);
            color: white;
        }

        .secondary-button {
            background: #6c757d;
            color: white;
        }

        .action-button:hover {
            opacity: 0.9;
        }

        .order-products {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .order-products h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .order-product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .product-name {
            flex: 2;
            font-weight: 500;
        }

        .product-quantity {
            flex: 1;
            text-align: center;
            color: var(--text-muted);
        }

        .product-price {
            flex: 1;
            text-align: right;
            font-weight: 500;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="success-container">
        <i class="fas fa-check-circle success-icon"></i>
        <h1 class="success-title">Siparişiniz Başarıyla Alındı!</h1>
        <p>Siparişiniz başarıyla oluşturuldu ve işleme alındı.</p>

        <div class="order-details">
            <div class="order-info">
                <strong>Sipariş Numarası:</strong> #<?= $siparis['siparis_id'] ?>
            </div>
            <div class="order-info">
                <strong>Sipariş Tarihi:</strong> <?= date('d.m.Y', strtotime($siparis['siparis_tarihi'])) ?>
            </div>
            <div class="order-info">
                <strong>Toplam Tutar:</strong> ₺<?= number_format($siparis['toplam_tutar'], 2, ',', '.') ?>
            </div>
            <div class="order-info">
                <strong>Toplam Ürün Çeşidi:</strong> <?= $siparis['toplam_urun_sayisi'] ?> adet
            </div>
            <div class="order-info">
                <strong>Toplam Ürün Adedi:</strong> <?= $siparis['toplam_urun_adedi'] ?> adet
            </div>

            <!-- Sipariş Ürünleri Detayı -->
            <div class="order-products">
                <h3>Sipariş Edilen Ürünler</h3>
                <?php foreach ($siparis_urunler as $urun): ?>
                    <div class="order-product-item">
                        <span class="product-name"><?= htmlspecialchars($urun['urun_adi']) ?></span>
                        <span class="product-quantity"><?= $urun['urun_miktar'] ?> adet</span>
                        <span class="product-price">₺<?= number_format($urun['urun_fiyat'] * $urun['urun_miktar'], 2, ',', '.') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="action-buttons">
            <a href="kullanici-siparisler.php" class="action-button primary-button">
                Siparişlerimi Görüntüle
            </a>
            <a href="index.php" class="action-button secondary-button">
                Alışverişe Devam Et
            </a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <audio id="onaySesi" src="sounds/onay.mp3"></audio>
    <script>
      window.addEventListener('DOMContentLoaded', function() {
        var audio = document.getElementById('onaySesi');
        if (audio) {
          audio.play().catch(function(e) { /* autoplay engellenirse sessiz geç */ });
        }
      });
    </script>
</body>
</html>