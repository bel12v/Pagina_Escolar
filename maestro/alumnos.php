<?php
session_start();
if($_SESSION['rol'] != 'docente') { 
    header("Location: ../login.php"); 
    exit(); 
}
require_once '../config/db.php';

$profesor_id = $_SESSION['user_id'];
$mensaje = '';
$error = '';

// Eliminar calificación
if(isset($_GET['eliminar_calif'])) {
    $alumno_id = $_GET['alumno_id'];
    $materia_id = $_GET['materia_id'];
    $unidad = $_GET['unidad'];
    
    $stmt = $pdo->prepare("DELETE FROM calificaciones WHERE alumno_id = ? AND materia_id = ? AND unidad = ?");
    if($stmt->execute([$alumno_id, $materia_id, $unidad])) {
        $mensaje = "✅ Calificación eliminada correctamente";
    } else {
        $error = "❌ Error al eliminar calificación";
    }
}

// Editar calificación individual
if(isset($_POST['editar_calif'])) {
    $alumno_id = $_POST['alumno_id'];
    $materia_id = $_POST['materia_id'];
    $unidad = $_POST['unidad'];
    $calificacion = $_POST['calificacion'];
    
    $stmt = $pdo->prepare("
        INSERT INTO calificaciones (alumno_id, materia_id, unidad, calificacion) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE calificacion = ?
    ");
    if($stmt->execute([$alumno_id, $materia_id, $unidad, $calificacion, $calificacion])) {
        $mensaje = "✅ Calificación actualizada correctamente";
    } else {
        $error = "❌ Error al actualizar calificación";
    }
}

// Guardar todas las calificaciones de un alumno
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_todas'])) {
    $alumno_id = $_POST['alumno_id'];
    $materia_id = $_POST['materia_id'];
    
    for($u = 1; $u <= 6; $u++) {
        $calif = $_POST["unidad_{$u}"] ?? null;
        if($calif !== null && $calif !== '') {
            $stmt = $pdo->prepare("
                INSERT INTO calificaciones (alumno_id, materia_id, unidad, calificacion) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE calificacion = ?
            ");
            $stmt->execute([$alumno_id, $materia_id, $u, $calif, $calif]);
        }
    }
    $mensaje = "✅ Calificaciones guardadas correctamente";
}

// Obtener materias del profesor
$materias = $pdo->prepare("
    SELECT DISTINCT gm.materia_id, m.nombre as materia_nombre, m.clave, gm.grupo_id, g.nombre as grupo_nombre
    FROM grupo_materias gm
    JOIN materias m ON gm.materia_id = m.id
    JOIN grupos g ON gm.grupo_id = g.id
    WHERE gm.maestro_id = ?
    ORDER BY m.nombre
");
$materias->execute([$profesor_id]);
$materias = $materias->fetchAll();

// Obtener calificaciones existentes
$calificaciones_existentes = [];
$stmt = $pdo->prepare("SELECT alumno_id, materia_id, unidad, calificacion FROM calificaciones");
$stmt->execute();
foreach($stmt->fetchAll() as $c) {
    $calificaciones_existentes[$c['alumno_id']][$c['materia_id']][$c['unidad']] = $c['calificacion'];
}

// Materia seleccionada
$materia_seleccionada = $_GET['materia_id'] ?? ($materias[0]['materia_id'] ?? 0);
$grupo_seleccionado = 0;
$alumnos = [];
$materia_nombre = '';
$grupo_nombre = '';

if($materia_seleccionada) {
    foreach($materias as $m) {
        if($m['materia_id'] == $materia_seleccionada) {
            $grupo_seleccionado = $m['grupo_id'];
            $materia_nombre = $m['materia_nombre'];
            $grupo_nombre = $m['grupo_nombre'];
            break;
        }
    }
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre, u.apellido, a.matricula
        FROM alumno_materias am
        JOIN usuarios u ON am.alumno_id = u.id
        JOIN alumnos a ON u.id = a.id
        WHERE am.materia_id = ? AND am.grupo_id = ?
        ORDER BY u.nombre
    ");
    $stmt->execute([$materia_seleccionada, $grupo_seleccionado]);
    $alumnos = $stmt->fetchAll();
}

// Datos para el modal
$modal_alumno_id = $_GET['edit_alumno'] ?? 0;
$modal_materia_id = $_GET['edit_materia'] ?? 0;
$modal_unidad = $_GET['edit_unidad'] ?? 0;
$modal_calificacion = '';
if($modal_alumno_id && $modal_materia_id && $modal_unidad) {
    $modal_calificacion = $calificaciones_existentes[$modal_alumno_id][$modal_materia_id][$modal_unidad] ?? '';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Alumnos y Calificaciones - Panel del Maestro</title>
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
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        .materia-selector {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .materia-btn {
            background: #e0e0e0;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }
        .materia-btn:hover { background: #ccc; }
        .materia-btn.active {
            background: #2c5f2d;
            color: white;
        }
        .calificacion-input {
            width: 70px;
            padding: 6px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-save {
            background: #2c5f2d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 20px;
        }
        .btn-save:hover { background: #1e3a1e; }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .aprobada { background: #e8f5e9; }
        .reprobada { background: #ffebee; }
        .promedio { font-weight: bold; }
        .acciones-calif {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        .btn-edit-calif {
            background: #fff3e0;
            color: #f57c00;
            border: none;
            border-radius: 5px;
            padding: 4px 8px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-edit-calif:hover { background: #ffe0b2; }
        .btn-delete-calif {
            background: #ffebee;
            color: #c62828;
            border: none;
            border-radius: 5px;
            padding: 4px 8px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-delete-calif:hover { background: #ffcdd2; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white;
            margin: 10% auto;
            max-width: 400px;
            border-radius: 12px;
            animation: modalSlide 0.3s ease;
        }
        @keyframes modalSlide {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { font-size: 1.1rem; }
        .close {
            font-size: 24px;
            cursor: pointer;
            color: #737373;
        }
        .modal-body { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .form-buttons { display: flex; gap: 12px; margin-top: 20px; }
        .btn-primary { background: #2c5f2d; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .btn-secondary { background: #f5f5f5; color: #404040; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar-header h2, .sidebar-header p, .sidebar-nav span:last-child, .user-info div { display: none; }
            .logo-img { display: none; }
            .main-content { margin-left: 80px; }
            .calificacion-input { width: 50px; }
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
            
        
        <?php if($mensaje): ?>
            <div class="alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>👨‍🎓 Alumnos y Calificaciones</h2>
            <br>
            
            <div class="materia-selector">
                <?php foreach($materias as $m): ?>
                    <a href="?materia_id=<?php echo $m['materia_id']; ?>" 
                       class="materia-btn <?php echo $materia_seleccionada == $m['materia_id'] ? 'active' : ''; ?>">
                        📖 <?php echo $m['materia_nombre']; ?> (<?php echo $m['grupo_nombre']; ?>)
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if(!empty($alumnos)): ?>
                <h3><?php echo $materia_nombre; ?> - Grupo <?php echo $grupo_nombre; ?></h3>
                <br>
                
                <div style="overflow-x: auto;">
                    <form method="POST" id="formCalificaciones">
                        <input type="hidden" name="materia_id" value="<?php echo $materia_seleccionada; ?>">
                        <table>
                            <thead>
                                <tr>
                                    <th>Alumno</th>
                                    <th>Matrícula</th>
                                    <?php for($u = 1; $u <= 6; $u++): ?>
                                        <th>Unidad <?php echo $u; ?></th>
                                    <?php endfor; ?>
                                    <th>Promedio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </thead>
                                <tbody>
                                    <?php foreach($alumnos as $alumno): ?>
                                        <?php
                                        $suma = 0;
                                        for($u = 1; $u <= 6; $u++) {
                                            $calif = $calificaciones_existentes[$alumno['id']][$materia_seleccionada][$u] ?? null;
                                            if($calif !== null) $suma += $calif;
                                        }
                                        $promedio = $suma / 6;
                                        $estado = $promedio >= 6 ? 'APROBADO' : 'REPROBADO';
                                        $clase_fila = $estado == 'APROBADO' ? 'aprobada' : 'reprobada';
                                        ?>
                                        <tr class="<?php echo $clase_fila; ?>">
                                            <td>
                                                <?php echo $alumno['nombre'] . ' ' . $alumno['apellido']; ?>
                                                <input type="hidden" name="alumno_id" value="<?php echo $alumno['id']; ?>">
                                            </td>
                                            <td><?php echo $alumno['matricula']; ?></td>
                                            <?php for($u = 1; $u <= 6; $u++): ?>
                                                <td style="text-align: center;">
                                                    <?php 
                                                    $calif_actual = $calificaciones_existentes[$alumno['id']][$materia_seleccionada][$u] ?? '';
                                                    if($calif_actual !== ''): 
                                                    ?>
                                                        <span class="calif-valor" id="calif-<?php echo $alumno['id']; ?>-<?php echo $u; ?>">
                                                            <?php echo number_format($calif_actual, 1); ?>
                                                        </span>
                                                        <br>
                                                        <div class="acciones-calif" style="margin-top: 5px;">
                                                            <a href="?edit_alumno=<?php echo $alumno['id']; ?>&edit_materia=<?php echo $materia_seleccionada; ?>&edit_unidad=<?php echo $u; ?>" 
                                                               class="btn-edit-calif" 
                                                               onclick="return false;"
                                                               data-alumno="<?php echo $alumno['id']; ?>"
                                                               data-materia="<?php echo $materia_seleccionada; ?>"
                                                               data-unidad="<?php echo $u; ?>"
                                                               data-calif="<?php echo $calif_actual; ?>">
                                                                ✏️
                                                            </a>
                                                            <a href="?eliminar_calif=1&alumno_id=<?php echo $alumno['id']; ?>&materia_id=<?php echo $materia_seleccionada; ?>&unidad=<?php echo $u; ?>" 
                                                               class="btn-delete-calif"
                                                               onclick="return confirm('¿Eliminar esta calificación?')">
                                                                🗑️
                                                            </a>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="calif-valor" id="calif-<?php echo $alumno['id']; ?>-<?php echo $u; ?>">-</span>
                                                        <br>
                                                        <div class="acciones-calif" style="margin-top: 5px;">
                                                            <a href="?edit_alumno=<?php echo $alumno['id']; ?>&edit_materia=<?php echo $materia_seleccionada; ?>&edit_unidad=<?php echo $u; ?>" 
                                                               class="btn-edit-calif"
                                                               onclick="return false;"
                                                               data-alumno="<?php echo $alumno['id']; ?>"
                                                               data-materia="<?php echo $materia_seleccionada; ?>"
                                                               data-unidad="<?php echo $u; ?>"
                                                               data-calif="">
                                                                ➕ Agregar
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                    <input type="hidden" name="unidad_<?php echo $u; ?>" value="<?php echo $calif_actual; ?>">
                                                </td>
                                            <?php endfor; ?>
                                            <td class="promedio"><?php echo number_format($promedio, 2); ?></td>
                                            <td><?php echo $estado; ?></td>
                                            <td>
                                                <button type="submit" name="guardar_todas" form="formCalificaciones" 
                                                        class="btn-edit-calif" style="background: #e8f5e9;"
                                                        onclick="document.getElementById('alumno_id_<?php echo $alumno['id']; ?>').value = <?php echo $alumno['id']; ?>">
                                                    💾 Guardar Todo
                                                </button>
                                                <input type="hidden" name="alumno_id" id="alumno_id_<?php echo $alumno['id']; ?>" value="<?php echo $alumno['id']; ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                        </table>
                    </form>
                </div>
            <?php else: ?>
                <p>No hay alumnos inscritos en esta materia.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal para editar calificación -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>✏️ Editar Calificación</h3>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="editar_calif" value="1">
                <input type="hidden" name="alumno_id" id="modal_alumno_id">
                <input type="hidden" name="materia_id" id="modal_materia_id">
                <input type="hidden" name="unidad" id="modal_unidad">
                
                <div class="form-group">
                    <label>📚 Materia:</label>
                    <input type="text" id="modal_materia_nombre" readonly style="background: #f5f5f5;">
                </div>
                <div class="form-group">
                    <label>👨‍🎓 Alumno:</label>
                    <input type="text" id="modal_alumno_nombre" readonly style="background: #f5f5f5;">
                </div>
                <div class="form-group">
                    <label>📊 Unidad:</label>
                    <input type="text" id="modal_unidad_num" readonly style="background: #f5f5f5;">
                </div>
                <div class="form-group">
                    <label>🎯 Calificación (0-10):</label>
                    <input type="number" step="0.1" min="0" max="10" name="calificacion" id="modal_calificacion" required>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn-primary">💾 Guardar Cambios</button>
                    <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Datos de los alumnos para el modal
const alumnos = <?php 
    $alumnos_data = [];
    foreach($alumnos as $a) {
        $alumnos_data[$a['id']] = $a['nombre'] . ' ' . $a['apellido'];
    }
    echo json_encode($alumnos_data);
?>;

const materiaNombre = "<?php echo $materia_nombre; ?>";

function abrirModal(alumnoId, materiaId, unidad, califActual) {
    document.getElementById('modal_alumno_id').value = alumnoId;
    document.getElementById('modal_materia_id').value = materiaId;
    document.getElementById('modal_unidad').value = unidad;
    document.getElementById('modal_calificacion').value = califActual;
    document.getElementById('modal_alumno_nombre').value = alumnos[alumnoId] || 'Desconocido';
    document.getElementById('modal_materia_nombre').value = materiaNombre;
    document.getElementById('modal_unidad_num').value = unidad;
    document.getElementById('editModal').style.display = 'block';
}

function cerrarModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Configurar los botones de editar
document.querySelectorAll('.btn-edit-calif[data-alumno]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const alumnoId = this.getAttribute('data-alumno');
        const materiaId = this.getAttribute('data-materia');
        const unidad = this.getAttribute('data-unidad');
        const calif = this.getAttribute('data-calif');
        abrirModal(alumnoId, materiaId, unidad, calif);
    });
});

// Cerrar modal haciendo clic fuera
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        cerrarModal();
    }
}
</script>
</body>
</html>