<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Verificar permisos de administrador
if(!isLoggedIn() || (!hasRole('superadmin') && !hasRole('admin'))) {
    header('Location: ../login.php');
    exit;
}

// Variables de sesión
$user_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? $_SESSION['usuario_rol'] ?? null;

// Función para obtener configuración
function getConfigValue($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE config_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['valor'] : $default;
    } catch(Exception $e) {
        error_log("Error obteniendo configuración $key: " . $e->getMessage());
        return $default;
    }
}

// Función para obtener todas las configuraciones
function getAllConfigs() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT config_key, valor FROM configuracion");
        $configs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $configs[$row['config_key']] = $row['valor'];
        }
        return $configs;
    } catch(Exception $e) {
        error_log("Error obteniendo configuraciones: " . $e->getMessage());
        return [];
    }
}

// Función para guardar configuración
function saveConfig($key, $value) {
    global $pdo;
    try {
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id FROM configuracion WHERE config_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists) {
            // Actualizar
            $stmt = $pdo->prepare("UPDATE configuracion SET valor = ?, updated_at = NOW() WHERE config_key = ?");
            return $stmt->execute([$value, $key]);
        } else {
            // Insertar nuevo
            $stmt = $pdo->prepare("INSERT INTO configuracion (config_key, valor, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            return $stmt->execute([$key, $value]);
        }
    } catch(Exception $e) {
        error_log("Error guardando configuración $key: " . $e->getMessage());
        return false;
    }
}

