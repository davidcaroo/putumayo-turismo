<?php
// evento-detalle.php
include_once 'includes/config.php';

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

    // Redes sociales
    'social_facebook',
    'social_instagram',
    'social_twitter',
    'social_youtube',
    'social_linkedin',
    'social_whatsapp'
];

$config = [];
foreach ($config_keys as $key) {
    // Valores por defecto
    $default = '';
    if ($key === 'site_name') $default = 'Putumayo Turismo';
    if ($key === 'site_description') $default = 'Descubre la belleza del Putumayo';
    if ($key === 'primary_color') $default = '#2E8B57';
    if ($key === 'secondary_color') $default = '#267349';
    if ($key === 'accent_color') $default = '#2196F3';
    if ($key === 'font_family') $default = "'Inter', sans-serif";

    $config[$key] = getConfigValue($key, $default);
}

// ========== URL AMIGABLE: /evento/{slug} ===========
$evento_slug = null;

if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $evento_slug = $_GET['slug'];
    error_log("evento-detalle.php: Buscando evento por slug: " . $evento_slug);
} elseif (isset($_GET['id'])) {
    // Compatibilidad temporal con parámetro antiguo (?id=) - redirigir a slug
    $evento_id_temp = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($evento_id_temp) {
        try {
            $stmt = $pdo->prepare("SELECT slug FROM eventos WHERE id = ?");
            $stmt->execute([$evento_id_temp]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && $result['slug']) {
                header('Location: ' . BASE_URL . 'evento/' . $result['slug']);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error obteniendo slug: " . $e->getMessage());
        }
    }
    header('Location: eventos.php');
    exit;
} else {
    header('Location: eventos.php');
    exit;
}

// Obtener información del evento por slug
try {
    $sql = "SELECT e.*, c.nombre as categoria_nombre, c.color as categoria_color, c.icono as categoria_icono 
            FROM eventos e 
            LEFT JOIN categorias_eventos c ON e.categoria_id = c.id 
            WHERE e.slug = ? AND e.activo = 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$evento_slug]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$evento) {
        header('Location: eventos.php');
        exit;
    }

    $evento_id = $evento['id'];
} catch (Exception $e) {
    error_log("Error obteniendo evento: " . $e->getMessage());
    header('Location: eventos.php');
    exit;
}

// Obtener eventos relacionados (misma categoría)
try {
    $sql_relacionados = "SELECT e.*, c.nombre as categoria_nombre 
                        FROM eventos e 
                        LEFT JOIN categorias_eventos c ON e.categoria_id = c.id 
                        WHERE e.categoria_id = ? 
                        AND e.id != ? 
                        AND e.activo = 1 
                        AND e.fecha_inicio >= CURDATE()
                        ORDER BY e.fecha_inicio 
                        LIMIT 3";

    $stmt_relacionados = $pdo->prepare($sql_relacionados);
    $stmt_relacionados->execute([$evento['categoria_id'], $evento_id]);
    $eventos_relacionados = $stmt_relacionados->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $eventos_relacionados = [];
}

// Configuración de página
$page_title = $evento['titulo'] . " - " . $config['site_name'];
$page_description = $evento['descripcion_corta'] ?? substr($evento['descripcion'] ?? '', 0, 160);
$page_image = $evento['imagen'] ? 'uploads/eventos/' . $evento['imagen'] : '';
include_once 'includes/header.php';
?>

