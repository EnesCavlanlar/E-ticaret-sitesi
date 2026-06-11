<?php
require_once 'includes/db_connection.php';
session_start();

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isim = trim($_POST['isim']);
    $eposta = trim($_POST['eposta']);
    $telefon = trim($_POST['telefon']);
    $firma_adi = trim($_POST['firma_adi']);
    $firma_adresi = trim($_POST['firma_adresi']);
    $aciklama = trim($_POST['aciklama']);
    
    // E-posta başlığı
    $konu = "Yeni Satıcı Başvurusu - " . $firma_adi;
    
    // E-posta içeriği
    $mesaj = "Yeni bir satıcı başvurusu alındı:\n\n";
    $mesaj .= "İsim: " . $isim . "\n";
    $mesaj .= "E-posta: " . $eposta . "\n";
    $mesaj .= "Telefon: " . $telefon . "\n";
    $mesaj .= "Firma Adı: " . $firma_adi . "\n";
    $mesaj .= "Firma Adresi: " . $firma_adresi . "\n";
    $mesaj .= "Açıklama: " . $aciklama . "\n";
    
    // E-posta başlıkları
    $headers = "From: " . $eposta . "\r\n";
    $headers .= "Reply-To: " . $eposta . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // E-postayı gönder
    if(mail("info@gitmedigitmiyor.com", $konu, $mesaj, $headers)) {
        $basarili = "Başvurunuz başarıyla alındı. En kısa sürede sizinle iletişime geçeceğiz.";
    } else {
        $hata = "Başvurunuz gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satıcı Ol - GitmediGitmiyor</title>
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .seller-form-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-header h1 {
            color: var(--text-color);
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: var(--text-muted);
            font-size: 16px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .submit-button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s ease;
        }
        
        .submit-button:hover {
            background: var(--accent-color);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="seller-form-container">
        <div class="form-header">
            <h1>Satıcı Olmak İstiyorum</h1>
            <p>GitmediGitmiyor'da satıcı olmak için aşağıdaki formu doldurun.</p>
        </div>

        <?php if (isset($basarili)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $basarili ?>
            </div>
        <?php endif; ?>

        <?php if (isset($hata)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $hata ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="isim">Adınız Soyadınız</label>
                    <input type="text" id="isim" name="isim" required>
                </div>
                <div class="form-group">
                    <label for="eposta">E-posta Adresiniz</label>
                    <input type="email" id="eposta" name="eposta" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="telefon">Telefon Numaranız</label>
                    <input type="tel" id="telefon" name="telefon" required>
                </div>
                <div class="form-group">
                    <label for="firma_adi">Firma Adınız</label>
                    <input type="text" id="firma_adi" name="firma_adi" required>
                </div>
            </div>

            <div class="form-group">
                <label for="firma_adresi">Firma Adresiniz</label>
                <textarea id="firma_adresi" name="firma_adresi" required></textarea>
            </div>

            <div class="form-group">
                <label for="aciklama">Satmak İstediğiniz Ürünler Hakkında Bilgi</label>
                <textarea id="aciklama" name="aciklama" required placeholder="Satmak istediğiniz ürün kategorileri ve ürünler hakkında kısa bir açıklama yazın..."></textarea>
            </div>

            <button type="submit" class="submit-button">
                <i class="fas fa-paper-plane"></i> Başvuru Yap
            </button>
        </form>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>