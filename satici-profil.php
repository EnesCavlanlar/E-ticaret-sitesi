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

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $satici_adi = trim($_POST['satici_adi']);
    $satici_adres = trim($_POST['satici_adres']);
    $satici_eposta = trim($_POST['satici_eposta']);
    $satici_no = trim($_POST['satici_no']);
    $mevcut_sifre = $_POST['mevcut_sifre'];
    $yeni_sifre = $_POST['yeni_sifre'];
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'];
    
    $hatalar = [];
    
    // Validasyonlar
    if (empty($satici_adi)) $hatalar[] = "Satıcı adı boş bırakılamaz.";
    if (empty($satici_adres)) $hatalar[] = "Adres boş bırakılamaz.";
    if (empty($satici_eposta)) $hatalar[] = "E-posta boş bırakılamaz.";
    if (!filter_var($satici_eposta, FILTER_VALIDATE_EMAIL)) $hatalar[] = "Geçerli bir e-posta adresi giriniz.";
    if (empty($satici_no)) $hatalar[] = "Telefon numarası boş bırakılamaz.";
    
    // E-posta benzersizlik kontrolü
    if ($satici_eposta !== $satici['satici_eposta']) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM satici WHERE satici_eposta = :eposta AND satici_id != :id");
        $stmt->bindParam(':eposta', $satici_eposta);
        $stmt->bindParam(':id', $_SESSION['satici_id']);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            $hatalar[] = "Bu e-posta adresi başka bir satıcı tarafından kullanılıyor.";
        }
    }
    
    // Şifre değişikliği yapılacaksa
    if (!empty($yeni_sifre)) {
        if ($mevcut_sifre !== $satici['satici_sifre']) {
            $hatalar[] = "Mevcut şifre yanlış.";
        }
        if (strlen($yeni_sifre) < 6) {
            $hatalar[] = "Yeni şifre en az 6 karakter olmalıdır.";
        }
        if ($yeni_sifre !== $yeni_sifre_tekrar) {
            $hatalar[] = "Yeni şifreler eşleşmiyor.";
        }
    }
    
    // Hata yoksa güncelle
    if (empty($hatalar)) {
        try {
            if (!empty($yeni_sifre)) {
                $stmt = $conn->prepare("
                    UPDATE satici 
                    SET satici_adi = :adi, 
                        satici_adres = :adres, 
                        satici_eposta = :eposta, 
                        satici_no = :telefon,
                        satici_sifre = :sifre
                    WHERE satici_id = :id
                ");
                $stmt->bindParam(':sifre', $yeni_sifre);
            } else {
                $stmt = $conn->prepare("
                    UPDATE satici 
                    SET satici_adi = :adi, 
                        satici_adres = :adres, 
                        satici_eposta = :eposta, 
                        satici_no = :telefon
                    WHERE satici_id = :id
                ");
            }
            
            $stmt->bindParam(':adi', $satici_adi);
            $stmt->bindParam(':adres', $satici_adres);
            $stmt->bindParam(':eposta', $satici_eposta);
            $stmt->bindParam(':telefon', $satici_no);
            $stmt->bindParam(':id', $_SESSION['satici_id']);
            
            if ($stmt->execute()) {
                $basarili = "Profil bilgileriniz başarıyla güncellendi.";
                // Güncel bilgileri yeniden yükle
                $stmt = $conn->prepare("SELECT * FROM satici WHERE satici_id = :id");
                $stmt->bindParam(':id', $_SESSION['satici_id']);
                $stmt->execute();
                $satici = $stmt->fetch();
            }
        } catch(PDOException $e) {
            $hatalar[] = "Bir hata oluştu, lütfen tekrar deneyin.";
            error_log("Satıcı güncelleme hatası: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Düzenle - <?= htmlspecialchars($satici['satici_adi']) ?></title>
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 30px auto;
            background: var(--white);
            padding: 30px;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }
        .profile-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .profile-header h1 {
            color: var(--text-color);
            font-size: 24px;
            margin-bottom: 10px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .password-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        .password-section h2 {
            font-size: 18px;
            color: var(--text-color);
            margin-bottom: 20px;
        }
        .alert {
            padding: 8px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            position: relative;
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
        .btn-primary {
            background: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background: var(--accent-color);
        }
        .alert .close-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            padding: 0 5px;
        }
        .alert .close-btn:hover {
            opacity: 1;
        }
        .alert .error-messages p {
            margin: 5px 0;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .profile-container {
                margin: 15px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <h1>Profil Düzenle</h1>
            <p>Satıcı hesap bilgilerinizi buradan güncelleyebilirsiniz.</p>
        </div>

        <?php if (!empty($basarili)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $basarili ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($hatalar)): ?>
            <div class="alert alert-danger">
                <?php foreach ($hatalar as $hata): ?>
                    <p><i class="fas fa-exclamation-circle"></i> <?= $hata ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="satici_adi">Satıcı Adı</label>
                    <input type="text" id="satici_adi" name="satici_adi" value="<?= htmlspecialchars($satici['satici_adi']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="satici_eposta">E-posta</label>
                    <input type="email" id="satici_eposta" name="satici_eposta" value="<?= htmlspecialchars($satici['satici_eposta']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="satici_no">Telefon</label>
                <input type="tel" id="satici_no" name="satici_no" value="<?= htmlspecialchars($satici['satici_no']) ?>" required>
            </div>

            <div class="form-group">
                <label for="satici_adres">Adres</label>
                <textarea id="satici_adres" name="satici_adres" required><?= htmlspecialchars($satici['satici_adres']) ?></textarea>
            </div>

            <div class="password-section">
                <h2>Şifre Değiştir</h2>
                <p style="margin-bottom: 20px;">Şifrenizi değiştirmek istemiyorsanız bu alanları boş bırakabilirsiniz.</p>
                
                <div id="password-errors" class="alert alert-danger" style="display: none;">
                    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                    <div class="error-messages"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="mevcut_sifre">Mevcut Şifre</label>
                        <input type="password" id="mevcut_sifre" name="mevcut_sifre">
                    </div>
                    <div class="form-group">
                        <label for="yeni_sifre">Yeni Şifre</label>
                        <input type="password" id="yeni_sifre" name="yeni_sifre">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="yeni_sifre_tekrar">Yeni Şifre (Tekrar)</label>
                    <input type="password" id="yeni_sifre_tekrar" name="yeni_sifre_tekrar">
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Değişiklikleri Kaydet
            </button>
        </form>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Şifre Değiştirme Onay Modalı -->
    <div id="confirmPasswordModal" class="modal">
        <div class="modal-content">
            <h3>Şifre Değiştirme Onayı</h3>
            <p>Şifrenizi değiştirmek istediğinizden emin misiniz?</p>
            <div class="modal-buttons">
                <button id="confirmYes" class="form-btn">Onayla</button>
                <button id="confirmNo" class="form-btn secondary">İptal</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const mevcutSifre = document.getElementById('mevcut_sifre');
        const yeniSifre = document.getElementById('yeni_sifre');
        const yeniSifreTekrar = document.getElementById('yeni_sifre_tekrar');
        const passwordErrors = document.getElementById('password-errors');
        const errorMessages = passwordErrors.querySelector('.error-messages');
        
        // Şifre alanlarının değişikliklerini izle
        [mevcutSifre, yeniSifre, yeniSifreTekrar].forEach(input => {
            input.addEventListener('input', function() {
                validatePasswordFields();
                // Hata mesajları varsa temizle
                passwordErrors.style.display = 'none';
            });
        });

        function validatePasswordFields() {
            // Eğer herhangi bir şifre alanı doluysa, hepsi zorunlu olur
            const isAnyPasswordFieldFilled = mevcutSifre.value || yeniSifre.value || yeniSifreTekrar.value;
            
            if (isAnyPasswordFieldFilled) {
                mevcutSifre.required = true;
                yeniSifre.required = true;
                yeniSifreTekrar.required = true;
            } else {
                mevcutSifre.required = false;
                yeniSifre.required = false;
                yeniSifreTekrar.required = false;
            }
        }

        form.addEventListener('submit', function(e) {
            const isAnyPasswordFieldFilled = mevcutSifre.value || yeniSifre.value || yeniSifreTekrar.value;
            
            if (isAnyPasswordFieldFilled) {
                e.preventDefault();
                
                // Client-side validasyon
                let errors = [];
                
                if (!mevcutSifre.value) {
                    errors.push("Mevcut şifre gereklidir.");
                }
                
                if (!yeniSifre.value) {
                    errors.push("Yeni şifre gereklidir.");
                } else if (yeniSifre.value.length < 6) {
                    errors.push("Yeni şifre en az 6 karakter olmalıdır.");
                }
                
                if (!yeniSifreTekrar.value) {
                    errors.push("Yeni şifre tekrarı gereklidir.");
                } else if (yeniSifre.value !== yeniSifreTekrar.value) {
                    errors.push("Yeni şifreler eşleşmiyor.");
                }
                
                if (errors.length > 0) {
                    // Hata mesajlarını şifre bölümünün altında göster
                    errorMessages.innerHTML = '';
                    errors.forEach(error => {
                        const p = document.createElement('p');
                        p.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${error}`;
                        errorMessages.appendChild(p);
                    });
                    passwordErrors.style.display = 'block';
                    
                    // Hata mesajlarının olduğu bölüme scroll
                    passwordErrors.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                
                // Mevcut şifreyi doğrula
                fetch('includes/satici-sifre-guncelle.php?validate=1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        current_password: mevcutSifre.value,
                        new_password: yeniSifre.value,
                        confirm_password: yeniSifreTekrar.value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.valid) {
                        // Şifre doğruysa onay modalını göster
                        const modal = document.getElementById('confirmPasswordModal');
                        modal.style.display = 'flex';
                        setTimeout(() => modal.classList.add('show'), 10);
                    } else {
                        // Hata mesajını göster
                        errorMessages.innerHTML = '<p><i class="fas fa-exclamation-circle"></i> Mevcut şifre yanlış.</p>';
                        passwordErrors.style.display = 'block';
                        passwordErrors.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    errorMessages.innerHTML = '<p><i class="fas fa-exclamation-circle"></i> Bir hata oluştu. Lütfen tekrar deneyin.</p>';
                    passwordErrors.style.display = 'block';
                    passwordErrors.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            }
        });

        // Modal işlevleri
        function closeModal() {
            const modal = document.getElementById('confirmPasswordModal');
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        document.getElementById('confirmYes').addEventListener('click', function() {
            closeModal();
            form.submit();
        });

        document.getElementById('confirmNo').addEventListener('click', closeModal);

        document.getElementById('confirmPasswordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    });
    </script>
</body>
</html> 