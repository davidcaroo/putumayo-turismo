<?php
// reservas.php - Página principal de reservas
session_start();
include 'includes/config.php';

// =============== FUNCIONES PARA OBTENER CONFIGURACIÓN ===============
if (!function_exists('getConfigValue')) {
    function getConfigValue($key, $default = '')
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT valor FROM configuraciones WHERE config_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['valor'] : $default;
        } catch (Exception $e) {
            error_log("Error obteniendo configuración $key: " . $e->getMessage());
            return $default;
        }
    }
}

// =============== OBTENER CONFIGURACIÓN ACTUAL ===============
$config_keys = [
    // Colores y apariencia
    'primary_color',
    'secondary_color',
    'accent_color',
    'font_family',

    // Textos del sitio
    'site_name',
    'site_description',
    'site_keywords',

    // Contacto
    'contact_phone',
    'contact_email',
    'contact_address',

    // Redes sociales
    'social_whatsapp',
    'social_facebook',
    'social_instagram',
    'social_twitter'
];

$config = [];
foreach ($config_keys as $key) {
    // Valores por defecto
    $default = '';
    if ($key === 'site_name') $default = 'Putumayo Turismo';
    if ($key === 'site_description') $default = 'Descubre la belleza del Putumayo';
    if ($key === 'primary_color') $default = '#2e8b57';
    if ($key === 'secondary_color') $default = '#267349';
    if ($key === 'accent_color') $default = '#2196f3';
    if ($key === 'font_family') $default = "'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    if ($key === 'contact_phone') $default = '+57 300 123 4567';
    if ($key === 'contact_email') $default = 'reservas@putumayoturismo.com';
    if ($key === 'contact_address') $default = 'Mocoa, Putumayo, Colombia';
    if ($key === 'social_whatsapp') $default = 'https://wa.me/573001234567';

    $config[$key] = getConfigValue($key, $default);
}

// Variables CSS dinámicas
$primary_color = htmlspecialchars($config['primary_color']);
$secondary_color = htmlspecialchars($config['secondary_color']);
$accent_color = htmlspecialchars($config['accent_color']);

// Configuración de página
$page_title = "Reserva tu Aventura - " . $config['site_name'];
$page_description = "Reserva tus experiencias en Putumayo. Tours, aventuras y actividades turísticas en la Amazonía colombiana.";

// ========== URL AMIGABLE: /reserva/{id}/{slug} ===========
$reserva_id = null;
$reserva_slug = null;
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $slug = $_GET['slug'];
    if (preg_match('/^([0-9]+)-(.+)$/', $slug, $matches)) {
        $reserva_id = (int)$matches[1];
        $reserva_slug = $matches[2];
    } elseif (preg_match('/^([0-9]+)$/', $slug, $matches)) {
        $reserva_id = (int)$matches[1];
    } else {
        $reserva_slug = $slug;
    }
}

// Compatibilidad con parámetro antiguo (?success=)
if (!$reserva_id && isset($_GET['success']) && is_numeric($_GET['success'])) {
    $reserva_id = intval($_GET['success']);
    $_SESSION['reserva_success'] = $reserva_id;
    header("Location: reservas.php");
    exit;
}

