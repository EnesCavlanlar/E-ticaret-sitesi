<?php
require_once 'includes/db_connection.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['satici_id'])) {
    header("Location: index.php");
    exit();
}

try {
    $stmt = $conn->query("SELECT * FROM kategori ORDER BY ust_kategori_id, kategori_adi");
    $tumKategoriler = $stmt->fetchAll();
    
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urun_adi = trim($_POST['urun_adi']);
    $aciklama = trim($_POST['aciklama']);
    $kategori_id = $_POST['kategori_id'];
    $fiyat = floatval($_POST['fiyat']);
    $stok = intval($_POST['stok']);
    
    $hatalar = [];
    
    $resim_yolu = null;
    if (isset($_FILES['urun_resim']) && $_FILES['urun_resim']['error'] === UPLOAD_ERR_OK) {
        $gecerli_tipler = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $max_boyut = 5 * 1024 * 1024;
        
        if (!in_array($_FILES['urun_resim']['type'], $gecerli_tipler)) {
            $hatalar[] = "Sadece JPG, JPEG, PNG ve WebP formatında resimler yüklenebilir.";
        } elseif ($_FILES['urun_resim']['size'] > $max_boyut) {
            $hatalar[] = "Resim boyutu 5MB'dan büyük olamaz.";
        } else {
            $resim_adi = uniqid() . '_' . basename($_FILES['urun_resim']['name']);
            $yuklenecek_yer = 'uploads/' . $resim_adi;
            
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['urun_resim']['tmp_name'], $yuklenecek_yer)) {
                $resim_yolu = $yuklenecek_yer;
            } else {
                $upload_error = error_get_last();
                $hatalar[] = "Resim yüklenirken bir hata oluştu: " . 
                            "Hata kodu: " . $_FILES['urun_resim']['error'] . 
                            ", Hata mesajı: " . ($upload_error ? $upload_error['message'] : 'Bilinmeyen hata');
                error_log("Resim yükleme hatası - Dosya: " . $_FILES['urun_resim']['name'] . 
                         ", Hata: " . ($upload_error ? $upload_error['message'] : 'Bilinmeyen hata'));
            }
        }
    }
    
    if (empty($urun_adi)) $hatalar[] = "Ürün adı boş bırakılamaz.";
    if (empty($aciklama)) $hatalar[] = "Ürün açıklaması boş bırakılamaz.";
    if (empty($kategori_id)) $hatalar[] = "Kategori seçilmelidir.";
    if ($fiyat <= 0) $hatalar[] = "Geçerli bir fiyat giriniz.";
    if ($stok < 0) $hatalar[] = "Geçerli bir stok miktarı giriniz.";
    if (!isset($_FILES['urun_resim']) || $_FILES['urun_resim']['error'] === UPLOAD_ERR_NO_FILE) {
        $hatalar[] = "Ürün resmi yüklemelisiniz.";
    }
    
    if (empty($hatalar)) {
        try {
            $conn->beginTransaction();
            
            $stmt = $conn->prepare("INSERT INTO urun (urun_adi, aciklama, resim_url) VALUES (:adi, :aciklama, :resim_url)");
            $stmt->bindParam(':adi', $urun_adi);
            $stmt->bindParam(':aciklama', $aciklama);
            $stmt->bindParam(':resim_url', $resim_yolu);
            $stmt->execute();
            
            $urun_id = $conn->lastInsertId();
            
            $stmt = $conn->prepare("INSERT INTO kategori_urun (kategori_id, urun_id) VALUES (:kategori_id, :urun_id)");
            $stmt->bindParam(':kategori_id', $kategori_id);
            $stmt->bindParam(':urun_id', $urun_id);
            $stmt->execute();
            
            $stmt = $conn->prepare("INSERT INTO urun_satici (urun_id, satici_id, urun_fiyat, stok_miktar) VALUES (:urun_id, :satici_id, :fiyat, :stok)");
            $stmt->bindParam(':urun_id', $urun_id);
            $stmt->bindParam(':satici_id', $_SESSION['satici_id']);
            $stmt->bindParam(':fiyat', $fiyat);
            $stmt->bindParam(':stok', $stok);
            $stmt->execute();
            
            $conn->commit();
            $basarili = "Ürün başarıyla eklendi.";
            
            $urun_adi = $aciklama = '';
            $kategori_id = $fiyat = $stok = 0;
            
        } catch(PDOException $e) {
            $conn->rollBack();
            if (isset($resim_yolu) && file_exists($resim_yolu)) {
                unlink($resim_yolu);
            }
            $hatalar[] = "Bir hata oluştu: " . $e->getMessage();
            error_log("Ürün ekleme hatası: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Ürün Ekle - GitmediGitmiyor</title>
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
            <h1>Yeni Ürün Ekle</h1>
            <p>Mağazanıza yeni bir ürün eklemek için aşağıdaki formu doldurun.</p>
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

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="urun_adi">Ürün Adı</label>
                <input type="text" id="urun_adi" name="urun_adi" value="<?= htmlspecialchars($urun_adi ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="kategori_id">Kategori</label>
                <select id="kategori_id" name="kategori_id" required>
                    <option value="">Kategori Seçin</option>
                    <?php foreach ($kategoriler as $anaKategori): ?>
                        <optgroup label="<?= htmlspecialchars($anaKategori['ad']) ?>">
                            <?php foreach ($anaKategori['alt_kategoriler'] as $altKategori): ?>
                                <option value="<?= $altKategori['id'] ?>" <?= isset($kategori_id) && $kategori_id == $altKategori['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($altKategori['ad']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="urun_resim">Ürün Resmi</label>
                <input type="file" id="urun_resim" name="urun_resim" accept="image/jpeg,image/png,image/jpg,image/webp" required>
                <small class="form-text text-muted">Maksimum dosya boyutu: 5MB. İzin verilen formatlar: JPG, JPEG, PNG, WebP</small>
            </div>

            <div class="form-group">
                <label for="aciklama">Ürün Açıklaması</label>
                <textarea id="aciklama" name="aciklama" required><?= htmlspecialchars($aciklama ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fiyat">Fiyat</label>
                    <div class="input-group">
                        <span class="currency-symbol">₺</span>
                        <input type="number" id="fiyat" name="fiyat" step="0.01" min="0" value="<?= htmlspecialchars($fiyat ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="stok">Stok Miktarı</label>
                    <input type="number" id="stok" name="stok" min="0" value="<?= htmlspecialchars($stok ?? '') ?>" required>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-plus-circle"></i> Ürün Ekle
            </button>
        </form>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 