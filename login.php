<?php
// Iniciar sesión solo si no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';

// Si ya está logueado, redirigir
if(isset($_SESSION['user_id'])) {
    if($_SESSION['rol'] == 'admin') header("Location: admin/");
    elseif($_SESSION['rol'] == 'docente') header("Location: maestro/");
    elseif($_SESSION['rol'] == 'alumno') header("Location: alumno/");
    exit();
}

$error_login = '';
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = MD5($_POST['password']);
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND password = ? AND estado = 'vigente'");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();
    
    if($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['apellido'] = $user['apellido'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['foto'] = $user['foto'];
        
        if($user['rol'] == 'admin') header("Location: admin/");
        elseif($user['rol'] == 'docente') header("Location: maestro/");
        elseif($user['rol'] == 'alumno') header("Location: alumno/");
        exit();
    } else {
        $error_login = "Email o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COBACH BC - Sistema Escolar</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a472a 0%, #0d2818 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            height: 80px;
            width: auto;
            margin-bottom: 15px;
        }

        .logo h1 {
            color: #1a472a;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .logo p {
            color: #666;
            font-size: 12px;
        }

        .subtitle {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e8e8e8;
        }

        .subtitle h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .subtitle p {
            color: #888;
            font-size: 12px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1a472a;
            box-shadow: 0 0 5px rgba(26,71,42,0.3);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: #1a472a;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: #0d2818;
            transform: translateY(-2px);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 13px;
        }

        .info {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e8e8e8;
            text-align: center;
        }

        .info p {
            color: #888;
            font-size: 11px;
            margin: 5px 0;
        }

        .info strong {
            color: #1a472a;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <img src="assets/images/logo.jpeg" alt="COBACH BC" onerror="this.src='https://placehold.co/80x80/1a472a/white?text=COBACH'">
        </div>

        <div class="subtitle">
            <h2>INGRESA TUS CREDENCIALES</h2>
        </div>

        <?php if($error_login): ?>
            <div class="error-message">
                ⚠️ <?php echo $error_login; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>📧 Correo Electrónico</label>
                <input type="email" name="email" required placeholder="ejemplo@cobach.edu">
            </div>
            <div class="form-group">
                <label>🔒 Contraseña</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" name="login" class="btn-login">Iniciar Sesión</button>
        </form>
</body>
</html>