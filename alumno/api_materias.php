<?php
session_start();
if($_SESSION['rol'] != 'alumno') { 
    echo json_encode([]); 
    exit(); 
}
require_once '../config/db.php';

// Obtener periodo activo
$periodo_activo = $pdo->query("SELECT id FROM periodos_inscripcion WHERE activo = 1 AND NOW() BETWEEN fecha_inicio AND fecha_fin LIMIT 1")->fetchColumn();

if(!$periodo_activo) {
    echo json_encode([]);
    exit();
}

$materias = $pdo->prepare("
    SELECT md.*, m.nombre 
    FROM materias_disponibles md
    JOIN materias m ON md.materia_id = m.id
    WHERE md.periodo_id = ? AND md.cupo_actual < md.cupo_maximo
");
$materias->execute([$periodo_activo]);
echo json_encode($materias->fetchAll());
?>