<?php
//db
$host = 'localhost';
$db   = 'booknest';
$user = 'root';
$pass = '';

//php data objects. objected-oriented interface per l'accesso ai diversi db
//utilizza i prepared statements per aumentare la sicurezza
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}