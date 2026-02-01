<?php
// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Obtener información del usuario
$user_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['usuario_rol'] ?? $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['usuario_nombre'] ?? $_SESSION['user_name'] ?? 'Administrador';

// Determinar página activa
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Botón Hamburguesa para Móviles -->
<button class="hamburger-menu" id="hamburgerBtn" aria-label="Abrir menú">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay para cerrar sidebar en móviles -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <?php
            // Mostrar logo si existe
            $logo_path = '../uploads/config/logo.png';
            if (file_exists($logo_path)):
            ?>
                <img src="<?php echo $logo_path; ?>" alt="Logo" class="sidebar-logo">
            <?php else: ?>
                <div class="logo-placeholder">
                    <i class="fas fa-mountain"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <h4><?php echo htmlspecialchars($user_name); ?></h4>
            <span class="user-role"><?php echo getRoleName($user_role); ?></span>
        </div>
    </div>

    <div class="sidebar-menu">
        <!-- Dashboard -->
        <div class="menu-section">
            <div class="menu-title">Principal</div>
            <a href="dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Gestión de Contenido -->
        <div class="menu-section">
            <div class="menu-title">Gestión de Contenido</div>
            <a href="gestion-destinos.php" class="menu-item <?php echo $current_page == 'gestion-destinos.php' ? 'active' : ''; ?>">
                <i class="fas fa-map-marker-alt"></i>
                <span>Destinos</span>
            </a>
            <a href="gestion-actividades.php" class="menu-item <?php echo $current_page == 'gestion-actividades.php' ? 'active' : ''; ?>">
                <i class="fas fa-hiking"></i>
                <span>Actividades</span>
            </a>
            <a href="gestion-galeria.php" class="menu-item <?php echo $current_page == 'gestion-galeria.php' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i>
                <span>Galería</span>
            </a>
            <a href="gestion-resenas.php" class="menu-item <?php echo $current_page == 'gestion-resenas.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i>
                <span>Reseñas</span>
            </a>
        </div>

        <!-- Gestión de Usuarios -->
        <div class="menu-section">
            <div class="menu-title">Usuarios</div>
            <a href="gestion-usuarios.php" class="menu-item <?php echo $current_page == 'gestion-usuarios.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Usuarios</span>
            </a>
            <a href="gestion-reservas.php" class="menu-item <?php echo $current_page == 'gestion-reservas.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Reservas</span>
            </a>
        </div>

        <!-- Configuración -->
        <div class="menu-section">
            <div class="menu-title">Configuración</div>
            <a href="configuracion.php" class="menu-item <?php echo $current_page == 'configuracion.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Configuración del Sitio</span>
            </a>
            <a href="backup.php" class="menu-item <?php echo $current_page == 'backup.php' ? 'active' : ''; ?>">
                <i class="fas fa-database"></i>
                <span>Backup</span>
            </a>
            <?php if ($user_role == 'superadmin'): ?>
                <a href="logs.php" class="menu-item <?php echo $current_page == 'logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Logs del Sistema</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Enlaces Rápidos -->
        <div class="menu-section">
            <div class="menu-title">Enlaces Rápidos</div>
            <a href="../index.php" target="_blank" class="menu-item">
                <i class="fas fa-external-link-alt"></i>
                <span>Ver Sitio Web</span>
            </a>
            <a href="estadisticas.php" class="menu-item <?php echo $current_page == 'estadisticas.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Estadísticas</span>
            </a>
            <a href="../contacto.php" class="menu-item">
                <i class="fas fa-envelope"></i>
                <span>Mensajes de Contacto</span>
            </a>
        </div>

        <!-- Cerrar Sesión -->
        <div class="menu-section logout-section">
            <a href="../logout.php" class="menu-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="system-info">
            <span class="version">v1.0.0</span>
            <span class="status active">● En línea</span>
        </div>
    </div>
</div>

