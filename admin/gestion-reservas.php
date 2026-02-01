<?php
// admin/gestion-reservas.php
include '../includes/config.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('superadmin'))) {
    header('Location: ../login.php');
    exit;
}

// Inicializar variables para mensajes
$message = '';
$error = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Procesar actualización de estado de reserva
    if (isset($_POST['actualizar_estado_reserva'])) {
        $reserva_id = intval($_POST['reserva_id']);
        $estado = trim($_POST['estado']);
        $notas_internas = trim($_POST['notas_internas'] ?? '');

        if (empty($estado)) {
            $error = "El estado es obligatorio";
        } else {
            try {
                // Verificar que la reserva existe
                $stmt = $pdo->prepare("SELECT id FROM reservas WHERE id = ?");
                $stmt->execute([$reserva_id]);

                if (!$stmt->fetch()) {
                    $error = "La reserva no existe";
                } else {
                    // Actualizar estado de la reserva
                    $sql = "UPDATE reservas SET estado = ?, notas_internas = ?, fecha_actualizacion = NOW() WHERE id = ?";
                    $stmt = $pdo->prepare($sql);

                    if ($stmt->execute([$estado, $notas_internas, $reserva_id])) {
                        $message = "✅ Estado de reserva #$reserva_id actualizado correctamente a: " . ucfirst($estado);

                        // Registrar en activity log
                        if (isset($_SESSION['user_id'])) {
                            $user_id = $_SESSION['user_id'];
                            try {
                                $stmt_log = $pdo->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)");
                                $stmt_log->execute([
                                    $user_id,
                                    'update_reserva_estado',
                                    "Reserva ID $reserva_id actualizada a estado: $estado"
                                ]);
                            } catch (Exception $e) {
                                error_log("Error al registrar log: " . $e->getMessage());
                            }
                        }
                    } else {
                        $error = "❌ Error al actualizar el estado de la reserva";
                    }
                }
            } catch (PDOException $e) {
                $error = "❌ Error de base de datos: " . $e->getMessage();
                error_log("Error en gestion-reservas.php (actualizar estado): " . $e->getMessage());
            }
        }
    }

    // Resto del código para condiciones y servicios...
    if (isset($_POST['agregar_condicion'])) {
        $titulo = trim($_POST['titulo']);
        $descripcion = trim($_POST['descripcion']);
        $icono = trim($_POST['icono']);
        $orden = intval($_POST['orden']);
        $activo = isset($_POST['activo']) ? 1 : 1;

        try {
            $stmt = $pdo->prepare("INSERT INTO condiciones_transporte (titulo, descripcion, icono, orden, activo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $descripcion, $icono, $orden, $activo]);
            $message = "Condición agregada correctamente";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['editar_condicion'])) {
        $id = intval($_POST['id']);
        $titulo = trim($_POST['titulo']);
        $descripcion = trim($_POST['descripcion']);
        $icono = trim($_POST['icono']);
        $orden = intval($_POST['orden']);
        $activo = isset($_POST['activo']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE condiciones_transporte SET titulo = ?, descripcion = ?, icono = ?, orden = ?, activo = ? WHERE id = ?");
            $stmt->execute([$titulo, $descripcion, $icono, $orden, $activo, $id]);
            $message = "Condición actualizada correctamente";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['eliminar_condicion'])) {
        $id = intval($_POST['id']);

        try {
            $stmt = $pdo->prepare("DELETE FROM condiciones_transporte WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Condición eliminada correctamente";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['agregar_servicio'])) {
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $categoria = trim($_POST['categoria']);
        $orden = intval($_POST['orden']);
        $activo = isset($_POST['activo']) ? 1 : 1;

        try {
            $stmt = $pdo->prepare("INSERT INTO servicios_reserva (nombre, descripcion, precio, categoria, orden, activo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $precio, $categoria, $orden, $activo]);
            $message = "Servicio agregado correctamente";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['editar_servicio'])) {
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $categoria = trim($_POST['categoria']);
        $orden = intval($_POST['orden']);
        $activo = isset($_POST['activo']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE servicios_reserva SET nombre = ?, descripcion = ?, precio = ?, categoria = ?, orden = ?, activo = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $precio, $categoria, $orden, $activo, $id]);
            $message = "Servicio actualizado correctamente";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['eliminar_servicio'])) {
        $id = intval($_POST['id']);

        try {
            $stmt = $pdo->prepare("DELETE FROM servicios_reserva WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Servicio eliminado correctamente";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Obtener condiciones
try {
    $stmt = $pdo->query("SELECT * FROM condiciones_transporte ORDER BY orden");
    $condiciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $condiciones = [];
}

// Obtener servicios
try {
    $stmt = $pdo->query("SELECT * FROM servicios_reserva ORDER BY categoria, orden");
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $servicios = [];
}

// Obtener reservas con información completa
try {
    $stmt = $pdo->query("SELECT 
        r.*, 
        d.nombre as destino_nombre,
        u.nombre as usuario_nombre,
        u.email as usuario_email
    FROM reservas r
    LEFT JOIN destinos d ON r.destino_id = d.id
    LEFT JOIN usuarios u ON r.usuario_id = u.id
    ORDER BY r.fecha_creacion DESC 
    LIMIT 50");
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reservas = [];
    error_log("Error al cargar reservas: " . $e->getMessage());
}

// Obtener categorías únicas de servicios
$categorias = [];
foreach ($servicios as $servicio) {
    if (!in_array($servicio['categoria'], $categorias)) {
        $categorias[] = $servicio['categoria'];
    }
}
sort($categorias);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reservas - Admin</title>
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

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            margin-left: 280px;
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
            animation: fadeIn 0.5s forwards;
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

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

        /* ===== TABLAS ===== */
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            padding: 20px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .admin-table th,
        .admin-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .admin-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }

        .admin-table tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        /* ===== BADGES DE ESTADO ===== */
        .badge-estado {
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

        /* ===== FORMULARIOS ===== */
        .form-control,
        .form-select {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px;
            width: 100%;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: none;
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

        /* ===== MODALES ===== */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
            padding: 20px;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .modal h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -30px -30px 20px -30px;
        }

        .modal-close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #7f8c8d;
            line-height: 1;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        /* ===== CARD STYLES ===== */
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }

        /* ===== NAV TABS ===== */
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 20px;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            border: none;
            color: var(--secondary-color);
        }

        .nav-tabs .nav-link.active {
            color: var(--secondary-color);
            border-bottom: 3px solid var(--secondary-color);
            background: transparent;
        }

        /* ===== ACCIONES ===== */
        .action-buttons {
            display: flex;
            gap: 5px;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 1200px) {
            .admin-table {
                min-width: 800px;
            }
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 75px 15px 15px 15px;
            }

            .sidebar-overlay.active {
                display: block;
            }

            .content-header h1 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 75px 10px 15px 10px;
            }

            .content-header h1 {
                font-size: 1.5rem;
                margin-top: 0;
            }

            .nav-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .table-container {
                padding: 15px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .admin-table {
                min-width: 800px;
                font-size: 14px;
            }

            .admin-table th,
            .admin-table td {
                padding: 10px 8px;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .modal-dialog {
                width: 95%;
                max-height: 90vh;
                overflow-y: auto;
            }

            .modal-content {
                padding: 20px;
            }

            .form-group {
                margin-bottom: 15px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 70px 5px 10px 5px;
            }

            .content-header h1 {
                font-size: 1.3rem;
            }

            .content-header p {
                font-size: 12px;
            }

            .table-container {
                padding: 10px;
                border-radius: 8px;
            }

            .admin-table {
                min-width: 750px;
                font-size: 12px;
            }

            .admin-table th,
            .admin-table td {
                padding: 8px 5px;
            }

            .btn {
                padding: 8px 12px;
                font-size: 12px;
            }

            .btn-sm {
                padding: 5px 8px;
                font-size: 11px;
            }

            .nav-tabs {
                font-size: 12px;
            }

            .nav-tabs li a {
                padding: 10px 12px;
            }

            .modal-content {
                padding: 15px;
            }

            .form-group label {
                font-size: 12px;
            }

            .form-control {
                padding: 8px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Contenido principal -->
        <div class="main-content" id="mainContent">
            <!-- Header -->
            <div class="content-header">
                <h1><i class="fas fa-calendar-check text-primary me-2"></i>Gestión de Reservas</h1>
                <p>Administra condiciones de transporte, servicios y reservas</p>
            </div>

            <!-- Mensajes -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
            <?php endif; ?>

            <!-- Pestañas -->
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="reservas-tab" data-bs-toggle="tab" data-bs-target="#reservas" type="button">
                        <i class="fas fa-list me-2"></i>Lista de Reservas
                        <span class="badge bg-primary ms-2"><?php echo count($reservas); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="condiciones-tab" data-bs-toggle="tab" data-bs-target="#condiciones" type="button">
                        <i class="fas fa-bus me-2"></i>Condiciones de Transporte
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="servicios-tab" data-bs-toggle="tab" data-bs-target="#servicios" type="button">
                        <i class="fas fa-concierge-bell me-2"></i>Servicios Adicionales
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <!-- Pestaña Reservas -->
                <div class="tab-pane fade show active" id="reservas" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-calendar-alt me-2"></i>Todas las Reservas
                                <span class="badge bg-secondary ms-2"><?php echo count($reservas); ?></span>
                            </div>
                            <a href="actualizar-reserva.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i>Ver Todas
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($reservas)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No hay reservas registradas</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Cliente</th>
                                                <th>Destino</th>
                                                <th>Fecha Viaje</th>
                                                <th>Personas</th>
                                                <th>Estado</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reservas as $reserva):
                                                // Decodificar servicios seleccionados
                                                $servicios_reserva = json_decode($reserva['servicios_seleccionados'] ?? '[]', true);
                                                $num_servicios = is_array($servicios_reserva) ? count($servicios_reserva) : 0;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <strong>#<?php echo $reserva['id']; ?></strong>
                                                    </td>
                                                    <td>
                                                        <div class="fw-medium"><?php echo htmlspecialchars($reserva['nombre']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($reserva['email']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($reserva['destino_nombre'] ?? 'N/A'); ?>
                                                        <?php if ($num_servicios > 0): ?>
                                                            <br><small class="text-info">+<?php echo $num_servicios; ?> servicios</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo date('d/m/Y', strtotime($reserva['fecha_viaje'])); ?>
                                                        <br><small class="text-muted">Creada: <?php echo date('d/m/Y', strtotime($reserva['fecha_creacion'])); ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-secondary"><?php echo $reserva['cantidad_personas']; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $estado_clases = [
                                                            'pendiente' => 'badge-pendiente',
                                                            'confirmada' => 'badge-confirmada',
                                                            'cancelada' => 'badge-cancelada',
                                                            'completada' => 'badge-completada'
                                                        ];
                                                        $estado_texto = [
                                                            'pendiente' => 'Pendiente',
                                                            'confirmada' => 'Confirmada',
                                                            'cancelada' => 'Cancelada',
                                                            'completada' => 'Completada'
                                                        ];
                                                        $estado = $reserva['estado'];
                                                        $clase = $estado_clases[$estado] ?? 'badge-secondary';
                                                        $texto = $estado_texto[$estado] ?? 'Desconocido';
                                                        ?>
                                                        <span class="badge-estado <?php echo $clase; ?>"><?php echo $texto; ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="action-buttons justify-content-center">
                                                            <button class="btn btn-sm btn-info"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#detalleReservaModal<?php echo $reserva['id']; ?>"
                                                                title="Ver detalles">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-warning"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editarReservaModal<?php echo $reserva['id']; ?>"
                                                                title="Editar estado">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <a href="actualizar-reserva.php?id=<?php echo $reserva['id']; ?>"
                                                                class="btn btn-sm btn-primary" title="Editar completa">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Pestaña Condiciones -->
                <div class="tab-pane fade" id="condiciones" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-4 col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-plus me-2"></i>Agregar Nueva Condición
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Título *</label>
                                            <input type="text" class="form-control" name="titulo" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Descripción *</label>
                                            <textarea class="form-control" name="descripcion" rows="3" required></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Icono (Font Awesome)</label>
                                            <input type="text" class="form-control" name="icono"
                                                value="fas fa-car" placeholder="Ej: fas fa-car, fas fa-ship">
                                            <small class="text-muted">Usa clases de Font Awesome</small>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Orden</label>
                                                <input type="number" class="form-control" name="orden" value="1" min="1">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label d-block">Estado</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="activo" checked>
                                                    <label class="form-check-label">Activo</label>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" name="agregar_condicion" class="btn btn-primary w-100">
                                            <i class="fas fa-save me-2"></i>Guardar Condición
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-list me-2"></i>Condiciones Existentes
                                    <span class="badge bg-secondary ms-2"><?php echo count($condiciones); ?></span>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($condiciones)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No hay condiciones registradas</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="admin-table">
                                                <thead>
                                                    <tr>
                                                        <th>Icono</th>
                                                        <th>Título</th>
                                                        <th>Descripción</th>
                                                        <th>Orden</th>
                                                        <th>Estado</th>
                                                        <th class="text-end">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($condiciones as $condicion): ?>
                                                        <tr>
                                                            <td>
                                                                <i class="<?php echo htmlspecialchars($condicion['icono']); ?> fa-lg"></i>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($condicion['titulo']); ?></td>
                                                            <td>
                                                                <small><?php echo htmlspecialchars($condicion['descripcion']); ?></small>
                                                            </td>
                                                            <td><?php echo $condicion['orden']; ?></td>
                                                            <td>
                                                                <?php if ($condicion['activo']): ?>
                                                                    <span class="badge bg-success">Activo</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Inactivo</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-end">
                                                                <div class="action-buttons">
                                                                    <button class="btn btn-sm btn-warning"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editCondicionModal<?php echo $condicion['id']; ?>">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button class="btn btn-sm btn-danger"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#deleteCondicionModal<?php echo $condicion['id']; ?>">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pestaña Servicios -->
                <div class="tab-pane fade" id="servicios" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-4 col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-plus me-2"></i>Agregar Nuevo Servicio
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Nombre del Servicio *</label>
                                            <input type="text" class="form-control" name="nombre" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Descripción</label>
                                            <textarea class="form-control" name="descripcion" rows="2"></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Precio ($)</label>
                                                <input type="number" class="form-control" name="precio" value="0" min="0" step="0.01">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Categoría</label>
                                                <input type="text" class="form-control" name="categoria" value="General">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Orden</label>
                                                <input type="number" class="form-control" name="orden" value="1" min="1">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label d-block">Estado</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="activo" checked>
                                                    <label class="form-check-label">Activo</label>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" name="agregar_servicio" class="btn btn-primary w-100">
                                            <i class="fas fa-save me-2"></i>Guardar Servicio
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-list me-2"></i>Servicios Existentes
                                    <span class="badge bg-secondary ms-2"><?php echo count($servicios); ?></span>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($servicios)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-concierge-bell fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No hay servicios registrados</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="admin-table">
                                                <thead>
                                                    <tr>
                                                        <th>Categoría</th>
                                                        <th>Servicio</th>
                                                        <th>Descripción</th>
                                                        <th>Precio</th>
                                                        <th>Estado</th>
                                                        <th class="text-end">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($servicios as $servicio): ?>
                                                        <tr>
                                                            <td>
                                                                <span class="badge bg-info"><?php echo htmlspecialchars($servicio['categoria']); ?></span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($servicio['nombre']); ?></td>
                                                            <td>
                                                                <small><?php echo htmlspecialchars($servicio['descripcion']); ?></small>
                                                            </td>
                                                            <td>
                                                                <?php if ($servicio['precio'] > 0): ?>
                                                                    <span class="badge bg-success">$<?php echo number_format($servicio['precio'], 2); ?></span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Gratis</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($servicio['activo']): ?>
                                                                    <span class="badge bg-success">Activo</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Inactivo</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-end">
                                                                <div class="action-buttons">
                                                                    <button class="btn btn-sm btn-warning"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editServicioModal<?php echo $servicio['id']; ?>">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button class="btn btn-sm btn-danger"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#deleteServicioModal<?php echo $servicio['id']; ?>">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modales para condiciones -->
    <?php foreach ($condiciones as $condicion): ?>
        <!-- Modal Editar Condición -->
        <div class="modal fade" id="editCondicionModal<?php echo $condicion['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Condición</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?php echo $condicion['id']; ?>">

                            <div class="mb-3">
                                <label class="form-label">Título *</label>
                                <input type="text" class="form-control" name="titulo"
                                    value="<?php echo htmlspecialchars($condicion['titulo']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción *</label>
                                <textarea class="form-control" name="descripcion" rows="3" required><?php echo htmlspecialchars($condicion['descripcion']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Icono</label>
                                <input type="text" class="form-control" name="icono"
                                    value="<?php echo htmlspecialchars($condicion['icono']); ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Orden</label>
                                    <input type="number" class="form-control" name="orden"
                                        value="<?php echo $condicion['orden']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label d-block">Estado</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                            name="activo" <?php echo $condicion['activo'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Activo</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" name="editar_condicion" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Eliminar Condición -->
        <div class="modal fade" id="deleteCondicionModal<?php echo $condicion['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de eliminar la condición <strong>"<?php echo htmlspecialchars($condicion['titulo']); ?>"</strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo $condicion['id']; ?>">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" name="eliminar_condicion" class="btn btn-danger">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Modales para servicios -->
    <?php foreach ($servicios as $servicio): ?>
        <!-- Modal Editar Servicio -->
        <div class="modal fade" id="editServicioModal<?php echo $servicio['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Servicio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?php echo $servicio['id']; ?>">

                            <div class="mb-3">
                                <label class="form-label">Nombre del Servicio *</label>
                                <input type="text" class="form-control" name="nombre"
                                    value="<?php echo htmlspecialchars($servicio['nombre']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="descripcion" rows="2"><?php echo htmlspecialchars($servicio['descripcion']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Precio ($)</label>
                                    <input type="number" class="form-control" name="precio"
                                        value="<?php echo $servicio['precio']; ?>" min="0" step="0.01">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Categoría</label>
                                    <input type="text" class="form-control" name="categoria"
                                        value="<?php echo htmlspecialchars($servicio['categoria']); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Orden</label>
                                    <input type="number" class="form-control" name="orden"
                                        value="<?php echo $servicio['orden']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label d-block">Estado</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                            name="activo" <?php echo $servicio['activo'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Activo</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" name="editar_servicio" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Eliminar Servicio -->
        <div class="modal fade" id="deleteServicioModal<?php echo $servicio['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de eliminar el servicio <strong>"<?php echo htmlspecialchars($servicio['nombre']); ?>"</strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo $servicio['id']; ?>">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" name="eliminar_servicio" class="btn btn-danger">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Modales para reservas -->
    <?php foreach ($reservas as $reserva): ?>
        <?php
        // Decodificar servicios para esta reserva
        $servicios_reserva = json_decode($reserva['servicios_seleccionados'] ?? '[]', true);
        $num_servicios = is_array($servicios_reserva) ? count($servicios_reserva) : 0;
        ?>

        <!-- Modal Detalle Reserva -->
        <div class="modal fade" id="detalleReservaModal<?php echo $reserva['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-info-circle me-2"></i>Detalle de Reserva #<?php echo $reserva['id']; ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Información del Cliente</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($reserva['nombre']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($reserva['email']); ?></p>
                                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($reserva['telefono']); ?></p>
                                        <?php if (!empty($reserva['usuario_id'])): ?>
                                            <p><strong>Usuario ID:</strong> <?php echo $reserva['usuario_id']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Detalles del Viaje</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Destino:</strong> <?php echo htmlspecialchars($reserva['destino_nombre'] ?? 'N/A'); ?></p>
                                        <p><strong>Fecha de Viaje:</strong> <?php echo date('d/m/Y', strtotime($reserva['fecha_viaje'])); ?></p>
                                        <p><strong>Personas:</strong> <?php echo $reserva['cantidad_personas']; ?></p>
                                        <p><strong>Estado:</strong>
                                            <?php
                                            $estado = $reserva['estado'];
                                            $clase = [
                                                'pendiente' => 'badge-pendiente',
                                                'confirmada' => 'badge-confirmada',
                                                'cancelada' => 'badge-cancelada',
                                                'completada' => 'badge-completada'
                                            ][$estado] ?? 'badge-secondary';
                                            $texto = [
                                                'pendiente' => 'Pendiente',
                                                'confirmada' => 'Confirmada',
                                                'cancelada' => 'Cancelada',
                                                'completada' => 'Completada'
                                            ][$estado] ?? 'Desconocido';
                                            ?>
                                            <span class="badge-estado <?php echo $clase; ?>"><?php echo $texto; ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($num_servicios > 0): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-concierge-bell me-2"></i>Servicios Contratados (<?php echo $num_servicios; ?>)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Servicio</th>
                                                    <th>Precio</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if (is_array($servicios_reserva) && !empty($servicios_reserva)) {
                                                    // Obtener detalles de los servicios
                                                    $servicios_ids = array_filter($servicios_reserva, 'is_numeric');
                                                    if (!empty($servicios_ids)) {
                                                        $placeholders = str_repeat('?,', count($servicios_ids) - 1) . '?';
                                                        $stmt_serv = $pdo->prepare("SELECT nombre, precio FROM servicios_reserva WHERE id IN ($placeholders)");
                                                        $stmt_serv->execute($servicios_ids);
                                                        $servicios_info = $stmt_serv->fetchAll(PDO::FETCH_ASSOC);

                                                        foreach ($servicios_info as $servicio_info):
                                                ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($servicio_info['nombre']); ?></td>
                                                                <td>
                                                                    <?php if ($servicio_info['precio'] > 0): ?>
                                                                        <span class="badge bg-success">$<?php echo number_format($servicio_info['precio'], 2); ?></span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-secondary">Gratis</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                <?php endforeach;
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($reserva['comentarios'])): ?>
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-comment-alt me-2"></i>Comentarios del Cliente</h6>
                                </div>
                                <div class="card-body">
                                    <div class="border rounded p-3 bg-light">
                                        <?php echo nl2br(htmlspecialchars($reserva['comentarios'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($reserva['notas_internas'])): ?>
                            <div class="card mt-3">
                                <div class="card-header bg-warning bg-opacity-25">
                                    <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notas Internas</h6>
                                </div>
                                <div class="card-body">
                                    <div class="border rounded p-3 bg-warning bg-opacity-10">
                                        <?php echo nl2br(htmlspecialchars($reserva['notas_internas'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-warning"
                            data-bs-toggle="modal"
                            data-bs-target="#editarReservaModal<?php echo $reserva['id']; ?>"
                            data-bs-dismiss="modal">
                            <i class="fas fa-edit me-2"></i>Editar Estado
                        </button>
                        <a href="actualizar-reserva.php?id=<?php echo $reserva['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-external-link-alt me-2"></i>Ver Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Editar Estado de Reserva (AHORA FUNCIONA) -->
        <div class="modal fade" id="editarReservaModal<?php echo $reserva['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Editar Estado de Reserva #<?php echo $reserva['id']; ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <!-- FORMULARIO CORREGIDO: Se envía a ESTA MISMA PÁGINA -->
                    <form method="POST" action="">
                        <div class="modal-body">
                            <input type="hidden" name="reserva_id" value="<?php echo $reserva['id']; ?>">

                            <div class="mb-3">
                                <label class="form-label">Estado de la Reserva *</label>
                                <select class="form-select" name="estado" required>
                                    <option value="pendiente" <?php echo $reserva['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="confirmada" <?php echo $reserva['estado'] == 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                    <option value="cancelada" <?php echo $reserva['estado'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                    <option value="completada" <?php echo $reserva['estado'] == 'completada' ? 'selected' : ''; ?>>Completada</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notas Internas</label>
                                <textarea class="form-control" name="notas_internas" rows="3"><?php echo htmlspecialchars($reserva['notas_internas'] ?? ''); ?></textarea>
                                <small class="text-muted">Estas notas solo son visibles para administradores</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" name="actualizar_estado_reserva" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Actualizar Estado
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script>
        // Control del page
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-close alerts
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    alert.style.display = 'none';
                });
            }, 5000);

            // Asegurar que los modales de reservas tengan el comportamiento correcto
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (button) {
                        console.log('Modal abierto desde:', button);
                    }
                });
            });

            // Inicializar tooltips de Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>