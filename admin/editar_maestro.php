<?php
session_start();
if($_SESSION['rol'] != 'admin') { 
    header("Location: ../login.php"); 
    exit(); 
}
require_once '../config/db.php';

$id = $_GET['id'] ?? 0;
$mensaje = '';

$stmt = $pdo->prepare("SELECT u.*, m.especialidad FROM usuarios u LEFT JOIN maestros m ON u.id = m.id WHERE u.id = ? AND u.rol = 'docente'");
$stmt->execute([$id]);
$maestro = $stmt->fetch();

if(!$maestro) { 
    header("Location: gestion_maestros.php"); 
    exit(); 
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, estado = ? WHERE id = ?");
    $stmt->execute([$_POST['nombre'], $_POST['apellido'], $_POST['email'], $_POST['telefono'], $_POST['estado'], $id]);
    
    $stmt = $pdo->prepare("INSERT INTO maestros (id, especialidad) VALUES (?, ?) ON DUPLICATE KEY UPDATE especialidad = ?");
    $stmt->execute([$id, $_POST['especialidad'], $_POST['especialidad']]);
    
    $mensaje = "✅ Maestro actualizado correctamente";
    
    $stmt = $pdo->prepare("SELECT u.*, m.especialidad FROM usuarios u LEFT JOIN maestros m ON u.id = m.id WHERE u.id = ?");
    $stmt->execute([$id]);
    $maestro = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Editar Maestro</title>
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
            <h1>✏️ Editar Maestro</h1>
            <?php if($mensaje): ?><div class="alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group"><label>Nombre:</label><input type="text" name="nombre" value="<?php echo htmlspecialchars($maestro['nombre']); ?>" required></div>
                <div class="form-group"><label>Apellido:</label><input type="text" name="apellido" value="<?php echo htmlspecialchars($maestro['apellido'] ?? ''); ?>"></div>
                <div class="form-group"><label>Email:</label><input type="email" name="email" value="<?php echo htmlspecialchars($maestro['email']); ?>" required></div>
                <div class="form-group"><label>Teléfono:</label><input type="text" name="telefono" value="<?php echo $maestro['telefono']; ?>"></div>
                <div class="form-group"><label>Especialidad:</label><input type="text" name="especialidad" value="<?php echo htmlspecialchars($maestro['especialidad'] ?? ''); ?>" placeholder="Ej: Matemáticas, Física"></div>
                <div class="form-group"><label>Estado:</label><select name="estado"><option value="vigente" <?php echo $maestro['estado'] == 'vigente' ? 'selected' : ''; ?>>Vigente</option><option value="inactivo" <?php echo $maestro['estado'] == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option></select></div>
                <button type="submit" class="btn-primary">💾 Guardar Cambios</button>
                <a href="gestion_maestros.php" class="btn-secondary">← Volver</a>
            </form>
        </div>
    </div>
</body>
</html>