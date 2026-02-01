<?php
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Redirige al usuario según su rol
 */
function redirectByRole()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    $role = $_SESSION['user_role'] ?? 'usuario';

    switch ($role) {
        case 'superadmin':
        case 'admin':
            header('Location: admin/dashboard.php');
            break;

        case 'moderador':
            header('Location: moderador/dashboard.php');
            break;

        case 'usuario':
        default:
            header('Location: dashboard.php');
            break;
    }
    exit;
}

/**
 * Verifica si el usuario tiene un rol específico
 */
function hasRole($required_role)
{
    if (!isLoggedIn()) return false;

    $user_role = $_SESSION['user_role'] ?? 'usuario';

    if ($user_role == 'superadmin') return true;

    if (is_array($required_role)) {
        return in_array($user_role, $required_role);
    }

    return $user_role == $required_role;
}

/**
 * Verifica permisos y redirige si no tiene acceso
 */
function requireRole($required_role)
{
    if (!hasRole($required_role)) {
        if (!isLoggedIn()) {
            header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        } else {
            header('Location: ../unauthorized.php');
        }
        exit;
    }
}

/**
 * Verifica si el usuario puede acceder a una página específica
 */
function canAccess($page)
{
    if (!isLoggedIn()) return false;

    $user_role = $_SESSION['user_role'] ?? 'usuario';

    $permissions = [
        'superadmin' => ['*'],
        'admin' => [
            'admin/dashboard.php',
            'admin/gestion-reservas.php',
            'admin/gestion-resenas.php',
            'admin/configuracion.php',
            'reserva.php'
        ],
        'moderador' => [
            'moderador/dashboard.php',
            'moderador/gestion-resenas.php'
        ],
        'usuario' => [
            'dashboard.php',
            'mis-reservas.php',
            'mis-resenas.php',
            'perfil.php'
        ]
    ];

    if ($user_role == 'superadmin') return true;

    return isset($permissions[$user_role]) &&
        (in_array('*', $permissions[$user_role]) ||
            in_array($page, $permissions[$user_role]));
}

/**
 * Cierra sesión y redirige
 */
function logout()
{
    session_destroy();
    header('Location: login.php?msg=sesion_cerrada');
    exit;
}

/**
 * Obtiene el nombre del rol legible
 */
function getRoleName($role)
{
    $roles = [
        'superadmin' => 'Super Administrador',
        'admin' => 'Administrador',
        'moderador' => 'Moderador',
        'usuario' => 'Usuario'
    ];

    return $roles[$role] ?? 'Usuario';
}

/**
 * Verifica si el usuario actual puede editar/eliminar un recurso
 */
function canEdit($resource_user_id)
{
    if (!isLoggedIn()) return false;

    $current_user_id = $_SESSION['user_id'];
    $current_user_role = $_SESSION['user_role'];

    if (in_array($current_user_role, ['superadmin', 'admin'])) {
        return true;
    }

    if ($current_user_role == 'moderador' && $current_user_id == $resource_user_id) {
        return true;
    }

    return $current_user_id == $resource_user_id;
}

// ===================================================================
// FUNCIONES DE MANEJO DE DATOS
// ===================================================================

/**
 * Sanitizar datos de entrada
 */
