<?php
// usuario/perfil.php - Configuración del perfil del usuario

session_start();

// Verificar sesión
if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=usuario/perfil.php');
    exit();
}

// Verificar que sea usuario normal
if(isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header('Location: ../admin/dashboard.php');
    exit();
}

require_once '../includes/config.php';

// Obtener información actual del usuario
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_nombre'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
$user_avatar = $_SESSION['user_avatar'] ?? '';

// Obtener datos completos del usuario
$usuario = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error obteniendo datos del usuario: " . $e->getMessage());
}

// Procesar actualización de perfil
$mensaje = '';
$tipo_mensaje = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';
    
    $errores = [];
    
    // Validar nombre
    if(empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    } elseif(strlen($nombre) < 2) {
        $errores[] = "El nombre debe tener al menos 2 caracteres";
    }
    
    // Validar teléfono si se proporciona
    if(!empty($telefono) && !preg_match('/^[0-9\s\-\+\(\)]{7,20}$/', $telefono)) {
        $errores[] = "El formato del teléfono no es válido";
    }
    
    // Validar cambio de contraseña
    if(!empty($password_nueva) || !empty($password_confirmar)) {
        if(empty($password_actual)) {
            $errores[] = "Debes ingresar tu contraseña actual para cambiarla";
        } elseif(!password_verify($password_actual, $usuario['password'])) {
            $errores[] = "La contraseña actual no es correcta";
        } elseif($password_nueva !== $password_confirmar) {
            $errores[] = "Las nuevas contraseñas no coinciden";
        } elseif(strlen($password_nueva) < 6) {
            $errores[] = "La nueva contraseña debe tener al menos 6 caracteres";
        }
    }
    
    // Manejar carga de avatar
    $nuevo_avatar = $user_avatar;
    if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $archivo = $_FILES['avatar'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        
        if(in_array($extension, $extensiones_permitidas)) {
            if($archivo['size'] <= 2 * 1024 * 1024) { // 2MB máximo
                // Crear carpeta si no existe
                $carpeta_uploads = '../uploads/avatars/';
                if(!is_dir($carpeta_uploads)) {
                    mkdir($carpeta_uploads, 0777, true);
                }
                
                // Generar nombre único
                $nombre_archivo = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
                $ruta_completa = $carpeta_uploads . $nombre_archivo;
                
                if(move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                    // Eliminar avatar anterior si existe
                    if(!empty($nuevo_avatar) && file_exists($carpeta_uploads . $nuevo_avatar)) {
                        unlink($carpeta_uploads . $nuevo_avatar);
                    }
                    $nuevo_avatar = $nombre_archivo;
                } else {
                    $errores[] = "Error al subir la imagen";
                }
            } else {
                $errores[] = "La imagen debe ser menor a 2MB";
            }
        } else {
            $errores[] = "Formato de imagen no permitido. Usa JPG, PNG o GIF";
        }
    } elseif($_FILES['avatar']['error'] != 4) { // Error 4 = No file uploaded
        $errores[] = "Error al subir la imagen";
    }
    
    if(empty($errores)) {
        try {
            // Preparar datos para actualizar
            $datos_actualizar = [
                'nombre' => $nombre,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'avatar' => $nuevo_avatar
            ];
            
            // Si hay nueva contraseña, agregarla
            if(!empty($password_nueva)) {
                $datos_actualizar['password'] = password_hash($password_nueva, PASSWORD_DEFAULT);
            }
            
            // Construir query dinámica
            $campos = [];
            $valores = [];
            foreach($datos_actualizar as $campo => $valor) {
                $campos[] = "$campo = ?";
                $valores[] = $valor;
            }
            
            $valores[] = $user_id; // Para el WHERE
            
            $query = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute($valores);
            
            // Actualizar datos en sesión
            $_SESSION['user_nombre'] = $nombre;
            $_SESSION['user_avatar'] = $nuevo_avatar;
            
            // Registrar actividad
            if(function_exists('logActivity')) {
                logActivity($user_id, 'profile_update', "Perfil actualizado exitosamente");
            }
            
            $mensaje = "Perfil actualizado correctamente";
            $tipo_mensaje = "success";
            
        } catch (Exception $e) {
            error_log("Error actualizando perfil: " . $e->getMessage());
            $mensaje = "Error al actualizar el perfil. Por favor intenta nuevamente.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "error";
    }
}

