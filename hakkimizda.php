<?php
require_once 'header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row">
        <div class="col-12">
            <h1 class="text-center mb-5">Hakkımızda</h1>
            
            <div class="about-content mb-5">
                <h2 class="mb-4">GitmediGitmiyor E-Ticaret Platformu</h2>
                <p class="lead">
                    GitmediGitmiyor, müşterilerimize en iyi alışveriş deneyimini sunmak için tasarlanmış modern bir e-ticaret platformudur. 
                    Amacımız, güvenilir, hızlı ve kullanıcı dostu bir alışveriş ortamı sağlamaktır.
                </p>
            </div>

            <div class="team-section">
                <h2 class="text-center mb-4">Emeği Geçenler</h2>
                <div class="row justify-content-center">
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="team-member text-center">
                            <div class="member-image mb-3">
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                            </div>
                            <h4>Özkan Kaya</h4>
                            <p class="text-muted">Geliştirici</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="team-member text-center">
                            <div class="member-image mb-3">
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                            </div>
                            <h4>Yusuf Alper Yıldırım</h4>
                            <p class="text-muted">Geliştirici</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="team-member text-center">
                            <div class="member-image mb-3">
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                            </div>
                            <h4>Selim Kayra Aydın</h4>
                            <p class="text-muted">Geliştirici</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="team-member text-center">
                            <div class="member-image mb-3">
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                            </div>
                            <h4>Enes Cavlanlar</h4>
                            <p class="text-muted">Geliştirici</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.about-content {
    background-color: #f8f9fa;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}

.team-member {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.team-member:hover {
    transform: translateY(-5px);
}

.member-image {
    color: #007bff;
}

.team-member h4 {
    margin: 10px 0;
    color: #333;
}

.team-member p {
    margin-bottom: 0;
}
</style>

<?php require_once 'footer.php'; ?> 