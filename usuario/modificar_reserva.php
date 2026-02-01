<?php
// usuario/modificar_reserva.php - Modificar una reserva existente

session_start();

// Verificar sesión
if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=usuario/modificar_reserva.php?id=' . $_GET['id']);
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

// Verificar que la reserva existe y pertenece al usuario
$sql = "SELECT r.*, d.nombre as destino_nombre, d.descripcion, d.imagen_principal, 
               a.nombre as actividad_nombre, a.precio as precio_actividad
        FROM reservas r 
        LEFT JOIN destinos d ON r.destino_id = d.id 
        LEFT JOIN actividades a ON r.actividad_id = a.id 
        WHERE r.id = ? AND r.usuario_id = ? AND r.estado IN ('pendiente', 'confirmada')";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$reserva_id, $user_id]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reserva) {
    $_SESSION['error'] = 'Reserva no encontrada o no se puede modificar';
    header('Location: reservas.php');
    exit();
}

// Obtener precio por persona (de la actividad o destino)
$precio_por_persona = $reserva['precio_actividad'] ?? 0;

// Procesar el formulario de modificación
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : $reserva['nombre'];
    $email = isset($_POST['email']) ? trim($_POST['email']) : $reserva['email'];
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : $reserva['telefono'];
    $cantidad_personas = isset($_POST['cantidad_personas']) ? intval($_POST['cantidad_personas']) : 1;
    $fecha_viaje = isset($_POST['fecha_viaje']) ? $_POST['fecha_viaje'] : $reserva['fecha_viaje'];
    $notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
    $metodo_pago = isset($_POST['metodo_pago']) ? trim($_POST['metodo_pago']) : ($reserva['metodo_pago'] ?? 'efectivo');
    
    // Validaciones
    $errores = [];
    
    if(empty($nombre)) {
        $errores[] = 'El nombre es obligatorio';
    }
    
    if(empty($email)) {
        $errores[] = 'El email es obligatorio';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no es válido';
    }
    
    if(empty($telefono)) {
        $errores[] = 'El teléfono es obligatorio';
    }
    
    if($cantidad_personas < 1 || $cantidad_personas > 20) {
        $errores[] = 'La cantidad de personas debe ser entre 1 y 20';
    }
    
    if(empty($fecha_viaje)) {
        $errores[] = 'La fecha de viaje es obligatoria';
    } elseif(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_viaje)) {
        $errores[] = 'Formato de fecha inválido';
    } elseif(strtotime($fecha_viaje) < strtotime(date('Y-m-d'))) {
        $errores[] = 'La fecha de viaje no puede ser anterior a hoy';
    }
    
    // Calcular nuevo total
    $nuevo_total = $cantidad_personas * $precio_por_persona;
    
    // Si no hay errores, actualizar la reserva
    if(empty($errores)) {
        try {
            $pdo->beginTransaction();
            
            // Actualizar reserva
            $sql_update = "UPDATE reservas SET 
                          nombre = ?,
                          email = ?,
                          telefono = ?,
                          cantidad_personas = ?,
                          fecha_viaje = ?,
                          precio_total = ?,
                          notas = ?,
                          metodo_pago = ?,
                          fecha_modificacion = NOW()
                          WHERE id = ? AND usuario_id = ?";
            
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                $nombre,
                $email,
                $telefono,
                $cantidad_personas,
                $fecha_viaje,
                $nuevo_total,
                $notas,
                $metodo_pago,
                $reserva_id,
                $user_id
            ]);
            
            // Registrar historial de modificación
            $sql_historial = "INSERT INTO historial_reservas 
                            (reserva_id, usuario_id, accion, detalles) 
                            VALUES (?, ?, ?, ?)";
            
            $detalles_historial = json_encode([
                'nombre_anterior' => $reserva['nombre'],
                'nombre_nuevo' => $nombre,
                'email_anterior' => $reserva['email'],
                'email_nuevo' => $email,
                'telefono_anterior' => $reserva['telefono'],
                'telefono_nuevo' => $telefono,
                'cantidad_personas_anterior' => $reserva['cantidad_personas'],
                'cantidad_personas_nuevo' => $cantidad_personas,
                'fecha_viaje_anterior' => $reserva['fecha_viaje'],
                'fecha_viaje_nuevo' => $fecha_viaje,
                'precio_total_anterior' => $reserva['precio_total'],
                'precio_total_nuevo' => $nuevo_total,
                'notas_anterior' => $reserva['notas'] ?? '',
                'notas_nuevo' => $notas,
                'metodo_pago_anterior' => $reserva['metodo_pago'] ?? 'efectivo',
                'metodo_pago_nuevo' => $metodo_pago
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt_historial = $pdo->prepare($sql_historial);
            $stmt_historial->execute([
                $reserva_id,
                $user_id,
                'modificacion',
                $detalles_historial
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Reserva modificada exitosamente';
            header('Location: detalle_reserva.php?id=' . $reserva_id);
            exit();
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $errores[] = 'Error al modificar la reserva: ' . $e->getMessage();
        }
    }
}

$page_title = 'Modificar Reserva';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Putumayo Turismo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
            max-width: 1000px;
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
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .modificar-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .modificar-container {
                grid-template-columns: 1fr;
            }
        }
        
        .reserva-info-card, .formulario-card {
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
        }
        
        .info-group {
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.3rem;
            display: block;
            font-size: 0.9rem;
        }
        
        .info-value {
            padding: 0.6rem 0.8rem;
            background: var(--light-color);
            border-radius: 6px;
            border-left: 3px solid var(--primary-color);
            font-size: 0.95rem;
        }
        
        .destino-imagen {
            width: 100%;
            height: 180px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        
        .destino-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .destino-nombre {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .destino-actividad {
            font-size: 1rem;
            color: #666;
            margin-bottom: 1rem;
            font-style: italic;
        }
        
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: var(--font-family);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        select.form-control {
            background: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px;
            padding-right: 2.5rem;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 2px solid transparent;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        
        .resumen-cambios {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.2rem;
            margin-top: 1.5rem;
            border: 2px dashed var(--border-color);
        }
        
        .cambio-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }
        
        .cambio-item:last-child {
            border-bottom: none;
        }
        
        .cambio-label {
            color: #666;
        }
        
        .cambio-valor {
            font-weight: 600;
            text-align: right;
        }
        
        .cambio-diferencia {
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        
        .diferencia-positiva {
            background: #d4edda;
            color: #155724;
        }
        
        .diferencia-negativa {
            background: #f8d7da;
            color: #721c24;
        }
        
        .breadcrumb {
            margin-bottom: 1.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--light-color);
        }
        
        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .precio-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border-left: 4px solid var(--accent-color);
        }
        
        .precio-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .precio-total {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            border-top: 2px solid var(--border-color);
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .campo-requerido::after {
            content: " *";
            color: var(--danger-color);
        }
        
        .info-icon {
            color: var(--primary-color);
            margin-right: 0.5rem;
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
            <a href="detalle_reserva.php?id=<?php echo $reserva_id; ?>">Detalle Reserva</a> / 
            <span>Modificar Reserva</span>
        </div>
        
        <!-- Encabezado -->
        <div class="header">
            <h1><i class="fas fa-edit"></i> Modificar Reserva</h1>
            <a href="detalle_reserva.php?id=<?php echo $reserva_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Detalle
            </a>
        </div>
        
        <!-- Mensajes de error/success -->
        <?php if(isset($errores) && !empty($errores)): ?>
            <div class="alert alert-danger">
                <h3><i class="fas fa-exclamation-triangle"></i> Errores encontrados:</h3>
                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                    <?php foreach($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="modificar-container">
            <!-- Información de la reserva actual -->
            <div class="reserva-info-card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i>
                    <h2>Reserva Actual</h2>
                    <span class="badge badge-<?php echo $reserva['estado'] == 'pendiente' ? 'warning' : 'success'; ?>">
                        <?php echo ucfirst($reserva['estado']); ?>
                    </span>
                </div>
                
                <?php if(!empty($reserva['imagen_principal'])): ?>
                <div class="destino-imagen">
                    <img src="../uploads/destinos/<?php echo htmlspecialchars($reserva['imagen_principal']); ?>" 
                         alt="<?php echo htmlspecialchars($reserva['destino_nombre']); ?>">
                </div>
                <?php endif; ?>
                
                <h3 class="destino-nombre"><?php echo htmlspecialchars($reserva['destino_nombre'] ?? 'Destino no especificado'); ?></h3>
                
                <?php if(!empty($reserva['actividad_nombre'])): ?>
                <p class="destino-actividad">
                    <i class="fas fa-hiking"></i> <?php echo htmlspecialchars($reserva['actividad_nombre']); ?>
                </p>
                <?php endif; ?>
                
                <div class="info-group">
                    <span class="info-label">Código de Reserva:</span>
                    <div class="info-value">
                        <i class="fas fa-hashtag info-icon"></i> 
                        <?php echo htmlspecialchars($reserva['codigo_reserva'] ?? 'RES-' . str_pad($reserva_id, 6, '0', STR_PAD_LEFT)); ?>
                    </div>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Fecha de Creación:</span>
                    <div class="info-value">
                        <i class="fas fa-calendar-plus info-icon"></i> 
                        <?php echo date('d/m/Y H:i', strtotime($reserva['fecha_creacion'])); ?>
                    </div>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Solicitante:</span>
                    <div class="info-value">
                        <i class="fas fa-user info-icon"></i> 
                        <?php echo htmlspecialchars($reserva['nombre']); ?>
                    </div>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Email:</span>
                    <div class="info-value">
                        <i class="fas fa-envelope info-icon"></i> 
                        <?php echo htmlspecialchars($reserva['email']); ?>
                    </div>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Teléfono:</span>
                    <div class="info-value">
                        <i class="fas fa-phone info-icon"></i> 
                        <?php echo htmlspecialchars($reserva['telefono']); ?>
                    </div>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Personas:</span>
                    <div class="info-value">
                        <i class="fas fa-users info-icon"></i> 
                        <?php echo $reserva['cantidad_personas']; ?> personas
                    </div>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Fecha de Viaje:</span>
                    <div class="info-value">
                        <i class="fas fa-plane-departure info-icon"></i> 
                        <?php echo $reserva['fecha_viaje'] ? date('d/m/Y', strtotime($reserva['fecha_viaje'])) : 'No especificada'; ?>
                    </div>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Total Actual:</span>
                    <div class="info-value" style="border-left-color: var(--success-color); font-weight: 600;">
                        <i class="fas fa-money-bill-wave info-icon"></i> 
                        $<?php echo number_format($reserva['precio_total'] ?? 0, 2); ?>
                    </div>
                </div>
                
                <?php if(!empty($reserva['notas'])): ?>
                <div class="info-group">
                    <span class="info-label">Notas:</span>
                    <div class="info-value">
                        <i class="fas fa-sticky-note info-icon"></i> 
                        <?php echo nl2br(htmlspecialchars($reserva['notas'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Formulario de modificación -->
            <div class="formulario-card">
                <div class="card-header">
                    <i class="fas fa-edit"></i>
                    <h2>Modificar Reserva</h2>
                </div>
                
                <form method="POST" action="" id="modificarForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label campo-requerido">Nombre completo</label>
                            <input type="text" name="nombre" class="form-control" 
                                   value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : htmlspecialchars($reserva['nombre']); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label campo-requerido">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control" 
                                   value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : htmlspecialchars($reserva['telefono']); ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label campo-requerido">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($reserva['email']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label campo-requerido">Fecha de Viaje</label>
                            <input type="text" name="fecha_viaje" class="form-control fecha-picker" 
                                   value="<?php echo isset($_POST['fecha_viaje']) ? $_POST['fecha_viaje'] : $reserva['fecha_viaje']; ?>"
                                   placeholder="Seleccionar fecha" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label campo-requerido">Cantidad de Personas</label>
                            <input type="number" name="cantidad_personas" class="form-control" 
                                   min="1" max="20" 
                                   value="<?php echo isset($_POST['cantidad_personas']) ? $_POST['cantidad_personas'] : $reserva['cantidad_personas']; ?>" 
                                   required onchange="calcularNuevoTotal()">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Método de Pago</label>
                        <select name="metodo_pago" class="form-control">
                            <option value="efectivo" <?php echo (isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : ($reserva['metodo_pago'] ?? 'efectivo')) == 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                            <option value="tarjeta_credito" <?php echo (isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : ($reserva['metodo_pago'] ?? 'efectivo')) == 'tarjeta_credito' ? 'selected' : ''; ?>>Tarjeta de Crédito</option>
                            <option value="tarjeta_debito" <?php echo (isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : ($reserva['metodo_pago'] ?? 'efectivo')) == 'tarjeta_debito' ? 'selected' : ''; ?>>Tarjeta de Débito</option>
                            <option value="transferencia" <?php echo (isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : ($reserva['metodo_pago'] ?? 'efectivo')) == 'transferencia' ? 'selected' : ''; ?>>Transferencia Bancaria</option>
                            <option value="paypal" <?php echo (isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : ($reserva['metodo_pago'] ?? 'efectivo')) == 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Notas Adicionales</label>
                        <textarea name="notas" class="form-control" rows="4" 
                                  placeholder="Requerimientos especiales, alergias, preferencias de comida, etc."><?php echo isset($_POST['notas']) ? htmlspecialchars($_POST['notas']) : htmlspecialchars($reserva['notas'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Resumen de cambios en tiempo real -->
                    <div class="resumen-cambios" id="resumenCambios">
                        <h3><i class="fas fa-exchange-alt"></i> Resumen de Cambios</h3>
                        
                        <div class="cambio-item">
                            <span class="cambio-label">Precio por Persona:</span>
                            <span class="cambio-valor">$<?php echo number_format($precio_por_persona, 2); ?></span>
                        </div>
                        
                        <div class="cambio-item">
                            <span class="cambio-label">Personas:</span>
                            <span class="cambio-valor">
                                <span id="personasActual"><?php echo $reserva['cantidad_personas']; ?></span> → 
                                <span id="personasNuevo"><?php echo $reserva['cantidad_personas']; ?></span>
                            </span>
                        </div>
                        
                        <div class="cambio-item">
                            <span class="cambio-label">Total:</span>
                            <span class="cambio-valor">
                                $<span id="totalActual"><?php echo number_format($reserva['precio_total'] ?? 0, 2); ?></span> → 
                                $<span id="totalNuevo"><?php echo number_format($reserva['precio_total'] ?? 0, 2); ?></span>
                                <span class="cambio-diferencia" id="diferenciaTotal"></span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="precio-info">
                        <div class="precio-item">
                            <span>Precio por persona:</span>
                            <span>$<span id="precioPersona"><?php echo number_format($precio_por_persona, 2); ?></span></span>
                        </div>
                        <div class="precio-item">
                            <span>Número de personas:</span>
                            <span id="numPersonasDisplay"><?php echo $reserva['cantidad_personas']; ?></span>
                        </div>
                        <div class="precio-total">
                            <span>Nuevo total:</span>
                            <span>$<span id="nuevoTotalDisplay"><?php echo number_format($reserva['precio_total'] ?? 0, 2); ?></span></span>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Importante:</strong> 
                        <?php if($reserva['estado'] == 'confirmada'): ?>
                            Esta reserva ya está confirmada. Los cambios pueden estar sujetos a políticas de modificación.
                        <?php else: ?>
                            Al modificar la reserva, el sistema calculará automáticamente el nuevo total.
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="detalle_reserva.php?id=<?php echo $reserva_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script>
        // Inicializar datepicker
        flatpickr('.fecha-picker', {
            dateFormat: 'Y-m-d',
            locale: 'es',
            minDate: 'today',
            disableMobile: true
        });
        
        // Datos de la reserva actual
        const precioPorPersona = <?php echo $precio_por_persona; ?>;
        const personasActual = <?php echo $reserva['cantidad_personas']; ?>;
        const totalActual = <?php echo $reserva['precio_total'] ?? 0; ?>;
        
        function calcularNuevoTotal() {
            const inputPersonas = document.querySelector('[name="cantidad_personas"]');
            const personasNuevo = parseInt(inputPersonas.value) || 1;
            const nuevoTotal = personasNuevo * precioPorPersona;
            const diferencia = nuevoTotal - totalActual;
            
            // Actualizar displays
            document.getElementById('personasNuevo').textContent = personasNuevo;
            document.getElementById('numPersonasDisplay').textContent = personasNuevo;
            document.getElementById('totalNuevo').textContent = nuevoTotal.toFixed(2);
            document.getElementById('nuevoTotalDisplay').textContent = nuevoTotal.toFixed(2);
            
            // Actualizar diferencia
            const diferenciaElement = document.getElementById('diferenciaTotal');
            if(Math.abs(diferencia) > 0.01) {
                diferenciaElement.textContent = (diferencia > 0 ? '+' : '') + diferencia.toFixed(2);
                diferenciaElement.className = 'cambio-diferencia ' + 
                    (diferencia > 0 ? 'diferencia-negativa' : 'diferencia-positiva');
                diferenciaElement.style.display = 'inline-block';
            } else {
                diferenciaElement.style.display = 'none';
            }
        }
        
        // Calcular al cargar
        document.addEventListener('DOMContentLoaded', calcularNuevoTotal);
        
        // Validación del formulario
        document.getElementById('modificarForm').addEventListener('submit', function(e) {
            const personas = parseInt(document.querySelector('[name="cantidad_personas"]').value);
            const fechaViaje = document.querySelector('[name="fecha_viaje"]').value;
            const hoy = new Date().toISOString().split('T')[0];
            
            if(personas < 1 || personas > 20) {
                alert('La cantidad de personas debe ser entre 1 y 20');
                e.preventDefault();
                return false;
            }
            
            if(fechaViaje && fechaViaje < hoy) {
                alert('La fecha de viaje no puede ser anterior a hoy');
                e.preventDefault();
                return false;
            }
            
            if(!confirm('¿Estás seguro de modificar esta reserva?\n\nSe registrarán los cambios en el historial.')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Actualizar automáticamente al cambiar valores
        document.querySelector('[name="cantidad_personas"]').addEventListener('input', calcularNuevoTotal);
    </script>
</body>
</html>