<style>
    /* Estilos del Sidebar */
    .sidebar {
        width: 280px;
        background: linear-gradient(180deg, #1a2b3c 0%, #2c3e50 100%);
        color: white;
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
    }

    .sidebar-header {
        padding: 25px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
    }

    .logo-container {
        margin-bottom: 15px;
    }

    .sidebar-logo {
        max-width: 120px;
        max-height: 60px;
        object-fit: contain;
    }

    .logo-placeholder {
        width: 80px;
        height: 80px;
        margin: 0 auto;
        background: linear-gradient(135deg, #2E8B57 0%, #267349 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
    }

    .user-info h4 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
    }

    .user-role {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.7);
        background: rgba(255, 255, 255, 0.1);
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
        margin-top: 8px;
    }

    .sidebar-menu {
        flex: 1;
        overflow-y: auto;
        padding: 20px 0;
    }

    .menu-section {
        margin-bottom: 25px;
    }

    .menu-title {
        padding: 0 20px 10px;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255, 255, 255, 0.5);
        font-weight: 600;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        transition: all 0.3s;
        border-left: 4px solid transparent;
        margin: 2px 0;
    }

    .menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-left-color: rgba(46, 139, 87, 0.5);
    }

    .menu-item.active {
        background: rgba(46, 139, 87, 0.2);
        color: white;
        border-left-color: #2E8B57;
    }

    .menu-item i {
        width: 24px;
        text-align: center;
        font-size: 1.1rem;
    }

    .menu-item span {
        font-size: 0.95rem;
        font-weight: 500;
    }

    .logout-section {
        margin-top: auto;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .menu-item.logout {
        color: #ff6b6b;
    }

    .menu-item.logout:hover {
        background: rgba(255, 107, 107, 0.1);
        border-left-color: #ff6b6b;
    }

    .sidebar-footer {
        padding: 15px 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.1);
    }

    .system-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .version {
        background: rgba(255, 255, 255, 0.1);
        padding: 4px 8px;
        border-radius: 4px;
    }

    .status {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .status.active {
        color: #4cd964;
    }

    /* Scrollbar personalizado */
    .sidebar-menu::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-menu::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
    }

    .sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    .sidebar-menu::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* Botón Hamburguesa */
    .hamburger-menu {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1100;
        background: linear-gradient(135deg, #2E8B57 0%, #267349 100%);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px;
        cursor: pointer;
        font-size: 1.3rem;
        width: 50px;
        height: 50px;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .hamburger-menu:hover {
        background: linear-gradient(135deg, #267349 0%, #1e5a36 100%);
        transform: scale(1.05);
    }

    .hamburger-menu:active {
        transform: scale(0.95);
    }

    /* Overlay para cerrar sidebar en móvil */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }

    /* Responsive - Tablets y menores */
    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .hamburger-menu {
            display: flex;
        }

        .main-content {
            margin-left: 0 !important;
        }
    }

    /* Responsive - Móviles */
    @media (max-width: 768px) {
        .sidebar {
            width: 280px;
        }

        .sidebar-header {
            padding: 20px 15px;
        }

        .logo-placeholder {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }

        .user-info h4 {
            font-size: 1rem;
        }

        .menu-item {
            padding: 10px 15px;
        }

        .menu-item i {
            font-size: 1rem;
        }

        .menu-item span {
            font-size: 0.9rem;
        }

        .menu-title {
            font-size: 0.75rem;
        }
    }

    /* Responsive - Móviles pequeños */
    @media (max-width: 480px) {
        .sidebar {
            width: 260px;
        }

        .sidebar-header {
            padding: 15px 10px;
        }

        .logo-placeholder {
            width: 50px;
            height: 50px;
            font-size: 1.3rem;
        }

        .user-info h4 {
            font-size: 0.95rem;
        }

        .user-role {
            font-size: 0.75rem;
            padding: 3px 10px;
        }

        .menu-item {
            padding: 9px 12px;
            gap: 10px;
        }

        .menu-item i {
            width: 20px;
            font-size: 0.95rem;
        }

        .menu-item span {
            font-size: 0.85rem;
        }

        .menu-title {
            font-size: 0.7rem;
            padding: 0 12px 8px;
        }

        .sidebar-footer {
            padding: 12px 15px;
        }

        .system-info {
            font-size: 0.75rem;
        }
    }
</style>

<script>
    // JavaScript para controlar el sidebar en móviles
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        const hamburger = document.querySelector('.hamburger-menu');

        // Función para abrir sidebar
        function openSidebar() {
            if (sidebar) sidebar.classList.add('active');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Función para cerrar sidebar
        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Toggle sidebar con botón hamburguesa
        if (hamburger) {
            hamburger.addEventListener('click', function(e) {
                e.stopPropagation();
                if (sidebar && sidebar.classList.contains('active')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        // Cerrar sidebar al hacer clic en overlay
        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        // Cerrar sidebar al hacer clic en un enlace (solo en móvil)
        const menuItems = document.querySelectorAll('.sidebar .menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    closeSidebar();
                }
            });
        });

        // Cerrar sidebar al redimensionar a escritorio
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth > 992) {
                    closeSidebar();
                }
            }, 250);
        });

        // Prevenir scroll del body cuando sidebar está abierto
        if (sidebar) {
            sidebar.addEventListener('touchmove', function(e) {
                e.stopPropagation();
            }, {
                passive: true
            });
        }
    });
</script>