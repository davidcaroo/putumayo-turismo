<?php

// Iniciar sesión si no está iniciada
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración - CORREGIDO: Agregué la barra diagonal faltante
$config_file = __DIR__ . '/includes/config.php';
if(file_exists($config_file)) {
    require_once $config_file;
}

// Determinar el modo actual (claro/oscuro)
$modo = isset($_COOKIE['modo']) ? $_COOKIE['modo'] : 'claro';

// Obtener destinos para el menú con manejo de errores robusto
$destinos_menu = [];
try {
    if(isset($pdo) && $pdo instanceof PDO) {
        $stmt_menu = $pdo->prepare("SELECT id, nombre, ubicacion FROM destinos WHERE activo = 1 ORDER BY nombre LIMIT 8");
        $stmt_menu->execute();
        $destinos_menu = $stmt_menu->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error cargando destinos para menú: " . $e->getMessage());
    $destinos_menu = [];
}

// Verificar sesión de usuario
$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['usuario_id']);
$user_name = '';
$user_role = 'usuario';

if($is_logged_in) {
    $user_name = $_SESSION['user_name'] ?? $_SESSION['nombre'] ?? 'Usuario';
    $user_role = $_SESSION['user_role'] ?? $_SESSION['rol'] ?? 'usuario';
}

// Función helper para obtener nombre del rol
if(!function_exists('getRoleName')) {
    function getRoleName($role) {
        $roles = [
            'superadmin' => 'Super Admin',
            'admin' => 'Administrador',
            'usuario' => 'Usuario',
        ];
        return $roles[$role] ?? ucfirst($role);
    }
}