// Función para subir archivo
function uploadConfigFile($file, $upload_dir, $allowed_types = ['png', 'jpg', 'jpeg', 'svg', 'webp', 'ico']) {
    $result = ['success' => false, 'filename' => '', 'error' => ''];
    
    if($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'Error al subir el archivo';
        return $result;
    }
    
    $filename = basename($file['name']);
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if(!in_array($file_ext, $allowed_types)) {
        $result['error'] = 'Tipo de archivo no permitido. Solo se permiten: ' . implode(', ', $allowed_types);
        return $result;
    }
    
    if($file['size'] > 2 * 1024 * 1024) { // 2MB
        $result['error'] = 'Archivo demasiado grande (máx 2MB)';
        return $result;
    }
    
    // Generar nombre único
    $new_filename = uniqid() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;
    
    // Crear directorio si no existe
    if(!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if(move_uploaded_file($file['tmp_name'], $upload_path)) {
        $result['success'] = true;
        $result['filename'] = $new_filename;
    } else {
        $result['error'] = 'Error al guardar el archivo';
    }
    
    return $result;
}

// Obtener todas las configuraciones existentes en la BD
$config = getAllConfigs();

// Procesar cambios
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Configuración general
    if(isset($_POST['save_general'])) {
        try {
            $fields = ['site_name', 'site_description', 'site_keywords', 'contact_email', 'contact_phone', 'contact_address'];
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    saveConfig($field, trim($_POST[$field]));
                }
            }
            
            $message = "Configuración general guardada correctamente";
            logActivity($user_id, 'update_config', 'Configuración general actualizada');
        } catch(Exception $e) {
            $error = "Error al guardar configuración: " . $e->getMessage();
        }
    }
    
    // Configuración de apariencia
    if(isset($_POST['save_appearance'])) {
        try {
            $fields = ['primary_color', 'secondary_color', 'accent_color', 'font_family', 'logo_text'];
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    saveConfig($field, trim($_POST[$field]));
                }
            }
            
            // Procesar logo si se subió
            if(isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
                $logo_result = uploadConfigFile($_FILES['logo'], '../uploads/config/', ['png', 'jpg', 'jpeg', 'svg', 'webp']);
                if($logo_result['success']) {
                    // Eliminar logo anterior si existe
                    if(isset($config['logo_file']) && $config['logo_file'] && file_exists('../uploads/config/' . $config['logo_file'])) {
                        unlink('../uploads/config/' . $config['logo_file']);
                    }
                    saveConfig('logo_file', $logo_result['filename']);
                } else {
                    $error = "Error al subir logo: " . $logo_result['error'];
                }
            }
            
            // Procesar favicon si se subió
            if(isset($_FILES['favicon']) && $_FILES['favicon']['error'] === 0) {
                $favicon_result = uploadConfigFile($_FILES['favicon'], '../uploads/config/', ['ico', 'png', 'jpg', 'jpeg', 'svg']);
                if($favicon_result['success']) {
                    // Eliminar favicon anterior si existe
                    if(isset($config['favicon_file']) && $config['favicon_file'] && file_exists('../uploads/config/' . $config['favicon_file'])) {
                        unlink('../uploads/config/' . $config['favicon_file']);
                    }
                    saveConfig('favicon_file', $favicon_result['filename']);
                } else {
                    $error = "Error al subir favicon: " . $favicon_result['error'];
                }
            }
            
            if(empty($error)) {
                $message = "Apariencia guardada correctamente";
                logActivity($user_id, 'update_appearance', 'Apariencia del sitio actualizada');
            }
        } catch(Exception $e) {
            $error = "Error al guardar apariencia: " . $e->getMessage();
        }
    }
    
    // Configuración del footer
    if(isset($_POST['save_footer'])) {
        try {
            saveConfig('footer_text', trim($_POST['footer_text']));
            
            // Solo procesar campos si existen en el formulario
            $social_fields = ['facebook_url', 'instagram_url', 'twitter_url', 'whatsapp_number'];
            foreach ($social_fields as $field) {
                if (isset($_POST[$field])) {
                    saveConfig($field, trim($_POST[$field]));
                }
            }
            
            saveConfig('show_social', isset($_POST['show_social']) ? '1' : '0');
            
            $message = "Configuración del footer guardada correctamente";
            logActivity($user_id, 'update_footer', 'Configuración del footer actualizada');
        } catch(Exception $e) {
            $error = "Error al guardar footer: " . $e->getMessage();
        }
    }
    
    // Configuración del carrusel
    if(isset($_POST['save_carousel'])) {
        try {
            saveConfig('carousel_speed', (string)(int)$_POST['carousel_speed']);
            saveConfig('carousel_autoplay', isset($_POST['carousel_autoplay']) ? '1' : '0');
            saveConfig('show_indicators', isset($_POST['show_indicators']) ? '1' : '0');
            saveConfig('show_controls', isset($_POST['show_controls']) ? '1' : '0');
            
            $message = "Configuración del carrusel guardada correctamente";
            logActivity($user_id, 'update_carousel', 'Configuración del carrusel actualizada');
        } catch(Exception $e) {
            $error = "Error al guardar carrusel: " . $e->getMessage();
        }
    }
    
    // Configuración SEO
    if(isset($_POST['save_seo'])) {
        try {
            saveConfig('meta_title', trim($_POST['meta_title']));
            saveConfig('meta_description', trim($_POST['meta_description']));
            saveConfig('meta_keywords', trim($_POST['meta_keywords']));
            saveConfig('enable_og_tags', isset($_POST['enable_og_tags']) ? '1' : '0');
            saveConfig('enable_schema', isset($_POST['enable_schema']) ? '1' : '0');
            
            $message = "Configuración SEO guardada correctamente";
            logActivity($user_id, 'update_seo', 'Configuración SEO actualizada');
        } catch(Exception $e) {
            $error = "Error al guardar configuración SEO: " . $e->getMessage();
        }
    }
    
    // Configuración Redes Sociales
    if(isset($_POST['save_social'])) {
        try {
            $social_fields = [
                'social_facebook', 'social_instagram', 'social_twitter',
                'social_youtube', 'social_linkedin', 'social_whatsapp',
                'social_tiktok'
            ];
            
            foreach ($social_fields as $field) {
                if (isset($_POST[$field])) {
                    saveConfig($field, trim($_POST[$field]));
                }
            }
            
            saveConfig('social_share', isset($_POST['social_share']) ? '1' : '0');
            
            $message = "Configuración de redes sociales guardada correctamente";
            logActivity($user_id, 'update_social', 'Configuración de redes sociales actualizada');
        } catch(Exception $e) {
            $error = "Error al guardar redes sociales: " . $e->getMessage();
        }
    }
    
    // Configuración WhatsApp Chatbot
    if(isset($_POST['save_whatsapp_chatbot'])) {
        try {
            // Configuración básica
            saveConfig('whatsapp_titulo', trim($_POST['whatsapp_titulo']));
            saveConfig('whatsapp_descripcion', trim($_POST['whatsapp_descripcion']));
            saveConfig('whatsapp_mensaje_default', trim($_POST['whatsapp_mensaje_default']));
            
            // Colores
            saveConfig('whatsapp_color_primario', trim($_POST['whatsapp_color_primario']));
            saveConfig('whatsapp_color_secundario', trim($_POST['whatsapp_color_secundario']));
            
            // Configuración de comportamiento
            saveConfig('whatsapp_posicion', trim($_POST['whatsapp_posicion']));
            saveConfig('whatsapp_auto_abrir', isset($_POST['whatsapp_auto_abrir']) ? '1' : '0');
            saveConfig('whatsapp_mostrar_horarios', isset($_POST['whatsapp_mostrar_horarios']) ? '1' : '0');
            saveConfig('whatsapp_mostrar_especialidades', isset($_POST['whatsapp_mostrar_especialidades']) ? '1' : '0');
            
            // Activación
            saveConfig('whatsapp_activo', isset($_POST['whatsapp_activo']) ? '1' : '0');
            
            $message = "Configuración del chatbot WhatsApp guardada correctamente";
            logActivity($user_id, 'update_whatsapp_chatbot', 'Configuración del chatbot WhatsApp actualizada');
        } catch(Exception $e) {
            $error = "Error al guardar configuración WhatsApp: " . $e->getMessage();
        }
    }
    
    // Recargar configuraciones después de guardar cambios
    if($message) {
        $config = getAllConfigs();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sitio - Administración</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($config['primary_color'] ?? '#2E8B57'); ?>;
            --secondary-color: <?php echo htmlspecialchars($config['secondary_color'] ?? '#FF9800'); ?>;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: <?php echo htmlspecialchars($config['font_family'] ?? "'Inter', sans-serif"); ?>;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), #1a472a);
            color: white;
            min-height: 100vh;
            position: fixed;
            width: 250px;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 15px;
            border-radius: 5px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .config-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary-color);
        }
        
        .config-section h3 {
            color: var(--primary-color);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .btn-save {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .btn-save:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            border: 1px solid #ddd;
            display: inline-block;
            margin-right: 10px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .file-preview {
            max-width: 100px;
            max-height: 100px;
            margin: 10px 0;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 5px;
        }
        
        .whatsapp-color-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid #ddd;
            display: inline-block;
            margin-left: 10px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="sidebar col-md-3 col-lg-2 d-md-block">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-cog me-2"></i>Configuración</h4>
                        <small>Administración del Sitio</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#general">
                                <i class="fas fa-globe me-2"></i>General
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#apariencia">
                                <i class="fas fa-paint-brush me-2"></i>Apariencia
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#footer">
                                <i class="fas fa-shoe-prints me-2"></i>Footer
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#carrusel">
                                <i class="fas fa-images me-2"></i>Carrusel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#seo">
                                <i class="fas fa-chart-line me-2"></i>SEO
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#social">
                                <i class="fas fa-share-alt me-2"></i>Redes Sociales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#whatsapp-chatbot">
                                <i class="fab fa-whatsapp me-2"></i>WhatsApp Chatbot
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mt-4 text-center">
                        <a href="../admin/dashboard.php" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Volver al Dashboard
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="main-content col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
                    <h2><i class="fas fa-sliders-h me-2" style="color: var(--primary-color);"></i>Configuración del Sitio</h2>
                    <div>
                        <span class="badge bg-secondary">Admin: <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['nombre'] ?? 'Usuario'); ?></span>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Sección General -->
                <div class="config-section" id="general">
                    <h3><i class="fas fa-globe me-2"></i>Configuración General</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre del Sitio</label>
                                <input type="text" class="form-control" name="site_name" 
                                       value="<?php echo htmlspecialchars($config['site_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email de Contacto</label>
                                <input type="email" class="form-control" name="contact_email" 
                                       value="<?php echo htmlspecialchars($config['contact_email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="contact_phone" 
                                       value="<?php echo htmlspecialchars($config['contact_phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="contact_address" 
                                       value="<?php echo htmlspecialchars($config['contact_address'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Descripción del Sitio</label>
                                <textarea class="form-control" name="site_description" rows="3"><?php echo htmlspecialchars($config['site_description'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Palabras Clave (separadas por comas)</label>
                                <input type="text" class="form-control" name="site_keywords" 
                                       value="<?php echo htmlspecialchars($config['site_keywords'] ?? ''); ?>">
                            </div>
                        </div>
                        <button type="submit" name="save_general" class="btn btn-save">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </form>
                </div>

                <!-- Sección Apariencia -->
                <div class="config-section" id="apariencia">
                    <h3><i class="fas fa-paint-brush me-2"></i>Apariencia</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Color Primario</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <div class="color-preview" style="background: <?php echo htmlspecialchars($config['primary_color'] ?? '#2E8B57'); ?>;"></div>
                                    </span>
                                    <input type="color" class="form-control" name="primary_color" 
                                           value="<?php echo htmlspecialchars($config['primary_color'] ?? '#2E8B57'); ?>">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Color Secundario</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <div class="color-preview" style="background: <?php echo htmlspecialchars($config['secondary_color'] ?? '#FF9800'); ?>;"></div>
                                    </span>
                                    <input type="color" class="form-control" name="secondary_color" 
                                           value="<?php echo htmlspecialchars($config['secondary_color'] ?? '#FF9800'); ?>">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Color de Acento</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <div class="color-preview" style="background: <?php echo htmlspecialchars($config['accent_color'] ?? '#2196F3'); ?>;"></div>
                                    </span>
                                    <input type="color" class="form-control" name="accent_color" 
                                           value="<?php echo htmlspecialchars($config['accent_color'] ?? '#2196F3'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fuente Principal</label>
                                <input type="text" class="form-control" name="font_family" 
                                       value="<?php echo htmlspecialchars($config['font_family'] ?? "'Inter', sans-serif"); ?>">
                                <small class="text-muted">Ej: 'Inter', sans-serif</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Texto Alternativo del Logo</label>
                                <input type="text" class="form-control" name="logo_text" 
                                       value="<?php echo htmlspecialchars($config['logo_text'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Logo del Sitio</label>
                                <input type="file" class="form-control" name="logo" accept=".png,.jpg,.jpeg,.svg,.webp">
                                <?php if(isset($config['logo_file']) && $config['logo_file']): ?>
                                <div class="mt-2">
                                    <small>Logo actual:</small>
                                    <img src="../uploads/config/<?php echo htmlspecialchars($config['logo_file']); ?>" 
                                         class="file-preview" alt="Logo actual">
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Favicon</label>
                                <input type="file" class="form-control" name="favicon" accept=".ico,.png,.jpg,.jpeg,.svg">
                                <?php if(isset($config['favicon_file']) && $config['favicon_file']): ?>
                                <div class="mt-2">
                                    <small>Favicon actual:</small>
                                    <img src="../uploads/config/<?php echo htmlspecialchars($config['favicon_file']); ?>" 
                                         class="file-preview" alt="Favicon actual">
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="submit" name="save_appearance" class="btn btn-save">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </form>
                </div>

                <!-- Sección Footer -->
                <div class="config-section" id="footer">
                    <h3><i class="fas fa-shoe-prints me-2"></i>Configuración del Footer</h3>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Texto del Footer</label>
                                <textarea class="form-control" name="footer_text" rows="2"><?php echo htmlspecialchars($config['footer_text'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Facebook URL</label>
                                <input type="url" class="form-control" name="facebook_url" 
                                       value="<?php echo htmlspecialchars($config['facebook_url'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Instagram URL</label>
                                <input type="url" class="form-control" name="instagram_url" 
                                       value="<?php echo htmlspecialchars($config['instagram_url'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Twitter/X URL</label>
                                <input type="url" class="form-control" name="twitter_url" 
                                       value="<?php echo htmlspecialchars($config['twitter_url'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de WhatsApp</label>
                                <input type="text" class="form-control" name="whatsapp_number" 
                                       value="<?php echo htmlspecialchars($config['whatsapp_number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_social" 
                                           id="show_social" <?php echo (isset($config['show_social']) && $config['show_social'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_social">
                                        Mostrar enlaces a redes sociales en el footer
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="save_footer" class="btn btn-save">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </form>
                </div>

                <!-- Sección Carrusel -->
                <div class="config-section" id="carrusel">
                    <h3><i class="fas fa-images me-2"></i>Configuración del Carrusel</h3>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Velocidad del Carrusel (ms)</label>
                                <input type="number" class="form-control" name="carousel_speed" 
                                       value="<?php echo htmlspecialchars($config['carousel_speed'] ?? '5000'); ?>" min="1000" step="500">
                                <small class="text-muted">Milisegundos entre transiciones</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="carousel_autoplay" 
                                           id="carousel_autoplay" <?php echo (isset($config['carousel_autoplay']) && $config['carousel_autoplay'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="carousel_autoplay">
                                        Reproducción automática del carrusel
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="show_indicators" 
                                           id="show_indicators" <?php echo (isset($config['show_indicators']) && $config['show_indicators'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_indicators">
                                        Mostrar indicadores (puntos)
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_controls" 
                                           id="show_controls" <?php echo (isset($config['show_controls']) && $config['show_controls'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_controls">
                                        Mostrar controles (flechas)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="save_carousel" class="btn btn-save">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </form>
                </div>

                <!-- Sección SEO -->
                <div class="config-section" id="seo">
                    <h3><i class="fas fa-chart-line me-2"></i>Configuración SEO</h3>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Meta Título</label>
                                <input type="text" class="form-control" name="meta_title" 
                                       value="<?php echo htmlspecialchars($config['meta_title'] ?? ''); ?>">
                                <small class="text-muted">Aparece en la pestaña del navegador y resultados de búsqueda</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Meta Descripción</label>
                                <textarea class="form-control" name="meta_description" rows="3"><?php echo htmlspecialchars($config['meta_description'] ?? ''); ?></textarea>
                                <small class="text-muted">Descripción que aparece en resultados de búsqueda</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Meta Palabras Clave</label>
                                <input type="text" class="form-control" name="meta_keywords" 
                                       value="<?php echo htmlspecialchars($config['meta_keywords'] ?? ''); ?>">
                                <small class="text-muted">Palabras clave para SEO (separadas por comas)</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="enable_og_tags" 
                                           id="enable_og_tags" <?php echo (isset($config['enable_og_tags']) && $config['enable_og_tags'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_og_tags">
                                        Habilitar Open Graph Tags (para compartir en redes sociales)
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_schema" 
                                           id="enable_schema" <?php echo (isset($config['enable_schema']) && $config['enable_schema'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_schema">
                                        Habilitar Schema Markup (mejora resultados de búsqueda)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="save_seo" class="btn btn-save">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </form>
                </div>

                <!-- Sección Redes Sociales -->
                <div class="config-section" id="social">
                    <h3><i class="fas fa-share-alt me-2"></i>Redes Sociales</h3>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fab fa-facebook me-2" style="color: #1877F2;"></i>Facebook</label>
                                <input type="url" class="form-control" name="social_facebook" 
                                       value="<?php echo htmlspecialchars($config['social_facebook'] ?? ''); ?>"
                                       placeholder="https://facebook.com/tuempresa">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fab fa-instagram me-2" style="color: #E4405F;"></i>Instagram</label>
                                <input type="url" class="form-control" name="social_instagram" 
                                       value="<?php echo htmlspecialchars($config['social_instagram'] ?? ''); ?>"
                                       placeholder="https://instagram.com/tuempresa">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fab fa-twitter me-2" style="color: #1DA1F2;"></i>Twitter/X</label>
                                <input type="url" class="form-control" name="social_twitter" 
                                       value="<?php echo htmlspecialchars($config['social_twitter'] ?? ''); ?>"
                                       placeholder="https://twitter.com/tuempresa">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fab fa-youtube me-2" style="color: #FF0000;"></i>YouTube</label>
                                <input type="url" class="form-control" name="social_youtube" 
                                       value="<?php echo htmlspecialchars($config['social_youtube'] ?? ''); ?>"
                                       placeholder="https://youtube.com/c/tuempresa">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fab fa-linkedin me-2" style="color: #0A66C2;"></i>LinkedIn</label>
                                <input type="url" class="form-control" name="social_linkedin" 
                                       value="<?php echo htmlspecialchars($config['social_linkedin'] ?? ''); ?>"
                                       placeholder="https://linkedin.com/company/tuempresa">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fab fa-whatsapp me-2" style="color: #25D366;"></i>WhatsApp</label>
                                <input type="text" class="form-control" name="social_whatsapp" 
                                       value="<?php echo htmlspecialchars($config['social_whatsapp'] ?? ''); ?>"
                                       placeholder="+1234567890">
                                <small class="text-muted">Número en formato internacional</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fab fa-tiktok me-2" style="color: #000000;"></i>TikTok</label>
                                <input type="url" class="form-control" name="social_tiktok" 
                                       value="<?php echo htmlspecialchars($config['social_tiktok'] ?? ''); ?>"
                                       placeholder="https://tiktok.com/@tuempresa">
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="social_share" 
                                           id="social_share" <?php echo (isset($config['social_share']) && $config['social_share'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="social_share">
                                        Habilitar botones para compartir en redes sociales
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle me-1"></i>Nota: Solo se mostrarán los íconos de redes sociales que tengan un enlace configurado.
                                </small>
                            </div>
                        </div>
                        <button type="submit" name="save_social" class="btn btn-save">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </form>
                </div>

                <!-- Sección WhatsApp Chatbot -->
                <div class="config-section" id="whatsapp-chatbot">
                    <h3><i class="fab fa-whatsapp me-2"></i>Configuración del Chatbot WhatsApp</h3>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" name="whatsapp_activo" 
                                           id="whatsapp_activo" <?php echo (isset($config['whatsapp_activo']) && $config['whatsapp_activo'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="whatsapp_activo">
                                        <strong>Activar Chatbot de WhatsApp</strong>
                                    </label>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle me-1"></i>El chatbot solo se mostrará si hay asesores configurados en la base de datos.
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Título del Chatbot</label>
                                <input type="text" class="form-control" name="whatsapp_titulo" 
                                       value="<?php echo htmlspecialchars($config['whatsapp_titulo'] ?? 'Chat con Asesores'); ?>"
                                       placeholder="Ej: Chat con Asesores">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Posición</label>
                                <select class="form-select" name="whatsapp_posicion">
                                    <option value="derecha" <?php echo (isset($config['whatsapp_posicion']) && $config['whatsapp_posicion'] == 'derecha') ? 'selected' : ''; ?>>Derecha</option>
                                    <option value="izquierda" <?php echo (isset($config['whatsapp_posicion']) && $config['whatsapp_posicion'] == 'izquierda') ? 'selected' : ''; ?>>Izquierda</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Descripción/Subtítulo</label>
                                <textarea class="form-control" name="whatsapp_descripcion" rows="2"
                                          placeholder="Ej: Selecciona un asesor para chatear"><?php echo htmlspecialchars($config['whatsapp_descripcion'] ?? 'Selecciona un asesor para chatear'); ?></textarea>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Mensaje Predeterminado</label>
                                <textarea class="form-control" name="whatsapp_mensaje_default" rows="3"
                                          placeholder="Este mensaje aparecerá cuando los usuarios inicien el chat"><?php echo htmlspecialchars($config['whatsapp_mensaje_default'] ?? 'Hola, estoy interesado en información sobre turismo en Putumayo'); ?></textarea>
                                <small class="text-muted">Los usuarios podrán modificar este mensaje antes de enviar</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Color Primario</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <div class="whatsapp-color-preview" style="background: <?php echo htmlspecialchars($config['whatsapp_color_primario'] ?? '#25D366'); ?>;"></div>
                                    </span>
                                    <input type="color" class="form-control" name="whatsapp_color_primario" 
                                           value="<?php echo htmlspecialchars($config['whatsapp_color_primario'] ?? '#25D366'); ?>">
                                    <span class="input-group-text"><?php echo htmlspecialchars($config['whatsapp_color_primario'] ?? '#25D366'); ?></span>
                                </div>
                                <small class="text-muted">Color del botón y encabezado</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Color Secundario</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <div class="whatsapp-color-preview" style="background: <?php echo htmlspecialchars($config['whatsapp_color_secundario'] ?? '#128C7E'); ?>;"></div>
                                    </span>
                                    <input type="color" class="form-control" name="whatsapp_color_secundario" 
                                           value="<?php echo htmlspecialchars($config['whatsapp_color_secundario'] ?? '#128C7E'); ?>">
                                    <span class="input-group-text"><?php echo htmlspecialchars($config['whatsapp_color_secundario'] ?? '#128C7E'); ?></span>
                                </div>
                                <small class="text-muted">Color hover y detalles</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Comportamiento del Chatbot</label>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="whatsapp_auto_abrir" 
                                                   id="whatsapp_auto_abrir" <?php echo (isset($config['whatsapp_auto_abrir']) && $config['whatsapp_auto_abrir'] == '1') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="whatsapp_auto_abrir">
                                                Abrir automáticamente al cargar la página
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="whatsapp_mostrar_horarios" 
                                                   id="whatsapp_mostrar_horarios" <?php echo (isset($config['whatsapp_mostrar_horarios']) && $config['whatsapp_mostrar_horarios'] == '1') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="whatsapp_mostrar_horarios">
                                                Mostrar horarios de los asesores
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="whatsapp_mostrar_especialidades" 
                                                   id="whatsapp_mostrar_especialidades" <?php echo (isset($config['whatsapp_mostrar_especialidades']) && $config['whatsapp_mostrar_especialidades'] == '1') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="whatsapp_mostrar_especialidades">
                                                Mostrar especialidades de los asesores
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Gestión de Asesores:</strong> Para agregar, editar o eliminar asesores de WhatsApp, 
                                    <a href="whatsapp-asesores.php" class="alert-link">haz clic aquí</a>.
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <button type="submit" name="save_whatsapp_chatbot" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>Guardar Configuración del Chatbot
                                </button>
                                <a href="whatsapp-asesores.php" class="btn btn-outline-primary ms-2">
                                    <i class="fas fa-users me-2"></i>Gestionar Asesores
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Actualizar vista previa de colores en tiempo real
        document.querySelectorAll('input[type="color"]').forEach(input => {
            input.addEventListener('input', function() {
                // Para colores normales
                const preview = this.closest('.input-group')?.querySelector('.color-preview');
                if(preview) {
                    preview.style.background = this.value;
                }
                
                // Para colores de WhatsApp
                const whatsappPreview = this.closest('.input-group')?.querySelector('.whatsapp-color-preview');
                if(whatsappPreview) {
                    whatsappPreview.style.background = this.value;
                }
                
                // Actualizar texto del valor
                const valueSpan = this.closest('.input-group')?.querySelector('.input-group-text:last-child');
                if(valueSpan) {
                    valueSpan.textContent = this.value;
                }
            });
        });

        // Scroll suave para navegación interna
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetSection = document.querySelector(targetId);
                
                if(targetSection) {
                    // Remover clase active de todos los enlaces
                    document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
                    // Agregar clase active al enlace clickeado
                    this.classList.add('active');
                    
                    // Scroll a la sección
                    targetSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Detectar sección visible para resaltar enlace activo
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('.config-section');
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            
            let currentSection = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                const sectionHeight = section.clientHeight;
                
                if(window.pageYOffset >= sectionTop && window.pageYOffset < sectionTop + sectionHeight) {
                    currentSection = '#' + section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if(link.getAttribute('href') === currentSection) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>