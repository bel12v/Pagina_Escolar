<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['rol']) || $_SESSION['rol'] != 'docente') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

require_once '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $alumno_id = $_POST['alumno_id'] ?? 0;
    $materia_id = $_POST['materia_id'] ?? 0;
    $calificacion = floatval($_POST['calificacion'] ?? 0);
    
    if($alumno_id <= 0 || $materia_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit();
    }
    
    if($calificacion < 0 || $calificacion > 100) {
        echo json_encode(['success' => false, 'error' => 'Calificación fuera de rango']);
        exit();
    }
    
    try {
        // Verificar si ya existe calificación
        $stmt = $pdo->prepare("SELECT id FROM calificaciones WHERE alumno_id = ? AND materia_id = ?");
        $stmt->execute([$alumno_id, $materia_id]);
        
        if($stmt->rowCount() > 0) {
            // Actualizar
            $stmt = $pdo->prepare("UPDATE calificaciones SET calificacion = ? WHERE alumno_id = ? AND materia_id = ?");
            $result = $stmt->execute([$calificacion, $alumno_id, $materia_id]);
        } else {
            // Insertar
            $stmt = $pdo->prepare("INSERT INTO calificaciones (alumno_id, materia_id, calificacion) VALUES (?, ?, ?)");
            $result = $stmt->execute([$alumno_id, $materia_id, $calificacion]);
        }
        
        if($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al guardar en la base de datos']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>