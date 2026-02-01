<?php
// destino-detalle.php - VERSIÓN CON SISTEMA DE CONFIGURACIÓN DINÁMICO

// Iniciar sesión PRIMERO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que config.php exista
$config_path = 'includes/config.php';
if (!file_exists($config_path)) {
    die('Error: No se encontró el archivo de configuración.');
}

require_once $config_path;

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

// Variables de sesión del usuario
$usuario_logueado = isset($_SESSION['user_id']) || isset($_SESSION['usuario_id']);
$usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
$usuario_nombre = $_SESSION['username'] ?? $_SESSION['nombre'] ?? $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuario_email = $_SESSION['email'] ?? $_SESSION['usuario_email'] ?? null;

// Procesar formulario de reseña si el usuario está logueado
$reseña_error = '';
$reseña_success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_resena'])) {
    error_log("DEBUG - Formulario recibido");

    if ($usuario_logueado) {
        $destino_id = filter_var($_POST['destino_id'], FILTER_VALIDATE_INT);
        $titulo = trim($_POST['titulo'] ?? '');
        $comentario = trim($_POST['comentario'] ?? '');
        $calificacion = filter_var($_POST['calificacion'], FILTER_VALIDATE_INT);

        error_log("DEBUG - Valores: destino_id=$destino_id, titulo=$titulo, calificacion=$calificacion");
        error_log("DEBUG - Usuario: id=$usuario_id, nombre=$usuario_nombre, email=$usuario_email");

        // Validaciones
        if (empty($titulo) || strlen($titulo) < 3) {
            $reseña_error = "El título debe tener al menos 3 caracteres";
        } elseif (empty($comentario) || strlen($comentario) < 10) {
            $reseña_error = "El comentario debe tener al menos 10 caracteres";
        } elseif ($calificacion < 1 || $calificacion > 5) {
            $reseña_error = "La calificación debe ser entre 1 y 5 estrellas";
        } else {
            try {
                // Verificar si el usuario ya ha dejado una reseña para este destino
                $sql_check = "SELECT id FROM resenas_destino WHERE (usuario_id = ? OR email = ?) AND destino_id = ?";
                error_log("DEBUG - SQL Check: $sql_check");
                error_log("DEBUG - Parámetros Check: $usuario_id, $usuario_email, $destino_id");

                $stmt = $pdo->prepare($sql_check);
                $stmt->execute([$usuario_id, $usuario_email, $destino_id]);
                $reseña_existente = $stmt->fetch();

                if ($reseña_existente) {
                    $reseña_error = "Ya has dejado una reseña para este destino";
                    error_log("DEBUG - Usuario ya tiene reseña");
                } else {
                    // Insertar nueva reseña en resenas_destino (pendiente de aprobación)
                    $sql_insert = "INSERT INTO resenas_destino 
                        (destino_id, usuario_id, nombre, email, titulo, comentario, calificacion, estado) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')";

                    error_log("DEBUG - SQL Insert: $sql_insert");
                    error_log("DEBUG - Valores Insert: $destino_id, $usuario_id, $usuario_nombre, $usuario_email, $titulo, $comentario, $calificacion");

                    $stmt = $pdo->prepare($sql_insert);

                    // Si el email está vacío, usar un valor por defecto
                    if (empty($usuario_email)) {
                        $usuario_email = 'sin-email@ejemplo.com';
                    }

                    $resultado = $stmt->execute([
                        $destino_id,
                        $usuario_id,
                        $usuario_nombre,
                        $usuario_email,
                        $titulo,
                        $comentario,
                        $calificacion
                    ]);

                    error_log("DEBUG - Resultado execute: " . ($resultado ? 'true' : 'false'));

                    if ($resultado) {
                        $reseña_success = "¡Gracias por tu reseña! Será revisada por nuestro equipo antes de publicarse.";
                        error_log("DEBUG - Reseña guardada exitosamente");
                    } else {
                        $reseña_error = "Error: No se pudo guardar la reseña";
                        error_log("DEBUG - Error en execute");
                    }
                }
            } catch (PDOException $e) {
                error_log("ERROR PDO: " . $e->getMessage());
                error_log("ERROR SQLSTATE: " . $e->errorInfo[0]);
                error_log("ERROR Driver: " . $e->errorInfo[1]);
                error_log("ERROR Mensaje: " . $e->errorInfo[2]);
                $reseña_error = "Error al guardar la reseña: " . $e->getMessage();
            }
        }
    } else {
        $reseña_error = "Debes iniciar sesión para dejar una reseña";
        error_log("DEBUG - Usuario no logueado");
    }
}

// ========== URL AMIGABLE: /destino/{slug} ===========
$destino_slug = null;

