<?php
require_once 'header.php';
?>

<div class="container mt-5 mb-5">
    <h1 class="text-center mb-5">İade Politikası</h1>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-4">İade ve Değişim Koşulları</h2>
                    
                    <div class="policy-section mb-4">
                        <h3 class="h5 mb-3">İade Süresi</h3>
                        <p>Ürünlerinizi teslim aldığınız tarihten itibaren 14 gün içerisinde iade edebilir veya değiştirebilirsiniz.</p>
                    </div>

                    <div class="policy-section mb-4">
                        <h3 class="h5 mb-3">İade Koşulları</h3>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Ürünün kullanılmamış olması</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Orijinal ambalajında olması</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Faturasının eksiksiz olması</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Ürünün tüm parçalarının eksiksiz olması</li>
                        </ul>
                    </div>

                    <div class="policy-section mb-4">
                        <h3 class="h5 mb-3">İade Süreci</h3>
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item">Hesabınızdan "Siparişlerim" bölümüne gidin</li>
                            <li class="list-group-item">İade etmek istediğiniz ürünü seçin</li>
                            <li class="list-group-item">İade nedenini belirtin</li>
                            <li class="list-group-item">Kargo firmasını seçin</li>
                            <li class="list-group-item">İade kargo etiketini yazdırın</li>
                            <li class="list-group-item">Ürünü kargo firmasına teslim edin</li>
                        </ol>
                    </div>

                    <div class="policy-section mb-4">
                        <h3 class="h5 mb-3">İade Ödemeleri</h3>
                        <p>İade işlemi onaylandıktan sonra ödemeniz, kullandığınız ödeme yöntemine göre aşağıdaki sürelerde iade edilecektir:</p>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-credit-card me-2"></i> Kredi Kartı: 3-7 iş günü</li>
                            <li class="mb-2"><i class="fas fa-university me-2"></i> Havale/EFT: 1-3 iş günü</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h3 class="h5 mb-3">İade Kargo Ücreti</h3>
                        <p>Ürün kaynaklı iadelerde kargo ücreti firmamız tarafından karşılanmaktadır. Müşteri kaynaklı iadelerde kargo ücreti müşteriye aittir.</p>
                    </div>
                </div>
            </div>

            <!-- İade Formu -->
            <div class="card">
                <div class="card-body">
                    <h2 class="h4 mb-4">İade Talebi Oluştur</h2>
                    <form action="" method="POST" class="return-form">
                        <div class="mb-3">
                            <label for="siparis_no" class="form-label">Sipariş Numarası</label>
                            <input type="text" class="form-control" id="siparis_no" name="siparis_no" required>
                        </div>
                        <div class="mb-3">
                            <label for="urun_kodu" class="form-label">Ürün Kodu</label>
                            <input type="text" class="form-control" id="urun_kodu" name="urun_kodu" required>
                        </div>
                        <div class="mb-3">
                            <label for="iade_nedeni" class="form-label">İade Nedeni</label>
                            <select class="form-select" id="iade_nedeni" name="iade_nedeni" required>
                                <option value="">Seçiniz</option>
                                <option value="yanlis_urun">Yanlış Ürün</option>
                                <option value="hasarli_urun">Hasarlı Ürün</option>
                                <option value="beklentiyi_karsilamadi">Beklentiyi Karşılamadı</option>
                                <option value="diger">Diğer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="aciklama" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="aciklama" name="aciklama" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">İade Talebi Oluştur</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.policy-section {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.policy-section h3 {
    color: #0d6efd;
}

.return-form {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
}

.list-group-item {
    background-color: #f8f9fa;
    border: none;
    margin-bottom: 5px;
    border-radius: 5px !important;
}
</style>

<?php require_once 'footer.php'; ?> 