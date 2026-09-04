<?php
session_start();
if($_SESSION['rol'] != 'docente') { 
    header("Location: ../login.php"); 
    exit(); 
}
require_once '../config/db.php';

$profesor_id = $_SESSION['user_id'];

$horario = $pdo->prepare("
    SELECT gm.*, m.nombre as materia_nombre, m.clave, g.nombre as grupo_nombre
    FROM grupo_materias gm
    JOIN materias m ON gm.materia_id = m.id
    JOIN grupos g ON gm.grupo_id = g.id
    WHERE gm.maestro_id = ?
    ORDER BY FIELD(gm.dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'), gm.hora_inicio
");
$horario->execute([$profesor_id]);
$horario = $horario->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Horario - Panel del Maestro</title>
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
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        .horario-tabla {
            width: 100%;
            border-collapse: collapse;
        }
        .horario-tabla th, .horario-tabla td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .horario-tabla th {
            background: #e8f5e9;
            font-weight: 600;
        }
        .materia-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-materia { background: #e3f2fd; color: #1976d2; }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h2, .sidebar-header p, .sidebar-nav span:last-child, .user-info div { display: none; }
            .logo-img { display: none; }
            .main-content { margin-left: 80px; }
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
                    <p class="name"><?php echo $_SESSION['nombre']; ?></p>
                    <p class="role">Maestro</p>
                    <p><?php echo $_SESSION['email'] ?? ''; ?></p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>📅 Mi Horario de Clases</h2>
            <br>
            <?php if(empty($horario)): ?>
                <p style="color: red;">⚠️ No tienes materias asignadas. Contacta al administrador.</p>
            <?php else: ?>
                <table class="horario-tabla">
                    <thead>
                        <tr><th>Día</th><th>Horario</th><th>Materia</th><th>Grupo</th><th>Salón</th></thead>
                    <tbody>
                        <?php foreach($horario as $h): ?>
                        <tr>
                            <td><?php echo $h['dia']; ?></td>
                            <td><?php echo substr($h['hora_inicio'], 0, 5) . ' - ' . substr($h['hora_fin'], 0, 5); ?></td>
                            <td><?php echo $h['materia_nombre']; ?> <span class="badge badge-materia"><?php echo $h['clave']; ?></span></td>
                            <td><?php echo $h['grupo_nombre']; ?></td>
                            <td><?php echo $h['salon'] ?? '---'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>📚 Mis Materias Asignadas</h2>
            <br>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                <?php
                $materias_unicas = [];
                foreach($horario as $h) {
                    if(!isset($materias_unicas[$h['materia_id']])) {
                        $materias_unicas[$h['materia_id']] = $h;
                    }
                }
                foreach($materias_unicas as $m):
                ?>
                <div class="materia-card">
                    <h3>📖 <?php echo $m['materia_nombre']; ?></h3>
                    <p><strong>Clave:</strong> <?php echo $m['clave']; ?></p>
                    <p><strong>Grupo:</strong> <?php echo $m['grupo_nombre']; ?></p>
                    <p><strong>Horario:</strong> <?php echo $m['dia'] . ' ' . substr($m['hora_inicio'], 0, 5) . ' - ' . substr($m['hora_fin'], 0, 5); ?></p>
                    <p><strong>Salón:</strong> <?php echo $m['salon'] ?? 'No asignado'; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>