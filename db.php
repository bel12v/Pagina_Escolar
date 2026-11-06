<?php
$host = 'localhost';
$dbname = 'pagina_escolar';
$user = 'root';
$pass = '';

try { 
    // 1. Creamos la conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. LE DECIMOS A LA API QUE LA CONEXIÓN SE LLAMA $conn
    $conn = $pdo; 

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>