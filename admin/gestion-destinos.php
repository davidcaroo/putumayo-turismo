<?php
// Incluir archivos de configuración
include '../includes/config.php';
include_once '../includes/functions.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || (!hasRole('superadmin') && !hasRole('admin'))) {
    header('Location: ../login.php');
    exit;
}

// Verificar si existe la variable $pdo
if (!isset($pdo)) {
    die("Error: No se pudo conectar a la base de datos. Verifica config.php");
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agregar_destino'])) {
        // Lógica para agregar destino
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $ubicacion = $_POST['ubicacion'] ?? '';
        $activo = isset($_POST['activo']) ? 1 : 0;

        // Obtener el último orden para colocarlo al final
        try {
            $stmt = $pdo->query("SELECT MAX(orden) as max_orden FROM destinos");
            $result = $stmt->fetch();
            $orden = ($result['max_orden'] ?? 0) + 1;
        } catch (PDOException $e) {
            $orden = 1;
        }

        // Manejo de la imagen con validación mejorada
        $imagen_nombre = '';
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
            $upload_result = subirImagen($_FILES['imagen_principal'], '../uploads/destinos/');

            // Validar que subirImagen retorne un array y no el string "Array"
            if (is_array($upload_result) && isset($upload_result['success'])) {
                if ($upload_result['success']) {
                    $imagen_nombre = $upload_result['filename'];
                } else {
                    $error = "Error al subir imagen: " . ($upload_result['error'] ?? 'Error desconocido');
                }
            } else {
                // Prevenir que se guarde "Array" en la BD
                $error = "Error crítico: La función de subida de imagen no retornó un resultado válido";
            }
        }

        if ($nombre && !isset($error)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO destinos (nombre, descripcion, ubicacion, imagen_principal, activo, orden) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$nombre, $descripcion, $ubicacion, $imagen_nombre, $activo, $orden])) {
                    // Registrar actividad
                    if (isset($_SESSION['usuario_id'])) {
                        registrarActividad($_SESSION['usuario_id'], "Destino creado: $nombre");
                    }
                    header('Location: gestion-destinos.php?success=destino_creado');
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Error al crear destino: " . $e->getMessage();
            }
        } else {
            $error = "El nombre del destino es obligatorio";
        }
    } elseif (isset($_POST['editar_destino'])) {
        // Lógica para editar destino
        $id = $_POST['id'] ?? 0;
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $ubicacion = $_POST['ubicacion'] ?? '';
        $activo = isset($_POST['activo']) ? 1 : 0;
        $orden = $_POST['orden'] ?? 0;

        if ($id && $nombre) {
            try {
                // Obtener destino actual para conservar imagen si no se cambia
                $stmt = $pdo->prepare("SELECT imagen_principal FROM destinos WHERE id = ?");
                $stmt->execute([$id]);
                $destino_actual = $stmt->fetch();
                $imagen_nombre = $destino_actual['imagen_principal'] ?? '';

                // Manejo de nueva imagen con validación mejorada
                if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
                    // Subir nueva imagen
                    $upload_result = subirImagen($_FILES['imagen_principal'], '../uploads/destinos/');

                    // Validar que subirImagen retorne un array válido
                    if (is_array($upload_result) && isset($upload_result['success'])) {
                        if ($upload_result['success']) {
                            // Eliminar imagen anterior si existe y la nueva se subió correctamente
                            if ($imagen_nombre && file_exists("../uploads/destinos/$imagen_nombre")) {
                                unlink("../uploads/destinos/$imagen_nombre");
                            }
                            $imagen_nombre = $upload_result['filename'];
                        } else {
                            $error = "Error al subir nueva imagen: " . ($upload_result['error'] ?? 'Error desconocido');
                        }
                    } else {
                        // Prevenir que se guarde "Array" en la BD
                        $error = "Error crítico: La función de subida de imagen no retornó un resultado válido";
                    }
                }

                // Solo ejecutar UPDATE si no hay errores previos
                if (!isset($error)) {
                    $stmt = $pdo->prepare("UPDATE destinos SET nombre = ?, descripcion = ?, ubicacion = ?, imagen_principal = ?, activo = ?, orden = ? WHERE id = ?");
                    if ($stmt->execute([$nombre, $descripcion, $ubicacion, $imagen_nombre, $activo, $orden, $id])) {
                        // Registrar actividad
                        if (isset($_SESSION['usuario_id'])) {
                            registrarActividad($_SESSION['usuario_id'], "Destino editado: $nombre (ID: $id)");
                        }
                        header('Location: gestion-destinos.php?success=destino_editado');
                        exit;
                    }
                }
            } catch (PDOException $e) {
                $error = "Error al editar destino: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['eliminar_destino'])) {
        // Lógica para eliminar destino
        $id = $_POST['id'] ?? 0;

        if ($id) {
            try {
                // Verificar si tiene actividades asociadas
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades WHERE destino_id = ?");
                $stmt->execute([$id]);
                $result = $stmt->fetch();

                if ($result['count'] > 0) {
                    header('Location: gestion-destinos.php?error=destino_con_actividades');
                    exit;
                }

                // Obtener información del destino para registrar actividad
                $stmt = $pdo->prepare("SELECT nombre, imagen_principal FROM destinos WHERE id = ?");
                $stmt->execute([$id]);
                $destino = $stmt->fetch();

                // Eliminar imágenes asociadas de la tabla destino_imagenes
                $stmt = $pdo->prepare("SELECT imagen FROM destino_imagenes WHERE destino_id = ?");
                $stmt->execute([$id]);
                $imagenes = $stmt->fetchAll();

                foreach ($imagenes as $imagen) {
                    $ruta_imagen = "../uploads/destinos/" . $imagen['imagen'];
                    if (file_exists($ruta_imagen) && is_file($ruta_imagen)) {
                        unlink($ruta_imagen);
                    }
                }

                // Eliminar registros de destino_imagenes
                $stmt = $pdo->prepare("DELETE FROM destino_imagenes WHERE destino_id = ?");
                $stmt->execute([$id]);

                // Eliminar imagen principal
                if (!empty($destino['imagen_principal'])) {
                    $ruta_imagen_principal = "../uploads/destinos/" . $destino['imagen_principal'];
                    if (file_exists($ruta_imagen_principal) && is_file($ruta_imagen_principal)) {
                        unlink($ruta_imagen_principal);
                    }
                }

                // Eliminar destino
                $stmt = $pdo->prepare("DELETE FROM destinos WHERE id = ?");
                if ($stmt->execute([$id])) {
                    // Reordenar los destinos restantes
                    $stmt = $pdo->query("SET @count = 0");
                    $stmt = $pdo->query("UPDATE destinos SET orden = (@count := @count + 1) ORDER BY orden");

                    // Registrar actividad
                    if (isset($_SESSION['usuario_id'])) {
                        registrarActividad($_SESSION['usuario_id'], "Destino eliminado: {$destino['nombre']} (ID: $id)");
                    }
                    header('Location: gestion-destinos.php?success=destino_eliminado');
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Error al eliminar destino: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['actualizar_orden'])) {
        // Lógica para actualizar el orden de los destinos
        if (isset($_POST['orden']) && is_array($_POST['orden'])) {
            try {
                $pdo->beginTransaction();

                foreach ($_POST['orden'] as $id => $nuevo_orden) {
                    if (is_numeric($id) && is_numeric($nuevo_orden)) {
                        $stmt = $pdo->prepare("UPDATE destinos SET orden = ? WHERE id = ?");
                        $stmt->execute([$nuevo_orden, $id]);
                    }
                }

                $pdo->commit();
                // Registrar actividad
                if (isset($_SESSION['usuario_id'])) {
                    registrarActividad($_SESSION['usuario_id'], "Orden de destinos actualizado");
                }
                header('Location: gestion-destinos.php?success=orden_actualizado');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Error al actualizar el orden: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['subir_orden']) || isset($_POST['bajar_orden'])) {
        // Lógica para subir o bajar un destino en el orden
        $id = $_POST['id'] ?? 0;
        $direccion = isset($_POST['subir_orden']) ? 'subir' : 'bajar';

        if ($id) {
            try {
                // Obtener orden actual del destino
                $stmt = $pdo->prepare("SELECT orden FROM destinos WHERE id = ?");
                $stmt->execute([$id]);
                $destino = $stmt->fetch();

                if ($destino) {
                    $orden_actual = $destino['orden'];

                    if ($direccion == 'subir') {
                        // Subir: intercambiar con el destino que tiene orden menor
                        $stmt = $pdo->prepare("SELECT id FROM destinos WHERE orden < ? ORDER BY orden DESC LIMIT 1");
                        $stmt->execute([$orden_actual]);
                        $destino_anterior = $stmt->fetch();

                        if ($destino_anterior) {
                            $pdo->beginTransaction();

                            // Poner el destino actual en la posición del anterior temporalmente
                            $stmt = $pdo->prepare("UPDATE destinos SET orden = ? WHERE id = ?");
                            $stmt->execute([-1, $id]);

                            // Mover el destino anterior a la posición actual
                            $stmt = $pdo->prepare("UPDATE destinos SET orden = ? WHERE id = ?");
                            $stmt->execute([$orden_actual, $destino_anterior['id']]);

                            // Mover el destino actual a la posición anterior
                            $stmt = $pdo->prepare("UPDATE destinos SET orden = ? WHERE id = ?");
                            $stmt->execute([$orden_actual - 1, $id]);

                            $pdo->commit();

                            // Registrar actividad
                            if (isset($_SESSION['usuario_id'])) {
                                registrarActividad($_SESSION['usuario_id'], "Destino subido en el orden (ID: $id)");
                            }
                        }
                    } else {
                        // Bajar: intercambiar con el destino que tiene orden mayor
                        $stmt = $pdo->prepare("SELECT id FROM destinos WHERE orden > ? ORDER BY orden ASC LIMIT 1");
                        $stmt->execute([$orden_actual]);
                        $destino_siguiente = $stmt->fetch();

                        if ($destino_siguiente) {
                            $pdo->beginTransaction();

                            // Poner el destino actual en la posición temporal
                            $stmt = $pdo->prepare("UPDATE destinos SET orden = ? WHERE id = ?");
                            $stmt->execute([-1, $id]);

                            // Mover el destino siguiente a la posición actual
                            $stmt = $pdo->prepare("UPDATE destinos SET orden = ? WHERE id = ?");
                            $stmt->execute([$orden_actual, $destino_siguiente['id']]);

                            // Mover el destino actual a la posición siguiente
                            $stmt = $pdo->prepare("UPDATE destinos SET orden = ? WHERE id = ?");
                            $stmt->execute([$orden_actual + 1, $id]);

                            $pdo->commit();

                            // Registrar actividad
                            if (isset($_SESSION['usuario_id'])) {
                                registrarActividad($_SESSION['usuario_id'], "Destino bajado en el orden (ID: $id)");
                            }
                        }
                    }

                    // Reordenar secuencialmente
                    $stmt = $pdo->query("SET @count = 0");
                    $stmt = $pdo->query("UPDATE destinos SET orden = (@count := @count + 1) ORDER BY orden");

                    header('Location: gestion-destinos.php?success=orden_ajustado');
                    exit;
                }
            } catch (PDOException $e) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Error al ajustar el orden: " . $e->getMessage();
            }
        }
    }
}

// Obtener destinos usando consulta directa para evitar problemas
$destinos = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM destinos ORDER BY orden ASC, nombre ASC");
    $stmt->execute();
    $destinos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al obtener destinos: " . $e->getMessage());
}

// Manejar acciones GET (para obtener datos para editar)
$destino_editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM destinos WHERE id = ?");
        $stmt->execute([$_GET['editar']]);
        $destino_editar = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener destino para editar: " . $e->getMessage());
    }
}

