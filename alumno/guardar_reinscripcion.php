<?php
session_start();
if($_SESSION['rol'] != 'alumno') { 
    echo json_encode(['error' => 'No autorizado']); 
    exit(); 
}
require_once '../config/db.php';

// Obtener el ID del alumno desde la sesión
$id_usuario = $_SESSION['user_id'];

// Obtener el alumno_id desde la tabla alumnos
$stmt = $pdo->prepare("SELECT id FROM alumnos WHERE id = ?");
$stmt->execute([$id_usuario]);
$alumno = $stmt->fetch();

if(!$alumno) {
    echo json_encode(['error' => 'No se encontró información del alumno']);
    exit();
}

$alumno_id = $alumno['id'];
$materias = json_decode($_POST['materias'], true);

// Obtener periodo activo
$periodo_activo = $pdo->query("SELECT id FROM periodos_inscripcion WHERE activo = 1 AND NOW() BETWEEN fecha_inicio AND fecha_fin LIMIT 1")->fetchColumn();

if(!$periodo_activo) {
    echo json_encode(['error' => 'No hay periodo de reinscripción activo']);
    exit();
}

try {
    // Verificar si ya existe una reinscripción
    $stmt = $pdo->prepare("SELECT id FROM reinscripciones WHERE alumno_id = ? AND periodo_id = ?");
    $stmt->execute([$alumno_id, $periodo_activo]);
    $reinscripcion = $stmt->fetch();
    
    if($reinscripcion) {
        // Eliminar detalles anteriores
        $pdo->prepare("DELETE FROM reinscripciones_detalle WHERE reinscripcion_id = ?")->execute([$reinscripcion['id']]);
        $reinscripcion_id = $reinscripcion['id'];
    } else {
        // Crear nueva reinscripción
        $stmt = $pdo->prepare("INSERT INTO reinscripciones (alumno_id, periodo_id, monto, estado) VALUES (?, ?, 850.00, 'pendiente')");
        $stmt->execute([$alumno_id, $periodo_activo]);
        $reinscripcion_id = $pdo->lastInsertId();
    }
    
    // Guardar materias seleccionadas
    foreach($materias as $materia_disponible_id) {
        // Verificar cupo
        $cupo = $pdo->prepare("SELECT cupo_actual, cupo_maximo FROM materias_disponibles WHERE id = ?");
        $cupo->execute([$materia_disponible_id]);
        $datos = $cupo->fetch();
        
        if($datos && $datos['cupo_actual'] < $datos['cupo_maximo']) {
            // Insertar detalle
            $stmt = $pdo->prepare("INSERT INTO reinscripciones_detalle (reinscripcion_id, materia_disponible_id) VALUES (?, ?)");
            $stmt->execute([$reinscripcion_id, $materia_disponible_id]);
            
            // Actualizar cupo
            $pdo->prepare("UPDATE materias_disponibles SET cupo_actual = cupo_actual + 1 WHERE id = ?")->execute([$materia_disponible_id]);
        }
    }
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>