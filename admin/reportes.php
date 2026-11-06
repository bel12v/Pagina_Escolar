<?php
session_start();
if($_SESSION['rol'] != 'admin') { header("Location: ../login.php"); exit(); }
require_once '../config/db.php';

// ==========================================
// EXPORTAR A EXCEL
// ==========================================
if(isset($_GET['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_usuarios_' . date('Y-m-d_H-i-s') . '.xls"');
    
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    echo '<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Teléfono</th><th>Rol</th><th>Estado</th><th>Fecha Registro</th></tr>';
    
    $usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY id");
    foreach($usuarios as $u) {
        echo '<tr>';
        echo '<td>' . $u['id'] . '</td>';
        echo '<td>' . htmlspecialchars($u['nombre']) . '</td>';
        echo '<td>' . htmlspecialchars($u['apellido'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($u['email']) . '</td>';
        echo '<td>' . ($u['telefono'] ?? '') . '</td>';
        echo '<td>' . $u['rol'] . '</td>';
        echo '<td>' . $u['estado'] . '</td>';
        echo '<td>' . $u['fecha_registro'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</body></html>';
    exit();
}

// ==========================================
// VISTA PRINCIPAL
// ==========================================
$total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_admin = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'")->fetchColumn();
$total_docentes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'docente'")->fetchColumn();
$total_alumnos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'alumno'")->fetchColumn();
$total_activos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'vigente'")->fetchColumn();
$total_inactivos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'inactivo'")->fetchColumn();

$reinscripciones_pendientes = $pdo->query("SELECT COUNT(*) FROM reinscripciones WHERE estado = 'pendiente'")->fetchColumn();
$reinscripciones_pagadas = $pdo->query("SELECT COUNT(*) FROM reinscripciones WHERE estado = 'pagado'")->fetchColumn();

$ultimas_reinscripciones = $pdo->query("
    SELECT r.*, u.nombre, u.apellido, u.email 
    FROM reinscripciones r
    JOIN usuarios u ON r.alumno_id = u.id
    ORDER BY r.fecha_solicitud DESC LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reportes - COBACH BC</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        .dashboard { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: white; border-right: 1px solid #e0e0e0; position: fixed; height: 100vh; }
        .sidebar-header { padding: 24px; text-align: center; border-bottom: 1px solid #e0e0e0; }
        .logo-img { height: 50px; width: auto; margin-bottom: 10px; }
        .sidebar-header h2 { color: #2c5f2d; font-size: 1.25rem; }
        .sidebar-header p { color: #6c757d; font-size: 0.75rem; }
        .sidebar-nav { padding: 16px 12px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #404040; text-decoration: none; border-radius: 8px; }
        .nav-item:hover { background: #f5f5f5; }
        .nav-item.active { background: #e8f5e9; color: #2c5f2d; }
        .sidebar-footer { padding: 16px; border-top: 1px solid #e0e0e0; position: absolute; bottom: 0; width: 100%; }
        .user-info { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .main-content { margin-left: 280px; padding: 24px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px; }
        .top-bar h1 { font-size: 1.5rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; font-weight: bold; color: #2c5f2d; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn-export { background: #2c5f2d; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 10px; }
        .btn-excel { background: #1e5620; }
        .btn-pdf { background: #c62828; }
        .btn-print { background: #1565c0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f5f5f5; font-weight: 600; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-pendiente { background: #fff3e0; color: #f57c00; }
        .badge-pagado { background: #e8f5e9; color: #2e7d32; }
        @media print {
            .sidebar, .top-bar div, .sidebar-footer, .btn-export { display: none; }
            .main-content { margin-left: 0; padding: 0; }
            .card { box-shadow: none; border: 1px solid #ddd; page-break-inside: avoid; }
            body { background: white; }
        }
        @media (max-width: 768px) { .sidebar { width: 80px; } .sidebar-header h2, .sidebar-header p, .sidebar-nav span:last-child, .user-info div { display: none; } .logo-img { display: none; } .main-content { margin-left: 80px; } }
    </style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.jpeg" alt="COBACH BC" class="logo-img" onerror="this.src='https://placehold.co/50x50/2c5f2d/white?text=COBACH'">
            <p>Administrador</p>
        </div>
        <nav class="sidebar-nav">
    <a href="index.php" class="nav-item">🏠 Inicio</a>
    <a href="crear_usuario.php" class="nav-item">➕ Crear Usuario</a>
    <a href="gestion_usuarios.php" class="nav-item">👥 Usuarios</a>
    <a href="gestion_alumnos.php" class="nav-item">👨‍🎓 Alumnos</a>
    <a href="gestion_maestros.php" class="nav-item">👨‍🏫 Maestros</a>
    <a href="materias.php" class="nav-item">📚 Materias</a>
    <a href="grupos.php" class="nav-item">👥 Grupos</a>
    <a href="asignar_materias.php" class="nav-item">📋 Asignar Materias</a>
    <a href="inscribir_alumnos.php" class="nav-item">✏️ Inscribir Alumnos</a>
    <a href="horario_alumno.php" class="nav-item">📅 Horario Alumnos</a>
    <a href="reinscripciones.php" class="nav-item">💰 Reinscripciones</a>
    <a href="reportes.php" class="nav-item active">📄 Reportes</a>
</nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <img src="../assets/uploads/<?php echo $_SESSION['foto'] ?? 'default.jpg'; ?>" class="user-avatar" onerror="this.src='../assets/uploads/default.jpg'">
                <div><p><?php echo $_SESSION['nombre']; ?></p><a href="../logout.php" style="color:#f44336;">Cerrar Sesión</a></div>
            </div>
        </div>
    </aside>
    <main class="main-content" id="reporteContent">
        <div class="top-bar">
            <h1>📄 Reportes Estadísticos</h1>
            <div>
                <a href="?export_excel=1" class="btn-export btn-excel">📊 Exportar a Excel</a>
                <button class="btn-export btn-pdf" onclick="guardarComoPDF()">📄 Exportar a PDF</button>
                <button class="btn-export btn-print" onclick="window.print()">🖨️ Imprimir</button>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card"><div class="stat-number"><?php echo $total_usuarios; ?></div><p>Total Usuarios</p></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total_admin; ?></div><p>Administradores</p></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total_docentes; ?></div><p>Maestros</p></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total_alumnos; ?></div><p>Alumnos</p></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total_activos; ?></div><p>Usuarios Activos</p></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total_inactivos; ?></div><p>Usuarios Inactivos</p></div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 15px;">💰 Estadísticas de Reinscripciones</h3>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="background: #fff3e0; padding: 15px; border-radius: 8px; flex: 1; text-align: center;">
                    <div style="font-size: 28px; font-weight: bold; color: #f57c00;"><?php echo $reinscripciones_pendientes; ?></div>
                    <p>Pagos Pendientes</p>
                </div>
                <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; flex: 1; text-align: center;">
                    <div style="font-size: 28px; font-weight: bold; color: #2e7d32;"><?php echo $reinscripciones_pagadas; ?></div>
                    <p>Pagos Completados</p>
                </div>
            </div>
        </div>

        <?php if(!empty($ultimas_reinscripciones)): ?>
        <div class="card">
            <h3 style="margin-bottom: 15px;">📋 Últimas Reinscripciones</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead><tr><th>ID</th><th>Alumno</th><th>Email</th><th>Monto</th><th>Fecha</th><th>Estado</th></thead>
                    <tbody>
                        <?php foreach($ultimas_reinscripciones as $r): ?>
                        <tr>
                            <td><?php echo $r['id']; ?></td>
                            <td><?php echo htmlspecialchars($r['nombre'] . ' ' . $r['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($r['email']); ?></td>
                            <td>$<?php echo number_format($r['monto'], 2); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($r['fecha_solicitud'])); ?></td>
                            <td><span class="badge badge-<?php echo $r['estado']; ?>"><?php echo ucfirst($r['estado']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom: 15px;">👥 Lista de Usuarios</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Rol</th><th>Estado</th><th>Registro</th></thead>
                    <tbody>
                        <?php
                        $usuarios = $pdo->query("SELECT id, nombre, apellido, email, telefono, rol, estado, fecha_registro FROM usuarios ORDER BY id");
                        foreach($usuarios as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo $u['nombre'] . ' ' . $u['apellido']; ?></td>
                            <td><?php echo $u['email']; ?></td>
                            <td><?php echo $u['telefono'] ?? '---'; ?></td>
                            <td><?php echo $u['rol']; ?></td>
                            <td><?php echo $u['estado']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($u['fecha_registro'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function guardarComoPDF() {
    // Abrir la ventana de impresión del navegador
    window.print();
}
</script>
</body>
</html>