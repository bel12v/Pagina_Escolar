<?php
session_start();
if($_SESSION['rol'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once '../config/db.php';

$id = $_GET['id'] ?? 0;
if($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($usuario);
} else {
    echo json_encode(['error' => 'ID no válido']);
}
?>