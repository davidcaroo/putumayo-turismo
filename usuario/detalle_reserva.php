<?php
// usuario/detalle_reserva.php - Detalle completo de una reserva

session_start();

// Verificar sesión
if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=usuario/detalle_reserva.php?id=' . $_GET['id']);
    exit();
}

// Verificar que sea usuario normal
if(isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header('Location: ../admin/dashboard.php');
    exit();
}

require_once '../includes/config.php';

$user_id = $_SESSION['user_id'];
$reserva_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener información básica de la reserva (solo campos de la tabla reservas)
$sql = "SELECT r.*
        FROM reservas r 
        WHERE r.id = ? AND r.usuario_id = ?";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$reserva_id, $user_id]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reserva) {
    $_SESSION['error'] = 'Reserva no encontrada';
    header('Location: reservas.php');
    exit();
}

// Obtener información del destino (si existe)
$destino = null;
if(!empty($reserva['destino_id'])) {
    $sql_destino = "SELECT nombre, descripcion, imagen_principal, ubicacion FROM destinos WHERE id = ?";
    $stmt_destino = $pdo->prepare($sql_destino);
    $stmt_destino->execute([$reserva['destino_id']]);
    $destino = $stmt_destino->fetch(PDO::FETCH_ASSOC);
}

// Obtener información de la actividad (si existe)
$actividad = null;
if(!empty($reserva['actividad_id'])) {
    $sql_actividad = "SELECT nombre, descripcion, precio FROM actividades WHERE id = ?";
    $stmt_actividad = $pdo->prepare($sql_actividad);
    $stmt_actividad->execute([$reserva['actividad_id']]);
    $actividad = $stmt_actividad->fetch(PDO::FETCH_ASSOC);
}

// Formatear fechas
$fecha_creacion = date('d/m/Y H:i', strtotime($reserva['fecha_creacion']));
$fecha_reserva = $reserva['fecha_reserva'] ? date('d/m/Y', strtotime($reserva['fecha_reserva'])) : 'No especificada';
$fecha_viaje = $reserva['fecha_viaje'] ? date('d/m/Y', strtotime($reserva['fecha_viaje'])) : 'No especificada';
$fecha_modificacion = $reserva['fecha_modificacion'] ? date('d/m/Y H:i', strtotime($reserva['fecha_modificacion'])) : 'Sin modificaciones';

// Mapear estado a clases CSS
$estado_classes = [
    'pendiente' => 'estado-pendiente',
    'confirmada' => 'estado-confirmada',
    'cancelada' => 'estado-cancelada'
];

$estado_texto = [
    'pendiente' => 'Pendiente',
    'confirmada' => 'Confirmada',
    'cancelada' => 'Cancelada'
];

// Mapear método de pago
$metodo_pago_texto = [
    'efectivo' => 'Efectivo',
    'tarjeta_credito' => 'Tarjeta de Crédito',
    'tarjeta_debito' => 'Tarjeta de Débito',
    'transferencia' => 'Transferencia Bancaria',
    'paypal' => 'PayPal'
];

// Generar código de reserva si no existe
if(empty($reserva['codigo_reserva'])) {
    $reserva['codigo_reserva'] = 'RES-' . str_pad($reserva_id, 6, '0', STR_PAD_LEFT) . '-' . date('ym');
}