if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $destino_slug = $_GET['slug'];
    error_log("destino-detalle.php: Buscando destino por slug: " . $destino_slug);
} elseif (isset($_GET['id'])) {
    // Compatibilidad temporal con parámetro antiguo (?id=) - redirigir a slug
    $destino_id_temp = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($destino_id_temp) {
        try {
            $stmt = $pdo->prepare("SELECT slug FROM destinos WHERE id = ?");
            $stmt->execute([$destino_id_temp]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && $result['slug']) {
                header('Location: ' . BASE_URL . 'destino/' . $result['slug']);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error obteniendo slug: " . $e->getMessage());
        }
    }
    header('Location: destinos.php');
    exit;
} else {
    error_log("destino-detalle.php: No se proporcionó slug");
    header('Location: destinos.php');
    exit;
}

// Obtener información del destino por slug
try {
    $stmt = $pdo->prepare("SELECT * FROM destinos WHERE slug = ? AND activo = 1");
    $stmt->execute([$destino_slug]);
    $destino = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$destino) {
        error_log("destino-detalle.php: Destino no encontrado con slug: " . $destino_slug);
        header('Location: destinos.php');
        exit;
    }

    $destino_id = $destino['id'];
    error_log("destino-detalle.php: Destino encontrado - ID: $destino_id, Nombre: " . $destino['nombre']);
} catch (PDOException $e) {
    error_log("Error al obtener destino: " . $e->getMessage());
    header('Location: destinos.php');
    exit;
}

// Obtener imágenes del carrusel
try {
    $stmt = $pdo->prepare("SELECT * FROM destino_imagenes WHERE destino_id = ? ORDER BY orden");
    $stmt->execute([$destino_id]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $imagenes = [];
    error_log("Error al obtener imágenes: " . $e->getMessage());
}

// Obtener actividades
try {
    $stmt = $pdo->prepare("SELECT * FROM actividades WHERE destino_id = ? AND activo = 1 ORDER BY nombre");
    $stmt->execute([$destino_id]);
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $actividades = [];
    error_log("Error al obtener actividades: " . $e->getMessage());
}

// Función para obtener reseñas con manejo seguro
function obtenerResenas($pdo, $destino_id)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM resenas_destino 
                              WHERE destino_id = ? AND estado = 'aprobado' 
                              ORDER BY fecha_creacion DESC LIMIT 10");
        $stmt->execute([$destino_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener reseñas: " . $e->getMessage());
        return [];
    }
}

$reseñas = obtenerResenas($pdo, $destino_id);

// Verificar si el usuario ya ha dejado una reseña para este destino
$usuario_ya_reseno = false;
if ($usuario_logueado) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM resenas_destino WHERE (usuario_id = ? OR email = ?) AND destino_id = ?");
        $stmt->execute([$usuario_id, $usuario_email, $destino_id]);
        $usuario_ya_reseno = $stmt->fetch() ? true : false;
    } catch (PDOException $e) {
        error_log("Error al verificar reseña del usuario: " . $e->getMessage());
    }
}

