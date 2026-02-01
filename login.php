<?php
// login.php
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
            return $default;
        }
    }
}

// =============== OBTENER CONFIGURACIÓN ACTUAL ===============
$config_keys = [
    // Colores y apariencia
    'primary_color', 'secondary_color', 'accent_color', 'font_family',
    
    // Textos del sitio
    'site_name', 'site_description'
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
    
    $config[$key] = getConfigValue($key, $default);
}

// Si ya está logueado, redirigir según su rol
if(isset($_SESSION['user_id'])) {
    $user_role = $_SESSION['user_role'] ?? 'usuario';
    
    // Determinar redirección según rol
    if($user_role === 'usuario') {
        $redirect = 'usuario/dashboard.php';
    } elseif(in_array($user_role, ['admin', 'superadmin'])) {
        $redirect = 'admin/dashboard.php';
    } else {
        $redirect = 'index.php';
    }
    
    // Si hay un parámetro redirect en la URL, usarlo (excepto si es login.php)
    if(isset($_GET['redirect']) && basename($_GET['redirect']) !== 'login.php') {
        $redirect = $_GET['redirect'];
    }
    
    // Limpiar parámetros de welcome/login_success si ya están en la URL
    $redirect = preg_replace('/[?&](welcome|login_success)=[^&]*/', '', $redirect);
    
    // Asegurar que no quede ?& o ?? en la URL
    $redirect = str_replace(['?&', '??'], '?', $redirect);
    if (substr($redirect, -1) === '?') {
        $redirect = substr($redirect, 0, -1);
    }
    
    // Agregar parámetros de éxito si no están ya
    if(strpos($redirect, '?') === false) {
        $redirect .= '?welcome=1';
    } else {
        $redirect .= '&welcome=1';
    }
    
    $redirect .= '&login_success=1';
    
    header("Location: $redirect");
    exit;
}

// Variable para tipo de login seleccionado (usuario o admin)
$login_type = isset($_GET['type']) && in_array($_GET['type'], ['usuario', 'admin']) ? $_GET['type'] : 'usuario';

// Procesar login
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $login_type = $_POST['login_type'] ?? 'usuario'; // Obtener tipo de login del formulario
    
    // Obtener redirect del formulario
    $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : (isset($_GET['redirect']) ? $_GET['redirect'] : '');
    
    // Verificar credenciales
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($usuario && password_verify($password, $usuario['password'])) {
        // Verificar si el tipo de login coincide con el rol
        $user_role = $usuario['rol'];
        
        // Validar que el tipo de login seleccionado coincida con el rol
        if(($login_type === 'admin' && !in_array($user_role, ['admin', 'superadmin'])) || 
           ($login_type === 'usuario' && in_array($user_role, ['admin', 'superadmin']) && strpos($redirect, 'admin/') === false)) {
            $error = "Credenciales no válidas para acceso como " . ($login_type === 'admin' ? 'administrador' : 'usuario');
        } else {
            // Iniciar sesión
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_email'] = $usuario['email'];
            $_SESSION['user_nombre'] = $usuario['nombre'];
            $_SESSION['user_role'] = $usuario['rol'];
            $_SESSION['user_avatar'] = $usuario['avatar'] ?? '';
            
            // Determinar redirección según rol del usuario
            if(empty($redirect) || basename($redirect) === 'login.php') {
                // No hay redirect válido, usar según rol
                if($user_role === 'usuario') {
                    $redirect = 'usuario/dashboard.php';
                } elseif(in_array($user_role, ['admin', 'superadmin'])) {
                    $redirect = 'admin/dashboard.php';
                } else {
                    $redirect = 'index.php';
                }
            }
            
            // Validar que el redirect no sea el login mismo
            if(basename($redirect) === 'login.php') {
                if($user_role === 'usuario') {
                    $redirect = 'usuario/dashboard.php';
                } elseif(in_array($user_role, ['admin', 'superadmin'])) {
                    $redirect = 'admin/dashboard.php';
                } else {
                    $redirect = 'index.php';
                }
            }
            
            // Limpiar parámetros de welcome/login_success si ya están en la URL
            $redirect = preg_replace('/[?&](welcome|login_success)=[^&]*/', '', $redirect);
            
            // Asegurar que no quede ?& o ?? en la URL
            $redirect = str_replace(['?&', '??'], '?', $redirect);
            if (substr($redirect, -1) === '?') {
                $redirect = substr($redirect, 0, -1);
            }
            
            // Agregar parámetros de éxito
            if(strpos($redirect, '?') === false) {
                $redirect .= '?welcome=1';
            } else {
                $redirect .= '&welcome=1';
            }
            
            $redirect .= '&login_success=1';
            
            // Registrar actividad de login
            if(function_exists('logActivity')) {
                $tipo_login = ($usuario['rol'] === 'admin' || $usuario['rol'] === 'superadmin') ? 'admin_login' : 'user_login';
                $pagina_destino = basename($redirect);
                logActivity($usuario['id'], $tipo_login, "Inicio de sesión exitoso. Redirigiendo a: $pagina_destino");
            }
            
            header("Location: $redirect");
            exit;
        }
    } else {
        $error = "Credenciales incorrectas. Por favor intenta nuevamente.";
    }
}

