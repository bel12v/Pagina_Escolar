<?php
$host = 'localhost';
$dbname = 'pagina_escolar';
$user = 'root';
$pass = 'root';

try { 
    // 1. Creamos la conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Asignamos la conexión a la variable $conn que usarán nuestros modelos
    $conn = $pdo; 
    
    // 3. Iniciamos sesión si no está activa
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>