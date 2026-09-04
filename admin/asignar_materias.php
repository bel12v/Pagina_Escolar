<?php
session_start();
if($_SESSION['rol'] != 'admin') { header("Location: ../login.php"); exit(); }
require_once '../config/db.php';

// Procesar asignación
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asignar'])) {
    $grupo_id = $_POST['grupo_id'];
    $materia_id = $_POST['materia_id'];
    $maestro_id = $_POST['maestro_id'];
    $dia = $_POST['dia'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $salon = $_POST['salon'];
    
    $stmt = $pdo->prepare("INSERT INTO grupo_materias (grupo_id, materia_id, maestro_id, dia, hora_inicio, hora_fin, salon) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$grupo_id, $materia_id, $maestro_id, $dia, $hora_inicio, $hora_fin, $salon]);
    $mensaje = "✅ Materia asignada correctamente";
}

// Eliminar asignación
if(isset($_GET['eliminar'])) {
    $pdo->prepare("DELETE FROM grupo_materias WHERE id = ?")->execute([$_GET['eliminar']]);
    header("Location: asignar_materias.php");
    exit();
}

// Obtener datos
$grupos = $pdo->query("SELECT * FROM grupos ORDER BY nombre")->fetchAll();
$materias = $pdo->query("SELECT * FROM materias ORDER BY nombre")->fetchAll();
$maestros = $pdo->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'docente' AND estado = 'vigente'")->fetchAll();

// Obtener asignaciones existentes
$asignaciones = $pdo->query("
    SELECT gm.*, g.nombre as grupo_nombre, m.nombre as materia_nombre, 
           CONCAT(u.nombre, ' ', u.apellido) as maestro_nombre
    FROM grupo_materias gm
    JOIN grupos g ON gm.grupo_id = g.id
    JOIN materias m ON gm.materia_id = m.id
    JOIN usuarios u ON gm.maestro_id = u.id
    ORDER BY g.nombre, gm.dia, gm.hora_inicio
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Asignar Materias</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        .dashboard { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: white; border-right: 1px solid #e0e0e0; position: fixed; height: 100vh; }
        .sidebar-header { padding: 24px; text-align: center; border-bottom: 1px solid #e0e0e0; }
        .logo-img { height: 50px; width: auto; margin-bottom: 10px; }
        .sidebar-header h2 { color: #2c5f2d; font-size: 1.25rem; }
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
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-primary { background: #2c5f2d; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .btn-danger { background: #c62828; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f5f5f5; }
        .alert-success { background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
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
            <a href="asignar_materias.php" class="nav-item active">📋 Asignar Materias</a>
            <a href="inscribir_alumnos.php" class="nav-item">✏️ Inscribir Alumnos</a>
            <a href="reinscripciones.php" class="nav-item">💰 Reinscripciones</a>
            <a href="reportes.php" class="nav-item">📄 Reportes</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <img src="../assets/uploads/<?php echo $_SESSION['foto'] ?? 'default.jpg'; ?>" class="user-avatar">
                <div><p><?php echo $_SESSION['nombre']; ?></p><a href="../logout.php" style="color:#f44336;">Cerrar Sesión</a></div>
            </div>
        </div>
    </aside>
    <main class="main-content">
        <div class="top-bar"><h1>📋 Asignar Materias a Grupos</h1></div>
        
        <?php if(isset($mensaje)): ?>
            <div class="alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>➕ Nueva Asignación</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Grupo:</label>
                    <select name="grupo_id" required>
                        <option value="">Seleccionar grupo</option>
                        <?php foreach($grupos as $g): ?>
                            <option value="<?php echo $g['id']; ?>"><?php echo $g['nombre']; ?> (<?php echo $g['grado'] ?? ''; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Materia:</label>
                    <select name="materia_id" required>
                        <option value="">Seleccionar materia</option>
                        <?php foreach($materias as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo $m['nombre']; ?> (<?php echo $m['clave']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Maestro:</label>
                    <select name="maestro_id" required>
                        <option value="">Seleccionar maestro</option>
                        <?php foreach($maestros as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo $m['nombre'] . ' ' . $m['apellido']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Día:</label>
                    <select name="dia" required>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miércoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hora Inicio:</label>
                    <input type="time" name="hora_inicio" required>
                </div>
                <div class="form-group">
                    <label>Hora Fin:</label>
                    <input type="time" name="hora_fin" required>
                </div>
                <div class="form-group">
                    <label>Salón:</label>
                    <input type="text" name="salon" placeholder="Ej: A-101">
                </div>
                <button type="submit" name="asignar" class="btn-primary">✅ Asignar Materia</button>
            </form>
        </div>
        
        <div class="card">
            <h3>📋 Asignaciones Actuales</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>Grupo</th><th>Materia</th><th>Maestro</th><th>Día</th><th>Horario</th><th>Salón</th><th>Acción</th></thead>
                    <tbody>
                        <?php foreach($asignaciones as $a): ?>
                        <tr>
                            <td><?php echo $a['grupo_nombre']; ?></td>
                            <td><?php echo $a['materia_nombre']; ?></td>
                            <td><?php echo $a['maestro_nombre']; ?></td>
                            <td><?php echo $a['dia']; ?></td>
                            <td><?php echo substr($a['hora_inicio'], 0, 5) . ' - ' . substr($a['hora_fin'], 0, 5); ?></td>
                            <td><?php echo $a['salon'] ?? '---'; ?></td>
                            <td><a href="?eliminar=<?php echo $a['id']; ?>" class="btn-danger" onclick="return confirm('¿Eliminar esta asignación?')">🗑️</a></td>
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