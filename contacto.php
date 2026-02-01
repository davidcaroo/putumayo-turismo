<?php
// contacto.php - Página de contacto para consultas
session_start();
require_once 'includes/config.php';

$asunto_predefinido = isset($_GET['asunto']) ? htmlspecialchars($_GET['asunto']) : '';
$codigo_reserva = '';

// Extraer código de reserva del asunto si está presente
if (strpos($asunto_predefinido, 'RES-') !== false) {
    $partes = explode(' ', $asunto_predefinido);
    foreach ($partes as $parte) {
        if (strpos($parte, 'RES-') === 0) {
            $codigo_reserva = $parte;
            break;
        }
    }
}

// Obtener información de la reserva si el usuario está logueado y hay código de reserva
$reserva_info = null;
if (isset($_SESSION['user_id']) && !empty($codigo_reserva)) {
    $sql = "SELECT r.*, d.nombre as destino_nombre 
            FROM reservas r 
            LEFT JOIN destinos d ON r.destino_id = d.id 
            WHERE r.codigo_reserva = ? AND r.usuario_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$codigo_reserva, $_SESSION['user_id']]);
    $reserva_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Procesar el formulario de contacto
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $asunto = trim($_POST['asunto']);
    $mensaje = trim($_POST['mensaje']);
    $codigo_reserva_input = trim($_POST['codigo_reserva']);
    
    // Validaciones
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio';
    }
    
    if (empty($email)) {
        $errores[] = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no es válido';
    }
    
    if (empty($asunto)) {
        $errores[] = 'El asunto es obligatorio';
    }
    
    if (empty($mensaje)) {
        $errores[] = 'El mensaje es obligatorio';
    }
    
    if (empty($errores)) {
        // Preparar el mensaje de correo
        $para = "info@putumayoturismo.com"; // Cambiar por el email real
        $titulo = "Consulta desde Putumayo Turismo: " . $asunto;
        
        $contenido = "Nueva consulta recibida:\n\n";
        $contenido .= "Nombre: " . $nombre . "\n";
        $contenido .= "Email: " . $email . "\n";
        $contenido .= "Teléfono: " . ($telefono ?: 'No proporcionado') . "\n";
        $contenido .= "Código de Reserva: " . ($codigo_reserva_input ?: 'No aplica') . "\n";
        $contenido .= "Asunto: " . $asunto . "\n";
        $contenido .= "Mensaje:\n" . $mensaje . "\n\n";
        $contenido .= "Fecha: " . date('d/m/Y H:i:s') . "\n";
        $contenido .= "IP: " . $_SERVER['REMOTE_ADDR'];
        
        $cabeceras = "From: " . $email . "\r\n";
        $cabeceras .= "Reply-To: " . $email . "\r\n";
        $cabeceras .= "X-Mailer: PHP/" . phpversion();
        
        // Enviar correo
        if (mail($para, $titulo, $contenido, $cabeceras)) {
            // También guardar en la base de datos si existe la tabla
            try {
                $sql = "SHOW TABLES LIKE 'contactos'";
                $stmt = $pdo->query($sql);
                
                if ($stmt->rowCount() > 0) {
                    $sql_insert = "INSERT INTO contactos (nombre, email, telefono, asunto, mensaje, codigo_reserva, fecha_creacion) 
                                   VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    $stmt_insert->execute([$nombre, $email, $telefono, $asunto, $mensaje, $codigo_reserva_input]);
                }
            } catch (Exception $e) {
                // Si falla la inserción en BD, solo registramos el error
                error_log("Error al guardar contacto: " . $e->getMessage());
            }
            
            $_SESSION['success'] = 'Mensaje enviado con éxito. Nos pondremos en contacto contigo pronto.';
            header('Location: contacto.php');
            exit();
        } else {
            $errores[] = 'Error al enviar el mensaje. Por favor, intenta nuevamente.';
        }
    }
}

