<?php
require_once 'header.php';
?>

<div class="container mt-5 mb-5">
    <h1 class="text-center mb-5">Kargo Takip</h1>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="" method="POST" class="tracking-form">
                        <div class="mb-4">
                            <label for="tracking_number" class="form-label">Kargo Takip Numarası</label>
                            <input type="text" class="form-control form-control-lg" id="tracking_number" name="tracking_number" placeholder="Kargo takip numaranızı giriniz" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Sorgula</button>
                    </form>
                </div>
            </div>

            <!-- Kargo Durumu Örneği -->
            <div class="tracking-result mt-4" style="display: none;">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Kargo Durumu</h5>
                    </div>
                    <div class="card-body">
                        <div class="tracking-info">
                            <p><strong>Kargo Firması:</strong> <span id="cargo-company">-</span></p>
                            <p><strong>Takip Numarası:</strong> <span id="tracking-number">-</span></p>
                            <p><strong>Gönderim Tarihi:</strong> <span id="shipping-date">-</span></p>
                            <p><strong>Durum:</strong> <span id="status">-</span></p>
                        </div>
                        
                        <div class="tracking-timeline mt-4">
                            <div class="timeline-item">
                                <div class="timeline-point"></div>
                                <div class="timeline-content">
                                    <h6>Kargo Teslim Alındı</h6>
                                    <p class="text-muted">15 Mart 2024, 10:30</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-point"></div>
                                <div class="timeline-content">
                                    <h6>Dağıtım Merkezine Ulaştı</h6>
                                    <p class="text-muted">15 Mart 2024, 14:45</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-point"></div>
                                <div class="timeline-content">
                                    <h6>Yolda</h6>
                                    <p class="text-muted">16 Mart 2024, 09:15</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kargo Firmaları -->
            <div class="cargo-companies mt-5">
                <h3 class="mb-4">Anlaşmalı Kargo Firmalarımız</h3>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <img src="https://via.placeholder.com/150" alt="Kargo Firması" class="img-fluid mb-3">
                                <h5>Yurtiçi Kargo</h5>
                                <a href="https://www.yurticikargo.com" target="_blank" class="btn btn-outline-primary">Takip Et</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <img src="https://via.placeholder.com/150" alt="Kargo Firması" class="img-fluid mb-3">
                                <h5>Aras Kargo</h5>
                                <a href="https://www.araskargo.com.tr" target="_blank" class="btn btn-outline-primary">Takip Et</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <img src="https://via.placeholder.com/150" alt="Kargo Firması" class="img-fluid mb-3">
                                <h5>MNG Kargo</h5>
                                <a href="https://www.mngkargo.com.tr" target="_blank" class="btn btn-outline-primary">Takip Et</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.tracking-form {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
}

.tracking-timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-point {
    position: absolute;
    left: -30px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #0d6efd;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #0d6efd;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -21px;
    top: 20px;
    width: 2px;
    height: calc(100% - 20px);
    background-color: #0d6efd;
}

.timeline-content {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.timeline-content h6 {
    margin-bottom: 5px;
}

.cargo-companies .card {
    transition: transform 0.3s ease;
}

.cargo-companies .card:hover {
    transform: translateY(-5px);
}
</style>

<?php require_once 'footer.php'; ?> 