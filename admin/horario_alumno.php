<?php
session_start();
if($_SESSION['rol'] != 'admin') { header("Location: ../login.php"); exit(); }
require_once '../config/db.php';

$alumno_id = $_GET['id'] ?? 0;
$horario = [];

if($alumno_id) {
    $stmt = $pdo->prepare("
        SELECT m.nombre as materia, gm.dia, gm.hora_inicio, gm.hora_fin, gm.salon,
               CONCAT(u.nombre, ' ', u.apellido) as maestro
        FROM alumno_materias am
        JOIN grupo_materias gm ON am.materia_id = gm.materia_id AND am.grupo_id = gm.grupo_id
        JOIN materias m ON gm.materia_id = m.id
        JOIN usuarios u ON gm.maestro_id = u.id
        WHERE am.alumno_id = ?
        ORDER BY FIELD(gm.dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'), gm.hora_inicio
    ");
    $stmt->execute([$alumno_id]);
    $horario = $stmt->fetchAll();
}

$alumnos = $pdo->query("SELECT u.id, u.nombre, u.apellido, a.matricula FROM usuarios u JOIN alumnos a ON u.id = a.id WHERE u.rol = 'alumno' ORDER BY u.nombre")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Horario de Alumno</title>
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
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .horario-table { width: 100%; border-collapse: collapse; }
        .horario-table th, .horario-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        .horario-table th { background: #2c5f2d; color: white; }
        @media (max-width: 768px) { .sidebar { width: 80px; } .sidebar-header h2, .sidebar-header p, .sidebar-nav span:last-child, .user-info div { display: none; } .logo-img { display: none; } .main-content { margin-left: 80px; } }
    </style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar"><?php include 'sidebar.php'; ?></aside>
    <main class="main-content">
        <div class="card">
            <h3>📅 Ver Horario de Alumno</h3>
            <form method="GET">
                <div class="form-group">
                    <select name="id" onchange="this.form.submit()">
                        <option value="">-- Seleccionar Alumno --</option>
                        <?php foreach($alumnos as $a): ?>
                            <option value="<?php echo $a['id']; ?>" <?php echo $alumno_id == $a['id'] ? 'selected' : ''; ?>>
                                <?php echo $a['nombre'] . ' ' . $a['apellido']; ?> (<?php echo $a['matricula']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <?php if($alumno_id && !empty($horario)): ?>
        <div class="card">
            <h3>📅 Horario de Clases</h3>
            <table class="horario-table">
                <thead>
                    <tr><th>Materia</th><th>Maestro</th><th>Día</th><th>Horario</th><th>Salón</th></thead>
                <tbody>
                    <?php foreach($horario as $h): ?>
                    <tr>
                        <td><?php echo $h['materia']; ?></td>
                        <td><?php echo $h['maestro']; ?></td>
                        <td><?php echo $h['dia']; ?></td>
                        <td><?php echo substr($h['hora_inicio'], 0, 5) . ' - ' . substr($h['hora_fin'], 0, 5); ?></td>
                        <td><?php echo $h['salon'] ?? '---'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php elseif($alumno_id): ?>
            <div class="card"><p>⚠️ Este alumno no tiene materias inscritas.</p></div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>