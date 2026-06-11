<?php
require_once 'header.php';

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = '<div class="alert alert-success">Geri bildiriminiz başarıyla iletildi. En kısa sürede size dönüş yapacağız.</div>';
}
?>

<div class="container mt-5 mb-5">
    <div class="row">
        <div class="col-md-6">
            <h2>İletişim Bilgileri</h2>
            <div class="contact-info mt-4">
                <p><i class="fas fa-phone"></i> <strong>Telefon:</strong> <a href="tel:+902121234567">+90 212 123 45 67</a></p>
                <p><i class="fas fa-envelope"></i> <strong>E-posta:</strong> <a href="mailto:info@gitmedigitmiyor.com">info@gitmedigitmiyor.com</a></p>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Adres:</strong> E-Ticaret Cad. No:123, İstanbul</p>
            </div>
            
            <div class="working-hours mt-4">
                <h4>Çalışma Saatleri</h4>
                <p>Pazartesi - Cuma: 09:00 - 18:00</p>
                <p>Cumartesi: 10:00 - 16:00</p>
                <p>Pazar: Kapalı</p>
            </div>
        </div>
        
        <div class="col-md-6">
            <h2>Bize Ulaşın</h2>
            <?php echo $message; ?>
            
            <form action="" method="POST" class="contact-form mt-4">
                <div class="form-group mb-3">
                    <label for="ad_soyad">Ad Soyad *</label>
                    <input type="text" class="form-control" id="ad_soyad" name="ad_soyad" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="email">E-posta Adresi *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="konu">Konu *</label>
                    <input type="text" class="form-control" id="konu" name="konu" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="mesaj">Mesajınız *</label>
                    <textarea class="form-control" id="mesaj" name="mesaj" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Gönder</button>
            </form>
        </div>
    </div>
</div>

<style>
.contact-info p {
    margin-bottom: 15px;
    font-size: 16px;
}

.contact-info i {
    width: 25px;
    color: #007bff;
}

.working-hours {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
}

.working-hours h4 {
    color: #333;
    margin-bottom: 15px;
}

.contact-form {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.contact-form label {
    font-weight: 500;
}

.contact-form .btn-primary {
    padding: 10px 30px;
}

.alert {
    margin-bottom: 20px;
}
</style>

<?php require_once 'footer.php'; ?> 