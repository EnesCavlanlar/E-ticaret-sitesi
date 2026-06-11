<?php
require_once 'includes/db_connection.php';
session_start();

// Eğer satıcı girişi yapılmışsa, satıcı paneline yönlendir
if(isset($_SESSION['satici_id'])) {
    header("Location: satici-panel.php");
    exit();
}

// Arama sorgusunu al
$arama_sorgusu = isset($_GET['q']) ? trim($_GET['q']) : '';

// Ürünleri getir
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
            COALESCE(AVG(y.puan), 0) as ortalama_puan,
            COUNT(y.puan) as degerlendirme_sayisi
        FROM Urun u
        JOIN Urun_Satici us ON u.urun_id = us.urun_id
        JOIN Satici s ON us.satici_id = s.satici_id
        LEFT JOIN kategori_urun ku ON u.urun_id = ku.urun_id
        LEFT JOIN kategori k ON ku.kategori_id = k.kategori_id
        LEFT JOIN yorum y ON u.urun_id = y.urun_id
        WHERE us.stok_miktar > 0
        AND (u.urun_adi LIKE :arama_sorgusu
        OR u.aciklama LIKE :arama_sorgusu
        OR k.kategori_adi LIKE :arama_sorgusu)
        GROUP BY u.urun_id, u.urun_adi, u.aciklama, u.resim_url, us.urun_fiyat, us.stok_miktar, s.satici_adi, k.kategori_adi
        ORDER BY u.urun_id DESC
    ");
    
    $arama_parametresi = "%{$arama_sorgusu}%";
    $stmt->bindParam(':arama_sorgusu', $arama_parametresi);
    $stmt->execute();
    $urunler = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Ürün arama hatası: " . $e->getMessage();
    $urunler = [];
}

// Kullanıcının favorilerini getir
$favori_urunler = [];
if (isset($_SESSION['kullanici_id'])) {
    try {
        $stmt = $conn->prepare("SELECT urun_id FROM favoriler WHERE kullanici_id = :kullanici_id");
        $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
        $stmt->execute();
        $favori_urunler = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {
        error_log("Favori yükleme hatası: " . $e->getMessage());
    }
}

// Ana kategorileri getir
try {
    $stmt = $conn->prepare("SELECT * FROM Kategori WHERE ust_kategori_id IS NULL");
    $stmt->execute();
    $anaKategoriler = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Kategori yükleme hatası: " . $e->getMessage();
    $anaKategoriler = [];
}

// Seçili kategoriyi al
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : null;

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Arama - GitmediGitmiyor</title>
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Smooth scrolling için */
        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Sticky header ve kategori menüsü için gölge efekti */
        header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .header-main {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
            flex: 0 0 200px;
            display: flex;
            align-items: center;
        }

        .search-container {
            flex: 0 1 600px;
            margin: 0 auto;
        }

        .search-bar {
            display: flex;
            width: 100%;
            position: relative;
        }

        .search-bar input {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 45px 12px 15px;
            font-size: 14px;
            width: 100%;
            transition: all 0.2s ease;
        }

        .search-bar input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 70, 190, 0.1);
        }

        .search-bar button {
            background: transparent;
            border: none;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            cursor: pointer;
            padding: 5px;
        }

        @media (max-width: 900px) {
            .header-main {
                flex-wrap: nowrap;
                padding: 10px 15px;
                gap: 10px;
            }
            
            .logo {
                flex: 0 0 auto;
                font-size: 18px;
            }
            
            .search-container {
                flex: 1;
                margin: 0 10px;
            }
        }

        /* Search Results Styles */
        .search-results {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            min-height: calc(100vh - 400px);
        }

        .search-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .search-header h1 {
            font-size: 24px;
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .search-header p {
            color: var(--text-muted);
            font-size: 16px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        /* Notification Popup Styles */
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

    <main class="search-results">
        <?php include 'urunler.php'; ?>
        <?php if ($arama_sorgusu): ?>
            <div class="search-header">
                <h1>Arama Sonuçları</h1>
                <p>"<?= htmlspecialchars($arama_sorgusu) ?>" için <?= count($urunler) ?> sonuç bulundu</p>
            </div>

            <?php if (!empty($urunler)): ?>
                <div class="products-grid">
                    <?php foreach($urunler as $urun): ?>
                        <?php renderProductCard($urun, $favori_urunler); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <h2>Aradığınız ürün bulunamadı</h2>
                    <p>Aramanıza uygun ürün bulunamadı. Lütfen farklı bir arama terimi deneyin.</p>
                    <a href="index.php" class="btn-primary">Ana Sayfa</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
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

    function showNotification() {
        const notification = document.getElementById('notification');
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
    </script>
</body>
</html> 