function sanitizeInput($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Obtener el valor de un parámetro GET de forma segura
 */
function getParam($key, $default = null)
{
    return isset($_GET[$key]) ? sanitizeInput($_GET[$key]) : $default;
}

/**
 * Obtener el valor de un parámetro POST de forma segura
 */
function postParam($key, $default = null)
{
    return isset($_POST[$key]) ? sanitizeInput($_POST[$key]) : $default;
}

/**
 * Redirigir a una URL
 */
function redirect($url, $statusCode = 302)
{
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Alias para sanitizeInput (compatibilidad)
 */
function sanitize($data)
{
    return sanitizeInput($data);
}

// ===================================================================
// FUNCIONES DE FORMATEO Y VISUALIZACIÓN
// ===================================================================

/**
 * Obtener nombre del estado
 */
function getStatusName($status)
{
    $statuses = [
        'pendiente' => 'Pendiente',
        'confirmada' => 'Confirmada',
        'cancelada' => 'Cancelada',
        'aprobada' => 'Aprobada',
        'rechazada' => 'Rechazada'
    ];

    return $statuses[$status] ?? 'Desconocido';
}

/**
 * Formatear fecha
 */
function formatDate($date, $format = 'd/m/Y')
{
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Formatear fecha en español
 */
function formatFechaEspanol($fecha)
{
    if (empty($fecha)) return '';

    $timestamp = strtotime($fecha);
    $dia = date('d', $timestamp);
    $mes = getMesEspanol(date('n', $timestamp));
    $anio = date('Y', $timestamp);

    return $dia . ' de ' . $mes . ' de ' . $anio;
}

/**
 * Obtener el nombre del mes en español
 */
function getMesEspanol($numero_mes)
{
    $meses = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];
    return $meses[$numero_mes] ?? 'Desconocido';
}

/**
 * Formatear un precio para mostrar
 */
function formatoPrecio($precio, $moneda = '$')
{
    if (empty($precio) || $precio == 0) return 'No definido';
    return $moneda . number_format($precio, 0, ',', '.');
}

/**
 * Limitar texto a una longitud máxima
 */
function limitarTexto($texto, $longitud = 100, $sufijo = '...')
{
    if (strlen($texto) <= $longitud) return $texto;
    return substr($texto, 0, $longitud) . $sufijo;
}

// ===================================================================
// FUNCIONES DE ARCHIVOS E IMÁGENES
// ===================================================================

/**
 * Obtener URL de imagen validando su existencia
 * @param string $imagen Nombre del archivo de imagen
 * @param string $tipo Tipo de carpeta (destinos, galeria, actividades)
 * @return string|false URL de la imagen o false si no existe
 */
function getImageUrl($imagen, $tipo = 'destinos')
{
    // Validar que la imagen no esté vacía y no sea un valor inválido
    if (empty($imagen) || $imagen === 'Array' || $imagen === 'NULL' || strtolower($imagen) === 'null') {
        return false;
    }

    // Construir la ruta física del archivo
    $ruta_fisica = UPLOADS_PATH . $tipo . '/' . $imagen;

    // Verificar si el archivo existe físicamente
    if (file_exists($ruta_fisica) && is_file($ruta_fisica)) {
        // Retornar la URL web accesible desde el navegador
        return UPLOADS_URL . $tipo . '/' . $imagen;
    }

    return false;
}

/**
 * Obtener URL de imagen o placeholder
 * @param string $imagen Nombre del archivo de imagen
 * @param string $tipo Tipo de carpeta (destinos, galeria, actividades)
 * @return string URL de la imagen o del placeholder
 */
function getImageUrlOrPlaceholder($imagen, $tipo = 'destinos')
{
    $url = getImageUrl($imagen, $tipo);

    if ($url) {
        return $url;
    }

    // Retornar URL del placeholder según el tipo
    $placeholders = [
        'destinos' => UPLOADS_URL . 'placeholder-destino.jpg',
        'galeria' => UPLOADS_URL . 'placeholder-galeria.jpg',
        'actividades' => UPLOADS_URL . 'placeholder-actividad.jpg'
    ];

    return $placeholders[$tipo] ?? UPLOADS_URL . 'placeholder-destino.jpg';
}

/**
 * Subir archivo con validación
 */
function uploadFile($file, $destination, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'])
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error en la subida del archivo'];
    }

    $filename = uniqid() . '_' . basename($file['name']);
    $target_path = rtrim($destination, '/') . '/' . $filename;
    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'El archivo es demasiado grande (máximo 5MB)'];
    }

    if (!crearDirectorio($destination)) {
        return ['success' => false, 'error' => 'No se pudo crear el directorio de destino'];
    }

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Error al mover el archivo'];
    }
}

/**
 * Validar si un archivo es una imagen válida
 */
function esImagenValida($file)
{
    if ($file['error'] !== UPLOAD_ERR_OK) return false;

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) return false;
    if ($file['size'] > $max_size) return false;

    return true;
}

