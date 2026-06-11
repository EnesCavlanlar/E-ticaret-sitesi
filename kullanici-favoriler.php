<?php
require_once 'includes/db_connection.php';
session_start();

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: index.php");
    exit;
}

// Kullanıcının favori ürünlerini getir
try {
    $stmt = $conn->prepare("
        SELECT 
            u.urun_id, 
            u.urun_adi, 
            u.aciklama,
            us.urun_fiyat, 
            us.stok_miktar,
            s.satici_adi,
            k.kategori_adi,
            (SELECT resim_url FROM urun WHERE urun_id = u.urun_id) as resim_url,
            COALESCE(AVG(y.puan), 0) as ortalama_puan,
            COUNT(y.puan) as degerlendirme_sayisi
        FROM favoriler f
        JOIN urun u ON f.urun_id = u.urun_id
        JOIN urun_satici us ON u.urun_id = us.urun_id
        JOIN satici s ON us.satici_id = s.satici_id
        LEFT JOIN kategori_urun ku ON u.urun_id = ku.urun_id
        LEFT JOIN kategori k ON ku.kategori_id = k.kategori_id
        LEFT JOIN yorum y ON u.urun_id = y.urun_id
        WHERE f.kullanici_id = :kullanici_id
        GROUP BY u.urun_id, u.urun_adi, u.aciklama, us.urun_fiyat, us.stok_miktar, s.satici_adi, k.kategori_adi
        ORDER BY u.urun_id DESC
    ");
    
    $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
    $stmt->execute();
    $favoriler = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Favori ürün yükleme hatası: " . $e->getMessage();
    $favoriler = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorilerim - GitmediGitmiyor</title>
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .favorites-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .favorites-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .favorites-header h1 {
            font-size: 32px;
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .product-card {
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .remove-favorite {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.2s;
        }

        .remove-favorite i {
            color: #ff4b4b;
            font-size: 18px;
            transition: all 0.2s;
        }

        .remove-favorite:hover {
            transform: scale(1.1);
            background: #ff4b4b;
        }

        .remove-favorite:hover i {
            color: white;
        }

        .product-image {
            position: relative;
            padding-top: 75%;
            overflow: hidden;
        }

        .product-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 15px;
        }

        .product-category {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .product-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 40px;
        }

        .product-description {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 40px;
        }

        .product-rating {
            margin-bottom: 10px;
        }

        .stars {
            display: inline-block;
            margin-right: 5px;
        }

        .stars i {
            color: #ffd700;
            font-size: 18px;
        }

        .rating-count {
            font-size: 12px;
            color: var(--text-muted);
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .product-seller {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .product-stock {
            font-size: 13px;
            color: var(--text-success);
            margin-bottom: 15px;
        }

        .stock-low {
            color: var(--text-warning);
        }

        .stock-out {
            color: var(--text-danger);
        }

        .product-actions {
            display: flex;
            justify-content: center;
        }

        .btn-primary {
            width: 100%;
            max-width: 200px;
            padding: 8px;
            font-size: 14px;
            text-align: center;
            border-radius: 4px;
            background: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .no-favorites {
            text-align: center;
            padding: 40px 20px;
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }

        .no-favorites p {
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .no-favorites a {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .no-favorites a:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="favorites-container">
        <?php include 'urunler.php'; ?>
        <div class="favorites-header">
            <h1>Favorilerim</h1>
        </div>

        <?php if (empty($favoriler)): ?>
            <div class="no-favorites">
                <p>Henüz favori ürününüz bulunmamaktadır.</p>
                <a href="index.php">Alışverişe Başla</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach($favoriler as $urun): ?>
                    <?php renderProductCard($urun, array_column($favoriler, 'urun_id')); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <script>
    function toggleFavorite(button, urunId) {
        if (!button.classList.contains('processing')) {
            button.classList.add('processing');
            
            fetch('includes/favori-ekle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'urun_id=' + urunId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Ürünü sayfadan kaldır
                    button.closest('.product-card').remove();
                    
                    // Eğer hiç ürün kalmadıysa sayfayı yenile
                    if (document.querySelectorAll('.product-card').length === 0) {
                        location.reload();
                    }
                } else {
                    alert(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu');
            })
            .finally(() => {
                button.classList.remove('processing');
            });
        }
    }

    function sepeteEkle(urunId) {
        // AJAX ile sepete ekleme işlemi yapılacak
        alert('Ürün sepete eklendi!');
    }
    </script>
</body>
</html> 