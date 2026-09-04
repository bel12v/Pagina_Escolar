<?php
session_start();
if($_SESSION['rol'] != 'alumno') { 
    header("Location: ../login.php"); 
    exit(); 
}
require_once '../config/db.php';

$alumno_id = $_SESSION['user_id'];

// Obtener información del alumno
$stmt = $pdo->prepare("
    SELECT u.*, a.matricula, a.grado, a.grupo_id
    FROM usuarios u 
    LEFT JOIN alumnos a ON u.id = a.id 
    WHERE u.id = ?
");
$stmt->execute([$alumno_id]);
$alumno = $stmt->fetch();

// Obtener materias del alumno con calificaciones
$stmt = $pdo->prepare("
    SELECT m.id, m.nombre, m.clave, g.nombre as grupo,
           c1.calificacion as u1, c2.calificacion as u2, c3.calificacion as u3,
           c4.calificacion as u4, c5.calificacion as u5, c6.calificacion as u6
    FROM alumno_materias am
    JOIN materias m ON am.materia_id = m.id
    JOIN grupos g ON am.grupo_id = g.id
    LEFT JOIN calificaciones c1 ON am.alumno_id = c1.alumno_id AND m.id = c1.materia_id AND c1.unidad = 1
    LEFT JOIN calificaciones c2 ON am.alumno_id = c2.alumno_id AND m.id = c2.materia_id AND c2.unidad = 2
    LEFT JOIN calificaciones c3 ON am.alumno_id = c3.alumno_id AND m.id = c3.materia_id AND c3.unidad = 3
    LEFT JOIN calificaciones c4 ON am.alumno_id = c4.alumno_id AND m.id = c4.materia_id AND c4.unidad = 4
    LEFT JOIN calificaciones c5 ON am.alumno_id = c5.alumno_id AND m.id = c5.materia_id AND c5.unidad = 5
    LEFT JOIN calificaciones c6 ON am.alumno_id = c6.alumno_id AND m.id = c6.materia_id AND c6.unidad = 6
    WHERE am.alumno_id = ?
    ORDER BY m.nombre
");
$stmt->execute([$alumno_id]);
$materias = $stmt->fetchAll();

// Obtener horario del alumno
$stmt = $pdo->prepare("
    SELECT m.nombre as materia, gm.dia, gm.hora_inicio, gm.hora_fin, gm.salon,
           CONCAT(ma.nombre, ' ', ma.apellido) as maestro
    FROM alumno_materias am
    JOIN grupo_materias gm ON am.materia_id = gm.materia_id AND am.grupo_id = gm.grupo_id
    JOIN materias m ON gm.materia_id = m.id
    JOIN usuarios ma ON gm.maestro_id = ma.id
    WHERE am.alumno_id = ?
    ORDER BY FIELD(gm.dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'), gm.hora_inicio
");
$stmt->execute([$alumno_id]);
$horario = $stmt->fetchAll();

// Obtener reinscripción
$stmt = $pdo->prepare("
    SELECT r.*, COUNT(rd.id) as total_materias
    FROM reinscripciones r
    LEFT JOIN reinscripciones_detalle rd ON r.id = rd.reinscripcion_id
    WHERE r.alumno_id = ?
    GROUP BY r.id
    ORDER BY r.id DESC LIMIT 1
");
$stmt->execute([$alumno_id]);
$reinscripcion = $stmt->fetch();

// Calcular promedio general
$promedio_general = 0;
$total_materias = count($materias);
foreach($materias as $m) {
    $suma = ($m['u1'] ?? 0) + ($m['u2'] ?? 0) + ($m['u3'] ?? 0) + 
            ($m['u4'] ?? 0) + ($m['u5'] ?? 0) + ($m['u6'] ?? 0);
    $promedio_general += $suma / 6;
}
$promedio_general = $total_materias > 0 ? $promedio_general / $total_materias : 0;

$pagina = $_GET['pagina'] ?? 'inicio';
$monto_reinscripcion = 2500.00;

// Formatear fecha de nacimiento
$fecha_nacimiento = !empty($alumno['fecha_nacimiento']) ? date('d/m/Y', strtotime($alumno['fecha_nacimiento'])) : 'No registrada';
$fecha_registro = date('d/m/Y', strtotime($alumno['fecha_registro']));
$bloqueado_hasta = !empty($alumno['bloqueado_hasta']) ? date('d/m/Y H:i', strtotime($alumno['bloqueado_hasta'])) : 'No bloqueado';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Panel Alumno - COBACH BC</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }
        .dashboard { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid #e0e0e0;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        .logo-img {
            width: 100%;
            max-height: 60px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .sidebar-header h2 { color: #2c5f2d; font-size: 1rem; margin-top: 5px; }
        .sidebar-header p { color: #6c757d; font-size: 0.7rem; }
        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #404040;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.3s ease;
        }
        .nav-item:hover { background: #f5f5f5; transform: translateX(5px); }
        .nav-item.active { background: #e8f5e9; color: #2c5f2d; }
        .nav-item span:first-child { font-size: 20px; }
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid #e0e0e0;
        }
        .user-info { display: flex; align-items: center; gap: 12px; }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 24px;
            width: 100%;
        }
        
        /* Tarjeta de perfil */
        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 24px;
            display: flex;
            gap: 30px;
            align-items: flex-start;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #2c5f2d;
        }
        .profile-info {
            flex: 1;
        }
        .profile-info h2 {
            color: #2c5f2d;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .profile-info .grado {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 10px;
        }
        .info-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 3px solid #2c5f2d;
        }
        .info-item strong {
            display: block;
            color: #2c5f2d;
            font-size: 11px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .info-item span {
            color: #333;
            font-size: 13px;
        }
        .badge-estado {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        /* Stats cards */
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            text-align: center;
            flex: 1;
            min-width: 150px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-number { font-size: 32px; font-weight: bold; color: #2c5f2d; }
        .stat-label { color: #666; margin-top: 8px; font-size: 14px; }
        
        /* Tabla */
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }
        .card h3 {
            color: #2c5f2d;
            margin-bottom: 15px;
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f5f5f5;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-aprobado { background: #e8f5e9; color: #2e7d32; }
        .badge-reprobado { background: #ffebee; color: #c62828; }
        .badge-pagado { background: #e8f5e9; color: #2e7d32; }
        
        .logout-btn {
            background: #ffebee;
            color: #c62828;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .cerrar-sesion {
            text-align: right;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h2, .sidebar-header p, .sidebar-nav span:last-child, .user-info div { display: none; }
            .logo-img { display: none; }
            .main-content { margin-left: 80px; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.jpeg" alt="COBACH BC" class="logo-img" onerror="this.src='https://placehold.co/200x60/2c5f2d/white?text=COBACH+BC'">
            <p>Alumno</p>
        </div>
        <nav class="sidebar-nav">
            <a href="?pagina=inicio" class="nav-item <?php echo $pagina == 'inicio' ? 'active' : ''; ?>">
                <span>🏠</span> <span>Inicio</span>
            </a>
            <a href="?pagina=horario" class="nav-item <?php echo $pagina == 'horario' ? 'active' : ''; ?>">
                <span>📅</span> <span>Horario</span>
            </a>
            <a href="?pagina=calificaciones" class="nav-item <?php echo $pagina == 'calificaciones' ? 'active' : ''; ?>">
                <span>📊</span> <span>Calificaciones</span>
            </a>
            <a href="?pagina=recibos" class="nav-item <?php echo $pagina == 'recibos' ? 'active' : ''; ?>">
                <span>🧾</span> <span>Recibos</span>
            </a>
            <a href="?pagina=reinscripcion" class="nav-item <?php echo $pagina == 'reinscripcion' ? 'active' : ''; ?>">
                <span>💰</span> <span>Reinscripción</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <img src="../assets/uploads/<?php echo $_SESSION['foto'] ?? 'default.jpg'; ?>" class="user-avatar" onerror="this.src='../assets/uploads/default.jpg'">
                <div>
                    <p><?php echo $_SESSION['nombre']; ?></p>
                    <a href="../logout.php" style="color:#f44336;">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Botón cerrar sesión arriba a la derecha -->
        <div class="cerrar-sesion">
            <a href="../logout.php" class="logout-btn">🚪 Cerrar Sesión</a>
        </div>
        
        <!-- PÁGINA DE INICIO -->
        <?php if($pagina == 'inicio'): ?>
            <!-- Perfil con foto e información completa -->
            <div class="profile-card">
                <img src="../assets/uploads/<?php echo $alumno['foto'] ?? 'default.jpg'; ?>" class="profile-photo" onerror="this.src='../assets/uploads/default.jpg'">
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($alumno['nombre'] . ' ' . ($alumno['apellido'] ?? '')); ?></h2>
                    <div class="grado">
                        ID: <?php echo $alumno['id']; ?> | 
                        Grado: <?php echo $alumno['grado'] ?? 'No asignado'; ?> | 
                        Grupo: <?php echo $alumno['grupo_id'] ?? 'No asignado'; ?>
                    </div>
                    <div class="info-grid">
                        <div class="info-item"><strong>📧 Correo</strong><span><?php echo htmlspecialchars($alumno['email']); ?></span></div>
                        <div class="info-item"><strong>📱 Teléfono</strong><span><?php echo $alumno['telefono'] ? htmlspecialchars($alumno['telefono']) : 'No registrado'; ?></span></div>
                        <div class="info-item"><strong>🔢 Matrícula</strong><span><?php echo $alumno['matricula'] ?? 'No asignada'; ?></span></div>
                        <div class="info-item"><strong>🎂 Fecha Nacimiento</strong><span><?php echo $fecha_nacimiento; ?></span></div>
                        <div class="info-item"><strong>📅 Registro</strong><span><?php echo $fecha_registro; ?></span></div>
                        <div class="info-item"><strong>👤 Rol</strong><span><?php echo ucfirst($alumno['rol']); ?></span></div>
                        <div class="info-item"><strong>📊 Estado</strong><span class="badge-estado"><?php echo $alumno['estado'] == 'vigente' ? '✅ Activo' : '❌ Inactivo'; ?></span></div>
                        <div class="info-item"><strong>🔐 Intentos fallidos</strong><span><?php echo $alumno['intentos_fallidos'] ?? 0; ?></span></div>
                        <div class="info-item"><strong>⏰ Bloqueado hasta</strong><span><?php echo $bloqueado_hasta; ?></span></div>
                    </div>
                </div>
            </div>
            
            <!-- Stats: Materias cursando, promedio -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($materias); ?></div>
                    <div class="stat-label">📚 Materias Cursando</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($promedio_general, 1); ?></div>
                    <div class="stat-label">📊 Promedio General</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">✅</div>
                    <div class="stat-label">Vigente</div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- PÁGINA DE HORARIO -->
        <?php if($pagina == 'horario'): ?>
            <div class="card">
                <h3>📅 Mi Horario de Clases</h3>
                <?php if(empty($horario)): ?>
                    <p style="text-align: center; padding: 20px;">No hay horario asignado aún</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr><th>Día</th><th>Horario</th><th>Materia</th><th>Maestro</th><th>Salón</th></thead>
                            <tbody>
                                <?php foreach($horario as $h): ?>
                                <tr>
                                    <td><?php echo $h['dia']; ?></td>
                                    <td><?php echo substr($h['hora_inicio'], 0, 5) . ' - ' . substr($h['hora_fin'], 0, 5); ?></td>
                                    <td><?php echo htmlspecialchars($h['materia']); ?></td>
                                    <td><?php echo htmlspecialchars($h['maestro']); ?></td>
                                    <td><?php echo $h['salon'] ?? '---'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- PÁGINA DE CALIFICACIONES -->
        <?php if($pagina == 'calificaciones'): ?>
            <div class="card">
                <h3>📊 Calificaciones Detalladas</h3>
                <?php if(empty($materias)): ?>
                    <p style="text-align: center; padding: 20px;">No hay calificaciones registradas aún</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Materia</th><th>Grupo</th><th>U1</th><th>U2</th><th>U3</th>
                                    <th>U4</th><th>U5</th><th>U6</th><th>Promedio</th><th>Estado</th>
                                </thead>
                                <tbody>
                                    <?php foreach($materias as $m): 
                                        $u1 = $m['u1'] ?? '-';
                                        $u2 = $m['u2'] ?? '-';
                                        $u3 = $m['u3'] ?? '-';
                                        $u4 = $m['u4'] ?? '-';
                                        $u5 = $m['u5'] ?? '-';
                                        $u6 = $m['u6'] ?? '-';
                                        
                                        $suma = 0;
                                        $count = 0;
                                        foreach([$u1, $u2, $u3, $u4, $u5, $u6] as $nota) {
                                            if(is_numeric($nota)) {
                                                $suma += $nota;
                                                $count++;
                                            }
                                        }
                                        $promedio = $count > 0 ? $suma / 6 : 0;
                                        $estado = $promedio >= 6 ? 'APROBADO' : 'REPROBADO';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($m['nombre']); ?></td>
                                        <td><?php echo $m['grupo']; ?></td>
                                        <td><?php echo is_numeric($u1) ? number_format($u1, 1) : '-'; ?></td>
                                        <td><?php echo is_numeric($u2) ? number_format($u2, 1) : '-'; ?></td>
                                        <td><?php echo is_numeric($u3) ? number_format($u3, 1) : '-'; ?></td>
                                        <td><?php echo is_numeric($u4) ? number_format($u4, 1) : '-'; ?></td>
                                        <td><?php echo is_numeric($u5) ? number_format($u5, 1) : '-'; ?></td>
                                        <td><?php echo is_numeric($u6) ? number_format($u6, 1) : '-'; ?></td>
                                        <td><strong><?php echo number_format($promedio, 2); ?></strong></td>
                                        <td><span class="badge <?php echo $estado == 'APROBADO' ? 'badge-aprobado' : 'badge-reprobado'; ?>"><?php echo $estado; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f5f5f5;">
                                        <td colspan="8"><strong>Promedio General:</strong></td>
                                        <td><strong><?php echo number_format($promedio_general, 2); ?></strong></td>
                                        <td><span class="badge <?php echo $promedio_general >= 6 ? 'badge-aprobado' : 'badge-reprobado'; ?>"><?php echo $promedio_general >= 6 ? 'APROBADO' : 'REPROBADO'; ?></span></td>
                                    </tr>
                                </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- PÁGINA DE RECIBOS -->
        <?php if($pagina == 'recibos'): ?>
            <div class="card">
                <h3>🧾 Recibos de Pago</h3>
                <?php if($reinscripcion): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead><tr><th>Descripción</th><th>Emisión</th><th>Vigencia</th><th>Importe</th><th>Estado</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td>APORTACION VOLUNTARIA REINSCRIPCION</td>
                                    <td><?php echo date('d/m/Y', strtotime($reinscripcion['fecha_solicitud'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime('+30 days', strtotime($reinscripcion['fecha_solicitud']))); ?></td>
                                    <td>$<?php echo number_format($reinscripcion['monto'], 2); ?></td>
                                    <td><span class="badge badge-pagado">CUBIERTO</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; padding: 20px;">No hay recibos registrados</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- PÁGINA DE REINSCRIPCIÓN -->
        <?php if($pagina == 'reinscripcion'): ?>
            <div class="card">
                <h3>💰 Reinscripción <?php echo date('Y'); ?>-<?php echo date('Y')+1; ?></h3>
                <div style="text-align: center;">
                    <?php if($reinscripcion): ?>
                        <div style="background: #e8f5e9; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                            <div style="font-size: 48px; margin-bottom: 10px;">✅</div>
                            <h3 style="color: #2e7d32;">¡Reinscripción Completada!</h3>
                            <p>Materias inscritas: <?php echo $reinscripcion['total_materias']; ?></p>
                            <p>Monto total: $<?php echo number_format($reinscripcion['monto'], 2); ?></p>
                        </div>
                    <?php else: ?>
                        <div style="background: #fff3e0; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                            <p>No hay una reinscripción activa para el periodo actual</p>
                            <p style="font-size: 24px; font-weight: bold; color: #f57c00;">$<?php echo number_format($monto_reinscripcion, 2); ?></p>
                            <p style="margin-top: 15px;">Acude al departamento de control escolar para realizar tu reinscripción</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>