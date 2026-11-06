<?php
session_start();
if($_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])) {
    $id = $_POST['id'];
    
    // Validar archivo
    $archivo = $_FILES['foto'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];
    
    if(!in_array($extension, $tipos_permitidos)) {
        header("Location: gestion_usuarios.php?msg=error_foto");
        exit();
    }
    
    // Generar nombre único
    $nuevo_nombre = time() . '_' . $id . '.' . $extension;
    $ruta_destino = '../assets/uploads/' . $nuevo_nombre;
    
    // Crear carpeta si no existe
    if(!is_dir('../assets/uploads')) {
        mkdir('../assets/uploads', 0777, true);
    }
    
    // Subir archivo
    if(move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        // Actualizar base de datos
        $stmt = $pdo->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
        $stmt->execute([$nuevo_nombre, $id]);
        header("Location: gestion_usuarios.php?msg=foto");
    } else {
        header("Location: gestion_usuarios.php?msg=error_foto");
    }
} else {
    header("Location: gestion_usuarios.php");
}
exit();
?>