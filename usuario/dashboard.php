<?php
// usuario/dashboard.php - Dashboard principal para usuarios

session_start();

// Verificar sesión
if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=usuario/dashboard.php');
    exit();
}

// Verificar que sea usuario normal
if(isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header('Location: ../admin/dashboard.php');
    exit();
}

require_once '../includes/config.php';

// Obtener información del usuario
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_nombre'] ?? 'Usuario';
$user_email = $_SESSION['user_email'] ?? '';
$user_avatar = $_SESSION['user_avatar'] ?? '';

// Obtener estadísticas del usuario
try {
    // Total de reservas
    $stmt_reservas = $pdo->prepare("SELECT COUNT(*) as total FROM reservas WHERE usuario_id = ?");
    $stmt_reservas->execute([$user_id]);
    $total_reservas = $stmt_reservas->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Reservas activas (pendientes y confirmadas)
    $stmt_activas = $pdo->prepare("SELECT COUNT(*) as activas FROM reservas WHERE usuario_id = ? AND estado IN ('confirmada', 'pendiente')");
    $stmt_activas->execute([$user_id]);
    $reservas_activas = $stmt_activas->fetch(PDO::FETCH_ASSOC)['activas'];
    
    // Favoritos
    $stmt_fav = $pdo->prepare("SELECT COUNT(*) as favoritos FROM favoritos WHERE usuario_id = ?");
    $stmt_fav->execute([$user_id]);
    $total_favoritos = $stmt_fav->fetch(PDO::FETCH_ASSOC)['favoritos'] ?? 0;
    
    // Total gastado en reservas confirmadas
    $stmt_gastado = $pdo->prepare("SELECT SUM(precio_total) as total_gastado FROM reservas WHERE usuario_id = ? AND estado = 'confirmada'");
    $stmt_gastado->execute([$user_id]);
    $total_gastado = $stmt_gastado->fetch(PDO::FETCH_ASSOC)['total_gastado'] ?? 0;
    
} catch (Exception $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
    $total_reservas = 0;
    $reservas_activas = 0;
    $total_favoritos = 0;
    $total_gastado = 0;
}

