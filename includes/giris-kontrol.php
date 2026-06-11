<?php
require_once 'db_connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $giris_turu = $_POST['giris_turu'];

    if ($giris_turu == 'kullanici') {
        $eposta = $_POST['kullanici_eposta'];
        $sifre = $_POST['kullanici_sifre'];

        try {
            $stmt = $conn->prepare("SELECT * FROM kullanici WHERE kullanici_eposta = :eposta");
            $stmt->bindParam(':eposta', $eposta);
            $stmt->execute();
            $kullanici = $stmt->fetch();

            if ($kullanici && $sifre == $kullanici['kullanici_sifre']) {
                $_SESSION['kullanici_id'] = $kullanici['kullanici_id'];
                $_SESSION['kullanici_adi'] = $kullanici['kullanici_adi'];
                header("Location: ../index.php");
                exit();
            } else {
                header("Location: ../index.php?error=invalid_credentials&giris_turu=kullanici");
                exit();
            }
        } catch(PDOException $e) {
            header("Location: ../index.php?error=database_error&giris_turu=kullanici");
            exit();
        }
    } elseif ($giris_turu == 'satici') {
        $eposta = $_POST['satici_eposta'];
        $sifre = $_POST['satici_sifre'];

        try {
            $stmt = $conn->prepare("SELECT * FROM satici WHERE satici_eposta = :eposta");
            $stmt->bindParam(':eposta', $eposta);
            $stmt->execute();
            $satici = $stmt->fetch();

            if ($satici && $sifre == $satici['satici_sifre']) {
                $_SESSION['satici_id'] = $satici['satici_id'];
                $_SESSION['satici_adi'] = $satici['satici_adi'];
                header("Location: ../satici-panel.php");
                exit();
            } else {
                header("Location: ../index.php?error=invalid_credentials&giris_turu=satici");
                exit();
            }
        } catch(PDOException $e) {
            header("Location: ../index.php?error=database_error&giris_turu=satici");
            exit();
        }
    }
} else {
    header("Location: ../index.php");
    exit();
}
?> 