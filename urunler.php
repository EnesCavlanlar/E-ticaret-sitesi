<?php
// Bu dosya ürün kartı template'ini ve stillerini içerir
?>

<style>
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .product-card {
        background: var(--white);
        border-radius: 8px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
    }

    .product-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .product-link:hover {
        text-decoration: none;
        color: inherit;
    }

    .product-image {
        position: relative;
        padding-top: 75%;
        overflow: hidden;
    }

    .product-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-info {
        padding: 15px;
    }

    .product-category {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 5px;
    }

    .product-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-color);
        margin-top: 10px;
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 24px;
        line-height: 1.1;
    }

    .product-description {
        font-size: 14px;
        color: var(--text-muted);
        margin-top: 0;
        margin-bottom: 0px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 32px;
        line-height: 1.1;
    }

    .product-rating {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
    }

    .stars {
        color: #ffd700;
        font-size: 14px;
    }

    .rating-count {
        color: var(--text-muted);
        font-size: 12px;
    }

    .product-price {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 10px;
    }

    .product-seller {
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 10px;
    }

    .product-stock {
        font-size: 13px;
        color: var(--text-success);
        margin-bottom: 15px;
    }

    .stock-low {
        color: var(--text-warning);
    }

    .stock-out {
        color: var(--text-danger);
    }

    .product-actions {
        display: flex;
        justify-content: center;
        padding: 0 15px;
    }

    .btn-primary {
        width: 100%;
        max-width: 200px;
        padding: 8px;
        font-size: 14px;
        text-align: center;
        border-radius: 4px;
        background: var(--primary-color);
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
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
        color: #ff4b4b;
    }

    .favorite-btn:hover {
        transform: scale(1.1);
    }

    .favorite-btn:hover i {
        color: #ff4b4b;
    }

    @media (max-width: 768px) {
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
    }
</style>

<?php
function renderProductCard($urun, $favori_urunler = []) {
    ?>
    <div class="product-card">
        <?php if (isset($_SESSION['kullanici_id'])): ?>
            <button class="favorite-btn <?= in_array($urun['urun_id'], $favori_urunler) ? 'active' : '' ?>" 
                    onclick="toggleFavorite(this, <?= $urun['urun_id'] ?>)">
                <i class="fas fa-heart"></i>
            </button>
        <?php endif; ?>
        <a href="urun-detay.php?id=<?= $urun['urun_id'] ?>" class="product-link">
            <div class="product-image">
                <img src="<?= $urun['resim_url'] ?? 'images/placeholder.jpg' ?>" 
                     alt="<?= htmlspecialchars($urun['urun_adi']) ?>"
                     onerror="this.src='images/placeholder.jpg'">
            </div>
            <div class="product-info">
                <div class="product-category">
                    <?= htmlspecialchars($urun['kategori_adi'] ?? 'Genel') ?>
                </div>
                <h3 class="product-title">
                    <?= htmlspecialchars($urun['urun_adi']) ?>
                </h3>
                <div class="product-description">
                    <?= htmlspecialchars($urun['aciklama']) ?>
                </div>
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
                        (<?= $urun['degerlendirme_sayisi'] ?> değerlendirme)
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
                        <button class="btn-primary" onclick="event.preventDefault(); event.stopPropagation(); sepeteEkle(<?= $urun['urun_id'] ?>)">
                            Sepete Ekle
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </a>
    </div>
    <?php
}
?> 