<?php
session_start();
if($_SESSION['rol'] != 'admin') { header("Location: ../login.php"); exit(); }
require_once '../config/db.php';

// Obtener lista de alumnos (corregido)
$alumnos = $pdo->query("
    SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.foto, u.estado, u.fecha_registro,
           a.matricula, a.grado, a.grupo_id
    FROM usuarios u
    LEFT JOIN alumnos a ON u.id = a.id
    WHERE u.rol = 'alumno'
    ORDER BY u.id DESC
")->fetchAll();

$total_alumnos = count($alumnos);
$activos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'alumno' AND estado = 'vigente'")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestionar Alumnos</title>
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
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .stats { display: flex; gap: 20px; margin-bottom: 24px; flex-wrap: wrap; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; text-align: center; flex: 1; min-width: 150px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; font-weight: bold; color: #2c5f2d; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f5f5f5; font-weight: 600; }
        .table-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-vigente { background: #e8f5e9; color: #2e7d32; }
        .badge-inactivo { background: #ffebee; color: #c62828; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-view { background: #e3f2fd; color: #1976d2; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-edit { background: #fff3e0; color: #f57c00; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-delete { background: #ffebee; color: #c62828; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; max-width: 500px; border-radius: 12px; }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; }
        .close { font-size: 24px; cursor: pointer; }
        #modalBody { padding: 24px; }
        .user-detail { display: flex; gap: 20px; flex-wrap: wrap; }
        .detail-photo { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; }
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
    <a href="gestion_alumnos.php" class="nav-item active">👨‍🎓 Alumnos</a>
    <a href="gestion_maestros.php" class="nav-item">👨‍🏫 Maestros</a>
    <a href="materias.php" class="nav-item">📚 Materias</a>
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
            <h1>👨‍🎓 Gestión de Alumnos</h1>
            <a href="crear_usuario.php?rol=alumno" class="btn-primary">➕ Nuevo Alumno</a>
        </div>

        <div class="stats">
            <div class="stat-card"><div class="stat-number"><?php echo $total_alumnos; ?></div><p>Total Alumnos</p></div>
            <div class="stat-card"><div class="stat-number"><?php echo $activos; ?></div><p>Alumnos Activos</p></div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 15px;">📋 Lista de Alumnos</h3>
            <?php if(empty($alumnos)): ?>
                <p style="text-align:center; padding:20px;">No hay alumnos registrados</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Foto</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Matrícula</th>
                                <th>Grado</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($alumnos as $a): ?>
                            <tr>
                                <td><?php echo $a['id']; ?></td>
                                <td><img src="../assets/uploads/<?php echo $a['foto'] ?? 'default.jpg'; ?>" class="table-avatar" onerror="this.src='../assets/uploads/default.jpg'"></td>
                                <td><?php echo htmlspecialchars($a['nombre'] . ' ' . ($a['apellido'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($a['email']); ?></td>
                                <td><?php echo $a['matricula'] ?? 'No asignada'; ?></td>
                                <td><?php echo $a['grado'] ?? 'No asignado'; ?></td>
                                <td><span class="badge <?php echo $a['estado'] == 'vigente' ? 'badge-vigente' : 'badge-inactivo'; ?>"><?php echo $a['estado']; ?></span></td>
                                <td class="actions">
                                    <button class="btn-view" onclick="verAlumno(<?php echo $a['id']; ?>)">👁️ Ver</button>
                                    <button class="btn-edit" onclick="editarAlumno(<?php echo $a['id']; ?>)">✏️ Editar</button>
                                    <button class="btn-delete" onclick="eliminarAlumno(<?php echo $a['id']; ?>, '<?php echo addslashes($a['nombre']); ?>')">🗑️ Eliminar</button>
                                
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal Ver Alumno -->
<div id="modalVer" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h2>👤 Detalles del Alumno</h2><span class="close" onclick="cerrarModal()">&times;</span></div>
        <div id="modalBody"></div>
    </div>
</div>

<script>
function verAlumno(id) {
    fetch(`api_usuario.php?id=${id}`).then(r=>r.json()).then(data=>{
        document.getElementById('modalBody').innerHTML = `
            <div class="user-detail">
                <img src="../assets/uploads/${data.foto||'default.jpg'}" class="detail-photo">
                <div>
                    <p><strong>ID:</strong> ${data.id}</p>
                    <p><strong>Nombre:</strong> ${data.nombre} ${data.apellido||''}</p>
                    <p><strong>Email:</strong> ${data.email}</p>
                    <p><strong>Teléfono:</strong> ${data.telefono||'No registrado'}</p>
                    <p><strong>Rol:</strong> ${data.rol}</p>
                    <p><strong>Estado:</strong> ${data.estado}</p>
                    <p><strong>Registro:</strong> ${data.fecha_registro}</p>
                </div>
            </div>`;
        document.getElementById('modalVer').style.display = 'block';
    });
}
function cerrarModal() { document.getElementById('modalVer').style.display = 'none'; }
function editarAlumno(id) { window.location.href = `editar_alumno.php?id=${id}`; }
function eliminarAlumno(id, nombre) { if(confirm(`¿Eliminar al alumno "${nombre}"?`)) window.location.href = `eliminar_alumno.php?id=${id}`; }
window.onclick = function(e) { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; }
</script>
</body>
</html>