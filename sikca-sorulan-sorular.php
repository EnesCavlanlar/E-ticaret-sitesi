<?php
require_once 'header.php';
?>

<div class="container mt-5 mb-5">
    <h1 class="text-center mb-5">Sıkça Sorulan Sorular</h1>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="accordion" id="faqAccordion">
                <!-- Sipariş ve Teslimat -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#siparis1">
                            Siparişim ne zaman elime ulaşır?
                        </button>
                    </h2>
                    <div id="siparis1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Siparişleriniz genellikle 1-3 iş günü içerisinde kargoya verilmektedir. Kargo firmasına teslim edildikten sonra 1-3 iş günü içerisinde adresinize teslim edilecektir.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#siparis2">
                            Siparişimi nasıl takip edebilirim?
                        </button>
                    </h2>
                    <div id="siparis2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Siparişiniz kargoya verildikten sonra size SMS ve e-posta ile bilgilendirme yapılacaktır. Kargo takip numarası ile siparişinizi takip edebilirsiniz.
                        </div>
                    </div>
                </div>

                <!-- Ödeme -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#odeme1">
                            Hangi ödeme yöntemlerini kullanabilirim?
                        </button>
                    </h2>
                    <div id="odeme1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Kredi kartı, banka kartı, havale/EFT ve kapıda ödeme seçeneklerini kullanabilirsiniz. Tüm ödemeleriniz güvenli ödeme altyapımız ile korunmaktadır.
                        </div>
                    </div>
                </div>

                <!-- İade ve Değişim -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#iade1">
                            Ürün iade ve değişim koşulları nelerdir?
                        </button>
                    </h2>
                    <div id="iade1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Ürünlerinizi teslim aldığınız tarihten itibaren 14 gün içerisinde iade edebilir veya değiştirebilirsiniz. Ürünün kullanılmamış ve orijinal ambalajında olması gerekmektedir.
                        </div>
                    </div>
                </div>

                <!-- Üyelik -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#uyelik1">
                            Üye olmadan alışveriş yapabilir miyim?
                        </button>
                    </h2>
                    <div id="uyelik1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Evet, üye olmadan da alışveriş yapabilirsiniz. Ancak üye olarak alışveriş yapmanız durumunda özel kampanyalardan ve indirimlerden faydalanabilirsiniz.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Soru Sorma Formu -->
            <div class="mt-5">
                <h3 class="mb-4">Sorunuzu Gönderin</h3>
                <form action="" method="POST" class="faq-form">
                    <div class="mb-3">
                        <label for="ad_soyad" class="form-label">Ad Soyad</label>
                        <input type="text" class="form-control" id="ad_soyad" name="ad_soyad" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta Adresi</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="soru" class="form-label">Sorunuz</label>
                        <textarea class="form-control" id="soru" name="soru" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gönder</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.accordion-item {
    border: none;
    margin-bottom: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.accordion-button {
    font-weight: 500;
    padding: 15px 20px;
}

.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #0d6efd;
}

.accordion-body {
    padding: 20px;
    background-color: #fff;
}

.faq-form {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
</style>

<?php require_once 'footer.php'; ?> 