// Obtener reservas recientes (corregido con campos reales)
$reservas_recientes = [];
try {
    $stmt_recientes = $pdo->prepare("
        SELECT r.*, d.nombre as destino_nombre, d.imagen_principal 
        FROM reservas r 
        LEFT JOIN destinos d ON r.destino_id = d.id 
        WHERE r.usuario_id = ? 
        ORDER BY r.fecha_creacion DESC 
        LIMIT 5
    ");
    $stmt_recientes->execute([$user_id]);
    $reservas_recientes = $stmt_recientes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error obteniendo reservas recientes: " . $e->getMessage());
}

$page_title = 'Dashboard';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Putumayo Turismo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2e8b57;
            --secondary-color: #3cb371;
            --accent-color: #2196f3;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --success-color: #28a745;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --border-color: #dee2e6;
            --font-family: 'Poppins', sans-serif;
            --sidebar-width: 250px;
            --header-height: 70px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            background-color: #f5f7fa;
            min-height: 100vh;
            display: flex;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .user-info {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 1rem;
            border: 3px solid rgba(255,255,255,0.3);
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-details h3 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .user-details p {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .nav-menu {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1.5rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid white;
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: margin-left 0.3s;
        }
        
        .page-header {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-header h1 {
            color: var(--dark-color);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .page-header span {
            color: #666;
            font-size: 0.95rem;
        }
        
        /* Welcome Message */
        .welcome-message {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.5s ease;
        }
        
        .welcome-message h3 {
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-top: 4px solid var(--primary-color);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #666;
            font-weight: 500;
        }
        
        /* Reservas Recientes */
        .recent-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .section-header h2 {
            color: var(--dark-color);
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }
        
        .view-all:hover {
            color: var(--secondary-color);
        }
        
        /* Reservas Table */
        .reservas-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .reservas-table th {
            background: var(--light-color);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            border-bottom: 2px solid var(--border-color);
        }
        
        .reservas-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .reservas-table tr:hover {
            background: var(--light-color);
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmada {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelada {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-btn {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
        .action-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .action-btn i {
            font-size: 2rem;
            color: var(--primary-color);
        }
        
        .action-btn:hover i {
            color: white;
        }
        
        /* No data message */
        .no-data {
            text-align: center;
            padding: 3rem 1rem;
            color: #666;
        }
        
        .no-data i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        /* Button Styles */
        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .reservas-table {
                display: block;
                overflow-x: auto;
            }
            
            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--dark-color);
            cursor: pointer;
            margin-right: 1rem;
        }
        
        @media (max-width: 768px) {
            .mobile-toggle {
                display: block;
            }
        }
        
        /* Table Actions */
        .table-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            transition: color 0.3s;
        }
        
        .action-link:hover {
            color: var(--secondary-color);
        }
        
        /* Reservation Image */
        .reserva-img {
            width: 50px;
            height: 50px;
            border-radius: 5px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-compass"></i> Mi Cuenta</h2>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php if(!empty($user_avatar)): ?>
                    <img src="../uploads/avatars/<?php echo htmlspecialchars($user_avatar); ?>" 
                         alt="<?php echo htmlspecialchars($user_name); ?>">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); font-size: 2rem;">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="user-details">
                <h3><?php echo htmlspecialchars($user_name); ?></h3>
                <p><?php echo htmlspecialchars($user_email); ?></p>
                <p style="margin-top: 0.5rem; font-size: 0.8rem; opacity: 0.7;">Usuario</p>
            </div>
        </div>
        
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="reservas.php" class="nav-link">
                    <i class="fas fa-calendar-check"></i>
                    <span>Mis Reservas</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="perfil.php" class="nav-link">
                    <i class="fas fa-user-edit"></i>
                    <span>Mi Perfil</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="favoritos.php" class="nav-link">
                    <i class="fas fa-heart"></i>
                    <span>Favoritos</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="resenas.php" class="nav-link">
                    <i class="fas fa-star"></i>
                    <span>Mis Reseñas</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../logout.php" class="nav-link" style="color: #ff6b6b;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Mobile Toggle Button -->
        <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <span>Bienvenido a tu panel de control</span>
        </div>
        
        <!-- Welcome Message -->
        <?php if(isset($_GET['welcome']) || isset($_GET['login_success'])): ?>
        <div class="welcome-message" id="welcomeMessage">
            <h3><i class="fas fa-check-circle"></i> ¡Bienvenido de nuevo, <?php echo htmlspecialchars($user_name); ?>!</h3>
            <p>Tu sesión ha sido iniciada correctamente. Gestiona tus reservas y configura tu perfil.</p>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card" onclick="window.location.href='reservas.php'">
                <h3><?php echo $total_reservas; ?></h3>
                <p>Total Reservas</p>
                <small>Ver todas mis reservas</small>
            </div>
            
            <div class="stat-card" onclick="window.location.href='reservas.php?filter=pendiente'">
                <h3><?php echo $reservas_activas; ?></h3>
                <p>Reservas Activas</p>
                <small>Pendientes y confirmadas</small>
            </div>
            
            <div class="stat-card" onclick="window.location.href='favoritos.php'">
                <h3><?php echo $total_favoritos; ?></h3>
                <p>Destinos Favoritos</p>
                <small>Mis lugares favoritos</small>
            </div>
            
            <div class="stat-card" onclick="window.location.href='reservas.php?filter=confirmada'">
                <h3>$<?php echo number_format($total_gastado, 0, ',', '.'); ?></h3>
                <p>Total Gastado</p>
                <small>En reservas confirmadas</small>
            </div>
        </div>
        
        <!-- Reservas Recientes -->
        <div class="recent-section">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> Reservas Recientes</h2>
                <a href="reservas.php" class="view-all">
                    Ver todas <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <?php if(!empty($reservas_recientes)): ?>
                <div class="table-responsive">
                    <table class="reservas-table">
                        <thead>
                            <tr>
                                <th>Destino</th>
                                <th>Fecha</th>
                                <th>Personas</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservas_recientes as $reserva): 
                                $estado = strtolower($reserva['estado'] ?? 'pendiente');
                                $estado_class = 'status-' . $estado;
                                $estado_text = ucfirst($reserva['estado'] ?? 'Pendiente');
                                $fecha_reserva = $reserva['fecha_reserva'] ? date('d/m/Y', strtotime($reserva['fecha_reserva'])) : 'No especificada';
                                $fecha_viaje = $reserva['fecha_viaje'] ? date('d/m/Y', strtotime($reserva['fecha_viaje'])) : 'No especificada';
                                $destino_nombre = htmlspecialchars($reserva['destino_nombre'] ?? 'Destino no especificado');
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo $destino_nombre; ?></strong><br>
                                    <small style="color: #666;"><?php echo $fecha_viaje; ?></small>
                                </td>
                                <td><?php echo $fecha_reserva; ?></td>
                                <td><?php echo $reserva['cantidad_personas'] ?? 1; ?></td>
                                <td>$<?php echo number_format($reserva['precio_total'] ?? 0, 2, ',', '.'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $estado_class; ?>">
                                        <?php echo $estado_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="detalle_reserva.php?id=<?php echo $reserva['id']; ?>" 
                                           class="action-link" title="Ver detalles">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                        <?php if($estado == 'pendiente'): ?>
                                        <a href="pagar_reserva.php?id=<?php echo $reserva['id']; ?>" 
                                           class="action-link" title="Pagar reserva">
                                            <i class="fas fa-credit-card"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No tienes reservas aún</h3>
                    <p>Comienza a planificar tu próxima aventura</p>
                    <a href="../destinos.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Explorar Destinos
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="../destinos.php" class="action-btn">
                <i class="fas fa-map-marked-alt"></i>
                <span>Explorar Destinos</span>
                <small>Descubre nuevos lugares</small>
            </a>
            
            <a href="reservas.php" class="action-btn">
                <i class="fas fa-calendar-alt"></i>
                <span>Ver Mis Reservas</span>
                <small>Gestiona tus viajes</small>
            </a>
            
            <a href="perfil.php" class="action-btn">
                <i class="fas fa-user-cog"></i>
                <span>Configurar Perfil</span>
                <small>Actualiza tu información</small>
            </a>
            
            <a href="favoritos.php" class="action-btn">
                <i class="fas fa-heart"></i>
                <span>Mis Favoritos</span>
                <small>Ver lugares guardados</small>
            </a>
        </div>
    </main>

    <script>
        // Toggle sidebar en móvil
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
        
        // Cerrar sidebar al hacer clic fuera en móvil
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const isMobile = window.innerWidth <= 768;
            
            if(isMobile && sidebar.classList.contains('active') && 
               !event.target.closest('.sidebar') && 
               !event.target.closest('.mobile-toggle')) {
                sidebar.classList.remove('active');
            }
        });
        
        // Auto-ocultar mensaje de bienvenida
        const welcomeMessage = document.getElementById('welcomeMessage');
        if(welcomeMessage) {
            setTimeout(() => {
                welcomeMessage.style.opacity = '0';
                welcomeMessage.style.transition = 'opacity 1s';
                setTimeout(() => {
                    if(welcomeMessage.parentNode) {
                        welcomeMessage.style.display = 'none';
                    }
                }, 1000);
            }, 5000);
        }
        
        // Animar cards al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s, transform 0.5s';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Animar action buttons
            const actionBtns = document.querySelectorAll('.action-btn');
            actionBtns.forEach((btn, index) => {
                btn.style.opacity = '0';
                btn.style.transform = 'translateY(20px)';
                btn.style.transition = 'opacity 0.5s, transform 0.5s';
                
                setTimeout(() => {
                    btn.style.opacity = '1';
                    btn.style.transform = 'translateY(0)';
                }, (statCards.length * 100) + (index * 100));
            });
        });
        
        // Mostrar notificación
        function showNotification(message, type = 'info') {
            // Crear elemento de notificación
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.innerHTML = `
                <div style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#e8f4fd'};
                    color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
                    padding: 1rem 1.5rem;
                    border-radius: 8px;
                    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    animation: slideIn 0.3s ease;
                ">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <div>${message}</div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remover después de 3 segundos
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if(notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, 3000);
            
            // Agregar estilos de animación si no existen
            if(!document.querySelector('#notification-styles')) {
                const style = document.createElement('style');
                style.id = 'notification-styles';
                style.textContent = `
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    
                    @keyframes slideOut {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(100%); opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }
        }
        
        // Confirmar acciones importantes
        function confirmAction(message, url) {
            if(confirm(message)) {
                window.location.href = url;
            }
        }
        
        // Copiar información de reserva
        function copyReservationInfo(reservaId) {
            const text = `Reserva #${reservaId} - Putumayo Turismo\nPara ver detalles: ${window.location.origin}/usuario/detalle_reserva.php?id=${reservaId}`;
            
            navigator.clipboard.writeText(text)
                .then(() => {
                    showNotification('Información copiada al portapapeles', 'success');
                })
                .catch(err => {
                    showNotification('Error al copiar', 'error');
                });
        }
    </script>
</body>
</html>