// Función mejorada para verificar existencia de archivos de imágenes
function verificarImagen($ruta_imagen, $tipo = 'destino')
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

    if ($tipo === 'destino') {
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
    } elseif ($tipo === 'actividad') {
        $posibles_rutas = [
            // Si solo es el nombre del archivo
            'uploads/actividades/' . $ruta_imagen,
            'uploads/' . $ruta_imagen,
            'img/actividades/' . $ruta_imagen,
            'images/actividades/' . $ruta_imagen,
            'assets/img/actividades/' . $ruta_imagen,

            // Rutas con basename (por si hay carpetas)
            'uploads/actividades/' . basename($ruta_imagen),
            'uploads/' . basename($ruta_imagen),
            'img/actividades/' . basename($ruta_imagen),
            'images/actividades/' . basename($ruta_imagen),
            'assets/img/actividades/' . basename($ruta_imagen),
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

// Incluir header
$header_path = 'includes/header.php';
if (file_exists($header_path)) {
    include $header_path;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($destino['nombre']); ?> - <?php echo htmlspecialchars($config['site_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($config['primary_color']); ?>;
            --secondary-color: <?php echo htmlspecialchars($config['secondary_color']); ?>;
            --accent-color: <?php echo htmlspecialchars($config['accent_color']); ?>;
            --font-family: <?php echo htmlspecialchars($config['font_family']); ?>;
            --text-color: #333;
            --text-light: #666;
            --bg-color: #ffffff;
            --bg-light: #f5f5f5;
            --border-color: #e0e0e0;
            --card-bg: #ffffff;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --error-bg: #f8d7da;
            --error-color: #721c24;
            --success-bg: #d4edda;
            --success-color: #155724;
            --warning-bg: #fff3cd;
            --warning-color: #856404;
            --info-bg: #e8f4fd;
            --info-color: #0c5460;
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
            --shadow-color: rgba(0, 0, 0, 0.3);
        }

        <?php
        // Función para oscurecer colores TEMPORAL - se usará solo para calcular primary-dark
        function calcularPrimaryDark($color, $amount = 20)
        {
            $color = ltrim($color, '#');
            if (strlen($color) == 3) {
                $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
            }

            $rgb = [
                hexdec(substr($color, 0, 2)),
                hexdec(substr($color, 2, 2)),
                hexdec(substr($color, 4, 2))
            ];

            $rgb = array_map(function ($value) use ($amount) {
                return max(0, $value - $amount);
            }, $rgb);

            return sprintf("#%02x%02x%02x", $rgb[0], $rgb[1], $rgb[2]);
        }

        // Calcular primary-dark usando la función temporal
        $primary_dark = calcularPrimaryDark($config['primary_color']);
        ?>

        /* Agregar primary-dark al root */
        :root {
            --primary-dark: <?php echo htmlspecialchars($primary_dark); ?>;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Estilos específicos para destino-detalle */
        .destino-detalle {
            padding-top: 100px;
            min-height: 100vh;
        }

        .breadcrumb {
            margin-bottom: 2rem;
            font-size: 0.9rem;
            color: var(--text-light);
            padding: 1rem 0;
        }

        .breadcrumb a {
            color: var(--text-light);
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumb a:hover {
            color: var(--primary-color);
        }

        .breadcrumb span {
            color: var(--primary-color);
            font-weight: 500;
        }

        .destino-header {
            margin-bottom: 2rem;
        }

        .destino-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--text-color);
            font-weight: 700;
        }

        .destino-location {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .destino-location i {
            color: var(--primary-color);
        }

        /* Carrusel mejorado */
        .carousel-container {
            position: relative;
            margin-bottom: 3rem;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .carousel-inner {
            display: flex;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .carousel-item {
            min-width: 100%;
            position: relative;
        }

        .carousel-item img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            display: block;
        }

        .carousel-control {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--card-bg);
            color: var(--primary-color);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            z-index: 10;
        }

        .carousel-control:hover {
            background: var(--primary-color);
            color: var(--bg-color);
            transform: translateY(-50%) scale(1.1);
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
            border: 2px solid var(--bg-color);
            background: transparent;
            cursor: pointer;
            transition: all 0.3s;
        }

        .carousel-indicator.active {
            background: var(--bg-color);
            transform: scale(1.2);
        }

        /* Layout principal */
        .destino-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            margin: 3rem 0;
        }

        .destino-main {
            /* Columna principal */
        }

        .destino-sidebar {
            position: sticky;
            top: 120px;
            height: fit-content;
        }

        .info-box {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .info-box h3 {
            color: var(--text-color);
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
        }

        .info-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .info-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-label {
            margin: 0;
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .info-value {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .rating-stars {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }

        .rating-stars i {
            color: #FFD700;
            font-size: 1.1rem;
        }

        .rating-value {
            margin-left: 10px;
            font-weight: 700;
            color: var(--text-color);
        }

        /* Sección de descripción */
        .section-title {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .destino-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-color);
            margin-bottom: 2rem;
        }

        .caracteristicas-list {
            list-style: none;
            padding: 0;
        }

        .caracteristicas-list li {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .caracteristicas-list i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        /* Botones generales */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            font-family: var(--font-family);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--bg-color);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(var(--primary-color-rgb), 0.3);
        }

        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: var(--bg-color);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        /* Actividades grid */
        .actividades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .actividad-card {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .actividad-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .actividad-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .actividad-content {
            padding: 1.5rem;
        }

        .actividad-title {
            margin: 0 0 0.5rem 0;
            color: var(--text-color);
            font-size: 1.2rem;
        }

        .actividad-description {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .actividad-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .actividad-price {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.3rem;
        }

        .actividad-duration {
            color: var(--text-light);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Reseñas */
        .resenas-section {
            margin: 4rem 0;
        }

        .resenas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .resenas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .resena-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
        }

        .resena-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .resena-author {
            margin: 0;
            color: var(--text-color);
            font-weight: 600;
        }

        .resena-date {
            margin: 5px 0 0 0;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .resena-rating {
            color: #FFD700;
        }

        .resena-comment {
            margin: 0;
            color: var(--text-color);
            font-style: italic;
            line-height: 1.6;
        }

        /* Formulario de reseña */
        .resena-form-container {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
        }

        .resena-form-title {
            color: var(--text-color);
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .resena-form-title i {
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.1);
        }

        .rating-selector {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .star-rating {
            cursor: pointer;
            font-size: 1.8rem;
            color: #ddd;
            transition: color 0.3s;
        }

        .star-rating:hover,
        .star-rating.selected {
            color: #FFD700;
        }

        .star-rating:hover~.star-rating {
            color: #ddd;
        }

        .btn-submit-resena {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--bg-color);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit-resena:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(var(--primary-color-rgb), 0.3);
        }

        .btn-submit-resena:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-submit-resena:disabled:hover {
            transform: none;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .login-prompt {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--bg-color);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 3rem;
        }

        .login-prompt h3 {
            margin: 0 0 1rem 0;
            font-size: 1.4rem;
        }

        .login-prompt p {
            margin: 0 0 1.5rem 0;
            opacity: 0.9;
        }

        .btn-login {
            background: var(--bg-color);
            color: var(--primary-color);
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Alertas para reseñas */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .alert-success {
            background-color: var(--success-bg);
            color: var(--success-color);
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background-color: var(--error-bg);
            color: var(--error-color);
            border-left: 4px solid #dc3545;
        }

        .alert-close {
            cursor: pointer;
            font-size: 20px;
            color: inherit;
            opacity: 0.7;
        }

        .alert-close:hover {
            opacity: 1;
        }

        /* CTA Section */
        .cta-section {
            margin: 3rem 0;
            padding: 3rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 20px;
            color: var(--bg-color);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            align-items: center;
        }

        .cta-content h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
        }

        .cta-content p {
            margin: 0;
            opacity: 0.95;
        }

        .btn-reservar {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-color);
            color: var(--primary-color);
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-reservar:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: var(--card-bg);
            border-radius: 15px;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-light);
            margin: 0;
        }

        .text-muted {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* Estilos para imágenes de placeholder */
        .image-placeholder {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--bg-color);
            text-align: center;
            font-weight: 600;
        }

        .image-placeholder i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            opacity: 0.8;
        }

        .image-placeholder span {
            display: block;
            font-size: 0.9rem;
            opacity: 0.9;
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

        .carousel-container,
        .info-box,
        .actividad-card,
        .resena-card,
        .resena-form-container {
            animation: fadeIn 0.6s ease-out;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .destino-content {
                grid-template-columns: 1fr;
            }

            .destino-sidebar {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .destino-title {
                font-size: 2rem;
            }

            .carousel-item img {
                height: 300px;
            }

            .actividades-grid,
            .resenas-grid {
                grid-template-columns: 1fr;
            }

            .resenas-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .cta-section {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .btn-reservar {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .destino-title {
                font-size: 1.8rem;
            }

            .destino-location {
                font-size: 1rem;
            }

            .carousel-control {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .info-box {
                padding: 1.5rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .cta-section {
                padding: 2rem;
            }

            .btn-reservar {
                padding: 12px 20px;
                font-size: 1rem;
            }
        }

        /* Estilos para iconos específicos */
        .fa-leaf {
            color: var(--primary-color);
        }

        .fa-map-marker-alt {
            color: var(--primary-color);
        }

        .fa-hiking {
            color: var(--primary-color);
        }

        .fa-star {
            color: #FFD700;
        }

        .fa-check-circle {
            color: var(--success-color);
        }

        .fa-exclamation-circle {
            color: var(--error-color);
        }

        .fa-info-circle {
            color: var(--accent-color);
        }
    </style>
</head>

<body>
    <section class="destino-detalle">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="index.php">Inicio</a> &gt;
                <a href="destinos.php">Destinos</a> &gt;
                <span><?php echo htmlspecialchars($destino['nombre']); ?></span>
            </nav>

            <!-- Header del destino -->
            <div class="destino-header">
                <h1 class="destino-title"><?php echo htmlspecialchars($destino['nombre']); ?></h1>

                <?php if (!empty($destino['ubicacion'])): ?>
                    <p class="destino-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($destino['ubicacion']); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Carrusel de imágenes -->
            <?php
            $imagenes_validas = [];
            if (count($imagenes) > 0) {
                foreach ($imagenes as $imagen) {
                    $imagen_url = verificarImagen($imagen['imagen'] ?? '', 'destino');
                    if ($imagen_url) {
                        $imagenes_validas[] = [
                            'url' => $imagen_url,
                            'nombre' => $imagen['imagen'] ?? ''
                        ];
                    }
                }
            }

            if (count($imagenes_validas) > 0): ?>
                <div class="carousel-container">
                    <div class="carousel-inner" id="carousel-inner">
                        <?php foreach ($imagenes_validas as $imagen): ?>
                            <div class="carousel-item">
                                <img src="<?php echo htmlspecialchars($imagen['url']); ?>"
                                    alt="<?php echo htmlspecialchars($destino['nombre']); ?>"
                                    class="destino-img"
                                    onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\" image-placeholder\" style=\"width:100%;height:500px;\"><i class=\"fas fa-mountain-sun\"></i><span>Imagen del Putumayo</span>
                            </div>';">
                    </div>
                <?php endforeach; ?>
                </div>

                <?php if (count($imagenes_validas) > 1): ?>
                    <button class="carousel-control prev" id="carousel-prev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-control next" id="carousel-next">
                        <i class="fas fa-chevron-right"></i>
                    </button>

                    <div class="carousel-indicators" id="carousel-indicators">
                        <?php for ($i = 0; $i < count($imagenes_validas); $i++): ?>
                            <button class="carousel-indicator <?php echo $i === 0 ? 'active' : ''; ?>"
                                data-index="<?php echo $i; ?>"></button>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
        </div>

        <?php
                // Verificar imagen principal del destino como fallback usando getImageUrl
                $imagen_principal_url = getImageUrl($destino['imagen_principal'] ?? '', 'destinos');
                if (!$imagen_principal_url && !empty($destino['imagen'])) {
                    $imagen_principal_url = getImageUrl($destino['imagen'], 'destinos');
                }

                if ($imagen_principal_url): ?>
            <div class="carousel-container">
                <img src="<?php echo htmlspecialchars($imagen_principal_url); ?>"
                    alt="<?php echo htmlspecialchars($destino['nombre']); ?>"
                    class="destino-img"
                    style="width: 100%; height: 500px; object-fit: cover; display: block;"
                    onerror="this.onerror=null; this.src='<?php echo getImageUrlOrPlaceholder('', 'destinos'); ?>';">
            </div>

        <?php else: ?>
            <div class="carousel-container" style="height: 500px;">
                <div class="image-placeholder" style="width:100%;height:100%;">
                    <i class="fas fa-mountain-sun"></i>
                    <span><?php echo htmlspecialchars($destino['nombre']); ?></span>
                    <span style="font-size:0.8rem;margin-top:10px;">Putumayo Turismo</span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Contenido principal -->
    <div class="destino-content">
        <!-- Columna principal -->
        <div class="destino-main">
            <h2 class="section-title">Acerca de <?php echo htmlspecialchars($destino['nombre']); ?></h2>

            <div class="destino-description">
                <?php
                if (isset($destino['descripcion']) && !empty(trim($destino['descripcion']))) {
                    echo nl2br(htmlspecialchars($destino['descripcion']));
                } else {
                    echo "<p style='color: var(--text-light); font-style: italic;'>Descripción no disponible.</p>";
                }
                ?>
            </div>

            <!-- Características -->
            <?php if (!empty($actividades) || !empty($reseñas)): ?>
                <div style="margin: 2rem 0;">
                    <h3 class="section-title" style="font-size: 1.5rem;">Características Destacadas</h3>
                    <ul class="caracteristicas-list">
                        <?php if (!empty($actividades)): ?>
                            <li>
                                <i class="fas fa-hiking"></i>
                                <strong><?php echo count($actividades); ?></strong> actividades disponibles
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($destino['ubicacion'])): ?>
                            <li>
                                <i class="fas fa-map-pin"></i>
                                Ubicación: <?php echo htmlspecialchars($destino['ubicacion']); ?>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($reseñas)): ?>
                            <li>
                                <i class="fas fa-star"></i>
                                <strong><?php echo count($reseñas); ?></strong> reseñas de visitantes
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="destino-sidebar">
            <div class="info-box">
                <h3>Información Importante</h3>

                <!-- Precio promedio -->
                <?php
                $precio_promedio = 0;
                $actividades_con_precio = array_filter($actividades, function ($a) {
                    return !empty($a['precio']) && $a['precio'] > 0;
                });

                if (count($actividades_con_precio) > 0) {
                    $suma_precios = array_sum(array_column($actividades_con_precio, 'precio'));
                    $precio_promedio = round($suma_precios / count($actividades_con_precio));
                }

                if ($precio_promedio > 0): ?>
                    <div class="info-item">
                        <p class="info-label">Precio promedio por actividad</p>
                        <p class="info-value">$<?php echo number_format($precio_promedio, 0, ',', '.'); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Calificación -->
                <?php if (count($reseñas) > 0): ?>
                    <div class="info-item">
                        <p class="info-label">Calificación promedio</p>
                        <?php
                        $calificacion_promedio = 0;
                        $calificaciones = array_column($reseñas, 'calificacion');
                        if (!empty($calificaciones)) {
                            $calificacion_promedio = array_sum($calificaciones) / count($calificaciones);
                        }
                        ?>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= round($calificacion_promedio) ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                            <span class="rating-value"><?php echo number_format($calificacion_promedio, 1); ?>/5</span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Botones de acción -->
                <div style="margin-top: 2rem;">
                    <?php
                    $url_reserva = $usuario_logueado ?
                        "reservas.php?destino=" . $destino_id :
                        "login.php?redirect=reservas.php?destino=" . $destino_id;
                    ?>

                    <a href="<?php echo $url_reserva; ?>" class="btn btn-primary" style="width: 100%; text-align: center; margin-bottom: 10px;">
                        <i class="fas fa-calendar-check"></i>
                        <?php echo $usuario_logueado ? 'Reservar Ahora' : 'Iniciar sesión'; ?>
                    </a>

                    <a href="contacto.php?destino=<?php echo $destino_id; ?>" class="btn btn-outline" style="width: 100%; text-align: center;">
                        <i class="fas fa-question-circle"></i>
                        Más información
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividades -->
    <?php if (!empty($actividades)): ?>
        <div style="margin: 4rem 0;">
            <h2 class="section-title" style="text-align: center; margin-bottom: 3rem;">
                Actividades Disponibles
            </h2>

            <div class="actividades-grid">
                <?php foreach ($actividades as $actividad):
                    $imagen_actividad_url = verificarImagen($actividad['imagen'] ?? '', 'actividad');
                ?>
                    <div class="actividad-card">
                        <?php if ($imagen_actividad_url): ?>
                            <img src="<?php echo htmlspecialchars($imagen_actividad_url); ?>"
                                alt="<?php echo htmlspecialchars($actividad['nombre']); ?>"
                                class="actividad-image"
                                onerror="this.onerror=null; this.parentElement.innerHTML='<div class=&quot;image-placeholder&quot; style=&quot;width:100%;height:220px;&quot;><i class=&quot;fas fa-hiking&quot;></i><span>'+this.alt+'</span></div>';">
                        <?php else: ?>
                            <div class="actividad-image image-placeholder" style="height:220px;">
                                <i class="fas fa-hiking"></i>
                                <span><?php echo htmlspecialchars($actividad['nombre']); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="actividad-content">
                            <h3 class="actividad-title"><?php echo htmlspecialchars($actividad['nombre']); ?></h3>

                            <?php if (!empty($actividad['descripcion'])): ?>
                                <p class="actividad-description">
                                    <?php
                                    $desc = htmlspecialchars($actividad['descripcion']);
                                    echo mb_strlen($desc) > 120 ? mb_substr($desc, 0, 120) . '...' : $desc;
                                    ?>
                                </p>
                            <?php endif; ?>

                            <div class="actividad-footer">
                                <div>
                                    <?php if (!empty($actividad['precio'])): ?>
                                        <p class="actividad-price">
                                            $<?php echo number_format($actividad['precio'], 0, ',', '.'); ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($actividad['duracion'])): ?>
                                        <p class="actividad-duration">
                                            <i class="fas fa-clock"></i>
                                            <?php echo htmlspecialchars($actividad['duracion']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <?php
                                $url_reserva_actividad = $usuario_logueado ?
                                    "reservas.php?destino=" . $destino_id . "&actividad=" . $actividad['id'] :
                                    "login.php?redirect=reservas.php?destino=" . $destino_id . "&actividad=" . $actividad['id'];
                                ?>

                                <a href="<?php echo $url_reserva_actividad; ?>" class="btn btn-primary btn-sm">
                                    <?php echo $usuario_logueado ? 'Reservar' : 'Ver detalles'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sección de Reseñas -->
    <div class="resenas-section">
        <div class="resenas-header">
            <h2 class="section-title">
                Reseñas de Visitantes
            </h2>
            <?php if ($usuario_logueado && !$usuario_ya_reseno): ?>
                <button class="btn btn-primary" onclick="mostrarFormularioResena()">
                    <i class="fas fa-star"></i> Dejar una reseña
                </button>
            <?php elseif ($usuario_logueado && $usuario_ya_reseno): ?>
                <button class="btn btn-secondary" disabled>
                    <i class="fas fa-check"></i> Ya dejaste tu reseña
                </button>
            <?php endif; ?>
        </div>

        <!-- Mensajes de éxito/error -->
        <?php if ($reseña_success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($reseña_success); ?>
                <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>

        <?php if ($reseña_error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($reseña_error); ?>
                <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>

        <!-- Formulario para dejar reseña (visible si usuario está logueado y no ha reseñado) -->
        <?php if ($usuario_logueado && !$usuario_ya_reseno): ?>
            <div class="resena-form-container" id="resenaFormContainer" style="<?php echo isset($_POST['submit_resena']) ? 'display: block;' : 'display: none;' ?>">
                <h3 class="resena-form-title">
                    <i class="fas fa-pen-alt"></i>
                    Deja tu reseña sobre <?php echo htmlspecialchars($destino['nombre']); ?>
                </h3>

                <form method="POST" id="resenaForm">
                    <input type="hidden" name="destino_id" value="<?php echo $destino_id; ?>">

                    <div class="form-group">
                        <label for="titulo">Título de tu reseña *</label>
                        <input type="text" id="titulo" name="titulo" class="form-control"
                            placeholder="Ej: ¡Una experiencia inolvidable!"
                            value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>"
                            required>
                        <small class="text-muted">Describe brevemente tu experiencia</small>
                    </div>

                    <div class="form-group">
                        <label>Calificación *</label>
                        <div class="rating-selector" id="ratingSelector">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star star-rating" data-value="<?php echo $i; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="calificacion" id="calificacion" value="<?php echo htmlspecialchars($_POST['calificacion'] ?? '0'); ?>" required>
                        <small class="text-muted">Selecciona de 1 a 5 estrellas</small>
                    </div>

                    <div class="form-group">
                        <label for="comentario">Tu experiencia *</label>
                        <textarea id="comentario" name="comentario" class="form-control" rows="5"
                            placeholder="Comparte tu experiencia en este destino. ¿Qué te gustó más? ¿Recomendarías este lugar?"
                            required><?php echo htmlspecialchars($_POST['comentario'] ?? ''); ?></textarea>
                        <small class="text-muted">Mínimo 10 caracteres. Sé específico y describe tu experiencia.</small>
                    </div>

                    <div class="form-group">
                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Tu reseña será revisada por nuestro equipo antes de publicarse.
                            Esto puede tomar hasta 24 horas.
                        </p>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="submit_resena" class="btn-submit-resena">
                            <i class="fas fa-paper-plane"></i>
                            Enviar Reseña
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="ocultarFormularioResena()">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif (!$usuario_logueado): ?>
            <!-- Si no está logueado, mostrar invitación a iniciar sesión -->
            <div class="login-prompt">
                <h3>¿Ya has visitado <?php echo htmlspecialchars($destino['nombre']); ?>?</h3>
                <p>Inicia sesión para compartir tu experiencia y ayudar a otros viajeros.</p>
                <a href="login.php?redirect=<?php echo urlencode("destino-detalle.php?id=" . $destino_id); ?>" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar sesión para reseñar
                </a>
            </div>
        <?php endif; ?>

        <!-- Lista de reseñas existentes -->
        <?php if (!empty($reseñas)): ?>
            <div class="resenas-grid">
                <?php foreach ($reseñas as $reseña): ?>
                    <div class="resena-card">
                        <div class="resena-header">
                            <div>
                                <h4 class="resena-author">
                                    <?php echo htmlspecialchars($reseña['nombre'] ?? 'Anónimo'); ?>
                                </h4>
                                <?php if (!empty($reseña['fecha_creacion'])): ?>
                                    <p class="resena-date">
                                        <?php echo date('d/m/Y', strtotime($reseña['fecha_creacion'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($reseña['calificacion'])): ?>
                                <div class="resena-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $reseña['calificacion'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($reseña['titulo'])): ?>
                            <h5 style="margin: 0 0 10px 0; color: var(--text-color);"><?php echo htmlspecialchars($reseña['titulo']); ?></h5>
                        <?php endif; ?>

                        <?php if (!empty($reseña['comentario'])): ?>
                            <p class="resena-comment">
                                <?php echo htmlspecialchars($reseña['comentario']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <p>Aún no hay reseñas para este destino. ¡Sé el primero en opinar!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- CTA Final -->
    <div class="cta-section">
        <div class="cta-content">
            <h3>¿Listo para vivir la experiencia?</h3>
            <p>Reserva ahora tu viaje a <?php echo htmlspecialchars($destino['nombre']); ?> y descubre todo lo que tiene para ofrecer.</p>
        </div>
        <div>
            <a href="<?php echo $url_reserva; ?>" class="btn-reservar">
                <i class="fas fa-calendar-alt"></i>
                <?php echo $usuario_logueado ? 'Reservar Ahora' : 'Iniciar Sesión'; ?>
            </a>
        </div>
    </div>
    </div>
    </section>

    <script>
        // Funcionalidad del carrusel mejorada - Envuelta en IIFE para evitar conflictos
        (function() {
            'use strict';

            document.addEventListener('DOMContentLoaded', function() {
                const carouselInner = document.getElementById('carousel-inner');
                const prevBtn = document.getElementById('carousel-prev');
                const nextBtn = document.getElementById('carousel-next');
                const indicators = document.querySelectorAll('.carousel-indicator');

                if (!carouselInner) return;

                const items = carouselInner.querySelectorAll('.carousel-item');
                let currentIndex = 0;
                let autoSlideInterval;

                function updateCarousel() {
                    carouselInner.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';

                    indicators.forEach(function(indicator, index) {
                        if (index === currentIndex) {
                            indicator.classList.add('active');
                        } else {
                            indicator.classList.remove('active');
                        }
                    });
                }

                function nextSlide() {
                    currentIndex = (currentIndex + 1) % items.length;
                    updateCarousel();
                }

                function prevSlide() {
                    currentIndex = (currentIndex - 1 + items.length) % items.length;
                    updateCarousel();
                }

                function startAutoSlide() {
                    if (items.length <= 1) return;
                    clearInterval(autoSlideInterval);
                    autoSlideInterval = setInterval(nextSlide, 5000);
                }

                if (prevBtn) {
                    prevBtn.addEventListener('click', function() {
                        prevSlide();
                        startAutoSlide();
                    });
                }

                if (nextBtn) {
                    nextBtn.addEventListener('click', function() {
                        nextSlide();
                        startAutoSlide();
                    });
                }

                indicators.forEach(function(indicator, index) {
                    indicator.addEventListener('click', function() {
                        currentIndex = index;
                        updateCarousel();
                        startAutoSlide();
                    });
                });

                startAutoSlide();

                // Pausar en hover
                if (carouselInner.parentElement) {
                    carouselInner.parentElement.addEventListener('mouseenter', function() {
                        clearInterval(autoSlideInterval);
                    });

                    carouselInner.parentElement.addEventListener('mouseleave', function() {
                        startAutoSlide();
                    });
                }
            });

            // Smooth scroll para anclas
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
                    anchor.addEventListener('click', function(e) {
                        e.preventDefault();
                        const target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    });
                });
            });
        })();

        // Funcionalidad para reseñas
        document.addEventListener('DOMContentLoaded', function() {
            // Sistema de calificación con estrellas
            const ratingStars = document.querySelectorAll('.star-rating');
            const ratingInput = document.getElementById('calificacion');

            if (ratingStars.length > 0 && ratingInput) {
                // Si ya hay un valor en el formulario, marcar las estrellas
                if (ratingInput.value > 0) {
                    for (let i = 0; i < ratingStars.length; i++) {
                        if (i < ratingInput.value) {
                            ratingStars[i].classList.add('selected');
                        }
                    }
                }

                // Event listeners para las estrellas
                ratingStars.forEach(star => {
                    star.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        ratingInput.value = value;

                        // Remover todas las selecciones
                        ratingStars.forEach(s => s.classList.remove('selected'));

                        // Seleccionar las estrellas hasta la clickeada
                        for (let i = 0; i < value; i++) {
                            ratingStars[i].classList.add('selected');
                        }
                    });

                    star.addEventListener('mouseover', function() {
                        const value = this.getAttribute('data-value');

                        // Remover todas las selecciones
                        ratingStars.forEach(s => s.classList.remove('selected'));

                        // Seleccionar las estrellas hasta la hover
                        for (let i = 0; i < value; i++) {
                            ratingStars[i].classList.add('selected');
                        }
                    });
                });

                // Restaurar selección al quitar hover
                document.getElementById('ratingSelector').addEventListener('mouseleave', function() {
                    const value = ratingInput.value;

                    // Remover todas las selecciones
                    ratingStars.forEach(s => s.classList.remove('selected'));

                    // Restaurar selección guardada
                    if (value > 0) {
                        for (let i = 0; i < value; i++) {
                            ratingStars[i].classList.add('selected');
                        }
                    }
                });
            }

            // Validación del formulario de reseña
            const resenaForm = document.getElementById('resenaForm');
            if (resenaForm) {
                resenaForm.addEventListener('submit', function(e) {
                    const titulo = document.getElementById('titulo');
                    const comentario = document.getElementById('comentario');
                    const calificacion = document.getElementById('calificacion');
                    let isValid = true;

                    // Limpiar errores previos
                    document.querySelectorAll('.error-message').forEach(el => el.remove());
                    document.querySelectorAll('.form-control').forEach(el => el.style.borderColor = '');

                    // Validar título
                    if (!titulo.value.trim() || titulo.value.trim().length < 3) {
                        showError(titulo, 'El título debe tener al menos 3 caracteres');
                        isValid = false;
                    }

                    // Validar comentario
                    if (!comentario.value.trim() || comentario.value.trim().length < 10) {
                        showError(comentario, 'El comentario debe tener al menos 10 caracteres');
                        isValid = false;
                    }

                    // Validar calificación
                    if (!calificacion.value || calificacion.value < 1 || calificacion.value > 5) {
                        showError(document.getElementById('ratingSelector'), 'Debes seleccionar una calificación');
                        isValid = false;
                    }

                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            }

            function showError(element, message) {
                element.style.borderColor = '#dc3545';
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.style.color = '#dc3545';
                errorDiv.style.fontSize = '0.9rem';
                errorDiv.style.marginTop = '5px';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
                element.parentNode.appendChild(errorDiv);
            }

            // Smooth scroll para anclas
            document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Manejo de errores de imágenes
            document.addEventListener('error', function(e) {
                if (e.target.tagName === 'IMG') {
                    const img = e.target;
                    if (img.classList.contains('destino-img') || img.classList.contains('actividad-image')) {
                        const placeholder = document.createElement('div');
                        placeholder.className = 'image-placeholder';
                        placeholder.style.width = img.width ? img.width + 'px' : '100%';
                        placeholder.style.height = img.height ? img.height + 'px' : '100%';

                        const icon = document.createElement('i');
                        if (img.classList.contains('destino-img')) {
                            icon.className = 'fas fa-mountain-sun';
                            placeholder.innerHTML = `<i class="fas fa-mountain-sun"></i><span>${img.alt || 'Imagen del Putumayo'}</span>`;
                        } else {
                            icon.className = 'fas fa-hiking';
                            placeholder.innerHTML = `<i class="fas fa-hiking"></i><span>${img.alt || 'Actividad'}</span>`;
                        }

                        img.parentNode.replaceChild(placeholder, img);
                    }
                }
            }, true);

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
        });

        // Funciones globales para mostrar/ocultar formulario de reseña
        function mostrarFormularioResena() {
            const formContainer = document.getElementById('resenaFormContainer');
            if (formContainer) {
                formContainer.style.display = 'block';
                formContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        function ocultarFormularioResena() {
            const formContainer = document.getElementById('resenaFormContainer');
            if (formContainer) {
                formContainer.style.display = 'none';
            }
        }

        // Configuración dinámica del color de acento
        document.addEventListener('DOMContentLoaded', function() {
            const accentColor = '<?php echo htmlspecialchars($config["accent_color"]); ?>';
            if (accentColor) {
                const style = document.createElement('style');
                style.textContent = `
                .btn-primary:hover {
                    box-shadow: 0 5px 15px ${hexToRgba(accentColor, 0.3)} !important;
                }
                
                .btn-submit-resena:hover {
                    box-shadow: 0 5px 15px ${hexToRgba(accentColor, 0.3)} !important;
                }
                
                .btn-login:hover {
                    box-shadow: 0 5px 15px ${hexToRgba(accentColor, 0.3)} !important;
                }
                
                .btn-reservar:hover {
                    box-shadow: 0 5px 20px ${hexToRgba(accentColor, 0.3)} !important;
                }
                
                .btn-outline:hover {
                    background: ${accentColor} !important;
                    border-color: ${accentColor} !important;
                }
            `;
                document.head.appendChild(style);
            }

            function hexToRgba(hex, alpha) {
                const r = parseInt(hex.slice(1, 3), 16);
                const g = parseInt(hex.slice(3, 5), 16);
                const b = parseInt(hex.slice(5, 7), 16);
                return `rgba(${r}, ${g}, ${b}, ${alpha})`;
            }
        });
    </script>

    <?php
    // Ahora podemos incluir el footer sin problema
    if (file_exists('includes/footer.php')) {
        include 'includes/footer.php';
    }
    ?>
</body>

</html><?php
// destino-detalle.php - CORREGIDO