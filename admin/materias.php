<?php
session_start();
if($_SESSION['rol'] != 'admin') { header("Location: ../login.php"); exit(); }
require_once '../config/db.php'; // Asegúrate de que esta sea la ruta correcta a tu db.php

// Crear tabla si no existe (y agregar la columna estado si falta)
$pdo->exec("CREATE TABLE IF NOT EXISTS materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    clave VARCHAR(20) UNIQUE,
    creditos INT DEFAULT 5
)");

// Intentar agregar la columna 'estado' si no existe (para el botón desactivar)
try {
    $pdo->exec("ALTER TABLE materias ADD COLUMN estado TINYINT(1) DEFAULT 1");
} catch (PDOException $e) {
    // Si la columna ya existe, no hace nada y continúa
}

// Insertar materia por POST (Método tradicional de tu formulario)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $stmt = $pdo->prepare("INSERT INTO materias (nombre, clave, creditos, estado) VALUES (?, ?, ?, 1)");
    $stmt->execute([$_POST['nombre'], $_POST['clave'], $_POST['creditos']]);
}

// Obtener materias
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

        /* --- ESTILOS DE LOS BOTONES DE ACCIÓN (IGUAL A TU IMAGEN) --- */
        .acciones { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-accion { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: 0.2s; }
        .btn-ver { background: #e3f2fd; color: #1976d2; }
        .btn-editar { background: #fff3e0; color: #f57c00; }
        .btn-foto { background: #e0f7fa; color: #0097a7; }
        .btn-eliminar { background: #ffebee; color: #d32f2f; }
        .btn-desactivar { background: #e8f5e9; color: #2e7d32; }
        .btn-activar { background: #fff9c4; color: #f57f17; }
        .btn-accion:hover { filter: brightness(0.9); }

        /* --- ESTILOS PARA VENTANAS MODALES --- */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 24px; border-radius: 12px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .close { font-size: 24px; cursor: pointer; color: #666; }

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
        
        <!-- FORMULARIO NUEVA MATERIA -->
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
        
        <!-- TABLA DE MATERIAS -->
        <div class="card">
            <h3 style="margin-bottom: 15px;">📋 Lista de Materias</h3>
            <?php if(empty($materias)): ?>
                <p style="text-align:center; padding:20px;">No hay materias registradas</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Clave</th>
                                <th>Nombre</th>
                                <th>Créditos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($materias as $m): ?>
                            <tr>
                                <td><?php echo $m['id']; ?></td>
                                <td><?php echo $m['clave']; ?></td>
                                <td><?php echo $m['nombre']; ?></td>
                                <td><?php echo $m['creditos']; ?></td>
                                <td>
                                    <div class="acciones">
                                        <!-- Botón Ver -->
                                        <button class="btn-accion btn-ver" onclick="verMateria('<?php echo $m['nombre']; ?>', '<?php echo $m['clave']; ?>', '<?php echo $m['creditos']; ?>')">
                                            👁️ Ver
                                        </button>
                                        
                                        <!-- Botón Editar -->
                                        <button class="btn-accion btn-editar" onclick="abrirModalEditar(<?php echo $m['id']; ?>, '<?php echo $m['nombre']; ?>', '<?php echo $m['clave']; ?>', <?php echo $m['creditos']; ?>)">
                                            ✏️ Editar
                                        </button>

                                        <!-- Botón Eliminar -->
                                        <button class="btn-accion btn-eliminar" onclick="eliminarMateria(<?php echo $m['id']; ?>)">
                                            🗑️ Eliminar
                                        </button>

                                        <!-- Botón Desactivar/Activar (Basado en el estado) -->
                                        <?php if(isset($m['estado']) && $m['estado'] == 1): ?>
                                            <button class="btn-accion btn-desactivar" onclick="cambiarEstado(<?php echo $m['id']; ?>, 0)">
                                                🔴 Desactivar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-accion btn-activar" onclick="cambiarEstado(<?php echo $m['id']; ?>, 1)">
                                                🟢 Activar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- MODAL PARA VER MATERIA -->
<div id="modalVer" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>👁️ Detalles de la Materia</h3>
            <span class="close" onclick="cerrarModal('modalVer')">&times;</span>
        </div>
        <div class="form-group"><strong>Nombre:</strong> <p id="ver_nombre"></p></div>
        <div class="form-group"><strong>Clave:</strong> <p id="ver_clave"></p></div>
        <div class="form-group"><strong>Créditos:</strong> <p id="ver_creditos"></p></div>
    </div>
</div>

<!-- MODAL PARA EDITAR MATERIA -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>✏️ Editar Materia</h3>
            <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
        </div>
        <form id="formEditar">
            <input type="hidden" id="edit_id">
            <div class="form-group"><label>Nombre:</label><input type="text" id="edit_nombre" required></div>
            <div class="form-group"><label>Clave:</label><input type="text" id="edit_clave" required></div>
            <div class="form-group"><label>Créditos:</label><input type="number" id="edit_creditos" required></div>
            <button type="button" class="btn-primary" onclick="guardarEdicion()">💾 Guardar Cambios</button>
            <button type="button" class="btn-secondary" onclick="cerrarModal('modalEditar')">❌ Cancelar</button>
        </form>
    </div>
</div>

<script>
// --- FUNCIONES DE UI ---
function mostrarFormulario() { document.getElementById('formMateria').style.display = 'block'; }
function ocultarFormulario() { document.getElementById('formMateria').style.display = 'none'; }
function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }

// --- VER MATERIA ---
function verMateria(nombre, clave, creditos) {
    document.getElementById('ver_nombre').innerText = nombre;
    document.getElementById('ver_clave').innerText = clave;
    document.getElementById('ver_creditos').innerText = creditos;
    document.getElementById('modalVer').style.display = 'flex';
}

// --- EDITAR MATERIA ---
function abrirModalEditar(id, nombre, clave, creditos) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_clave').value = clave;
    document.getElementById('edit_creditos').value = creditos;
    document.getElementById('modalEditar').style.display = 'flex';
}

function guardarEdicion() {
    const id = document.getElementById('edit_id').value;
    const data = {
        nombre: document.getElementById('edit_nombre').value,
        clave: document.getElementById('edit_clave').value,
        creditos: document.getElementById('edit_creditos').value
    };

    // Petición PUT a tu API
    fetch(`../api/materias.php?id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') { location.reload(); }
        else { alert("Error al editar"); }
    });
}

// --- ELIMINAR MATERIA ---
function eliminarMateria(id) {
    if(confirm("¿Estás seguro de eliminar esta materia?")) {
        // Petición DELETE a tu API
        fetch(`../api/materias.php?id=${id}`, { method: 'DELETE' })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') { location.reload(); }
            else { alert("Error al eliminar"); }
        });
    }
}

// --- DESACTIVAR / ACTIVAR (Cambiar Estado) ---
function cambiarEstado(id, nuevoEstado) {
    // Petición PUT a tu API (necesitamos agregar la columna 'estado' a la tabla)
    fetch(`../api/materias.php?id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ estado: nuevoEstado }) // Solo enviamos el campo estado
    })
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') { location.reload(); }
        else { alert("Error al cambiar estado. Asegúrate de tener el endpoint PUT actualizado."); }
    });
}
</script>
</body>
</html>