<?php
session_start();
if($_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];
    
    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, rol = ?, estado = ? WHERE id = ?");
    $stmt->execute([$nombre, $apellido, $email, $telefono, $rol, $estado, $id]);
    
    header("Location: gestion_usuarios.php?msg=editado");
    exit();
}
?>