// Verificar si el usuario está logueado
if (isset($_SESSION['user_id'])) {
    $logueado = true;
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'] ?? 'usuario';

    // Obtener datos del usuario para prellenar el formulario
    $stmt = $pdo->prepare("SELECT nombre, email, telefono FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si viene de redirección con parámetro GET SOLO si está logueado
    if (isset($_GET['redirected']) && $_GET['redirected'] == '1') {
        $_SESSION['redirect_message'] = "Ya puedes realizar tu reserva. ¡Bienvenido!";
    }

    // Verificar si viene de login con parámetro welcome
    if (isset($_GET['welcome']) && $_GET['welcome'] == '1') {
        $_SESSION['redirect_message'] = "¡Bienvenido! Ya puedes realizar tu reserva.";
    }
} else {
    $logueado = false;
    // Si no está logueado y viene directamente, redirigir al login
    if (!isset($_GET['preview'])) {
        // Guardar la URL actual para redirigir después del login
        $current_url = $_SERVER['REQUEST_URI'];
        $_SESSION['redirect_url'] = $current_url;
        header("Location: login.php?redirect=" . urlencode($current_url));
        exit;
    }
}

// Verificar mensajes de sesión
$redirect_message = isset($_SESSION['redirect_message']) ? $_SESSION['redirect_message'] : null;
$success_message = null;
$reserva_id = null;

// Verificar si hay un mensaje de éxito de reserva en sesión
if (isset($_SESSION['reserva_success']) && is_numeric($_SESSION['reserva_success'])) {
    $reserva_id = $_SESSION['reserva_success'];
    $success_message = "¡Reserva enviada con éxito! Tu número de reserva es #" . $reserva_id . ". Te contactaremos pronto para confirmar.";

    // Limpiar el mensaje de sesión después de mostrarlo
    unset($_SESSION['reserva_success']);
}

// Limpiar otros mensajes de sesión después de mostrarlos
if (isset($_SESSION['redirect_message'])) {
    unset($_SESSION['redirect_message']);
}

// Obtener destinos disponibles
$stmt = $pdo->prepare("SELECT * FROM destinos WHERE activo = 1 ORDER BY nombre");
$stmt->execute();
$destinos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener condiciones de transporte desde la base de datos
$stmt = $pdo->prepare("SELECT * FROM condiciones_transporte WHERE activo = 1 ORDER BY orden");
$stmt->execute();
$condiciones_transporte = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener servicios disponibles
$stmt = $pdo->prepare("SELECT * FROM servicios_reserva WHERE activo = 1 ORDER BY categoria, nombre");
$stmt->execute();
$servicios_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar servicios por categoría
$servicios_por_categoria = [];
foreach ($servicios_disponibles as $servicio) {
    $categoria = $servicio['categoria'] ?? 'General';
    if (!isset($servicios_por_categoria[$categoria])) {
        $servicios_por_categoria[$categoria] = [];
    }
    $servicios_por_categoria[$categoria][] = $servicio;
}

// Inicializar variables para actividades
$actividades = [];
$destino_seleccionado = null;

// Obtener actividades del destino seleccionado
if (isset($_GET['destino']) && is_numeric($_GET['destino'])) {
    $destino_seleccionado = $_GET['destino'];
    $stmt = $pdo->prepare("SELECT * FROM actividades WHERE destino_id = ? AND activo = 1 ORDER BY nombre");
    $stmt->execute([$destino_seleccionado]);
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (isset($_POST['destino_id']) && is_numeric($_POST['destino_id'])) {
    $destino_seleccionado = $_POST['destino_id'];
    $stmt = $pdo->prepare("SELECT * FROM actividades WHERE destino_id = ? AND activo = 1 ORDER BY nombre");
    $stmt->execute([$destino_seleccionado]);
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Procesar reserva SOLO si el usuario está logueado
if ($logueado && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Usar datos del usuario logueado, pero permitir modificar el teléfono
    $nombre = $usuario['nombre'] ?? ($_POST['nombre'] ?? '');
    $email = $usuario['email'] ?? ($_POST['email'] ?? '');
    $telefono = $_POST['telefono'] ?? ''; // Siempre tomar del formulario

    // Validar datos requeridos
    if (empty($nombre) || empty($email) || empty($telefono)) {
        $error = "Nombre, email y teléfono son campos obligatorios";
    } else {
        // Actualizar teléfono en la base de datos si ha cambiado
        if (!empty($telefono) && $telefono != ($usuario['telefono'] ?? '')) {
            try {
                $update_stmt = $pdo->prepare("UPDATE usuarios SET telefono = ? WHERE id = ?");
                $update_stmt->execute([$telefono, $user_id]);
            } catch (PDOException $e) {
                error_log("Error al actualizar teléfono: " . $e->getMessage());
            }
        }

        $destino_id = $_POST['destino_id'] ?? 0;
        $actividad_id = $_POST['actividad_id'] ?? 0;
        $fecha_viaje = $_POST['fecha_viaje'] ?? '';
        $cantidad_personas = $_POST['cantidad_personas'] ?? 1;
        $comentarios = $_POST['comentarios'] ?? '';

        // Validar datos del viaje
        if (empty($destino_id) || empty($actividad_id) || empty($fecha_viaje) || empty($cantidad_personas)) {
            $error = "Todos los campos del viaje son obligatorios";
        } else {
            // Obtener servicios seleccionados
            $servicios_seleccionados = $_POST['servicios'] ?? [];
            $servicios_json = !empty($servicios_seleccionados) ? json_encode($servicios_seleccionados) : null;

            // Obtener condiciones de transporte desde base de datos
            $condiciones_texto = "";
            foreach ($condiciones_transporte as $condicion) {
                $condiciones_texto .= "{$condicion['titulo']}: {$condicion['descripcion']}\n";
            }

            // Agregar condiciones a los comentarios
            if (!empty($condiciones_texto)) {
                $comentarios .= "\n\n--- CONDICIONES DE TRANSPORTE ---\n" . $condiciones_texto;
            }

            // Agregar servicios seleccionados a los comentarios
            if (!empty($servicios_seleccionados)) {
                $comentarios .= "\n\n--- SERVICIOS CONTRATADOS ---\n";
                $total_servicios = 0;
                foreach ($servicios_seleccionados as $servicio_id) {
                    foreach ($servicios_disponibles as $servicio) {
                        if ($servicio['id'] == $servicio_id) {
                            $precio = floatval($servicio['precio']);
                            $comentarios .= "- {$servicio['nombre']}";
                            if ($precio > 0) {
                                $comentarios .= " (\${$precio})";
                                $total_servicios += $precio;
                            }
                            $comentarios .= "\n";
                        }
                    }
                }
                if ($total_servicios > 0) {
                    $comentarios .= "\nTotal adicional por servicios: \${$total_servicios}\n";
                }
            }

            try {
                // Verificar estructura de la tabla reservas
                $stmt_check = $pdo->query("DESCRIBE reservas");
                $table_info = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

                // Extraer nombres de columnas
                $columns = [];
                foreach ($table_info as $col) {
                    $columns[] = $col['Field'];
                }

                // Determinar qué columnas existen
                $has_nombre = in_array('nombre', $columns);
                $has_email = in_array('email', $columns);
                $has_telefono = in_array('telefono', $columns);
                $has_comentarios = in_array('comentarios', $columns);
                $has_servicios_seleccionados = in_array('servicios_seleccionados', $columns);

                // Preparar la consulta SQL dinámicamente
                $sql = "INSERT INTO reservas (";
                $sql .= "usuario_id, destino_id, actividad_id, fecha_viaje, cantidad_personas";

                if ($has_nombre) $sql .= ", nombre";
                if ($has_email) $sql .= ", email";
                if ($has_telefono) $sql .= ", telefono";
                if ($has_comentarios) $sql .= ", comentarios";
                if ($has_servicios_seleccionados) $sql .= ", servicios_seleccionados";

                $sql .= ", estado, fecha_creacion) VALUES (?, ?, ?, ?, ?";

                $params = [$user_id, $destino_id, $actividad_id, $fecha_viaje, $cantidad_personas];

                if ($has_nombre) {
                    $sql .= ", ?";
                    $params[] = $nombre;
                }
                if ($has_email) {
                    $sql .= ", ?";
                    $params[] = $email;
                }
                if ($has_telefono) {
                    $sql .= ", ?";
                    $params[] = $telefono;
                }
                if ($has_comentarios) {
                    $sql .= ", ?";
                    $params[] = $comentarios;
                }
                if ($has_servicios_seleccionados) {
                    $sql .= ", ?";
                    $params[] = $servicios_json;
                }

                $sql .= ", 'pendiente', NOW())";

                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    $reserva_id = $pdo->lastInsertId();

                    // Guardar el ID de reserva en sesión para mostrar mensaje después de redirección
                    $_SESSION['reserva_success'] = $reserva_id;

                    // Redirigir para evitar reenvío del formulario
                    header("Location: reservas.php");
                    exit;
                } else {
                    $error = "Error al procesar la reserva. Por favor intenta nuevamente.";
                }
            } catch (PDOException $e) {
                $error = "Error al guardar la reserva: " . $e->getMessage();
                error_log("Error en reserva: " . $e->getMessage());
                error_log("SQL ejecutado: " . ($sql ?? 'No definido'));
                error_log("Parámetros: " . print_r($params ?? [], true));
            }
        }
    }
}

// Incluir header después de configurar las variables de sesión
include_once 'includes/header.php';
?>

<style>
    /* Variables CSS dinámicas según configuración */
    :root {
        --primary-color: <?php echo $primary_color; ?>;
        --secondary-color: <?php echo $secondary_color; ?>;
        --accent-color: <?php echo $accent_color; ?>;
        --font-family: <?php echo htmlspecialchars($config['font_family']); ?>;
        --text-color: #2c3e50;
        --text-light: #7f8c8d;
        --bg-color: #ffffff;
        --bg-light: #f8f9fa;
        --card-bg: #ffffff;
        --border-color: #e1e8ed;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --info-color: #17a2b8;
    }

    [data-theme="oscuro"] {
        --text-color: #f8f9fa;
        --text-light: #bdc3c7;
        --bg-color: #121212;
        --bg-light: #1a1a1a;
        --card-bg: #1e1e1e;
        --border-color: #333;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --danger-color: #f44336;
        --info-color: #00bcd4;
    }

    body {
        font-family: var(--font-family);
        background-color: var(--bg-light);
        color: var(--text-color);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Hero Section */
    .reserva-hero {
        background: linear-gradient(135deg, #a8e6cf 0%, #8fd9a8 50%, #7bc89b 100%);
        color: #1a4d2e;
        padding: 120px 0 80px;
        text-align: center;
        margin-bottom: 50px;
        position: relative;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .reserva-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg,
                rgba(168, 230, 207, 0.3),
                rgba(123, 200, 155, 0.3));
        z-index: 1;
    }

    .reserva-hero .container {
        position: relative;
        z-index: 2;
    }

    .reserva-hero h1 {
        font-size: 3.5rem;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(255, 255, 255, 0.5);
        color: #0d3b1f;
        font-weight: 700;
    }

    .reserva-hero p {
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto 2rem;
        color: #1a4d2e;
        font-weight: 500;
    }

    /* Información del usuario */
    .user-info-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        padding: 12px 24px;
        border-radius: 25px;
        margin-top: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .user-info-badge i {
        font-size: 1.3rem;
        color: var(--warning-color);
    }

    /* Alertas */
    .alert {
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        display: flex;
        align-items: flex-start;
        gap: 15px;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background: rgba(40, 167, 69, 0.1);
        border: 1px solid rgba(40, 167, 69, 0.3);
        color: var(--success-color);
    }

    .alert-error {
        background: rgba(220, 53, 69, 0.1);
        border: 1px solid rgba(220, 53, 69, 0.3);
        color: var(--danger-color);
    }

    .alert-info {
        background: rgba(23, 162, 184, 0.1);
        border: 1px solid rgba(23, 162, 184, 0.3);
        color: var(--info-color);
    }

    .alert i {
        font-size: 1.5rem;
        margin-top: 2px;
    }

    /* Layout principal */
    .reserva-main {
        padding: 3rem 0;
    }

    .reserva-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    /* Formulario principal */
    .formulario-reserva {
        background: var(--card-bg);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
    }

    [data-theme="oscuro"] .formulario-reserva {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .form-section {
        margin-bottom: 2.5rem;
        padding-bottom: 2.5rem;
        border-bottom: 2px solid var(--border-color);
    }

    .form-section:last-child {
        border-bottom: none;
    }

    .form-title {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--primary-color);
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-title i {
        font-size: 1.3rem;
    }

    /* Campos del formulario */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text-color);
        font-size: 0.95rem;
    }

    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        font-family: var(--font-family);
        font-size: 1rem;
        transition: all 0.3s ease;
        background: var(--card-bg);
        color: var(--text-color);
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.2);
    }

    .form-input[readonly] {
        background: var(--bg-light);
        cursor: not-allowed;
    }

    .form-helper {
        display: block;
        margin-top: 6px;
        color: var(--text-light);
        font-size: 0.85rem;
    }

    /* Servicios adicionales */
    .servicios-container {
        margin-top: 1.5rem;
    }

    .servicio-categoria {
        margin-bottom: 2rem;
    }

    .servicio-categoria-title {
        color: var(--secondary-color);
        font-size: 1.1rem;
        margin-bottom: 1rem;
        padding-left: 12px;
        border-left: 4px solid var(--secondary-color);
    }

    .servicios-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
    }

    .servicio-item {
        background: var(--bg-light);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 15px;
        transition: all 0.3s ease;
    }

    .servicio-item:hover {
        background: rgba(var(--primary-color-rgb), 0.05);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .servicio-checkbox {
        display: flex;
        gap: 12px;
        cursor: pointer;
        align-items: flex-start;
    }

    .servicio-checkbox input[type="checkbox"] {
        margin-top: 4px;
    }

    .servicio-info {
        flex: 1;
    }

    .servicio-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
    }

    .servicio-nombre {
        margin: 0;
        font-size: 1rem;
        color: var(--text-color);
        font-weight: 500;
    }

    .servicio-precio {
        color: var(--primary-color);
        font-weight: 600;
        font-size: 0.95rem;
    }

    .servicio-desc {
        margin: 0;
        color: var(--text-light);
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .total-servicios {
        background: rgba(var(--accent-color-rgb), 0.1);
        padding: 15px 20px;
        border-radius: 10px;
        margin-top: 1.5rem;
        border-left: 4px solid var(--accent-color);
        display: none;
        justify-content: space-between;
        align-items: center;
    }

    /* Panel lateral */
    .reserva-sidebar {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .sidebar-card {
        background: var(--card-bg);
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
    }

    [data-theme="oscuro"] .sidebar-card {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .sidebar-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--primary-color);
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--border-color);
    }

    .sidebar-title i {
        font-size: 1.1rem;
    }

    /* Condiciones de transporte */
    .condiciones-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .condicion-item {
        padding: 15px;
        background: var(--bg-light);
        border-radius: 8px;
        border-left: 3px solid var(--accent-color);
    }

    .condicion-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }

    .condicion-header i {
        color: var(--accent-color);
        font-size: 1rem;
    }

    .condicion-header h4 {
        margin: 0;
        font-size: 0.95rem;
        color: var(--text-color);
    }

    .condicion-desc {
        margin: 0;
        color: var(--text-light);
        font-size: 0.9rem;
        line-height: 1.5;
    }

    /* Beneficios */
    .beneficios-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .beneficio-item {
        display: flex;
        gap: 15px;
        align-items: flex-start;
    }

    .beneficio-icon {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .beneficio-icon i {
        color: var(--bg-color);
        font-size: 1.1rem;
    }

    .beneficio-content h5 {
        margin: 0 0 5px 0;
        font-size: 0.95rem;
        color: var(--text-color);
    }

    .beneficio-content p {
        margin: 0;
        color: var(--text-light);
        font-size: 0.85rem;
        line-height: 1.4;
    }

    /* Tarjeta de contacto */
    .contacto-card {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: var(--bg-color);
    }

    .contacto-card .sidebar-title {
        color: var(--bg-color);
        border-bottom-color: rgba(255, 255, 255, 0.2);
    }

    .contacto-card p {
        opacity: 0.9;
        margin-bottom: 1.5rem;
    }

    .contacto-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .contacto-item {
        display: flex;
        gap: 15px;
        align-items: center;
        padding: 12px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
    }

    .contacto-item i {
        font-size: 1.2rem;
        opacity: 0.9;
    }

    .contacto-info .contacto-label {
        font-size: 0.8rem;
        opacity: 0.8;
        margin-bottom: 2px;
    }

    .contacto-info .contacto-value {
        font-size: 0.95rem;
        font-weight: 500;
    }

    .btn-whatsapp {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: #25D366;
        color: var(--bg-color);
        padding: 12px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        margin-top: 1rem;
        transition: all 0.3s ease;
    }

    .btn-whatsapp:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
    }

    /* Términos y condiciones */
    .terminos-container {
        background: var(--bg-light);
        padding: 20px;
        border-radius: 10px;
        margin: 2rem 0;
    }

    .terminos-checkbox {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .terminos-checkbox input[type="checkbox"] {
        margin-top: 4px;
    }

    .terminos-checkbox span {
        flex: 1;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .terminos-checkbox a {
        color: var(--primary-color);
        text-decoration: underline;
        font-weight: 500;
    }

    .terminos-checkbox a:hover {
        color: var(--secondary-color);
    }

    /* Botón de envío */
    .btn-submit {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: var(--bg-color);
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 2rem;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(var(--primary-color-rgb), 0.3);
    }

    .btn-submit:active {
        transform: translateY(-1px);
    }

    /* Vista para usuarios no logueados */
    .auth-required {
        text-align: center;
        padding: 4rem 2rem;
    }

    .lock-icon {
        width: 100px;
        height: 100px;
        background: rgba(var(--primary-color-rgb), 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        border: 3px solid var(--primary-color);
    }

    .lock-icon i {
        font-size: 3rem;
        color: var(--primary-color);
    }

    .auth-required h3 {
        color: var(--primary-color);
        margin-bottom: 1rem;
        font-size: 1.8rem;
    }

    .auth-required p {
        color: var(--text-light);
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto 2rem;
        line-height: 1.6;
    }

    .auth-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-bottom: 3rem;
        flex-wrap: wrap;
    }

    .btn-login,
    .btn-register {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-login {
        background: var(--primary-color);
        color: var(--bg-color);
    }

    .btn-login:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(var(--primary-color-rgb), 0.3);
    }

    .btn-register {
        background: var(--accent-color);
        color: var(--bg-color);
    }

    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(var(--accent-color-rgb), 0.3);
    }

    /* Beneficios de registro */
    .benefits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 2rem;
    }

    .benefit-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        background: var(--bg-light);
        border-radius: 8px;
    }

    .benefit-item i {
        color: var(--success-color);
        font-size: 1.2rem;
    }

    .benefit-item span {
        font-size: 0.95rem;
        color: var(--text-color);
    }

    /* Animaciones */
    .fade-in {
        animation: fadeInUp 0.8s ease forwards;
        opacity: 0;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive */
    @media (max-width: 992px) {
        .reserva-grid {
            grid-template-columns: 1fr;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .servicios-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .reserva-hero h1 {
            font-size: 2.5rem;
        }

        .reserva-hero {
            padding: 80px 0 50px;
        }

        .formulario-reserva {
            padding: 20px;
        }

        .auth-buttons {
            flex-direction: column;
            align-items: center;
        }

        .btn-login,
        .btn-register {
            width: 100%;
            justify-content: center;
        }

        .benefits-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .container {
            padding: 0 15px;
        }

        .reserva-hero h1 {
            font-size: 2rem;
        }

        .reserva-hero p {
            font-size: 1rem;
        }

        .lock-icon {
            width: 80px;
            height: 80px;
        }

        .lock-icon i {
            font-size: 2.5rem;
        }

        .servicios-grid {
            grid-template-columns: 1fr;
        }

        .form-section {
            padding-bottom: 2rem;
            margin-bottom: 2rem;
        }
    }

    /* Utilidades */
    .text-center {
        text-align: center !important;
    }

    .mb-0 {
        margin-bottom: 0 !important;
    }

    .mb-1 {
        margin-bottom: 0.25rem !important;
    }

    .mb-2 {
        margin-bottom: 0.5rem !important;
    }

    .mb-3 {
        margin-bottom: 1rem !important;
    }

    .mt-1 {
        margin-top: 0.25rem !important;
    }

    .mt-2 {
        margin-top: 0.5rem !important;
    }

    .mt-3 {
        margin-top: 1rem !important;
    }

    .w-100 {
        width: 100% !important;
    }
</style>

<!-- Hero Section -->
<section class="reserva-hero">
    <div class="container">
        <h1 class="mb-3">Reserva tu Aventura</h1>
        <p>Vive experiencias únicas en el corazón del Putumayo</p>

        <?php if ($logueado): ?>
            <div class="user-info-badge fade-in">
                <i class="fas fa-user-circle"></i>
                <span>Reservando como: <strong><?php echo htmlspecialchars($usuario['nombre'] ?? 'Usuario'); ?></strong></span>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <!-- Alertas -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success fade-in">
            <i class="fas fa-check-circle"></i>
            <div>
                <?php echo nl2br(htmlspecialchars($success_message)); ?>
                <?php if ($reserva_id): ?>
                    <div style="margin-top: 10px; font-size: 0.9em; opacity: 0.8;">
                        <i class="fas fa-info-circle"></i> Puedes ver el estado de tu reserva en
                        <a href="mi-cuenta.php" style="color: inherit; text-decoration: underline; font-weight: 500;">
                            Mi Cuenta
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($redirect_message)): ?>
        <div class="alert alert-info fade-in">
            <i class="fas fa-info-circle"></i>
            <div><?php echo htmlspecialchars($redirect_message); ?></div>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error fade-in">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <div class="reserva-main">
        <?php if (!$logueado): ?>
            <!-- Vista para usuarios no logueados -->
            <div class="auth-required">
                <div class="lock-icon fade-in">
                    <i class="fas fa-lock"></i>
                </div>
                <h3 class="fade-in">Inicia sesión para realizar tu reserva</h3>
                <p class="fade-in">Para reservar tu aventura y disfrutar de todas nuestras experiencias, necesitas tener una cuenta. ¡Es rápido y fácil!</p>

                <div class="auth-buttons fade-in">
                    <a href="login.php?redirect=<?php echo urlencode('reservas.php' . (isset($_GET['destino']) ? '?destino=' . $_GET['destino'] : '')); ?>" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </a>
                    <a href="registro.php?redirect=<?php echo urlencode('reservas.php' . (isset($_GET['destino']) ? '?destino=' . $_GET['destino'] : '')); ?>" class="btn-register">
                        <i class="fas fa-user-plus"></i> Crear Cuenta Gratis
                    </a>
                </div>

                <div class="benefits-grid fade-in">
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Reservas más rápidas</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Historial de reservas</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Ofertas exclusivas</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Acceso prioritario</span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Vista para usuarios logueados -->
            <div class="reserva-grid">
                <!-- Formulario principal -->
                <div class="formulario-reserva fade-in">
                    <form method="POST" id="reservaForm">
                        <!-- Información del Usuario -->
                        <div class="form-section">
                            <div class="form-title">
                                <i class="fas fa-user"></i>
                                <span>Información Personal</span>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="nombre">Nombre Completo *</label>
                                    <input type="text" id="nombre" name="nombre"
                                        class="form-input"
                                        value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>"
                                        required readonly>
                                    <span class="form-helper">Completado automáticamente</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="email">Email *</label>
                                    <input type="email" id="email" name="email"
                                        class="form-input"
                                        value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>"
                                        required readonly>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="telefono">Teléfono *</label>
                                    <input type="tel" id="telefono" name="telefono"
                                        class="form-input"
                                        value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>"
                                        required>
                                    <span class="form-helper">Puedes modificar tu número de teléfono</span>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Viaje -->
                        <div class="form-section">
                            <div class="form-title">
                                <i class="fas fa-map-marked-alt"></i>
                                <span>Detalles del Viaje</span>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="destino_id">Destino *</label>
                                    <select id="destino_id" name="destino_id"
                                        class="form-select" required
                                        onchange="cargarActividades(this.value);">
                                        <option value="">Selecciona un destino</option>
                                        <?php foreach ($destinos as $destino):
                                            $selected = ($destino_seleccionado == $destino['id']) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $destino['id']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($destino['nombre']); ?>
                                                <?php if (isset($destino['grupo'])): ?>
                                                    (Grupo <?php echo $destino['grupo']; ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="actividad_id">Actividad *</label>
                                    <select id="actividad_id" name="actividad_id"
                                        class="form-select" required>
                                        <option value="">Primero selecciona un destino</option>
                                        <?php
                                        if (!empty($actividades) && count($actividades) > 0):
                                            foreach ($actividades as $actividad): ?>
                                                <option value="<?php echo $actividad['id']; ?>">
                                                    <?php echo htmlspecialchars($actividad['nombre']); ?>
                                                    <?php if (isset($actividad['precio']) && $actividad['precio'] > 0): ?>
                                                        - $<?php echo number_format($actividad['precio'], 0, ',', '.'); ?>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach;
                                        elseif ($destino_seleccionado): ?>
                                            <option value="">No hay actividades disponibles para este destino</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="fecha_viaje">Fecha de Viaje *</label>
                                    <input type="date" id="fecha_viaje" name="fecha_viaje"
                                        class="form-input"
                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                        required>
                                    <span class="form-helper">Mínimo 1 día de anticipación</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="cantidad_personas">Número de Personas *</label>
                                    <input type="number" id="cantidad_personas" name="cantidad_personas"
                                        class="form-input"
                                        min="1" max="20" value="1" required>
                                    <span class="form-helper">Máximo 20 personas por reserva</span>
                                </div>
                            </div>
                        </div>

                        <!-- Servicios Adicionales -->
                        <div class="form-section">
                            <div class="form-title">
                                <i class="fas fa-concierge-bell"></i>
                                <span>Servicios Adicionales</span>
                            </div>

                            <div class="servicios-container">
                                <?php if (!empty($servicios_por_categoria)): ?>
                                    <?php foreach ($servicios_por_categoria as $categoria => $servicios): ?>
                                        <div class="servicio-categoria">
                                            <h5 class="servicio-categoria-title"><?php echo htmlspecialchars($categoria); ?></h5>
                                            <div class="servicios-grid">
                                                <?php foreach ($servicios as $servicio): ?>
                                                    <div class="servicio-item">
                                                        <label class="servicio-checkbox">
                                                            <input type="checkbox" name="servicios[]"
                                                                value="<?php echo $servicio['id']; ?>"
                                                                data-precio="<?php echo $servicio['precio']; ?>"
                                                                onchange="calcularTotalServicios()">
                                                            <div class="servicio-info">
                                                                <div class="servicio-header">
                                                                    <h6 class="servicio-nombre"><?php echo htmlspecialchars($servicio['nombre']); ?></h6>
                                                                    <?php if ($servicio['precio'] > 0): ?>
                                                                        <span class="servicio-precio">
                                                                            $<?php echo number_format($servicio['precio'], 0, ',', '.'); ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <?php if (!empty($servicio['descripcion'])): ?>
                                                                    <p class="servicio-desc"><?php echo htmlspecialchars($servicio['descripcion']); ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center">No hay servicios adicionales disponibles en este momento.</p>
                                <?php endif; ?>
                            </div>

                            <div class="total-servicios" id="totalServicios">
                                <strong>Total adicional por servicios:</strong>
                                <span id="totalAmount">$0</span>
                            </div>
                        </div>

                        <!-- Comentarios -->
                        <div class="form-section">
                            <div class="form-title">
                                <i class="fas fa-comment-alt"></i>
                                <span>Información Adicional</span>
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label" for="comentarios">Comentarios o Requerimientos Especiales</label>
                                <textarea id="comentarios" name="comentarios"
                                    class="form-textarea" rows="4"
                                    placeholder="Ej: Alergias alimenticias, movilidad reducida, preferencias específicas..."></textarea>
                            </div>
                        </div>

                        <!-- Términos y condiciones -->
                        <div class="terminos-container fade-in">
                            <label class="terminos-checkbox">
                                <input type="checkbox" id="terminos" name="terminos" required>
                                <span>Acepto los <a href="terminos.php" target="_blank">términos y condiciones</a>
                                    y autorizo el tratamiento de mis datos personales según la ley de protección de datos.</span>
                            </label>
                        </div>

                        <!-- Botón de envío -->
                        <button type="submit" class="btn-submit fade-in">
                            <i class="fas fa-paper-plane"></i> Enviar Solicitud de Reserva
                        </button>
                    </form>
                </div>

                <!-- Sidebar -->
                <div class="reserva-sidebar">
                    <!-- Condiciones de transporte -->
                    <?php if (!empty($condiciones_transporte)): ?>
                        <div class="sidebar-card fade-in">
                            <div class="sidebar-title">
                                <i class="fas fa-bus"></i>
                                <span>Condiciones de Transporte</span>
                            </div>

                            <div class="condiciones-list">
                                <?php foreach ($condiciones_transporte as $condicion): ?>
                                    <div class="condicion-item">
                                        <div class="condicion-header">
                                            <i class="<?php echo htmlspecialchars($condicion['icono'] ?? 'fas fa-car'); ?>"></i>
                                            <h4><?php echo htmlspecialchars($condicion['titulo']); ?></h4>
                                        </div>
                                        <p class="condicion-desc"><?php echo htmlspecialchars($condicion['descripcion']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Beneficios incluidos -->
                    <div class="sidebar-card fade-in">
                        <div class="sidebar-title">
                            <i class="fas fa-gift"></i>
                            <span>Beneficios Incluidos</span>
                        </div>

                        <div class="beneficios-list">
                            <?php
                            $beneficios = [
                                ['icon' => 'fa-shield-alt', 'title' => 'Seguro de Viaje', 'desc' => 'Cobertura médica y de equipaje'],
                                ['icon' => 'fa-user-tie', 'title' => 'Guía Especializado', 'desc' => 'Guías locales certificados'],
                                ['icon' => 'fa-utensils', 'title' => 'Alimentación', 'desc' => 'Comidas típicas incluidas'],
                                ['icon' => 'fa-car', 'title' => 'Transporte', 'desc' => 'Recogida desde alojamiento'],
                                ['icon' => 'fa-camera', 'title' => 'Fotografías', 'desc' => 'Fotos profesionales del tour'],
                                ['icon' => 'fa-first-aid', 'title' => 'Botiquín', 'desc' => 'Primeros auxilios incluidos']
                            ];
                            ?>
                            <?php foreach ($beneficios as $beneficio): ?>
                                <div class="beneficio-item">
                                    <div class="beneficio-icon">
                                        <i class="fas <?php echo $beneficio['icon']; ?>"></i>
                                    </div>
                                    <div class="beneficio-content">
                                        <h5><?php echo $beneficio['title']; ?></h5>
                                        <p><?php echo $beneficio['desc']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Contacto -->
                    <div class="sidebar-card contacto-card fade-in">
                        <div class="sidebar-title">
                            <i class="fas fa-headset"></i>
                            <span>¿Necesitas Ayuda?</span>
                        </div>

                        <p>Nuestro equipo está disponible para asesorarte:</p>

                        <div class="contacto-list">
                            <div class="contacto-item">
                                <i class="fas fa-phone"></i>
                                <div class="contacto-info">
                                    <div class="contacto-label">Llámanos</div>
                                    <div class="contacto-value"><?php echo htmlspecialchars($config['contact_phone']); ?></div>
                                </div>
                            </div>

                            <div class="contacto-item">
                                <i class="fas fa-envelope"></i>
                                <div class="contacto-info">
                                    <div class="contacto-label">Escríbenos</div>
                                    <div class="contacto-value"><?php echo htmlspecialchars($config['contact_email']); ?></div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($config['social_whatsapp'])): ?>
                            <a href="<?php echo htmlspecialchars($config['social_whatsapp']); ?>?text=Hola,%20quiero%20hacer%20una%20reserva"
                                target="_blank" class="btn-whatsapp">
                                <i class="fab fa-whatsapp"></i>
                                <span>Chat en WhatsApp</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Función para cargar actividades dinámicamente
    function cargarActividades(destinoId) {
        if (!destinoId) {
            const select = document.getElementById('actividad_id');
            if (select) {
                select.innerHTML = '<option value="">Primero selecciona un destino</option>';
            }
            return;
        }

        const select = document.getElementById('actividad_id');
        if (!select) return;

        select.innerHTML = '<option value="">Cargando actividades...</option>';
        select.disabled = true;

        // Usar fetch para cargar actividades dinámicamente
        fetch('ajax/cargar-actividades.php?destino_id=' + destinoId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                select.innerHTML = '<option value="">Selecciona una actividad</option>';
                select.disabled = false;

                if (!data || data.length === 0) {
                    select.innerHTML = '<option value="">No hay actividades disponibles para este destino</option>';
                    return;
                }

                data.forEach(actividad => {
                    const option = document.createElement('option');
                    option.value = actividad.id;
                    let texto = actividad.nombre;
                    if (actividad.precio) {
                        texto += ` - $${new Intl.NumberFormat().format(actividad.precio)}`;
                    }
                    option.textContent = texto;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                select.innerHTML = '<option value="">Error al cargar actividades</option>';
                select.disabled = false;

                // Método alternativo: recargar la página con el destino seleccionado
                window.location.href = 'reservas.php?destino=' + destinoId;
            });
    }

    // Calcular total de servicios adicionales
    function calcularTotalServicios() {
        const checkboxes = document.querySelectorAll('input[name="servicios[]"]:checked');
        let total = 0;

        checkboxes.forEach(checkbox => {
            const precio = parseFloat(checkbox.getAttribute('data-precio')) || 0;
            total += precio;
        });

        const totalElement = document.getElementById('totalServicios');
        const amountElement = document.getElementById('totalAmount');

        if (total > 0) {
            totalElement.style.display = 'flex';
            amountElement.textContent = '$' + total.toLocaleString();
        } else {
            totalElement.style.display = 'none';
        }
    }

    // Configuración inicial para usuarios logueados
    <?php if ($logueado): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar fecha mínima
            const fechaInput = document.getElementById('fecha_viaje');
            if (fechaInput) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                fechaInput.min = tomorrow.toISOString().split('T')[0];
            }

            // Si hay un destino seleccionado al cargar la página, cargar sus actividades
            <?php if ($destino_seleccionado): ?>
                cargarActividades(<?php echo $destino_seleccionado; ?>);
            <?php endif; ?>

            // Validar formulario
            const reservaForm = document.getElementById('reservaForm');
            if (reservaForm) {
                reservaForm.addEventListener('submit', function(e) {
                    const fechaViaje = document.getElementById('fecha_viaje').value;
                    const fechaMinima = new Date(Date.now() + 86400000).toISOString().split('T')[0];

                    if (fechaViaje < fechaMinima) {
                        e.preventDefault();
                        alert('Por favor selecciona una fecha futura (mínimo 1 día de anticipación).');
                        return false;
                    }

                    if (!document.getElementById('terminos').checked) {
                        e.preventDefault();
                        alert('Debes aceptar los términos y condiciones para continuar.');
                        return false;
                    }

                    // Validar teléfono
                    const telefono = document.getElementById('telefono').value;
                    if (!telefono || telefono.trim() === '') {
                        e.preventDefault();
                        alert('Por favor ingresa tu número de teléfono.');
                        return false;
                    }

                    // Validar actividad seleccionada
                    const actividad = document.getElementById('actividad_id').value;
                    if (!actividad || actividad.trim() === '') {
                        e.preventDefault();
                        alert('Por favor selecciona una actividad.');
                        return false;
                    }

                    // Mostrar mensaje de carga
                    const submitBtn = document.querySelector('.btn-submit');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                        submitBtn.disabled = true;

                        // Restaurar botón después de 5 segundos (si hay algún error)
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 5000);
                    }
                });
            }

            // Aplicar color de acento dinámico
            const accentColor = '<?php echo $accent_color; ?>';
            if (accentColor) {
                // Aplicar color de acento a elementos específicos
                document.querySelectorAll('.condicion-item').forEach(item => {
                    item.style.borderLeftColor = accentColor;
                });

                // Cambiar color de hover en botones
                const style = document.createElement('style');
                style.textContent = `
            .btn-register:hover {
                background-color: ${accentColor} !important;
                filter: brightness(1.1);
            }
            .servicio-item:hover {
                border-color: ${accentColor};
            }
        `;
                document.head.appendChild(style);
            }
        });
    <?php endif; ?>

    // Animaciones al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        // Añadir animaciones a los elementos
        const fadeElements = document.querySelectorAll('.fade-in');

        function checkFade() {
            fadeElements.forEach(element => {
                const rect = element.getBoundingClientRect();
                const windowHeight = window.innerHeight;

                if (rect.top < windowHeight - 100) {
                    element.style.animation = 'fadeInUp 0.8s ease forwards';
                    element.style.animationDelay = (Math.random() * 0.3) + 's';
                }
            });
        }

        // Verificar al cargar y al hacer scroll
        window.addEventListener('scroll', checkFade);
        window.addEventListener('load', checkFade);

        // Verificar inmediatamente
        checkFade();

        // Efecto de scroll suave para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId !== '#') {
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?>