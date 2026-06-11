<?php
require_once 'includes/db_connection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Satıcı bilgilerini getir
$satici = null;
if(isset($_SESSION['satici_id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM satici WHERE satici_id = :id");
        $stmt->bindParam(':id', $_SESSION['satici_id']);
        $stmt->execute();
        $satici = $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Satıcı bilgileri yükleme hatası: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<header>
    <div class="container header-main">
        <?php if(isset($_SESSION['satici_id'])): ?>
            <!-- Satıcı Header -->
            <div class="seller-header">
                <a href="satici-panel.php" class="logo">GitmediGitmiyor Satıcı</a>
                <div class="user-actions">
                    <div class="account-dropdown">
                        <a href="satici-panel.php" class="account-trigger">
                            <i class="fa fa-store"></i>
                            <span><?= htmlspecialchars($satici['satici_adi']) ?></span>
                            <i class="fa fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-menu">
                            <a href="satici-panel.php">
                                <i class="fa fa-dashboard"></i>
                                <span>Panel</span>
                            </a>
                            <a href="satici-urunler.php">
                                <i class="fas fa-box"></i>
                                <span>Ürünlerim</span>
                            </a>
                            <a href="satici-siparisler.php">
                                <i class="fa fa-shopping-cart"></i>
                                <span>Siparişler</span>
                            </a>
                            <a href="satici-profil.php">
                                <i class="fa fa-user-edit"></i>
                                <span>Profil</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="includes/cikis.php" class="logout-link">
                                <i class="fa fa-sign-out-alt"></i>
                                <span>Çıkış Yap</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Normal Kullanıcı Header -->
            <a href="index.php" class="logo">GitmediGitmiyor</a>
            
            <div class="search-container">
                <form class="search-bar" action="arama.php" method="GET">
                    <input type="text" name="q" placeholder="Ürün, kategori veya marka ara">
                    <button type="submit"><i class="fa fa-search"></i></button>
                </form>
            </div>

            <div class="user-actions">
                <?php if(isset($_SESSION['kullanici_id'])): ?>
                    <div class="account-dropdown">
                        <a href="kullanici-hesap.php" class="account-trigger">
                            <i class="fa fa-user"></i>
                            <span><?= htmlspecialchars($kullanici['kullanici_adi']) ?></span>
                            <i class="fa fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-menu">
                            <a href="kullanici-hesap.php">
                                <i class="fas fa-user"></i>
                                <span>Hesap Bilgilerim</span>
                            </a>
                            <a href="kullanici-siparisler.php">
                                <i class="fas fa-box"></i>
                                <span>Siparişlerim</span>
                            </a>
                            <a href="kullanici-favoriler.php">
                                <i class="fas fa-heart"></i>
                                <span>Favorilerim</span>
                            </a>
                            <a href="kullanici-kartlar.php">
                                <i class="fas fa-credit-card"></i>
                                <span>Kartlarım</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="includes/cikis.php" class="logout-link">
                                <i class="fa fa-sign-out-alt"></i>
                                <span>Çıkış Yap</span>
                            </a>
                        </div>
                    </div>
                    <a href="kullanici-sepet.php" class="cart-button">
                        <i class="fa fa-shopping-cart"></i>
                        <span>Sepetim</span>
                        <?php if(isset($_SESSION['sepet_urun_sayisi'])): ?>
                            <span class="sepet-sayi"><?= $_SESSION['sepet_urun_sayisi'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="#" id="openLoginModal"><i class="fa fa-user"></i><span>Giriş Yap</span></a>
                    <a href="kullanici-sepet.php" class="cart-button">
                        <i class="fa fa-shopping-cart"></i>
                        <span>Sepetim</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</header> 

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
document.addEventListener('DOMContentLoaded', function() {
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

    if (openModalBtn) {
        openModalBtn.addEventListener("click", (e) => {
            e.preventDefault();
            modal.style.display = "flex";
            // Hata mesajlarını temizle
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener("click", () => {
            modal.style.display = "none";
            // URL'den hata parametresini temizle
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }

    // Modal dışına tıklandığında kapatma
    window.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.style.display = "none";
            // URL'den hata parametresini temizle
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });

    // Form submit işlemleri
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            if (!email || !password) {
                e.preventDefault();
                document.getElementById("loginError").textContent = "Tüm alanları doldurunuz!";
                document.getElementById("loginError").style.display = "block";
            }
        });
    }

    if (sellerLoginForm) {
        sellerLoginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('sellerEmail').value;
            const password = document.getElementById('sellerPassword').value;
            if (!email || !password) {
                e.preventDefault();
                document.getElementById("sellerError").textContent = "Tüm alanları doldurunuz!";
                document.getElementById("sellerError").style.display = "block";
            }
        });
    }

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
});
</script>
</body>
</html> 