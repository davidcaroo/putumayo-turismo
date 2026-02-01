<?php
// registro.php
session_start();
include 'includes/config.php';

// =============== FUNCIONES PARA OBTENER CONFIGURACIÓN ===============
if(!function_exists('getConfigValue')) {
    function getConfigValue($key, $default = '') {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT valor FROM configuraciones WHERE config_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['valor'] : $default;
        } catch(Exception $e) {
            error_log("Error obteniendo configuración $key: " . $e->getMessage());
            return $default;
        }
    }
}

// =============== OBTENER CONFIGURACIÓN ACTUAL ===============
$config_keys = [
    // Colores y apariencia
    'primary_color', 'secondary_color', 'accent_color', 'font_family',
    
    // Textos del sitio
    'site_name', 'site_description',
    
    // Contacto
    'contact_phone'
];

$config = [];
foreach ($config_keys as $key) {
    // Valores por defecto
    $default = '';
    if ($key === 'site_name') $default = 'Putumayo Turismo';
    if ($key === 'site_description') $default = 'Descubre la belleza del Putumayo';
    if ($key === 'primary_color') $default = '#2e8b57';
    if ($key === 'secondary_color') $default = '#3cb371';
    if ($key === 'accent_color') $default = '#2196f3';
    if ($key === 'font_family') $default = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    if ($key === 'contact_phone') $default = '+57 300 123 4567';
    
    $config[$key] = getConfigValue($key, $default);
}

// Si ya está logueado, redirigir
if(isset($_SESSION['user_id'])) {
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
    header("Location: $redirect?redirected=1");
    exit;
}

