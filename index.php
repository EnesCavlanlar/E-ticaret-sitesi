<?php
require_once 'includes/db_connection.php';
session_start();

// Eğer satıcı girişi yapılmışsa, satıcı paneline yönlendir
if(isset($_SESSION['satici_id'])) {
    header("Location: satici-panel.php");
    exit();
}

// Seçili kategoriyi al
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : null;

// Kullanıcı bilgilerini getir
$kullanici = null;
if(isset($_SESSION['kullanici_id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM kullanici WHERE kullanici_id = :id");
        $stmt->bindParam(':id', $_SESSION['kullanici_id']);
        $stmt->execute();
        $kullanici = $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Kullanıcı bilgileri yükleme hatası: " . $e->getMessage());
    }
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

// Tüm ürünleri getir (kategori filtresi ile)
try {
    $query = "
        SELECT 
            u.urun_id, 
            u.urun_adi, 
            u.aciklama,
            us.urun_fiyat, 
            us.stok_miktar,
            s.satici_adi,
            k.kategori_adi,
            k.kategori_id,
            (SELECT resim_url FROM urun WHERE urun_id = u.urun_id) as resim_url,
            COALESCE(AVG(y.puan), 0) as ortalama_puan,
            COUNT(y.puan) as degerlendirme_sayisi
        FROM Urun u
        JOIN Urun_Satici us ON u.urun_id = us.urun_id
        JOIN Satici s ON us.satici_id = s.satici_id
        LEFT JOIN kategori_urun ku ON u.urun_id = ku.urun_id
        LEFT JOIN kategori k ON ku.kategori_id = k.kategori_id
        LEFT JOIN yorum y ON u.urun_id = y.urun_id
        WHERE us.stok_miktar > 0";
    
    if ($selected_category) {
        $query .= " AND (k.kategori_id = :category_id OR k.ust_kategori_id = :category_id)";
    }
    
    $query .= " GROUP BY u.urun_id, u.urun_adi, u.aciklama, us.urun_fiyat, us.stok_miktar, s.satici_adi, k.kategori_adi, k.kategori_id ORDER BY u.urun_id DESC";
    
    $stmt = $conn->prepare($query);
    
    if ($selected_category) {
        $stmt->bindParam(':category_id', $selected_category);
    }
    
    $stmt->execute();
    $urunler = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Ürün yükleme hatası: " . $e->getMessage();
    $urunler = [];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>GitmediGitmiyor - Alışveriş Sitesi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .products-container {
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .products-header {
            width: 100%;
            margin: 0 0 20px 0;
            padding: 0 20px;
            text-align: center;
        }
        .products-header h1 {
            font-size: 32px;
            color: var(--text-color);
            margin-bottom: 10px;
        }
        .products-header p {
            color: var(--text-muted);
            font-size: 16px;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            min-height: calc(100vh - 400px); /* Footer'ın alta sabitlenmesi için minimum yükseklik */
        }

        .products-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .products-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
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

        .notification i {
            font-size: 16px;
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

    <main class="main-content">
        <?php include 'urunler.php'; ?>
        <div class="products-header">
            <h1><?php 
                if ($selected_category) {
                    $categoryName = '';
                    foreach($anaKategoriler as $cat) {
                        if ($cat['kategori_id'] == $selected_category) {
                            $categoryName = $cat['kategori_adi'];
                            break;
                        }
                    }
                    if (!$categoryName) {
                        try {
                            $stmt = $conn->prepare("SELECT kategori_adi, ust_kategori_id FROM Kategori WHERE kategori_id = :id");
                            $stmt->bindParam(':id', $selected_category);
                            $stmt->execute();
                            $result = $stmt->fetch();
                            $categoryName = $result['kategori_adi'];
                            
                            // Üst kategori adını al
                            if ($result['ust_kategori_id']) {
                                $stmt = $conn->prepare("SELECT kategori_adi FROM Kategori WHERE kategori_id = :id");
                                $stmt->bindParam(':id', $result['ust_kategori_id']);
                                $stmt->execute();
                                $ustKategori = $stmt->fetch();
                                $categoryName = $ustKategori['kategori_adi'] . ' - ' . $categoryName;
                            }
                        } catch(PDOException $e) {
                            $categoryName = 'Kategori';
                        }
                    }
                    echo htmlspecialchars($categoryName);
                } else {
                    echo 'Tüm Ürünler';
                }
            ?></h1>
            <p>Satıcılarımızın sizin için seçtiği ürünleri keşfedin</p>
        </div>

        <div class="products-grid">
            <?php foreach($urunler as $urun): ?>
                <?php renderProductCard($urun, $favori_urunler); ?>
            <?php endforeach; ?>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <!-- Giriş Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <div class="tabs">
                <div class="tab active" data-tab="login">Giriş Yap</div>
                <div class="tab" data-tab="register">Kayıt Ol</div>
                <div class="tab" data-tab="seller">Satıcı Girişi</div>
            </div>

            <!-- Kullanıcı Giriş -->
            <div class="tab-content active" id="login">
                <form id="loginForm" action="includes/giris-kontrol.php" method="POST">
                    <p class="error-message" id="loginError" style="display: none; color: red; margin-bottom: 10px;"></p>
                    <div class="form-group">
                        <label for="loginEmail">E-posta</label>
                        <input type="email" id="loginEmail" name="kullanici_eposta" required>
                    </div>
                    <div class="form-group">
                        <label for="loginPassword">Şifre</label>
                        <input type="password" id="loginPassword" name="kullanici_sifre" required>
                    </div>
                    <input type="hidden" name="giris_turu" value="kullanici">
                    <button type="submit" class="form-btn">Giriş Yap</button>
                    <div class="form-footer">
                        <a href="sifremi-unuttum.php">Şifremi Unuttum</a>
                    </div>
                </form>
            </div>

            <!-- Kullanıcı Kayıt -->
            <div class="tab-content" id="register">
                <form id="registerForm" action="includes/kayit-kontrol.php" method="POST">
                    <p class="error-message" id="registerError" style="display: none; color: red; margin-bottom: 10px;"></p>
                    <div class="form-group">
                        <label for="registerName">Ad</label>
                        <input type="text" id="registerName" name="kullanici_adi" required>
                    </div>
                    <div class="form-group">
                        <label for="registerSurname">Soyad</label>
                        <input type="text" id="registerSurname" name="kullanici_soyadi" required>
                    </div>
                    <div class="form-group">
                        <label for="registerEmail">E-posta</label>
                        <input type="email" id="registerEmail" name="kullanici_eposta" required>
                    </div>
                    <div class="form-group">
                        <label for="registerTel">Telefon</label>
                        <input type="tel" id="registerTel" name="kullanici_tel" required>
                    </div>
                    <div class="form-group">
                        <label for="registerAddress">Adres</label>
                        <textarea id="registerAddress" name="kullanici_adres" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="registerPassword">Şifre</label>
                        <input type="password" id="registerPassword" name="kullanici_sifre" required>
                    </div>
                    <button type="submit" class="form-btn">Kayıt Ol</button>
                </form>
            </div>

            <!-- Satıcı Giriş -->
            <div class="tab-content" id="seller">
                <form id="sellerLoginForm" action="includes/giris-kontrol.php" method="POST">
                    <p class="error-message" id="sellerError" style="display: none; color: red; margin-bottom: 10px;"></p>
                    <div class="form-group">
                        <label for="sellerEmail">E-posta</label>
                        <input type="email" id="sellerEmail" name="satici_eposta" required>
                    </div>
                    <div class="form-group">
                        <label for="sellerPassword">Şifre</label>
                        <input type="password" id="sellerPassword" name="satici_sifre" required>
                    </div>
                    <input type="hidden" name="giris_turu" value="satici">
                    <button type="submit" class="form-btn">Giriş Yap</button>
                    <div class="form-footer">
                        <a href="satici-ol.php">Satıcı Olmak İstiyorum</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
        
        // 3 saniye sonra bildirimi kaldır
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