<style>
    /* Variables CSS dinámicas según configuración */
    :root {
        --primary-color: <?php echo htmlspecialchars($config['primary_color']); ?>;
        --secondary-color: <?php echo htmlspecialchars($config['secondary_color']); ?>;
        --accent-color: <?php echo htmlspecialchars($config['accent_color']); ?>;
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
        --facebook-color: #1877f2;
        --twitter-color: #1da1f2;
        --instagram-color: #e4405f;
        --whatsapp-color: #25d366;
    }

    [data-theme="oscuro"] {
        --primary-color: #4CAF50;
        --secondary-color: #FF9800;
        --accent-color: #FFC107;
        --text-color: #f8f9fa;
        --text-light: #bdc3c7;
        --bg-color: #121212;
        --bg-light: #1a1a1a;
        --card-bg: #1e1e1e;
        --border-color: #333;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --danger-color: #f44336;
    }

    --card-bg: #1e1e1e;
    --border-color: #333;
    --success-color: #4caf50;
    --warning-color: #ff9800;
    --danger-color: #f44336;
    }

    body {
        font-family: var(--font-family);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Hero del Evento */
    .evento-detalle-hero {
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
            <?php if ($evento['imagen']): ?> url('uploads/eventos/<?php echo htmlspecialchars($evento['imagen']); ?>') <?php else: ?> url('uploads/eventos/default-hero.jpg') <?php endif; ?> no-repeat center center;
        background-size: cover;
        color: var(--bg-color);
        padding: 120px 0 80px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .evento-detalle-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg,
                rgba(var(--primary-color-rgb), 0.8),
                rgba(var(--secondary-color-rgb), 0.8));
        z-index: 1;
    }

    .evento-detalle-hero .container {
        position: relative;
        z-index: 2;
    }

    .badge-destacado-detalle {
        background: var(--warning-color);
        color: #212529;
        padding: 10px 25px;
        border-radius: 25px;
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 30px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
        }
    }

    .evento-info-card {
        background: var(--card-bg);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        margin-top: -60px;
        position: relative;
        z-index: 10;
        border: 1px solid var(--border-color);
    }

    [data-theme="oscuro"] .evento-info-card {
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    }

    /* Badges personalizados */
    .badge-custom {
        padding: 10px 25px;
        border-radius: 25px;
        font-size: 1rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
    }

    .badge-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    /* Información en tarjetas */
    .info-item {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
        padding-bottom: 25px;
        border-bottom: 2px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .info-item:hover {
        border-color: var(--primary-color);
    }

    .info-icon {
        width: 60px;
        height: 60px;
        background: var(--primary-color);
        color: var(--bg-color);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }

    .info-item:hover .info-icon {
        background: var(--secondary-color);
        transform: scale(1.1) rotate(5deg);
    }

    .info-content h5 {
        color: var(--text-color);
        margin-bottom: 8px;
        font-size: 1.2rem;
    }

    .info-content p {
        color: var(--text-light);
        margin-bottom: 0;
        line-height: 1.6;
    }

    /* Botón de reserva */
    .btn-reservar {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: var(--bg-color);
        padding: 18px 45px;
        border-radius: 15px;
        font-size: 1.2rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        box-shadow: 0 10px 25px rgba(46, 139, 87, 0.3);
        position: relative;
        overflow: hidden;
    }

    .btn-reservar::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: 0.5s;
    }

    .btn-reservar:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(46, 139, 87, 0.4);
    }

    .btn-reservar:hover::before {
        left: 100%;
    }

    .btn-reservar:disabled {
        background: var(--border-color);
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }

    /* Tarjetas relacionadas */
    .card-relacionado {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    [data-theme="oscuro"] .card-relacionado {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .card-relacionado:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        border-color: var(--primary-color);
    }

    .card-relacionado-img {
        height: 180px;
        object-fit: cover;
        width: 100%;
        transition: transform 0.5s ease;
    }

    .card-relacionado:hover .card-relacionado-img {
        transform: scale(1.05);
    }

    /* Barra de progreso de capacidad */
    .capacidad-progress {
        height: 30px;
        border-radius: 15px;
        overflow: hidden;
        background: var(--bg-light);
        position: relative;
    }

    .progress-bar {
        height: 100%;
        border-radius: 15px;
        transition: width 1s ease;
        position: relative;
        overflow: hidden;
    }

    .progress-bar::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.1) 50%,
                transparent 100%);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% {
            transform: translateX(-100%);
        }

        100% {
            transform: translateX(100%);
        }
    }

    /* Botones de compartir */
    .btn-share {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: all 0.3s ease;
        color: var(--bg-color);
        text-decoration: none;
    }

    .btn-share:hover {
        transform: translateY(-5px) scale(1.1);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    .btn-share.facebook {
        background: var(--facebook-color);
    }

    .btn-share.twitter {
        background: var(--twitter-color);
    }

    .btn-share.instagram {
        background: var(--instagram-color);
    }

    .btn-share.whatsapp {
        background: var(--whatsapp-color);
    }

    /* Tarjetas del sidebar */
    .sidebar-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    [data-theme="oscuro"] .sidebar-card {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .sidebar-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-color: var(--primary-color);
    }

    .sidebar-card h5 {
        color: var(--text-color);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sidebar-card h5 i {
        color: var(--primary-color);
    }

    /* Mapa placeholder */
    .mapa-placeholder {
        background: var(--bg-light);
        border-radius: 10px;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .mapa-placeholder::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, transparent 40%, rgba(255, 255, 255, 0.1) 50%, transparent 60%);
        animation: mapaShimmer 2s infinite;
    }

    @keyframes mapaShimmer {
        0% {
            transform: translateX(-100%);
        }

        100% {
            transform: translateX(100%);
        }
    }

    /* Descripción del evento */
    .evento-descripcion {
        color: var(--text-color);
        line-height: 1.8;
        font-size: 1.1rem;
    }

    .evento-descripcion p {
        margin-bottom: 1.5rem;
    }

    /* Alerta de cupos */
    .alert-cupos {
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Contacto rápido */
    .contacto-rapido {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .contacto-rapido li {
        margin-bottom: 15px;
        padding-left: 30px;
        position: relative;
        transition: all 0.3s ease;
    }

    .contacto-rapido li:hover {
        transform: translateX(5px);
    }

    .contacto-rapido li i {
        position: absolute;
        left: 0;
        top: 2px;
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .contacto-rapido li:hover i {
        transform: scale(1.2);
    }

    .contacto-rapido a {
        color: var(--text-color);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .contacto-rapido a:hover {
        color: var(--primary-color);
        text-decoration: underline;
    }

    /* Galería */
    .evento-galeria {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .evento-galeria:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
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

    /* Layout */
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -15px;
    }

    .col-lg-8,
    .col-lg-4 {
        padding: 0 15px;
        width: 100%;
    }

    @media (min-width: 992px) {
        .col-lg-8 {
            width: 66.6667%;
        }

        .col-lg-4 {
            width: 33.3333%;
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .evento-detalle-hero {
            padding: 80px 0 60px;
        }

        .evento-info-card {
            margin-top: 0;
            padding: 25px;
            border-radius: 15px;
        }

        .badge-destacado-detalle {
            font-size: 0.9rem;
            padding: 8px 20px;
        }

        .badge-custom {
            font-size: 0.9rem;
            padding: 8px 15px;
            margin-bottom: 10px;
        }

        .info-item {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }

        .info-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }

        .btn-reservar {
            width: 100%;
            justify-content: center;
            padding: 15px 20px;
            font-size: 1.1rem;
        }

        .sidebar-card {
            padding: 20px;
        }

        .card-relacionado-img {
            height: 150px;
        }
    }

    @media (max-width: 480px) {
        .evento-detalle-hero {
            padding: 60px 0 40px;
        }

        .evento-detalle-hero h1 {
            font-size: 2rem;
        }

        .evento-info-card {
            padding: 20px;
        }

        .badge-custom {
            width: 100%;
            justify-content: center;
            margin-bottom: 8px;
        }

        .capacidad-progress {
            height: 25px;
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

    .mb-4 {
        margin-bottom: 1.5rem !important;
    }

    .mt-0 {
        margin-top: 0 !important;
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

    .mt-4 {
        margin-top: 1.5rem !important;
    }

    .d-flex {
        display: flex !important;
    }

    .justify-content-center {
        justify-content: center !important;
    }

    .justify-content-between {
        justify-content: space-between !important;
    }

    .align-items-center {
        align-items: center !important;
    }

    .align-items-start {
        align-items: flex-start !important;
    }

    .gap-2 {
        gap: 0.5rem !important;
    }

    .gap-3 {
        gap: 1rem !important;
    }

    .w-100 {
        width: 100% !important;
    }

    .small {
        font-size: 0.875rem !important;
    }

    .img-fluid {
        max-width: 100%;
        height: auto;
    }

    .rounded {
        border-radius: 10px !important;
    }

    .text-muted {
        color: var(--text-light) !important;
    }

    .text-success {
        color: var(--success-color) !important;
    }

    .text-warning {
        color: var(--warning-color) !important;
    }

    .text-danger {
        color: var(--danger-color) !important;
    }

    .alert {
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .alert-light {
        background: var(--bg-light);
        border: 1px solid var(--border-color);
        color: var(--text-color);
    }

    .alert-danger {
        background: rgba(220, 53, 69, 0.1);
        border: 1px solid var(--danger-color);
        color: var(--danger-color);
    }

    .list-unstyled {
        list-style: none;
        padding-left: 0;
    }
</style>

<!-- Hero del Evento -->
<section class="evento-detalle-hero">
    <div class="container">
        <?php if ($evento['destacado']): ?>
            <div class="badge-destacado-detalle fade-in">
                <i class="fas fa-star"></i> Evento Destacado
            </div>
        <?php endif; ?>

        <h1 class="mb-3 fade-in"><?php echo htmlspecialchars($evento['titulo']); ?></h1>

        <div class="d-flex justify-content-center flex-wrap gap-3 mb-4">
            <span class="badge-custom" style="background: <?php echo $evento['categoria_color'] ?? $config['primary_color']; ?>; color: var(--bg-color);">
                <i class="<?php echo $evento['categoria_icono'] ?? 'fas fa-calendar'; ?>"></i>
                <?php echo htmlspecialchars($evento['categoria_nombre'] ?? 'Evento'); ?>
            </span>

            <span class="badge-custom" style="background: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);">
                <i class="fas fa-calendar-day"></i>
                <?php echo date('d M, Y', strtotime($evento['fecha_inicio'])); ?>
            </span>

            <?php if (floatval($evento['precio']) > 0): ?>
                <span class="badge-custom" style="background: var(--success-color); color: var(--bg-color);">
                    <i class="fas fa-tag"></i>
                    $<?php echo number_format($evento['precio'], 2); ?>
                </span>
            <?php else: ?>
                <span class="badge-custom" style="background: var(--success-color); color: var(--bg-color);">
                    <i class="fas fa-gift"></i> Gratuito
                </span>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container">
    <div class="row">
        <!-- Información del Evento -->
        <div class="col-lg-8">
            <div class="evento-info-card fade-in">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item fade-in">
                            <div class="info-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="info-content">
                                <h5 class="mb-1">Fecha y Hora</h5>
                                <p class="mb-0">
                                    <strong><?php echo date('l, d F Y', strtotime($evento['fecha_inicio'])); ?></strong><br>
                                    <span class="text-muted">
                                        <?php echo date('H:i', strtotime($evento['fecha_inicio'])); ?> -
                                        <?php echo date('H:i', strtotime($evento['fecha_fin'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-item fade-in">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <h5 class="mb-1">Ubicación</h5>
                                <p class="mb-0">
                                    <strong><?php echo htmlspecialchars($evento['ubicacion'] ?? 'Por definir'); ?></strong>
                                    <?php if (isset($evento['tipo_evento']) && $evento['tipo_evento']): ?>
                                        <br><span class="text-muted">Tipo: <?php echo ucfirst($evento['tipo_evento']); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if ($evento['capacidad_max'] > 0): ?>
                        <div class="col-md-6">
                            <div class="info-item fade-in">
                                <div class="info-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="info-content">
                                    <h5 class="mb-1">Disponibilidad</h5>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Cupos disponibles</span>
                                            <span>
                                                <?php
                                                $disponibles = $evento['capacidad_max'] - $evento['inscripciones_actual'];
                                                echo $disponibles . '/' . $evento['capacidad_max'];
                                                ?>
                                            </span>
                                        </div>
                                        <div class="capacidad-progress">
                                            <?php
                                            $porcentaje = ($evento['inscripciones_actual'] / $evento['capacidad_max']) * 100;
                                            $clase_progress = $disponibles <= 0 ? 'bg-danger' : ($porcentaje >= 80 ? 'bg-warning' : 'bg-success');
                                            ?>
                                            <div class="progress-bar <?php echo $clase_progress; ?>"
                                                style="width: <?php echo min($porcentaje, 100); ?>%"></div>
                                        </div>
                                        <?php if ($disponibles <= 0): ?>
                                            <div class="alert-cupos" style="background: rgba(220, 53, 69, 0.1); color: var(--danger-color);">
                                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                                                <div>
                                                    <strong>¡Evento agotado!</strong><br>
                                                    <small>No hay cupos disponibles</small>
                                                </div>
                                            </div>
                                        <?php elseif ($disponibles < 10): ?>
                                            <div class="alert-cupos" style="background: rgba(255, 193, 7, 0.1); color: var(--warning-color);">
                                                <i class="fas fa-exclamation-circle fa-2x"></i>
                                                <div>
                                                    <strong>¡Últimos cupos!</strong><br>
                                                    <small>Solo quedan <?php echo $disponibles; ?> disponibles</small>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-6">
                        <div class="info-item fade-in">
                            <div class="info-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="info-content">
                                <h5 class="mb-1">Información</h5>
                                <p class="mb-0">
                                    <strong>Categoría:</strong> <?php echo htmlspecialchars($evento['categoria_nombre'] ?? 'General'); ?><br>
                                    <?php if ($evento['precio'] > 0): ?>
                                        <strong>Precio:</strong> $<?php echo number_format($evento['precio'], 2); ?><br>
                                    <?php else: ?>
                                        <strong>Precio:</strong> Gratuito<br>
                                    <?php endif; ?>
                                    <?php if ($evento['capacidad_max'] > 0): ?>
                                        <strong>Capacidad:</strong> <?php echo $evento['capacidad_max']; ?> personas<br>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 fade-in">
                    <h4 class="mb-3" style="color: var(--text-color);">Descripción del Evento</h4>
                    <div class="evento-descripcion">
                        <?php echo nl2br(htmlspecialchars($evento['descripcion'])); ?>
                    </div>
                </div>

                <?php if ($evento['descripcion_corta']): ?>
                    <div class="mt-4 fade-in">
                        <h4 class="mb-3" style="color: var(--text-color);">Resumen</h4>
                        <div class="alert alert-light">
                            <i class="fas fa-info-circle me-2" style="color: var(--primary-color);"></i>
                            <?php echo htmlspecialchars($evento['descripcion_corta']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Galería (si hay más imágenes) -->
                <?php if ($evento['imagen']): ?>
                    <div class="mt-4 fade-in">
                        <h4 class="mb-3" style="color: var(--text-color);">Galería</h4>
                        <div class="evento-galeria">
                            <img src="uploads/eventos/<?php echo htmlspecialchars($evento['imagen']); ?>"
                                alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                class="img-fluid w-100"
                                onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNmOGY5ZmEiLz48dGV4dCB4PSIxNTAiIHk9IjEwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjNmM3NTgwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5JbWFnZW4gbm8gZGlzcG9uaWJsZTwvdGV4dD48L3N2Zz4=';">
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Botón de Reserva -->
                <div class="mt-5 text-center fade-in">
                    <?php if ($evento['capacidad_max'] > 0 && ($evento['capacidad_max'] - $evento['inscripciones_actual']) <= 0): ?>
                        <div class="alert alert-danger fade-in">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>Evento agotado.</strong> No hay cupos disponibles.
                        </div>
                    <?php else: ?>
                        <a href="reservar-evento.php?id=<?php echo $evento['id']; ?>" class="btn-reservar fade-in">
                            <i class="fas fa-ticket-alt"></i>
                            <?php echo floatval($evento['precio']) > 0 ? 'Reservar Ahora' : 'Registrarse Gratis'; ?>
                        </a>
                        <p class="text-muted mt-3 fade-in">
                            <i class="fas fa-shield-alt me-2"></i>
                            Reserva segura con confirmación instantánea
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar con info adicional -->
        <div class="col-lg-4">
            <!-- Botón de compartir -->
            <div class="sidebar-card fade-in">
                <h5><i class="fas fa-share-alt"></i>Compartir Evento</h5>
                <div class="d-flex justify-content-center gap-3">
                    <?php
                    $url_evento = urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
                    $titulo_evento = urlencode($evento['titulo']);
                    $descripcion_evento = urlencode($evento['descripcion_corta'] ?? '');
                    ?>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $url_evento; ?>"
                        target="_blank" class="btn-share facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo $url_evento; ?>&text=<?php echo $titulo_evento; ?>"
                        target="_blank" class="btn-share twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.instagram.com/"
                        target="_blank" class="btn-share instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://wa.me/?text=<?php echo $titulo_evento . '%20' . $url_evento; ?>"
                        target="_blank" class="btn-share whatsapp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>

            <!-- Mapa (si hay ubicación) -->
            <?php if ($evento['ubicacion']): ?>
                <div class="sidebar-card fade-in">
                    <h5><i class="fas fa-map"></i>Ubicación</h5>
                    <div class="mapa-placeholder mb-3">
                        <div class="text-center">
                            <i class="fas fa-map-marker-alt fa-3x text-muted mb-2"></i>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($evento['ubicacion']); ?></p>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="https://maps.google.com/?q=<?php echo urlencode($evento['ubicacion']); ?>"
                            target="_blank" class="btn-reservar" style="padding: 12px; font-size: 1rem;">
                            <i class="fas fa-directions me-2"></i>Ver en Google Maps
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Eventos Relacionados -->
            <?php if (!empty($eventos_relacionados)): ?>
                <div class="sidebar-card fade-in">
                    <h5><i class="fas fa-calendar-check"></i>Eventos Relacionados</h5>
                    <?php foreach ($eventos_relacionados as $relacionado): ?>
                        <div class="card-relacionado mb-3">
                            <?php if ($relacionado['imagen']): ?>
                                <img src="uploads/eventos/<?php echo htmlspecialchars($relacionado['imagen']); ?>"
                                    class="card-relacionado-img"
                                    alt="<?php echo htmlspecialchars($relacionado['titulo']); ?>"
                                    onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNmOGY5ZmEiLz48dGV4dCB4PSIxNTAiIHk9IjEwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjNmM3NTgwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5JbWFnZW4gbm8gZGlzcG9uaWJsZTwvdGV4dD48L3N2Zz4=';">
                            <?php endif; ?>
                            <div class="card-body">
                                <h6 class="mb-2" style="color: var(--text-color);"><?php echo htmlspecialchars($relacionado['titulo']); ?></h6>
                                <p class="small text-muted mb-3">
                                    <?php echo date('d M, H:i', strtotime($relacionado['fecha_inicio'])); ?>
                                </p>
                                <a href="evento-detalle.php?id=<?php echo $relacionado['id']; ?>"
                                    class="btn-reservar" style="padding: 10px 15px; font-size: 0.9rem;">
                                    Ver detalles
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Contacto Rápido -->
            <div class="sidebar-card fade-in">
                <h5><i class="fas fa-question-circle"></i>¿Preguntas?</h5>
                <p class="small text-muted mb-3">¿Tienes dudas sobre este evento? Contáctanos:</p>
                <ul class="contacto-rapido">
                    <li>
                        <i class="fas fa-phone text-primary"></i>
                        <a href="tel:+573001234567">+57 300 123 4567</a>
                    </li>
                    <li>
                        <i class="fas fa-envelope text-primary"></i>
                        <a href="mailto:info@putumayoturismo.com">info@putumayoturismo.com</a>
                    </li>
                    <li>
                        <i class="fab fa-whatsapp text-success"></i>
                        <a href="https://wa.me/573001234567" target="_blank">WhatsApp</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
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

        // Efecto de scroll suave
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

        // Contador de capacidad (efecto visual)
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            const width = progressBar.style.width;
            progressBar.style.width = '0%';

            setTimeout(() => {
                progressBar.style.width = width;
            }, 300);
        }

        // Manejo de imágenes que no cargan
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                if (!this.src.includes('data:image/svg+xml')) {
                    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNmOGY5ZmEiLz48dGV4dCB4PSIxNTAiIHk9IjEwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjNmM3NTgwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5JbWFnZW4gbm8gZGlzcG9uaWJsZTwvdGV4dD48L3N2Zz4=';
                }
            });
        });

        // Efecto hover en botones de reserva
        const btnReserva = document.querySelector('.btn-reservar');
        if (btnReserva) {
            btnReserva.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });

            btnReserva.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        }

        // Contador de visitas al evento (simulado)
        const eventoId = <?php echo $evento_id; ?>;
        if (eventoId) {
            // Aquí podrías hacer una petición AJAX para registrar la visita
            // Por ahora solo un console.log
            console.log('Evento visto:', eventoId);
        }
    });

    // Función para compartir en redes sociales
    function compartirEvento(redSocial) {
        const titulo = encodeURIComponent(document.title);
        const url = encodeURIComponent(window.location.href);

        let shareUrl = '';
        switch (redSocial) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${titulo}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${titulo}%20${url}`;
                break;
        }

        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    }
</script>

<?php include_once 'includes/footer.php'; ?>