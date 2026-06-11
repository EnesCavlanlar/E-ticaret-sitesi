<?php
require_once 'includes/db_connection.php';
session_start();

// Satıcı girişi kontrolü
if (!isset($_SESSION['satici_id'])) {
    header("Location: index.php");
    exit();
}

// Ürün ID kontrolü
if (!isset($_GET['id'])) {
    header("Location: satici-urunler.php");
    exit();
}

$urun_id = $_GET['id'];

// Ürünün bu satıcıya ait olduğunu kontrol et
try {
    $stmt = $conn->prepare("
        SELECT u.*, us.urun_fiyat, us.stok_miktar, ku.kategori_id
        FROM urun u
        JOIN urun_satici us ON u.urun_id = us.urun_id
        JOIN kategori_urun ku ON u.urun_id = ku.urun_id
        WHERE u.urun_id = :urun_id AND us.satici_id = :satici_id
    ");
    $stmt->bindParam(':urun_id', $urun_id);
    $stmt->bindParam(':satici_id', $_SESSION['satici_id']);
    $stmt->execute();
    $urun = $stmt->fetch();

    if (!$urun) {
        header("Location: satici-urunler.php");
        exit();
    }
} catch(PDOException $e) {
    error_log("Ürün yükleme hatası: " . $e->getMessage());
    header("Location: satici-urunler.php");
    exit();
}

// Kategorileri getir
try {
    $stmt = $conn->query("SELECT * FROM kategori ORDER BY ust_kategori_id, kategori_adi");
    $tumKategoriler = $stmt->fetchAll();
    
    // Kategorileri hiyerarşik olarak düzenle
    $kategoriler = [];
    foreach ($tumKategoriler as $kategori) {
        if ($kategori['ust_kategori_id'] === null) {
            $kategoriler[$kategori['kategori_id']] = [
                'id' => $kategori['kategori_id'],
                'ad' => $kategori['kategori_adi'],
                'alt_kategoriler' => []
            ];
        }
    }
    foreach ($tumKategoriler as $kategori) {
        if ($kategori['ust_kategori_id'] !== null) {
            $kategoriler[$kategori['ust_kategori_id']]['alt_kategoriler'][] = [
                'id' => $kategori['kategori_id'],
                'ad' => $kategori['kategori_adi']
            ];
        }
    }
} catch(PDOException $e) {
    error_log("Kategori yükleme hatası: " . $e->getMessage());
    $kategoriler = [];
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urun_adi = trim($_POST['urun_adi']);
    $aciklama = trim($_POST['aciklama']);
    $kategori_id = $_POST['kategori_id'];
    $fiyat = floatval($_POST['fiyat']);
    $stok = intval($_POST['stok']);
    
    $hatalar = [];
    
    // Resim yükleme kontrolü
    $resim_yolu = $urun['resim_url']; // Mevcut resim yolunu koru
    if (isset($_FILES['urun_resim']) && $_FILES['urun_resim']['error'] !== UPLOAD_ERR_NO_FILE) {
        $gecerli_tipler = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_boyut = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['urun_resim']['type'], $gecerli_tipler)) {
            $hatalar[] = "Sadece JPG, JPEG ve PNG formatında resimler yüklenebilir.";
        } elseif ($_FILES['urun_resim']['size'] > $max_boyut) {
            $hatalar[] = "Resim boyutu 5MB'dan büyük olamaz.";
        } else {
            $resim_adi = uniqid() . '_' . basename($_FILES['urun_resim']['name']);
            $yuklenecek_yer = 'uploads/' . $resim_adi;
            
            if (move_uploaded_file($_FILES['urun_resim']['tmp_name'], $yuklenecek_yer)) {
                // Eski resmi sil
                if ($urun['resim_url'] && file_exists($urun['resim_url'])) {
                    unlink($urun['resim_url']);
                }
                $resim_yolu = $yuklenecek_yer;
            } else {
                $hatalar[] = "Resim yüklenirken bir hata oluştu.";
            }
        }
    }
    
    // Validasyonlar
    if (empty($urun_adi)) $hatalar[] = "Ürün adı boş bırakılamaz.";
    if (empty($aciklama)) $hatalar[] = "Ürün açıklaması boş bırakılamaz.";
    if (empty($kategori_id)) $hatalar[] = "Kategori seçilmelidir.";
    if ($fiyat <= 0) $hatalar[] = "Geçerli bir fiyat giriniz.";
    if ($stok < 0) $hatalar[] = "Geçerli bir stok miktarı giriniz.";
    
    // Hata yoksa ürünü güncelle
    if (empty($hatalar)) {
        try {
            $conn->beginTransaction();
            
            // Ürün bilgilerini güncelle
            $stmt = $conn->prepare("UPDATE urun SET urun_adi = :adi, aciklama = :aciklama, resim_url = :resim_url WHERE urun_id = :urun_id");
            $stmt->bindParam(':adi', $urun_adi);
            $stmt->bindParam(':aciklama', $aciklama);
            $stmt->bindParam(':resim_url', $resim_yolu);
            $stmt->bindParam(':urun_id', $urun_id);
            $stmt->execute();
            
            // Kategori bağlantısını güncelle
            $stmt = $conn->prepare("UPDATE kategori_urun SET kategori_id = :kategori_id WHERE urun_id = :urun_id");
            $stmt->bindParam(':kategori_id', $kategori_id);
            $stmt->bindParam(':urun_id', $urun_id);
            $stmt->execute();
            
            // Satıcı-ürün bilgilerini güncelle
            $stmt = $conn->prepare("UPDATE urun_satici SET urun_fiyat = :fiyat, stok_miktar = :stok WHERE urun_id = :urun_id AND satici_id = :satici_id");
            $stmt->bindParam(':fiyat', $fiyat);
            $stmt->bindParam(':stok', $stok);
            $stmt->bindParam(':urun_id', $urun_id);
            $stmt->bindParam(':satici_id', $_SESSION['satici_id']);
            $stmt->execute();
            
            $conn->commit();
            header("Location: satici-urunler.php?basarili=guncellendi");
            exit();
            
        } catch(PDOException $e) {
            $conn->rollBack();
            $hatalar[] = "Bir hata oluştu: " . $e->getMessage();
            error_log("Ürün güncelleme hatası: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Düzenle - GitmediGitmiyor</title>
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .product-container {
            max-width: 800px;
            margin: 30px auto;
            background: var(--white);
            padding: 30px;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }
        .product-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .product-header h1 {
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            height: 150px;
            resize: vertical;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
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
        .input-group {
            position: relative;
        }
        .input-group input {
            padding-right: 30px;
        }
        .input-group .currency-symbol {
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-color);
        }

        .current-image {
            max-width: 200px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: var(--shadow-sm);
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .product-container {
                margin: 15px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="product-container">
        <div class="product-header">
            <h1>Ürün Düzenle</h1>
            <p>Ürün bilgilerini güncellemek için aşağıdaki formu kullanın.</p>
        </div>

        <?php if (!empty($hatalar)): ?>
            <div class="alert alert-danger">
                <?php foreach ($hatalar as $hata): ?>
                    <p><i class="fas fa-exclamation-circle"></i> <?= $hata ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="urun_adi">Ürün Adı</label>
                <input type="text" id="urun_adi" name="urun_adi" value="<?= htmlspecialchars($urun['urun_adi']) ?>" required>
            </div>

            <div class="form-group">
                <label for="kategori_id">Kategori</label>
                <select id="kategori_id" name="kategori_id" required>
                    <option value="">Kategori Seçin</option>
                    <?php foreach ($kategoriler as $anaKategori): ?>
                        <optgroup label="<?= htmlspecialchars($anaKategori['ad']) ?>">
                            <?php foreach ($anaKategori['alt_kategoriler'] as $altKategori): ?>
                                <option value="<?= $altKategori['id'] ?>" <?= $urun['kategori_id'] == $altKategori['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($altKategori['ad']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Mevcut Resim</label>
                <?php if ($urun['resim_url']): ?>
                    <img src="<?= htmlspecialchars($urun['resim_url']) ?>" alt="Mevcut ürün resmi" class="current-image">
                <?php else: ?>
                    <p>Ürün resmi yok</p>
                <?php endif; ?>
                
                <label for="urun_resim">Yeni Resim Yükle (İsteğe bağlı)</label>
                <input type="file" id="urun_resim" name="urun_resim" accept="image/jpeg,image/png,image/jpg">
                <small class="form-text text-muted">Maksimum dosya boyutu: 5MB. İzin verilen formatlar: JPG, JPEG, PNG</small>
            </div>

            <div class="form-group">
                <label for="aciklama">Ürün Açıklaması</label>
                <textarea id="aciklama" name="aciklama" required><?= htmlspecialchars($urun['aciklama']) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fiyat">Fiyat</label>
                    <div class="input-group">
                        <span class="currency-symbol">₺</span>
                        <input type="number" id="fiyat" name="fiyat" step="0.01" min="0" value="<?= htmlspecialchars($urun['urun_fiyat']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="stok">Stok Miktarı</label>
                    <input type="number" id="stok" name="stok" min="0" value="<?= htmlspecialchars($urun['stok_miktar']) ?>" required>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Değişiklikleri Kaydet
            </button>
        </form>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 