// Obtener la página de origen
$current_redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
if(empty($current_redirect) && isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    $site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    
    // Extraer la ruta relativa desde la URL completa
    if(strpos($referer, $site_url) === 0) {
        $current_redirect = substr($referer, strlen($site_url));
        
        // Limpiar parámetros no deseados
        $current_redirect = preg_replace('/[?&](logout|unauthorized|welcome|login_success)=[^&]*/', '', $current_redirect);
        
        // No redirigir al login mismo
        if(basename($current_redirect) === 'login.php') {
            $current_redirect = '';
        }
        
        // Si queda vacío o es solo una barra, dejarlo vacío
        if($current_redirect === '/' || $current_redirect === '') {
            $current_redirect = '';
        }
    }
}

// Limpiar parámetros de welcome/login_success si vienen en el redirect
$current_redirect = preg_replace('/[?&](welcome|login_success)=[^&]*/', '', $current_redirect);
$current_redirect = str_replace(['?&', '??'], '?', $current_redirect);
if (substr($current_redirect, -1) === '?') {
    $current_redirect = substr($current_redirect, 0, -1);
}

// Si no hay redirect, dejarlo vacío
if(empty($current_redirect)) {
    $current_redirect = '';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo htmlspecialchars($config['site_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($config['primary_color']); ?>;
            --secondary-color: <?php echo htmlspecialchars($config['secondary_color']); ?>;
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
        
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        
        .login-card {
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
        
        .login-type-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 12px;
        }
        
        .type-btn {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
            font-family: var(--font-family);
        }
        
        .type-btn:hover {
            background: rgba(0,0,0,0.05);
        }
        
        .type-btn.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(var(--primary-color-rgb), 0.2);
        }
        
        .type-btn.active i {
            color: white;
        }
        
        .type-btn i {
            font-size: 1.2rem;
            transition: color 0.3s;
        }
        
        .user-type i {
            color: var(--accent-color);
        }
        
        .admin-type i {
            color: #dc3545;
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
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(var(--primary-color-rgb), 0.3);
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        }
        
        .login-links {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .login-links a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .login-links a:hover {
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
        
        .role-info {
            background: var(--info-bg);
            color: var(--info-color);
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
            border: 1px solid var(--accent-color);
        }
        
        .password-container {
            position: relative;
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
            font-size: 1.1rem;
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-card {
            animation: fadeIn 0.6s ease-out;
        }
        
        .login-content {
            transition: opacity 0.3s ease;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .logo h1 {
                font-size: 1.5rem;
            }
            
            .type-btn {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <h1>
                    <i class="fas fa-leaf"></i>
                    <?php echo htmlspecialchars($config['site_name']); ?>
                </h1>
                <p><?php echo htmlspecialchars($config['site_description']); ?></p>
            </div>
            
            <!-- Selector de tipo de usuario -->
            <div class="login-type-selector">
                <button type="button" class="type-btn user-type <?php echo $login_type === 'usuario' ? 'active' : ''; ?>" 
                        onclick="selectLoginType('usuario')">
                    <i class="fas fa-user"></i>
                    Usuario
                </button>
                <button type="button" class="type-btn admin-type <?php echo $login_type === 'admin' ? 'active' : ''; ?>" 
                        onclick="selectLoginType('admin')">
                    <i class="fas fa-user-shield"></i>
                    Administrador
                </button>
            </div>
            
            <!-- Contenido del formulario -->
            <div class="login-content">
                <!-- Información según el tipo seleccionado -->
                <div class="role-info">
                    <?php if($login_type === 'usuario'): ?>
                    <i class="fas fa-user"></i> 
                    Acceso como usuario. Serás redirigido a tu dashboard personal.
                    <?php else: ?>
                    <i class="fas fa-user-shield"></i> 
                    Acceso administrativo. Requiere credenciales de administrador.
                    <?php endif; ?>
                </div>
                
                <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if(isset($_GET['registered']) && $_GET['registered'] == '1'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>¡Registro exitoso! Ahora puedes iniciar sesión.</div>
                </div>
                <?php endif; ?>
                
                <?php if(isset($_GET['logout']) && $_GET['logout'] == '1'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>¡Sesión cerrada exitosamente!</div>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <!-- Campos ocultos -->
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($current_redirect); ?>">
                    <input type="hidden" id="login_type" name="login_type" value="<?php echo $login_type; ?>">
                    
                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="tu@correo.com" autocomplete="email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" required 
                                   placeholder="••••••••" autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Iniciar Sesión como <?php echo $login_type === 'usuario' ? 'Usuario' : 'Administrador'; ?>
                    </button>
                </form>
                
                <div class="login-links">
                    <?php if($login_type === 'usuario'): ?>
                    <p>
                        ¿No tienes cuenta? 
                        <a href="registro.php?redirect=<?php echo urlencode($current_redirect ?: 'usuario/dashboard.php'); ?>">
                            Regístrate aquí
                        </a>
                    </p>
                    <?php endif; ?>
                    <p>
                        <a href="recuperar.php?type=<?php echo $login_type; ?>&redirect=<?php echo urlencode($current_redirect ?: 'usuario/dashboard.php'); ?>">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </p>
                </div>
                
                <div class="back-link">
                    <a href="index.php">
                        <i class="fas fa-arrow-left"></i>
                        Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Enfocar el campo email al cargar
        document.getElementById('email').focus();
        
        // Variable para guardar los datos del formulario
        let formData = {
            email: '',
            password: ''
        };
        
        // Función para seleccionar tipo de login (SIN RECARGAR LA PÁGINA)
        function selectLoginType(type) {
            // Guardar los datos actuales del formulario
            saveFormData();
            
            // Actualizar botones activos
            document.querySelectorAll('.type-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            if(type === 'usuario') {
                document.querySelector('.user-type').classList.add('active');
            } else {
                document.querySelector('.admin-type').classList.add('active');
            }
            
            // Actualizar el campo hidden
            document.getElementById('login_type').value = type;
            
            // Actualizar el texto del botón de login
            const loginBtn = document.querySelector('.btn-login');
            loginBtn.innerHTML = `<i class="fas fa-sign-in-alt"></i> Iniciar Sesión como ${type === 'usuario' ? 'Usuario' : 'Administrador'}`;
            
            // Actualizar el mensaje de información
            const roleInfo = document.querySelector('.role-info');
            if(type === 'usuario') {
                roleInfo.innerHTML = `<i class="fas fa-user"></i> Acceso como usuario. Serás redirigido a tu dashboard personal.`;
                
                // Mostrar enlace de registro
                document.querySelector('.login-links').innerHTML = `
                    <p>
                        ¿No tienes cuenta? 
                        <a href="registro.php?redirect=<?php echo urlencode($current_redirect ?: 'usuario/dashboard.php'); ?>">
                            Regístrate aquí
                        </a>
                    </p>
                    <p>
                        <a href="recuperar.php?type=usuario&redirect=<?php echo urlencode($current_redirect ?: 'usuario/dashboard.php'); ?>">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </p>
                `;
            } else {
                roleInfo.innerHTML = `<i class="fas fa-user-shield"></i> Acceso administrativo. Requiere credenciales de administrador.`;
                
                // Ocultar enlace de registro para admin
                document.querySelector('.login-links').innerHTML = `
                    <p>
                        <a href="recuperar.php?type=admin&redirect=<?php echo urlencode($current_redirect ?: 'admin/dashboard.php'); ?>">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </p>
                `;
            }
            
            // Restaurar los datos del formulario
            restoreFormData();
            
            // Enfocar el campo email
            document.getElementById('email').focus();
        }
        
        // Función para guardar datos del formulario
        function saveFormData() {
            formData.email = document.getElementById('email').value;
            formData.password = document.getElementById('password').value;
        }
        
        // Función para restaurar datos del formulario
        function restoreFormData() {
            document.getElementById('email').value = formData.email;
            document.getElementById('password').value = formData.password;
        }
        
        // Función para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleButton.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleButton.className = 'fas fa-eye';
            }
        }
        
        // Validación del formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                showAlert('Por favor completa todos los campos.', 'error');
                return false;
            }
            
            // Validar formato de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                showAlert('Por favor ingresa un email válido.', 'error');
                return false;
            }
            
            // Mostrar indicador de carga
            const btn = document.querySelector('.btn-login');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            btn.disabled = true;
            
            // Permitir que el formulario se envíe
            return true;
        });
        
        // Función para mostrar alertas
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
            
            // Insertar después del role-info
            const roleInfo = document.querySelector('.role-info');
            if(roleInfo) {
                roleInfo.parentNode.insertBefore(alertDiv, roleInfo.nextSibling);
            }
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
        
        // Mejorar experiencia de usuario con teclado
        document.addEventListener('keydown', function(e) {
            // Enter en el campo de password envía el formulario
            if (e.key === 'Enter' && document.activeElement.id === 'password') {
                document.getElementById('loginForm').submit();
            }
        });
        
        // Guardar datos cuando el usuario escribe
        document.getElementById('email').addEventListener('input', function() {
            formData.email = this.value;
        });
        
        document.getElementById('password').addEventListener('input', function() {
            formData.password = this.value;
        });
    </script>
</body>
</html>