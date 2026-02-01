<?php
// usuario/reservas.php - Lista de reservas del usuario

session_start();

// Verificar sesión
if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=usuario/reservas.php');
    exit();
}

// Verificar que sea usuario normal
if(isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header('Location: ../admin/dashboard.php');
    exit();
}

require_once '../includes/config.php';

$user_id = $_SESSION['user_id'];

// Obtener parámetros de filtro
$filter = $_GET['filter'] ?? 'todas';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Construir consulta con filtros
$where_conditions = ["r.usuario_id = ?"];
$params = [$user_id];

if($filter !== 'todas') {
    $where_conditions[] = "r.estado = ?";
    $params[] = $filter;
}

if(!empty($search)) {
    $where_conditions[] = "(d.nombre LIKE ? OR r.codigo_reserva LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = implode(' AND ', $where_conditions);

// Obtener total de reservas
$sql_total = "SELECT COUNT(*) as total 
              FROM reservas r 
              LEFT JOIN destinos d ON r.destino_id = d.id 
              WHERE $where_sql";
              
$stmt_total = $pdo->prepare($sql_total);
$stmt_total->execute($params);
$total_reservas = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_reservas / $limit);

// Obtener reservas paginadas - CORREGIDO: usando campos reales de la tabla reservas
$sql_reservas = "SELECT r.*, d.nombre as destino_nombre, d.imagen_principal, d.ubicacion
                 FROM reservas r 
                 LEFT JOIN destinos d ON r.destino_id = d.id 
                 WHERE $where_sql 
                 ORDER BY r.fecha_reserva DESC 
                 LIMIT $limit OFFSET $offset";

$stmt_reservas = $pdo->prepare($sql_reservas);
$stmt_reservas->execute($params);
$reservas = $stmt_reservas->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas por estado
$estados = [
    'todas' => 'Todas',
    'pendiente' => 'Pendientes',
    'confirmada' => 'Confirmadas', 
    'cancelada' => 'Canceladas'
];

