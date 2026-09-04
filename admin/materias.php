<?php
session_start();
if($_SESSION['rol'] != 'admin') { header("Location: ../login.php"); exit(); }
require_once '../config/db.php';

// Crear tabla si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    clave VARCHAR(20) UNIQUE,
    creditos INT DEFAULT 5
)");

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $stmt = $pdo->prepare("INSERT INTO materias (nombre, clave, creditos) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['nombre'], $_POST['clave'], $_POST['creditos']]);
}

$materias = $pdo->query("SELECT * FROM materias ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestionar Materias</title>
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
        .btn-primary { background: #2c5f2d; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-secondary { background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f5f5f5; font-weight: 600; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #d4d4d4; border-radius: 8px; }
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
    <a href="materias.php" class="nav-item active">📚 Materias</a>
    <a href="grupos.php" class="nav-item">👥 Grupos</a>
    <a href="asignar_materias.php" class="nav-item">📋 Asignar Materias</a>
    <a href="inscribir_alumnos.php" class="nav-item">✏️ Inscribir Alumnos</a>
    <a href="horario_alumno.php" class="nav-item">📅 Horario Alumnos</a>
    <a href="reinscripciones.php" class="nav-item">💰 Reinscripciones</a>
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
        <div class="top-bar">
            <h1>📚 Gestión de Materias</h1>
            <button class="btn-primary" onclick="mostrarFormulario()">➕ Nueva Materia</button>
        </div>
        
        <div id="formMateria" class="card" style="display:none;">
            <h3 style="margin-bottom: 15px;">📝 Nueva Materia</h3>
            <form method="POST">
                <div class="form-group"><label>Nombre:</label><input type="text" name="nombre" required></div>
                <div class="form-group"><label>Clave:</label><input type="text" name="clave" required></div>
                <div class="form-group"><label>Créditos:</label><input type="number" name="creditos" value="5" required></div>
                <button type="submit" name="guardar" class="btn-primary">💾 Guardar</button>
                <button type="button" class="btn-secondary" onclick="ocultarFormulario()">❌ Cancelar</button>
            </form>
        </div>
        
        <div class="card">
            <h3 style="margin-bottom: 15px;">📋 Lista de Materias</h3>
            <?php if(empty($materias)): ?>
                <p style="text-align:center; padding:20px;">No hay materias registradas</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Clave</th><th>Nombre</th><th>Créditos</th></thead>
                        <tbody>
                            <?php foreach($materias as $m): ?>
                            <tr><td><?php echo $m['id']; ?></td><td><?php echo $m['clave']; ?></td><td><?php echo $m['nombre']; ?></td><td><?php echo $m['creditos']; ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
function mostrarFormulario() { document.getElementById('formMateria').style.display = 'block'; }
function ocultarFormulario() { document.getElementById('formMateria').style.display = 'none'; }
</script>
</body>
</html>