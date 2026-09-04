<?php
$host = 'localhost';
$dbname = 'cobach_bc';
$user = 'root';
$pass = '';

try { 
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>