$page_title = 'Contacto - Putumayo Turismo';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
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
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        header p {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        @media (max-width: 992px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        
        .card h2 {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .card h2 i {
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-family: var(--font-family);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.1);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
            width: 100%;
            justify-content: center;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-icon {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .info-content h3 {
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        
        .info-content p {
            color: #666;
            line-height: 1.6;
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
        
        .reserva-info {
            background: #e8f5e9;
            border-left: 4px solid var(--primary-color);
            padding: 1.5rem;
            border-radius: 0 8px 8px 0;
            margin-bottom: 2rem;
        }
        
        .reserva-info h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .reserva-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .reserva-detail {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .reserva-detail .label {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .reserva-detail .value {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .map-container {
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 2rem;
            border: 2px solid var(--border-color);
        }
        
        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
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
        
        .required {
            color: var(--danger-color);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            header {
                padding: 1.5rem;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .reserva-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Migas de pan -->
        <div class="breadcrumb">
            <a href="index.php">Inicio</a> / 
            <span>Contacto</span>
        </div>
        
        <!-- Encabezado -->
        <header>
            <h1><i class="fas fa-headset"></i> Contáctanos</h1>
            <p>¿Tienes preguntas sobre tu reserva o necesitas más información? Estamos aquí para ayudarte.</p>
        </header>
        
        <!-- Mensajes -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($errores) && !empty($errores)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Por favor corrige los siguientes errores:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                    <?php foreach($errores as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Información de reserva (si aplica) -->
        <?php if($reserva_info): ?>
        <div class="reserva-info">
            <h3><i class="fas fa-file-invoice"></i> Consulta sobre tu Reserva</h3>
            <div class="reserva-details">
                <div class="reserva-detail">
                    <div class="label">Código de Reserva</div>
                    <div class="value"><?php echo htmlspecialchars($reserva_info['codigo_reserva']); ?></div>
                </div>
                <?php if(!empty($reserva_info['destino_nombre'])): ?>
                <div class="reserva-detail">
                    <div class="label">Destino</div>
                    <div class="value"><?php echo htmlspecialchars($reserva_info['destino_nombre']); ?></div>
                </div>
                <?php endif; ?>
                <?php if(!empty($reserva_info['fecha_viaje'])): ?>
                <div class="reserva-detail">
                    <div class="label">Fecha de Viaje</div>
                    <div class="value"><?php echo date('d/m/Y', strtotime($reserva_info['fecha_viaje'])); ?></div>
                </div>
                <?php endif; ?>
                <div class="reserva-detail">
                    <div class="label">Estado</div>
                    <div class="value">
                        <?php 
                        $estado_texto = [
                            'pendiente' => 'Pendiente',
                            'confirmada' => 'Confirmada',
                            'cancelada' => 'Cancelada'
                        ];
                        echo $estado_texto[$reserva_info['estado']] ?? ucfirst($reserva_info['estado']);
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="content-wrapper">
            <!-- Formulario de contacto -->
            <div class="card">
                <h2><i class="fas fa-paper-plane"></i> Envíanos un Mensaje</h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Nombre <span class="required">*</span></label>
                        <input type="text" name="nombre" class="form-control" 
                               value="<?php echo isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_name'] ?? '') : htmlspecialchars($_POST['nombre'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_email'] ?? '') : htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Código de Reserva (si aplica)</label>
                        <input type="text" name="codigo_reserva" class="form-control" 
                               value="<?php echo htmlspecialchars($codigo_reserva ?: ($_POST['codigo_reserva'] ?? '')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Asunto <span class="required">*</span></label>
                        <input type="text" name="asunto" class="form-control" 
                               value="<?php echo htmlspecialchars($asunto_predefinido ?: ($_POST['asunto'] ?? '')); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mensaje <span class="required">*</span></label>
                        <textarea name="mensaje" class="form-control" required><?php echo htmlspecialchars($_POST['mensaje'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Enviar Mensaje
                    </button>
                </form>
            </div>
            
            <!-- Información de contacto -->
            <div class="card">
                <h2><i class="fas fa-info-circle"></i> Información de Contacto</h2>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Nuestra Ubicación</h3>
                        <p>Mocoa, Putumayo - Colombia<br>Plaza Principal, Edificio Turístico</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-content">
                        <h3>Teléfonos</h3>
                        <p>+57 302 519 1138 (WhatsApp)<br>Lunes a Viernes: 8:00 AM - 6:00 PM</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Correo Electrónico</h3>
                        <p>info@putumayoturismo.com<br>reservas@putumayoturismo.com<br>Soporte: soporte@putumayoturismo.com</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h3>Horario de Atención</h3>
                        <p>Lunes a Viernes: 8:00 AM - 6:00 PM<br>Sábados: 9:00 AM - 2:00 PM<br>Domingos: Cerrado</p>
                    </div>
                </div>
                
                <!-- Mapa -->
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3987.473241845835!2d-76.64613368570959!3d1.1491602992296027!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e285d0f0a5b3b3b%3A0x5b5b5b5b5b5b5b5b!2sMocoa%2C%20Putumayo%2C%20Colombia!5e0!3m2!1ses!2sco!4v1640000000000!5m2!1ses!2sco" 
                            allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
        
        <!-- Preguntas frecuentes -->
        <div class="card">
            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h2>
            
            <div style="margin-top: 1.5rem;">
                <div style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                    <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-calendar-check"></i> ¿Cómo puedo modificar mi reserva?
                    </h3>
                    <p>Puedes modificar tu reserva desde tu panel de usuario. Si necesitas cambios importantes, contáctanos directamente.</p>
                </div>
                
                <div style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                    <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-undo-alt"></i> ¿Cuál es la política de cancelación?
                    </h3>
                    <p>Las cancelaciones realizadas con más de 48 horas de anticipación reciben reembolso completo. Consulta los términos específicos de tu reserva.</p>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-clock"></i> ¿Cuánto tiempo tardan en responder?
                    </h3>
                    <p>Normalmente respondemos en un plazo de 24-48 horas hábiles. Para emergencias, puedes contactarnos por WhatsApp.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Validación del formulario
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                let valid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.style.borderColor = 'var(--danger-color)';
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Por favor completa todos los campos obligatorios.');
                }
            });
            
            // Resaltar campos al escribir
            const inputs = form.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.style.borderColor = '';
                });
            });
        });
    </script>
</body>
</html>