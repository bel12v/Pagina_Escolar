<?php
session_start();
if($_SESSION['rol'] != 'admin') { header("Location: ../login.php"); exit(); }
require_once '../config/db.php';

$mensaje = '';
$alumnos_materias = [];

// Inscribir alumno
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['inscribir'])) {
    $alumno_id = $_POST['alumno_id'];
    $materia_id = $_POST['materia_id'];
    $grupo_id = $_POST['grupo_id'];
    
    // Verificar si ya está inscrito
    $check = $pdo->prepare("SELECT * FROM alumno_materias WHERE alumno_id = ? AND materia_id = ?");
    $check->execute([$alumno_id, $materia_id]);
    if($check->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO alumno_materias (alumno_id, materia_id, grupo_id) VALUES (?, ?, ?)");
        $stmt->execute([$alumno_id, $materia_id, $grupo_id]);
        $mensaje = "✅ Alumno inscrito correctamente";
    } else {
        $mensaje = "⚠️ El alumno ya está inscrito en esta materia";
    }
}

// Obtener datos
$grupos = $pdo->query("SELECT * FROM grupos ORDER BY nombre")->fetchAll();
$alumnos = $pdo->query("SELECT u.id, u.nombre, u.apellido, a.matricula FROM usuarios u JOIN alumnos a ON u.id = a.id WHERE u.rol = 'alumno' AND u.estado = 'vigente' ORDER BY u.nombre")->fetchAll();

// Seleccionar grupo para mostrar materias
$grupo_seleccionado = $_GET['grupo_id'] ?? 0;
$materias_disponibles = [];
if($grupo_seleccionado) {
    $materias_disponibles = $pdo->prepare("
        SELECT gm.*, m.nombre as materia_nombre, m.clave,
               CONCAT(u.nombre, ' ', u.apellido) as maestro_nombre
        FROM grupo_materias gm
        JOIN materias m ON gm.materia_id = m.id
        JOIN usuarios u ON gm.maestro_id = u.id
        WHERE gm.grupo_id = ?
    ");
    $materias_disponibles->execute([$grupo_seleccionado]);
    $materias_disponibles = $materias_disponibles->fetchAll();
}

// Ver inscripciones del alumno
if(isset($_GET['ver_alumno'])) {
    $stmt = $pdo->prepare("
        SELECT am.*, m.nombre as materia_nombre, g.nombre as grupo_nombre
        FROM alumno_materias am
        JOIN materias m ON am.materia_id = m.id
        JOIN grupos g ON am.grupo_id = g.id
        WHERE am.alumno_id = ?
    ");
    $stmt->execute([$_GET['ver_alumno']]);
    $alumnos_materias = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inscribir Alumnos</title>
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
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-primary { background: #2c5f2d; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f5f5f5; }
        .alert-success { background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-warning { background: #fff3e0; color: #f57c00; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .horario-table td, .horario-table th { padding: 8px; text-align: center; }
        @media (max-width: 768px) { .sidebar { width: 80px; } .sidebar-header h2, .sidebar-header p, .sidebar-nav span:last-child, .user-info div { display: none; } .logo-img { display: none; } .main-content { margin-left: 80px; } }
    </style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar"><?php include 'sidebar.php'; ?></aside>
    <main class="main-content">
        <div class="top-bar"><h1>✏️ Inscribir Alumnos a Materias</h1></div>
        
        <?php if($mensaje): ?>
            <div class="alert-<?php echo strpos($mensaje, '✅') !== false ? 'success' : 'warning'; ?>"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>📋 Paso 1: Selecciona un Grupo</h3>
            <form method="GET">
                <div class="form-group">
                    <select name="grupo_id" onchange="this.form.submit()">
                        <option value="">-- Seleccionar Grupo --</option>
                        <?php foreach($grupos as $g): ?>
                            <option value="<?php echo $g['id']; ?>" <?php echo $grupo_seleccionado == $g['id'] ? 'selected' : ''; ?>>
                                <?php echo $g['nombre']; ?> (<?php echo $g['grado'] ?? ''; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <?php if($grupo_seleccionado && !empty($materias_disponibles)): ?>
        <div class="card">
            <h3>📚 Paso 2: Materias del Grupo <?php echo $grupos[array_search($grupo_seleccionado, array_column($grupos, 'id'))]['nombre'] ?? ''; ?></h3>
            <div style="overflow-x: auto;">
                <table class="horario-table">
                    <thead>
                        <tr><th>Materia</th><th>Maestro</th><th>Día</th><th>Horario</th><th>Salón</th></thead>
                    <tbody>
                        <?php foreach($materias_disponibles as $m): ?>
                        <tr>
                            <td><?php echo $m['materia_nombre']; ?> (<?php echo $m['clave']; ?>)</td>
                            <td><?php echo $m['maestro_nombre']; ?></td>
                            <td><?php echo $m['dia']; ?></td>
                            <td><?php echo substr($m['hora_inicio'], 0, 5) . ' - ' . substr($m['hora_fin'], 0, 5); ?></td>
                            <td><?php echo $m['salon'] ?? '---'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <h3>👨‍🎓 Paso 3: Inscribir Alumno</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Seleccionar Alumno:</label>
                    <select name="alumno_id" required>
                        <option value="">-- Seleccionar Alumno --</option>
                        <?php foreach($alumnos as $a): ?>
                            <option value="<?php echo $a['id']; ?>"><?php echo $a['nombre'] . ' ' . $a['apellido']; ?> (<?php echo $a['matricula']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Seleccionar Materia:</label>
                    <select name="materia_id" required>
                        <option value="">-- Seleccionar Materia --</option>
                        <?php foreach($materias_disponibles as $m): ?>
                            <option value="<?php echo $m['materia_id']; ?>"><?php echo $m['materia_nombre']; ?> - <?php echo $m['maestro_nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="grupo_id" value="<?php echo $grupo_seleccionado; ?>">
                <button type="submit" name="inscribir" class="btn-primary">✅ Inscribir Alumno</button>
            </form>
        </div>
        <?php elseif($grupo_seleccionado): ?>
            <div class="alert-warning">⚠️ No hay materias asignadas a este grupo. Ve a "Asignar Materias" primero.</div>
        <?php endif; ?>
        
        <div class="card">
            <h3>🔍 Ver materias de un alumno</h3>
            <form method="GET">
                <div class="form-group">
                    <select name="ver_alumno" onchange="this.form.submit()">
                        <option value="">-- Seleccionar Alumno --</option>
                        <?php foreach($alumnos as $a): ?>
                            <option value="<?php echo $a['id']; ?>"><?php echo $a['nombre'] . ' ' . $a['apellido']; ?> (<?php echo $a['matricula']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            
            <?php if(!empty($alumnos_materias)): ?>
                <h4>Materias inscritas:</h4>
                <table>
                    <thead><tr><th>Materia</th><th>Grupo</th></tr></thead>
                    <tbody>
                        <?php foreach($alumnos_materias as $am): ?>
                            <tr><td><?php echo $am['materia_nombre']; ?></td><td><?php echo $am['grupo_nombre']; ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>