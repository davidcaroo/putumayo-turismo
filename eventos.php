<?php
// eventos.php - Vista pública de eventos
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

    // Carrusel
    'carousel_speed',
    'carousel_autoplay',
    'show_indicators',
    'show_controls',

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
    if ($key === 'carousel_speed') $default = '5000';
    if ($key === 'carousel_autoplay') $default = '1';
    if ($key === 'show_indicators') $default = '1';
    if ($key === 'show_controls') $default = '1';

    $config[$key] = getConfigValue($key, $default);
}

// Obtener mes y año actuales
$mes_actual = date('m');
$anio_actual = date('Y');

// Obtener mes y año del filtro
$mes_filtro = isset($_GET['mes']) ? intval($_GET['mes']) : $mes_actual;
$anio_filtro = isset($_GET['anio']) ? intval($_GET['anio']) : $anio_actual;

// Validar mes
if ($mes_filtro < 1 || $mes_filtro > 12) {
    $mes_filtro = $mes_actual;
}

// Obtener eventos activos del mes
try {
    $fecha_inicio = "$anio_filtro-$mes_filtro-01";
    $fecha_fin = date('Y-m-t', strtotime($fecha_inicio));

    $sql = "SELECT e.*, c.nombre as categoria_nombre, c.color as categoria_color, c.icono as categoria_icono 
            FROM eventos e 
            LEFT JOIN categorias_eventos c ON e.categoria_id = c.id 
            WHERE e.activo = 1 
            AND (DATE(e.fecha_inicio) BETWEEN ? AND ? OR DATE(e.fecha_fin) BETWEEN ? AND ?)
            ORDER BY e.destacado DESC, e.fecha_inicio ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error obteniendo eventos públicos: " . $e->getMessage());
    $eventos = [];
}

// Obtener eventos destacados
try {
    $sql_destacados = "SELECT e.*, c.nombre as categoria_nombre, c.color as categoria_color 
                       FROM eventos e 
                       LEFT JOIN categorias_eventos c ON e.categoria_id = c.id 
                       WHERE e.destacado = 1 AND e.activo = 1 AND e.fecha_inicio >= CURDATE() 
                       ORDER BY e.fecha_inicio LIMIT 3";
    $stmt_destacados = $pdo->query($sql_destacados);
    $eventos_destacados = $stmt_destacados->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $eventos_destacados = [];
}

// Obtener todos los meses con eventos para el selector
try {
    $sql_meses = "SELECT DISTINCT 
                  YEAR(fecha_inicio) as año, 
                  MONTH(fecha_inicio) as mes,
                  MONTHNAME(fecha_inicio) as nombre_mes
                  FROM eventos 
                  WHERE activo = 1 AND fecha_inicio >= CURDATE()
                  ORDER BY año DESC, mes DESC";
    $stmt_meses = $pdo->query($sql_meses);
    $meses_disponibles = $stmt_meses->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $meses_disponibles = [];
}