/**
 * Eliminar imagen del servidor
 * (Eliminada función duplicada para evitar error de redeclaración)
 */

/**
 * Crear directorio si no existe
 */
function crearDirectorio($directorio, $permisos = 0755)
{
    if (!file_exists($directorio)) {
        return mkdir($directorio, $permisos, true);
    }
    return true;
}

// ===================================================================
// FUNCIONES DE PAGINACIÓN
// ===================================================================

/**
 * Generar paginación
 */
function generatePagination($total_items, $items_per_page, $current_page, $url)
{
    $total_pages = ceil($total_items / $items_per_page);

    if ($total_pages <= 1) return '';

    $pagination = '<div class="pagination">';

    if ($current_page > 1) {
        $pagination .= '<a href="' . $url . '?page=' . ($current_page - 1) . '" class="page-link">&laquo; Anterior</a>';
    }

    for ($i = 1; $i <= $total_pages; $i++) {
        $active = $i == $current_page ? ' active' : '';
        $pagination .= '<a href="' . $url . '?page=' . $i . '" class="page-link' . $active . '">' . $i . '</a>';
    }

    if ($current_page < $total_pages) {
        $pagination .= '<a href="' . $url . '?page=' . ($current_page + 1) . '" class="page-link">Siguiente &raquo;</a>';
    }

    $pagination .= '</div>';

    return $pagination;
}

// ===================================================================
// FUNCIONES DE BASE DE DATOS
// ===================================================================

/**
 * Obtener un destino por su ID
 */
function obtenerDestinoPorId($id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM destinos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en obtenerDestinoPorId: " . $e->getMessage());
        return null;
    }
}

// Función para subir imágenes
function subirImagen($file, $upload_dir = '../uploads/', $allowed_types = ['png', 'jpg', 'jpeg', 'webp', 'gif'], $max_size = 5)
{
    $result = [
        'success' => false,
        'filename' => '',
        'error' => '',
        'full_path' => ''
    ];

    // Verificar si se subió un archivo
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $result['error'] = 'No se ha seleccionado ningún archivo';
        return $result;
    }

    // Verificar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo solo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
        ];

        $result['error'] = $error_messages[$file['error']] ?? 'Error desconocido al subir el archivo';
        return $result;
    }

    // Verificar que sea un archivo válido
    if (!is_uploaded_file($file['tmp_name'])) {
        $result['error'] = 'Archivo no válido';
        return $result;
    }

    // Obtener información del archivo
    $filename = basename($file['name']);
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // Validar tipo de archivo
    if (!in_array($file_ext, $allowed_types)) {
        $result['error'] = 'Tipo de archivo no permitido. Solo se permiten: ' . implode(', ', $allowed_types);
        return $result;
    }

    // Validar tamaño máximo (en MB)
    $max_size_bytes = $max_size * 1024 * 1024;
    if ($file['size'] > $max_size_bytes) {
        $result['error'] = "Archivo demasiado grande (máx {$max_size}MB)";
        return $result;
    }

    // Validar tamaño mínimo (opcional)
    $min_size_bytes = 10 * 1024; // 10KB mínimo
    if ($file['size'] < $min_size_bytes) {
        $result['error'] = 'Archivo demasiado pequeño (mín 10KB)';
        return $result;
    }

    // Validar que sea una imagen
    $image_info = @getimagesize($file['tmp_name']);
    if (!$image_info) {
        $result['error'] = 'El archivo no es una imagen válida';
        return $result;
    }

    // Lista blanca de tipos MIME
    $allowed_mimes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif'
    ];

    if (!in_array($image_info['mime'], $allowed_mimes)) {
        $result['error'] = 'Tipo MIME no permitido';
        return $result;
    }

    // Generar nombre único y seguro
    $safe_filename = preg_replace('/[^a-zA-Z0-9\._-]/', '', $filename);
    $safe_filename = substr($safe_filename, 0, 100); // Limitar longitud
    $new_filename = uniqid() . '_' . time() . '_' . $safe_filename;

    // Asegurar que el directorio termine con /
    $upload_dir = rtrim($upload_dir, '/') . '/';

    // Crear directorio si no existe
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $result['error'] = 'No se pudo crear el directorio de subida';
            return $result;
        }
    }

    // Verificar que el directorio sea escribible
    if (!is_writable($upload_dir)) {
        $result['error'] = 'El directorio de destino no tiene permisos de escritura';
        return $result;
    }

    $upload_path = $upload_dir . $new_filename;

    // Mover el archivo
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Verificar que el archivo se haya movido correctamente
        if (file_exists($upload_path)) {
            // Establecer permisos seguros
            chmod($upload_path, 0644);

            $result['success'] = true;
            $result['filename'] = $new_filename;
            $result['full_path'] = $upload_path;
            $result['original_name'] = $filename;
            $result['file_size'] = $file['size'];
            $result['mime_type'] = $image_info['mime'];
            $result['dimensions'] = [
                'width' => $image_info[0],
                'height' => $image_info[1]
            ];
        } else {
            $result['error'] = 'El archivo no se guardó correctamente';
        }
    } else {
        $result['error'] = 'Error al mover el archivo subido';
    }

    return $result;
}

