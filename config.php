<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$db   = 'foodmap_gis';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>