<?php
// destinos.php - VERSIÓN CORREGIDA

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir header
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

    // SEO
    'meta_title',
    'meta_description',
    'meta_keywords'
];

$config = [];
foreach ($config_keys as $key) {
    // Valores por defecto
    $default = '';
    if ($key === 'site_name') $default = 'Putumayo Turismo';
    if ($key === 'site_description') $default = 'Descubre la belleza del Putumayo';
    if ($key === 'primary_color') $default = '#2e8b57';
    if ($key === 'secondary_color') $default = '#1e6b47';
    if ($key === 'accent_color') $default = '#2196f3';
    if ($key === 'font_family') $default = "'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    if ($key === 'meta_title') $default = 'Destinos del Putumayo';
    if ($key === 'meta_description') $default = 'Descubre los destinos más increíbles del Putumayo';

    $config[$key] = getConfigValue($key, $default);
}

// Función auxiliar para obtener destinos si no existe
if (!function_exists('getDestinos')) {
    function getDestinos($solo_activos = false, $orden_personalizado = true)
    {
        global $pdo;
        try {
            $sql = "SELECT * FROM destinos";
            if ($solo_activos) {
                $sql .= " WHERE activo = 1";
            }

            if ($orden_personalizado) {
                $sql .= " ORDER BY orden ASC, nombre ASC";
            } else {
                $sql .= " ORDER BY nombre ASC";
            }

            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getDestinos: " . $e->getMessage());
            return [];
        }
    }
}

// Obtener destinos con manejo de errores
$destinos = [];
$error_message = null;

try {
    $destinos = getDestinos(true, true);
} catch (Exception $e) {
    error_log("Error al cargar destinos: " . $e->getMessage());
    $error_message = "Error al cargar los destinos. Por favor intenta más tarde.";
}