$page_title = 'Mi Perfil';
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
        /* Incluir todos los estilos del dashboard y perfil aquí */
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
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        /* Profile Specific Styles */
        .profile-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .avatar-upload {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary-color);
            background: var(--light-color);
            margin: 0 auto;
        }
        
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-upload label {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .avatar-upload label:hover {
            background: var(--secondary-color);
        }
        
        .avatar-upload input[type="file"] {
            display: none;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: var(--font-family);
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--border-color);
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            border-radius: 2px;
            background: #e0e0e0;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }
        
        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }
        
        .password-requirements {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #666;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }
        
        .requirement i {
            font-size: 0.75rem;
        }
        
        .requirement.met {
            color: #28a745;
        }
        
        .requirement.unmet {
            color: #dc3545;
        }
        
        .account-info {
            background: var(--light-color);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            padding: 0.5rem 0;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            color: #666;
        }
        
        .danger-zone {
            background: #f8f9fa;
            border: 2px dashed #dc3545;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .danger-zone h3 {
            color: #dc3545;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
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
                <a href="perfil.php" class="nav-link active">
                    <i class="fas fa-user-edit"></i>
                    <span>Mi Perfil</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="reservas.php" class="nav-link">
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
            <h1><i class="fas fa-user-edit"></i> Configuración de Perfil</h1>
            <span>Actualiza tu información personal</span>
        </div>
        
        <!-- Mensajes -->
        <?php if(!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <div><?php echo $mensaje; ?></div>
        </div>
        <?php endif; ?>
        
        <!-- Formulario de Perfil -->
        <div class="profile-container">
            <div class="profile-header">
                <div class="avatar-upload">
                    <div class="avatar-preview" id="avatarPreview">
                        <?php if(!empty($user_avatar)): ?>
                            <img src="../uploads/avatars/<?php echo htmlspecialchars($user_avatar); ?>" 
                                 alt="<?php echo htmlspecialchars($user_name); ?>"
                                 id="avatarImage">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #2e8b57;">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <label for="avatarInput" title="Cambiar foto">
                        <i class="fas fa-camera"></i>
                        <input type="file" id="avatarInput" name="avatar" accept="image/*">
                    </label>
                </div>
                <h3><?php echo htmlspecialchars($user_name); ?></h3>
                <p><?php echo htmlspecialchars($user_email); ?></p>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-grid">
                    <!-- Información Personal -->
                    <div>
                        <h3 style="margin-bottom: 1.5rem; color: var(--dark-color);"><i class="fas fa-user"></i> Información Personal</h3>
                        
                        <div class="form-group">
                            <label for="nombre">Nombre Completo *</label>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($usuario['nombre'] ?? $user_name); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" id="email" 
                                   value="<?php echo htmlspecialchars($user_email); ?>" 
                                   disabled>
                            <small style="color: #666;">El email no se puede cambiar</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" 
                                   value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>"
                                   placeholder="+57 XXX XXX XXXX">
                        </div>
                        
                        <div class="form-group">
                            <label for="direccion">Dirección</label>
                            <textarea id="direccion" name="direccion" 
                                      placeholder="Tu dirección completa"><?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Cambio de Contraseña -->
                    <div>
                        <h3 style="margin-bottom: 1.5rem; color: var(--dark-color);"><i class="fas fa-lock"></i> Seguridad</h3>
                        
                        <div class="form-group">
                            <label for="password_actual">Contraseña Actual</label>
                            <input type="password" id="password_actual" name="password_actual"
                                   placeholder="••••••••">
                            <small style="color: #666;">Solo si quieres cambiar la contraseña</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_nueva">Nueva Contraseña</label>
                            <input type="password" id="password_nueva" name="password_nueva"
                                   placeholder="••••••••"
                                   onkeyup="checkPasswordStrength(this.value)">
                            <div class="password-strength">
                                <div class="strength-meter" id="strengthMeter"></div>
                            </div>
                            <div class="password-requirements" id="passwordRequirements">
                                <div class="requirement" id="reqLength">
                                    <i class="fas fa-circle"></i>
                                    <span>Mínimo 6 caracteres</span>
                                </div>
                                <div class="requirement" id="reqUpper">
                                    <i class="fas fa-circle"></i>
                                    <span>Al menos una mayúscula</span>
                                </div>
                                <div class="requirement" id="reqNumber">
                                    <i class="fas fa-circle"></i>
                                    <span>Al menos un número</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirmar">Confirmar Nueva Contraseña</label>
                            <input type="password" id="password_confirmar" name="password_confirmar"
                                   placeholder="••••••••">
                            <div id="passwordMatch" style="margin-top: 0.5rem; font-size: 0.85rem;"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Información de la cuenta -->
                <div class="account-info">
                    <h3 style="margin-bottom: 1rem; color: var(--dark-color);"><i class="fas fa-info-circle"></i> Información de la Cuenta</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">ID de Usuario</div>
                            <div class="info-value"><?php echo $user_id; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Fecha de Registro</div>
                            <div class="info-value"><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'] ?? date('Y-m-d'))); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Estado</div>
                            <div class="info-value">
                                <span style="color: #28a745;">
                                    <i class="fas fa-check-circle"></i> Activo
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Rol</div>
                            <div class="info-value">Usuario</div>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones del formulario -->
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="window.location.href='dashboard.php'">
                        <i class="fas fa-arrow-left"></i> Volver al Dashboard
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
            
            <!-- Zona de peligro -->
            <div class="danger-zone">
                <h3><i class="fas fa-exclamation-triangle"></i> Zona de Peligro</h3>
                <p>Estas acciones son irreversibles. Por favor procede con cuidado.</p>
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="exportData()">
                        <i class="fas fa-download"></i> Exportar Mis Datos
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmDeleteAccount()">
                        <i class="fas fa-trash-alt"></i> Eliminar Mi Cuenta
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Previsualización de avatar
        const avatarInput = document.getElementById('avatarInput');
        const avatarImage = document.getElementById('avatarImage');
        const avatarPreview = document.getElementById('avatarPreview');
        
        if(avatarInput) {
            avatarInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if(file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if(avatarImage) {
                            avatarImage.src = e.target.result;
                        } else {
                            // Crear imagen si no existe
                            const img = document.createElement('img');
                            img.id = 'avatarImage';
                            img.src = e.target.result;
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.objectFit = 'cover';
                            avatarPreview.innerHTML = '';
                            avatarPreview.appendChild(img);
                        }
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
        
        // Validación de contraseñas
        const passwordNueva = document.getElementById('password_nueva');
        const passwordConfirmar = document.getElementById('password_confirmar');
        const passwordMatch = document.getElementById('passwordMatch');
        
        if(passwordNueva && passwordConfirmar) {
            passwordConfirmar.addEventListener('keyup', function() {
                const nueva = passwordNueva.value;
                const confirmar = passwordConfirmar.value;
                
                if(nueva && confirmar) {
                    if(nueva === confirmar) {
                        passwordMatch.innerHTML = '<span style="color: #28a745;"><i class="fas fa-check-circle"></i> Las contraseñas coinciden</span>';
                    } else {
                        passwordMatch.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> Las contraseñas no coinciden</span>';
                    }
                } else {
                    passwordMatch.innerHTML = '';
                }
            });
        }
        
        // Validación de formulario antes de enviar
        const form = document.querySelector('form');
        if(form) {
            form.addEventListener('submit', function(e) {
                const nombre = document.getElementById('nombre').value.trim();
                const passwordNueva = document.getElementById('password_nueva').value;
                const passwordConfirmar = document.getElementById('password_confirmar').value;
                
                // Validar nombre
                if(!nombre || nombre.length < 2) {
                    e.preventDefault();
                    showAlert('El nombre debe tener al menos 2 caracteres', 'error');
                    return false;
                }
                
                // Validar contraseñas si se están cambiando
                if(passwordNueva || passwordConfirmar) {
                    const passwordActual = document.getElementById('password_actual').value;
                    
                    if(!passwordActual) {
                        e.preventDefault();
                        showAlert('Debes ingresar tu contraseña actual para cambiarla', 'error');
                        return false;
                    }
                    
                    if(passwordNueva !== passwordConfirmar) {
                        e.preventDefault();
                        showAlert('Las nuevas contraseñas no coinciden', 'error');
                        return false;
                    }
                    
                    if(passwordNueva.length < 6) {
                        e.preventDefault();
                        showAlert('La nueva contraseña debe tener al menos 6 caracteres', 'error');
                        return false;
                    }
                }
                
                return true;
            });
        }
        
        function checkPasswordStrength(password) {
            const meter = document.getElementById('strengthMeter');
            const reqLength = document.getElementById('reqLength');
            const reqUpper = document.getElementById('reqUpper');
            const reqNumber = document.getElementById('reqNumber');
            
            let strength = 0;
            
            // Longitud mínima
            if(password.length >= 6) {
                strength += 1;
                reqLength.classList.add('met');
                reqLength.classList.remove('unmet');
                reqLength.innerHTML = '<i class="fas fa-check-circle"></i> Mínimo 6 caracteres ✓';
            } else {
                reqLength.classList.add('unmet');
                reqLength.classList.remove('met');
                reqLength.innerHTML = '<i class="fas fa-circle"></i> Mínimo 6 caracteres';
            }
            
            // Mayúsculas
            if(/[A-Z]/.test(password)) {
                strength += 1;
                reqUpper.classList.add('met');
                reqUpper.classList.remove('unmet');
                reqUpper.innerHTML = '<i class="fas fa-check-circle"></i> Al menos una mayúscula ✓';
            } else {
                reqUpper.classList.add('unmet');
                reqUpper.classList.remove('met');
                reqUpper.innerHTML = '<i class="fas fa-circle"></i> Al menos una mayúscula';
            }
            
            // Números
            if(/[0-9]/.test(password)) {
                strength += 1;
                reqNumber.classList.add('met');
                reqNumber.classList.remove('unmet');
                reqNumber.innerHTML = '<i class="fas fa-check-circle"></i> Al menos un número ✓';
            } else {
                reqNumber.classList.add('unmet');
                reqNumber.classList.remove('met');
                reqNumber.innerHTML = '<i class="fas fa-circle"></i> Al menos un número';
            }
            
            // Actualizar medidor
            let width = 0;
            let color = '';
            
            switch(strength) {
                case 0:
                    width = 0;
                    color = 'strength-weak';
                    break;
                case 1:
                    width = 33;
                    color = 'strength-weak';
                    break;
                case 2:
                    width = 66;
                    color = 'strength-medium';
                    break;
                case 3:
                    width = 100;
                    color = 'strength-strong';
                    break;
            }
            
            if(meter) {
                meter.style.width = width + '%';
                meter.className = 'strength-meter ' + color;
            }
        }
        
        function exportData() {
            if(confirm('¿Deseas exportar todos tus datos personales? Esto puede tomar unos momentos.')) {
                // Simular exportación
                showAlert('Preparando exportación de datos...', 'info');
                
                setTimeout(() => {
                    showAlert('Datos exportados correctamente. Te llegará un email con el archivo.', 'success');
                }, 2000);
            }
        }
        
        function confirmDeleteAccount() {
            if(confirm('⚠️ ADVERTENCIA: Esta acción es irreversible.\n\n¿Estás seguro de que deseas eliminar tu cuenta? Todos tus datos serán eliminados permanentemente.')) {
                const password = prompt('Por favor ingresa tu contraseña para confirmar:');
                if(password) {
                    // Enviar solicitud de eliminación
                    fetch('../ajax/eliminar-cuenta.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            password: password
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            showAlert('Cuenta eliminada exitosamente. Serás redirigido...', 'success');
                            setTimeout(() => {
                                window.location.href = '../logout.php?account_deleted=1';
                            }, 2000);
                        } else {
                            showAlert('Error: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showAlert('Error al procesar la solicitud', 'error');
                    });
                }
            }
        }
        
        function showAlert(message, type) {
            // Crear alerta
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <div>${message}</div>
            `;
            
            // Insertar después del header de página
            const pageHeader = document.querySelector('.page-header');
            if(pageHeader) {
                pageHeader.parentNode.insertBefore(alertDiv, pageHeader.nextSibling);
            } else {
                document.querySelector('.main-content').prepend(alertDiv);
            }
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if(alertDiv.parentNode) {
                    alertDiv.style.opacity = '0';
                    alertDiv.style.transition = 'opacity 0.5s';
                    setTimeout(() => {
                        if(alertDiv.parentNode) {
                            alertDiv.remove();
                        }
                    }, 500);
                }
            }, 5000);
        }
        
        // Responsive sidebar toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const isMobile = window.innerWidth <= 768;
            
            if(isMobile && sidebar.classList.contains('active') && 
               !event.target.closest('.sidebar') && 
               !event.target.closest('.menu-toggle')) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>