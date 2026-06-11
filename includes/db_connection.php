<?php
$host = "localhost";
$dbname = "eticaret";
$username = "root";
$password = "";

try {
    error_log("Attempting database connection to $dbname at $host");
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    error_log("Database connection successful");

    $test = $conn->query("SELECT 1");
    if ($test) {
        error_log("Database connection test query successful");
    }

} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    error_log("Error code: " . $e->getCode());
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>
