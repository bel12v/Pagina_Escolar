<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/logo.jpeg" alt="COBACH BC" class="logo-img" onerror="this.src='https://placehold.co/200x60/2c5f2d/white?text=COBACH+BC'">
        <p>Administrador</p>
    </div>
    <nav class="sidebar-nav" style="max-height: calc(100vh - 200px); overflow-y: auto;">
        <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <span>🏠</span> <span>Inicio</span>
        </a>
        <a href="crear_usuario.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'crear_usuario.php' ? 'active' : ''; ?>">
            <span>➕</span> <span>Crear Usuario</span>
        </a>
        <a href="gestion_usuarios.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_usuarios.php' ? 'active' : ''; ?>">
            <span>👥</span> <span>Usuarios</span>
        </a>
        <a href="gestion_alumnos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_alumnos.php' ? 'active' : ''; ?>">
            <span>👨‍🎓</span> <span>Alumnos</span>
        </a>
        <a href="gestion_maestros.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_maestros.php' ? 'active' : ''; ?>">
            <span>👨‍🏫</span> <span>Maestros</span>
        </a>
        <a href="materias.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'materias.php' ? 'active' : ''; ?>">
            <span>📚</span> <span>Materias</span>
        </a>
        <a href="grupos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'grupos.php' ? 'active' : ''; ?>">
            <span>👥</span> <span>Grupos</span>
        </a>
        <a href="asignar_materias.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'asignar_materias.php' ? 'active' : ''; ?>">
            <span>📋</span> <span>Asignar Materias</span>
        </a>
        <a href="inscribir_alumnos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'inscribir_alumnos.php' ? 'active' : ''; ?>">
            <span>✏️</span> <span>Inscribir Alumnos</span>
        </a>
        <a href="horario_alumno.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'horario_alumno.php' ? 'active' : ''; ?>">
            <span>📅</span> <span>Horario Alumnos</span>
        </a>
        <a href="reinscripciones.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reinscripciones.php' ? 'active' : ''; ?>">
            <span>💰</span> <span>Reinscripciones</span>
        </a>
        <a href="reportes.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>">
            <span>📄</span> <span>Reportes</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <img src="../assets/uploads/<?php echo $_SESSION['foto'] ?? 'default.jpg'; ?>" class="user-avatar" onerror="this.src='../assets/uploads/default.jpg'">
            <div>
                <p><?php echo $_SESSION['nombre']; ?></p>
                <a href="../logout.php" style="color:#f44336;">Cerrar Sesión</a>
            </div>
        </div>
    </div>
</aside>