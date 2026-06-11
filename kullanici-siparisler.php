<?php
require_once 'includes/db_connection.php';
session_start();

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: index.php");
    exit;
}

// Yorum ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $urun_id = $_POST['urun_id'];
    $yorum = $_POST['yorum'];
    $puan = $_POST['rating'];
    $kullanici_id = $_SESSION['kullanici_id'];
    $tarih = date('Y-m-d');

    try {
        // Önce bu ürün için daha önce yorum yapılmış mı kontrol et
        $check_stmt = $conn->prepare("SELECT yorum_id FROM yorum WHERE kullanici_id = :kullanici_id AND urun_id = :urun_id");
        $check_stmt->execute([
            ':kullanici_id' => $kullanici_id,
            ':urun_id' => $urun_id
        ]);

        if ($check_stmt->rowCount() > 0) {
            $error_message = "Bu ürün için daha önce değerlendirme yapmışsınız.";
        } else {
            // Yeni yorum ekle
            $stmt = $conn->prepare("
                INSERT INTO yorum (puan, kullanici_id, yorum_tarihi, yorum_icerigi, urun_id) 
                VALUES (:puan, :kullanici_id, :tarih, :yorum, :urun_id)
            ");
            
            $stmt->execute([
                ':puan' => $puan,
                ':kullanici_id' => $kullanici_id,
                ':tarih' => $tarih,
                ':yorum' => $yorum,
                ':urun_id' => $urun_id
            ]);

            $success_message = "Değerlendirmeniz başarıyla kaydedildi!";
        }
    } catch(PDOException $e) {
        $error_message = "Değerlendirme kaydedilirken bir hata oluştu: " . $e->getMessage();
    }
}