// Configuración de página
$page_title = "Eventos y Actividades - " . $config['site_name'];
$page_description = "Descubre los mejores eventos y actividades turísticas en Putumayo. Tours, festivales, talleres y experiencias únicas.";
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
    }

    [data-theme="oscuro"] {
        --text-color: #f8f9fa;
        --text-light: #bdc3c7;
        --bg-color: #121212;
        --bg-light: #1a1a1a;
        --card-bg: #1e1e1e;
        --border-color: #333;
    }

    body {
        font-family: var(--font-family);
    }

    /* Estilos para la vista pública de eventos */
    .eventos-hero {
        background: linear-gradient(rgba(46, 139, 87, 0.9), rgba(38, 115, 73, 0.9)),
            url('uploads/eventos/hero-bg.jpg') no-repeat center center;
        background-size: cover;
        color: var(--bg-color);
        padding: 100px 0;
        text-align: center;
        margin-bottom: 50px;
        position: relative;
        overflow: hidden;
    }

    .eventos-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(var(--primary-color-rgb), 0.8), rgba(var(--secondary-color-rgb), 0.8));
        z-index: 1;
    }

    .eventos-hero .container {
        position: relative;
        z-index: 2;
    }

    .eventos-hero h1 {
        font-size: 3.5rem;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        color: var(--bg-color);
    }

    .eventos-hero p {
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto;
        opacity: 0.9;
        color: var(--bg-color);
    }

    .mes-selector {
        background: var(--card-bg);
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
    }

    .calendario-mes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }

    .dia-calendario {
        background: var(--card-bg);
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease;
        border: 1px solid var(--border-color);
    }

    .dia-calendario:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .dia-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 15px;
        margin-bottom: 15px;
        border-bottom: 2px solid var(--border-color);
    }

    .numero-dia {
        background: var(--primary-color);
        color: var(--bg-color);
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
    }

    .evento-item {
        background: var(--bg-light);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        border-left: 4px solid var(--primary-color);
        transition: transform 0.2s ease;
    }

    .evento-item:hover {
        transform: translateX(5px);
    }

    .evento-destacado {
        border-left: 4px solid #ffc107;
        background: linear-gradient(135deg, var(--card-bg), rgba(255, 200, 7, 0.1));
    }

    [data-theme="oscuro"] .evento-destacado {
        background: linear-gradient(135deg, rgba(44, 62, 80, 0.9), rgba(255, 200, 7, 0.15));
    }

    .evento-imagen {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 15px;
        transition: transform 0.3s ease;
    }

    .evento-imagen:hover {
        transform: scale(1.02);
    }

    .badge-categoria {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--bg-color);
        border: none;
        cursor: pointer;
    }

    .hora-evento {
        display: flex;
        align-items: center;
        gap: 5px;
        color: var(--text-light);
        font-size: 0.9rem;
    }

    .ubicacion-evento {
        display: flex;
        align-items: center;
        gap: 5px;
        color: var(--text-light);
        font-size: 0.9rem;
        margin-top: 5px;
    }

    .precio-evento {
        font-weight: bold;
        color: var(--primary-color);
        font-size: 1.1rem;
    }

    .btn-detalle {
        background: var(--primary-color);
        color: var(--bg-color);
        padding: 8px 20px;
        border-radius: 25px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
    }

    .btn-detalle:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(var(--primary-color-rgb), 0.3);
        color: var(--bg-color);
    }

    .eventos-destacados {
        background: var(--card-bg);
        padding: 30px;
        border-radius: 15px;
        margin: 50px 0;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
    }

    .card-destacado {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: transform 0.3s ease;
        height: 100%;
        background: var(--card-bg);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .card-destacado:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    .card-destacado-img {
        height: 200px;
        object-fit: cover;
        width: 100%;
        transition: transform 0.3s ease;
    }

    .card-destacado:hover .card-destacado-img {
        transform: scale(1.05);
    }

    .card-destacado-body {
        padding: 20px;
    }

    .card-destacado .badge-destacado {
        background: #ffc107;
        color: #212529;
        font-weight: bold;
        padding: 5px 10px;
        border-radius: 20px;
    }

    .sin-eventos {
        text-align: center;
        padding: 50px;
        background: var(--card-bg);
        border-radius: 15px;
        margin: 30px 0;
        border: 1px solid var(--border-color);
    }

    .sin-eventos i {
        font-size: 4rem;
        color: var(--border-color);
        margin-bottom: 20px;
    }

    .sin-eventos h3 {
        color: var(--text-color);
        margin-bottom: 10px;
    }

    .sin-eventos p {
        color: var(--text-light);
    }

    /* Estilos para el formulario de filtro */
    .mes-selector form {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .mes-selector .form-select {
        flex: 1;
        min-width: 150px;
        padding: 8px 15px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: var(--card-bg);
        color: var(--text-color);
        font-size: 0.9rem;
    }

    .mes-selector .btn {
        padding: 8px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }

    .mes-selector .btn-primary {
        background: var(--primary-color);
        border: none;
        color: var(--bg-color);
    }

    .mes-selector .btn-primary:hover {
        background: var(--secondary-color);
    }

    .mes-selector .btn-outline-secondary {
        background: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-color);
    }

    /* Navegación de meses disponibles */
    .meses-disponibles {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
    }

    .meses-disponibles .btn {
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .meses-disponibles .btn-outline-primary {
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
        background: transparent;
    }

    .meses-disponibles .btn-outline-primary:hover {
        background: var(--primary-color);
        color: var(--bg-color);
    }

    /* Mejoras para el tema oscuro */
    [data-theme="oscuro"] .mes-selector .form-select {
        background: var(--card-bg);
        color: var(--text-color);
        border-color: var(--border-color);
    }

    [data-theme="oscuro"] .evento-item {
        background: var(--bg-light);
    }

    [data-theme="oscuro"] .card-destacado {
        background: var(--card-bg);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    [data-theme="oscuro"] .card-destacado:hover {
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .eventos-hero h1 {
            font-size: 2.5rem;
        }

        .eventos-hero {
            padding: 60px 0;
        }

        .calendario-mes {
            grid-template-columns: 1fr;
        }

        .card-destacado {
            margin-bottom: 20px;
        }

        .mes-selector form {
            flex-direction: column;
        }

        .mes-selector .form-select {
            width: 100%;
        }

        .meses-disponibles {
            justify-content: center;
        }

        .eventos-destacados {
            padding: 20px;
            margin: 30px 0;
        }

        .sin-eventos {
            padding: 30px 20px;
        }
    }

    @media (max-width: 480px) {
        .eventos-hero h1 {
            font-size: 2rem;
        }

        .eventos-hero p {
            font-size: 1rem;
            padding: 0 15px;
        }

        .dia-calendario {
            padding: 15px;
        }

        .card-destacado-img {
            height: 150px;
        }
    }

    /* Animaciones */
    .fade-in {
        animation: fadeIn 0.8s ease forwards;
        opacity: 0;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Estilos generales */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -15px;
    }

    .col-md-4,
    .col-md-8 {
        padding: 0 15px;
        width: 100%;
    }

    @media (min-width: 768px) {
        .col-md-4 {
            width: 33.3333%;
        }

        .col-md-8 {
            width: 66.6667%;
        }
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

    .mt-2 {
        margin-top: 0.5rem !important;
    }

    .mt-3 {
        margin-top: 1rem !important;
    }

    .mt-4 {
        margin-top: 1.5rem !important;
    }

    .text-muted {
        color: var(--text-light) !important;
    }

    .text-warning {
        color: #ffc107 !important;
    }

    .text-success {
        color: #28a745 !important;
    }

    .d-flex {
        display: flex !important;
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

    .justify-content-center {
        justify-content: center !important;
    }

    .flex-wrap {
        flex-wrap: wrap !important;
    }

    .w-100 {
        width: 100% !important;
    }

    .small {
        font-size: 0.875rem !important;
    }

    .badge {
        display: inline-block;
        padding: 0.25em 0.4em;
        font-size: 0.75rem;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }

    .badge.bg-warning {
        background-color: #ffc107 !important;
    }

    .badge.bg-success {
        background-color: #28a745 !important;
    }

    .bg-light {
        background-color: var(--bg-light) !important;
    }
</style>

<!-- Hero Section -->
<section class="eventos-hero">
    <div class="container">
        <h1><i class="fas fa-calendar-alt me-3"></i>Eventos y Actividades</h1>
        <p>Descubre los mejores eventos, tours y experiencias únicas en Putumayo. Vive la aventura, conoce nuestra cultura y disfruta de la naturaleza.</p>
    </div>
</section>

<div class="container">
    <!-- Selector de Mes -->
    <div class="mes-selector fade-in">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-0">
                    <i class="fas fa-calendar me-2"></i>
                    Eventos de <?php echo date('F Y', strtotime("$anio_filtro-$mes_filtro-01")); ?>
                </h3>
                <p class="text-muted mb-0 mt-2">Explora los eventos programados para este mes</p>
            </div>
            <div class="col-md-4">
                <form method="GET" class="d-flex gap-2">
                    <select class="form-select" name="mes">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == $mes_filtro ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <select class="form-select" name="anio">
                        <?php for ($i = 2023; $i <= 2030; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == $anio_filtro ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="eventos.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sync"></i>
                    </a>
                </form>
            </div>
        </div>

        <!-- Navegación de meses con eventos -->
        <?php if (!empty($meses_disponibles)): ?>
            <div class="mt-4">
                <p class="text-muted mb-2">
                    <i class="fas fa-calendar-check me-2"></i>
                    Próximos eventos en:
                </p>
                <div class="meses-disponibles">
                    <?php foreach ($meses_disponibles as $mes): ?>
                        <a href="eventos.php?mes=<?php echo $mes['mes']; ?>&anio=<?php echo $mes['año']; ?>"
                            class="btn btn-outline-primary">
                            <?php echo $mes['nombre_mes'] . ' ' . $mes['año']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Eventos Destacados -->
    <?php if (!empty($eventos_destacados)): ?>
        <div class="eventos-destacados fade-in">
            <h3 class="mb-4">
                <i class="fas fa-star text-warning me-2"></i>
                Eventos Destacados
            </h3>
            <div class="row">
                <?php foreach ($eventos_destacados as $evento): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card card-destacado">
                            <?php if ($evento['imagen']): ?>
                                <img src="uploads/eventos/<?php echo htmlspecialchars($evento['imagen']); ?>"
                                    class="card-destacado-img"
                                    alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                    onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNmOGY5ZmEiLz48dGV4dCB4PSIxNTAiIHk9IjEwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjNmM3NTgwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5JbWFnZW4gbm8gZGlzcG9uaWJsZTwvdGV4dD48L3N2Zz4=';">
                            <?php else: ?>
                                <div class="card-destacado-img bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-destacado-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge badge-categoria" style="background: <?php echo $evento['categoria_color'] ?? $config['primary_color']; ?>;">
                                        <?php echo htmlspecialchars($evento['categoria_nombre'] ?? 'Evento'); ?>
                                    </span>
                                    <span class="badge badge-destacado">
                                        <i class="fas fa-star"></i> Destacado
                                    </span>
                                </div>

                                <h5 class="mb-2" style="color: var(--text-color);"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                <p class="text-muted small">
                                    <?php echo htmlspecialchars($evento['descripcion_corta'] ?? substr($evento['descripcion'] ?? '', 0, 100) . '...'); ?>
                                </p>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <div class="hora-evento">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('d M, H:i', strtotime($evento['fecha_inicio'])); ?>
                                        </div>
                                        <?php if ($evento['ubicacion']): ?>
                                            <div class="ubicacion-evento">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($evento['ubicacion']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (floatval($evento['precio']) > 0): ?>
                                        <div class="precio-evento">$<?php echo number_format($evento['precio'], 2); ?></div>
                                    <?php else: ?>
                                        <div class="text-success">Gratuito</div>
                                    <?php endif; ?>
                                </div>

                                <a href="<?php echo BASE_URL; ?>evento/<?php echo $evento['slug']; ?>" class="btn-detalle mt-3 w-100 justify-content-center">
                                    <i class="fas fa-info-circle"></i> Ver Detalles
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Calendario de Eventos por Día -->
    <?php if (empty($eventos)): ?>
        <div class="sin-eventos fade-in">
            <i class="fas fa-calendar-times"></i>
            <h3>No hay eventos programados para este mes</h3>
            <p class="text-muted">¡Vuelve pronto para descubrir nuevas actividades!</p>
            <a href="eventos.php" class="btn-detalle mt-3">
                <i class="fas fa-calendar-check me-2"></i>Ver eventos actuales
            </a>
        </div>
    <?php else: ?>
        <div class="calendario-mes">
            <?php
            // Agrupar eventos por día
            $eventos_por_dia = [];
            foreach ($eventos as $evento) {
                $fecha = date('Y-m-d', strtotime($evento['fecha_inicio']));
                if (!isset($eventos_por_dia[$fecha])) {
                    $eventos_por_dia[$fecha] = [];
                }
                $eventos_por_dia[$fecha][] = $evento;
            }

            // Mostrar días con eventos
            foreach ($eventos_por_dia as $fecha => $eventos_dia):
                $dia_numero = date('d', strtotime($fecha));
                $dia_nombre = date('l', strtotime($fecha));
            ?>
                <div class="dia-calendario fade-in">
                    <div class="dia-header">
                        <div>
                            <h4 class="mb-0" style="color: var(--text-color);"><?php echo $dia_nombre; ?></h4>
                            <small class="text-muted"><?php echo date('d M, Y', strtotime($fecha)); ?></small>
                        </div>
                        <div class="numero-dia"><?php echo $dia_numero; ?></div>
                    </div>

                    <?php foreach ($eventos_dia as $evento): ?>
                        <div class="evento-item <?php echo $evento['destacado'] ? 'evento-destacado' : ''; ?>">
                            <?php if ($evento['imagen']): ?>
                                <img src="uploads/eventos/<?php echo htmlspecialchars($evento['imagen']); ?>"
                                    class="evento-imagen"
                                    alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                    onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNmOGY5ZmEiLz48dGV4dCB4PSIxNTAiIHk9IjEwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjNmM3NTgwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5JbWFnZW4gbm8gZGlzcG9uaWJsZTwvdGV4dD48L3N2Zz4=';">
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-1" style="color: var(--text-color);"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                <?php if ($evento['destacado']): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-star"></i>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <span class="badge badge-categoria mb-3" style="background: <?php echo $evento['categoria_color'] ?? $config['primary_color']; ?>;">
                                <i class="<?php echo $evento['categoria_icono'] ?? 'fas fa-calendar'; ?>"></i>
                                <?php echo htmlspecialchars($evento['categoria_nombre'] ?? 'General'); ?>
                            </span>

                            <p class="text-muted small mb-3">
                                <?php echo htmlspecialchars($evento['descripcion_corta'] ?? substr($evento['descripcion'] ?? '', 0, 150) . '...'); ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="hora-evento">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('H:i', strtotime($evento['fecha_inicio'])); ?> -
                                    <?php echo date('H:i', strtotime($evento['fecha_fin'])); ?>
                                </div>

                                <?php if (floatval($evento['precio']) > 0): ?>
                                    <div class="precio-evento">$<?php echo number_format($evento['precio'], 2); ?></div>
                                <?php else: ?>
                                    <span class="badge bg-success">Gratuito</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($evento['ubicacion']): ?>
                                <div class="ubicacion-evento mb-3">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($evento['ubicacion']); ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($evento['capacidad_max'] > 0): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-users"></i>
                                        <?php echo $evento['inscripciones_actual']; ?>/<?php echo $evento['capacidad_max']; ?> cupos
                                    </small>
                                <?php endif; ?>

                                <a href="<?php echo BASE_URL; ?>evento/<?php echo $evento['slug']; ?>" class="btn-detalle">
                                    <i class="fas fa-info-circle"></i> Más Info
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Promoción de inscripción -->
    <?php if (!empty($eventos)): ?>
        <div class="mes-selector text-center mt-5 fade-in">
            <h3 class="mb-3" style="color: var(--text-color);">
                <i class="fas fa-bell text-warning me-2"></i>
                ¿No encuentras lo que buscas?
            </h3>
            <p class="text-muted mb-4">Explora otros meses o suscríbete para recibir notificaciones de nuevos eventos</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#mes-selector" class="btn-detalle">
                    <i class="fas fa-calendar-alt me-2"></i>Cambiar Mes
                </a>
                <a href="contacto.php" class="btn-detalle" style="background: var(--accent-color);">
                    <i class="fas fa-envelope me-2"></i>Contáctanos
                </a>
            </div>
        </div>
    <?php endif; ?>
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
                    element.style.animation = 'fadeIn 0.8s ease forwards';
                }
            });
        }

        // Verificar al cargar y al hacer scroll
        window.addEventListener('scroll', checkFade);
        window.addEventListener('load', checkFade);

        // Verificar inmediatamente
        checkFade();

        // Resaltar los eventos destacados
        const eventosDestacados = document.querySelectorAll('.evento-destacado');
        eventosDestacados.forEach(evento => {
            evento.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 8px 20px rgba(255, 200, 7, 0.2)';
            });

            evento.addEventListener('mouseleave', function() {
                this.style.boxShadow = '';
            });
        });

        // Scroll suave para los enlaces internos
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

        // Mejorar la experiencia del formulario de filtro
        const formFiltro = document.querySelector('.mes-selector form');
        if (formFiltro) {
            formFiltro.addEventListener('submit', function() {
                const btnSubmit = this.querySelector('button[type="submit"]');
                if (btnSubmit) {
                    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    btnSubmit.disabled = true;
                }
            });
        }
    });

    // Manejar imágenes que no cargan
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            img.addEventListener('error', function() {
                // Si ya tiene el fallback, no hacer nada
                if (!this.src.includes('data:image/svg+xml')) {
                    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNmOGY5ZmEiLz48dGV4dCB4PSIxNTAiIHk9IjEwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjNmM3NTgwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5JbWFnZW4gbm8gZGlzcG9uaWJsZTwvdGV4dD48L3N2Zz4=';
                }
            });
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?>