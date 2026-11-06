<?php
session_start();
if($_SESSION['rol'] != 'admin') { header("Location: ../login.php"); exit(); }
require_once '../config/db.php';

$total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_admin = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'")->fetchColumn();
$total_docentes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'docente'")->fetchColumn();
$total_alumnos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'alumno'")->fetchColumn();
$total_activos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'vigente'")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Panel Administrador - COBACH BC</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
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
        .sidebar-header h2 { color: #2c5f2d; font-size: 1.1rem; }
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
        
        /* Main Content */
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
            justify-content: space-between;
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
        .logout-btn {
            background: #ffebee;
            color: #c62828;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: #2c5f2d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
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
        <!-- Tarjeta de usuario arriba -->
        <div class="user-profile-card">
            <div class="user-profile-info">
                <img src="../assets/uploads/<?php echo $_SESSION['foto'] ?? 'default.jpg'; ?>" class="profile-avatar" onerror="this.src='../assets/uploads/default.jpg'">
                <div class="profile-details">
                    <p class="name"><?php echo $_SESSION['nombre']; ?></p>
                    <p class="role">Administrador</p>
                    <p><?php echo $_SESSION['email'] ?? ''; ?></p>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn">🚪 Cerrar Sesión</a>
        </div>
        
        <div class="stats">
            <div class="stat-card"><div class="stat-number"><?php echo $total_usuarios; ?></div><div class="stat-label">Total Usuarios</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total_admin; ?></div><div class="stat-label">Administradores</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total_docentes; ?></div><div class="stat-label">Maestros</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total_alumnos; ?></div><div class="stat-label">Alumnos</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total_activos; ?></div><div class="stat-label">Usuarios Activos</div></div>
        </div>
        
        <div class="card">
            <h3>Bienvenido al Panel de Administración</h3>
            <p>Desde aquí puedes gestionar todos los aspectos del sistema escolar COBACH BC.</p>
            <br>
            <a href="crear_usuario.php" class="btn-primary">➕ Crear Usuario</a>
            <a href="gestion_usuarios.php" class="btn-primary">👥 Gestionar Usuarios</a>
        </div>
    </main>
</div>
</body>
</html>