$page_title = 'Mis Reservas';
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
        
        /* Estadísticas */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
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
        
        /* Filtros */
        .filters-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-tab:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .filter-tab.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        /* Lista de reservas */
        .reservas-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .reservas-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .reserva-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .reserva-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .reserva-imagen {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .reserva-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .reserva-imagen:hover img {
            transform: scale(1.05);
        }
        
        .reserva-info {
            flex-grow: 1;
        }
        
        .reserva-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.8rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .reserva-codigo {
            font-size: 0.9rem;
            color: #666;
            background: var(--light-color);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-family: monospace;
            display: inline-block;
        }
        
        .reserva-estado {
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-block;
        }
        
        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .estado-confirmada {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .estado-cancelada {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .reserva-destino {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .reserva-destino a {
            color: inherit;
            text-decoration: none;
        }
        
        .reserva-destino a:hover {
            color: var(--primary-color);
        }
        
        .reserva-detalles {
            display: flex;
            gap: 2rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .reserva-detalle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }
        
        .reserva-detalle i {
            color: var(--primary-color);
        }
        
        .reserva-acciones {
            display: flex;
            gap: 0.8rem;
        }
        
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
        
        .btn-secondary {
            background: var(--light-color);
            color: var(--dark-color);
            border: 2px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            border-color: var(--primary-color);
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-info {
            background: var(--accent-color);
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .no-reservas {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-reservas i {
            font-size: 3rem;
            color: var(--border-color);
            margin-bottom: 1rem;
        }
        
        .no-reservas h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .no-reservas p {
            margin-bottom: 1.5rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            border-color: var(--primary-color);
            background: var(--light-color);
        }
        
        .pagination .active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .actions-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
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
            
            .reserva-card {
                flex-direction: column;
                text-align: center;
            }
            
            .reserva-imagen {
                width: 100%;
                height: 200px;
            }
            
            .reserva-header {
                flex-direction: column;
                align-items: center;
            }
            
            .reserva-acciones {
                justify-content: center;
            }
            
            .reserva-detalles {
                justify-content: center;
            }
            
            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .reserva-acciones {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .filter-tabs {
                flex-direction: column;
            }
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
                <?php 
                $user_avatar = $_SESSION['user_avatar'] ?? '';
                $user_name = $_SESSION['user_nombre'] ?? 'Usuario';
                $user_email = $_SESSION['user_email'] ?? '';
                ?>
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
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="perfil.php" class="nav-link">
                    <i class="fas fa-user-edit"></i>
                    <span>Mi Perfil</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="reservas.php" class="nav-link active">
                    <i class="fas fa-calendar-check"></i>
                    <span>Mis Reservas</span>
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
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> Mis Reservas</h1>
            <span>Gestiona todas tus reservas y viajes planificados</span>
        </div>
        
        <!-- Botones de acción -->
        <div class="actions-container">
            <a href="../destinos.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Reserva
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
        
        <!-- Estadísticas -->
        <div class="stats-container">
            <?php 
            // Obtener estadísticas por estado
            $sql_stats = "SELECT estado, COUNT(*) as total FROM reservas WHERE usuario_id = ? GROUP BY estado";
            $stmt_stats = $pdo->prepare($sql_stats);
            $stmt_stats->execute([$user_id]);
            $stats = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);
            
            $estados_stats = [
                'todas' => $total_reservas,
                'pendiente' => 0,
                'confirmada' => 0,
                'cancelada' => 0
            ];
            
            foreach($stats as $stat) {
                if(isset($estados_stats[$stat['estado']])) {
                    $estados_stats[$stat['estado']] = $stat['total'];
                }
            }
            ?>
            
            <div class="stat-card">
                <h3><?php echo $estados_stats['todas']; ?></h3>
                <p>Total Reservas</p>
            </div>
            <div class="stat-card" style="border-top-color: #ffc107;">
                <h3><?php echo $estados_stats['pendiente']; ?></h3>
                <p>Pendientes</p>
            </div>
            <div class="stat-card" style="border-top-color: #17a2b8;">
                <h3><?php echo $estados_stats['confirmada']; ?></h3>
                <p>Confirmadas</p>
            </div>
            <div class="stat-card" style="border-top-color: #dc3545;">
                <h3><?php echo $estados_stats['cancelada']; ?></h3>
                <p>Canceladas</p>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters-container">
            <div class="filter-tabs">
                <?php foreach($estados as $key => $label): ?>
                    <a href="?filter=<?php echo $key; ?>&search=<?php echo urlencode($search); ?>" 
                       class="filter-tab <?php echo $filter === $key ? 'active' : ''; ?>">
                        <i class="fas fa-<?php 
                            switch($key) {
                                case 'pendiente': echo 'clock'; break;
                                case 'confirmada': echo 'check-circle'; break;
                                case 'cancelada': echo 'times-circle'; break;
                                default: echo 'list';
                            }
                        ?>"></i>
                        <?php echo $label; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <form method="GET" action="" class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Buscar por destino o código de reserva..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                <button type="submit" style="display: none;">Buscar</button>
            </form>
        </div>
        
        <!-- Lista de reservas -->
        <div class="reservas-container">
            <?php if(empty($reservas)): ?>
                <div class="no-reservas">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No se encontraron reservas</h3>
                    <p><?php echo $filter !== 'todas' ? "No tienes reservas " . strtolower($estados[$filter]) . "." : "Aún no has realizado ninguna reserva."; ?></p>
                    <a href="../destinos.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Explorar Destinos
                    </a>
                </div>
            <?php else: ?>
                <div class="reservas-list">
                    <?php foreach($reservas as $reserva): 
                        // CORRECCIÓN: Usar campos correctos de la tabla reservas
                        $cantidad_personas = $reserva['cantidad_personas'];
                        $precio_total = $reserva['precio_total'];
                        $fecha_viaje = $reserva['fecha_viaje'];
                    ?>
                        <div class="reserva-card">
                            <div class="reserva-imagen">
                                <?php if(!empty($reserva['imagen_principal'])): ?>
                                    <img src="../uploads/destinos/<?php echo htmlspecialchars($reserva['imagen_principal']); ?>" 
                                         alt="<?php echo htmlspecialchars($reserva['destino_nombre']); ?>">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: var(--light-color); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-image" style="font-size: 2rem; color: var(--border-color);"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="reserva-info">
                                <div class="reserva-header">
                                    <div>
                                        <h2 class="reserva-destino">
                                            <a href="../destino-detalle.php?id=<?php echo $reserva['destino_id']; ?>">
                                                <?php echo htmlspecialchars($reserva['destino_nombre'] ?? 'Destino no especificado'); ?>
                                            </a>
                                        </h2>
                                        <span class="reserva-codigo">
                                            <i class="fas fa-hashtag"></i>
                                            <?php echo htmlspecialchars($reserva['codigo_reserva'] ?? 'RES-' . $reserva['id']); ?>
                                        </span>
                                    </div>
                                    
                                    <span class="reserva-estado estado-<?php echo $reserva['estado']; ?>">
                                        <?php echo ucfirst($reserva['estado']); ?>
                                    </span>
                                </div>
                                
                                <div class="reserva-detalles">
                                    <div class="reserva-detalle">
                                        <i class="fas fa-calendar"></i>
                                        <span><strong>Reserva:</strong> <?php echo $reserva['fecha_reserva'] ? date('d/m/Y', strtotime($reserva['fecha_reserva'])) : 'No especificada'; ?></span>
                                    </div>
                                    
                                    <div class="reserva-detalle">
                                        <i class="fas fa-users"></i>
                                        <span><strong>Personas:</strong> <?php echo $cantidad_personas; ?></span>
                                    </div>
                                    
                                    <div class="reserva-detalle">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span><strong>Total:</strong> $<?php echo number_format($precio_total, 2); ?></span>
                                    </div>
                                    
                                    <?php if(!empty($fecha_viaje)): ?>
                                    <div class="reserva-detalle">
                                        <i class="fas fa-plane-departure"></i>
                                        <span><strong>Viaje:</strong> <?php echo date('d/m/Y', strtotime($fecha_viaje)); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="reserva-acciones">
                                    <a href="detalle_reserva.php?id=<?php echo $reserva['id']; ?>" 
                                       class="btn btn-info btn-small">
                                        <i class="fas fa-eye"></i> Ver Detalles
                                    </a>
                                    
                                    <?php if($reserva['estado'] == 'pendiente'): ?>
                                        <a href="pagar_reserva.php?id=<?php echo $reserva['id']; ?>" 
                                           class="btn btn-success btn-small">
                                            <i class="fas fa-credit-card"></i> Pagar
                                        </a>
                                        <a href="cancelar_reserva.php?id=<?php echo $reserva['id']; ?>" 
                                           class="btn btn-danger btn-small"
                                           onclick="return confirm('¿Estás seguro de cancelar esta reserva?');">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    <?php elseif($reserva['estado'] == 'confirmada'): ?>
                                        <a href="modificar_reserva.php?id=<?php echo $reserva['id']; ?>" 
                                           class="btn btn-secondary btn-small">
                                            <i class="fas fa-edit"></i> Modificar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginación -->
                <?php if($total_paginas > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page-1; ?>">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_paginas, $page + 2);
                    
                    for($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_paginas): ?>
                        <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page+1; ?>">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Búsqueda en tiempo real
        document.querySelector('[name="search"]').addEventListener('keyup', function(e) {
            if(e.key === 'Enter') {
                this.form.submit();
            }
        });
        
        // Confirmación para acciones
        document.querySelectorAll('.btn-danger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if(!confirm('¿Estás seguro de realizar esta acción?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Responsive sidebar toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
        
        // Auto-submit search on change
        document.querySelector('[name="filter"]')?.addEventListener('change', function() {
            this.form.submit();
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const isMobile = window.innerWidth <= 768;
            
            if(isMobile && sidebar.classList.contains('active') && 
               !event.target.closest('.sidebar')) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>