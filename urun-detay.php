<?php
require_once 'includes/db_connection.php';
session_start();

// Ürün ID'sini al
$urun_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$urun_id) {
    header("Location: index.php");
    exit();
}

// Ürün bilgilerini getir
try {
    $stmt = $conn->prepare("
        SELECT 
            u.urun_id,
            u.urun_adi,
            u.aciklama,
            u.resim_url,
            us.urun_fiyat,
            us.stok_miktar,
            s.satici_adi,
            k.kategori_adi,
            k.kategori_id,
            k.ust_kategori_id,
            (SELECT kategori_adi FROM kategori WHERE kategori_id = k.ust_kategori_id) as ust_kategori_adi,
            (SELECT kategori_id FROM kategori WHERE kategori_id = k.ust_kategori_id) as ust_kategori_id,
            COALESCE(AVG(y.puan), 0) as ortalama_puan,
            COUNT(y.puan) as degerlendirme_sayisi
        FROM Urun u
        JOIN Urun_Satici us ON u.urun_id = us.urun_id
        JOIN Satici s ON us.satici_id = s.satici_id
        LEFT JOIN kategori_urun ku ON u.urun_id = ku.urun_id
        LEFT JOIN kategori k ON ku.kategori_id = k.kategori_id
        LEFT JOIN yorum y ON u.urun_id = y.urun_id
        WHERE u.urun_id = :urun_id AND us.stok_miktar > 0
        GROUP BY u.urun_id, u.urun_adi, u.aciklama, u.resim_url, us.urun_fiyat, us.stok_miktar, s.satici_adi, k.kategori_adi, k.kategori_id, k.ust_kategori_id
    ");
    $stmt->bindParam(':urun_id', $urun_id);
    $stmt->execute();
    $urun = $stmt->fetch();

    if (!$urun) {
        header("Location: index.php");
        exit();
    }

    // Ürün yorumlarını getir
    $stmt = $conn->prepare("
        SELECT 
            y.*,
            k.kullanici_adi,
            k.kullanici_soyadi
        FROM yorum y
        JOIN kullanici k ON y.kullanici_id = k.kullanici_id
        WHERE y.urun_id = :urun_id
        ORDER BY y.yorum_tarihi DESC
    ");
    $stmt->bindParam(':urun_id', $urun_id);
    $stmt->execute();
    $yorumlar = $stmt->fetchAll();

} catch(PDOException $e) {
    echo "Ürün yükleme hatası: " . $e->getMessage();
    exit();
}

// Kullanıcının favorilerini kontrol et
$favori_durumu = false;
if (isset($_SESSION['kullanici_id'])) {
    try {
        $stmt = $conn->prepare("SELECT 1 FROM favoriler WHERE kullanici_id = :kullanici_id AND urun_id = :urun_id");
        $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
        $stmt->bindParam(':urun_id', $urun_id);
        $stmt->execute();
        $favori_durumu = $stmt->fetchColumn() ? true : false;
    } catch(PDOException $e) {
        error_log("Favori kontrol hatası: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($urun['urun_adi']) ?> - GitmediGitmiyor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .product-detail {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .product-images {
            position: relative;
        }

        .main-image {
            width: 100%;
            aspect-ratio: 1;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }

        .product-info {
            padding: 20px;
        }

        .product-category {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 10px;
        }

        /* Breadcrumb Styles */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .breadcrumb a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .breadcrumb a:hover {
            color: var(--primary-color);
        }

        .breadcrumb i {
            font-size: 12px;
            color: var(--text-muted);
        }

        .breadcrumb span {
            color: var(--text-color);
            font-size: 14px;
        }

        .product-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .stars {
            color: #ffd700;
        }

        .rating-count {
            color: var(--text-muted);
            font-size: 14px;
        }

        .product-price {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .product-seller {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 15px;
        }

        .product-stock {
            font-size: 14px;
            color: var(--text-success);
            margin-bottom: 20px;
        }

        .stock-low {
            color: var(--text-warning);
        }

        .stock-out {
            color: var(--text-danger);
        }

        .product-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .add-to-cart {
            flex: 2;
            padding: 15px;
            font-size: 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .add-to-cart:hover {
            background: rgba(0, 70, 190, 0.7);
        }

        .favorite-btn {
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

        .favorite-btn i {
            color: #999;
            font-size: 18px;
            transition: all 0.2s;
        }

        .favorite-btn.active i {
            color: #ff4444;
        }

        .favorite-btn:hover {
            transform: scale(1.1);
        }

        .favorite-btn:hover i {
            color: #ff4b4b;
        }

        .product-description {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
        }

        .description-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .description-content {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-color);
            margin-bottom: 30px;
        }

        .reviews-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
        }

        .reviews-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-color);
        }

        .review-item {
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .reviewer-name {
            font-weight: 600;
            color: var(--text-color);
        }

        .review-date {
            color: var(--text-muted);
            font-size: 12px;
        }

        .review-rating {
            color: #ffd700;
            margin-bottom: 10px;
        }

        .review-content {
            color: var(--text-color);
            font-size: 14px;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        .notification {
            position: fixed;
            top: 80px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 998;
            transform: translateY(-100%);
            opacity: 0;
            transition: all 0.3s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Sosyal Medya Paylaşım Butonları */
        .share-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .share-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            color: white;
            font-size: 18px;
        }

        .share-button.whatsapp {
            background-color: #25d366;
        }

        .share-button.twitter {
            background-color: #000000;
        }

        .share-button.telegram {
            background-color: #0088cc;
        }

        .share-button:hover {
            transform: scale(1.1);
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!-- Notification element -->
    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i>
        <span>Ürün sepete eklendi!</span>
    </div>

    <?php include 'kategoriler.php'; ?>

    <main class="product-detail">
        <div class="product-images">
            <?php if (isset($_SESSION['kullanici_id'])): ?>
                <button class="favorite-btn <?= $favori_durumu ? 'active' : '' ?>" 
                        onclick="toggleFavorite(this, <?= $urun['urun_id'] ?>)">
                    <i class="fas fa-heart"></i>
                </button>
            <?php endif; ?>
            <img src="<?= $urun['resim_url'] ?? 'images/placeholder.jpg' ?>" 
                 alt="<?= htmlspecialchars($urun['urun_adi']) ?>" 
                 class="main-image"
                 onerror="this.src='images/placeholder.jpg'">
        </div>

        <div class="product-info">
            <div class="breadcrumb">
                <a href="index.php">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right"></i>
                <?php if ($urun['ust_kategori_id']): ?>
                    <a href="index.php?category=<?= $urun['ust_kategori_id'] ?>">
                        <?= htmlspecialchars($urun['ust_kategori_adi']) ?>
                    </a>
                    <i class="fas fa-chevron-right"></i>
                <?php endif; ?>
                <?php if ($urun['kategori_id']): ?>
                    <a href="index.php?category=<?= $urun['kategori_id'] ?>">
                        <?= htmlspecialchars($urun['kategori_adi']) ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <h1 class="product-title">
                <?= htmlspecialchars($urun['urun_adi']) ?>
            </h1>

            <div class="product-rating">
                <div class="stars">
                    <?php
                    $rating = round($urun['ortalama_puan']);
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $rating) {
                            echo '<i class="fas fa-star"></i>';
                        } else {
                            echo '<i class="far fa-star"></i>';
                        }
                    }
                    ?>
                </div>
                <span class="rating-count">
                    <?= number_format($urun['ortalama_puan'], 1) ?> (<?= $urun['degerlendirme_sayisi'] ?> değerlendirme)
                </span>
            </div>

            <div class="product-price">
                ₺<?= number_format($urun['urun_fiyat'], 2, ',', '.') ?>
            </div>

            <div class="product-seller">
                Satıcı: <?= htmlspecialchars($urun['satici_adi']) ?>
            </div>

            <div class="product-stock <?= 
                $urun['stok_miktar'] == 0 ? 'stock-out' : 
                ($urun['stok_miktar'] <= 5 ? 'stock-low' : '') ?>">
                <?php
                if ($urun['stok_miktar'] == 0) {
                    echo 'Stokta Yok';
                } elseif ($urun['stok_miktar'] <= 5) {
                    echo 'Son ' . $urun['stok_miktar'] . ' ürün';
                } else {
                    echo 'Stokta ' . $urun['stok_miktar'] . ' adet';
                }
                ?>
            </div>

            <div class="product-actions">
                <?php if ($urun['stok_miktar'] > 0): ?>
                    <button class="add-to-cart" onclick="sepeteEkle(<?= $urun['urun_id'] ?>)">
                        Sepete Ekle
                    </button>
                <?php endif; ?>
            </div>

            <div class="share-buttons">
                <button class="share-button whatsapp" onclick="shareOnWhatsApp()" title="WhatsApp'ta Paylaş">
                    <i class="fab fa-whatsapp"></i>
                </button>
                <button class="share-button twitter" onclick="shareOnTwitter()" title="Twitter'da Paylaş">
                    <i class="fab fa-x-twitter"></i>
                </button>
                <button class="share-button telegram" onclick="shareOnTelegram()" title="Telegram'da Paylaş">
                    <i class="fab fa-telegram"></i>
                </button>
            </div>

            <div class="product-description">
                <h2 class="description-title">Ürün Açıklaması</h2>
                <div class="description-content">
                    <?= nl2br(htmlspecialchars($urun['aciklama'])) ?>
                </div>
            </div>

            <!-- Yorumlar Bölümü -->
            <div class="reviews-section">
                <h2 class="reviews-title">Ürün Değerlendirmeleri</h2>
                <?php if (empty($yorumlar)): ?>
                    <p>Bu ürün için henüz değerlendirme yapılmamış.</p>
                <?php else: ?>
                    <?php foreach ($yorumlar as $yorum): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <span class="reviewer-name">
                                    <?= htmlspecialchars($yorum['kullanici_adi']) ?> 
                                    <?= htmlspecialchars($yorum['kullanici_soyadi']) ?>
                                </span>
                                <span class="review-date">
                                    <?= date('d.m.Y', strtotime($yorum['yorum_tarihi'])) ?>
                                </span>
                            </div>
                            <div class="review-rating">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $yorum['puan']) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="review-content">
                                <?= nl2br(htmlspecialchars($yorum['yorum_icerigi'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
    // Modal işlemleri
    const modal = document.getElementById("loginModal");
    const openModalBtn = document.getElementById("openLoginModal");
    const closeModalBtn = document.getElementById("closeModal");
    const loginForm = document.getElementById("loginForm");
    const sellerLoginForm = document.getElementById("sellerLoginForm");
    const registerForm = document.getElementById("registerForm");

    // URL'den hata parametresini kontrol et
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const giris_turu = urlParams.get('giris_turu');

    if (error === 'invalid_credentials') {
        modal.style.display = "flex";
        if (giris_turu === 'satici') {
            // Satıcı tab'ını aktif et
            document.querySelector('[data-tab="seller"]').click();
            document.getElementById("sellerError").textContent = "E-posta veya şifre hatalı!";
            document.getElementById("sellerError").style.display = "block";
        } else {
            // Kullanıcı tab'ını aktif et
            document.querySelector('[data-tab="login"]').click();
            document.getElementById("loginError").textContent = "E-posta veya şifre hatalı!";
            document.getElementById("loginError").style.display = "block";
        }
    } else if (error === 'database_error') {
        modal.style.display = "flex";
        if (giris_turu === 'satici') {
            document.querySelector('[data-tab="seller"]').click();
            document.getElementById("sellerError").textContent = "Bir hata oluştu, lütfen tekrar deneyin.";
            document.getElementById("sellerError").style.display = "block";
        } else {
            document.querySelector('[data-tab="login"]').click();
            document.getElementById("loginError").textContent = "Bir hata oluştu, lütfen tekrar deneyin.";
            document.getElementById("loginError").style.display = "block";
        }
    }

    openModalBtn.addEventListener("click", () => {
        modal.style.display = "flex";
        // Add show class after a small delay to trigger transition
        requestAnimationFrame(() => {
            modal.classList.add('show');
        });
        // Hata mesajlarını temizle
        document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
    });

    closeModalBtn.addEventListener("click", () => {
        modal.classList.remove('show');
        // Remove display after transition
        setTimeout(() => {
            modal.style.display = "none";
        }, 300);
        // URL'den hata parametresini temizle
        window.history.replaceState({}, document.title, window.location.pathname);
    });

    // Modal dışına tıklandığında kapatma
    window.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = "none";
            }, 300);
            // URL'den hata parametresini temizle
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });

    // Form submit işlemleri
    loginForm.addEventListener('submit', function(e) {
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        if (!email || !password) {
            e.preventDefault();
            document.getElementById("loginError").textContent = "Tüm alanları doldurunuz!";
            document.getElementById("loginError").style.display = "block";
        }
    });

    sellerLoginForm.addEventListener('submit', function(e) {
        const email = document.getElementById('sellerEmail').value;
        const password = document.getElementById('sellerPassword').value;
        if (!email || !password) {
            e.preventDefault();
            document.getElementById("sellerError").textContent = "Tüm alanları doldurunuz!";
            document.getElementById("sellerError").style.display = "block";
        }
    });

    // Tab işlemleri
    const tabs = document.querySelectorAll(".tab");
    const tabContents = document.querySelectorAll(".tab-content");

    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            // Aktif tab'ı kaldır
            tabs.forEach(t => t.classList.remove("active"));
            tabContents.forEach(c => c.classList.remove("active"));
            
            // Yeni tab'ı aktif yap
            tab.classList.add("active");
            document.getElementById(tab.dataset.tab).classList.add("active");
            
            // Hata mesajlarını temizle
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
        });
    });

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
                    button.classList.toggle('active');
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

    function showNotification(message) {
        const notification = document.getElementById('notification');
        notification.querySelector('span').textContent = message;
        notification.classList.add('show');
        
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    function sepeteEkle(urunId) {
        fetch('includes/sepete-ekle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `urun_id=${urunId}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    showNotification('Ürün sepete eklendi');
                }
            } else {
                if (data.message === 'Ürün eklemek için giriş yapmalısınız') {
                    document.getElementById('loginModal').style.display = 'flex';
                } else {
                    showNotification(data.message || 'Bir hata oluştu');
                }
            }
        })
        .catch(error => {
            console.error('Sepete ekleme hatası:', error);
            showNotification('Bir hata oluştu');
        });
    }

    function shareOnWhatsApp() {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent('Bu ürünü incelemek ister misiniz? <?= htmlspecialchars($urun['urun_adi']) ?>');
        window.open(`https://wa.me/?text=${text}%20${url}`, '_blank');
    }

    function shareOnTwitter() {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent('Bu ürünü incelemek ister misiniz? <?= htmlspecialchars($urun['urun_adi']) ?>');
        window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
    }

    function shareOnTelegram() {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent('Bu ürünü incelemek ister misiniz? <?= htmlspecialchars($urun['urun_adi']) ?>');
        window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank');
    }
    </script>
</body>
</html> 