// Cargar configuración de WhatsApp
$whatsapp_config = [];
$whatsapp_asesores = [];
try {
    if(isset($pdo) && $pdo instanceof PDO) {
        // Obtener configuración
        $stmt_config = $pdo->query("SELECT config_key, valor FROM configuracion WHERE config_key LIKE 'whatsapp_%'");
        while($row = $stmt_config->fetch(PDO::FETCH_ASSOC)) {
            $whatsapp_config[$row['config_key']] = $row['valor'];
        }
        
        // Obtener asesores activos
        $stmt_asesores = $pdo->query("SELECT * FROM whatsapp_asesores WHERE activo = 1 ORDER BY orden ASC");
        $whatsapp_asesores = $stmt_asesores->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(Exception $e) {
    error_log("Error cargando configuración WhatsApp: " . $e->getMessage());
}

// Configurar $base_url si no está definido
if(!isset($base_url)) {
    $base_url = '/';
}

// Determinar página actual para resaltar en el menú
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?php echo $modo; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Putumayo Turismo - Descubre la Amazonía Colombiana</title>
    <meta name="description" content="Explora los mejores destinos turísticos del Putumayo, Colombia. Reserva tours, actividades y experiencias únicas en la Amazonía.">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Estilos del Chatbot WhatsApp -->
    <style>
    :root {
        --primary-color: #2E8B57;
        --secondary-color: #267349;
        --text-color: #2c3e50;
        --text-light: #7f8c8d;
        --bg-light: #f8f9fa;
        --card-bg: #ffffff;
        --border-color: #e1e8ed;
        --header-bg: rgba(255, 255, 255, 0.98);
        --whatsapp-primary: <?php echo !empty($whatsapp_config['whatsapp_color_primario']) ? htmlspecialchars($whatsapp_config['whatsapp_color_primario']) : '#25D366'; ?>;
        --whatsapp-secondary: <?php echo !empty($whatsapp_config['whatsapp_color_secundario']) ? htmlspecialchars($whatsapp_config['whatsapp_color_secundario']) : '#128C7E'; ?>;
    }
    
    [data-theme="oscuro"] {
        --text-color: #ecf0f1;
        --text-light: #bdc3c7;
        --bg-light: #1a1a1a;
        --card-bg: #2c3e50;
        --border-color: #34495e;
        --header-bg: rgba(44, 62, 80, 0.98);
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Poppins', sans-serif;
        line-height: 1.6;
        color: var(--text-color);
        background: var(--bg-light);
        transition: background 0.3s, color 0.3s;
        padding-top: 80px;
        position: relative;
        min-height: 100vh;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }
    
    /* Header */
    .header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: var(--header-bg);
        backdrop-filter: blur(10px);
        z-index: 1000;
        box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        transition: all 0.3s;
        height: 80px;
    }
    
    .navbar {
        height: 100%;
    }
    
    .nav-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
        height: 100%;
    }
    
    .nav-logo {
        flex-shrink: 0;
    }
    
    .nav-logo a {
        display: block;
    }
    
    .nav-logo img {
        height: 50px;
        width: auto;
        transition: transform 0.3s;
    }
    
    .nav-logo img:hover {
        transform: scale(1.05);
    }
    
    .nav-menu {
        flex: 1;
        display: flex;
        justify-content: center;
    }
    
    .nav-list {
        display: flex;
        list-style: none;
        gap: 2rem;
        margin: 0;
        padding: 0;
        align-items: center;
    }
    
    .nav-item {
        position: relative;
    }
    
    .nav-link {
        color: var(--text-color);
        text-decoration: none;
        font-weight: 500;
        font-size: 1rem;
        padding: 0.5rem 0;
        transition: color 0.3s;
        position: relative;
        display: inline-block;
    }
    
    .nav-link.active {
        color: var(--primary-color);
        font-weight: 600;
    }
    
    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: var(--primary-color);
        transition: width 0.3s;
    }
    
    .nav-link:hover {
        color: var(--primary-color);
    }
    
    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--primary-color);
        transition: width 0.3s;
    }
    
    .nav-link:hover::after {
        width: 100%;
    }
    
    /* Dropdown en Desktop */
    .dropdown {
        position: relative;
    }
    
    .dropdown-toggle {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        cursor: pointer;
    }
    
    .dropdown-toggle i {
        font-size: 0.7rem;
        transition: transform 0.3s;
    }
    
    .dropdown:hover .dropdown-toggle i {
        transform: rotate(180deg);
    }
    
    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%) translateY(10px);
        min-width: 220px;
        background: var(--card-bg);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        border-radius: 10px;
        padding: 0.5rem 0;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        z-index: 100;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .dropdown:hover .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }
    
    .dropdown-menu li {
        list-style: none;
    }
    
    .dropdown-menu a {
        display: block;
        padding: 0.8rem 1.5rem;
        color: var(--text-color);
        text-decoration: none;
        transition: all 0.3s;
        font-size: 0.95rem;
    }
    
    .dropdown-menu a:hover {
        background: var(--primary-color);
        color: white;
        padding-left: 2rem;
    }
    
    .dropdown-menu a.active {
        background: var(--primary-color);
        color: white;
        padding-left: 2rem;
    }
    
    .dropdown-menu hr {
        margin: 0.5rem 0;
        border: none;
        border-top: 1px solid var(--border-color);
    }
    
    /* Nav Extra (botones y controles) */
    .nav-extra {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-shrink: 0;
    }
    
    .user-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: var(--card-bg);
        border-radius: 25px;
        font-size: 0.9rem;
        border: 1px solid var(--border-color);
        transition: all 0.3s;
    }
    
    .user-indicator:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .user-indicator:hover i {
        color: white;
    }
    
    .user-indicator i {
        color: var(--primary-color);
        transition: color 0.3s;
    }
    
    .user-indicator small {
        color: var(--text-light);
        font-size: 0.8rem;
    }
    
    .user-indicator:hover small {
        color: rgba(255, 255, 255, 0.9);
    }
    
    .desktop-only {
        display: flex;
    }
    
    /* Botón de tema */
    .theme-toggle {
        background: var(--card-bg);
        border: 2px solid var(--border-color);
        color: var(--text-color);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 1.1rem;
    }
    
    .theme-toggle:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
        transform: rotate(30deg);
    }
    
    /* Botón hamburguesa */
    .nav-toggle {
        display: none;
        flex-direction: column;
        gap: 4px;
        cursor: pointer;
        padding: 0.5rem;
        background: transparent;
        border: none;
        z-index: 1001;
        position: relative;
    }
    
    .nav-toggle span {
        width: 25px;
        height: 3px;
        background: var(--text-color);
        border-radius: 2px;
        transition: all 0.3s;
    }
    
    .nav-toggle.active span:nth-child(1) {
        transform: rotate(45deg) translate(6px, 6px);
    }
    
    .nav-toggle.active span:nth-child(2) {
        opacity: 0;
    }
    
    .nav-toggle.active span:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px);
    }
    
    /* ============================================
       ESTILOS DEL CHATBOT WHATSAPP CONFIGURABLE
       ============================================ */
    
    .whatsapp-chatbot-container {
        position: fixed;
        bottom: 20px;
        z-index: 9998;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    /* Posiciones */
    .whatsapp-chatbot-container.right-position {
        right: 20px;
    }
    
    .whatsapp-chatbot-container.left-position {
        left: 20px;
    }
    
    /* Botón flotante */
    .whatsapp-toggle-btn {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        border: none;
        color: white;
        font-size: 28px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        position: relative;
        z-index: 10001;
        background: var(--whatsapp-primary);
    }
    
    .whatsapp-toggle-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        background: var(--whatsapp-secondary);
    }
    
    /* Badge de notificación */
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ff4757;
        color: white;
        font-size: 12px;
        font-weight: bold;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
    }
    
    /* Panel del chat */
    .whatsapp-chat-panel {
        position: absolute;
        bottom: 70px;
        width: 350px;
        max-height: 500px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        display: none;
        flex-direction: column;
        overflow: hidden;
        animation: slideUp 0.3s ease;
        z-index: 10000;
    }
    
    .whatsapp-chatbot-container.right-position .whatsapp-chat-panel {
        right: 0;
    }
    
    .whatsapp-chatbot-container.left-position .whatsapp-chat-panel {
        left: 0;
    }
    
    /* Panel abierto por defecto */
    .whatsapp-chatbot-container.auto-open .whatsapp-chat-panel {
        display: flex;
    }
    
    /* Header del chat */
    .chat-header {
        padding: 20px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        background: var(--whatsapp-primary);
    }
    
    .header-content {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    .whatsapp-icon {
        font-size: 40px;
        opacity: 0.9;
    }
    
    .header-text h4 {
        margin: 0 0 5px 0;
        font-size: 18px;
        font-weight: 600;
    }
    
    .header-text p {
        margin: 0;
        opacity: 0.9;
        font-size: 13px;
    }
    
    .close-chat-btn {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
        opacity: 0.8;
        transition: opacity 0.3s;
        padding: 5px;
    }
    
    .close-chat-btn:hover {
        opacity: 1;
    }
    
    /* Cuerpo del chat */
    .chat-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        max-height: 400px;
    }
    
    /* Lista de asesores */
    .asesores-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .asesor-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .asesor-card:hover {
        background: #e9ecef;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .asesor-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .asesor-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .asesor-info {
        flex: 1;
    }
    
    .asesor-info h5 {
        margin: 0 0 5px 0;
        color: #333;
        font-size: 16px;
        font-weight: 600;
    }
    
    .asesor-cargo {
        margin: 0 0 8px 0;
        color: #666;
        font-size: 14px;
        font-weight: 500;
    }
    
    .asesor-especialidad,
    .asesor-horario {
        margin: 5px 0;
        color: #777;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .asesor-especialidad i,
    .asesor-horario i {
        color: var(--whatsapp-primary);
        font-size: 11px;
    }
    
    /* Botón de chatear */
    .chat-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 15px;
        border-radius: 20px;
        color: white;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        white-space: nowrap;
        background: var(--whatsapp-primary);
    }
    
    .chat-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        background: var(--whatsapp-secondary);
    }
    
    /* Footer del chat */
    .chat-footer {
        padding: 20px;
        border-top: 1px solid #e9ecef;
        background: #f8f9fa;
    }
    
    .custom-message {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .custom-message-input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        resize: none;
        font-size: 14px;
        font-family: inherit;
        min-height: 60px;
    }
    
    .custom-message-input:focus {
        outline: none;
        border-color: var(--whatsapp-primary);
        box-shadow: 0 0 0 2px rgba(37, 211, 102, 0.2);
    }
    
    .update-message-btn {
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 8px;
        width: 40px;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .update-message-btn:hover {
        background: #5a6268;
    }
    
    .disclaimer {
        margin: 0;
        font-size: 11px;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .disclaimer i {
        font-size: 12px;
    }
    
    /* Animaciones del chatbot */
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7);
        }
        70% {
            box-shadow: 0 0 0 15px rgba(37, 211, 102, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(37, 211, 102, 0);
        }
    }
    
    .pulse-animation {
        animation: pulse 2s infinite;
    }
    
    /* Botones */
    .btn {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }
    
    .btn-primary {
        background: var(--primary-color);
        color: white;
    }
    
    .btn-primary:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(46, 139, 87, 0.3);
    }
    
    .btn-outline {
        background: transparent;
        color: var(--primary-color);
        border: 2px solid var(--primary-color);
    }
    
    .btn-outline:hover {
        background: var(--primary-color);
        color: white;
    }
    
    .btn-sm {
        padding: 8px 16px;
        font-size: 0.9rem;
    }
    
    /* Ocultar dropdown en móvil por defecto */
    @media (max-width: 768px) {
        .dropdown-menu {
            display: none !important;
        }
        
        .dropdown-arrow {
            display: none !important;
        }
    }
    
    /* Responsive - MÓVIL (768px o menos) */
    @media (max-width: 768px) {
        body {
            padding-top: 70px;
        }
        
        .header {
            height: 70px;
        }
        
        .nav-container {
            padding: 0 1rem;
        }
        
        /* Overlay para móvil */
        .sidebar-overlay {
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Menú móvil */
        .nav-menu {
            position: fixed;
            top: 70px;
            left: -100%;
            width: 280px;
            height: calc(100vh - 70px);
            background: var(--card-bg);
            backdrop-filter: none;
            padding: 20px;
            transition: left 0.3s ease;
            box-shadow: 2px 0 20px rgba(0,0,0,0.1);
            overflow-y: auto;
            z-index: 999;
            justify-content: flex-start;
        }
        
        .nav-menu.active {
            left: 0;
        }
        
        .nav-list {
            flex-direction: column;
            gap: 0;
            width: 100%;
            margin-top: 0;
        }
        
        .nav-item {
            width: 100%;
            border-bottom: 1px solid var(--border-color);
        }
        
        /* Enlaces en móvil */
        .nav-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            width: 100%;
            cursor: pointer;
            position: relative;
        }
        
        /* Flecha para navegación normal en móvil */
        .mobile-arrow {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
            color: var(--text-light);
        }
        
        /* En móvil, los dropdowns se comportan como enlaces normales */
        .dropdown .nav-link {
            pointer-events: all;
        }
        
        .dropdown-toggle {
            width: 100%;
            justify-content: space-between;
        }
        
        /* Botón hamburguesa visible */
        .nav-toggle {
            display: flex;
        }
        
        /* Indicador de usuario en móvil */
        .user-indicator {
            font-size: 0.8rem;
            padding: 15px 0;
            margin-top: 20px;
            justify-content: center;
            width: 100%;
            border: none;
            background: transparent;
        }
        
        .user-indicator small {
            display: none;
        }
        
        /* Ocultar elementos de desktop */
        .desktop-only {
            display: none;
        }
        
        /* Chatbot WhatsApp ajustado para móvil */
        .whatsapp-chatbot-container {
            bottom: 15px;
        }
        
        .whatsapp-chatbot-container.right-position {
            right: 15px;
        }
        
        .whatsapp-chatbot-container.left-position {
            left: 15px;
        }
        
        .whatsapp-toggle-btn {
            width: 50px;
            height: 50px;
            font-size: 24px;
        }
        
        .whatsapp-chat-panel {
            width: 90vw;
            max-width: 350px;
            bottom: 65px;
            left: 50% !important;
            right: auto !important;
            transform: translateX(-50%);
        }
        
        .chat-body {
            max-height: 60vh;
        }
        
        /* Ocultar indicador de usuario en nav-extra en móvil */
        .nav-extra .user-indicator {
            display: none;
        }
        
        /* Botón de tema visible */
        .nav-extra .theme-toggle {
            display: flex;
        }
    }
    
    /* Responsive - PEQUEÑOS MÓVILES (iPhone XR: 414px de ancho) */
    @media (max-width: 414px) {
        .nav-menu {
            width: 85%;
            max-width: 300px;
        }
        
        .nav-logo img {
            height: 40px;
        }
        
        .nav-link {
            padding: 12px 0;
            font-size: 0.95rem;
        }
        
        .whatsapp-toggle-btn {
            width: 45px;
            height: 45px;
            font-size: 22px;
        }
        
        .notification-badge {
            width: 18px;
            height: 18px;
            font-size: 10px;
        }
    }
    
    /* Responsive - MÓVILES MUY PEQUEÑOS (menos de 360px) */
    @media (max-width: 360px) {
        .nav-menu {
            width: 90%;
            max-width: 280px;
        }
        
        .nav-link {
            font-size: 0.9rem;
        }
        
        .user-indicator {
            font-size: 0.75rem;
        }
    }
    </style>
