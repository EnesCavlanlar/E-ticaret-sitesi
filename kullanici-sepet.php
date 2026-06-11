<?php
require_once 'includes/db_connection.php';
session_start();

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: index.php");
    exit();
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

// Kullanıcının sepetini getir veya oluştur
try {
    $stmt = $conn->prepare("SELECT sepet_id FROM sepet WHERE kullanici_id = :kullanici_id");
    $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
    $stmt->execute();
    $sepet = $stmt->fetch();

    if (!$sepet) {
        // Sepet yoksa yeni sepet oluştur
        $stmt = $conn->prepare("INSERT INTO sepet (kullanici_id) VALUES (:kullanici_id)");
        $stmt->bindParam(':kullanici_id', $_SESSION['kullanici_id']);
        $stmt->execute();
        $sepet_id = $conn->lastInsertId();
    } else {
        $sepet_id = $sepet['sepet_id'];
    }

    // Sepetteki ürünleri getir
    $stmt = $conn->prepare("
        SELECT 
            u.urun_id,
            u.urun_adi,
            u.aciklama,
            u.resim_url,
            us.urun_fiyat,
            us.stok_miktar,
            su.urun_miktar,
            s.satici_adi,
            s.satici_id
        FROM sepet_urun su
        JOIN urun u ON su.urun_id = u.urun_id
        JOIN urun_satici us ON u.urun_id = us.urun_id
        JOIN satici s ON us.satici_id = s.satici_id
        WHERE su.sepet_id = :sepet_id
        ORDER BY su.urun_id DESC
    ");
    $stmt->bindParam(':sepet_id', $sepet_id);
    $stmt->execute();
    $sepet_urunler = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Sepet yükleme hatası: " . $e->getMessage());
    $sepet_urunler = [];
}

// Toplam tutarı hesapla
$toplam_tutar = 0;
foreach ($sepet_urunler as $urun) {
    $toplam_tutar += $urun['urun_fiyat'] * $urun['urun_miktar'];
}

// Kargo ücreti hesaplama
$kargo_ucreti = $toplam_tutar >= 300 ? 0 : 59.99;
$genel_toplam = $toplam_tutar + $kargo_ucreti;

// Sipariş oluşturma işlemi
if (isset($_POST['create_order']) && isset($_POST['selected_card_id'])) {
    try {
        // Sipariş tamamlama işlemini includes/siparis-tamamla.php'ye yönlendir
        $response = file_get_contents("includes/siparis-tamamla.php", false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'kart_id' => $_POST['selected_card_id']
                ])
            ]
        ]));

        $result = json_decode($response, true);

        if ($result['success']) {
            // Başarılı sipariş sayfasına yönlendir
            header("Location: siparis-basarili.php?order_id=" . $result['siparis_id']);
            exit();
        } else {
            $error_message = $result['message'];
        }
    } catch(Exception $e) {
        $error_message = "Sipariş oluşturulurken bir hata oluştu: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sepetim - GitmediGitmiyor</title>
    <link rel="stylesheet" href="css/gitmedigitmiyor-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .cart-title {
            font-size: 24px;
            color: var(--text-color);
        }

        .cart-summary {
            font-size: 18px;
            color: var(--text-color);
        }

        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }

        .cart-item {
            display: flex;
            background: var(--white);
            border-radius: 8px;
            padding: 15px;
            box-shadow: var(--shadow-sm);
            gap: 20px;
        }

        .cart-item-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .cart-item-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .cart-item-seller {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .cart-item-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            background: var(--primary-color);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
            color: #fff;
        }

        .quantity-btn:hover {
            background: rgba(0, 70, 190, 0.8);
        }

        .quantity-value {
            font-size: 16px;
            color: var(--text-color);
            min-width: 40px;
            text-align: center;
        }

        .remove-btn {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .remove-btn:hover {
            background: var(--danger-dark);
        }

        .cart-summary-box {
            background: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 16px;
            color: var(--text-color);
        }

        .summary-total {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }

        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 20px;
        }

        .checkout-btn:hover {
            background: rgba(0, 70, 190, 0.8);
        }

        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }

        .empty-cart i {
            font-size: 48px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .empty-cart p {
            font-size: 18px;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .continue-shopping {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .continue-shopping:hover {
            background: var(--primary-dark);
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

        /* Loading state styles */
        .quantity-btn.loading,
        .remove-btn.loading {
            opacity: 0.7;
            cursor: not-allowed;
            position: relative;
        }

        .quantity-btn.loading::after,
        .remove-btn.loading::after {
            content: '';
            position: absolute;
            width: 12px;
            height: 12px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .quantity-btn:disabled,
        .remove-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
            }

            .cart-item-image {
                width: 100%;
                height: 200px;
            }

            .cart-item-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .quantity-controls {
                justify-content: center;
            }
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 700;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid var(--border-color);
        }

        .free-shipping-info {
            background: #e8f4ff;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--primary-color);
        }

        .free-shipping-info i {
            font-size: 16px;
        }

        /* Card Selection Styles */
        .payment-cards {
            margin-top: 20px;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }

        .payment-cards h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .card-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }

        .card-option {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-option:hover {
            border-color: var(--primary-color);
            background: #fff;
        }

        .card-option.selected {
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-option i {
            color: var(--primary-color);
            font-size: 20px;
        }

        .card-details {
            flex: 1;
        }

        .card-name {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 4px;
        }

        .card-number {
            font-size: 14px;
            color: var(--text-muted);
        }

        .add-card-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            padding: 8px 0;
        }

        .add-card-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            // ... existing media queries ...
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!-- Notification element -->
    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i>
        <span id="notification-message">İşlem başarıyla gerçekleşti!</span>
    </div>

    <div class="cart-container">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?= $_SESSION['error_message'] ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="cart-header">
            <h1 class="cart-title">Sepetim</h1>
            <div class="cart-summary">
                <?= count($sepet_urunler) ?> Ürün
            </div>
        </div>

        <?php if (empty($sepet_urunler)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Sepetiniz boş</p>
                <a href="index.php" class="continue-shopping">Alışverişe Devam Et</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($sepet_urunler as $urun): ?>
                    <div class="cart-item" data-urun-id="<?= $urun['urun_id'] ?>">
                        <div class="cart-item-image">
                            <img src="<?= $urun['resim_url'] ?? 'images/placeholder.jpg' ?>" 
                                 alt="<?= htmlspecialchars($urun['urun_adi']) ?>"
                                 onerror="this.src='images/placeholder.jpg'">
                        </div>
                        <div class="cart-item-details">
                            <div>
                                <h3 class="cart-item-title"><?= htmlspecialchars($urun['urun_adi']) ?></h3>
                                <p class="cart-item-seller">Satıcı: <?= htmlspecialchars($urun['satici_adi']) ?></p>
                            </div>
                            <div class="cart-item-actions">
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="updateQuantity(<?= $urun['urun_id'] ?>, 'decrease')">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="quantity-value"><?= $urun['urun_miktar'] ?></span>
                                    <button class="quantity-btn" onclick="updateQuantity(<?= $urun['urun_id'] ?>, 'increase')">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div class="cart-item-price" data-unit-price="<?= $urun['urun_fiyat'] ?>">
                                    ₺<?= number_format($urun['urun_fiyat'] * $urun['urun_miktar'], 2, ',', '.') ?>
                                </div>
                                <button class="remove-btn" onclick="removeFromCart(<?= $urun['urun_id'] ?>)">
                                    <i class="fas fa-trash"></i> Kaldır
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary-box">
                <div class="summary-row">
                    <span>Ara Toplam</span>
                    <span>₺<?= number_format($toplam_tutar, 2, ',', '.') ?></span>
                </div>
                <div class="summary-row">
                    <span>Kargo Ücreti</span>
                    <span><?= $kargo_ucreti > 0 ? '₺' . number_format($kargo_ucreti, 2, ',', '.') : 'Ücretsiz' ?></span>
                </div>
                <?php if ($kargo_ucreti > 0 && $toplam_tutar > 0): ?>
                    <div class="free-shipping-info">
                        <i class="fas fa-truck"></i>
                        <span>₺<?= number_format(300 - $toplam_tutar, 2, ',', '.') ?> daha alışveriş yapın, kargo bedava!</span>
                    </div>
                <?php endif; ?>
                <div class="summary-row summary-total">
                    <span>Toplam</span>
                    <span>₺<?= number_format($genel_toplam, 2, ',', '.') ?></span>
                </div>

                <!-- Kart Seçim Bölümü -->
                <div class="payment-cards">
                    <h3>Ödeme Yöntemi</h3>
                    <div class="card-options">
                        <?php if (empty($kartlar)): ?>
                            <p>Henüz kayıtlı kartınız bulunmamaktadır.</p>
                        <?php else: ?>
                            <?php foreach ($kartlar as $kart): ?>
                                <div class="card-option" data-card-id="<?= $kart['kart_id'] ?>" onclick="selectCard(this)">
                                    <i class="fas fa-credit-card"></i>
                                    <div class="card-details">
                                        <div class="card-name"><?= htmlspecialchars($kart['kart_adi']) ?></div>
                                        <div class="card-number">**** **** **** <?= substr($kart['kart_no'], -4) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <a href="kullanici-kartlar.php" class="add-card-link">
                        <i class="fas fa-plus-circle"></i>
                        <?= empty($kartlar) ? 'Kart Ekle' : 'Başka Bir Kart Ekle' ?>
                    </a>
                </div>

                <form id="orderForm" method="POST" action="includes/siparis-tamamla.php">
                    <input type="hidden" name="selected_card_id" id="selectedCardId">
                    <button type="submit" class="checkout-btn" onclick="return validateOrder()">
                        Siparişi Tamamla
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    function showNotification(message) {
        const notification = document.getElementById('notification');
        const messageElement = document.getElementById('notification-message');
        messageElement.textContent = message;
        notification.classList.add('show');
        
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    function updateCartUI(urunId, newQuantity, action) {
        const cartItem = document.querySelector(`.cart-item[data-urun-id="${urunId}"]`);
        if (!cartItem) return;

        if (action === 'remove' || newQuantity === 0) {
            cartItem.remove();
            // Check if cart is empty and refresh if needed
            if (document.querySelectorAll('.cart-item').length === 0) {
                location.reload();
                return;
            }
        } else {
            // Update quantity display
            const quantityElement = cartItem.querySelector('.quantity-value');
            if (quantityElement) {
                quantityElement.textContent = newQuantity;
            }

            // Update price
            const priceElement = cartItem.querySelector('.cart-item-price');
            const unitPrice = parseFloat(priceElement.getAttribute('data-unit-price'));
            if (priceElement && unitPrice) {
                const newPrice = (unitPrice * newQuantity).toFixed(2);
                priceElement.textContent = `₺${newPrice.replace('.', ',')}`;
            }
        }

        // Update total price
        updateTotalPrice();
    }

    function updateTotalPrice() {
        let total = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const price = parseFloat(item.querySelector('.cart-item-price').textContent.replace('₺', '').replace(',', '.'));
            total += price;
        });
        
        const totalElement = document.querySelector('.summary-total span:last-child');
        if (totalElement) {
            totalElement.textContent = `₺${total.toFixed(2).replace('.', ',')}`;
        }
        
        const subtotalElement = document.querySelector('.summary-row:first-child span:last-child');
        if (subtotalElement) {
            subtotalElement.textContent = `₺${total.toFixed(2).replace('.', ',')}`;
        }
    }

    function updateQuantity(urunId, action) {
        const cartItem = document.querySelector(`.cart-item[data-urun-id="${urunId}"]`);
        if (!cartItem) return;

        const quantityBtn = cartItem.querySelector(`button[onclick*="${action}"]`);
        if (quantityBtn) {
            quantityBtn.disabled = true;
            quantityBtn.classList.add('loading');
        }

        fetch('includes/sepet-guncelle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `urun_id=${urunId}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currentQuantity = parseInt(cartItem.querySelector('.quantity-value').textContent);
                const newQuantity = action === 'increase' ? currentQuantity + 1 : currentQuantity - 1;
                updateCartUI(urunId, newQuantity, action);
                showNotification('Sepet güncellendi');
            } else {
                showNotification(data.message || 'Bir hata oluştu');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Bir hata oluştu');
        })
        .finally(() => {
            if (quantityBtn) {
                quantityBtn.disabled = false;
                quantityBtn.classList.remove('loading');
            }
        });
    }

    function removeFromCart(urunId) {
        if (confirm('Bu ürünü sepetten kaldırmak istediğinize emin misiniz?')) {
            const cartItem = document.querySelector(`.cart-item[data-urun-id="${urunId}"]`);
            if (!cartItem) return;

            const removeBtn = cartItem.querySelector('.remove-btn');
            if (removeBtn) {
                removeBtn.disabled = true;
                removeBtn.classList.add('loading');
            }

            fetch('includes/sepet-guncelle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `urun_id=${urunId}&action=remove`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartUI(urunId, 0, 'remove');
                    showNotification('Ürün sepetten kaldırıldı');
                } else {
                    showNotification(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Bir hata oluştu');
            })
            .finally(() => {
                if (removeBtn) {
                    removeBtn.disabled = false;
                    removeBtn.classList.remove('loading');
                }
            });
        }
    }

    // Kart seçimi için JavaScript fonksiyonları
    function selectCard(element) {
        // Önceki seçili kartı kaldır
        document.querySelectorAll('.card-option').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Yeni kartı seç
        element.classList.add('selected');
        
        // Seçili kart ID'sini form'a ekle
        document.getElementById('selectedCardId').value = element.getAttribute('data-card-id');
    }

    function validateOrder() {
        const selectedCard = document.querySelector('.card-option.selected');
        if (!selectedCard) {
            alert('Lütfen bir ödeme yöntemi seçin.');
            return false;
        }
        return true;
    }
    </script>
</body>
</html>