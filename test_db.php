<?php
require_once 'config/db.php';
echo "<h1>Prueba de conexión</h1>";
echo "<p>✅ Conexión exitosa a la base de datos</p>";

// Verificar usuarios
$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
$total = $stmt->fetchColumn();
echo "<p>Total de usuarios: " . $total . "</p>";
?>