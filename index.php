<?php
// Si ya está logueado, redirigir a su panel
session_start();
if(isset($_SESSION['user_id'])) {
    if($_SESSION['rol'] == 'admin') header("Location: admin/");
    elseif($_SESSION['rol'] == 'docente') header("Location: maestro/");
    elseif($_SESSION['rol'] == 'alumno') header("Location: alumno/");
    exit();
}

// Manejar intento de login desde la página principal
$error_login = '';
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    require_once 'config/db.php';
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
    <title>COBACH BC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #1a1a2e;
        }
        
        .main-container {
            display: flex;
            min-height: 100vh;
        }
        
        .left-content {
            flex: 1;
            background: white;
            padding: 40px 40px;
            overflow-y: auto;
        }
        
        .right-login {
            width: 480px;
            background: #f8f9fc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            border-left: 1px solid #e8ecf0;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        
        .logo-section {
            margin-bottom: 20px;
        }
        
        .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo-img {
            height: 80px;
            width: auto;
            object-fit: contain;
            border-radius: 16px;
            margin-bottom: 10px;
        }
        
        .logo-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .logo-text p {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .hero-title {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .hero-title h2 {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
            color: #1a1a2e;
        }
        
        .hero-title .highlight {
            color: #2c5f2d;
        }
        
        .notice-card {
            background: #f0f4f8;
            border-radius: 12px;
            padding: 15px 20px;
            margin: 20px 0;
            border-left: 4px solid #2c5f2d;
            text-align: center;
        }
        
        .notice-card p {
            font-size: 0.85rem;
            color: #2c3e50;
            line-height: 1.4;
        }
        
        .notice-card strong {
            color: #2c5f2d;
        }
        
        .description {
            color: #5a6a7a;
            line-height: 1.5;
            margin-bottom: 25px;
            text-align: center;
            font-size: 0.85rem;
        }
        
        /* SECCIÓN PLATAFORMAS - VERSIÓN COMPACTA */
        .platforms-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e8ecf0;
        }
        
        .platforms-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 12px;
            text-align: center;
        }
        
        .platform-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            justify-content: center;
        }
        
        .platform-btn {
            padding: 6px 18px;
            background: #f0f4f8;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            color: #4a5568;
            transition: all 0.3s;
        }
        
        .platform-btn.active {
            background: #2c5f2d;
            color: white;
        }
        
        .platform-iframe {
            background: #f8f9fc;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e8ecf0;
            min-height: 280px;
        }
        
        .platform-iframe iframe {
            width: 100%;
            height: 280px;
            border: none;
        }
        
        .sites-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .sites-message .icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        
        .sites-message h3 {
            color: #1a1a2e;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .sites-message p {
            color: #6c757d;
            margin-bottom: 15px;
            font-size: 0.8rem;
        }
        
        .btn-open-sites {
            display: inline-block;
            background: #2c5f2d;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .btn-open-sites:hover {
            background: #1e3a1e;
            transform: translateY(-2px);
        }
        
        .login-card {
            width: 100%;
            max-width: 360px;
        }
        
        .login-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .login-card .login-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #dce1e8;
            border-radius: 12px;
            font-size: 14px;
            background: white;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #2c5f2d;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #2c5f2d;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .btn-login:hover {
            background: #1e3a1e;
        }
        
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dce1e8;
        }
        
        .divider span {
            background: #f8f9fc;
            padding: 0 10px;
            position: relative;
            font-size: 12px;
            color: #8a99a8;
        }
        
        .btn-google {
            width: 100%;
            padding: 12px;
            background: white;
            border: 1px solid #dce1e8;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #4a5568;
            margin-top: 15px;
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .register-link a {
            color: #2c5f2d;
            text-decoration: none;
        }
        
        @media (max-width: 1000px) {
            .main-container { flex-direction: column; }
            .right-login { width: 100%; border-left: none; border-top: 1px solid #e8ecf0; height: auto; position: relative; }
            .hero-title h2 { font-size: 1.5rem; }
            .logo-img { height: 60px; }
            .platform-iframe { min-height: 220px; }
            .platform-iframe iframe { height: 220px; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="left-content">
            <div class="logo-section">
                <div class="logo">
                    <img src="Logo-OF-Cobach.jpeg" alt="COBACH BC Logo" class="logo-img" onerror="this.src='https://placehold.co/80x80/2c5f2d/white?text=CB'">
                    <div class="logo-text">
                    </div>
                </div>
            </div>
            
            <div class="hero-title">
                <h2>EDUCACIÓN QUE <span class="highlight">CONECTA</span>.<br>
                CONOCIMIENTO QUE <span class="highlight">TRANSFORMA</span></h2>
            </div>
            
            <div class="notice-card">
                <p><strong>🔒 CONOCE TU NUEVO SISTEMA ACADÉMICO</strong><br>
                MÁS SENCILLA, MÁS EFICAZ</p>
            </div>
            
            <div class="description">
                <p>La principal plataforma dedicada a la administración educativa para alumnos, 
                que cuenta con una extensa variedad de servicios que proporcionan un soporte completo
                a lo largo de tu desarrollo profesional.</p>
            </div>
            
            <!-- SECCIÓN PLATAFORMAS COMPACTA -->
            <div class="platforms-section">
                <h3 class="platforms-title">🌐 Conoce nuestras plataformas</h3>
                <div class="platform-buttons">
                    <button class="platform-btn active" onclick="cambiarPlataforma('wix')">Wix</button>
                    <button class="platform-btn" onclick="cambiarPlataforma('sites')">Google Sites</button>
                </div>
                <div class="platform-iframe" id="platformContainer">
                    <iframe id="platformFrame" src="https://l23212445.wixsite.com/conocenos" allowfullscreen></iframe>
                </div>
            </div>
        </div>
        
        <div class="right-login">
            <div class="login-card">
                <h3>Acceso al Sistema</h3>
                <p class="login-subtitle">Ingresa con tus credenciales institucionales</p>
                
                <?php if($error_login): ?>
                    <div class="error-message">⚠️ <?php echo $error_login; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>📧 Correo Electrónico</label>
                        <input type="email" name="email" required placeholder="tu.correo@cobach.edu">
                    </div>
                    <div class="form-group">
                        <label>🔒 Contraseña</label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                    <button type="submit" name="login" class="btn-login">Iniciar Sesión →</button>
                </form>
                
                <div class="divider"><span>o continúa con</span></div>
                <button class="btn-google" disabled>🔵 Confirmar con Google (Próximamente)</button>
                <p class="register-link">¿No tienes cuenta? <a href="#">Crear Cuenta →</a></p>
                <div class="divider"></div>
            </div>
        </div>
    </div>
    
    <script>
        function cambiarPlataforma(tipo) {
            const frame = document.getElementById('platformFrame');
            const container = document.getElementById('platformContainer');
            const buttons = document.querySelectorAll('.platform-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            
            if(tipo === 'wix') {
                frame.style.display = 'block';
                frame.src = 'https://l23212445.wixsite.com/conocenos';
                const sitesMsg = document.querySelector('.sites-message');
                if(sitesMsg) sitesMsg.remove();
                buttons[0].classList.add('active');
            } else {
                frame.style.display = 'none';
                const oldMsg = document.querySelector('.sites-message');
                if(oldMsg) oldMsg.remove();
                
                const sitesMsg = document.createElement('div');
                sitesMsg.className = 'sites-message';
                sitesMsg.innerHTML = `
                    <div class="icon">📁</div>
                    <h3>Portal de Contacto</h3>
                    <p>Google Sites no permite incrustar contenido por seguridad.<br>Abrir en nueva ventana</p>
                    <a href="https://sites.google.com/tectijuana.edu.mx/contacto/inicio" target="_blank" class="btn-open-sites">Abrir Google Sites →</a>
                `;
                container.appendChild(sitesMsg);
                buttons[1].classList.add('active');
            }
        }
    </script>
</body>
</html>