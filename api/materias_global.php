<?php
// api/materias_global.php
// Consulta global: une las materias (MySQL) con su oferta (MongoDB) por clave.
// Correspondencia: materias.clave (MySQL) = clave_materia (MongoDB).

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../db.php';                 // conexión MySQL ($conn)
require_once __DIR__ . '/../models/materia.php';     // modelo existente (Fuente A)
require_once __DIR__ . '/../models/OfertaMongo.php'; // Fuente B

$estado = 'completo';
$fuentesFallidas = [];

// Fuente A: MySQL, con el modelo que ya usa la API.
$materias = (new Materia($conn))->obtenerTodas();

// Fuente B: MongoDB. Si no responde, el resultado queda "parcial".
try {
    $oferta = (new OfertaMongo())->obtenerTodas();
} catch (Exception $e) {
    $oferta = null;   // null = no se sabe (nunca 0)
    $estado = 'parcial';
    $fuentesFallidas[] = ['fuente' => 'mongodb', 'error' => $e->getMessage()];
}

$resultado = [];

// 1. Cada materia de MySQL, con su oferta de MongoDB si existe.
foreach ($materias as $m) {
    $o = $oferta[$m['clave']] ?? null;
    $resultado[] = [
        'clave'           => $m['clave'],
        'nombre'          => $m['nombre'],
        'creditos'        => $m['creditos'],
        'periodo'         => $o['periodo'] ?? null,
        'cupo'            => $o['cupo'] ?? null,
        'inscritos'       => $o['inscritos'] ?? null,
        'correspondencia' => $oferta === null ? 'no_verificable' : ($o ? 'completa' : 'sin_correspondencia'),
    ];
}

// 2. Ofertas de MongoDB cuya clave no existe en MySQL.
$clavesMysql = array_column($materias, 'clave');
foreach ($oferta ?? [] as $clave => $o) {
    if (!in_array($clave, $clavesMysql, true)) {
        $resultado[] = [
            'clave' => $clave, 'nombre' => null, 'creditos' => null,
            'periodo' => $o['periodo'], 'cupo' => $o['cupo'], 'inscritos' => $o['inscritos'],
            'correspondencia' => 'sin_correspondencia',
        ];
    }
}

echo json_encode([
    'estado'           => $estado,
    'fuentes_fallidas' => $fuentesFallidas,
    'data'             => $resultado,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
