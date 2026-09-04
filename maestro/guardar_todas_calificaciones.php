<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['rol']) || $_SESSION['rol'] != 'docente') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

require_once '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if(!isset($data['materia_id']) || !isset($data['calificaciones'])) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit();
    }
    
    $materia_id = $data['materia_id'];
    $calificaciones = $data['calificaciones'];
    $success = true;
    $error_msg = '';
    
    try {
        $pdo->beginTransaction();
        
        foreach($calificaciones as $cal) {
            $alumno_id = $cal['alumno_id'];
            $calificacion = floatval($cal['calificacion']);
            
            if($calificacion < 0 || $calificacion > 100) {
                throw new Exception("Calificación fuera de rango para alumno ID: $alumno_id");
            }
            
            // Verificar si ya existe
            $stmt = $pdo->prepare("SELECT id FROM calificaciones WHERE alumno_id = ? AND materia_id = ?");
            $stmt->execute([$alumno_id, $materia_id]);
            
            if($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE calificaciones SET calificacion = ? WHERE alumno_id = ? AND materia_id = ?");
                $stmt->execute([$calificacion, $alumno_id, $materia_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO calificaciones (alumno_id, materia_id, calificacion) VALUES (?, ?, ?)");
                $stmt->execute([$alumno_id, $materia_id, $calificacion]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>