// Si no hay destinos de la función, intentar obtener directamente
if (empty($destinos) && !$error_message) {
    try {
        if (isset($pdo)) {
            $stmt = $pdo->query("SELECT * FROM destinos WHERE activo = 1 ORDER BY orden ASC, nombre ASC");
            $destinos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error al obtener destinos directamente: " . $e->getMessage());
        $error_message = "Error al cargar los destinos. Por favor intenta más tarde.";
    }
}

// Función mejorada para verificar si la imagen existe
function verificarImagenDestino($imagen)
{
    if (empty($imagen)) {
        return false;
    }

    // Lista de posibles ubicaciones de imágenes
    $posibles_rutas = [
        // Si la imagen ya es una ruta completa
        $imagen,

        // Ubicaciones comunes
        'uploads/destinos/' . $imagen,
        'uploads/' . $imagen,
        'img/destinos/' . $imagen,
        'images/destinos/' . $imagen,
        'assets/img/destinos/' . $imagen,

        // Si es solo un nombre de archivo, probar con uploads/destinos/
        'uploads/destinos/' . basename($imagen),
        'uploads/' . basename($imagen),
        'img/destinos/' . basename($imagen),
        'images/destinos/' . basename($imagen),
        'assets/img/destinos/' . basename($imagen),
    ];

    // También probar con diferentes extensiones
    $base_name = pathinfo($imagen, PATHINFO_FILENAME);
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
    if (preg_match('/^https?:\/\//', $imagen)) {
        return $imagen;
    }

    return false;
}

// Función para obtener la URL de imagen con fallback
function obtenerImagenDestino($destino)
{
    $imagen_principal = $destino['imagen_principal'] ?? '';
    $imagen_valida = verificarImagenDestino($imagen_principal);

    if ($imagen_valida) {
        return $imagen_valida;
    }

    // Intentar con campo alternativo si existe
    if (!empty($destino['imagen'])) {
        $imagen_alternativa = verificarImagenDestino($destino['imagen']);
        if ($imagen_alternativa) {
            return $imagen_alternativa;
        }
    }

    // Si hay galería, usar la primera imagen
    if (!empty($destino['galeria'])) {
        $galeria = json_decode($destino['galeria'], true);
        if (is_array($galeria) && !empty($galeria)) {
            $primera_imagen = $galeria[0];
            $imagen_galeria = verificarImagenDestino($primera_imagen);
            if ($imagen_galeria) {
                return $imagen_galeria;
            }
        }
    }

    return false;
}
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
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.1);
        --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.15);
        --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.2);
        --transition: all 0.3s ease;
    }

    [data-theme="oscuro"] {
        --text-color: #f8f9fa;
        --text-light: #bdc3c7;
        --bg-light: #1a1a1a;
        --card-bg: #1e1e1e;
        --border-color: #333;
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.3);
        --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.4);
        --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.5);
    }

    /* Aplicar la fuente principal */
    body {
        font-family: var(--font-family);
    }

    /* Estilos para la página de destinos */
    .destinos-page {
        padding-top: 100px;
        min-height: 100vh;
        background: var(--bg-light);
    }

    .section-header {
        text-align: center;
        margin-bottom: 3rem;
        padding: 2rem 0;
    }

    .section-header h1 {
        font-size: 2.8rem;
        color: var(--text-color);
        margin-bottom: 1rem;
        font-weight: 700;
    }

    .section-header p {
        font-size: 1.2rem;
        color: var(--text-light);
        max-width: 600px;
        margin: 0 auto;
    }

    /* Grid de destinos mejorado */
    .destinos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 2rem;
        margin: 2rem 0;
    }

    /* Cards de destinos mejoradas */
    .destino-card {
        background: var(--card-bg);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
        position: relative;
        cursor: pointer;
    }

    .destino-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-lg);
    }

    .destino-img-container {
        position: relative;
        width: 100%;
        height: 250px;
        overflow: hidden;
        background: var(--bg-light);
    }

    .destino-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .destino-card:hover .destino-img {
        transform: scale(1.1);
    }

    .destino-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.7) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .destino-card:hover .destino-overlay {
        opacity: 1;
    }

    .destino-content {
        padding: 1.5rem;
    }

    .destino-title {
        font-size: 1.5rem;
        color: var(--text-color);
        margin-bottom: 0.8rem;
        font-weight: 600;
    }

    .destino-location {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-light);
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .destino-location i {
        color: var(--primary-color);
    }

    .destino-description {
        color: var(--text-color);
        line-height: 1.6;
        margin-bottom: 1rem;
        font-size: 0.95rem;
    }

    .destino-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }

    .destino-stats {
        display: flex;
        gap: 1rem;
        font-size: 0.85rem;
        color: var(--text-light);
    }

    .destino-stat {
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .destino-stat i {
        color: var(--primary-color);
    }

    .btn-explorar {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 10px 20px;
        background: var(--primary-color);
        color: var(--bg-color);
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        transition: var(--transition);
        font-size: 0.9rem;
        border: 2px solid transparent;
    }

    .btn-explorar:hover {
        background: var(--secondary-color);
        transform: translateX(5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        border-color: var(--accent-color);
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 12px 24px;
        background: var(--primary-color);
        color: var(--bg-color);
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: var(--transition);
        border: none;
        cursor: pointer;
    }

    .btn-primary:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .admin-badge {
        background: var(--accent-color);
        color: var(--bg-color);
        padding: 4px 10px;
        border-radius: 5px;
        font-size: 0.75rem;
        display: inline-block;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    /* Estado vacío */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 4rem 2rem;
        background: var(--card-bg);
        border-radius: 15px;
        box-shadow: var(--shadow-sm);
    }

    .empty-state i {
        font-size: 4rem;
        color: var(--text-light);
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state h3 {
        color: var(--text-color);
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: var(--text-light);
    }

    /* Alerta */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        background: var(--card-bg);
        box-shadow: var(--shadow-sm);
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert i {
        font-size: 1.5rem;
    }

    /* Animaciones */
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

    .fade-in {
        animation: fadeIn 0.6s ease forwards;
        opacity: 0;
    }

    /* Stagger animation para las cards */
    .destino-card:nth-child(1) {
        animation-delay: 0.1s;
    }

    .destino-card:nth-child(2) {
        animation-delay: 0.2s;
    }

    .destino-card:nth-child(3) {
        animation-delay: 0.3s;
    }

    .destino-card:nth-child(4) {
        animation-delay: 0.4s;
    }

    .destino-card:nth-child(5) {
        animation-delay: 0.5s;
    }

    .destino-card:nth-child(6) {
        animation-delay: 0.6s;
    }

    /* Filtros y búsqueda (para futuras mejoras) */
    .destinos-filters {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 10px 20px;
        background: var(--card-bg);
        color: var(--text-color);
        border: 2px solid var(--border-color);
        border-radius: 25px;
        cursor: pointer;
        transition: var(--transition);
        font-weight: 500;
    }

    .filter-btn:hover,
    .filter-btn.active {
        background: var(--primary-color);
        color: var(--bg-color);
        border-color: var(--primary-color);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .destinos-page {
            padding-top: 80px;
        }

        .section-header h1 {
            font-size: 2rem;
        }

        .section-header p {
            font-size: 1rem;
        }

        .destinos-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .destino-img-container {
            height: 200px;
        }

        .container {
            padding: 0 1rem;
        }
    }

    @media (max-width: 480px) {
        .destino-content {
            padding: 1rem;
        }

        .destino-footer {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
    }

    /* Estilo para cuando no hay imagen */
    .no-image {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        height: 100%;
        width: 100%;
        position: relative;
        overflow: hidden;
    }

    .no-image::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.05) 50%,
                transparent 70%);
        animation: shine 3s infinite linear;
    }

    @keyframes shine {
        0% {
            transform: translateX(-100%) translateY(-100%) rotate(45deg);
        }

        100% {
            transform: translateX(100%) translateY(100%) rotate(45deg);
        }
    }

    .no-image i {
        font-size: 3rem;
        color: var(--bg-color);
        opacity: 0.8;
        z-index: 1;
    }

    /* Imagen de placeholder mejorada */
    .placeholder-image {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--bg-color);
        text-align: center;
        padding: 2rem;
        height: 100%;
    }

    .placeholder-image i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.9;
    }

    .placeholder-image span {
        font-size: 0.9rem;
        opacity: 0.8;
    }
