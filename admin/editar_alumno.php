<?php
session_start();
if($_SESSION['rol'] != 'admin') { 
    header("Location: ../login.php"); 
    exit(); 
}
require_once '../config/db.php';

$id = $_GET['id'] ?? 0;
$mensaje = '';

$stmt = $pdo->prepare("SELECT u.*, a.matricula, a.grado, a.grupo_id FROM usuarios u LEFT JOIN alumnos a ON u.id = a.id WHERE u.id = ? AND u.rol = 'alumno'");
$stmt->execute([$id]);
$alumno = $stmt->fetch();

if(!$alumno) { 
    header("Location: gestion_alumnos.php"); 
    exit(); 
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, estado = ? WHERE id = ?");
    $stmt->execute([$_POST['nombre'], $_POST['apellido'], $_POST['email'], $_POST['telefono'], $_POST['estado'], $id]);
    
    $stmt = $pdo->prepare("INSERT INTO alumnos (id, matricula, grado, grupo_id) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE matricula = ?, grado = ?, grupo_id = ?");
    $stmt->execute([$id, $_POST['matricula'], $_POST['grado'], $_POST['grupo_id'], $_POST['matricula'], $_POST['grado'], $_POST['grupo_id']]);
    
    $mensaje = "✅ Alumno actualizado correctamente";
    
    $stmt = $pdo->prepare("SELECT u.*, a.matricula, a.grado, a.grupo_id FROM usuarios u LEFT JOIN alumnos a ON u.id = a.id WHERE u.id = ?");
    $stmt->execute([$id]);
    $alumno = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Editar Alumno</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; }
        .card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-primary { background: #2c5f2d; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .btn-secondary { background: #6c757d; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
        .alert-success { background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        h1 { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>✏️ Editar Alumno</h1>
            <?php if($mensaje): ?><div class="alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group"><label>Nombre:</label><input type="text" name="nombre" value="<?php echo htmlspecialchars($alumno['nombre']); ?>" required></div>
                <div class="form-group"><label>Apellido:</label><input type="text" name="apellido" value="<?php echo htmlspecialchars($alumno['apellido'] ?? ''); ?>"></div>
                <div class="form-group"><label>Email:</label><input type="email" name="email" value="<?php echo htmlspecialchars($alumno['email']); ?>" required></div>
                <div class="form-group"><label>Teléfono:</label><input type="text" name="telefono" value="<?php echo $alumno['telefono']; ?>"></div>
                <div class="form-group"><label>Matrícula:</label><input type="text" name="matricula" value="<?php echo $alumno['matricula']; ?>"></div>
                <div class="form-group"><label>Grado:</label><input type="text" name="grado" value="<?php echo $alumno['grado']; ?>" placeholder="Ej: 4to Semestre"></div>
                <div class="form-group"><label>Grupo ID:</label><input type="number" name="grupo_id" value="<?php echo $alumno['grupo_id']; ?>"></div>
                <div class="form-group"><label>Estado:</label><select name="estado"><option value="vigente" <?php echo $alumno['estado'] == 'vigente' ? 'selected' : ''; ?>>Vigente</option><option value="inactivo" <?php echo $alumno['estado'] == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option></select></div>
                <button type="submit" class="btn-primary">💾 Guardar Cambios</button>
                <a href="gestion_alumnos.php" class="btn-secondary">← Volver</a>
            </form>
        </div>
    </div>
</body>
</html>