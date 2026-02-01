<?php
// Incluir header primero para que las variables de sesión estén disponibles
include 'includes/header.php';

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

// Obtener configuración del carrusel
$carousel_speed = (int)$config['carousel_speed'] ?: 5000;
$carousel_autoplay = $config['carousel_autoplay'] === '1';
$show_indicators = $config['show_indicators'] === '1';
$show_controls = $config['show_controls'] === '1';

// =============== FUNCIONES AUXILIARES ===============
if (!function_exists('getDestinos')) {
    function getDestinos($solo_activos = false)
    {
        global $pdo;
        try {
            $sql = "SELECT * FROM destinos";
            if ($solo_activos) {
                $sql .= " WHERE activo = 1";
            }
            $sql .= " ORDER BY nombre";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getDestinos: " . $e->getMessage());
            return [];
        }
    }
}

// Función auxiliar para obtener imágenes del carrusel
function getImagenesCarrusel()
{
    global $pdo;
    try {
        // Buscar imágenes marcadas para carrusel (nueva columna 'carrusel')
        $sql = "SELECT * FROM galeria WHERE activo = 1 AND carrusel = 1 ORDER BY fecha_subida DESC LIMIT 5";
        $stmt = $pdo->query($sql);
        $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si no hay imágenes marcadas para carrusel, tomar las últimas 5 activas
        if (empty($imagenes)) {
            $sql = "SELECT * FROM galeria WHERE activo = 1 ORDER BY fecha_subida DESC LIMIT 5";
            $stmt = $pdo->query($sql);
            $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $imagenes;
    } catch (PDOException $e) {
        error_log("Error en getImagenesCarrusel: " . $e->getMessage());
        return [];
    }
}

// Función mejorada para verificar si la imagen existe
function verificarImagen($ruta_imagen, $tipo = 'carrusel')
{
    if (empty($ruta_imagen)) {
        return false;
    }

    // Si ya es una ruta completa, verificar directamente
    if (file_exists($ruta_imagen)) {
        return $ruta_imagen;
    }

    // Lista de posibles ubicaciones según el tipo
    $posibles_rutas = [];

    if ($tipo === 'carrusel') {
        $posibles_rutas = [
            // Si solo es el nombre del archivo
            'uploads/galeria/' . $ruta_imagen,
            'uploads/' . $ruta_imagen,
            'img/' . $ruta_imagen,
            'images/' . $ruta_imagen,
            'assets/img/galeria/' . $ruta_imagen,

            // Rutas con basename (por si hay carpetas)
            'uploads/galeria/' . basename($ruta_imagen),
            'uploads/' . basename($ruta_imagen),
            'img/' . basename($ruta_imagen),
            'images/' . basename($ruta_imagen),
            'assets/img/galeria/' . basename($ruta_imagen),
        ];
    } elseif ($tipo === 'destino') {
        $posibles_rutas = [
            // Si solo es el nombre del archivo
            'uploads/destinos/' . $ruta_imagen,
            'uploads/' . $ruta_imagen,
            'img/destinos/' . $ruta_imagen,
            'images/destinos/' . $ruta_imagen,
            'assets/img/destinos/' . $ruta_imagen,

            // Rutas con basename (por si hay carpetas)
            'uploads/destinos/' . basename($ruta_imagen),
            'uploads/' . basename($ruta_imagen),
            'img/destinos/' . basename($ruta_imagen),
            'images/destinos/' . basename($ruta_imagen),
            'assets/img/destinos/' . basename($ruta_imagen),
        ];
    }

    // También probar con diferentes extensiones
    $base_name = pathinfo($ruta_imagen, PATHINFO_FILENAME);
    $extensiones = ['', '.jpg', '.jpeg', '.png', '.gif', '.webp', '.JPG', '.JPEG', '.PNG'];

    foreach ($posibles_rutas as $ruta_base) {
        foreach ($extensiones as $ext) {
            // Si la ruta base ya incluye extensión, no agregar otra
            if ($ext === '' || !pathinfo($ruta_base, PATHINFO_EXTENSION)) {
                $ruta_completa = $ruta_base . $ext;
                if (file_exists($ruta_completa) && is_file($ruta_completa)) {
                    return $ruta_completa;
                }
            }
        }
    }

    // Si la imagen empieza con http o https, es una URL externa
    if (preg_match('/^https?:\/\//', $ruta_imagen)) {
        return $ruta_imagen;
    }

    return false;
}

// Obtener destinos
try {
    $destinos = getDestinos();
    // Si no hay resultados, intentar obtener directamente
    if (empty($destinos)) {
        $stmt = $pdo->query("SELECT * FROM destinos WHERE activo = 1 ORDER BY nombre");
        $destinos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $destinos = [];
}

// Obtener imágenes para el carrusel
$imagenes_carrusel = getImagenesCarrusel();
?>

<!-- Hero Section con Carrusel -->
<section class="hero">
    <?php if (!empty($imagenes_carrusel)): ?>
        <div class="hero-carousel">
            <div class="carousel-container">
                <div class="carousel-slides">
                    <?php foreach ($imagenes_carrusel as $index => $imagen): ?>
                        <?php
                        $imagen_url = getImageUrl($imagen['imagen'] ?? '', 'galeria');
                        $imagen_final = $imagen_url ? $imagen_url : getImageUrlOrPlaceholder('', 'destinos');
                        ?>
                        <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                            <img src="<?php echo htmlspecialchars($imagen_final); ?>"
                                alt="<?php echo htmlspecialchars($imagen['titulo'] ?? 'Imagen del Putumayo'); ?>"
                                class="carousel-image"
                                onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwMCIgaGVpZ2h0PSI2MDAiIHZpZXdCb3g9IjAgMCAxMjAwIDYwMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTIwMCIgaGVpZ2h0PSI2MDAiIGZpbGw9IiMyZTJiMzciLz48dGV4dCB4PSI2MDAiIHk9IjMwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjI0IiBmaWxsPSIjZmZmIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5QdXR1bWF5byBUdXJpc21vPC90ZXh0Pjwvc3ZnPg=='">
                            <div class="carousel-overlay">
                                <div class="carousel-content">
                                    <?php if (!empty($imagen['titulo'])): ?>
                                        <h2><?php echo htmlspecialchars($imagen['titulo']); ?></h2>
                                    <?php endif; ?>
                                    <?php if (!empty($imagen['descripcion'])): ?>
                                        <p><?php echo htmlspecialchars(substr($imagen['descripcion'], 0, 150)); ?>...</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Controles del carrusel (mostrar según configuración) -->
                <?php if ($show_controls && count($imagenes_carrusel) > 1): ?>
                    <button class="carousel-control prev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-control next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                <?php endif; ?>

                <!-- Indicadores (mostrar según configuración) -->
                <?php if ($show_indicators && count($imagenes_carrusel) > 1): ?>
                    <div class="carousel-indicators">
                        <?php for ($i = 0; $i < count($imagenes_carrusel); $i++): ?>
                            <button class="carousel-indicator <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></button>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Hero sin carrusel si no hay imágenes -->
        <div class="hero-default">
            <div class="hero-content">
                <h1>Descubre la Magia del Putumayo</h1>
                <p>Explora los destinos más increíbles de la Amazonía colombiana</p>
                <a href="destinos.php" class="btn">Ver Destinos</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contenido del Hero sobre el carrusel -->
    <div class="hero-content-overlay">
        <div class="container">
            <h1><?php echo htmlspecialchars($config['site_name']); ?></h1>
            <p><?php echo htmlspecialchars($config['site_description']); ?></p>
            <a href="destinos.php" class="btn">Ver Destinos</a>
        </div>
    </div>
</section>

<!-- Destinos Destacados -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Destinos Destacados</h2>
            <p>Conoce los lugares más emblemáticos del Putumayo</p>
        </div>

        <div class="destinos-grid">
            <?php
            $destinos_destacados = !empty($destinos) ? array_slice($destinos, 0, 6) : [];

            if (!empty($destinos_destacados)):
                foreach ($destinos_destacados as $destino):
                    // Obtener URL de imagen usando la nueva función
                    $imagen_url = getImageUrl($destino['imagen_principal'] ?? '', 'destinos');

                    if (!$imagen_url && !empty($destino['imagen'])) {
                        $imagen_url = getImageUrl($destino['imagen'], 'destinos');
                    }

                    $imagen_final = $imagen_url ? $imagen_url : getImageUrlOrPlaceholder('', 'destinos');
            ?>
                    <div class="destino-card fade-in">
                        <img src="<?php echo htmlspecialchars($imagen_final); ?>"
                            alt="<?php echo htmlspecialchars($destino['nombre']); ?>"
                            class="destino-img"
                            loading="lazy"
                            onerror="this.onerror=null; this.src='<?php echo getImageUrlOrPlaceholder('', 'destinos'); ?>'">
                        <div class="destino-content">
                            <h3><?php echo htmlspecialchars($destino['nombre']); ?></h3>
                            <p><?php echo !empty($destino['descripcion']) ? htmlspecialchars(substr($destino['descripcion'], 0, 100)) . '...' : 'Descubre este maravilloso destino del Putumayo.'; ?></p>
                            <a href="<?php echo BASE_URL; ?>destino/<?php echo $destino['slug']; ?>" class="btn">Ver Más</a>
                        </div>
                    </div>
                <?php
                endforeach;
            else: ?>
                <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                    <i class="fas fa-map-marker-alt" style="font-size: 4rem; color: var(--border-color); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-color); opacity: 0.7;">No hay destinos disponibles</h3>
                    <p style="opacity: 0.7;">Próximamente agregaremos más destinos</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($destinos)): ?>
            <div style="text-align: center; margin-top: 3rem;">
                <a href="destinos.php" class="btn">Ver Todos los Destinos</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Por qué elegirnos -->
<section class="section" style="background: var(--card-bg);">
    <div class="container">
        <div class="section-title">
            <h2>¿Por Qué Elegirnos?</h2>
        </div>

        <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
            <div class="feature-card fade-in">
                <i class="fas fa-shield-alt" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3>Seguridad Garantizada</h3>
                <p>Tu seguridad es nuestra prioridad en todas las actividades</p>
            </div>

            <div class="feature-card fade-in">
                <i class="fas fa-leaf" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3>Turismo Sostenible</h3>
                <p>Comprometidos con el medio ambiente y las comunidades locales</p>
            </div>

            <div class="feature-card fade-in">
                <i class="fas fa-headset" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3>Soporte 24/7</h3>
                <p>Estamos aquí para ayudarte en todo momento</p>
            </div>

            <div class="feature-card fade-in">
                <i class="fas fa-award" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3>Experiencias Únicas</h3>
                <p>Vive aventuras que recordarás para siempre</p>
            </div>
        </div>
    </div>
</section>

<style>
    /* Variables CSS dinámicas según configuración */
    :root {
        --primary-color: <?php echo htmlspecialchars($config['primary_color']); ?>;
        --secondary-color: <?php echo htmlspecialchars($config['secondary_color']); ?>;
        --accent-color: <?php echo htmlspecialchars($config['accent_color']); ?>;
        --font-family: <?php echo htmlspecialchars($config['font_family']); ?>;
        --text-color: #2c3e50;
        --text-light: #7f8c8d;
        --bg-light: #f8f9fa;
        --card-bg: #ffffff;
        --border-color: #e1e8ed;
    }

    /* Aplicar la fuente principal */
    body {
        font-family: var(--font-family);
    }

    [data-theme="oscuro"] {
        --text-color: #ecf0f1;
        --text-light: #bdc3c7;
        --bg-light: #1a1a1a;
        --card-bg: #2c3e50;
        --border-color: #34495e;
    }

    /* Estilos para el carrusel */
    .hero {
        position: relative;
        height: 70vh;
        min-height: 500px;
        overflow: hidden;
        margin-top: 0;
        /* Eliminado porque el header ya tiene fixed position */
        padding-top: 0;
        /* Aseguramos que no haya padding adicional */
    }

    /* Ajustar para el header fijo */
    body {
        padding-top: 80px !important;
        /* Importante para sobrescribir estilos anteriores */
    }

    @media (max-width: 768px) {
        body {
            padding-top: 70px !important;
        }
    }

    .hero-carousel {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
    }

    .carousel-container {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .carousel-slides {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .carousel-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 1s ease;
    }

    .carousel-slide.active {
        opacity: 1;
    }

    .carousel-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .carousel-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.7));
        display: flex;
        align-items: flex-end;
        padding: 3rem;
    }

    .carousel-content {
        color: white;
        max-width: 800px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
    }

    .carousel-content h2 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .carousel-content p {
        font-size: 1.2rem;
        opacity: 0.9;
    }

    .carousel-control {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        transition: background 0.3s ease;
    }

    .carousel-control:hover {
        background: rgba(0, 0, 0, 0.8);
    }

    .carousel-control.prev {
        left: 20px;
    }

    .carousel-control.next {
        right: 20px;
    }

    .carousel-indicators {
        position: absolute;
        bottom: 20px;
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        gap: 10px;
        z-index: 10;
    }

    .carousel-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: none;
        background: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        transition: background 0.3s ease, transform 0.3s ease;
    }

    .carousel-indicator.active {
        background: white;
        transform: scale(1.2);
    }

    .hero-default {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .hero-content {
        text-align: center;
        color: white;
        z-index: 2;
    }

    .hero-content h1 {
        font-size: 3.5rem;
        margin-bottom: 1rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .hero-content p {
        font-size: 1.5rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }

    .hero-content-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.4));
    }

    .hero-content-overlay .container {
        text-align: center;
        color: white;
    }

    .hero-content-overlay h1 {
        font-size: 4rem;
        margin-bottom: 1rem;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    }

    .hero-content-overlay p {
        font-size: 1.8rem;
        margin-bottom: 2rem;
        opacity: 0.9;
        text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    }

    .hero-content-overlay .btn {
        background: white;
        color: var(--primary-color);
        font-size: 1.2rem;
        padding: 1rem 2.5rem;
        border-radius: 50px;
        font-weight: bold;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .hero-content-overlay .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        background: #f8f9fa;
    }

    /* Estilos para la sección de destinos */
    .section {
        padding: 5rem 0;
    }

    .section-title {
        text-align: center;
        margin-bottom: 3rem;
    }

    .section-title h2 {
        color: var(--text-color);
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .section-title p {
        color: var(--text-light);
        font-size: 1.2rem;
    }

    .destinos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .destino-card {
        background: var(--card-bg);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    [data-theme="oscuro"] .destino-card {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .destino-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    .destino-img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .destino-content {
        padding: 1.5rem;
    }

    .destino-content h3 {
        color: var(--text-color);
        margin-bottom: 1rem;
        font-size: 1.3rem;
    }

    .destino-content p {
        color: var(--text-light);
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    .destino-content .btn {
        background: var(--primary-color);
        color: white;
        padding: 0.8rem 1.5rem;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        transition: background 0.3s ease;
    }

    .destino-content .btn:hover {
        background: var(--secondary-color);
    }

    .feature-card {
        background: var(--card-bg);
        padding: 2rem;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    [data-theme="oscuro"] .feature-card {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .feature-card:hover {
        transform: translateY(-5px);
    }

    .feature-card h3 {
        color: var(--text-color);
        margin-bottom: 1rem;
    }

    .feature-card p {
        color: var(--text-light);
        line-height: 1.6;
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

    /* Estilos generales para botones */
    .btn {
        display: inline-block;
        padding: 12px 24px;
        background: var(--primary-color);
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(var(--primary-color-rgb), 0.3);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }

    @media (max-width: 768px) {
        .hero {
            height: 60vh;
            min-height: 400px;
        }

        .hero-content-overlay h1 {
            font-size: 2.5rem;
        }

        .hero-content-overlay p {
            font-size: 1.3rem;
        }

        .carousel-content {
            padding: 2rem;
        }

        .carousel-content h2 {
            font-size: 1.8rem;
        }

        .carousel-control {
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
        }

        .carousel-overlay {
            padding: 2rem;
        }

        .section-title h2 {
            font-size: 2rem;
        }

        .destinos-grid {
            grid-template-columns: 1fr;
        }

        .features-grid {
            grid-template-columns: 1fr !important;
        }
    }

    /* Estilos para placeholders de imágenes */
    .carousel-slide .carousel-image[src*="base64"] {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .destino-card .destino-img[src*="base64"] {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<script>
    // Funcionalidad del carrusel con configuración dinámica
    document.addEventListener('DOMContentLoaded', function() {
        const carouselSlides = document.querySelectorAll('.carousel-slide');
        const indicators = document.querySelectorAll('.carousel-indicator');
        const prevBtn = document.querySelector('.carousel-control.prev');
        const nextBtn = document.querySelector('.carousel-control.next');

        if (carouselSlides.length <= 1) return;

        let currentIndex = 0;
        let autoSlideInterval;

        // Configuración del carrusel desde PHP
        const carouselSpeed = <?php echo $carousel_speed; ?>;
        const carouselAutoplay = <?php echo $carousel_autoplay ? 'true' : 'false'; ?>;

        function showSlide(index) {
            // Ocultar todos los slides
            carouselSlides.forEach(slide => {
                slide.classList.remove('active');
            });

            // Mostrar slide actual
            carouselSlides[index].classList.add('active');

            // Actualizar indicadores
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });

            currentIndex = index;
        }

        function nextSlide() {
            let nextIndex = currentIndex + 1;
            if (nextIndex >= carouselSlides.length) {
                nextIndex = 0;
            }
            showSlide(nextIndex);
        }

        function prevSlide() {
            let prevIndex = currentIndex - 1;
            if (prevIndex < 0) {
                prevIndex = carouselSlides.length - 1;
            }
            showSlide(prevIndex);
        }

        function startAutoSlide() {
            clearInterval(autoSlideInterval);
            if (carouselAutoplay) {
                autoSlideInterval = setInterval(nextSlide, carouselSpeed);
            }
        }

        // Configurar controles
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                prevSlide();
                startAutoSlide();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                nextSlide();
                startAutoSlide();
            });
        }

        // Configurar indicadores
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                showSlide(index);
                startAutoSlide();
            });
        });

        // Iniciar auto slide si está habilitado
        if (carouselAutoplay) {
            startAutoSlide();
        }

        // Pausar auto slide al pasar el mouse
        const carouselContainer = document.querySelector('.carousel-container');
        if (carouselContainer && carouselAutoplay) {
            carouselContainer.addEventListener('mouseenter', () => {
                clearInterval(autoSlideInterval);
            });

            carouselContainer.addEventListener('mouseleave', () => {
                startAutoSlide();
            });
        }

        // Animaciones de fade-in
        const fadeElements = document.querySelectorAll('.fade-in');

        function checkFade() {
            fadeElements.forEach(element => {
                const rect = element.getBoundingClientRect();
                const windowHeight = window.innerHeight;

                if (rect.top < windowHeight - 100) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            });
        }

        // Verificar al cargar y al hacer scroll
        window.addEventListener('scroll', checkFade);
        window.addEventListener('load', checkFade);

        // Verificar inmediatamente
        checkFade();

        // Cambiar color de acento dinámico
        const accentColor = '<?php echo $config['accent_color']; ?>';
        if (accentColor) {
            // Aplicar color de acento a elementos específicos
            document.querySelectorAll('.carousel-indicator.active').forEach(indicator => {
                indicator.style.backgroundColor = accentColor;
            });
        }

        // Precargar imágenes para mejor experiencia
        function preloadImages() {
            const images = document.querySelectorAll('img[src]:not([src*="base64"])');
            images.forEach(img => {
                const image = new Image();
                image.src = img.src;
            });
        }

        // Precargar después de un breve retraso
        setTimeout(preloadImages, 1000);

        // Manejo de errores de imágenes
        document.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG') {
                const img = e.target;

                if (img.classList.contains('carousel-image')) {
                    img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwMCIgaGVpZ2h0PSI2MDAiIHZpZXdCb3g9IjAgMCAxMjAwIDYwMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTIwMCIgaGVpZ2h0PSI2MDAiIGZpbGw9IiMyZTJiMzciLz48dGV4dCB4PSI2MDAiIHk9IjMwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjI0IiBmaWxsPSIjZmZmIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5QdXR1bWF5byBUdXJpc21vPC90ZXh0Pjwvc3ZnPg==';
                } else if (img.classList.contains('destino-img')) {
                    img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiMyZTJiMzciLz48dGV4dCB4PSIxNTAiIHk9IjEwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjZmZmIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5EZXN0aW5vIGRlIFB1dHVtYXlvPC90ZXh0Pjwvc3ZnPg==';
                }
            }
        }, true);
    });
</script>

<?php include 'includes/footer.php'; ?>