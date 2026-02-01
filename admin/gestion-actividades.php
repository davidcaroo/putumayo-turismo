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

// Función auxiliar si getDestinos no existe
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

// Función para procesar imágenes de actividades
function procesarImagenActividad($archivo)
{
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Validar tipo de archivo
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $tipo = mime_content_type($archivo['tmp_name']);

    if (!in_array($tipo, $tipos_permitidos)) {
        return null;
    }

    // Validar tamaño (máx 5MB)
    if ($archivo['size'] > 5 * 1024 * 1024) {
        return null;
    }

    // Generar nombre único
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_unico = 'actividad_' . uniqid() . '.' . $extension;
    $destino = '../uploads/actividades/' . $nombre_unico;

    // Crear directorio si no existe
    if (!file_exists('../uploads/actividades/')) {
        mkdir('../uploads/actividades/', 0777, true);
    }

    // Mover archivo
    if (move_uploaded_file($archivo['tmp_name'], $destino)) {
        return $nombre_unico;
    }

    return null;
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agregar_actividad'])) {
        $destino_id = $_POST['destino_id'] ?? 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = $_POST['precio'] ?? 0;
        $duracion = trim($_POST['duracion'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 1; // Por defecto activa

        // Validación básica
        if (empty($nombre) || empty($destino_id)) {
            $error = "Nombre y destino son obligatorios";
        } else {
            // Procesar imagen si se subió
            $imagen_nombre = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $imagen_nombre = procesarImagenActividad($_FILES['imagen']);
                if (empty($imagen_nombre)) {
                    $error = "Error al subir la imagen. Asegúrate de que sea una imagen válida (JPG, PNG, GIF, WEBP) y no exceda 5MB.";
                }
            }

            if (!isset($error)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO actividades (destino_id, nombre, descripcion, imagen, precio, duracion, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$destino_id, $nombre, $descripcion, $imagen_nombre, $precio, $duracion, $activo])) {
                        $message = "Actividad agregada correctamente";
                        if (isset($_SESSION['usuario_id'])) {
                            registrarActividad($_SESSION['usuario_id'], 'agregar_actividad', "Nueva actividad: $nombre");
                        }
                    } else {
                        $error = "Error al agregar la actividad";
                    }
                } catch (PDOException $e) {
                    $error = "Error en la base de datos: " . $e->getMessage();
                }
            }
        }
    } elseif (isset($_POST['editar_actividad'])) {
        $actividad_id = $_POST['actividad_id'] ?? 0;
        $destino_id = $_POST['destino_id'] ?? 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = $_POST['precio'] ?? 0;
        $duracion = trim($_POST['duracion'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;

        if (empty($nombre) || empty($destino_id) || empty($actividad_id)) {
            $error = "Datos incompletos";
        } else {
            // Obtener imagen actual
            $imagen_nombre = $_POST['imagen_actual'] ?? null;

            // Procesar nueva imagen si se subió
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $nueva_imagen = procesarImagenActividad($_FILES['imagen']);
                if (!empty($nueva_imagen)) {
                    // Eliminar imagen anterior si existe
                    if ($imagen_nombre && file_exists("../uploads/actividades/" . $imagen_nombre)) {
                        unlink("../uploads/actividades/" . $imagen_nombre);
                    }
                    $imagen_nombre = $nueva_imagen;
                } else {
                    $error = "Error al subir la nueva imagen";
                }
            }

            if (!isset($error)) {
                try {
                    $stmt = $pdo->prepare("UPDATE actividades SET destino_id = ?, nombre = ?, descripcion = ?, imagen = ?, precio = ?, duracion = ?, activo = ? WHERE id = ?");
                    if ($stmt->execute([$destino_id, $nombre, $descripcion, $imagen_nombre, $precio, $duracion, $activo, $actividad_id])) {
                        $message = "Actividad actualizada correctamente";
                        if (isset($_SESSION['usuario_id'])) {
                            registrarActividad($_SESSION['usuario_id'], 'editar_actividad', "Actividad actualizada: $nombre (ID: $actividad_id)");
                        }
                    } else {
                        $error = "Error al actualizar la actividad";
                    }
                } catch (PDOException $e) {
                    $error = "Error en la base de datos: " . $e->getMessage();
                }
            }
        }
    } elseif (isset($_POST['eliminar_actividad'])) {
        $actividad_id = $_POST['actividad_id'] ?? 0;

        if ($actividad_id) {
            try {
                // Obtener información de la actividad para el log
                $stmt = $pdo->prepare("SELECT nombre, imagen FROM actividades WHERE id = ?");
                $stmt->execute([$actividad_id]);
                $actividad = $stmt->fetch();

                if ($actividad) {
                    // Eliminar imagen si existe
                    if (!empty($actividad['imagen']) && file_exists("../uploads/actividades/" . $actividad['imagen'])) {
                        unlink("../uploads/actividades/" . $actividad['imagen']);
                    }

                    // Eliminar actividad
                    $stmt = $pdo->prepare("DELETE FROM actividades WHERE id = ?");
                    if ($stmt->execute([$actividad_id])) {
                        $message = "Actividad eliminada correctamente";
                        if (isset($_SESSION['usuario_id'])) {
                            registrarActividad($_SESSION['usuario_id'], 'eliminar_actividad', "Actividad eliminada: " . $actividad['nombre']);
                        }
                    } else {
                        $error = "Error al eliminar la actividad";
                    }
                } else {
                    $error = "Actividad no encontrada";
                }
            } catch (PDOException $e) {
                $error = "Error en la base de datos: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['toggle_actividad'])) {
        $actividad_id = $_POST['actividad_id'] ?? 0;
        $nuevo_estado = $_POST['nuevo_estado'] ?? 0;

        if ($actividad_id) {
            try {
                $stmt = $pdo->prepare("UPDATE actividades SET activo = ? WHERE id = ?");
                if ($stmt->execute([$nuevo_estado, $actividad_id])) {
                    // Obtener nombre para el log
                    $stmt = $pdo->prepare("SELECT nombre FROM actividades WHERE id = ?");
                    $stmt->execute([$actividad_id]);
                    $actividad = $stmt->fetch();

                    $estado_texto = $nuevo_estado ? 'activada' : 'desactivada';
                    $message = "Actividad $estado_texto correctamente";

                    if (isset($_SESSION['usuario_id'])) {
                        registrarActividad($_SESSION['usuario_id'], 'toggle_actividad', "Actividad $estado_texto: " . ($actividad['nombre'] ?? 'ID: ' . $actividad_id));
                    }
                } else {
                    $error = "Error al cambiar el estado de la actividad";
                }
            } catch (PDOException $e) {
                $error = "Error en la base de datos: " . $e->getMessage();
            }
        }
    }
}

// Obtener actividades con información del destino
try {
    $stmt = $pdo->query("
        SELECT a.*, d.nombre as destino_nombre 
        FROM actividades a 
        LEFT JOIN destinos d ON a.destino_id = d.id 
        ORDER BY a.destino_id, a.nombre
    ");
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $actividades = [];
    error_log("Error al cargar actividades: " . $e->getMessage());
    $error = "Error al cargar actividades. Verifica la conexión a la base de datos.";
}

// Obtener destinos para los formularios
$destinos = getDestinos();

// Manejar GET para edición
$actividad_editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, d.nombre as destino_nombre 
            FROM actividades a 
            LEFT JOIN destinos d ON a.destino_id = d.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$_GET['editar']]);
        $actividad_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al cargar actividad para editar: " . $e->getMessage());
        $error = "Error al cargar actividad para editar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Actividades - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f7fa;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Main content */
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 280px;
        }

        .content-header {
            margin-bottom: 30px;
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

        /* Alertas */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Botones */
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
            background-color: #3498db;
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

        .admin-actions {
            margin-bottom: 2rem;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .admin-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .admin-table tr:hover {
            background: #f8f9fa;
        }

        .status {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status.active {
            background: #d4edda;
            color: #155724;
        }

        .status.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 0.3rem;
            flex-wrap: wrap;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2E8B57;
            box-shadow: 0 0 0 2px rgba(46, 139, 87, 0.2);
        }

        .form-group small {
            display: block;
            margin-top: 0.3rem;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            color: #2c3e50;
            margin: 0;
        }

        .close-modal {
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }

        .close-modal:hover {
            color: #333;
        }

        .stats-grid {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 1.8rem;
            color: #2c3e50;
        }

        .stat-info p {
            margin: 0;
            color: #7f8c8d;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 75px 10px 15px 10px;
            }

            .content-header h1 {
                font-size: 1.5rem;
                margin-top: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .admin-table {
                display: block;
                overflow-x: auto;
                min-width: 800px;
            }

            .modal-dialog {
                width: 95%;
                max-height: 90vh;
                overflow-y: auto;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 70px 5px 10px 5px;
            }

            .content-header h1 {
                font-size: 1.3rem;
            }

            .content-header p {
                font-size: 12px;
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

            .admin-table {
                min-width: 750px;
                font-size: 12px;
            }

            .admin-table th,
            .admin-table td {
                padding: 8px 5px;
            }

            .btn {
                padding: 8px 12px;
                font-size: 12px;
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

            .form-control {
                padding: 8px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="content-header">
                <h1>Gestión de Actividades</h1>
                <p>Administra las actividades disponibles para cada destino</p>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="admin-actions">
                <button class="btn btn-primary" onclick="abrirModal('modalAgregarActividad')">
                    <i class="fas fa-plus"></i> Agregar Actividad
                </button>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">
                        <i class="fas fa-hiking"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($actividades); ?></h3>
                        <p>Total Actividades</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196F3;">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($destinos); ?></h3>
                        <p>Destinos Disponibles</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #FF9800;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $actividades_activas = 0;
                        if (!empty($actividades)) {
                            $actividades_activas = count(array_filter($actividades, function ($a) {
                                return isset($a['activo']) && $a['activo'] == 1;
                            }));
                        }
                        ?>
                        <h3><?php echo $actividades_activas; ?></h3>
                        <p>Actividades Activas</p>
                    </div>
                </div>
            </div>

            <!-- Tabla de actividades -->
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Destino</th>
                            <th>Precio</th>
                            <th>Duración</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($actividades)): ?>
                            <?php foreach ($actividades as $actividad): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($actividad['id']); ?></td>
                                    <td>
                                        <?php if (!empty($actividad['imagen'])): ?>
                                            <img src="../uploads/actividades/<?php echo htmlspecialchars($actividad['imagen']); ?>"
                                                alt="<?php echo htmlspecialchars($actividad['nombre']); ?>"
                                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #ccc;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($actividad['nombre']); ?></strong>
                                        <?php if (!empty($actividad['descripcion'])): ?>
                                            <br><small style="opacity: 0.7;"><?php echo substr(htmlspecialchars($actividad['descripcion']), 0, 50) . '...'; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($actividad['destino_nombre'] ?? 'Sin destino'); ?></td>
                                    <td>
                                        <?php if (!empty($actividad['precio'])): ?>
                                            $<?php echo number_format($actividad['precio'], 0, ',', '.'); ?>
                                        <?php else: ?>
                                            <span style="opacity: 0.5;">No definido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($actividad['duracion'] ?: 'No definida'); ?></td>
                                    <td>
                                        <span class="status <?php echo $actividad['activo'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $actividad['activo'] ? 'Activa' : 'Inactiva'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-info"
                                                onclick="editarActividad(<?php echo $actividad['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="actividad_id" value="<?php echo $actividad['id']; ?>">
                                                <input type="hidden" name="nuevo_estado" value="<?php echo $actividad['activo'] ? 0 : 1; ?>">
                                                <input type="hidden" name="toggle_actividad" value="1">
                                                <button type="submit" class="btn btn-sm <?php echo $actividad['activo'] ? 'btn-warning' : 'btn-success'; ?>"
                                                    onclick="return confirm('¿<?php echo $actividad['activo'] ? 'Desactivar' : 'Activar'; ?> esta actividad?')">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </form>

                                            <button class="btn btn-sm btn-danger"
                                                onclick="confirmarEliminacion(<?php echo $actividad['id']; ?>, '<?php echo htmlspecialchars(addslashes($actividad['nombre'])); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">
                                    <i class="fas fa-hiking" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                    <p>No hay actividades registradas</p>
                                    <button class="btn btn-primary" onclick="abrirModal('modalAgregarActividad')">
                                        <i class="fas fa-plus"></i> Agregar Primera Actividad
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para agregar actividad -->
    <div id="modalAgregarActividad" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Agregar Nueva Actividad</h3>
                <span class="close-modal" onclick="cerrarModal('modalAgregarActividad')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="formAgregarActividad">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="destino_id">Destino *</label>
                            <select id="destino_id" name="destino_id" required>
                                <option value="">Selecciona un destino</option>
                                <?php foreach ($destinos as $destino): ?>
                                    <option value="<?php echo $destino['id']; ?>">
                                        <?php echo htmlspecialchars($destino['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="nombre">Nombre de la Actividad *</label>
                            <input type="text" id="nombre" name="nombre" required
                                placeholder="Ej: Tour Cultural Sibundoy">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3"
                            placeholder="Describe la actividad..."></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="precio">Precio ($)</label>
                            <input type="number" id="precio" name="precio" min="0" step="1000"
                                placeholder="Ej: 50000">
                        </div>

                        <div class="form-group">
                            <label for="duracion">Duración</label>
                            <input type="text" id="duracion" name="duracion"
                                placeholder="Ej: 4 horas">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="imagen">Imagen de la Actividad</label>
                        <input type="file" id="imagen" name="imagen" accept="image/*">
                        <small>Formatos: JPG, PNG, GIF, WEBP (Máx. 5MB)</small>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="activo" value="1" checked>
                            Actividad activa
                        </label>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary"
                            onclick="cerrarModal('modalAgregarActividad')">Cancelar</button>
                        <button type="submit" name="agregar_actividad" class="btn btn-primary">
                            Agregar Actividad
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar actividad -->
    <div id="modalEditarActividad" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Actividad</h3>
                <span class="close-modal" onclick="cerrarModal('modalEditarActividad')">&times;</span>
            </div>
            <div class="modal-body">
                <?php if ($actividad_editar): ?>
                    <form method="POST" enctype="multipart/form-data" id="formEditarActividad">
                        <input type="hidden" name="actividad_id" value="<?php echo htmlspecialchars($actividad_editar['id']); ?>">
                        <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($actividad_editar['imagen'] ?? ''); ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="edit_destino_id">Destino *</label>
                                <select id="edit_destino_id" name="destino_id" required>
                                    <option value="">Selecciona un destino</option>
                                    <?php foreach ($destinos as $destino): ?>
                                        <option value="<?php echo $destino['id']; ?>"
                                            <?php echo ($destino['id'] == $actividad_editar['destino_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($destino['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="edit_nombre">Nombre de la Actividad *</label>
                                <input type="text" id="edit_nombre" name="nombre" required
                                    value="<?php echo htmlspecialchars($actividad_editar['nombre']); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit_descripcion">Descripción</label>
                            <textarea id="edit_descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($actividad_editar['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="edit_precio">Precio ($)</label>
                                <input type="number" id="edit_precio" name="precio" min="0" step="1000"
                                    value="<?php echo htmlspecialchars($actividad_editar['precio'] ?? 0); ?>">
                            </div>

                            <div class="form-group">
                                <label for="edit_duracion">Duración</label>
                                <input type="text" id="edit_duracion" name="duracion"
                                    value="<?php echo htmlspecialchars($actividad_editar['duracion'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit_imagen">Imagen de la Actividad</label>
                            <?php if (!empty($actividad_editar['imagen'])): ?>
                                <div style="margin-bottom: 10px;">
                                    <img src="../uploads/actividades/<?php echo htmlspecialchars($actividad_editar['imagen']); ?>"
                                        alt="Imagen actual"
                                        style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 5px;">
                                    <br><small>Imagen actual</small>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="edit_imagen" name="imagen" accept="image/*">
                            <small>Deja vacío para mantener la imagen actual</small>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="activo" value="1"
                                    <?php echo ($actividad_editar['activo'] ?? 0) ? 'checked' : ''; ?>>
                                Actividad activa
                            </label>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary"
                                onclick="cerrarModal('modalEditarActividad')">Cancelar</button>
                            <button type="submit" name="editar_actividad" class="btn btn-primary">
                                Actualizar Actividad
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <p style="text-align: center; padding: 2rem; color: #666;">Cargando información de la actividad...</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Formulario oculto para eliminar -->
    <form method="POST" id="formEliminarActividad" style="display: none;">
        <input type="hidden" name="actividad_id" id="eliminar_actividad_id">
        <input type="hidden" name="eliminar_actividad" value="1">
    </form>

    <script>
        // Funciones para modales
        function abrirModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Si es el modal de edición, redirigir sin parámetros
            if (modalId === 'modalEditarActividad') {
                window.location.href = 'gestion-actividades.php';
            }
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                // Si es el modal de edición, redirigir sin parámetros
                if (event.target.id === 'modalEditarActividad') {
                    window.location.href = 'gestion-actividades.php';
                }
            }
        }

        // Editar actividad
        function editarActividad(actividadId) {
            window.location.href = 'gestion-actividades.php?editar=' + actividadId;
        }

        // Confirmar eliminación
        function confirmarEliminacion(actividadId, actividadNombre) {
            if (confirm(`¿Estás seguro de que deseas eliminar la actividad "${actividadNombre}"?\nEsta acción no se puede deshacer.`)) {
                document.getElementById('eliminar_actividad_id').value = actividadId;
                document.getElementById('formEliminarActividad').submit();
            }
        }

        // Verificar si hay parámetro de edición en la URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('editar')) {
                abrirModal('modalEditarActividad');
            }

            // Agregar animaciones a las filas
            const tableRows = document.querySelectorAll('.admin-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.05}s`;
                row.style.opacity = '0';
                row.style.transform = 'translateY(10px)';
                row.style.animation = 'fadeInRow 0.5s forwards';
            });

            // Agregar animación CSS
            const style = document.createElement('style');
            style.textContent = `
            @keyframes fadeInRow {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
            document.head.appendChild(style);
        });
    </script>
</body>

</html>