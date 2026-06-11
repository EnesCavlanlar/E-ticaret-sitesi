<?php
require_once 'includes/db_connection.php';
session_start();

// Satıcı girişi kontrolü
if (!isset($_SESSION['satici_id'])) {
    header("Location: index.php");
    exit();
}

// Satıcı bilgilerini getir
try {
    $stmt = $conn->prepare("SELECT * FROM satici WHERE satici_id = :id");
    $stmt->bindParam(':id', $_SESSION['satici_id']);
    $stmt->execute();
    $satici = $stmt->fetch();
} catch(PDOException $e) {
    error_log("Satıcı bilgileri yükleme hatası: " . $e->getMessage());
    header("Location: index.php");
    exit();
}

// Satıcının ürün sayısını getir
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as urun_sayisi FROM urun_satici WHERE satici_id = :satici_id");
    $stmt->bindParam(':satici_id', $_SESSION['satici_id']);
    $stmt->execute();
    $urun_sayisi = $stmt->fetch()['urun_sayisi'];
} catch(PDOException $e) {
    $urun_sayisi = 0;
}

// Satıcının toplam satışını getir
try {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT s.siparis_id) as siparis_sayisi, 
               SUM(us.urun_fiyat * sd.urun_miktar) as toplam_satis
        FROM siparis s
        JOIN siparis_detay sd ON s.siparis_id = sd.siparis_id
        JOIN urun_satici us ON sd.urun_id = us.urun_id
        WHERE us.satici_id = :satici_id
        AND s.siparis_durumu = 'Teslim edildi'
    ");
    $stmt->bindParam(':satici_id', $_SESSION['satici_id']);
    $stmt->execute();
    $satis_bilgileri = $stmt->fetch();
} catch(PDOException $e) {
    $satis_bilgileri = ['siparis_sayisi' => 0, 'toplam_satis' => 0];
}

// Son 5 siparişi getir
try {
    $stmt = $conn->prepare("
        SELECT 
            s.siparis_id,
            s.siparis_tarihi,
            s.siparis_durumu,
            u.urun_adi,
            sd.urun_miktar,
            us.urun_fiyat,
            k.kullanici_adi,
            k.kullanici_soyadi,
            (sd.urun_miktar * us.urun_fiyat) as toplam_fiyat
        FROM siparis s
        JOIN sepet sp ON s.sepet_id = sp.sepet_id
        JOIN kullanici k ON sp.kullanici_id = k.kullanici_id
        JOIN siparis_detay sd ON s.siparis_id = sd.siparis_id
        JOIN urun u ON sd.urun_id = u.urun_id
        JOIN urun_satici us ON u.urun_id = us.urun_id
        WHERE us.satici_id = :satici_id
        ORDER BY s.siparis_tarihi DESC
        LIMIT 5
    ");
    $stmt->bindParam(':satici_id', $_SESSION['satici_id']);
    $stmt->execute();
    $son_siparisler = $stmt->fetchAll();
} catch(PDOException $e) {
    $son_siparisler = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satıcı Paneli - <?= htmlspecialchars($satici['satici_adi']) ?></title>
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .dashboard-header {
            background: var(--white);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
        }
        .dashboard-header h1 {
            color: var(--text-color);
            font-size: 24px;
            margin-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }
        .stat-card h3 {
            color: var(--text-color);
            font-size: 16px;
            margin-bottom: 10px;
        }
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        .recent-orders {
            background: var(--white);
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }
        .recent-orders h2 {
            color: var(--text-color);
            font-size: 20px;
            margin-bottom: 20px;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .orders-table th {
            font-weight: 500;
            color: var(--text-color);
            background: var(--light-gray);
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-siparis-alindi {
            background: #fff3e0;
            color: #ef6c00;
        }
        .status-kargoya-verildi {
            background: #e3f2fd;
            color: #1565c0;
        }
        .status-teslim-edildi {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .action-button:hover {
            background: var(--accent-color);
        }
        .action-button.secondary {
            background: var(--light-gray);
            color: var(--text-color);
        }
        .action-button.secondary:hover {
            background: var(--border-color);
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="dashboard">
        <div class="dashboard-header">
            <h1>Hoş Geldiniz, <?= htmlspecialchars($satici['satici_adi']) ?></h1>
            <p>Mağazanızın genel durumunu buradan takip edebilirsiniz.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Toplam Ürün</h3>
                <div class="value"><?= $urun_sayisi ?></div>
            </div>
            <div class="stat-card">
                <h3>Tamamlanan Sipariş</h3>
                <div class="value"><?= $satis_bilgileri['siparis_sayisi'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Toplam Satış</h3>
                <div class="value">₺<?= number_format($satis_bilgileri['toplam_satis'] ?? 0, 2, ',', '.') ?></div>
            </div>
        </div>

        <div class="recent-orders">
            <h2>Son Siparişler</h2>
            <?php if (empty($son_siparisler)): ?>
                <p>Henüz hiç sipariş bulunmuyor.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Sipariş No</th>
                                <th>Tarih</th>
                                <th>Müşteri</th>
                                <th>Ürün</th>
                                <th>Miktar</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($son_siparisler as $siparis): ?>
                                <tr>
                                    <td>#<?= $siparis['siparis_id'] ?></td>
                                    <td><?= date('d.m.Y', strtotime($siparis['siparis_tarihi'])) ?></td>
                                    <td><?= htmlspecialchars($siparis['kullanici_adi'] . ' ' . $siparis['kullanici_soyadi']) ?></td>
                                    <td><?= htmlspecialchars($siparis['urun_adi']) ?></td>
                                    <td><?= $siparis['urun_miktar'] ?></td>
                                    <td>₺<?= number_format($siparis['toplam_fiyat'], 2, ',', '.') ?></td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $siparis['siparis_durumu'])) ?>">
                                            <?= htmlspecialchars($siparis['siparis_durumu']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="quick-actions">
                <a href="satici-siparisler.php" class="action-button">
                    <i class="fas fa-list"></i>
                    Tüm Siparişler
                </a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>