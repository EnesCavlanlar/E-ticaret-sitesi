<?php
require_once 'header.php';
?>

<div class="container mt-5 mb-5">
    <h1 class="text-center mb-5">Blog</h1>
    
    <div class="row">
        <!-- Blog Yazıları -->
        <div class="col-md-8">
            <div class="blog-post mb-4">
                <img src="https://via.placeholder.com/800x400" class="img-fluid rounded mb-3" alt="Blog Görseli">
                <h2>E-Ticaret Trendleri 2024</h2>
                <p class="text-muted">Yayın Tarihi: 15 Mart 2024</p>
                <p class="lead">2024 yılında e-ticaret dünyasında öne çıkan trendler ve yenilikler hakkında detaylı bir inceleme.</p>
                <a href="#" class="btn btn-primary">Devamını Oku</a>
            </div>

            <div class="blog-post mb-4">
                <img src="https://via.placeholder.com/800x400" class="img-fluid rounded mb-3" alt="Blog Görseli">
                <h2>Online Alışverişte Güvenlik</h2>
                <p class="text-muted">Yayın Tarihi: 10 Mart 2024</p>
                <p class="lead">Online alışveriş yaparken dikkat edilmesi gereken güvenlik önlemleri ve ipuçları.</p>
                <a href="#" class="btn btn-primary">Devamını Oku</a>
            </div>

            <div class="blog-post mb-4">
                <img src="https://via.placeholder.com/800x400" class="img-fluid rounded mb-3" alt="Blog Görseli">
                <h2>Mobil Alışveriş Deneyimi</h2>
                <p class="text-muted">Yayın Tarihi: 5 Mart 2024</p>
                <p class="lead">Mobil cihazlardan alışveriş yaparken daha iyi bir deneyim için öneriler.</p>
                <a href="#" class="btn btn-primary">Devamını Oku</a>
            </div>
        </div>

        <!-- Yan Panel -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Kategoriler</h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none">E-Ticaret</a></li>
                        <li><a href="#" class="text-decoration-none">Teknoloji</a></li>
                        <li><a href="#" class="text-decoration-none">Alışveriş İpuçları</a></li>
                        <li><a href="#" class="text-decoration-none">Güvenlik</a></li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Popüler Yazılar</h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-decoration-none">En İyi Alışveriş Siteleri</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none">Kampanya Takip Etme Rehberi</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none">Online Ödeme Yöntemleri</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.blog-post {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.blog-post h2 {
    font-size: 24px;
    margin-bottom: 10px;
}

.blog-post .lead {
    margin-bottom: 20px;
}

.card {
    border: none;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: none;
}

.card-header h4 {
    margin: 0;
    font-size: 18px;
}
</style>

<?php require_once 'footer.php'; ?> 