<?php
session_start();
if($_SESSION['rol'] != 'admin') { header("Location: ../login.php"); exit(); }
require_once '../config/db.php';

// Crear tablas si no existen
$pdo->exec("CREATE TABLE IF NOT EXISTS periodos_inscripcion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    activo BOOLEAN DEFAULT TRUE
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS materias_disponibles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_id INT NOT NULL,
    periodo_id INT NOT NULL,
    horario VARCHAR(50),
    salon VARCHAR(20),
    dias VARCHAR(100),
    cupo_maximo INT DEFAULT 30,
    cupo_actual INT DEFAULT 0
)");

$pendientes = $pdo->query("SELECT COUNT(*) FROM reinscripciones WHERE estado = 'pendiente'")->fetchColumn();
$pagadas = $pdo->query("SELECT COUNT(*) FROM reinscripciones WHERE estado = 'pagado'")->fetchColumn();
$total = $pdo->query("SELECT COUNT(*) FROM reinscripciones")->fetchColumn();

$reinscripciones = $pdo->query("
    SELECT r.*, u.nombre, u.apellido, u.email 
    FROM reinscripciones r
    JOIN usuarios u ON r.alumno_id = u.id
    ORDER BY r.fecha_solicitud DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reinscripciones</title>
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
        .top-bar { margin-bottom: 24px; }
        .top-bar h1 { font-size: 1.5rem; }
        .stats { display: flex; gap: 20px; margin-bottom: 24px; flex-wrap: wrap; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; text-align: center; flex: 1; min-width: 150px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; font-weight: bold; color: #2c5f2d; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f5f5f5; font-weight: 600; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-pendiente { background: #fff3e0; color: #f57c00; }
        .badge-pagado { background: #e8f5e9; color: #2e7d32; }
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
    <a href="reinscripciones.php" class="nav-item active">💰 Reinscripciones</a>
    <a href="reportes.php" class="nav-item">📄 Reportes</a>
</nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <img src="../assets/uploads/<?php echo $_SESSION['foto'] ?? 'default.jpg'; ?>" class="user-avatar" onerror="this.src='../assets/uploads/default.jpg'">
                <div><p><?php echo $_SESSION['nombre']; ?></p><a href="../logout.php" style="color:#f44336;">Cerrar Sesión</a></div>
            </div>
        </div>
    </aside>
    <main class="main-content">
        <div class="top-bar"><h1>💰 Gestión de Reinscripciones</h1></div>
        
        <div class="stats">
            <div class="stat-card"><div class="stat-number"><?php echo $pendientes; ?></div><p>Solicitudes Pendientes</p></div>
            <div class="stat-card"><div class="stat-number"><?php echo $pagadas; ?></div><p>Pagos Completados</p></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total; ?></div><p>Total Solicitudes</p></div>
        </div>
        
        <div class="card">
            <h3 style="margin-bottom: 15px;">📋 Lista de Solicitudes</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Alumno</th><th>Monto</th><th>Fecha</th><th>Estado</th></thead>
                    <tbody>
                        <?php if(empty($reinscripciones)): ?>
                            <tr><td colspan="5" style="text-align:center;">No hay solicitudes de reinscripción</td></tr>
                        <?php else: ?>
                            <?php foreach($reinscripciones as $r): ?>
                            <tr>
                                <td><?php echo $r['id']; ?></td>
                                <td><?php echo htmlspecialchars($r['nombre'] . ' ' . $r['apellido']); ?></td>
                                <td>$<?php echo number_format($r['monto'], 2); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($r['fecha_solicitud'])); ?></td>
                                <td><span class="badge badge-<?php echo $r['estado']; ?>"><?php echo ucfirst($r['estado']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>