</style>

<section class="destinos-page">
    <div class="container">
        <!-- Header de la sección -->
        <div class="section-header">
            <h1>Descubre Nuestros Destinos</h1>
            <p><?php echo htmlspecialchars($config['site_description']); ?></p>
        </div>

        <!-- Mensaje de error si existe -->
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Debug para verificar las imágenes (solo para desarrollo) -->
        <?php if (false && isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'superadmin'])): ?>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-family: monospace; font-size: 12px;">
                <strong>Debug Info:</strong><br>
                <?php
                foreach ($destinos as $d) {
                    $img = obtenerImagenDestino($d);
                    echo htmlspecialchars($d['nombre']) . ": " . ($img ? $img : 'NO IMG') . "<br>";
                    echo "Campo imagen_principal: " . htmlspecialchars($d['imagen_principal'] ?? 'vacío') . "<br><br>";
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Grid de destinos -->
        <div class="destinos-grid">
            <?php if (!empty($destinos)): ?>
                <?php foreach ($destinos as $index => $destino): ?>
                    <div class="destino-card fade-in">
                        <div class="destino-img-container">
                            <?php
                            // Usar nueva función getImageUrl con BASE_URL
                            $imagen_url = getImageUrl($destino['imagen_principal'] ?? '', 'destinos');
                            if (!$imagen_url && !empty($destino['imagen'])) {
                                $imagen_url = getImageUrl($destino['imagen'], 'destinos');
                            }
                            $imagen_final = $imagen_url ? $imagen_url : getImageUrlOrPlaceholder('', 'destinos');
                            ?>

                            <img src="<?php echo htmlspecialchars($imagen_final); ?>"
                                alt="<?php echo htmlspecialchars($destino['nombre']); ?>"
                                class="destino-img"
                                loading="lazy"
                                onerror="this.onerror=null; this.src='<?php echo getImageUrlOrPlaceholder('', 'destinos'); ?>';">

                            <div class="destino-overlay"></div>
                        </div>

                        <div class="destino-content">
                            <!-- Badge de administrador -->
                            <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'superadmin'])): ?>
                                <div class="admin-badge">
                                    <i class="fas fa-sort"></i> Orden: <?php echo $destino['orden'] ?? 0; ?>
                                </div>
                            <?php endif; ?>

                            <h3 class="destino-title"><?php echo htmlspecialchars($destino['nombre']); ?></h3>

                            <?php if (!empty($destino['ubicacion'])): ?>
                                <div class="destino-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($destino['ubicacion']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($destino['descripcion'])): ?>
                                <p class="destino-description">
                                    <?php
                                    $descripcion = htmlspecialchars($destino['descripcion']);
                                    echo mb_strlen($descripcion) > 120 ? mb_substr($descripcion, 0, 120) . '...' : $descripcion;
                                    ?>
                                </p>
                            <?php endif; ?>

                            <div class="destino-footer">
                                <div class="destino-stats">
                                    <?php
                                    // Obtener cantidad de actividades
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM actividades WHERE destino_id = ? AND activo = 1");
                                        $stmt->execute([$destino['id']]);
                                        $actividades_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    } catch (PDOException $e) {
                                        $actividades_count = 0;
                                    }
                                    ?>

                                    <?php if ($actividades_count > 0): ?>
                                        <div class="destino-stat">
                                            <i class="fas fa-hiking"></i>
                                            <span><?php echo $actividades_count; ?> actividades</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <a href="<?php echo BASE_URL; ?>destino/<?php echo $destino['slug']; ?>"
                                    class="btn-explorar"
                                    aria-label="Explorar <?php echo htmlspecialchars($destino['nombre']); ?>"
                                    style="background-color: var(--primary-color); border-color: var(--accent-color);">
                                    Explorar
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-map-marked-alt"></i>
                    <h3>No hay destinos disponibles</h3>
                    <p>Estamos trabajando para traerte los mejores destinos del Putumayo. Vuelve pronto.</p>
                    <div style="margin-top: 2rem;">
                        <a href="index.php" class="btn-primary" style="background-color: var(--primary-color);">
                            <i class="fas fa-home"></i> Volver al inicio
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Información adicional -->
        <?php if (!empty($destinos)): ?>
            <div style="text-align: center; margin-top: 3rem; padding: 2rem; background: var(--card-bg); border-radius: 15px; box-shadow: var(--shadow-sm);">
                <h3 style="color: var(--text-color); margin-bottom: 1rem;">¿No encuentras lo que buscas?</h3>
                <p style="color: var(--text-light); margin-bottom: 1.5rem;">Contáctanos y te ayudaremos a planificar tu viaje perfecto</p>
                <a href="contacto.php" class="btn-primary" style="background-color: var(--primary-color);">
                    <i class="fas fa-envelope"></i> Contactar
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
    (function() {
        'use strict';

        // Hacer las cards completamente clicables
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.destino-card');

            cards.forEach(function(card) {
                const link = card.querySelector('.btn-explorar');

                if (link) {
                    // Hacer toda la card clicable excepto el botón
                    card.addEventListener('click', function(e) {
                        // Si no se hizo clic en el botón, redirigir
                        if (!e.target.closest('.btn-explorar') && !e.target.closest('.destino-stat')) {
                            window.location.href = link.href;
                        }
                    });

                    // Agregar cursor pointer a toda la card
                    card.style.cursor = 'pointer';
                }
            });

            // Inicializar animaciones
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observar todas las cards con animación fade-in
            document.querySelectorAll('.fade-in').forEach(function(element) {
                observer.observe(element);
            });

            // Aplicar color de acento dinámico a elementos interactivos
            const accentColor = '<?php echo htmlspecialchars($config["accent_color"]); ?>';
            if (accentColor) {
                // Cambiar color de hover en botones
                const style = document.createElement('style');
                style.textContent = `
                .destino-stat i {
                    color: ${accentColor} !important;
                }
                
                .admin-badge {
                    background-color: ${accentColor} !important;
                }
                
                .destino-card:hover {
                    border-color: ${accentColor};
                    box-shadow: 0 15px 40px rgba(0,0,0,0.2), 0 0 0 2px ${accentColor}20;
                }
                
                .btn-explorar:hover {
                    border-color: ${accentColor} !important;
                    box-shadow: 0 5px 15px ${accentColor}40 !important;
                }
            `;
                document.head.appendChild(style);
            }
        });

        // Lazy loading de imágenes mejorado
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        imageObserver.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            document.querySelectorAll('img[data-src]').forEach(function(img) {
                imageObserver.observe(img);
            });
        }

        // Manejo de errores de imágenes
        document.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG' && e.target.classList.contains('destino-img')) {
                const container = e.target.parentElement;
                container.innerHTML = '<div class="placeholder-image"><i class="fas fa-mountain-sun"></i><span>Imagen no disponible</span></div>';
            }
        }, true);

        // Precargar imágenes para mejor experiencia
        function preloadImages() {
            const images = document.querySelectorAll('.destino-img[src]');
            images.forEach(img => {
                const image = new Image();
                image.src = img.src;
            });
        }

        // Precargar después de un breve retraso
        setTimeout(preloadImages, 1000);

    })();
</script>

<?php include 'includes/footer.php'; ?>