</head>
<body>

    <!-- Header y Navegación -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <!-- Logo -->
                <div class="nav-logo">
                    <a href="<?php echo $base_url; ?>index.php">
                        <img src="<?php echo $base_url; ?>uploads/logo.png" 
                             alt="Putumayo Turismo" 
                             id="logo-img"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%2250%22%3E%3Crect width=%22200%22 height=%2250%22 fill=%22%232E8B57%22/%3E%3Ctext x=%22100%22 y=%2232%22 font-family=%22Arial%22 font-size=%2218%22 fill=%22white%22 text-anchor=%22middle%22%3EPutumayo Turismo%3C/text%3E%3C/svg%3E';">
                    </a>
                </div>
                
                <!-- Botón hamburguesa -->
                <button class="nav-toggle" id="nav-toggle" aria-label="Menú">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <!-- Overlay para móvil -->
                <div class="sidebar-overlay" id="sidebar-overlay"></div>
                
                <!-- Menú principal -->
                <div class="nav-menu" id="nav-menu">
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                                <span>Inicio</span>
                                <i class="fas fa-chevron-right mobile-arrow"></i>
                            </a>
                        </li>
                        
                        <!-- Destinos: Dropdown en desktop, enlace directo en móvil -->
                        <li class="nav-item dropdown" id="destinos-item">
                            <a href="<?php echo $base_url; ?>destinos.php" class="nav-link dropdown-toggle <?php echo in_array($current_page, ['destinos.php', 'destino-detalle.php']) ? 'active' : ''; ?>">
                                <span>Destinos</span>
                                <i class="fas fa-chevron-down dropdown-arrow"></i>
                                <i class="fas fa-chevron-right mobile-arrow"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if(!empty($destinos_menu)): ?>
                                    <?php foreach($destinos_menu as $destino_item): ?>
                                        <li>
                                            <a href="<?php echo $base_url; ?>destino-detalle.php?id=<?php echo intval($destino_item['id']); ?>">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($destino_item['nombre']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                    <li><hr></li>
                                    <li>
                                        <a href="<?php echo $base_url; ?>destinos.php" class="<?php echo $current_page == 'destinos.php' ? 'active' : ''; ?>">
                                            <i class="fas fa-th"></i>
                                            Ver todos los destinos
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li>
                                        <a href="<?php echo $base_url; ?>destinos.php" class="<?php echo $current_page == 'destinos.php' ? 'active' : ''; ?>">
                                            <i class="fas fa-map"></i>
                                            Ver destinos
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        
                        <!-- Eventos -->
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>eventos.php" class="nav-link <?php echo $current_page == 'eventos.php' ? 'active' : ''; ?>">
                                <span>Eventos</span>
                                <i class="fas fa-chevron-right mobile-arrow"></i>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>galeria.php" class="nav-link <?php echo $current_page == 'galeria.php' ? 'active' : ''; ?>">
                                <span>Galería</span>
                                <i class="fas fa-chevron-right mobile-arrow"></i>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>reservas.php" class="nav-link <?php echo $current_page == 'reservas.php' ? 'active' : ''; ?>">
                                <span>Reservas</span>
                                <i class="fas fa-chevron-right mobile-arrow"></i>
                            </a>
                        </li>
                        
                        <?php if($is_logged_in): ?>
                            <!-- Menú Mi Cuenta: En móvil es enlace directo, en desktop es dropdown -->
                            <li class="nav-item dropdown" id="cuenta-item">
                                <a href="<?php echo $base_url; ?>admin/dashboard.php" class="nav-link dropdown-toggle <?php echo in_array($current_page, ['dashboard.php', 'reserva-detalle.php', 'perfil.php']) ? 'active' : ''; ?>">
                                    <span><i class="fas fa-user-circle"></i> Mi Cuenta</span>
                                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                                    <i class="fas fa-chevron-right mobile-arrow"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="<?php echo $base_url; ?>admin/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                                            <i class="fas fa-tachometer-alt"></i>
                                            Dashboard
                                        </a>
                                    </li>
                                    <?php if(in_array($user_role, ['admin', 'superadmin'])): ?>
                                        <li><hr></li>
                                        <li>
                                            <a href="<?php echo $base_url; ?>admin/gestion-reservas.php" class="<?php echo $current_page == 'gestion-reservas.php' ? 'active' : ''; ?>">
                                                <i class="fas fa-cog"></i>
                                                Administrar Reservas
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li><hr></li>
                                    <li>
                                        <a href="<?php echo $base_url; ?>logout.php" style="color: #dc3545;">
                                            <i class="fas fa-sign-out-alt"></i>
                                            Cerrar Sesión
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a href="<?php echo $base_url; ?>login.php" class="nav-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">
                                    <span>Iniciar Sesión</span>
                                    <i class="fas fa-chevron-right mobile-arrow"></i>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo $base_url; ?>registro.php" class="btn btn-primary btn-sm" style="margin-top: 10px; width: 100%; text-align: center;">
                                    Registrarse
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Información del usuario en móvil -->
                        <?php if($is_logged_in): ?>
                            <li class="nav-item">
                                <div class="user-indicator">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($user_name); ?></span>
                                    <small><?php echo getRoleName($user_role); ?></small>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Controles adicionales (solo escritorio) -->
                <div class="nav-extra">
                    <?php if($is_logged_in): ?>
                        <div class="user-indicator desktop-only">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars($user_name); ?></span>
                            <small><?php echo getRoleName($user_role); ?></small>
                        </div>
                    <?php endif; ?>
                    
                    <button id="theme-toggle" class="theme-toggle" aria-label="Cambiar tema">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </nav>
    </header>

    <!-- ============================================
         CHATBOT WHATSAPP CONFIGURABLE
         ============================================ -->
    <?php if(!empty($whatsapp_asesores)): ?>
    <div class="whatsapp-chatbot-container <?php echo ($whatsapp_config['whatsapp_posicion'] ?? 'derecha') === 'izquierda' ? 'left-position' : 'right-position'; ?> <?php echo ($whatsapp_config['whatsapp_auto_abrir'] ?? '0') === '1' ? 'auto-open' : ''; ?>">
        <!-- Botón flotante -->
        <button class="whatsapp-toggle-btn" 
                style="background: <?php echo htmlspecialchars($whatsapp_config['whatsapp_color_primario'] ?? '#25D366'); ?>;"
                aria-label="Abrir chat de WhatsApp">
            <i class="fab fa-whatsapp"></i>
            <span class="notification-badge"><?php echo count($whatsapp_asesores); ?></span>
        </button>
        
        <!-- Panel del chat -->
        <div class="whatsapp-chat-panel">
            <div class="chat-header" 
                 style="background: <?php echo htmlspecialchars($whatsapp_config['whatsapp_color_primario'] ?? '#25D366'); ?>;">
                <div class="header-content">
                    <div class="whatsapp-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div class="header-text">
                        <h4><?php echo htmlspecialchars($whatsapp_config['whatsapp_titulo'] ?? 'Chat con Asesores'); ?></h4>
                        <p><?php echo htmlspecialchars($whatsapp_config['whatsapp_descripcion'] ?? 'Selecciona un asesor para chatear'); ?></p>
                    </div>
                </div>
                <button class="close-chat-btn" aria-label="Cerrar chat">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="chat-body">
                <div class="asesores-list">
                    <?php foreach ($whatsapp_asesores as $asesor): 
                        $avatar = !empty($asesor['avatar']) ? $asesor['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($asesor['nombre']) . '&background=' . substr(($whatsapp_config['whatsapp_color_secundario'] ?? '#128C7E'), 1) . '&color=fff';
                        $whatsappNumber = preg_replace('/[^0-9+]/', '', $asesor['numero_whatsapp']);
                        if (substr($whatsappNumber, 0, 1) !== '+') {
                            $whatsappNumber = '+57' . ltrim($whatsappNumber, '0');
                        }
                        $whatsappURL = "https://wa.me/{$whatsappNumber}?text=" . urlencode($whatsapp_config['whatsapp_mensaje_default'] ?? 'Hola, estoy interesado en información sobre turismo');
                    ?>
                    <div class="asesor-card" data-asesor-id="<?php echo $asesor['id']; ?>">
                        <div class="asesor-avatar">
                            <img src="<?php echo htmlspecialchars($avatar); ?>" 
                                 alt="<?php echo htmlspecialchars($asesor['nombre']); ?>"
                                 onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($asesor['nombre']); ?>&background=<?php echo substr(($whatsapp_config['whatsapp_color_secundario'] ?? '#128C7E'), 1); ?>&color=fff'">
                        </div>
                        <div class="asesor-info">
                            <h5><?php echo htmlspecialchars($asesor['nombre']); ?></h5>
                            <p class="asesor-cargo"><?php echo htmlspecialchars($asesor['cargo'] ?? 'Asesor'); ?></p>
                            
                            <?php if (($whatsapp_config['whatsapp_mostrar_especialidades'] ?? '1') === '1' && !empty($asesor['especialidad'])): ?>
                            <p class="asesor-especialidad">
                                <i class="fas fa-star"></i>
                                <?php echo htmlspecialchars($asesor['especialidad']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if (($whatsapp_config['whatsapp_mostrar_horarios'] ?? '1') === '1' && !empty($asesor['horario'])): ?>
                            <p class="asesor-horario">
                                <i class="fas fa-clock"></i>
                                <?php echo htmlspecialchars($asesor['horario']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo $whatsappURL; ?>" 
                           target="_blank" 
                           class="chat-btn"
                           style="background: <?php echo htmlspecialchars($whatsapp_config['whatsapp_color_primario'] ?? '#25D366'); ?>;"
                           data-asesor="<?php echo htmlspecialchars($asesor['nombre']); ?>">
                            <i class="fab fa-whatsapp"></i>
                            <span>Chatear</span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="chat-footer">
                    <div class="custom-message">
                        <textarea class="custom-message-input" 
                                  placeholder="Escribe tu mensaje personalizado..."><?php echo htmlspecialchars($whatsapp_config['whatsapp_mensaje_default'] ?? 'Hola, estoy interesado en información sobre turismo'); ?></textarea>
                        <button class="update-message-btn">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <p class="disclaimer">
                        <i class="fas fa-info-circle"></i>
                        Al hacer clic en "Chatear", serás redirigido a WhatsApp
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
    // Menú hamburguesa y dropdowns adaptativos
    document.addEventListener('DOMContentLoaded', function() {
        const navToggle = document.getElementById('nav-toggle');
        const navMenu = document.getElementById('nav-menu');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = themeToggleBtn?.querySelector('i');
        const htmlElement = document.documentElement;
        
        // Estado del menú
        let isMenuOpen = false;
        
        // Función para detectar si es móvil
        function isMobile() {
            return window.innerWidth <= 768;
        }
        
        // Función para abrir el menú
        function openMenu() {
            navMenu.classList.add('active');
            sidebarOverlay.classList.add('active');
            navToggle.classList.add('active');
            document.body.style.overflow = 'hidden';
            isMenuOpen = true;
        }
        
        // Función para cerrar el menú
        function closeMenu() {
            navMenu.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            navToggle.classList.remove('active');
            document.body.style.overflow = '';
            isMenuOpen = false;
            
            // Cerrar todos los dropdowns (solo en desktop)
            if (!isMobile()) {
                closeAllDropdowns();
            }
        }
        
        // Función para cerrar todos los dropdowns
        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
        
        // Función para toggle del menú
        function toggleMenu() {
            if (isMenuOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        }
        
        // Evento para el botón hamburguesa
        if (navToggle) {
            navToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleMenu();
            });
        }
        
        // Evento para cerrar con overlay
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function(e) {
                if (e.target === sidebarOverlay) {
                    closeMenu();
                }
            });
        }
        
        // Evento para cerrar con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isMenuOpen) {
                closeMenu();
            }
        });
        
        // ============================================
        // COMPORTAMIENTO ESPECÍFICO DE DESTINOS
        // ============================================
        const destinosItem = document.getElementById('destinos-item');
        const destinosLink = destinosItem?.querySelector('.dropdown-toggle');
        
        if (destinosItem && destinosLink) {
            // En móvil: comportamiento de enlace directo a destinos.php
            // En desktop: comportamiento hover para dropdown
            destinosLink.addEventListener('click', function(e) {
                if (isMobile()) {
                    // En móvil: Navegar directamente a la página de destinos
                    e.preventDefault();
                    window.location.href = this.href;
                    closeMenu();
                } else {
                    // En desktop: Evitar navegación para permitir hover
                    e.preventDefault();
                }
            });
        }
        
        // ============================================
        // COMPORTAMIENTO ESPECÍFICO DE MI CUENTA
        // ============================================
        const cuentaItem = document.getElementById('cuenta-item');
        const cuentaLink = cuentaItem?.querySelector('.dropdown-toggle');
        
        if (cuentaItem && cuentaLink) {
            cuentaLink.addEventListener('click', function(e) {
                if (isMobile()) {
                    // En móvil: Navegar directamente al dashboard
                    e.preventDefault();
                    window.location.href = this.href;
                    closeMenu();
                } else {
                    // En desktop: Evitar navegación para permitir hover
                    e.preventDefault();
                }
            });
        }
        
        // Dropdowns en desktop (hover) - para Destinos y Mi Cuenta
        if (!isMobile()) {
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                dropdown.addEventListener('mouseenter', function() {
                    this.classList.add('active');
                });
                
                dropdown.addEventListener('mouseleave', function() {
                    this.classList.remove('active');
                });
            });
        }
        
        // Cerrar dropdowns al hacer clic fuera (solo en desktop)
        document.addEventListener('click', function(e) {
            if (!isMobile()) {
                if (!e.target.closest('.dropdown')) {
                    closeAllDropdowns();
                }
            }
        });
        
        // Cerrar menú al hacer clic en enlaces normales (no dropdown)
        document.querySelectorAll('.nav-link[href]:not(.dropdown-toggle)').forEach(link => {
            link.addEventListener('click', function() {
                if (isMobile() && isMenuOpen) {
                    // Verificar que no sea un dropdown
                    if (!this.closest('.dropdown')) {
                        closeMenu();
                    }
                }
            });
        });
        
        // Cerrar menú al redimensionar a desktop
        window.addEventListener('resize', function() {
            if (!isMobile() && isMenuOpen) {
                closeMenu();
            }
        });
        
        // Toggle del tema
        if (themeToggleBtn) {
            // Cargar tema guardado
            const savedTheme = localStorage.getItem('theme') || '<?php echo $modo; ?>';
            htmlElement.setAttribute('data-theme', savedTheme);
            if (themeIcon) {
                themeIcon.className = savedTheme === 'oscuro' ? 'fas fa-sun' : 'fas fa-moon';
            }
            
            themeToggleBtn.addEventListener('click', function() {
                const currentTheme = htmlElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'oscuro' ? 'claro' : 'oscuro';
                
                htmlElement.setAttribute('data-theme', newTheme);
                
                if (themeIcon) {
                    themeIcon.className = newTheme === 'oscuro' ? 'fas fa-sun' : 'fas fa-moon';
                }
                
                // Guardar preferencia
                localStorage.setItem('theme', newTheme);
                document.cookie = 'modo=' + newTheme + '; path=/; max-age=' + (60*60*24*365);
            });
        }

        // ============================================
        // FUNCIONALIDAD DEL CHATBOT WHATSAPP
        // ============================================
        <?php if(!empty($whatsapp_asesores)): ?>
        const chatbotContainer = document.querySelector('.whatsapp-chatbot-container');
        const whatsappToggleBtn = document.querySelector('.whatsapp-toggle-btn');
        const closeChatBtn = document.querySelector('.close-chat-btn');
        const chatPanel = document.querySelector('.whatsapp-chat-panel');
        const customMessageInput = document.querySelector('.custom-message-input');
        const updateMessageBtn = document.querySelector('.update-message-btn');
        const chatButtons = document.querySelectorAll('.chat-btn');
        
        // Mostrar/ocultar panel del chatbot
        if (whatsappToggleBtn) {
            whatsappToggleBtn.addEventListener('click', function() {
                const isVisible = chatPanel.style.display === 'flex';
                chatPanel.style.display = isVisible ? 'none' : 'flex';
                
                // Animar el botón
                this.classList.toggle('pulse-animation');
                
                // Cerrar automáticamente después de 5 minutos si está abierto
                if (!isVisible) {
                    setTimeout(() => {
                        if (chatPanel.style.display === 'flex') {
                            chatPanel.style.display = 'none';
                            this.classList.remove('pulse-animation');
                        }
                    }, 300000); // 5 minutos
                }
            });
        }
        
        // Cerrar panel del chatbot
        if (closeChatBtn) {
            closeChatBtn.addEventListener('click', function() {
                chatPanel.style.display = 'none';
                if (whatsappToggleBtn) {
                    whatsappToggleBtn.classList.remove('pulse-animation');
                }
            });
        }
        
        // Cerrar chatbot al hacer clic fuera
        document.addEventListener('click', function(event) {
            if (chatbotContainer && chatPanel.style.display === 'flex' && 
                !chatbotContainer.contains(event.target) && 
                !whatsappToggleBtn.contains(event.target)) {
                chatPanel.style.display = 'none';
                if (whatsappToggleBtn) {
                    whatsappToggleBtn.classList.remove('pulse-animation');
                }
            }
        });
        
        // Actualizar mensaje personalizado
        if (updateMessageBtn && customMessageInput) {
            updateMessageBtn.addEventListener('click', function() {
                const newMessage = customMessageInput.value.trim();
                if (newMessage) {
                    updateAllChatLinks(newMessage);
                    showWhatsAppNotification('Mensaje actualizado correctamente');
                }
            });
            
            // Permitir Enter para actualizar mensaje
            customMessageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.ctrlKey) {
                    e.preventDefault();
                    updateMessageBtn.click();
                }
            });
        }
        
        // Actualizar todos los enlaces de chat
        function updateAllChatLinks(message) {
            chatButtons.forEach(button => {
                const whatsappNumber = button.getAttribute('href').split('?')[0].split('/').pop();
                const encodedMessage = encodeURIComponent(message);
                button.href = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
            });
        }
        
        // Mostrar notificación del chatbot
        function showWhatsAppNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'whatsapp-notification';
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                bottom: 80px;
                left: 50%;
                transform: translateX(-50%);
                background: var(--whatsapp-primary);
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                font-size: 14px;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Agregar estilos para notificaciones
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { bottom: -50px; opacity: 0; }
                to { bottom: 80px; opacity: 1; }
            }
            
            @keyframes slideOut {
                from { bottom: 80px; opacity: 1; }
                to { bottom: -50px; opacity: 0; }
            }
        `;
        document.head.appendChild(style);
        
        // Animación del botón al cargar
        setTimeout(() => {
            if (whatsappToggleBtn) {
                whatsappToggleBtn.classList.add('pulse-animation');
                setTimeout(() => whatsappToggleBtn.classList.remove('pulse-animation'), 2000);
            }
        }, 1000);
        <?php endif; ?>
    });
    </script>