// Manejar mensajes de éxito/error
$mensaje = '';
$tipo_mensaje = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'destino_creado':
            $mensaje = 'Destino creado exitosamente';
            $tipo_mensaje = 'success';
            break;
        case 'destino_editado':
            $mensaje = 'Destino editado exitosamente';
            $tipo_mensaje = 'success';
            break;
        case 'destino_eliminado':
            $mensaje = 'Destino eliminado exitosamente';
            $tipo_mensaje = 'success';
            break;
        case 'orden_actualizado':
            $mensaje = 'Orden de destinos actualizado exitosamente';
            $tipo_mensaje = 'success';
            break;
        case 'orden_ajustado':
            $mensaje = 'Posición del destino ajustada exitosamente';
            $tipo_mensaje = 'success';
            break;
    }
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'destino_con_actividades':
            $mensaje = 'No se puede eliminar el destino porque tiene actividades asociadas';
            $tipo_mensaje = 'error';
            break;
    }
}

// Mostrar errores de POST si existen
if (isset($error) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $mensaje = $error;
    $tipo_mensaje = 'error';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Destinos - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.css" rel="stylesheet">
    <style>
        /* Estilos completos para el panel de administración */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        /* Estilos de modal */
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
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
        }

        .modal h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .modal-close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: #e74c3c;
        }

        /* Estilos de formularios */
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
        .form-group textarea,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .form-group input[type="number"] {
            width: 100px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        /* Estilos de botones */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .btn-success {
            background-color: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background-color: #219653;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        .btn-warning {
            background-color: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background-color: #d68910;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background-color: #138496;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
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
        }

        .btn-up:hover {
            background-color: #7f8c8d;
        }

        .btn-down:hover {
            background-color: #7f8c8d;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Estilos de alertas */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .alert-close {
            cursor: pointer;
            font-size: 20px;
            color: inherit;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .alert-close:hover {
            opacity: 1;
        }

        /* Estilos de estado */
        .status {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .status.active {
            background-color: #d4edda;
            color: #155724;
        }

        .status.inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Layout principal */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #f5f5f5;
            margin-left: 280px;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        /* Estilos de tabla */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .admin-table th,
        .admin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .admin-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            position: sticky;
            top: 0;
        }

        .admin-table tr:hover {
            background-color: #f8f9fa;
        }

        .admin-table img {
            border-radius: 4px;
            border: 1px solid #eee;
        }

        /* Estilos del header */
        .content-header {
            margin-bottom: 30px;
        }

        .content-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .content-header p {
            color: #7f8c8d;
            line-height: 1.6;
        }

        /* Estilos de acciones */
        .admin-actions {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Previsualización de imágenes */
        .preview-image {
            max-width: 200px;
            max-height: 150px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            object-fit: cover;
        }

        .current-image {
            margin-top: 10px;
            font-size: 14px;
            color: #7f8c8d;
        }

        /* Controles de orden */
        .order-controls {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .order-controls h3 {
            margin-top: 0;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .order-controls p {
            color: #6c757d;
            margin-bottom: 15px;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        /* Estilos para ordenamiento drag & drop */
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
            transition: color 0.3s;
        }

        .handle:hover {
            color: #3498db;
        }

        /* Estilos para tabla con ordenamiento */
        .destino-item {
            transition: all 0.3s ease;
        }

        .destino-item:hover {
            background-color: #f8f9fa;
        }

        .order-number {
            display: inline-block;
            min-width: 20px;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 75px 10px 15px 10px;
            }

            .content-header h1 {
                font-size: 1.5rem;
            }

            .content-header p {
                font-size: 0.9rem;
            }

            .admin-actions {
                flex-direction: column;
                gap: 10px;
            }

            .admin-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 0 -10px;
                padding: 0 10px;
            }

            .admin-table {
                min-width: 800px;
                font-size: 13px;
            }

            .admin-table th,
            .admin-table td {
                padding: 8px 6px;
                white-space: nowrap;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
                align-items: stretch;
            }

            .btn-sm {
                width: 100%;
                margin-bottom: 5px;
                padding: 8px 12px;
                font-size: 0.85rem;
            }

            .modal-content {
                margin: 5% auto;
                padding: 20px 15px;
                width: 95%;
                max-height: 90vh;
                overflow-y: auto;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .order-actions {
                flex-direction: column;
                gap: 10px;
            }

            .order-actions .btn {
                width: 100%;
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
                font-size: 0.85rem;
            }

            .table-container {
                margin: 0 -5px;
                padding: 0 5px;
            }

            .admin-table {
                min-width: 750px;
                font-size: 12px;
            }

            .admin-table th,
            .admin-table td {
                padding: 6px 4px;
            }

            .btn {
                padding: 8px 12px;
                font-size: 0.8rem;
            }

            .btn-sm {
                padding: 6px 10px;
                font-size: 0.75rem;
            }

            .modal-content {
                margin: 2% 2.5%;
                padding: 15px 10px;
                width: 95%;
            }
        }

        font-size: 24px;
        }

        .table-container {
            padding: 15px;
        }

        .admin-table {
            display: block;
            overflow-x: auto;
        }
        }

        /* Animaciones */
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

        .fade-in {
            animation: fadeIn 0.5s forwards;
        }

        /* Estilos adicionales */
        small {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-size: 12px;
        }

        form[style*="display: inline"] {
            display: inline;
        }

        input[type="checkbox"] {
            width: auto;
            margin-right: 5px;
        }

        select.form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background-color: white;
        }

        /* Estilos para mensajes temporales */
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

        /* Estilos para botones deshabilitados */
        .btn:disabled,
        .btn[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        /* Estilos para iconos dentro de botones */
        .btn i {
            font-size: 14px;
        }

        /* Estilos para scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        /* Estilos para formularios de eliminación */
        form[onsubmit*="confirm"] {
            display: inline;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="content-header">
                <h1>Gestión de Destinos</h1>
                <p>Administra los destinos del Putumayo. Ordena los destinos arrastrando las filas o usando los botones de subir/bajar.</p>
            </div>
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje == 'success' ? 'success' : 'error'; ?>" id="mensaje">
                    <span><?php echo htmlspecialchars($mensaje); ?></span>
                    <span class="alert-close" onclick="document.getElementById('mensaje').style.display='none'">&times;</span>
                </div>
            <?php endif; ?>

            <div class="admin-actions">
                <button onclick="abrirModalAgregar()" class="btn btn-success">
                    <i class="fas fa-plus"></i> Agregar Nuevo Destino
                </button>
            </div>

            <div class="table-container">
                <?php if (empty($destinos)): ?>
                    <p>No hay destinos registrados. Agrega tu primer destino.</p>
                <?php else: ?>
                    <form id="formOrden" method="POST">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th width="60">Orden</th>
                                    <th width="80">Imagen</th>
                                    <th>Nombre</th>
                                    <th>Ubicación</th>
                                    <th width="100">Estado</th>
                                    <th width="200">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="listaDestinos">
                                <?php foreach ($destinos as $destino): ?>
                                    <tr class="destino-item" data-id="<?php echo $destino['id']; ?>">
                                        <td>
                                            <span class="order-number"><?php echo $destino['orden']; ?></span>
                                            <input type="hidden" name="orden[<?php echo $destino['id']; ?>]" value="<?php echo $destino['orden']; ?>">
                                        </td>
                                        <td>
                                            <?php if (!empty($destino['imagen_principal'])): ?>
                                                <img src="../uploads/destinos/<?php echo htmlspecialchars($destino['imagen_principal']); ?>"
                                                    alt="<?php echo htmlspecialchars($destino['nombre']); ?>"
                                                    width="60" height="60" style="object-fit: cover;">
                                            <?php else: ?>
                                                <img src="../uploads/default.jpg" alt="Sin imagen" width="60" height="60" style="object-fit: cover;">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($destino['nombre']); ?></strong><br>
                                            <small><?php echo substr(htmlspecialchars($destino['descripcion']), 0, 50); ?>...</small>
                                        </td>
                                        <td><?php echo htmlspecialchars($destino['ubicacion']); ?></td>
                                        <td>
                                            <span class="status <?php echo $destino['activo'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $destino['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" onclick="editarDestino(<?php echo $destino['id']; ?>)"
                                                    class="btn btn-edit btn-sm">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>

                                                <form method="POST" style="display: inline;"
                                                    onsubmit="return confirm('¿Estás seguro de eliminar este destino?');">
                                                    <input type="hidden" name="id" value="<?php echo $destino['id']; ?>">
                                                    <button type="submit" name="eliminar_destino" class="btn btn-delete btn-sm">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>

                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="id" value="<?php echo $destino['id']; ?>">
                                                    <button type="submit" name="subir_orden" class="btn btn-up btn-sm"
                                                        <?php echo $destino['orden'] <= 1 ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-arrow-up"></i>
                                                    </button>
                                                </form>

                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="id" value="<?php echo $destino['id']; ?>">
                                                    <button type="submit" name="bajar_orden" class="btn btn-down btn-sm"
                                                        <?php echo $destino['orden'] >= count($destinos) ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-arrow-down"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="order-controls">
                            <h3>Orden de Destinos</h3>
                            <p>Puedes reordenar los destinos arrastrando las filas o usar los botones de arriba/abajo. Guarda los cambios cuando termines.</p>
                            <div class="order-actions">
                                <button type="button" onclick="activarArrastre()" class="btn btn-info">
                                    <i class="fas fa-arrows-alt"></i> Activar Arrastre
                                </button>
                                <button type="submit" name="actualizar_orden" class="btn btn-success">
                                    <i class="fas fa-save"></i> Guardar Orden
                                </button>
                                <button type="button" onclick="desactivarArrastre()" class="btn btn-warning">
                                    <i class="fas fa-times"></i> Desactivar Arrastre
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para agregar destino -->
    <div id="modalAgregar" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="cerrarModalAgregar()">&times;</span>
            <h2>Agregar Nuevo Destino</h2>
            <form method="POST" enctype="multipart/form-data" id="formAgregar">
                <div class="form-group">
                    <label for="nombre">Nombre del Destino *</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion"></textarea>
                </div>

                <div class="form-group">
                    <label for="ubicacion">Ubicación</label>
                    <input type="text" id="ubicacion" name="ubicacion">
                </div>

                <div class="form-group">
                    <label for="imagen_principal">Imagen Principal</label>
                    <input type="file" id="imagen_principal" name="imagen_principal" accept="image/*" onchange="previewImage(this, 'previewAgregar')">
                    <small>Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 2MB</small>
                    <img id="previewAgregar" class="preview-image" src="" alt="Vista previa" style="display: none;">
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="activo" name="activo" checked>
                        <label for="activo">Destino activo (visible al público)</label>
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn btn-warning" onclick="cerrarModalAgregar()">Cancelar</button>
                    <button type="submit" name="agregar_destino" class="btn btn-success">Guardar Destino</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para editar destino -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="cerrarModalEditar()">&times;</span>
            <h2>Editar Destino</h2>
            <form method="POST" enctype="multipart/form-data" id="formEditar">
                <input type="hidden" id="edit_id" name="id">

                <div class="form-group">
                    <label for="edit_nombre">Nombre del Destino *</label>
                    <input type="text" id="edit_nombre" name="nombre" required>
                </div>

                <div class="form-group">
                    <label for="edit_descripcion">Descripción</label>
                    <textarea id="edit_descripcion" name="descripcion"></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_ubicacion">Ubicación</label>
                    <input type="text" id="edit_ubicacion" name="ubicacion">
                </div>

                <div class="form-group">
                    <label for="edit_imagen_principal">Imagen Principal</label>
                    <input type="file" id="edit_imagen_principal" name="imagen_principal" accept="image/*" onchange="previewImage(this, 'previewEditar')">
                    <small>Deja en blanco para conservar la imagen actual</small>
                    <div id="current_image" class="current-image"></div>
                    <img id="previewEditar" class="preview-image" src="" alt="Vista previa" style="display: none;">
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="edit_activo" name="activo">
                        <label for="edit_activo">Destino activo (visible al público)</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_orden">Orden</label>
                    <input type="number" id="edit_orden" name="orden" min="1">
                    <small>Número que determina la posición en la lista</small>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn btn-warning" onclick="cerrarModalEditar()">Cancelar</button>
                    <button type="submit" name="editar_destino" class="btn btn-success">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script>
        // Variables globales
        let sortable = null;
        let arrastreActivado = false;

        // Funciones para modales
        function abrirModalAgregar() {
            document.getElementById('modalAgregar').style.display = 'block';
            document.getElementById('formAgregar').reset();
            document.getElementById('previewAgregar').style.display = 'none';
        }

        function cerrarModalAgregar() {
            document.getElementById('modalAgregar').style.display = 'none';
        }

        function abrirModalEditar() {
            document.getElementById('modalEditar').style.display = 'block';
        }

        function cerrarModalEditar() {
            document.getElementById('modalEditar').style.display = 'none';
        }

        // Vista previa de imágenes
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }

                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        // Editar destino
        async function editarDestino(id) {
            try {
                const response = await fetch(`gestion-destinos.php?editar=${id}`);
                const text = await response.text();

                // Parsear el HTML para obtener los datos del destino
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = text;

                // Buscar la fila del destino en la tabla
                const destinoRow = document.querySelector(`tr[data-id="${id}"]`);
                if (!destinoRow) {
                    // Si no encontramos en el DOM, hacemos una petición directa
                    const destino = await obtenerDestinoPorId(id);
                    if (destino) {
                        llenarFormularioEditar(destino);
                        abrirModalEditar();
                    }
                    return;
                }

                // Extraer datos de la fila
                const destino = {
                    id: id,
                    nombre: destinoRow.querySelector('td:nth-child(3) strong').textContent,
                    descripcion: destinoRow.querySelector('td:nth-child(3) small').textContent.replace('...', ''),
                    ubicacion: destinoRow.querySelector('td:nth-child(4)').textContent,
                    activo: destinoRow.querySelector('.status').classList.contains('active'),
                    orden: destinoRow.querySelector('.order-number').textContent,
                    imagen_principal: destinoRow.querySelector('td:nth-child(2) img').getAttribute('src')
                };

                llenarFormularioEditar(destino);
                abrirModalEditar();

            } catch (error) {
                console.error('Error al obtener destino:', error);
                alert('Error al cargar los datos del destino');
            }
        }

        async function obtenerDestinoPorId(id) {
            try {
                const response = await fetch(`obtener_destino.php?id=${id}`);
                return await response.json();
            } catch (error) {
                console.error('Error:', error);
                return null;
            }
        }

        function llenarFormularioEditar(destino) {
            document.getElementById('edit_id').value = destino.id;
            document.getElementById('edit_nombre').value = destino.nombre;
            document.getElementById('edit_descripcion').value = destino.descripcion;
            document.getElementById('edit_ubicacion').value = destino.ubicacion;
            document.getElementById('edit_activo').checked = destino.activo;
            document.getElementById('edit_orden').value = destino.orden;

            // Manejar imagen actual
            const currentImageDiv = document.getElementById('current_image');
            if (destino.imagen_principal && !destino.imagen_principal.includes('default.jpg')) {
                currentImageDiv.innerHTML = `Imagen actual: <a href="${destino.imagen_principal}" target="_blank">Ver imagen</a>`;
            } else {
                currentImageDiv.innerHTML = 'Sin imagen actual';
            }

            // Reset preview
            document.getElementById('previewEditar').style.display = 'none';
        }

        // Funciones para ordenamiento drag & drop
        function activarArrastre() {
            if (arrastreActivado) return;

            const listaDestinos = document.getElementById('listaDestinos');
            sortable = new Sortable(listaDestinos, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                handle: '.handle',
                onEnd: function() {
                    actualizarNumerosOrden();
                }
            });

            // Agregar handles a las filas
            const rows = document.querySelectorAll('#listaDestinos tr');
            rows.forEach(row => {
                const orderCell = row.querySelector('td:first-child');
                orderCell.innerHTML = `<i class="fas fa-arrows-alt handle"></i> ` + orderCell.innerHTML;
            });

            arrastreActivado = true;
            mostrarMensajeTemporal('Modo arrastre activado. Arrastra las filas para cambiar el orden.', 'info');
        }

        function desactivarArrastre() {
            if (sortable) {
                sortable.destroy();
                sortable = null;
            }

            // Remover handles
            const handles = document.querySelectorAll('.handle');
            handles.forEach(handle => handle.remove());

            arrastreActivado = false;
            mostrarMensajeTemporal('Modo arrastre desactivado.', 'warning');
        }

        function actualizarNumerosOrden() {
            const rows = document.querySelectorAll('#listaDestinos tr');
            rows.forEach((row, index) => {
                const orderNumber = row.querySelector('.order-number');
                const hiddenInput = row.querySelector('input[name^="orden"]');

                if (orderNumber && hiddenInput) {
                    const newOrder = index + 1;
                    orderNumber.textContent = newOrder;

                    // Extraer el ID del destino del nombre del input
                    const inputName = hiddenInput.name;
                    const matches = inputName.match(/orden\[(\d+)\]/);
                    if (matches && matches[1]) {
                        hiddenInput.name = `orden[${matches[1]}]`;
                        hiddenInput.value = newOrder;
                    }
                }
            });
        }

        // Función para mostrar mensajes temporales
        function mostrarMensajeTemporal(mensaje, tipo) {
            // Remover mensaje anterior si existe
            const mensajeAnterior = document.getElementById('mensajeTemporal');
            if (mensajeAnterior) {
                mensajeAnterior.remove();
            }

            // Crear nuevo mensaje
            const mensajeDiv = document.createElement('div');
            mensajeDiv.id = 'mensajeTemporal';
            mensajeDiv.textContent = mensaje;

            // Estilos según tipo
            switch (tipo) {
                case 'success':
                    mensajeDiv.style.backgroundColor = '#27ae60';
                    break;
                case 'error':
                    mensajeDiv.style.backgroundColor = '#e74c3c';
                    break;
                case 'warning':
                    mensajeDiv.style.backgroundColor = '#f39c12';
                    break;
                case 'info':
                    mensajeDiv.style.backgroundColor = '#3498db';
                    break;
                default:
                    mensajeDiv.style.backgroundColor = '#3498db';
            }

            // Agregar al DOM
            document.body.appendChild(mensajeDiv);

            // Remover después de 3 segundos
            setTimeout(() => {
                mensajeDiv.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (mensajeDiv.parentNode) {
                        mensajeDiv.parentNode.removeChild(mensajeDiv);
                    }
                }, 300);
            }, 3000);
        }

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            const modalAgregar = document.getElementById('modalAgregar');
            const modalEditar = document.getElementById('modalEditar');

            if (event.target == modalAgregar) {
                cerrarModalAgregar();
            }
            if (event.target == modalEditar) {
                cerrarModalEditar();
            }
        }

        // Validación de formularios
        document.getElementById('formAgregar')?.addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            if (!nombre) {
                e.preventDefault();
                alert('El nombre del destino es obligatorio');
                return false;
            }

            // Validar archivo de imagen
            const imagenInput = document.getElementById('imagen_principal');
            if (imagenInput.files.length > 0) {
                const file = imagenInput.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('Formato de imagen no válido. Use JPG, PNG o GIF.');
                    return false;
                }

                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('La imagen es demasiado grande. Máximo 2MB.');
                    return false;
                }
            }
        });

        document.getElementById('formEditar')?.addEventListener('submit', function(e) {
            const nombre = document.getElementById('edit_nombre').value.trim();
            if (!nombre) {
                e.preventDefault();
                alert('El nombre del destino es obligatorio');
                return false;
            }

            // Validar archivo de imagen
            const imagenInput = document.getElementById('edit_imagen_principal');
            if (imagenInput.files.length > 0) {
                const file = imagenInput.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('Formato de imagen no válido. Use JPG, PNG o GIF.');
                    return false;
                }

                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('La imagen es demasiado grande. Máximo 2MB.');
                    return false;
                }
            }
        });

        // Cerrar alertas automáticamente después de 5 segundos
        setTimeout(() => {
            const alert = document.getElementById('mensaje');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }
        }, 5000);

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar tooltips a botones deshabilitados
            document.querySelectorAll('button[disabled]').forEach(button => {
                if (button.name === 'subir_orden') {
                    button.title = 'Ya está en la primera posición';
                }
                if (button.name === 'bajar_orden') {
                    button.title = 'Ya está en la última posición';
                }
            });

            // Mostrar destino a editar si viene en URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('editar')) {
                const id = urlParams.get('editar');
                if (id && !isNaN(id)) {
                    setTimeout(() => editarDestino(parseInt(id)), 100);
                }
            }
        });
    </script>
</body>

</html>