<?php
session_start();
if($_SESSION['rol'] != 'docente') { 
    header("Location: ../login.php"); 
    exit(); 
}
require_once '../config/db.php';

$profesor_id = $_SESSION['user_id'];

// ==========================================
// EXPORTAR A EXCEL
// ==========================================
if(isset($_GET['export_excel'])) {
    $tipo = $_GET['tipo'] ?? 'calificaciones';
    $materia_id = $_GET['materia_id'] ?? 0;
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . date('Y-m-d_H-i-s') . '.xls"');
    
    echo '<html><head><meta charset="UTF-8"></head><body>';
    
    if($tipo == 'calificaciones' && $materia_id) {
        $stmt = $pdo->prepare("
            SELECT m.nombre as materia, g.nombre as grupo,
                   u.nombre, u.apellido, a.matricula,
                   c1.calificacion as u1, c2.calificacion as u2, c3.calificacion as u3,
                   c4.calificacion as u4, c5.calificacion as u5, c6.calificacion as u6
            FROM materias m
            JOIN grupo_materias gm ON m.id = gm.materia_id
            JOIN grupos g ON gm.grupo_id = g.id
            JOIN alumno_materias am ON m.id = am.materia_id AND g.id = am.grupo_id
            JOIN usuarios u ON am.alumno_id = u.id
            JOIN alumnos a ON u.id = a.id
            LEFT JOIN calificaciones c1 ON u.id = c1.alumno_id AND m.id = c1.materia_id AND c1.unidad = 1
            LEFT JOIN calificaciones c2 ON u.id = c2.alumno_id AND m.id = c2.materia_id AND c2.unidad = 2
            LEFT JOIN calificaciones c3 ON u.id = c3.alumno_id AND m.id = c3.materia_id AND c3.unidad = 3
            LEFT JOIN calificaciones c4 ON u.id = c4.alumno_id AND m.id = c4.materia_id AND c4.unidad = 4
            LEFT JOIN calificaciones c5 ON u.id = c5.alumno_id AND m.id = c5.materia_id AND c5.unidad = 5
            LEFT JOIN calificaciones c6 ON u.id = c6.alumno_id AND m.id = c6.materia_id AND c6.unidad = 6
            WHERE gm.maestro_id = ? AND m.id = ?
            ORDER BY u.nombre
        ");
        $stmt->execute([$profesor_id, $materia_id]);
        $datos = $stmt->fetchAll();
        
        echo '<h2>Reporte de Calificaciones</h2>';
        echo '<table border="1">';
        echo '<tr><th>Materia</th><th>Grupo</th><th>Alumno</th><th>Matrícula</th>
              <th>U1</th><th>U2</th><th>U3</th><th>U4</th><th>U5</th><th>U6</th>
              <th>Promedio</th><th>Estado</th></tr>';
        
        foreach($datos as $d) {
            $promedio = ($d['u1'] + $d['u2'] + $d['u3'] + $d['u4'] + $d['u5'] + $d['u6']) / 6;
            $estado = $promedio >= 6 ? 'APROBADO' : 'REPROBADO';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($d['materia']) . '</td>';
            echo '<td>' . htmlspecialchars($d['grupo']) . '</td>';
            echo '<td>' . htmlspecialchars($d['nombre'] . ' ' . $d['apellido']) . '</td>';
            echo '<td>' . $d['matricula'] . '</td>';
            echo '<td>' . ($d['u1'] ?? '-') . '</td>';
            echo '<td>' . ($d['u2'] ?? '-') . '</td>';
            echo '<td>' . ($d['u3'] ?? '-') . '</td>';
            echo '<td>' . ($d['u4'] ?? '-') . '</td>';
            echo '<td>' . ($d['u5'] ?? '-') . '</td>';
            echo '<td>' . ($d['u6'] ?? '-') . '</td>';
            echo '<td>' . number_format($promedio, 2) . '</td>';
            echo '<td>' . $estado . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    elseif($tipo == 'resumen') {
        echo '<h2>Reporte Resumen de Materias</h2>';
        echo '<table border="1">';
        echo '<tr><th>Materia</th><th>Grupo</th><th>Total Alumnos</th><th>Aprobados</th><th>Reprobados</th><th>Promedio Grupo</th></tr>';
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT m.id, m.nombre as materia, g.nombre as grupo
            FROM grupo_materias gm
            JOIN materias m ON gm.materia_id = m.id
            JOIN grupos g ON gm.grupo_id = g.id
            WHERE gm.maestro_id = ?
        ");
        $stmt->execute([$profesor_id]);
        $materias_prof = $stmt->fetchAll();
        
        foreach($materias_prof as $mat) {
            $stmt = $pdo->prepare("
                SELECT u.id,
                       COALESCE(c1.calificacion,0) + COALESCE(c2.calificacion,0) + 
                       COALESCE(c3.calificacion,0) + COALESCE(c4.calificacion,0) + 
                       COALESCE(c5.calificacion,0) + COALESCE(c6.calificacion,0) as suma
                FROM alumno_materias am
                JOIN usuarios u ON am.alumno_id = u.id
                LEFT JOIN calificaciones c1 ON u.id = c1.alumno_id AND am.materia_id = c1.materia_id AND c1.unidad = 1
                LEFT JOIN calificaciones c2 ON u.id = c2.alumno_id AND am.materia_id = c2.materia_id AND c2.unidad = 2
                LEFT JOIN calificaciones c3 ON u.id = c3.alumno_id AND am.materia_id = c3.materia_id AND c3.unidad = 3
                LEFT JOIN calificaciones c4 ON u.id = c4.alumno_id AND am.materia_id = c4.materia_id AND c4.unidad = 4
                LEFT JOIN calificaciones c5 ON u.id = c5.alumno_id AND am.materia_id = c5.materia_id AND c5.unidad = 5
                LEFT JOIN calificaciones c6 ON u.id = c6.alumno_id AND am.materia_id = c6.materia_id AND c6.unidad = 6
                WHERE am.materia_id = ? AND am.grupo_id = (SELECT grupo_id FROM grupo_materias WHERE materia_id = ? AND maestro_id = ? LIMIT 1)
            ");
            $stmt->execute([$mat['id'], $mat['id'], $profesor_id]);
            $alumnos_notas = $stmt->fetchAll();
            
            $total_alumnos = count($alumnos_notas);
            $aprobados = 0;
            $suma_promedios = 0;
            
            foreach($alumnos_notas as $a) {
                $promedio = $a['suma'] / 6;
                $suma_promedios += $promedio;
                if($promedio >= 6) $aprobados++;
            }
            $reprobados = $total_alumnos - $aprobados;
            $promedio_grupo = $total_alumnos > 0 ? $suma_promedios / $total_alumnos : 0;
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($mat['materia']) . '</td>';
            echo '<td>' . htmlspecialchars($mat['grupo']) . '</td>';
            echo '<td>' . $total_alumnos . '</td>';
            echo '<td>' . $aprobados . '</td>';
            echo '<td>' . $reprobados . '</td>';
            echo '<td>' . number_format($promedio_grupo, 2) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    echo '</body></html>';
    exit();
}

// Obtener materias del profesor
$materias = $pdo->prepare("
    SELECT DISTINCT gm.materia_id, m.nombre as materia_nombre, gm.grupo_id, g.nombre as grupo_nombre
    FROM grupo_materias gm
    JOIN materias m ON gm.materia_id = m.id
    JOIN grupos g ON gm.grupo_id = g.id
    WHERE gm.maestro_id = ?
    ORDER BY m.nombre
");
$materias->execute([$profesor_id]);
$materias = $materias->fetchAll();

// Estadísticas
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT am.alumno_id) as total_alumnos FROM grupo_materias gm JOIN alumno_materias am ON gm.materia_id = am.materia_id AND gm.grupo_id = am.grupo_id WHERE gm.maestro_id = ?");
$stmt->execute([$profesor_id]);
$total_alumnos = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT materia_id) as total_materias FROM grupo_materias WHERE maestro_id = ?");
$stmt->execute([$profesor_id]);
$total_materias = $stmt->fetchColumn();

// Calcular promedios
$promedio_general = 0;
$aprobados_total = 0;
$reprobados_total = 0;
$alumnos_con_notas = 0;

foreach($materias as $m) {
    $stmt = $pdo->prepare("
        SELECT u.id,
               COALESCE(c1.calificacion,0) + COALESCE(c2.calificacion,0) + 
               COALESCE(c3.calificacion,0) + COALESCE(c4.calificacion,0) + 
               COALESCE(c5.calificacion,0) + COALESCE(c6.calificacion,0) as suma
        FROM alumno_materias am
        JOIN usuarios u ON am.alumno_id = u.id
        LEFT JOIN calificaciones c1 ON u.id = c1.alumno_id AND am.materia_id = c1.materia_id AND c1.unidad = 1
        LEFT JOIN calificaciones c2 ON u.id = c2.alumno_id AND am.materia_id = c2.materia_id AND c2.unidad = 2
        LEFT JOIN calificaciones c3 ON u.id = c3.alumno_id AND am.materia_id = c3.materia_id AND c3.unidad = 3
        LEFT JOIN calificaciones c4 ON u.id = c4.alumno_id AND am.materia_id = c4.materia_id AND c4.unidad = 4
        LEFT JOIN calificaciones c5 ON u.id = c5.alumno_id AND am.materia_id = c5.materia_id AND c5.unidad = 5
        LEFT JOIN calificaciones c6 ON u.id = c6.alumno_id AND am.materia_id = c6.materia_id AND c6.unidad = 6
        WHERE am.materia_id = ? AND am.grupo_id = ?
    ");
    $stmt->execute([$m['materia_id'], $m['grupo_id']]);
    $notas = $stmt->fetchAll();
    
    foreach($notas as $n) {
        $prom = $n['suma'] / 6;
        $promedio_general += $prom;
        if($prom >= 6) $aprobados_total++;
        else $reprobados_total++;
        $alumnos_con_notas++;
    }
}
$promedio_general = $alumnos_con_notas > 0 ? $promedio_general / $alumnos_con_notas : 0;

$materia_seleccionada = $_GET['materia_id'] ?? ($materias[0]['materia_id'] ?? 0);
$calificaciones_detalle = [];

if($materia_seleccionada) {
    $stmt = $pdo->prepare("
        SELECT u.nombre, u.apellido, a.matricula,
               c1.calificacion as u1, c2.calificacion as u2, c3.calificacion as u3,
               c4.calificacion as u4, c5.calificacion as u5, c6.calificacion as u6
        FROM alumno_materias am
        JOIN usuarios u ON am.alumno_id = u.id
        JOIN alumnos a ON u.id = a.id
        LEFT JOIN calificaciones c1 ON u.id = c1.alumno_id AND am.materia_id = c1.materia_id AND c1.unidad = 1
        LEFT JOIN calificaciones c2 ON u.id = c2.alumno_id AND am.materia_id = c2.materia_id AND c2.unidad = 2
        LEFT JOIN calificaciones c3 ON u.id = c3.alumno_id AND am.materia_id = c3.materia_id AND c3.unidad = 3
        LEFT JOIN calificaciones c4 ON u.id = c4.alumno_id AND am.materia_id = c4.materia_id AND c4.unidad = 4
        LEFT JOIN calificaciones c5 ON u.id = c5.alumno_id AND am.materia_id = c5.materia_id AND c5.unidad = 5
        LEFT JOIN calificaciones c6 ON u.id = c6.alumno_id AND am.materia_id = c6.materia_id AND c6.unidad = 6
        WHERE am.materia_id = ? AND am.grupo_id = (SELECT grupo_id FROM grupo_materias WHERE materia_id = ? AND maestro_id = ? LIMIT 1)
        ORDER BY u.nombre
    ");
    $stmt->execute([$materia_seleccionada, $materia_seleccionada, $profesor_id]);
    $calificaciones_detalle = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reportes - Panel del Maestro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        .dashboard { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: white; border-right: 1px solid #e0e0e0; position: fixed; height: 100vh; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid #e0e0e0; }
        .logo-img { width: 100%; max-height: 60px; object-fit: contain; margin-bottom: 10px; }
        .sidebar-header h2 { color: #2c5f2d; font-size: 1rem; }
        .sidebar-header p { color: #6c757d; font-size: 0.7rem; }
        .sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; color: #404040; text-decoration: none; border-radius: 8px; margin-bottom: 2px; }
        .nav-item:hover { background: #f5f5f5; }
        .nav-item.active { background: #e8f5e9; color: #2c5f2d; }
        .sidebar-footer { padding: 16px; border-top: 1px solid #e0e0e0; }
        .user-info { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .main-content { margin-left: 280px; padding: 24px; width: 100%; }
        .user-profile-card { background: white; border-radius: 12px; padding: 15px 20px; margin-bottom: 24px; display: flex; align-items: center; flex-wrap: wrap; gap: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .user-profile-info { display: flex; align-items: center; gap: 15px; }
        .profile-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .profile-details p { margin: 2px 0; }
        .profile-details .name { font-weight: bold; font-size: 1.1rem; }
        .profile-details .role { color: #2c5f2d; font-size: 0.8rem; }
        .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; font-weight: bold; color: #2c5f2d; }
        .stat-label { color: #666; margin-top: 8px; }
        .btn-export { background: #2c5f2d; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 10px; }
        .btn-excel { background: #1e5620; }
        .btn-pdf { background: #c62828; }
        .btn-print { background: #1565c0; }
        .materia-selector { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .materia-btn { background: #e0e0e0; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; color: #333; }
        .materia-btn.active { background: #2c5f2d; color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f5f5f5; font-weight: 600; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-aprobado { background: #e8f5e9; color: #2e7d32; }
        .badge-reprobado { background: #ffebee; color: #c62828; }
        @media print { .sidebar, .btn-export, .user-profile-card, .materia-selector { display: none; } .main-content { margin-left: 0; } }
        @media (max-width: 768px) { .sidebar { width: 80px; } .sidebar-header h2, .sidebar-header p, .sidebar-nav span:last-child, .user-info div { display: none; } .logo-img { display: none; } .main-content { margin-left: 80px; } }
    </style>
</head>
<body>
<div class="dashboard">
    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">
        <div class="user-profile-card">
            <div class="user-profile-info">
                <img src="../assets/uploads/<?php echo $_SESSION['foto'] ?? 'default.jpg'; ?>" class="profile-avatar" onerror="this.src='../assets/uploads/default.jpg'">
                <div class="profile-details">
                    <p class="name"><?php echo $_SESSION['nombre']; ?></p>
                    <p class="role">Maestro</p>
                    <p><?php echo $_SESSION['email'] ?? ''; ?></p>
                </div>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card"><div class="stat-number"><?php echo $total_materias; ?></div><div class="stat-label">📚 Materias</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total_alumnos; ?></div><div class="stat-label">👨‍🎓 Total Alumnos</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo number_format($promedio_general, 1); ?></div><div class="stat-label">📊 Promedio General</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $aprobados_total; ?> / <?php echo $reprobados_total; ?></div><div class="stat-label">✅ Aprobados / ❌ Reprobados</div></div>
        </div>
        
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h2>📄 Reportes de Calificaciones</h2>
                <div>
                    <a href="?export_excel=1&tipo=resumen" class="btn-export btn-excel">📊 Exportar Resumen a Excel</a>
                    <button class="btn-export btn-pdf" onclick="window.print()">📄 Exportar a PDF</button>
                    <button class="btn-export btn-print" onclick="window.print()">🖨️ Imprimir</button>
                </div>
            </div>
            
            <div class="materia-selector">
                <?php foreach($materias as $m): ?>
                    <a href="?materia_id=<?php echo $m['materia_id']; ?>" class="materia-btn <?php echo $materia_seleccionada == $m['materia_id'] ? 'active' : ''; ?>">
                        📖 <?php echo $m['materia_nombre']; ?> (<?php echo $m['grupo_nombre']; ?>)
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if($materia_seleccionada && !empty($calificaciones_detalle)): 
                $nombre_materia = '';
                foreach($materias as $m) {
                    if($m['materia_id'] == $materia_seleccionada) {
                        $nombre_materia = $m['materia_nombre'];
                        $nombre_grupo = $m['grupo_nombre'];
                        break;
                    }
                }
            ?>
                <div style="margin-bottom: 15px;">
                    <a href="?export_excel=1&tipo=calificaciones&materia_id=<?php echo $materia_seleccionada; ?>" class="btn-export btn-excel" style="display: inline-block; padding: 8px 16px; font-size: 12px;">📊 Exportar esta materia a Excel</a>
                </div>
                <h3><?php echo $nombre_materia; ?> - Grupo <?php echo $nombre_grupo; ?></h3>
                <br>
                <div style="overflow-x: auto;">
                    <table>
                        <thead><tr><th>Alumno</th><th>Matrícula</th><th>U1</th><th>U2</th><th>U3</th><th>U4</th><th>U5</th><th>U6</th><th>Promedio</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach($calificaciones_detalle as $c): 
                                $promedio = ($c['u1'] + $c['u2'] + $c['u3'] + $c['u4'] + $c['u5'] + $c['u6']) / 6;
                                $estado = $promedio >= 6 ? 'APROBADO' : 'REPROBADO';
                            ?>
                            <tr>
                                <td><?php echo $c['nombre'] . ' ' . $c['apellido']; ?></td>
                                <td><?php echo $c['matricula']; ?></td>
                                <td><?php echo $c['u1'] ?? '-'; ?></td>
                                <td><?php echo $c['u2'] ?? '-'; ?></td>
                                <td><?php echo $c['u3'] ?? '-'; ?></td>
                                <td><?php echo $c['u4'] ?? '-'; ?></td>
                                <td><?php echo $c['u5'] ?? '-'; ?></td>
                                <td><?php echo $c['u6'] ?? '-'; ?></td>
                                <td><strong><?php echo number_format($promedio, 2); ?></strong></td>
                                <td><span class="badge <?php echo $estado == 'APROBADO' ? 'badge-aprobado' : 'badge-reprobado'; ?>"><?php echo $estado; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>Selecciona una materia para ver el reporte de calificaciones.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>📊 Resumen por Materia</h3>
            <br>
            <div style="overflow-x: auto;">
                <table>
                    <thead><tr><th>Materia</th><th>Grupo</th><th>Total Alumnos</th><th>Aprobados</th><th>Reprobados</th><th>Promedio Grupo</th></tr></thead>
                    <tbody>
                        <?php foreach($materias as $m): 
                            $stmt = $pdo->prepare("
                                SELECT u.id,
                                       COALESCE(c1.calificacion,0) + COALESCE(c2.calificacion,0) + 
                                       COALESCE(c3.calificacion,0) + COALESCE(c4.calificacion,0) + 
                                       COALESCE(c5.calificacion,0) + COALESCE(c6.calificacion,0) as suma
                                FROM alumno_materias am
                                JOIN usuarios u ON am.alumno_id = u.id
                                LEFT JOIN calificaciones c1 ON u.id = c1.alumno_id AND am.materia_id = c1.materia_id AND c1.unidad = 1
                                LEFT JOIN calificaciones c2 ON u.id = c2.alumno_id AND am.materia_id = c2.materia_id AND c2.unidad = 2
                                LEFT JOIN calificaciones c3 ON u.id = c3.alumno_id AND am.materia_id = c3.materia_id AND c3.unidad = 3
                                LEFT JOIN calificaciones c4 ON u.id = c4.alumno_id AND am.materia_id = c4.materia_id AND c4.unidad = 4
                                LEFT JOIN calificaciones c5 ON u.id = c5.alumno_id AND am.materia_id = c5.materia_id AND c5.unidad = 5
                                LEFT JOIN calificaciones c6 ON u.id = c6.alumno_id AND am.materia_id = c6.materia_id AND c6.unidad = 6
                                WHERE am.materia_id = ? AND am.grupo_id = ?
                            ");
                            $stmt->execute([$m['materia_id'], $m['grupo_id']]);
                            $notas = $stmt->fetchAll();
                            $total = count($notas);
                            $aprobados = 0;
                            $suma_promedios = 0;
                            foreach($notas as $n) {
                                $prom = $n['suma'] / 6;
                                $suma_promedios += $prom;
                                if($prom >= 6) $aprobados++;
                            }
                            $reprobados = $total - $aprobados;
                            $promedio_grupo = $total > 0 ? $suma_promedios / $total : 0;
                        ?>
                        <tr>
                            <td><?php echo $m['materia_nombre']; ?></td>
                            <td><?php echo $m['grupo_nombre']; ?></td>
                            <td><?php echo $total; ?></td>
                            <td><?php echo $aprobados; ?></td>
                            <td><?php echo $reprobados; ?></td>
                            <td><?php echo number_format($promedio_grupo, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>