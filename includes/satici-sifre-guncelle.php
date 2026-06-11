<?php
require_once 'db_connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['satici_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['current_password']) || !isset($data['new_password']) || !isset($data['confirm_password'])) {
    echo json_encode(['success' => false, 'message' => 'Tüm alanları doldurun.']);
    exit;
}

if ($data['new_password'] !== $data['confirm_password']) {
    echo json_encode(['success' => false, 'message' => 'Yeni şifreler eşleşmiyor.']);
    exit;
}

try {
    // Mevcut şifreyi kontrol et
    $stmt = $conn->prepare("SELECT satici_sifre FROM satici WHERE satici_id = :id");
    $stmt->bindParam(':id', $_SESSION['satici_id']);
    $stmt->execute();
    $seller = $stmt->fetch();

    if (!$seller || $seller['satici_sifre'] !== $data['current_password']) {
        echo json_encode(['success' => false, 'message' => 'Mevcut şifre yanlış.']);
        exit;
    }

    // Eğer sadece doğrulama istendiyse
    if (isset($_GET['validate'])) {
        echo json_encode(['valid' => true]);
        exit;
    }

    // Yeni şifreyi güncelle
    $stmt = $conn->prepare("UPDATE satici SET satici_sifre = :yeni_sifre WHERE satici_id = :id");
    $stmt->bindParam(':yeni_sifre', $data['new_password']);
    $stmt->bindParam(':id', $_SESSION['satici_id']);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Şifreniz başarıyla güncellendi.']);
} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen tekrar deneyin.']);
} 