$page_title = 'Detalle de Reserva';
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
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: var(--dark-color);
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
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
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-info {
            background: var(--accent-color);
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .detalle-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 992px) {
            .detalle-container {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light-color);
        }
        
        .card-header i {
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        
        .card-header h2 {
            color: var(--dark-color);
            font-size: 1.5rem;
            flex-grow: 1;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .info-group {
            margin-bottom: 1.5rem;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            display: block;
            font-size: 0.9rem;
        }
        
        .info-value {
            padding: 0.8rem 1rem;
            background: var(--light-color);
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            font-size: 1rem;
            min-height: 3rem;
            display: flex;
            align-items: center;
        }
        
        .info-value i {
            margin-right: 0.8rem;
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }
        
        .estado-badge {
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
        }
        
        .estado-confirmada {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .estado-cancelada {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .destino-imagen {
            width: 100%;
            height: 250px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .destino-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .destino-imagen:hover img {
            transform: scale(1.05);
        }
        
        .destino-nombre {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .destino-ubicacion {
            color: #666;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .destino-descripcion {
            color: #555;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .precio-total {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            text-align: center;
            margin: 1.5rem 0;
            padding: 1rem;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border-radius: 10px;
            border: 2px solid var(--secondary-color);
        }
        
        .precio-desglose {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .precio-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .precio-item:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        
        .acciones-container {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }
        
        .breadcrumb {
            margin-bottom: 1.5rem;
            color: #666;
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 2px solid transparent;
        }
        
        .alert-info {
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .codigo-reserva {
            font-family: monospace;
            background: var(--dark-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 1.1rem;
            letter-spacing: 1px;
            display: inline-block;
        }
        
        .qr-code {
            text-align: center;
            margin: 1.5rem 0;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            border: 2px dashed var(--border-color);
        }
        
        .qr-code img {
            max-width: 200px;
            height: auto;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        
        .notas-container {
            background: #fff8e1;
            border-left: 4px solid var(--warning-color);
            padding: 1rem;
            border-radius: 0 8px 8px 0;
            margin: 1.5rem 0;
        }
        
        @media print {
            .btn, .acciones-container, .breadcrumb {
                display: none !important;
            }
            
            body {
                background: white;
                padding: 0;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Migas de pan -->
        <div class="breadcrumb">
            <a href="../index.php">Inicio</a> / 
            <a href="dashboard.php">Mi Cuenta</a> / 
            <a href="reservas.php">Mis Reservas</a> / 
            <span>Detalle de Reserva</span>
        </div>
        
        <!-- Encabezado -->
        <div class="header">
            <div>
                <h1><i class="fas fa-file-invoice"></i> Detalle de Reserva</h1>
                <div style="margin-top: 0.5rem;">
                    <span class="codigo-reserva">
                        <i class="fas fa-hashtag"></i> 
                        <?php echo htmlspecialchars($reserva['codigo_reserva']); ?>
                    </span>
                </div>
            </div>
            <div class="acciones-container">
                <a href="reservas.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Reservas
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
        
        <!-- Mensajes -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="detalle-container">
            <!-- Información del destino/actividad -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-map-marked-alt"></i>
                    <h2>Información del Viaje</h2>
                    <span class="estado-badge <?php echo $estado_classes[$reserva['estado']]; ?>">
                        <?php echo $estado_texto[$reserva['estado']]; ?>
                    </span>
                </div>
                
                <?php if(!empty($destino['imagen_principal'])): ?>
                <div class="destino-imagen">
                    <img src="../uploads/destinos/<?php echo htmlspecialchars($destino['imagen_principal']); ?>" 
                         alt="<?php echo htmlspecialchars($destino['nombre'] ?? 'Destino'); ?>">
                </div>
                <?php endif; ?>
                
                <?php if(!empty($destino['nombre'])): ?>
                <h3 class="destino-nombre"><?php echo htmlspecialchars($destino['nombre']); ?></h3>
                <?php endif; ?>
                
                <?php if(!empty($destino['ubicacion'])): ?>
                <p class="destino-ubicacion">
                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($destino['ubicacion']); ?>
                </p>
                <?php endif; ?>
                
                <?php if(!empty($destino['descripcion'])): ?>
                <div class="destino-descripcion">
                    <?php echo nl2br(htmlspecialchars($destino['descripcion'])); ?>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($actividad['nombre'])): ?>
                <div class="info-group">
                    <span class="info-label">Actividad Contratada:</span>
                    <div class="info-value">
                        <i class="fas fa-hiking"></i>
                        <div>
                            <strong><?php echo htmlspecialchars($actividad['nombre']); ?></strong>
                            <?php if(!empty($actividad['descripcion'])): ?>
                                <br><small><?php echo htmlspecialchars($actividad['descripcion']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Información de la reserva -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-receipt"></i>
                    <h2>Detalles de la Reserva</h2>
                </div>
                
                <div class="info-grid">
                    <div class="info-group">
                        <span class="info-label">Solicitante:</span>
                        <div class="info-value">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($reserva['nombre']); ?>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <span class="info-label">Email:</span>
                        <div class="info-value">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($reserva['email']); ?>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <span class="info-label">Teléfono:</span>
                        <div class="info-value">
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($reserva['telefono']); ?>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <span class="info-label">Fecha de Reserva:</span>
                        <div class="info-value">
                            <i class="fas fa-calendar-plus"></i> <?php echo $fecha_reserva; ?>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <span class="info-label">Fecha de Viaje:</span>
                        <div class="info-value">
                            <i class="fas fa-plane-departure"></i> <?php echo $fecha_viaje; ?>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <span class="info-label">Cantidad de Personas:</span>
                        <div class="info-value">
                            <i class="fas fa-users"></i> <?php echo $reserva['cantidad_personas']; ?> personas
                        </div>
                    </div>
                    
                    <?php if(!empty($reserva['metodo_pago'])): ?>
                    <div class="info-group">
                        <span class="info-label">Método de Pago:</span>
                        <div class="info-value">
                            <i class="fas fa-credit-card"></i> 
                            <?php 
                            $metodo = $reserva['metodo_pago'];
                            echo $metodo_pago_texto[$metodo] ?? ucfirst($metodo); 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-group">
                        <span class="info-label">Fecha de Creación:</span>
                        <div class="info-value">
                            <i class="fas fa-calendar"></i> <?php echo $fecha_creacion; ?>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <span class="info-label">Última Modificación:</span>
                        <div class="info-value">
                            <i class="fas fa-edit"></i> <?php echo $fecha_modificacion; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Notas adicionales -->
                <?php if(!empty($reserva['notas'])): ?>
                <div class="notas-container">
                    <strong><i class="fas fa-sticky-note"></i> Notas Adicionales:</strong>
                    <p style="margin-top: 0.5rem;"><?php echo nl2br(htmlspecialchars($reserva['notas'])); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Desglose de precio -->
                <div class="precio-desglose">
                    <h3 style="margin-bottom: 1rem; color: var(--dark-color);">
                        <i class="fas fa-calculator"></i> Desglose del Precio
                    </h3>
                    
                    <?php if($reserva['precio_total'] > 0): ?>
                    <div class="precio-item">
                        <span>Total a pagar</span>
                        <span>$<?php echo number_format($reserva['precio_total'], 2); ?></span>
                    </div>
                    <?php else: ?>
                    <div class="precio-item">
                        <span>Precio a confirmar</span>
                        <span>Por confirmar</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if($reserva['precio_total'] > 0): ?>
                <div class="precio-total">
                    TOTAL: $<?php echo number_format($reserva['precio_total'], 2); ?>
                </div>
                <?php endif; ?>
                
                <!-- Acciones según estado -->
                <div class="acciones-container">
                    <?php if($reserva['estado'] == 'pendiente'): ?>
                        <a href="pagar_reserva.php?id=<?php echo $reserva_id; ?>" class="btn btn-success">
                            <i class="fas fa-credit-card"></i> Pagar Reserva
                        </a>
                        <a href="modificar_reserva.php?id=<?php echo $reserva_id; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Modificar
                        </a>
                        <button onclick="mostrarModalCancelar()" class="btn btn-danger">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    <?php elseif($reserva['estado'] == 'confirmada'): ?>
                        <a href="modificar_reserva.php?id=<?php echo $reserva_id; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Modificar
                        </a>
                        <?php if(!empty($reserva['fecha_viaje']) && strtotime($reserva['fecha_viaje']) > time()): ?>
                        <button onclick="mostrarModalCancelar()" class="btn btn-danger">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <?php endif; ?>
                    <?php elseif($reserva['estado'] == 'cancelada'): ?>
                        <button class="btn btn-secondary" disabled>
                            <i class="fas fa-ban"></i> Reserva Cancelada
                        </button>
                    <?php endif; ?>
                    
                    <a href="../contacto.php?asunto=Consulta sobre reserva <?php echo urlencode($reserva['codigo_reserva']); ?>" 
                       class="btn btn-info">
                        <i class="fas fa-question-circle"></i> Consultar
                    </a>
                </div>
            </div>
        </div>
        
        <!-- QR Code para reserva -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-qrcode"></i>
                <h2>Código QR de la Reserva</h2>
            </div>
            <div class="qr-code">
                <div id="qrcode"></div>
                <p style="margin-top: 1rem; color: #666; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> 
                    Este código QR contiene la información de tu reserva. 
                    Preséntalo al llegar a tu destino.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmación para cancelar -->
    <div id="modalCancelar" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Cancelación</h3>
            <p>¿Estás seguro de que deseas cancelar esta reserva?</p>
            
            <?php if($reserva['estado'] == 'confirmada'): ?>
            <div class="alert alert-warning" style="margin: 1rem 0;">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Atención:</strong> Esta reserva ya está confirmada. 
                La cancelación puede estar sujeta a políticas de reembolso.
            </div>
            <?php endif; ?>
            
            <div class="modal-actions">
                <button onclick="cerrarModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> No, mantener reserva
                </button>
                <a href="cancelar_reserva.php?id=<?php echo $reserva_id; ?>" class="btn btn-danger">
                    <i class="fas fa-check"></i> Sí, cancelar reserva
                </a>
            </div>
        </div>
    </div>
    
    <!-- Incluir librería QR Code -->
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
    <script>
        // Generar QR Code
        const qrData = JSON.stringify({
            reserva_id: <?php echo $reserva_id; ?>,
            codigo: "<?php echo $reserva['codigo_reserva']; ?>",
            destino: "<?php echo htmlspecialchars($destino['nombre'] ?? 'Destino no especificado'); ?>",
            fecha_viaje: "<?php echo $reserva['fecha_viaje']; ?>",
            personas: <?php echo $reserva['cantidad_personas']; ?>,
            estado: "<?php echo $reserva['estado']; ?>"
        });
        
        new QRCode(document.getElementById("qrcode"), {
            text: qrData,
            width: 200,
            height: 200,
            colorDark: "#2e8b57",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // Funciones para modal
        function mostrarModalCancelar() {
            document.getElementById('modalCancelar').style.display = 'flex';
        }
        
        function cerrarModal() {
            document.getElementById('modalCancelar').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalCancelar');
            if (event.target == modal) {
                cerrarModal();
            }
        }
        
        // Escuchar tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModal();
            }
        });
    </script>
</body>
</html>