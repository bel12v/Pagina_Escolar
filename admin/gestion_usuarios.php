<?php
session_start();
if($_SESSION['rol'] != 'admin') { header("Location: ../login.php"); exit(); }
require_once '../config/db.php';

// Eliminar usuario
if(isset($_GET['eliminar'])) {
    $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$_GET['eliminar']]);
    header("Location: gestion_usuarios.php?msg=eliminado");
    exit();
}

// Cambiar estado
if(isset($_GET['toggle_estado'])) {
    $id = $_GET['toggle_estado'];
    $pdo->prepare("UPDATE usuarios SET estado = IF(estado = 'vigente', 'inactivo', 'vigente') WHERE id = ?")->execute([$id]);
    header("Location: gestion_usuarios.php?msg=estado");
    exit();
}

$mensaje = '';
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'eliminado') $mensaje = '✅ Usuario eliminado correctamente';
    if($_GET['msg'] == 'editado') $mensaje = '✅ Usuario actualizado correctamente';
    if($_GET['msg'] == 'estado') $mensaje = '✅ Estado actualizado correctamente';
    if($_GET['msg'] == 'foto') $mensaje = '✅ Foto actualizada correctamente';
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Usuarios</title>
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
        .top-bar { display: flex; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .top-bar h1 { font-size: 1.5rem; }
        .btn-primary { background: #2c5f2d; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background: #1e3a1e; }
        .alert-success { background: #e8f5e9; color: #2e7d32; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
        .search-box { margin-bottom: 20px; }
        .search-box input { padding: 10px 16px; border: 1px solid #d4d4d4; border-radius: 8px; width: 300px; }
        .table-container { background: white; border-radius: 12px; padding: 20px; overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 12px; background: #f5f5f5; font-weight: 600; font-size: 12px; }
        .data-table td { padding: 12px; border-bottom: 1px solid #e0e0e0; font-size: 14px; }
        .data-table tr:hover { background: #fafafa; }
        .table-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .badge-role { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-role.admin { background: #e3f2fd; color: #1976d2; }
        .badge-role.docente { background: #e8f5e9; color: #2e7d32; }
        .badge-role.alumno { background: #f3e5f5; color: #7b1fa2; }
        .badge-status { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-status.vigente { background: #e8f5e9; color: #2e7d32; }
        .badge-status.inactivo { background: #ffebee; color: #c62828; }
        .actions-cell { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-action { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; }
        .btn-view { background: #e3f2fd; color: #1976d2; }
        .btn-edit { background: #fff3e0; color: #f57c00; }
        .btn-delete { background: #ffebee; color: #c62828; }
        .btn-toggle { background: #e8f5e9; color: #2e7d32; }
        .btn-photo { background: #e0f7fa; color: #00838f; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; max-width: 500px; border-radius: 12px; animation: modalSlide 0.3s ease; }
        @keyframes modalSlide { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 1.125rem; }
        .close { font-size: 24px; cursor: pointer; color: #737373; }
        #modalVerBody, #modalEditarBody, #modalFotoBody { padding: 24px; }
        .user-detail { display: flex; gap: 20px; flex-wrap: wrap; }
        .detail-photo { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #d4d4d4; border-radius: 8px; }
        .form-buttons { display: flex; gap: 12px; margin-top: 20px; }
        .btn-save { background: #2c5f2d; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .btn-cancel { background: #f5f5f5; color: #404040; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .current-photo { text-align: center; margin-bottom: 20px; }
        .current-photo img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #2c5f2d; }
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
    <a href="gestion_usuarios.php" class="nav-item active">👥 Usuarios</a>
    <a href="gestion_alumnos.php" class="nav-item">👨‍🎓 Alumnos</a>
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
        <div class="top-bar"><h1>👥 Gestión de Usuarios</h1><a href="crear_usuario.php" class="btn-primary">➕ Nuevo Usuario</a></div>
        <?php if($mensaje): ?><div class="alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
        <div class="search-box"><input type="text" id="buscar" placeholder="🔍 Buscar por nombre o email..." onkeyup="filtrarTabla()"></div>
        <div class="table-container">
            <table class="data-table" id="tablaUsuarios">
                <thead><tr><th>ID</th><th>Foto</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach($usuarios as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><img src="../assets/uploads/<?php echo $u['foto'] ?? 'default.jpg'; ?>" class="table-avatar" onerror="this.src='../assets/uploads/default.jpg'"></td>
                        <td><?php echo htmlspecialchars($u['nombre'] . ' ' . ($u['apellido'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><span class="badge-role <?php echo $u['rol']; ?>"><?php echo $u['rol']; ?></span></td>
                        <td><span class="badge-status <?php echo $u['estado']; ?>"><?php echo $u['estado']; ?></span></td>
                        <td class="actions-cell">
                            <button class="btn-action btn-view" onclick="verUsuario(<?php echo $u['id']; ?>)">👁️ Ver</button>
                            <button class="btn-action btn-edit" onclick="editarUsuario(<?php echo $u['id']; ?>)">✏️ Editar</button>
                            <button class="btn-action btn-photo" onclick="cambiarFoto(<?php echo $u['id']; ?>)">📷 Foto</button>
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <button class="btn-action btn-delete" onclick="eliminarUsuario(<?php echo $u['id']; ?>, '<?php echo addslashes($u['nombre']); ?>')">🗑️ Eliminar</button>
                            <?php endif; ?>
                            <button class="btn-action btn-toggle" onclick="toggleEstado(<?php echo $u['id']; ?>)"><?php echo $u['estado'] == 'vigente' ? '🔴 Desactivar' : '🟢 Activar'; ?></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modales -->
<div id="modalVer" class="modal"><div class="modal-content"><div class="modal-header"><h2>👤 Detalles del Usuario</h2><span class="close" onclick="cerrarModal('modalVer')">&times;</span></div><div id="modalVerBody"></div></div></div>
<div id="modalEditar" class="modal"><div class="modal-content"><div class="modal-header"><h2>✏️ Editar Usuario</h2><span class="close" onclick="cerrarModal('modalEditar')">&times;</span></div><div id="modalEditarBody"></div></div></div>
<div id="modalFoto" class="modal"><div class="modal-content"><div class="modal-header"><h2>📷 Cambiar Foto de Perfil</h2><span class="close" onclick="cerrarModal('modalFoto')">&times;</span></div><div id="modalFotoBody"></div></div></div>

<script>
function filtrarTabla() {
    let input = document.getElementById('buscar');
    let filter = input.value.toLowerCase();
    let rows = document.getElementById('tablaUsuarios').getElementsByTagName('tr');
    for(let i = 1; i < rows.length; i++) {
        let nombre = rows[i].getElementsByTagName('td')[2];
        let email = rows[i].getElementsByTagName('td')[3];
        let text = (nombre ? nombre.textContent.toLowerCase() : '') + (email ? email.textContent.toLowerCase() : '');
        rows[i].style.display = text.indexOf(filter) > -1 ? '' : 'none';
    }
}

function verUsuario(id) {
    fetch(`api_usuario.php?id=${id}`).then(r=>r.json()).then(data=>{
        document.getElementById('modalVerBody').innerHTML = `<div class="user-detail"><img src="../assets/uploads/${data.foto||'default.jpg'}" class="detail-photo"><div><p><strong>ID:</strong> ${data.id}</p><p><strong>Nombre:</strong> ${data.nombre} ${data.apellido||''}</p><p><strong>Email:</strong> ${data.email}</p><p><strong>Teléfono:</strong> ${data.telefono||'No registrado'}</p><p><strong>Rol:</strong> ${data.rol}</p><p><strong>Estado:</strong> ${data.estado}</p><p><strong>Registro:</strong> ${data.fecha_registro}</p></div></div>`;
        document.getElementById('modalVer').style.display = 'block';
    });
}

function editarUsuario(id) {
    fetch(`api_usuario.php?id=${id}`).then(r=>r.json()).then(data=>{
        document.getElementById('modalEditarBody').innerHTML = `
            <form method="POST" action="actualizar_usuario.php">
                <input type="hidden" name="id" value="${data.id}">
                <div class="form-group"><label>Nombre:</label><input type="text" name="nombre" value="${data.nombre.replace(/['"]/g, '&#39;')}" required></div>
                <div class="form-group"><label>Apellido:</label><input type="text" name="apellido" value="${(data.apellido||'').replace(/['"]/g, '&#39;')}"></div>
                <div class="form-group"><label>Email:</label><input type="email" name="email" value="${data.email}" required></div>
                <div class="form-group"><label>Teléfono:</label><input type="text" name="telefono" value="${data.telefono||''}"></div>
                <div class="form-group"><label>Rol:</label><select name="rol"><option value="admin" ${data.rol=='admin'?'selected':''}>Admin</option><option value="docente" ${data.rol=='docente'?'selected':''}>Docente</option><option value="alumno" ${data.rol=='alumno'?'selected':''}>Alumno</option></select></div>
                <div class="form-group"><label>Estado:</label><select name="estado"><option value="vigente" ${data.estado=='vigente'?'selected':''}>Vigente</option><option value="inactivo" ${data.estado=='inactivo'?'selected':''}>Inactivo</option></select></div>
                <div class="form-buttons"><button type="submit" class="btn-save">💾 Guardar</button><button type="button" class="btn-cancel" onclick="cerrarModal('modalEditar')">Cancelar</button></div>
            </form>`;
        document.getElementById('modalEditar').style.display = 'block';
    });
}

function cambiarFoto(id) {
    fetch(`api_usuario.php?id=${id}`).then(r=>r.json()).then(data=>{
        document.getElementById('modalFotoBody').innerHTML = `
            <div class="current-photo"><img src="../assets/uploads/${data.foto||'default.jpg'}" onerror="this.src='../assets/uploads/default.jpg'"><p>Foto actual</p></div>
            <form method="POST" action="subir_foto.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="${data.id}">
                <div class="form-group"><label>Seleccionar nueva foto:</label><input type="file" name="foto" accept="image/jpeg,image/png,image/jpg" required></div>
                <div class="form-buttons"><button type="submit" class="btn-save">📷 Subir Foto</button><button type="button" class="btn-cancel" onclick="cerrarModal('modalFoto')">Cancelar</button></div>
            </form>`;
        document.getElementById('modalFoto').style.display = 'block';
    });
}

function eliminarUsuario(id, nombre) { if(confirm(`¿Eliminar a "${nombre}"?`)) window.location.href=`?eliminar=${id}`; }
function toggleEstado(id) { if(confirm('¿Cambiar estado?')) window.location.href=`?toggle_estado=${id}`; }
function cerrarModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
window.onclick = function(e) { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; }
</script>
</body>
</html>