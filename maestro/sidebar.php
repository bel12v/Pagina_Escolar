<aside class="sidebar">
    <div class="sidebar-header">
<img src="../assets/images/logo.jpeg" alt="COBACH BC" class="logo-img" onerror="this.src='https://placehold.co/200x60/2c5f2d/white?text=COBACH+BC'">      <p>Maestro</p>
    </div>
    <nav class="sidebar-nav" style="max-height: calc(100vh - 200px); overflow-y: auto;">
        <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <span>🏠</span> <span>Inicio</span>
        </a>
        <a href="horario.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'horario.php' ? 'active' : ''; ?>">
            <span>📅</span> <span>Horario y Materias</span>
        </a>
        <a href="alumnos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'alumnos.php' ? 'active' : ''; ?>">
            <span>👨‍🎓</span> <span>Alumnos y Calificaciones</span>
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