// Teslim alma işlemi
if (isset($_POST['confirm_delivery']) && isset($_POST['siparis_id'])) {
    try {
        $conn->beginTransaction();

        // Önce siparişi ve satıcıyı al
        $stmt = $conn->prepare("
            SELECT s.siparis_id, s.toplam_tutar, us.satici_id
            FROM siparis s
            JOIN siparis_detay sd ON s.siparis_id = sd.siparis_id
            JOIN urun u ON sd.urun_id = u.urun_id
            JOIN urun_satici us ON u.urun_id = us.urun_id
            WHERE s.siparis_id = :siparis_id
            LIMIT 1
        ");
        $stmt->bindParam(':siparis_id', $_POST['siparis_id']);
        $stmt->execute();
        $siparis_detay = $stmt->fetch();

        if ($siparis_detay) {
            // Siparişin durumunu güncelle
            $stmt = $conn->prepare("
                UPDATE siparis 
                SET siparis_durumu = 'Teslim edildi'
                WHERE siparis_id = :siparis_id
            ");
            $stmt->bindParam(':siparis_id', $_POST['siparis_id']);
            $stmt->execute();

            // Satıcının bakiyesini güncelle
            $stmt = $conn->prepare("
                UPDATE satici 
                SET satici_bakiye = satici_bakiye + :tutar
                WHERE satici_id = :satici_id
            ");
            $stmt->bindParam(':tutar', $siparis_detay['toplam_tutar']);
            $stmt->bindParam(':satici_id', $siparis_detay['satici_id']);
            $stmt->execute();

            $conn->commit();
            header("Location: kullanici-siparisler.php?success=1");
            exit();
        }
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "İşlem sırasında bir hata oluştu: " . $e->getMessage();
    }
}

// Kullanıcının siparişlerini getir
try {
    $stmt = $conn->prepare("
        SELECT 
            s.siparis_id,
            s.siparis_tarihi,
            s.siparis_durumu,
            s.toplam_tutar,
            GROUP_CONCAT(
                CONCAT(
                    u.urun_adi,
                    '|',
                    sd.urun_miktar,
                    '|',
                    sat.satici_adi,
                    '|',
                    us.urun_fiyat,
                    '|',
                    u.urun_id
                )
                SEPARATOR ';;'
            ) as siparis_urunler
        FROM siparis s
        JOIN sepet sp ON s.sepet_id = sp.sepet_id
        JOIN siparis_detay sd ON s.siparis_id = sd.siparis_id
        JOIN urun u ON sd.urun_id = u.urun_id
        JOIN urun_satici us ON u.urun_id = us.urun_id
        JOIN satici sat ON us.satici_id = sat.satici_id
        WHERE sp.kullanici_id = :kullanici_id
        GROUP BY s.siparis_id
        ORDER BY s.siparis_tarihi DESC
    ");
    
    $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
    $stmt->execute();
    $siparisler = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Sipariş yükleme hatası: " . $e->getMessage();
    $siparisler = [];
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
            text-align: center;
        }

        .orders-header h1 {
            font-size: 32px;
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .order-card {
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .order-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-date {
            font-size: 14px;
        }

        .order-status-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .order-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            height: 32px;
        }

        .order-content {
            padding: 20px;
        }

        .order-items {
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .item-seller {
            font-size: 14px;
            color: var(--text-muted);
        }

        .item-quantity {
            font-size: 14px;
            color: var(--text-color);
            margin: 0 20px;
        }

        .item-price {
            font-weight: 600;
            color: var(--primary-color);
            text-align: right;
        }

        .order-total {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding-top: 15px;
            border-top: 2px solid var(--border-color);
            font-size: 18px;
            font-weight: 600;
        }

        .order-total span {
            color: var(--primary-color);
            margin-left: 10px;
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .order-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .item-quantity {
                margin: 5px 0;
            }

            .item-price {
                text-align: left;
            }
        }

        .confirm-delivery-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 32px;
            margin: 0;
            line-height: 1;
        }

        .confirm-delivery-btn:hover {
            background-color: #218838;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
        }

        .status-siparis-alindi {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-kargoya-verildi {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-teslim-edildi {
            background-color: #d4edda;
            color: #155724;
        }

        .modal-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        #modalOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        #reviewModal {
            position: relative;
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .star-rating {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }

        .star-rating i {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
        }

        .star-rating i.active {
            color: #ffd700;
        }

        #reviewForm textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
            resize: vertical;
        }

        .submit-review-btn {
            display: block;
            width: 100%;
            padding: 10px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .close-modal {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        #reviewModal h2 {
            margin-top: 0;
            margin-bottom: 20px;
            padding-right: 30px;
        }

        .review-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 32px;
            margin: 0;
            line-height: 1;
            transition: background-color 0.2s;
        }

        .review-btn:hover {
            background-color: #218838;
        }

        .review-btn i {
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="orders-container">
        <div class="orders-header">
            <h1>Siparişlerim</h1>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Teslimat başarıyla onaylandı!
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($siparisler)): ?>
            <div class="order-card">
                <div class="order-content" style="text-align: center;">
                    <p>Henüz siparişiniz bulunmamaktadır.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach($siparisler as $siparis): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-date">
                            Sipariş Tarihi: <?= date('d.m.Y', strtotime($siparis['siparis_tarihi'])) ?>
                        </div>
                        <div class="order-status-container">
                            <div class="order-status status-<?= strtolower(str_replace(' ', '-', $siparis['siparis_durumu'])) ?>">
                                <i class="fas fa-<?= $siparis['siparis_durumu'] === 'Siparis alindi' ? 'clock' : ($siparis['siparis_durumu'] === 'Kargoya verildi' ? 'truck' : 'check') ?>"></i>
                                <?= htmlspecialchars($siparis['siparis_durumu']) ?>
                            </div>
                            <?php if ($siparis['siparis_durumu'] === 'Kargoya verildi'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="siparis_id" value="<?= $siparis['siparis_id'] ?>">
                                    <button type="submit" name="confirm_delivery" class="confirm-delivery-btn" onclick="return confirm('Siparişi teslim aldığınızı onaylıyor musunuz?')">
                                        <i class="fas fa-box-check"></i> Teslim Alındı
                                    </button>
                                </form>
                            <?php elseif ($siparis['siparis_durumu'] === 'Teslim edildi'): ?>
                                <?php 
                                $urunler = explode(';;', $siparis['siparis_urunler']);
                                foreach($urunler as $urun):
                                    list($urun_adi, $miktar, $satici_adi, $fiyat, $urun_id) = explode('|', $urun);
                                ?>
                                    <button type="button" class="review-btn" onclick="openReviewModal(<?= $urun_id ?>)">
                                        <i class="fas fa-star"></i> Değerlendir
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="order-content">
                        <div class="order-items">
                            <?php 
                            $urunler = explode(';;', $siparis['siparis_urunler']);
                            foreach($urunler as $urun):
                                list($urun_adi, $miktar, $satici_adi, $fiyat, $urun_id) = explode('|', $urun);
                            ?>
                                <div class="order-item">
                                    <div class="item-details">
                                        <div class="item-name"><?= htmlspecialchars($urun_adi) ?></div>
                                        <div class="item-seller">Satıcı: <?= htmlspecialchars($satici_adi) ?></div>
                                    </div>
                                    <div class="item-quantity"><?= $miktar ?> Adet</div>
                                    <div class="item-price">₺<?= number_format($fiyat * $miktar, 2, ',', '.') ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-total">
                            Toplam Tutar: <span>₺<?= number_format($siparis['toplam_tutar'], 2, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <div class="modal-container">
        <div id="modalOverlay"></div>
        <div id="reviewModal">
            <span class="close-modal">&times;</span>
            <h2>Ürün Değerlendirmesi</h2>
            <form id="reviewForm" method="POST" action="">
                <input type="hidden" name="urun_id" id="review_urun_id">
                <input type="hidden" name="rating" id="rating_value">
                <div class="star-rating" id="starRating">
                    <i class="fas fa-star" data-rating="1"></i>
                    <i class="fas fa-star" data-rating="2"></i>
                    <i class="fas fa-star" data-rating="3"></i>
                    <i class="fas fa-star" data-rating="4"></i>
                    <i class="fas fa-star" data-rating="5"></i>
                </div>
                <textarea name="yorum" placeholder="Ürün hakkında düşüncelerinizi yazın..." required></textarea>
                <button type="submit" name="submit_review" class="submit-review-btn">
                    <i class="fas fa-paper-plane"></i> Değerlendirmeyi Gönder
                </button>
            </form>
        </div>
    </div>

    <script>
        const modalContainer = document.querySelector('.modal-container');
        const modal = document.getElementById('reviewModal');
        const overlay = document.getElementById('modalOverlay');
        const closeBtn = document.querySelector('.close-modal');
        const stars = document.querySelectorAll('.star-rating i');
        const ratingInput = document.getElementById('rating_value');
        const reviewForm = document.getElementById('reviewForm');

        function openReviewModal(urunId) {
            modalContainer.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.getElementById('review_urun_id').value = urunId;
        }

        function closeModal() {
            modalContainer.style.display = 'none';
            document.body.style.overflow = '';
        }

        closeBtn.onclick = closeModal;
        overlay.onclick = closeModal;

        // Prevent modal from closing when clicking inside it
        modal.onclick = function(e) {
            e.stopPropagation();
        };

        stars.forEach(star => {
            star.addEventListener('mouseover', function() {
                const rating = this.getAttribute('data-rating');
                highlightStars(rating);
            });

            star.addEventListener('mouseout', function() {
                const currentRating = ratingInput.value || 0;
                highlightStars(currentRating);
            });

            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingInput.value = rating;
                highlightStars(rating);
            });
        });

        function highlightStars(rating) {
            stars.forEach(star => {
                const starRating = star.getAttribute('data-rating');
                if (starRating <= rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        reviewForm.addEventListener('submit', function(e) {
            if (!ratingInput.value) {
                e.preventDefault();
                alert('Lütfen bir yıldız değerlendirmesi yapın!');
            } else {
                // Form gönderildiğinde modalı kapat
                setTimeout(function() {
                    closeModal();
                }, 100);
            }
        });

        // Close modal with escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>