<?php
session_start();
if($_SESSION['rol'] != 'docente') { 
    header("Location: ../login.php"); 
    exit(); 
}
require_once '../config/db.php';

$maestro_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT u.*, m.especialidad 
    FROM usuarios u 
    LEFT JOIN maestros m ON u.id = m.id 
    WHERE u.id = ?
");
$stmt->execute([$maestro_id]);
$maestro = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT materia_id) as total_materias,
           COUNT(DISTINCT grupo_id) as total_grupos
    FROM grupo_materias WHERE maestro_id = ?
");
$stmt->execute([$maestro_id]);
$stats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT am.alumno_id) as total_alumnos
    FROM grupo_materias gm
    JOIN alumno_materias am ON gm.materia_id = am.materia_id AND gm.grupo_id = am.grupo_id
    WHERE gm.maestro_id = ?
");
$stmt->execute([$maestro_id]);
$alumnos_stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inicio - Panel del Maestro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        .dashboard { display: flex; min-height: 100vh; }
        
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
        .sidebar-header h2 { color: #2c5f2d; font-size: 1rem; }
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
            padding: 10px 16px;
            color: #404040;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 2px;
        }
        .nav-item:hover { background: #f5f5f5; }
        .nav-item.active { background: #e8f5e9; color: #2c5f2d; }
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
        
        .main-content {
            margin-left: 280px;
            padding: 24px;
            width: 100%;
        }
        .user-profile-card {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .user-profile-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .profile-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-details p { margin: 2px 0; }
        .profile-details .name { font-weight: bold; font-size: 1.1rem; }
        .profile-details .role { color: #2c5f2d; font-size: 0.8rem; }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-number { font-size: 32px; font-weight: bold; color: #2c5f2d; }
        .stat-label { color: #666; margin-top: 8px; }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-label { width: 150px; font-weight: 600; color: #555; }
        .info-value { flex: 1; color: #333; }
        .info-value.verde { color: #2c5f2d; font-weight: bold; }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h2, .sidebar-header p, .sidebar-nav span:last-child, .user-info div { display: none; }
            .logo-img { display: none; }
            .main-content { margin-left: 80px; }
            .info-label { width: 100px; }
        }
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
                    <p class="name"><?php echo $maestro['nombre'] . ' ' . ($maestro['apellido'] ?? ''); ?></p>
                    <p class="role"><?php echo $maestro['especialidad'] ?? 'Docente'; ?></p>
                    <p><?php echo $maestro['email']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card"><div class="stat-number"><?php echo $stats['total_materias'] ?? 0; ?></div><div class="stat-label">📚 Materias Asignadas</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $stats['total_grupos'] ?? 0; ?></div><div class="stat-label">👥 Grupos</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $alumnos_stats['total_alumnos'] ?? 0; ?></div><div class="stat-label">👨‍🎓 Alumnos Totales</div></div>
        </div>
        
        <div class="info-card">
            <div class="info-row"><div class="info-label">👨‍🏫 Nombre completo:</div><div class="info-value"><?php echo $maestro['nombre'] . ' ' . ($maestro['apellido'] ?? ''); ?></div></div>
            <div class="info-row"><div class="info-label">📧 Correo electrónico:</div><div class="info-value"><?php echo $maestro['email']; ?></div></div>
            <div class="info-row"><div class="info-label">📞 Teléfono:</div><div class="info-value"><?php echo $maestro['telefono'] ?? 'No registrado'; ?></div></div>
            <div class="info-row"><div class="info-label">🎓 Especialidad:</div><div class="info-value verde"><?php echo $maestro['especialidad'] ?? 'No asignada'; ?></div></div>
            <div class="info-row"><div class="info-label">📅 Fecha de registro:</div><div class="info-value"><?php echo date('d/m/Y', strtotime($maestro['fecha_registro'])); ?></div></div>
            <div class="info-row"><div class="info-label">📊 Estado:</div><div class="info-value <?php echo $maestro['estado'] == 'vigente' ? 'verde' : ''; ?>"><?php echo $maestro['estado'] == 'vigente' ? '✅ Activo' : '❌ Inactivo'; ?></div></div>
        </div>
    </main>
</div>
</body>
</html>