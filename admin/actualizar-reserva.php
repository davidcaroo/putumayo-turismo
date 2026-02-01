<?php
// admin/actualizar-reserva.php
session_start(); // Asegurar que la sesión esté iniciada
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../includes/config.php';

// Verificar autenticación y permisos
if(!isLoggedIn() || (!hasRole('admin') && !hasRole('superadmin'))) {
    header('Location: ../login.php');
    exit;
}

// Inicializar variables
$reserva = null;
$actividades = [];
$destinos = [];
$servicios_reserva = [];
$servicios_detalles = [];
$total_servicios = 0;
$error = '';
$success = '';
$reserva_id = 0;

// Obtener datos de la reserva específica si hay ID
$reserva_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($reserva_id > 0) {
    try {
        // Obtener información detallada de la reserva
        $stmt = $pdo->prepare("SELECT 
                r.*, 
                d.nombre as destino_nombre,
                d.id as destino_id,
                a.nombre as actividad_nombre,
                a.id as actividad_id,
                a.precio as actividad_precio,
                u.nombre as usuario_nombre,
                u.email as usuario_email,
                u.telefono as usuario_telefono
            FROM reservas r
            LEFT JOIN destinos d ON r.destino_id = d.id
            LEFT JOIN actividades a ON r.actividad_id = a.id
            LEFT JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.id = ?");
        
        $stmt->execute([$reserva_id]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // DEBUG: Descomentar para ver datos
        /*
        echo "<pre>";
        echo "DEBUG - Reserva ID: $reserva_id\n";
        echo "Reserva encontrada: " . ($reserva ? 'SI' : 'NO') . "\n";
        if($reserva) print_r($reserva);
        echo "</pre>";
        */
        
        // Si no existe la reserva, redirigir
        if(!$reserva) {
            $_SESSION['error'] = "Reserva no encontrada (ID: $reserva_id)";
            header('Location: gestion-reservas.php');
            exit;
        }
    } catch(PDOException $e) {
        $error = "Error al cargar la reserva: " . $e->getMessage();
        error_log("Error en actualizar-reserva.php (cargar reserva): " . $e->getMessage());
    }
} else {
    // Si no hay ID, redirigir
    $_SESSION['error'] = "No se proporcionó ID de reserva";
    header('Location: gestion-reservas.php');
    exit;
}

// Obtener todos los destinos para el select
try {
    $stmt = $pdo->prepare("SELECT * FROM destinos WHERE activo = 1 ORDER BY nombre");
    $stmt->execute();
    $destinos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error al cargar destinos: " . $e->getMessage();
    error_log("Error en actualizar-reserva.php (cargar destinos): " . $e->getMessage());
}

// Procesar actualización de reserva
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_reserva'])) {
    $reserva_id = intval($_POST['id']);
    $estado = trim($_POST['estado']);
    $notas_internas = trim($_POST['notas_internas'] ?? '');
    
    // Campos opcionales que pueden actualizarse
    $destino_id = isset($_POST['destino_id']) && !empty($_POST['destino_id']) ? intval($_POST['destino_id']) : $reserva['destino_id'];
    $actividad_id = isset($_POST['actividad_id']) && !empty($_POST['actividad_id']) ? intval($_POST['actividad_id']) : $reserva['actividad_id'];
    $fecha_viaje = isset($_POST['fecha_viaje']) && !empty($_POST['fecha_viaje']) ? trim($_POST['fecha_viaje']) : $reserva['fecha_viaje'];
    $cantidad_personas = isset($_POST['cantidad_personas']) && !empty($_POST['cantidad_personas']) ? intval($_POST['cantidad_personas']) : $reserva['cantidad_personas'];
    
    // Validaciones básicas
    if(empty($estado)) {
        $error = "El estado es obligatorio";
    } elseif($cantidad_personas < 1 || $cantidad_personas > 100) {
        $error = "El número de personas debe ser entre 1 y 100";
    } elseif(empty($fecha_viaje)) {
        $error = "La fecha de viaje es obligatoria";
    } else {
        try {
            // Verificar que la reserva existe
            $stmt = $pdo->prepare("SELECT id FROM reservas WHERE id = ?");
            $stmt->execute([$reserva_id]);
            
            if(!$stmt->fetch()) {
                $error = "La reserva no existe en la base de datos";
            } else {
                // Construir la consulta SQL dinámicamente
                $sql = "UPDATE reservas SET estado = ?, notas_internas = ?, fecha_actualizacion = NOW()";
                $params = [$estado, $notas_internas];
                
                if($destino_id !== null) {
                    $sql .= ", destino_id = ?";
                    $params[] = $destino_id;
                }
                
                if($actividad_id !== null) {
                    $sql .= ", actividad_id = ?";
                    $params[] = $actividad_id;
                }
                
                if($fecha_viaje !== null) {
                    $sql .= ", fecha_viaje = ?";
                    $params[] = $fecha_viaje;
                }
                
                if($cantidad_personas !== null) {
                    $sql .= ", cantidad_personas = ?";
                    $params[] = $cantidad_personas;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $reserva_id;
                
                $stmt = $pdo->prepare($sql);
                if($stmt->execute($params)) {
                    $success = "✅ Reserva #$reserva_id actualizada correctamente";
                    
                    // Registrar en activity log
                    if(isset($_SESSION['user_id'])) {
                        $user_id = $_SESSION['user_id'];
                        try {
                            $stmt_log = $pdo->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)");
                            $stmt_log->execute([
                                $user_id,
                                'update_reserva',
                                "Reserva ID $reserva_id actualizada a estado: $estado"
                            ]);
                        } catch(Exception $e) {
                            error_log("Error al registrar log: " . $e->getMessage());
                        }
                    }
                    
                    // Recargar los datos de la reserva
                    $stmt = $pdo->prepare("SELECT 
                            r.*, 
                            d.nombre as destino_nombre,
                            d.id as destino_id,
                            a.nombre as actividad_nombre,
                            a.id as actividad_id,
                            a.precio as actividad_precio,
                            u.nombre as usuario_nombre,
                            u.email as usuario_email,
                            u.telefono as usuario_telefono
                        FROM reservas r
                        LEFT JOIN destinos d ON r.destino_id = d.id
                        LEFT JOIN actividades a ON r.actividad_id = a.id
                        LEFT JOIN usuarios u ON r.usuario_id = u.id
                        WHERE r.id = ?");
                    $stmt->execute([$reserva_id]);
                    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "❌ Error al ejecutar la actualización";
                    $errorInfo = $stmt->errorInfo();
                    if(isset($errorInfo[2])) {
                        $error .= ": " . $errorInfo[2];
                    }
                }
            }
        } catch(PDOException $e) {
            $error = "❌ Error de base de datos: " . $e->getMessage();
            error_log("Error en actualizar-reserva.php (actualizar): " . $e->getMessage());
        }
    }
}

// Obtener actividades si hay un destino seleccionado
if($reserva && !empty($reserva['destino_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM actividades WHERE destino_id = ? AND activo = 1 ORDER BY nombre");
        $stmt->execute([$reserva['destino_id']]);
        $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error al cargar actividades: " . $e->getMessage());
    }
}

// Decodificar servicios seleccionados
$servicios_reserva = [];
$total_servicios = 0;
if($reserva && !empty($reserva['servicios_seleccionados'])) {
    try {
        $servicios_reserva = json_decode($reserva['servicios_seleccionados'], true);
        
        // Validar que sea un array
        if(!is_array($servicios_reserva)) {
            $servicios_reserva = [];
        }
        
        // Calcular total de servicios si hay servicios
        if(!empty($servicios_reserva)) {
            // Filtrar solo IDs numéricos válidos
            $servicios_ids = array_filter($servicios_reserva, 'is_numeric');
            if(!empty($servicios_ids)) {
                $placeholders = str_repeat('?,', count($servicios_ids) - 1) . '?';
                $stmt = $pdo->prepare("SELECT SUM(precio) as total FROM servicios_reserva WHERE id IN ($placeholders)");
                $stmt->execute($servicios_ids);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $total_servicios = $result['total'] ?? 0;
            }
        }
    } catch(Exception $e) {
        $servicios_reserva = [];
        error_log("Error al decodificar servicios: " . $e->getMessage());
    }
}

// Obtener detalles de servicios para mostrar en tabla
$servicios_detalles = [];
if(!empty($servicios_reserva) && is_array($servicios_reserva)) {
    try {
        $servicios_ids = array_filter($servicios_reserva, 'is_numeric');
        if(!empty($servicios_ids)) {
            $placeholders = str_repeat('?,', count($servicios_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM servicios_reserva WHERE id IN ($placeholders)");
            $stmt->execute($servicios_ids);
            $servicios_detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch(Exception $e) {
        error_log("Error al obtener detalles de servicios: " . $e->getMessage());
    }
}

// Verificar sesión de usuario
if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Reserva - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== VARIABLES Y ESTILOS BASE ===== */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --sidebar-width: 250px;
            --sidebar-collapsed: 60px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* ===== LAYOUT CONTAINER ===== */
        .admin-container {
            display: flex;
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-width);
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }
        
        .sidebar h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            color: #bdc3c7;
            text-decoration: none;
            padding: 12px 20px;
            transition: all 0.3s ease;
            border-bottom: 1px solid #34495e;
            white-space: nowrap;
        }
        
        .sidebar a:hover,
        .sidebar a.active {
            background: #34495e;
            color: white;
            border-left: 4px solid #3498db;
        }
        
        .sidebar i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        
        /* ===== HAMBURGER MENU ===== */
        .hamburger-menu {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px;
            cursor: pointer;
            font-size: 1.5rem;
            width: 50px;
            height: 50px;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .hamburger-menu:hover {
            background: #34495e;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }
        
        .content-header {
            margin-bottom: 30px;
            padding-top: 10px;
        }
        
        .content-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .content-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        /* ===== ALERTAS ===== */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: none;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-close {
            cursor: pointer;
            font-size: 20px;
            color: inherit;
            opacity: 0.7;
        }
        
        .alert-close:hover {
            opacity: 1;
        }
        
        /* ===== BOTONES ===== */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-whatsapp {
            background-color: #25D366;
            color: white;
        }
        
        .btn-whatsapp:hover {
            background-color: #1da851;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(37, 211, 102, 0.2);
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        /* ===== TARJETAS DE INFORMACIÓN ===== */
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-left: 4px solid var(--secondary-color);
        }
        
        .info-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 14px;
            color: #212529;
            font-weight: 500;
        }
        
        /* ===== FORMULARIOS ===== */
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-control, .form-select {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px;
            width: 100%;
            font-size: 14px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: none;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .form-col {
            flex: 1;
            min-width: 200px;
        }
        
        /* ===== TABLA DE SERVICIOS ===== */
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .services-table th,
        .services-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        
        .services-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        /* ===== BADGES ===== */
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-confirmada {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-cancelada {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-completada {
            background-color: #cce5ff;
            color: #004085;
        }
        
        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }
            
            .sidebar.active {
                transform: translateX(0);
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px 15px 15px 70px;
            }
            
            .hamburger-menu {
                display: flex;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .content-header h1 {
                font-size: 1.8rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px 15px 15px;
            }
            
            .form-row {
                flex-direction: column;
            }
            
            .form-col {
                width: 100%;
            }
            
            .hamburger-menu {
                top: 15px;
                left: 15px;
                width: 45px;
                height: 45px;
            }
        }
        
        @media (max-width: 576px) {
            .content-header h1 {
                font-size: 1.5rem;
            }
            
            .form-container {
                padding: 15px;
            }
            
            .info-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Menú hamburguesa -->
    <button class="hamburger-menu" id="hamburgerBtn">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay para cerrar sidebar en móviles -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Putumayo Turismo</h3>
            </div>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="gestion-destinos.php"><i class="fas fa-map-marker-alt"></i> Destinos</a>
            <a href="gestion-actividades.php"><i class="fas fa-hiking"></i> Actividades</a>
            <a href="gestion-usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
            <a href="gestion-reservas.php" class="active"><i class="fas fa-calendar-check"></i> Reservas</a>
            <a href="gestion-galeria.php"><i class="fas fa-images"></i> Galería</a>
            <a href="../index.php"><i class="fas fa-home"></i> Volver al Sitio</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
        
        <!-- Contenido principal -->
        <div class="main-content" id="mainContent">
            <!-- Header -->
            <div class="content-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-calendar-check text-primary me-2"></i>Actualizar Reserva</h1>
                        <p>Administra y actualiza los detalles de esta reserva</p>
                    </div>
                    <?php if($reserva): ?>
                    <a href="gestion-reservas.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Reservas
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Mensajes -->
            <?php if(isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
            <?php endif; ?>
            
            <?php if(!$reserva): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle me-2"></i>Reserva no encontrada
                <a href="gestion-reservas.php" class="btn btn-sm btn-secondary ms-2">Volver a Reservas</a>
            </div>
            <?php else: ?>
            
            <!-- Información de la Reserva -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="info-card">
                        <h3><i class="fas fa-info-circle"></i> Información de la Reserva</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">ID de Reserva</div>
                                <div class="info-value">#<?php echo $reserva['id']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Fecha de Creación</div>
                                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($reserva['fecha_creacion'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Estado Actual</div>
                                <div class="info-value">
                                    <?php
                                    $estado_badge = [
                                        'pendiente' => ['badge-pendiente', 'Pendiente'],
                                        'confirmada' => ['badge-confirmada', 'Confirmada'],
                                        'cancelada' => ['badge-cancelada', 'Cancelada'],
                                        'completada' => ['badge-completada', 'Completada']
                                    ];
                                    $estado = $reserva['estado'];
                                    $badge_class = $estado_badge[$estado][0] ?? 'badge-pendiente';
                                    $badge_text = $estado_badge[$estado][1] ?? 'Desconocido';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Total Estimado</div>
                                <div class="info-value">
                                    <?php 
                                    $total_actividad = floatval($reserva['actividad_precio'] ?? 0) * intval($reserva['cantidad_personas'] ?? 1);
                                    $total_general = $total_actividad + $total_servicios;
                                    ?>
                                    <strong>$<?php echo number_format($total_general, 2); ?></strong>
                                    <small class="text-muted d-block">
                                        (Actividad: $<?php echo number_format($total_actividad, 2); ?> + Servicios: $<?php echo number_format($total_servicios, 2); ?>)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="info-card">
                        <h3><i class="fas fa-user"></i> Acciones Rápidas</h3>
                        <div class="d-grid gap-2">
                            <?php if(!empty($reserva['usuario_telefono'])): ?>
                            <a href="https://wa.me/57<?php echo preg_replace('/[^0-9]/', '', $reserva['usuario_telefono']); ?>?text=Hola%20<?php echo urlencode($reserva['usuario_nombre']); ?>%2C%20te%20contacto%20de%20Putumayo%20Turismo%20sobre%20tu%20reserva%20%23<?php echo $reserva['id']; ?>"
                               target="_blank" class="btn btn-whatsapp">
                                <i class="fab fa-whatsapp"></i> Contactar por WhatsApp
                            </a>
                            <?php endif; ?>
                            
                            <a href="mailto:<?php echo $reserva['usuario_email']; ?>?subject=Reserva%20%23<?php echo $reserva['id']; ?>%20-%20Putumayo%20Turismo" 
                               class="btn btn-primary">
                                <i class="fas fa-envelope"></i> Enviar Email
                            </a>
                            
                            <button type="button" class="btn btn-info" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimir Detalles
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detalles Completos -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="form-container">
                        <h3 class="mb-4"><i class="fas fa-user-tie"></i> Información del Cliente</h3>
                        
                        <div class="info-grid mb-4">
                            <div class="info-item">
                                <div class="info-label">Nombre Completo</div>
                                <div class="info-value"><?php echo htmlspecialchars($reserva['usuario_nombre'] ?? $reserva['nombre']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($reserva['usuario_email'] ?? $reserva['email']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Teléfono</div>
                                <div class="info-value"><?php echo htmlspecialchars($reserva['usuario_telefono'] ?? $reserva['telefono']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">ID de Usuario</div>
                                <div class="info-value">
                                    <?php if($reserva['usuario_id']): ?>
                                    <a href="gestion-usuarios.php?id=<?php echo $reserva['usuario_id']; ?>" class="text-decoration-none">
                                        #<?php echo $reserva['usuario_id']; ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">No registrado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <h3 class="mb-4"><i class="fas fa-map-marked-alt"></i> Detalles del Viaje</h3>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Destino</div>
                                <div class="info-value"><?php echo htmlspecialchars($reserva['destino_nombre'] ?? 'No especificado'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Actividad</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($reserva['actividad_nombre'] ?? 'No especificada'); ?>
                                    <?php if($reserva['actividad_precio'] > 0): ?>
                                    <span class="badge bg-success">$<?php echo number_format($reserva['actividad_precio'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Fecha del Viaje</div>
                                <div class="info-value"><?php echo date('d/m/Y', strtotime($reserva['fecha_viaje'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Número de Personas</div>
                                <div class="info-value"><?php echo $reserva['cantidad_personas']; ?></div>
                            </div>
                        </div>
                        
                        <?php if(!empty($reserva['comentarios'])): ?>
                        <div class="mt-4">
                            <h5><i class="fas fa-comment-alt"></i> Comentarios del Cliente</h5>
                            <div class="border rounded p-3 bg-light mt-2">
                                <?php echo nl2br(htmlspecialchars($reserva['comentarios'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($reserva['notas_internas'])): ?>
                        <div class="mt-4">
                            <h5><i class="fas fa-sticky-note"></i> Notas Internas</h5>
                            <div class="border rounded p-3 bg-warning bg-opacity-10 mt-2">
                                <?php echo nl2br(htmlspecialchars($reserva['notas_internas'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <!-- Servicios Contratados -->
                    <div class="form-container mb-4">
                        <h3><i class="fas fa-concierge-bell"></i> Servicios Contratados</h3>
                        
                        <?php if(!empty($servicios_reserva) && is_array($servicios_reserva)): ?>
                        <table class="services-table">
                            <thead>
                                <tr>
                                    <th>Servicio</th>
                                    <th>Precio</th>
                                    <th>Categoría</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $placeholders = str_repeat('?,', count($servicios_reserva) - 1) . '?';
                                $stmt_serv = $pdo->prepare("SELECT * FROM servicios_reserva WHERE id IN ($placeholders)");
                                $stmt_serv->execute($servicios_reserva);
                                $servicios_detalles = $stmt_serv->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach($servicios_detalles as $servicio):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($servicio['nombre']); ?></td>
                                    <td>
                                        <?php if($servicio['precio'] > 0): ?>
                                        <span class="badge bg-success">$<?php echo number_format($servicio['precio'], 2); ?></span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Gratis</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($servicio['categoria']); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end pt-3 border-top">
                                        <strong>Total de servicios: $<?php echo number_format($total_servicios, 2); ?></strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-concierge-bell fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No se contrataron servicios adicionales</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Formulario de Actualización -->
                    <div class="form-container">
                        <h3><i class="fas fa-edit"></i> Actualizar Reserva</h3>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="id" value="<?php echo $reserva['id']; ?>">
                            
                            <div class="form-group">
                                <label for="estado">Estado de la Reserva *</label>
                                <select class="form-select" id="estado" name="estado" required>
                                    <option value="pendiente" <?php echo $reserva['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="confirmada" <?php echo $reserva['estado'] == 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                    <option value="cancelada" <?php echo $reserva['estado'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                    <option value="completada" <?php echo $reserva['estado'] == 'completada' ? 'selected' : ''; ?>>Completada</option>
                                </select>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="destino_id">Destino</label>
                                        <select class="form-select" id="destino_id" name="destino_id">
                                            <option value="">Mantener actual</option>
                                            <?php foreach($destinos as $destino): ?>
                                            <option value="<?php echo $destino['id']; ?>" 
                                                <?php echo $reserva['destino_id'] == $destino['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($destino['nombre']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="actividad_id">Actividad</label>
                                        <select class="form-select" id="actividad_id" name="actividad_id">
                                            <option value="">Mantener actual</option>
                                            <?php foreach($actividades as $actividad): ?>
                                            <option value="<?php echo $actividad['id']; ?>" 
                                                <?php echo $reserva['actividad_id'] == $actividad['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($actividad['nombre']); ?>
                                                <?php if($actividad['precio'] > 0): ?> - $<?php echo number_format($actividad['precio'], 2); ?><?php endif; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="fecha_viaje">Fecha del Viaje</label>
                                        <input type="date" class="form-control" id="fecha_viaje" name="fecha_viaje" 
                                               value="<?php echo date('Y-m-d', strtotime($reserva['fecha_viaje'])); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="cantidad_personas">Número de Personas</label>
                                        <input type="number" class="form-control" id="cantidad_personas" name="cantidad_personas" 
                                               value="<?php echo $reserva['cantidad_personas']; ?>" min="1">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="notas_internas">Notas Internas</label>
                                <textarea class="form-control" id="notas_internas" name="notas_internas" rows="4"><?php echo htmlspecialchars($reserva['notas_internas'] ?? ''); ?></textarea>
                                <small class="text-muted">Estas notas solo son visibles para administradores</small>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="gestion-reservas.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                                <button type="submit" name="actualizar_reserva" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Información de Pago -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="form-container">
                        <h3><i class="fas fa-money-bill-wave"></i> Resumen de Costos</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td>Actividad principal:</td>
                                        <td class="text-end">
                                            $<?php echo number_format($reserva['actividad_precio'] ?? 0, 2); ?> × 
                                            <?php echo $reserva['cantidad_personas']; ?> personas
                                        </td>
                                        <td class="text-end">$<?php echo number_format($total_actividad, 2); ?></td>
                                    </tr>
                                    
                                    <?php if($total_servicios > 0): ?>
                                    <tr>
                                        <td>Servicios adicionales:</td>
                                        <td class="text-end"><?php echo count($servicios_reserva); ?> servicios</td>
                                        <td class="text-end">$<?php echo number_format($total_servicios, 2); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <tr class="table-active">
                                        <td><strong>Total estimado:</strong></td>
                                        <td></td>
                                        <td class="text-end"><strong>$<?php echo number_format($total_general, 2); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h5><i class="fas fa-info-circle"></i> Información de Pago</h5>
                                    <p class="mb-0">Esta es una estimación del costo total basada en los datos ingresados. 
                                    Para procesar pagos reales, contacta al cliente para confirmar métodos de pago.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Control del menú hamburguesa
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            // Verificar que los elementos existen
            if (!hamburgerBtn || !sidebar || !sidebarOverlay) {
                console.error('No se encontraron los elementos del menú hamburguesa');
                return;
            }
            
            // Toggle sidebar
            function toggleSidebar() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }
            
            // Event listeners
            hamburgerBtn.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);
            
            // Cargar actividades cuando cambia el destino (SOLO si existe el elemento)
            const destinoSelect = document.getElementById('destino_id');
            if (destinoSelect) {
                destinoSelect.addEventListener('change', function() {
                    const destinoId = this.value;
                    const actividadSelect = document.getElementById('actividad_id');
                    
                    if(!destinoId) {
                        actividadSelect.innerHTML = '<option value="">Selecciona un destino primero</option>';
                        return;
                    }
                    
                    // Mostrar loading
                    actividadSelect.innerHTML = '<option value="">Cargando actividades...</option>';
                    actividadSelect.disabled = true;
                    
                    // Cargar actividades vía AJAX
                    fetch(`../ajax/cargar-actividades.php?destino_id=${destinoId}`)
                        .then(response => response.json())
                        .then(data => {
                            actividadSelect.innerHTML = '<option value="">Mantener actual</option>';
                            
                            if(data && data.length > 0) {
                                data.forEach(actividad => {
                                    const option = document.createElement('option');
                                    option.value = actividad.id;
                                    option.textContent = actividad.nombre;
                                    if(actividad.precio) {
                                        option.textContent += ` - $${parseFloat(actividad.precio).toFixed(2)}`;
                                    }
                                    actividadSelect.appendChild(option);
                                });
                            } else {
                                actividadSelect.innerHTML = '<option value="">No hay actividades disponibles</option>';
                            }
                            
                            actividadSelect.disabled = false;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            actividadSelect.innerHTML = '<option value="">Error al cargar actividades</option>';
                            actividadSelect.disabled = false;
                        });
                });
            }
            
            // Auto-close alerts
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    alert.style.display = 'none';
                });
            }, 5000);
            
            // Form validation (SOLO si existe el formulario)
            const form = document.querySelector('form');
            if(form) {
                form.addEventListener('submit', function(e) {
                    const estado = document.getElementById('estado').value;
                    const cantidadPersonas = document.getElementById('cantidad_personas');
                    
                    if(!estado) {
                        e.preventDefault();
                        alert('Por favor selecciona un estado para la reserva.');
                        return false;
                    }
                    
                    if(cantidadPersonas && cantidadPersonas.value) {
                        const valor = parseInt(cantidadPersonas.value);
                        if(valor < 1 || valor > 50) {
                            e.preventDefault();
                            alert('El número de personas debe estar entre 1 y 50.');
                            return false;
                        }
                    }
                    
                    // Mostrar loading
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if(submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                        submitBtn.disabled = true;
                    }
                });
            }
            
            // Cerrar sidebar al hacer clic en un enlace (en móviles)
            if (window.innerWidth <= 992) {
                const sidebarLinks = document.querySelectorAll('.sidebar a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (sidebar.classList.contains('active')) {
                            toggleSidebar();
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>