// Función para eliminar imagen
function eliminarImagen($filename, $upload_dir = '../uploads/')
{
    $upload_dir = rtrim($upload_dir, '/') . '/';
    $file_path = $upload_dir . $filename;

    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}

// Función para crear miniatura (opcional)
function crearMiniatura($source_path, $dest_path, $max_width = 300, $max_height = 200, $quality = 85)
{
    if (!file_exists($source_path)) {
        return false;
    }

    $info = getimagesize($source_path);
    if (!$info) {
        return false;
    }

    list($original_width, $original_height, $type) = $info;

    // Determinar el tipo de imagen
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($source_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }

    // Calcular nuevas dimensiones manteniendo proporción
    $ratio = min($max_width / $original_width, $max_height / $original_height);
    $new_width = round($original_width * $ratio);
    $new_height = round($original_height * $ratio);

    // Crear imagen destino
    $thumb = imagecreatetruecolor($new_width, $new_height);

    // Preservar transparencia para PNG y GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    // Redimensionar
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

    // Guardar miniatura
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($thumb, $dest_path, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($thumb, $dest_path, 9); // 0-9, 9 es máxima compresión
            break;
        case IMAGETYPE_WEBP:
            $result = imagewebp($thumb, $dest_path, $quality);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($thumb, $dest_path);
            break;
        default:
            $result = false;
    }

    // Liberar memoria
    imagedestroy($source);
    imagedestroy($thumb);

    return $result;
}

/**
 * Obtener una actividad por su ID
 */
