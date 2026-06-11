<?php
require_once 'includes/db_connection.php';
session_start();

// Satıcı girişi kontrolü
if (!isset($_SESSION['satici_id'])) {
    header("Location: index.php");
    exit();
}

$satici_id = $_SESSION['satici_id'];

// Kargoya verme işlemi
if (isset($_POST['ship_order']) && isset($_POST['siparis_id'])) {
    try {
        $stmt = $conn->prepare("
            UPDATE siparis 
            SET siparis_durumu = 'Kargoya verildi'
            WHERE siparis_id = :siparis_id
        ");
        $stmt->bindParam(':siparis_id', $_POST['siparis_id']);
        $stmt->execute();
        
        header("Location: satici-siparisler.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Sipariş durumu güncellenirken bir hata oluştu: " . $e->getMessage();
    }
}

try {
    // Satıcının ürünlerine ait siparişleri getir
    $stmt = $conn->prepare("
        SELECT DISTINCT
            s.siparis_id,
            s.siparis_tarihi,
            s.siparis_durumu,
            u.urun_adi,
            sd.urun_miktar,
            us.urun_fiyat,
            (sd.urun_miktar * us.urun_fiyat) as toplam_fiyat,
            k.kullanici_adi,
            k.kullanici_soyadi,
            k.kullanici_adres,
            k.kullanici_tel
        FROM siparis s
        JOIN siparis_detay sd ON s.siparis_id = sd.siparis_id
        JOIN urun u ON sd.urun_id = u.urun_id
        JOIN urun_satici us ON u.urun_id = us.urun_id
        JOIN sepet sp ON s.sepet_id = sp.sepet_id
        JOIN kullanici k ON sp.kullanici_id = k.kullanici_id
        WHERE us.satici_id = :satici_id
        ORDER BY s.siparis_tarihi DESC
    ");
    
    $stmt->bindParam(':satici_id', $satici_id);
    $stmt->execute();
    $siparisler = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Bir hata oluştu: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siparişlerim - GitmediGitmiyor</title>
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .orders-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .orders-header h1 {
            color: var(--text-color);
            font-size: 24px;
            margin-bottom: 10px;
        }
        .order-card {
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
            padding: 20px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .order-id {
            font-weight: bold;
            color: var(--primary-color);
        }
        .order-date {
            color: var(--text-muted);
        }
        .order-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }
        .status-delivered {
            background-color: #cce5ff;
            color: #004085;
        }
        .order-details {
            margin-bottom: 15px;
        }
        .product-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .customer-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        .customer-info h4 {
            margin-bottom: 10px;
            color: var(--text-color);
        }
        .price {
            font-weight: bold;
            color: var(--primary-color);
        }
        .no-orders {
            text-align: center;
            padding: 50px;
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .order-status {
                margin-top: 10px;
            }
        }
        .ship-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }

        .ship-button:hover {
            background-color: #218838;
        }

        .ship-button:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="orders-container">
        <div class="orders-header">
            <h1>Siparişlerim</h1>
            <p>Mağazanıza gelen siparişleri buradan takip edebilirsiniz.</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Sipariş durumu başarıyla güncellendi!
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (empty($siparisler)): ?>
            <div class="no-orders">
                <i class="fas fa-box-open fa-3x" style="color: var(--text-muted); margin-bottom: 20px;"></i>
                <h3>Henüz hiç sipariş yok</h3>
                <p>Mağazanıza gelen siparişler burada listelenecektir.</p>
            </div>
        <?php else: ?>
            <?php foreach ($siparisler as $siparis): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-id">Sipariş #<?= htmlspecialchars($siparis['siparis_id']) ?></span>
                            <span class="order-date">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('d.m.Y', strtotime($siparis['siparis_tarihi'])) ?>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span class="order-status status-<?= strtolower($siparis['siparis_durumu']) ?>">
                                <?= htmlspecialchars($siparis['siparis_durumu']) ?>
                            </span>
                            <?php if ($siparis['siparis_durumu'] === 'Siparis alindi'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="siparis_id" value="<?= $siparis['siparis_id'] ?>">
                                    <button type="submit" name="ship_order" class="ship-button">
                                        <i class="fas fa-truck"></i> Kargoya Ver
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="product-info">
                            <div>
                                <strong><?= htmlspecialchars($siparis['urun_adi']) ?></strong>
                                <br>
                                <small>Miktar: <?= htmlspecialchars($siparis['urun_miktar']) ?> adet</small>
                            </div>
                            <div class="price">
                                ₺<?= number_format($siparis['toplam_fiyat'], 2, ',', '.') ?>
                            </div>
                        </div>
                    </div>

                    <div class="customer-info">
                        <h4>Müşteri Bilgileri</h4>
                        <p>
                            <strong>Ad Soyad:</strong> 
                            <?= htmlspecialchars($siparis['kullanici_adi'] . ' ' . $siparis['kullanici_soyadi']) ?>
                        </p>
                        <p>
                            <strong>Telefon:</strong> 
                            <?= htmlspecialchars($siparis['kullanici_tel']) ?>
                        </p>
                        <p>
                            <strong>Teslimat Adresi:</strong> 
                            <?= htmlspecialchars($siparis['kullanici_adres']) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 