// Procesar registro
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validaciones
    $errors = [];
    
    if(empty($nombre)) $errors[] = "El nombre es requerido";
    if(empty($email)) $errors[] = "El email es requerido";
    if(empty($password)) $errors[] = "La contraseña es requerida";
    if($password !== $confirm_password) $errors[] = "Las contraseñas no coinciden";
    
    // Validar formato de email
    if(!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del email no es válido";
    }
    
    // Validar fortaleza de contraseña
    if(strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if($stmt->fetch()) {
        $errors[] = "Este email ya está registrado";
    }
    
    if(empty($errors)) {
        try {
            // Insertar usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, telefono, password, rol, activo, fecha_registro) 
                                   VALUES (?, ?, ?, ?, 'usuario', 1, NOW())");
            
            if($stmt->execute([$nombre, $email, $telefono, $hashed_password])) {
                $user_id = $pdo->lastInsertId();
                
                // Registrar actividad
                if(function_exists('logActivity')) {
                    logActivity($user_id, 'user_register', "Nuevo usuario registrado: $nombre ($email)");
                }
                
                // Redirigir a login con mensaje de éxito
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'reserva.php';
                header("Location: login.php?redirect=" . urlencode($redirect) . "&registered=1");
                exit;
            }
        } catch(PDOException $e) {
            $error = "Error al registrar: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?php echo htmlspecialchars($config['site_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($config['primary_color']); ?>;
            --secondary-color: <?php echo htmlspecialchars($config['secondary-color']); ?>;
            --accent-color: <?php echo htmlspecialchars($config['accent_color']); ?>;
            --font-family: <?php echo htmlspecialchars($config['font_family']); ?>;
            --text-color: #333;
            --bg-color: #f9f9f9;
            --border-color: #e0e0e0;
            --card-bg: #ffffff;
            --shadow-color: rgba(0,0,0,0.1);
            --error-bg: #f8d7da;
            --error-color: #721c24;
            --success-bg: #d4edda;
            --success-color: #155724;
            --warning-bg: #fff3cd;
            --warning-color: #856404;
            --info-bg: #e8f4fd;
            --info-color: #0c5460;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
        }
        
        .register-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px var(--shadow-color);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.8rem;
        }
        
        .logo p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: var(--font-family);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.1);
        }
        
        .btn-register {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-family: var(--font-family);
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(var(--primary-color-rgb), 0.3);
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .register-links {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .register-links a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .register-links a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .alert-error {
            background: var(--error-bg);
            color: var(--error-color);
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: var(--success-bg);
            color: var(--success-color);
            border: 1px solid #c3e6cb;
        }
        
        .alert-warning {
            background: var(--warning-bg);
            color: var(--warning-color);
            border: 1px solid #ffeaa7;
        }
        
        .alert-info {
            background: var(--info-bg);
            color: var(--info-color);
            border: 1px solid #bee5eb;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #666;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: var(--primary-color);
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            transition: all 0.3s;
        }
        
        .password-strength.weak {
            color: var(--error-color);
        }
        
        .password-strength.moderate {
            color: var(--warning-color);
        }
        
        .password-strength.good {
            color: #17a2b8;
        }
        
        .password-strength.strong {
            color: var(--success-color);
        }
        
        .terms-container {
            padding: 1rem;
            background: var(--info-bg);
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
        }
        
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .terms-checkbox input {
            margin-top: 0.3rem;
            flex-shrink: 0;
        }
        
        .terms-checkbox span {
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .terms-checkbox a {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .terms-checkbox a:hover {
            color: var(--accent-color);
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .register-card {
            animation: fadeIn 0.6s ease-out;
        }
        
        .btn-register {
            position: relative;
            overflow: hidden;
        }
        
        .btn-register::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        .btn-register:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(40, 40);
                opacity: 0;
            }
        }
        
        /* Validación de campos */
        .form-group input:invalid {
            border-color: #dc3545;
        }
        
        .form-group input:valid {
            border-color: #28a745;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
        }
        
        .password-container {
            position: relative;
        }
        
        @media (max-width: 480px) {
            .register-card {
                padding: 2rem 1.5rem;
            }
            
            .logo h1 {
                font-size: 1.5rem;
            }
            
            .logo p {
                font-size: 0.9rem;
            }
        }
        
        /* Estilos para iconos */
        .fa-leaf {
            color: var(--primary-color);
        }
        
        .fa-user-plus {
            color: white;
        }
        
        .fa-exclamation-circle {
            color: var(--error-color);
        }
        
        .fa-check-circle {
            color: var(--success-color);
        }
        
        .fa-info-circle {
            color: var(--info-color);
        }
        
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Estilo para el placeholder del teléfono */
        .form-group input::placeholder {
            color: #999;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="logo">
                <h1>
                    <i class="fas fa-leaf"></i>
                    <?php echo htmlspecialchars($config['site_name']); ?>
                </h1>
                <p><?php echo htmlspecialchars($config['site_description']); ?></p>
                
                <?php if(isset($_GET['redirect'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div>Después de registrarte podrás hacer tu reserva</div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo $error; ?></div>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="nombre">Nombre Completo *</label>
                    <input type="text" id="nombre" name="nombre" required 
                           placeholder="Tu nombre completo" autocomplete="name"
                           value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
                           minlength="2" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="email">Correo Electrónico *</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="ejemplo@correo.com" autocomplete="email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" 
                           placeholder="<?php echo htmlspecialchars($config['contact_phone']); ?>" 
                           autocomplete="tel"
                           pattern="[0-9+\-\s()]{10,20}"
                           value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                    <small style="color: #666; font-size: 0.8rem;">Ej: +57 300 123 4567</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña *</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required 
                               placeholder="••••••••" autocomplete="new-password"
                               minlength="6" pattern=".{6,}">
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="password-strength" class="password-strength"></div>
                    <small style="color: #666; font-size: 0.8rem;">Mínimo 6 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña *</label>
                    <div class="password-container">
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="••••••••" autocomplete="new-password">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="password-match" style="margin-top: 0.5rem; font-size: 0.8rem;"></div>
                </div>
                
                <div class="terms-container">
                    <label class="terms-checkbox">
                        <input type="checkbox" id="terminos" name="terminos" required>
                        <span>
                            Acepto los <a href="terminos.php" target="_blank">términos y condiciones</a> 
                            y autorizo el tratamiento de mis datos personales para fines relacionados con el servicio.
                        </span>
                    </label>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i>
                    Crear Cuenta
                </button>
            </form>
            
            <div class="register-links">
                <p>
                    ¿Ya tienes cuenta? 
                    <a href="login.php?redirect=<?php echo isset($_GET['redirect']) ? urlencode($_GET['redirect']) : 'reserva.php'; ?>">
                        Inicia sesión aquí
                    </a>
                </p>
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #666;">
                    <i class="fas fa-shield-alt"></i> Tus datos están protegidos y nunca serán compartidos con terceros.
                </p>
            </div>
            
            <div class="back-link">
                <?php
                $back_url = 'index.php';
                if(isset($_GET['redirect']) && basename($_GET['redirect']) !== 'login.php') {
                    $back_url = $_GET['redirect'];
                }
                ?>
                <a href="<?php echo $back_url; ?>">
                    <i class="fas fa-arrow-left"></i>
                    <?php echo ($back_url === 'index.php') ? 'Volver al inicio' : 'Volver a la página anterior'; ?>
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Enfocar el primer campo
        document.getElementById('nombre').focus();
        
        // Función para mostrar/ocultar contraseña
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleButton = document.querySelector(`#${fieldId} + .password-toggle i`);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleButton.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleButton.className = 'fas fa-eye';
            }
        }
        
        // Validación en tiempo real de fortaleza de contraseña
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthElement = document.getElementById('password-strength');
            
            let strength = 0;
            let message = '';
            let className = '';
            
            // Longitud mínima
            if (password.length >= 6) strength++;
            
            // Tiene números
            if (/[0-9]/.test(password)) strength++;
            
            // Tiene mayúsculas y minúsculas
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            
            // Tiene caracteres especiales
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Determinar nivel de fortaleza
            switch(strength) {
                case 0:
                case 1:
                    message = 'Débil';
                    className = 'weak';
                    break;
                case 2:
                    message = 'Moderada';
                    className = 'moderate';
                    break;
                case 3:
                    message = 'Buena';
                    className = 'good';
                    break;
                case 4:
                    message = 'Fuerte';
                    className = 'strong';
                    break;
            }
            
            if (password.length > 0) {
                strengthElement.textContent = `Fortaleza: ${message}`;
                strengthElement.className = `password-strength ${className}`;
            } else {
                strengthElement.textContent = '';
                strengthElement.className = 'password-strength';
            }
            
            // Verificar coincidencia de contraseñas
            checkPasswordMatch();
        });
        
        // Verificar que las contraseñas coincidan
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchElement = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchElement.textContent = '';
                matchElement.style.color = '';
            } else if (password === confirmPassword) {
                matchElement.textContent = '✓ Las contraseñas coinciden';
                matchElement.style.color = 'var(--success-color)';
            } else {
                matchElement.textContent = '✗ Las contraseñas no coinciden';
                matchElement.style.color = 'var(--error-color)';
            }
        }
        
        // Validación del formulario
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terminos = document.getElementById('terminos').checked;
            
            let errors = [];
            
            if (nombre.length < 2) {
                errors.push('El nombre debe tener al menos 2 caracteres');
            }
            
            if (nombre.length > 100) {
                errors.push('El nombre no puede exceder los 100 caracteres');
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errors.push('Por favor ingresa un email válido');
            }
            
            if (password.length < 6) {
                errors.push('La contraseña debe tener al menos 6 caracteres');
            }
            
            if (password !== confirmPassword) {
                errors.push('Las contraseñas no coinciden');
            }
            
            if (!terminos) {
                errors.push('Debes aceptar los términos y condiciones');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                showAlert(errors.join('<br>'), 'error');
                return false;
            }
            
            // Mostrar carga
            const btn = document.querySelector('.btn-register');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
            btn.disabled = true;
            
            // Restaurar después de 5 segundos por si hay error
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 5000);
            
            return true;
        });
        
        // Función para mostrar alertas personalizadas
        function showAlert(message, type = 'info') {
            // Remover alertas existentes
            const existingAlerts = document.querySelectorAll('.alert:not(.permanent)');
            existingAlerts.forEach(alert => alert.remove());
            
            // Crear nueva alerta
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <div>${message}</div>
            `;
            
            // Insertar después del logo
            const logo = document.querySelector('.logo');
            logo.parentNode.insertBefore(alertDiv, logo.nextSibling);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.style.opacity = '0';
                    alertDiv.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        if (alertDiv.parentNode) {
                            alertDiv.parentNode.removeChild(alertDiv);
                        }
                    }, 300);
                }
            }, 5000);
        }
        
        // Validación en tiempo real del teléfono
        document.getElementById('telefono').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9+\-\s()]/g, '');
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar color de acento dinámico a elementos específicos
            const accentColor = '<?php echo htmlspecialchars($config["accent_color"]); ?>';
            if(accentColor) {
                // Aplicar color de acento a elementos específicos
                const style = document.createElement('style');
                style.textContent = `
                    .btn-register:hover {
                        box-shadow: 0 5px 15px ${accentColor}40 !important;
                    }
                    
                    .register-links a:hover {
                        color: ${accentColor} !important;
                    }
                    
                    .back-link a:hover {
                        color: ${accentColor} !important;
                    }
                    
                    .terms-checkbox a:hover {
                        color: ${accentColor} !important;
                    }
                    
                    .fa-info-circle {
                        color: ${accentColor} !important;
                    }
                `;
                document.head.appendChild(style);
            }
        });
        
        // Mejorar experiencia de usuario con teclado
        document.addEventListener('keydown', function(e) {
            // Enter en el campo de confirmación envía el formulario
            if (e.key === 'Enter' && document.activeElement.id === 'confirm_password') {
                document.getElementById('registerForm').submit();
            }
            
            // Escape cancela
            if (e.key === 'Escape') {
                const backLink = document.querySelector('.back-link a');
                if (backLink && confirm('¿Deseas cancelar el registro?')) {
                    window.location.href = backLink.href;
                }
            }
        });
        
        // Auto-completar para testing (solo en desarrollo)
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            document.addEventListener('DOMContentLoaded', function() {
                // Botón para auto-completar datos de prueba
                const autoFillDiv = document.createElement('div');
                autoFillDiv.style.marginTop = '1rem';
                autoFillDiv.style.textAlign = 'center';
                autoFillDiv.innerHTML = `
                    <div style="font-size: 0.8rem; color: #666; margin-bottom: 0.5rem;">Para testing rápido:</div>
                    <button onclick="autoFillTest()" style="padding: 0.3rem 0.6rem; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                        Rellenar datos de prueba
                    </button>
                `;
                
                // Insertar antes del botón de registro
                const form = document.querySelector('form');
                form.parentNode.insertBefore(autoFillDiv, form.nextSibling);
                
                // Función para auto-completar
                window.autoFillTest = function() {
                    const timestamp = Date.now();
                    document.getElementById('nombre').value = 'Usuario Test ' + timestamp.toString().slice(-4);
                    document.getElementById('email').value = 'test' + timestamp.toString().slice(-6) + '@test.com';
                    document.getElementById('telefono').value = '<?php echo htmlspecialchars($config['contact_phone']); ?>';
                    document.getElementById('password').value = 'password123';
                    document.getElementById('confirm_password').value = 'password123';
                    document.getElementById('terminos').checked = true;
                    
                    // Disparar eventos para actualizar visualizaciones
                    document.getElementById('password').dispatchEvent(new Event('input'));
                    checkPasswordMatch();
                    
                    showAlert('Datos de prueba cargados. Ahora puedes registrar.', 'info');
                };
            });
        }
    </script>
</body>
</html>