function obtenerActividadPorId($id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM actividades WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en obtenerActividadPorId: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtener actividades con información del destino
 */
function getActividadesConDestino($solo_activas = true)
{
    global $pdo;
    try {
        $sql = "SELECT a.*, d.nombre as destino_nombre 
                FROM actividades a 
                LEFT JOIN destinos d ON a.destino_id = d.id";

        if ($solo_activas) {
            $sql .= " WHERE a.activo = 1";
        }

        $sql .= " ORDER BY a.nombre";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en getActividadesConDestino: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener todas las actividades de un destino
 */
function obtenerActividadesPorDestino($destino_id, $solo_activas = false)
{
    global $pdo;
    try {
        $sql = "SELECT * FROM actividades WHERE destino_id = ?";
        if ($solo_activas) {
            $sql .= " AND activo = 1";
        }
        $sql .= " ORDER BY nombre";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$destino_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en obtenerActividadesPorDestino: " . $e->getMessage());
        return [];
    }
}

/**
 * Contar el número de actividades asociadas a un destino
 */
function contarActividadesPorDestino($destino_id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM actividades WHERE destino_id = ?");
        $stmt->execute([$destino_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error en contarActividadesPorDestino: " . $e->getMessage());
        return 0;
    }
}

/**
 * Verificar si un destino tiene actividades asociadas
 */
function destinoTieneActividades($destino_id)
{
    return contarActividadesPorDestino($destino_id) > 0;
}

/**
 * Obtener estadísticas del dashboard
 */
function getDashboardStats()
{
    global $pdo;

    $stats = [];

    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM destinos WHERE activo = 1");
        $stats['total_destinos'] = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
        $stats['total_usuarios'] = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas WHERE estado = 'confirmada'");
        $stats['total_reservas'] = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM resenas WHERE estado = 'pendiente'");
        $stats['reseñas_pendientes'] = $stmt->fetchColumn() ?? 0;

        $stmt = $pdo->query("SELECT SUM(precio_total) as total FROM reservas 
                             WHERE estado = 'confirmada' 
                             AND MONTH(fecha_creacion) = MONTH(CURDATE()) 
                             AND YEAR(fecha_creacion) = YEAR(CURDATE())");
        $stats['ingresos_mes'] = $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        error_log("Error en getDashboardStats: " . $e->getMessage());
    }

    return $stats;
}

/**
 * Log de actividades del sistema
 */
function logActivity($user_id, $action, $details = '')
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address, user_agent) 
                              VALUES (?, ?, ?, ?, ?)");

        $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Error en logActivity: " . $e->getMessage());
        return false;
    }
}

// Alias para compatibilidad
function registrarActividad($user_id, $action, $details = null)
{
    return logActivity($user_id, $action, $details);
}

/**
 * Validar y procesar reserva
 */
function processReservation($data)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO reservas (usuario_id, destino_id, actividad_id, fecha_reserva, fecha_viaje, cantidad_personas, precio_total, estado) 
                              VALUES (?, ?, ?, CURDATE(), ?, ?, ?, 'pendiente')");

        $stmt->execute([
            $data['usuario_id'],
            $data['destino_id'],
            $data['actividad_id'],
            $data['fecha_viaje'],
            $data['cantidad_personas'],
            $data['precio_total']
        ]);

        $reserva_id = $pdo->lastInsertId();
        $pdo->commit();

        return ['success' => true, 'reserva_id' => $reserva_id];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Obtener valor de configuración
 */
function getConfig($clave, $default = '')
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = ?");
        $stmt->execute([$clave]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['valor'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Guardar configuración
 */
function setConfig($clave, $valor)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE valor = ?, fecha_actualizacion = NOW()");
        return $stmt->execute([$clave, $valor, $valor]);
    } catch (PDOException $e) {
        return false;
    }
}

// ===================================================================
// FUNCIONES UTILITARIAS
// ===================================================================

/**
 * Obtener la URL base del sitio
 */
function getBaseUrl()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

/**
 * Generar slug para URLs amigables (con soporte para español)
 */
function generarSlug($texto)
{
    // Convertir a minúsculas
    $slug = mb_strtolower($texto, 'UTF-8');

    // Reemplazar caracteres especiales del español
    $replacements = [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ñ' => 'n',
        'ü' => 'u',
        'à' => 'a',
        'è' => 'e',
        'ì' => 'i',
        'ò' => 'o',
        'ù' => 'u',
    ];
    $slug = strtr($slug, $replacements);

    // Reemplazar caracteres no alfanuméricos por guiones
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    // Eliminar guiones múltiples y del inicio/fin
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    return $slug;
}

/**
 * Generar slug único para evitar duplicados
 */
function generarSlugUnico($nombre, $tabla = 'destinos', $id = null)
{
    global $pdo;

    $slug_base = generarSlug($nombre);
    $slug = $slug_base;
    $contador = 1;

    // Verificar si el slug ya existe
    while (true) {
        if ($id) {
            $sql = "SELECT id FROM $tabla WHERE slug = ? AND id != ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$slug, $id]);
        } else {
            $sql = "SELECT id FROM $tabla WHERE slug = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$slug]);
        }

        if ($stmt->rowCount() == 0) {
            break;
        }

        $slug = $slug_base . '-' . $contador;
        $contador++;
    }

    return $slug;
}

/**
 * Mostrar mensajes de alerta
 */
function mostrarAlerta($mensaje, $tipo = 'info')
{
    $clases = [
        'success' => 'alert-success',
        'error' => 'alert-error',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];

    $clase = $clases[$tipo] ?? $clases['info'];

    return '<div class="alert ' . $clase . '">
                <span>' . sanitizeInput($mensaje) . '</span>
                <span class="alert-close" onclick="this.parentElement.remove()">&times;</span>
            </div>';
}

/**
 * Verificar si el servidor permite subir archivos
 */
function puedeSubirArchivos()
{
    return ini_get('file_uploads') == 1;
}

/**
 * Obtener el tamaño máximo de archivo permitido
 */
function getMaxUploadSize()
{
    $max_size = min(
        ini_get('post_max_size'),
        ini_get('upload_max_filesize')
    );
    return $max_size;
}

/**
 * Enviar email de notificación
 */
function sendEmailNotification($to, $subject, $message)
{
    $headers = "From: no-reply@putumayoturismo.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return mail($to, $subject, $message, $headers);
}

function redirectToDashboard()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }

    $role = $_SESSION['user_role'] ?? 'usuario';

    // Todos van al mismo dashboard
    header('Location: admin/dashboard.php');
    exit;
}

