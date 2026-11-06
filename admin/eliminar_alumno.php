<?php
session_start();
if($_SESSION['rol'] != 'admin') { 
    header("Location: ../login.php"); 
    exit(); 
}
require_once '../config/db.php';

$id = $_GET['id'] ?? 0;
if($id > 0) {
    $pdo->prepare("DELETE FROM alumnos WHERE id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND rol = 'alumno'")->execute([$id]);
}
header("Location: gestion_alumnos.php");
exit();
?>