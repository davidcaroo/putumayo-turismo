<?php
// Incluir archivos de configuración
include '../includes/config.php';
// No incluir functions.php aquí ya que ya está incluido en config.php

// Verificar autenticación y permisos
if (!isLoggedIn() || (!hasRole('superadmin') && !hasRole('admin'))) {
    header('Location: ../login.php');
    exit;
}

// Verificar si existe la variable $pdo
if (!isset($pdo)) {
    die("Error: No se pudo conectar a la base de datos. Verifica config.php");
}

// Función auxiliar para subir imágenes
if (!function_exists('subirImagen')) {
    function subirImagen($file, $target_dir)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Validar que sea una imagen
        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            return null;
        }

        // Crear directorio si no existe
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Generar nombre único
        $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($extension);

        if (!in_array($extension, $allowed_extensions)) {
            return null;
        }

        $filename = uniqid() . '_' . time() . '.' . $extension;
        $target_file = $target_dir . $filename;

        // Mover archivo
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $filename;
        }

        return null;
    }
}

// Función auxiliar para formatear fecha
if (!function_exists('formatDate')) {
    function formatDate($date)
    {
        if (empty($date)) return 'N/A';
        $dateTime = new DateTime($date);
        return $dateTime->format('d/m/Y H:i');
    }
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['subir_imagen'])) {
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'general');
        $carrusel = isset($_POST['carrusel']) ? 1 : 0;
        $activo = isset($_POST['activo']) ? 1 : 1;

        // Procesar imagen
        $imagen_nombre = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen_nombre = subirImagen($_FILES['imagen'], '../uploads/galeria/');
            if (empty($imagen_nombre)) {
                $error = "Error al subir la imagen. Verifica que sea una imagen válida y no exceda el tamaño permitido.";
            }
        } else {
            $error = "Por favor, selecciona una imagen";
        }

        if (!isset($error) && $imagen_nombre) {
            try {
                // Verificar si la columna carrusel existe, si no, agregarla
                $stmt = $pdo->prepare("SHOW COLUMNS FROM galeria LIKE 'carrusel'");
                $stmt->execute();
                $columna_existe = $stmt->fetch();

                if (!$columna_existe) {
                    $pdo->exec("ALTER TABLE galeria ADD COLUMN carrusel TINYINT(1) DEFAULT 0");
                }

                $stmt = $pdo->prepare("INSERT INTO galeria (titulo, descripcion, imagen, categoria, carrusel, activo) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$titulo, $descripcion, $imagen_nombre, $categoria, $carrusel, $activo])) {
                    $message = "Imagen subida correctamente";
                    if ($carrusel) {
                        $message .= " (Marcada para carrusel)";
                    }
                    // Registrar actividad si la función existe
                    if (function_exists('logActivity')) {
                        $user_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0;
                        if ($user_id) {
                            logActivity($user_id, 'subir_imagen', "Imagen subida: $titulo");
                        }
                    }
                } else {
                    $error = "Error al guardar la imagen en la base de datos";
                }
            } catch (PDOException $e) {
                $error = "Error en la base de datos: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['editar_imagen'])) {
        $imagen_id = $_POST['imagen_id'] ?? 0;
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'general');
        $carrusel = isset($_POST['carrusel']) ? 1 : 0;
        $activo = isset($_POST['activo']) ? 1 : 0;

        if (!is_numeric($imagen_id) || $imagen_id <= 0) {
            $error = "ID de imagen inválido";
        } else {
            try {
                // Obtener imagen actual
                $stmt = $pdo->prepare("SELECT imagen FROM galeria WHERE id = ?");
                $stmt->execute([$imagen_id]);
                $imagen_actual = $stmt->fetch();
                $imagen_nombre = $imagen_actual['imagen'] ?? null;

                // Procesar nueva imagen si se subió
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $nueva_imagen = subirImagen($_FILES['imagen'], '../uploads/galeria/');
                    if (!empty($nueva_imagen)) {
                        // Eliminar imagen anterior si existe
                        if ($imagen_nombre && file_exists("../uploads/galeria/" . $imagen_nombre)) {
                            unlink("../uploads/galeria/" . $imagen_nombre);
                        }
                        $imagen_nombre = $nueva_imagen;
                    } else {
                        $error = "Error al subir la nueva imagen";
                    }
                }

                if (!isset($error)) {
                    // Verificar si la columna carrusel existe
                    $stmt = $pdo->prepare("SHOW COLUMNS FROM galeria LIKE 'carrusel'");
                    $stmt->execute();
                    $columna_existe = $stmt->fetch();

                    if ($columna_existe) {
                        $stmt = $pdo->prepare("UPDATE galeria SET titulo = ?, descripcion = ?, imagen = ?, categoria = ?, carrusel = ?, activo = ? WHERE id = ?");
                        $stmt->execute([$titulo, $descripcion, $imagen_nombre, $categoria, $carrusel, $activo, $imagen_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE galeria SET titulo = ?, descripcion = ?, imagen = ?, categoria = ?, activo = ? WHERE id = ?");
                        $stmt->execute([$titulo, $descripcion, $imagen_nombre, $categoria, $activo, $imagen_id]);
                    }

                    $message = "Imagen actualizada correctamente";
                    if ($carrusel) {
                        $message .= " (Marcada para carrusel)";
                    }
                    // Registrar actividad si la función existe
                    if (function_exists('logActivity')) {
                        $user_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0;
                        if ($user_id) {
                            logActivity($user_id, 'editar_imagen', "Imagen editada: $titulo (ID: $imagen_id)");
                        }
                    }
                }
            } catch (PDOException $e) {
                $error = "Error en la base de datos: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['toggle_carrusel'])) {
        $imagen_id = $_POST['imagen_id'] ?? 0;
        $nuevo_estado = $_POST['nuevo_estado'] ?? 0;

        if (!is_numeric($imagen_id) || $imagen_id <= 0) {
            $error = "ID de imagen inválido";
        } else {
            try {
                // Verificar si la columna carrusel existe, si no, agregarla
                $stmt = $pdo->prepare("SHOW COLUMNS FROM galeria LIKE 'carrusel'");
                $stmt->execute();
                $columna_existe = $stmt->fetch();

                if (!$columna_existe) {
                    $pdo->exec("ALTER TABLE galeria ADD COLUMN carrusel TINYINT(1) DEFAULT 0");
                }

                $stmt = $pdo->prepare("UPDATE galeria SET carrusel = ? WHERE id = ?");
                if ($stmt->execute([$nuevo_estado, $imagen_id])) {
                    $estado_texto = $nuevo_estado ? 'agregada al carrusel' : 'removida del carrusel';
                    $message = "Imagen $estado_texto correctamente";
                } else {
                    $error = "Error al cambiar el estado del carrusel";
                }
            } catch (PDOException $e) {
                $error = "Error en la base de datos: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['eliminar_imagen'])) {
        $imagen_id = $_POST['imagen_id'] ?? 0;

        if (!is_numeric($imagen_id) || $imagen_id <= 0) {
            $error = "ID de imagen inválido";
        } else {
            try {
                // Obtener información de la imagen
                $stmt = $pdo->prepare("SELECT titulo, imagen FROM galeria WHERE id = ?");
                $stmt->execute([$imagen_id]);
                $imagen = $stmt->fetch();

                if (!$imagen) {
                    $error = "Imagen no encontrada";
                } else {
                    // Eliminar imagen física
                    if (!empty($imagen['imagen']) && file_exists("../uploads/galeria/" . $imagen['imagen'])) {
                        unlink("../uploads/galeria/" . $imagen['imagen']);
                    }

                    // Eliminar registro de la base de datos
                    $stmt = $pdo->prepare("DELETE FROM galeria WHERE id = ?");
                    if ($stmt->execute([$imagen_id])) {
                        $message = "Imagen eliminada correctamente";
                        // Registrar actividad si la función existe
                        if (function_exists('logActivity')) {
                            $user_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0;
                            if ($user_id) {
                                logActivity($user_id, 'eliminar_imagen', "Imagen eliminada: " . $imagen['titulo']);
                            }
                        }
                    } else {
                        $error = "Error al eliminar la imagen";
                    }
                }
            } catch (PDOException $e) {
                $error = "Error en la base de datos: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['toggle_imagen'])) {
        $imagen_id = $_POST['imagen_id'] ?? 0;
        $nuevo_estado = $_POST['nuevo_estado'] ?? 0;

        if (!is_numeric($imagen_id) || $imagen_id <= 0) {
            $error = "ID de imagen inválido";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE galeria SET activo = ? WHERE id = ?");
                if ($stmt->execute([$nuevo_estado, $imagen_id])) {
                    $estado_texto = $nuevo_estado ? 'activada' : 'desactivada';
                    $message = "Imagen $estado_texto correctamente";
                } else {
                    $error = "Error al cambiar el estado de la imagen";
                }
            } catch (PDOException $e) {
                $error = "Error en la base de datos: " . $e->getMessage();
            }
        }
    }
}

// Obtener imágenes con filtros
$search = $_GET['search'] ?? '';
$categoria_filter = $_GET['categoria'] ?? '';
$estado_filter = isset($_GET['estado']) ? (int)$_GET['estado'] : '';
$carrusel_filter = isset($_GET['carrusel']) ? (int)$_GET['carrusel'] : '';

$sql = "SELECT * FROM galeria WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (titulo LIKE ? OR descripcion LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($categoria_filter) {
    $sql .= " AND categoria = ?";
    $params[] = $categoria_filter;
}

if ($estado_filter !== '') {
    $sql .= " AND activo = ?";
    $params[] = $estado_filter;
}

if ($carrusel_filter !== '') {
    $sql .= " AND carrusel = ?";
    $params[] = $carrusel_filter;
}

$sql .= " ORDER BY fecha_subida DESC";

// Obtener imágenes
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $imagenes = [];
    error_log("Error al cargar imágenes: " . $e->getMessage());
}

// Obtener categorías únicas
try {
    $stmt = $pdo->query("SELECT DISTINCT categoria FROM galeria WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");
    $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categorias = [];
}

// Obtener estadísticas de imágenes en carrusel
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_carrusel FROM galeria WHERE carrusel = 1 AND activo = 1");
    $stats_carrusel = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_carrusel = $stats_carrusel['total_carrusel'] ?? 0;
} catch (PDOException $e) {
    $total_carrusel = 0;
}

// Manejar GET para edición
$imagen_editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM galeria WHERE id = ?");
        $stmt->execute([$_GET['editar']]);
        $imagen_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error al cargar imagen para editar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Galería - Panel de Control</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos personalizados -->
    <style>
        /* ===== VARIABLES Y ESTILOS BASE ===== */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --sidebar-width: 250px;
            --sidebar-collapsed: 60px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ===== LAYOUT CONTAINER ===== */
        .admin-container {
            display: flex;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* ===== SIDEBAR ===== */

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .content-header {
            margin-bottom: 30px;
            padding-top: 10px;
        }

        .content-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .content-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        /* ===== ALERTAS ===== */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
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

        /* ===== BOTONES ===== */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-edit {
            background-color: #f39c12;
            color: white;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }

        .btn-up,
        .btn-down {
            background-color: #95a5a6;
            color: white;
            padding: 5px 10px;
            min-width: 35px;
        }

        .btn-up:hover,
        .btn-down:hover {
            background-color: #7f8c8d;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        button:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        /* ===== ADMIN ACTIONS ===== */
        .admin-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ===== TABLAS ===== */
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            padding: 20px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .admin-table th,
        .admin-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .admin-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }

        .admin-table tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        /* ===== ESTADOS Y BADGES ===== */
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status.active,
        .status-badge.status-active {
            background-color: #d5edda;
            color: #155724;
        }

        .status.inactive,
        .status-badge.status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-categoria {
            background-color: #e8f4fc;
            color: var(--secondary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
        }

        .badge-carrusel {
            background-color: #f0e6f5;
            color: #9b59b6;
        }

        /* ===== IMÁGENES ===== */
        .image-thumbnail {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            transition: transform 0.3s;
        }

        .image-thumbnail:hover {
            transform: scale(1.05);
        }

        .preview-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 5px;
            border: 2px dashed #ddd;
            padding: 5px;
            display: block;
        }

        .admin-table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }

        /* ===== FORMULARIOS ===== */
        .form-control,
        .form-select {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px;
            width: 100%;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: none;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input[type="number"] {
            width: 100px;
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
            min-height: 100px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ===== MODALES ===== */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
            padding: 20px;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .modal h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -30px -30px 20px -30px;
        }

        .modal-close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #7f8c8d;
            line-height: 1;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        /* ===== ORDER CONTROLS ===== */
        .order-controls {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .order-controls h3 {
            margin-top: 0;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        /* ===== DRAG & DROP SORTABLE ===== */
        .sortable-ghost {
            opacity: 0.4;
            background: #e3f2fd !important;
        }

        .sortable-chosen {
            background: #bbdefb !important;
        }

        .sortable-drag {
            opacity: 1 !important;
            background: #e3f2fd;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .handle {
            cursor: move;
            color: #7f8c8d;
            padding: 0 10px;
        }

        .handle:hover {
            color: #3498db;
        }

        .destino-item {
            transition: all 0.3s ease;
        }

        .destino-item:hover {
            background-color: #f8f9fa;
        }

        .order-number {
            font-weight: bold;
            color: #3498db;
        }

        /* ===== STAT CARDS ===== */
        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .stat-card.total {
            background: linear-gradient(45deg, #3498db, #2980b9);
        }

        .stat-card.carrusel {
            background: linear-gradient(45deg, #9b59b6, #8e44ad);
        }

        .stat-card.activas {
            background: linear-gradient(45deg, #27ae60, #219653);
        }

        .stat-card.inactivas {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }

        /* ===== TOGGLE SWITCH ===== */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: var(--success-color);
        }

        input:checked+.toggle-slider:before {
            transform: translateX(26px);
        }

        /* ===== ANIMACIONES ===== */
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

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s forwards;
        }

        /* ===== PAGINACIÓN ===== */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .pagination .page-link {
            padding: 8px 15px;
            background: #f8f9fa;
            color: var(--secondary-color);
            text-decoration: none;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            font-size: 14px;
        }

        .pagination .page-link:hover {
            background: #e9ecef;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }

        /* ===== MENSAJES TEMPORALES ===== */
        #mensajeTemporal {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 1200px) {
            .admin-table {
                min-width: 800px;
            }
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 75px 15px 15px 15px;
            }

            .hamburger-menu {
                display: flex;
            }

            .sidebar-overlay.active {
                display: block;
            }

            .content-header h1 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px 15px 15px;
            }

            .admin-actions {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .admin-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .order-actions {
                flex-direction: column;
            }

            .order-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                padding: 20px;
                margin: 10% auto;
                width: 95%;
            }

            .modal-buttons {
                flex-direction: column;
            }

            .modal-buttons .btn {
                width: 100%;
                justify-content: center;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
                gap: 5px;
            }

            .action-buttons .btn-sm,
            .action-buttons form {
                width: 100%;
            }

            .action-buttons .btn-sm {
                justify-content: center;
                padding: 8px 12px;
                font-size: 0.85rem;
            }

            .table-container {
                padding: 10px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 0 -10px;
            }

            .admin-table {
                min-width: 700px;
                font-size: 13px;
            }

            .admin-table th,
            .admin-table td {
                padding: 8px 6px;
                white-space: nowrap;
            }

            .content-header h1 {
                font-size: 1.5rem;
                margin-top: 0;
            }

            .content-header p {
                font-size: 0.9rem;
            }

            .hamburger-menu {
                top: 15px;
                left: 15px;
                width: 45px;
                height: 45px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px 10px 10px 10px;
            }

            .table-container {
                padding: 10px;
                border-radius: 8px;
            }

            .admin-table {
                min-width: 500px;
            }

            .admin-table th,
            .admin-table td {
                padding: 8px 5px;
                font-size: 13px;
            }

            .btn {
                padding: 8px 15px;
                font-size: 13px;
            }

            .btn-sm {
                padding: 4px 8px;
                font-size: 11px;
            }

            .modal-content {
                padding: 15px;
                margin: 5% auto;
            }

            .order-controls {
                padding: 15px;
            }

            .form-group input[type="number"] {
                width: 80px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-card i {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 70px 5px 10px 5px;
            }

            .content-header h1 {
                font-size: 1.3rem;
                margin-top: 0;
            }

            .stats-grid {
                gap: 8px;
            }

            .stat-card {
                padding: 12px;
            }

            .stat-card i {
                font-size: 1.8rem;
            }

            .stat-card h3 {
                font-size: 1.6rem;
            }

            .stat-card p {
                font-size: 11px;
            }

            .action-buttons {
                gap: 8px;
            }

            .btn {
                padding: 8px 12px;
                font-size: 12px;
            }

            .table-container {
                margin: 10px 0;
            }

            .admin-table {
                min-width: 650px;
                font-size: 12px;
            }

            .admin-table th,
            .admin-table td {
                padding: 8px 5px;
            }

            .btn-sm {
                padding: 5px 8px;
                font-size: 11px;
            }

            .modal-content {
                padding: 15px;
            }

            .form-group {
                margin-bottom: 12px;
            }

            .form-group label {
                font-size: 12px;
                margin-bottom: 4px;
            }

            .form-control {
                padding: 8px;
                font-size: 12px;
            }

            .modal-buttons {
                gap: 8px;
            }

            .imagen-preview {
                max-height: 150px;
            }
        }

        /* ===== UTILIDADES ===== */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .d-flex {
            display: flex;
        }

        .align-items-center {
            align-items: center;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .flex-wrap {
            flex-wrap: wrap;
        }

        .w-100 {
            width: 100%;
        }

        .mt-2 {
            margin-top: 10px;
        }

        .mt-3 {
            margin-top: 15px;
        }

        .mb-2 {
            margin-bottom: 10px;
        }

        .mb-3 {
            margin-bottom: 15px;
        }

        .p-2 {
            padding: 10px;
        }

        .p-3 {
            padding: 15px;
        }

        /* ===== ESTILOS ESPECÍFICOS PARA GALERÍA ===== */
        .image-thumbnail {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .actions-cell .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        .filter-section {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }

        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Contenido principal -->
        <div class="main-content" id="mainContent">
            <!-- Header -->
            <div class="content-header">
                <h1><i class="fas fa-images text-primary me-2"></i>Administrar Galería</h1>
                <p>Gestiona las imágenes de la galería y el carrusel principal</p>
            </div>

            <!-- Mensajes de alerta -->
            <?php if (isset($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card total">
                        <i class="fas fa-images"></i>
                        <h4><?php echo count($imagenes); ?></h4>
                        <p>Total Imágenes</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card carrusel">
                        <i class="fas fa-sliders-h"></i>
                        <h4><?php echo $total_carrusel; ?></h4>
                        <p>En Carrusel</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card activas">
                        <i class="fas fa-eye"></i>
                        <h4>
                            <?php
                            $activas = array_filter($imagenes, function ($img) {
                                return $img['activo'] == 1;
                            });
                            echo count($activas);
                            ?>
                        </h4>
                        <p>Activas</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card inactivas">
                        <i class="fas fa-eye-slash"></i>
                        <h4>
                            <?php
                            $inactivas = array_filter($imagenes, function ($img) {
                                return $img['activo'] == 0;
                            });
                            echo count($inactivas);
                            ?>
                        </h4>
                        <p>Inactivas</p>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <label for="search" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="search" name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Título o descripción">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select class="form-select" id="categoria" name="categoria">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"
                                    <?php echo $categoria_filter == $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="">Todos</option>
                            <option value="1" <?php echo $estado_filter === 1 ? 'selected' : ''; ?>>Activas</option>
                            <option value="0" <?php echo $estado_filter === 0 ? 'selected' : ''; ?>>Inactivas</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label for="carrusel" class="form-label">Carrusel</label>
                        <select class="form-select" id="carrusel" name="carrusel">
                            <option value="">Todos</option>
                            <option value="1" <?php echo $carrusel_filter === 1 ? 'selected' : ''; ?>>En carrusel</option>
                            <option value="0" <?php echo $carrusel_filter === 0 ? 'selected' : ''; ?>>No en carrusel</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                        <a href="gestion-galeria.php" class="btn btn-secondary">
                            <i class="fas fa-redo me-1"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <div class="row">
                <!-- Formulario para subir/editar imagen -->
                <div class="col-lg-4 col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-upload me-2"></i>
                            <?php echo $imagen_editar ? 'Editar Imagen' : 'Subir Nueva Imagen'; ?>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <?php if ($imagen_editar): ?>
                                    <input type="hidden" name="imagen_id" value="<?php echo $imagen_editar['id']; ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="titulo" class="form-label">Título *</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo"
                                        value="<?php echo $imagen_editar ? htmlspecialchars($imagen_editar['titulo']) : ''; ?>"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion"
                                        rows="3"><?php echo $imagen_editar ? htmlspecialchars($imagen_editar['descripcion']) : ''; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="categoria" class="form-label">Categoría</label>
                                    <input type="text" class="form-control" id="categoria" name="categoria"
                                        value="<?php echo $imagen_editar ? htmlspecialchars($imagen_editar['categoria']) : 'general'; ?>">
                                    <small class="text-muted">Usa categorías para organizar tus imágenes</small>
                                </div>

                                <div class="mb-3">
                                    <label for="imagen" class="form-label">
                                        Imagen *
                                        <small class="text-muted">(JPG, PNG, GIF, WEBP - Máx 5MB)</small>
                                    </label>
                                    <input type="file" class="form-control" id="imagen" name="imagen"
                                        accept="image/*" <?php echo !$imagen_editar ? 'required' : ''; ?>>

                                    <?php if ($imagen_editar && !empty($imagen_editar['imagen'])): ?>
                                        <div class="mt-2">
                                            <p class="mb-1">Imagen actual:</p>
                                            <img src="../uploads/galeria/<?php echo htmlspecialchars($imagen_editar['imagen']); ?>"
                                                alt="Imagen actual" class="preview-image">
                                        </div>
                                    <?php endif; ?>

                                    <div id="imagePreview" class="mt-2"></div>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="carrusel" name="carrusel"
                                        value="1" <?php echo ($imagen_editar && $imagen_editar['carrusel'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="carrusel">
                                        Mostrar en carrusel principal
                                    </label>
                                    <small class="text-muted d-block">Esta imagen aparecerá en el carrusel de la página principal</small>
                                </div>

                                <?php if ($imagen_editar): ?>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="activo" name="activo"
                                            value="1" <?php echo ($imagen_editar && $imagen_editar['activo'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="activo">Activa</label>
                                        <small class="text-muted d-block">Las imágenes inactivas no se mostrarán en el sitio</small>
                                    </div>
                                <?php endif; ?>

                                <div class="d-grid gap-2">
                                    <?php if ($imagen_editar): ?>
                                        <button type="submit" name="editar_imagen" class="btn btn-warning">
                                            <i class="fas fa-save me-1"></i> Actualizar Imagen
                                        </button>
                                        <a href="gestion-galeria.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-1"></i> Cancelar
                                        </a>
                                    <?php else: ?>
                                        <button type="submit" name="subir_imagen" class="btn btn-success">
                                            <i class="fas fa-upload me-1"></i> Subir Imagen
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Información -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle me-2"></i> Información
                        </div>
                        <div class="card-body">
                            <h6>Instrucciones:</h6>
                            <ul class="small">
                                <li>Las imágenes se almacenan en <code>/uploads/galeria/</code></li>
                                <li>Formatos permitidos: JPG, PNG, GIF, WEBP</li>
                                <li>Tamaño máximo recomendado: 5MB por imagen</li>
                                <li>Usa categorías para organizar mejor tu galería</li>
                                <li>El carrusel muestra hasta 10 imágenes aleatorias</li>
                                <li>Las imágenes inactivas no se muestran en el sitio</li>
                            </ul>

                            <h6 class="mt-3">Estadísticas:</h6>
                            <div class="small">
                                <p><i class="fas fa-images text-primary me-1"></i> Total: <?php echo count($imagenes); ?> imágenes</p>
                                <p><i class="fas fa-sliders-h text-purple me-1"></i> Carrusel: <?php echo $total_carrusel; ?> imágenes</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de imágenes -->
                <div class="col-lg-8 col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-list me-2"></i>Lista de Imágenes
                                <span class="badge bg-secondary ms-2"><?php echo count($imagenes); ?></span>
                            </div>
                            <div>
                                <?php if ($imagen_editar): ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-edit me-1"></i> Editando
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($imagenes)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-images fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No hay imágenes</h5>
                                    <p class="text-muted">Sube tu primera imagen usando el formulario</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th width="100">Imagen</th>
                                                <th>Título</th>
                                                <th>Categoría</th>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                                <th>Carrusel</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($imagenes as $imagen): ?>
                                                <tr>
                                                    <td>
                                                        <img src="../uploads/galeria/<?php echo htmlspecialchars($imagen['imagen']); ?>"
                                                            alt="<?php echo htmlspecialchars($imagen['titulo']); ?>"
                                                            class="image-thumbnail">
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($imagen['titulo']); ?></strong>
                                                        <?php if (!empty($imagen['descripcion'])): ?>
                                                            <p class="small text-muted mb-0"><?php echo substr(htmlspecialchars($imagen['descripcion']), 0, 50); ?>...</p>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge-categoria"><?php echo htmlspecialchars($imagen['categoria'] ?? 'General'); ?></span>
                                                    </td>
                                                    <td>
                                                        <small><?php echo formatDate($imagen['fecha_subida'] ?? $imagen['created_at'] ?? ''); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if ($imagen['activo'] == 1): ?>
                                                            <span class="status-badge status-active">
                                                                <i class="fas fa-check-circle me-1"></i> Activa
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="status-badge status-inactive">
                                                                <i class="fas fa-times-circle me-1"></i> Inactiva
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($imagen['carrusel']) && $imagen['carrusel'] == 1): ?>
                                                            <span class="badge-categoria badge-carrusel">
                                                                <i class="fas fa-sliders-h me-1"></i> Sí
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="actions-cell">
                                                        <div class="action-buttons">
                                                            <!-- Editar -->
                                                            <a href="?editar=<?php echo $imagen['id']; ?>"
                                                                class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>

                                                            <!-- Toggle Estado -->
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="imagen_id" value="<?php echo $imagen['id']; ?>">
                                                                <input type="hidden" name="nuevo_estado" value="<?php echo $imagen['activo'] ? 0 : 1; ?>">
                                                                <button type="submit" name="toggle_imagen"
                                                                    class="btn btn-sm <?php echo $imagen['activo'] ? 'btn-secondary' : 'btn-success'; ?>">
                                                                    <i class="fas fa-power-off"></i>
                                                                </button>
                                                            </form>

                                                            <!-- Toggle Carrusel -->
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="imagen_id" value="<?php echo $imagen['id']; ?>">
                                                                <input type="hidden" name="nuevo_estado" value="<?php echo $imagen['carrusel'] ? 0 : 1; ?>">
                                                                <button type="submit" name="toggle_carrusel"
                                                                    class="btn btn-sm <?php echo $imagen['carrusel'] ? 'btn-info' : 'btn-primary'; ?>">
                                                                    <i class="fas fa-sliders-h"></i>
                                                                </button>
                                                            </form>

                                                            <!-- Eliminar -->
                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                onclick="confirmarEliminacion(<?php echo $imagen['id']; ?>, '<?php echo htmlspecialchars(addslashes($imagen['titulo'])); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($imagenes)): ?>
                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Mostrando <?php echo count($imagenes); ?> imágenes
                                </small>
                                <div>
                                    <?php if (isset($_GET['search']) || isset($_GET['categoria']) || isset($_GET['estado']) || isset($_GET['carrusel'])): ?>
                                        <a href="gestion-galeria.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-redo me-1"></i> Mostrar todas
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Consejos -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <i class="fas fa-lightbulb me-2"></i> Consejos para Imágenes
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-compress text-primary me-2"></i> Optimización</h6>
                                    <ul class="small">
                                        <li>Comprime imágenes antes de subirlas</li>
                                        <li>Usa formato WEBP para mejor compresión</li>
                                        <li>Mantén dimensiones consistentes</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-chart-line text-success me-2"></i> Mejores Prácticas</h6>
                                    <ul class="small">
                                        <li>Usa nombres descriptivos para las imágenes</li>
                                        <li>Organiza por categorías temáticas</li>
                                        <li>Mantén el carrusel actualizado</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div id="modalConfirmarEliminar" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="cerrarModal('modalConfirmarEliminar')">&times;</span>
            <h2>Confirmar Eliminación</h2>
            <p id="mensajeConfirmacion">¿Está seguro de que desea eliminar esta imagen?</p>
            <form method="POST" id="formEliminarImagen">
                <input type="hidden" name="eliminar_imagen" value="1">
                <input type="hidden" id="id_eliminar" name="imagen_id" value="">

                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalConfirmarEliminar')">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar Imagen</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (opcional, si necesitas AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Preview de imagen antes de subir
        document.getElementById('imagen')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.innerHTML = `
                        <p class="mb-1">Vista previa:</p>
                        <img src="${e.target.result}" class="preview-image" alt="Vista previa">
                    `;
                }
                reader.readAsDataURL(file);
            }
        });

        // Funciones para modales
        function abrirModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function confirmarEliminacion(id, nombre) {
            document.getElementById('id_eliminar').value = id;
            document.getElementById('mensajeConfirmacion').innerHTML =
                '¿Está seguro de que desea eliminar la imagen: <strong>' + nombre + '</strong>?<br><small>Esta acción no se puede deshacer.</small>';
            abrirModal('modalConfirmarEliminar');
        }

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Auto-close alerts después de 5 segundos
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);

        // Filtro rápido con JavaScript
        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const title = row.querySelector('td:nth-child(2) strong')?.textContent.toLowerCase() || '';
                    const description = row.querySelector('td:nth-child(2) .small')?.textContent.toLowerCase() || '';
                    if (title.includes(searchTerm) || description.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>

</html>