/**
 * Verificar permisos de acceso
 */
function checkAccess($required_roles = ['superadmin', 'admin', 'moderador', 'usuario'])
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }

    $user_role = $_SESSION['user_role'] ?? 'usuario';

    if (!in_array($user_role, (array)$required_roles)) {
        header('Location: ../unauthorized.php');
        exit;
    }
}

/**
 * Obtener estadísticas según el rol
 */
function getStatsByRole($user_id, $user_role)
{
    global $pdo;
    $stats = [];

    try {
        switch ($user_role) {
            case 'superadmin':
            case 'admin':
                // Estadísticas completas para administradores
                $stats['total_usuarios'] = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1")->fetchColumn();
                $stats['total_reservas'] = $pdo->query("SELECT COUNT(*) FROM reservas")->fetchColumn();
                $stats['reservas_pendientes'] = $pdo->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'")->fetchColumn();
                $stats['total_resenas'] = $pdo->query("SELECT COUNT(*) FROM resenas")->fetchColumn();
                $stats['resenas_pendientes'] = $pdo->query("SELECT COUNT(*) FROM resenas WHERE estado = 'pendiente'")->fetchColumn();
                $stats['ingresos_mes'] = $pdo->query("SELECT SUM(precio_total) FROM reservas WHERE estado = 'confirmada' AND MONTH(fecha_creacion) = MONTH(CURDATE())")->fetchColumn() ?? 0;
                break;

            case 'moderador':
                // Estadísticas para moderadores
                $stats['total_resenas'] = $pdo->query("SELECT COUNT(*) FROM resenas")->fetchColumn();
                $stats['resenas_pendientes'] = $pdo->query("SELECT COUNT(*) FROM resenas WHERE estado = 'pendiente'")->fetchColumn();
                $stats['resenas_aprobadas'] = $pdo->query("SELECT COUNT(*) FROM resenas WHERE estado = 'aprobada' AND MONTH(fecha_creacion) = MONTH(CURDATE())")->fetchColumn();
                break;

            case 'usuario':
            default:
                // Estadísticas para usuarios normales
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE usuario_id = ?");
                $stmt->execute([$user_id]);
                $stats['total_reservas'] = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE usuario_id = ? AND estado = 'pendiente'");
                $stmt->execute([$user_id]);
                $stats['reservas_pendientes'] = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM resenas WHERE usuario_id = ?");
                $stmt->execute([$user_id]);
                $stats['total_resenas'] = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT AVG(rating) FROM resenas WHERE usuario_id = ?");
                $stmt->execute([$user_id]);
                $stats['rating_promedio'] = number_format($stmt->fetchColumn() ?? 0, 1);
                break;
        }
    } catch (PDOException $e) {
        error_log("Error en getStatsByRole: " . $e->getMessage());
    }

    return $stats;
}

/**
 * Obtener color según el rol
 */
function getRoleColor($role)
{
    $colors = [
        'superadmin' => '#dc3545', // Rojo
        'admin' => '#007bff',      // Azul
        'moderador' => '#ffc107',  // Amarillo
        'usuario' => '#28a745'     // Verde
    ];

    return $colors[$role] ?? '#6c757d';
}
