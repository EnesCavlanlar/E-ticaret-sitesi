<?php
require_once 'includes/db_connection.php';
session_start();

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: index.php");
    exit();
}

// Kullanıcı bilgilerini getir
$kullanici = null;
try {
    $stmt = $conn->prepare("SELECT * FROM kullanici WHERE kullanici_id = :id");
    $stmt->bindParam(':id', $_SESSION['kullanici_id']);
    $stmt->execute();
    $kullanici = $stmt->fetch();
} catch(PDOException $e) {
    error_log("Kullanıcı bilgileri yükleme hatası: " . $e->getMessage());
}

// Kullanıcının kartlarını getir
$kartlar = [];
try {
    $stmt = $conn->prepare("
        SELECT kb.* 
        FROM kart_bilgileri kb
        INNER JOIN kullanici_kart kk ON kb.kart_id = kk.kart_id
        WHERE kk.kullanici_id = :kullanici_id
    ");
    $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
    $stmt->execute();
    $kartlar = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Kart bilgileri yükleme hatası: " . $e->getMessage());
}

// Kart ekleme işlemi
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_card_id'])) {
        try {
            $conn->beginTransaction();

            $update_sql = "UPDATE kart_bilgileri SET 
                kart_adi = :kart_adi,
                son_kullanma_tarihi = :son_kullanma_tarihi";
            
            // Add CVV to update only if provided
            if (!empty($_POST['edit_cvv'])) {
                $update_sql .= ", cvv = :cvv";
            }
            
            $update_sql .= " WHERE kart_id = :kart_id AND kullanici_id = :kullanici_id";
            
            $stmt = $conn->prepare($update_sql);
            
            // Format date
            $tarih_parcalari = explode('/', $_POST['edit_son_kullanma_tarihi']);
            $son_kullanma = "20{$tarih_parcalari[1]}-{$tarih_parcalari[0]}-01";
            
            $stmt->bindParam(':kart_adi', $_POST['edit_kart_adi']);
            $stmt->bindParam(':son_kullanma_tarihi', $son_kullanma);
            $stmt->bindParam(':kart_id', $_POST['edit_card_id']);
            $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
            
            if (!empty($_POST['edit_cvv'])) {
                $stmt->bindParam(':cvv', $_POST['edit_cvv']);
            }
            
            if ($stmt->execute()) {
                $conn->commit();
                header("Location: kullanici-kartlar.php?success=3");
                exit();
            }
        } catch(PDOException $e) {
            $conn->rollBack();
            $error_message = "Kart güncellenirken bir hata oluştu: " . $e->getMessage();
        }
    } else if (isset($_POST['kart_adi']) && isset($_POST['kart_no']) && isset($_POST['cvv']) && isset($_POST['son_kullanma_tarihi'])) {
        try {
            $conn->beginTransaction();

            // Önce kart_bilgileri tablosuna ekle
            $stmt = $conn->prepare("
                INSERT INTO kart_bilgileri (kullanici_id, kart_adi, kart_no, cvv, son_kullanma_tarihi)
                VALUES (:kullanici_id, :kart_adi, :kart_no, :cvv, :son_kullanma_tarihi)
            ");
            
            $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
            $stmt->bindParam(':kart_adi', $_POST['kart_adi']);
            $stmt->bindParam(':kart_no', $_POST['kart_no']);
            $stmt->bindParam(':cvv', $_POST['cvv']);
            
            // Tarih formatını düzenle (MM/YY -> YYYY-MM-DD)
            $tarih_parcalari = explode('/', $_POST['son_kullanma_tarihi']);
            $son_kullanma = "20{$tarih_parcalari[1]}-{$tarih_parcalari[0]}-01";
            $stmt->bindParam(':son_kullanma_tarihi', $son_kullanma);
            
            $stmt->execute();
            $kart_id = $conn->lastInsertId();

            // Sonra kullanici_kart tablosuna ekle
            $stmt = $conn->prepare("
                INSERT INTO kullanici_kart (kart_id, kullanici_id)
                VALUES (:kart_id, :kullanici_id)
            ");
            
            $stmt->bindParam(':kart_id', $kart_id);
            $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
            $stmt->execute();

            $conn->commit();
            $success_message = "Kart başarıyla eklendi!";
            
            // Sayfayı yenile
            header("Location: kullanici-kartlar.php?success=1");
            exit();
        } catch(PDOException $e) {
            $conn->rollBack();
            $error_message = "Kart eklenirken bir hata oluştu: " . $e->getMessage();
        }
    }
}

// Kart silme işlemi
if (isset($_POST['delete_card']) && isset($_POST['kart_id'])) {
    try {
        $conn->beginTransaction();

        // Önce kullanici_kart tablosundan sil
        $stmt = $conn->prepare("DELETE FROM kullanici_kart WHERE kart_id = :kart_id AND kullanici_id = :kullanici_id");
        $stmt->bindParam(':kart_id', $_POST['kart_id']);
        $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
        $stmt->execute();

        // Sonra kart_bilgileri tablosundan sil
        $stmt = $conn->prepare("DELETE FROM kart_bilgileri WHERE kart_id = :kart_id");
        $stmt->bindParam(':kart_id', $_POST['kart_id']);
        $stmt->execute();

        $conn->commit();
        header("Location: kullanici-kartlar.php?success=2");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error_message = "Kart silinirken bir hata oluştu: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartlarım - GitmediGitmiyor</title>
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .cards-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }

        .card-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0 40px 0;
        }

        .card-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .card-item .card-name {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .card-item .card-name i {
            margin-right: 8px;
        }

        .card-item .card-details {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }

        .card-item .card-cvv {
            letter-spacing: 2px;
        }

        .card-item .card-number {
            font-size: 18px;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .card-item .card-expiry {
            color: #666;
            font-size: 14px;
        }

        .card-item .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 5px;
        }

        .card-item .delete-btn:hover {
            color: #bd2130;
        }

        .add-card-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        /* Edit Modal Styles */
        .edit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .edit-modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .edit-modal .modal-content {
            width: 100%;
            max-width: 500px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            position: relative;
            transform: translateY(-20px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .edit-modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .edit-modal .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            opacity: 0.7;
            z-index: 1001;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }

        .edit-modal .close:hover {
            opacity: 1;
        }

        .edit-modal h2 {
            margin-bottom: 20px;
            color: var(--text-color);
        }

        .edit-modal input[disabled] {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="cards-container">
        <h1>Kartlarım</h1>

        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Kart başarıyla eklendi!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == '2'): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Kart başarıyla silindi!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == '3'): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Kart başarıyla güncellendi!
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="card-list">
            <?php foreach ($kartlar as $kart): ?>
                <div class="card-item" onclick="openEditModal(<?php 
                    echo htmlspecialchars(json_encode([
                        'id' => $kart['kart_id'],
                        'name' => $kart['kart_adi'],
                        'number' => substr($kart['kart_no'], -4),
                        'expiry' => date('m/y', strtotime($kart['son_kullanma_tarihi']))
                    ])); 
                ?>)">
                    <form action="kullanici-kartlar.php" method="POST" style="display: inline;" onclick="event.stopPropagation();">
                        <input type="hidden" name="kart_id" value="<?php echo $kart['kart_id']; ?>">
                        <button type="submit" name="delete_card" class="delete-btn" onclick="return confirm('Bu kartı silmek istediğinizden emin misiniz?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    <div class="card-name">
                        <i class="fas fa-tag"></i>
                        <?php echo htmlspecialchars($kart['kart_adi']); ?>
                    </div>
                    <div class="card-number">
                        <i class="fas fa-credit-card"></i>
                        **** **** **** <?php echo substr($kart['kart_no'], -4); ?>
                    </div>
                    <div class="card-details">
                        <span class="card-expiry">
                            Son Kullanma: <?php echo date('m/y', strtotime($kart['son_kullanma_tarihi'])); ?>
                        </span>
                        <span class="card-cvv">
                            CVV: ***
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Edit Card Modal -->
        <div id="editCardModal" class="edit-modal">
            <div class="modal-content">
                <button type="button" class="close" onclick="closeEditModalImmediately()">&times;</button>
                <h2>Kartı Düzenle</h2>
                <form action="kullanici-kartlar.php" method="POST" id="editCardForm">
                    <input type="hidden" name="edit_card_id" id="editCardId">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_kart_adi">Kart Adı</label>
                            <input type="text" id="edit_kart_adi" name="edit_kart_adi" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_kart_no">Kart Numarası</label>
                            <input type="text" disabled id="edit_kart_no">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_son_kullanma_tarihi">Son Kullanma Tarihi</label>
                            <input type="text" id="edit_son_kullanma_tarihi" disabled>
                        </div>
                        <div class="form-group">
                            <label for="edit_cvv">CVV</label>
                            <input type="text" id="edit_cvv" value="***" disabled>
                        </div>
                    </div>
                    <button type="submit" class="form-btn">Değişiklikleri Kaydet</button>
                </form>
            </div>
        </div>

        <div class="add-card-form">
            <h2>Yeni Kart Ekle</h2>
            <form action="kullanici-kartlar.php" method="POST" id="cardForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="kart_adi">Kart Adı</label>
                        <input type="text" id="kart_adi" name="kart_adi" required placeholder="Örn: Banka Kartım">
                    </div>
                    <div class="form-group">
                        <label for="kart_no">Kart Numarası</label>
                        <input type="text" id="kart_no" name="kart_no" required maxlength="19" placeholder="1234 5678 9012 3456">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="son_kullanma_tarihi">Son Kullanma Tarihi</label>
                        <input type="text" id="son_kullanma_tarihi" name="son_kullanma_tarihi" required maxlength="5" placeholder="MM/YY">
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" required maxlength="3" placeholder="123">
                    </div>
                </div>
                <button type="submit" class="form-btn">Kartı Kaydet</button>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Kart numarası formatlama
        const kartNo = document.getElementById('kart_no');
        kartNo.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            value = value.replace(/(\d{4})/g, '$1 ').trim();
            e.target.value = value;
        });

        // Son kullanma tarihi formatlama
        const sonKullanma = document.getElementById('son_kullanma_tarihi');
        sonKullanma.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            if (value.length > 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            e.target.value = value;
        });

        // CVV formatlama
        const cvv = document.getElementById('cvv');
        cvv.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 3) value = value.slice(0, 3);
            e.target.value = value;
        });

        // Form doğrulama
        const cardForm = document.getElementById('cardForm');
        cardForm.addEventListener('submit', function(e) {
            const kartAdi = document.getElementById('kart_adi').value.trim();
            const kartNoValue = kartNo.value.replace(/\s/g, '');
            const sonKullanmaValue = sonKullanma.value;
            const cvvValue = cvv.value;

            if (kartAdi.length < 1) {
                e.preventDefault();
                alert('Lütfen kartınız için bir isim giriniz.');
                return;
            }

            if (kartNoValue.length !== 16) {
                e.preventDefault();
                alert('Lütfen geçerli bir kart numarası giriniz.');
                return;
            }

            if (!/^\d{2}\/\d{2}$/.test(sonKullanmaValue)) {
                e.preventDefault();
                alert('Lütfen geçerli bir son kullanma tarihi giriniz (AA/YY).');
                return;
            }

            if (cvvValue.length !== 3) {
                e.preventDefault();
                alert('Lütfen geçerli bir CVV giriniz.');
                return;
            }
        });
    });

    // Add these new functions
    function openEditModal(cardData) {
        const modal = document.getElementById('editCardModal');
        
        // Set form values
        document.getElementById('editCardId').value = cardData.id;
        document.getElementById('edit_kart_adi').value = cardData.name;
        document.getElementById('edit_kart_no').value = '**** **** **** ' + cardData.number;
        document.getElementById('edit_son_kullanma_tarihi').value = cardData.expiry;
        document.getElementById('edit_cvv').value = '***';

        // Show modal with animation
        modal.style.display = 'flex';
        requestAnimationFrame(() => {
            modal.classList.add('show');
        });
    }

    function closeEditModalImmediately() {
        const modal = document.getElementById('editCardModal');
        modal.style.display = 'none';
        modal.classList.remove('show');
    }

    // Update the click outside handler to use immediate close
    document.getElementById('editCardModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModalImmediately();
        }
    });

    // Prevent modal close when clicking modal content
    document.querySelector('#editCardModal .modal-content').addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Edit form validation
    const editCardForm = document.getElementById('editCardForm');
    if (editCardForm) {
        editCardForm.addEventListener('submit', function(e) {
            const kartAdi = document.getElementById('edit_kart_adi').value.trim();

            if (kartAdi.length < 1) {
                e.preventDefault();
                alert('Lütfen kartınız için bir isim giriniz.');
                return;
            }
        });
    }
    </script>
</body>
</html> 