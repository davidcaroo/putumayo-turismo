<?php
// Incluir archivos de configuración
include '../includes/config.php';
// No incluir functions.php aquí ya que ya está incluido en config.php

// Asegurar que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación usando las variables correctas de sesión
$user_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? $_SESSION['usuario_rol'] ?? null;

if (!$user_id || (!$user_role || ($user_role !== 'superadmin' && $user_role !== 'admin'))) {
    header('Location: ../login.php');
    exit;
}

// Función auxiliar para obtener nombre del rol
if (!function_exists('getRoleName')) {
    function getRoleName($role)
    {
        switch ($role) {
            case 'superadmin':
                return 'Super Admin';
            case 'admin':
                return 'Administrador';
            case 'usuario':
                return 'Usuario';
            default:
                return $role;
        }
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

// Inicializar mensajes
$message = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $target_user_id = $_POST['user_id'] ?? 0;

    // Validar que user_id sea numérico
    if (!is_numeric($target_user_id) || $target_user_id <= 0) {
        $error = "ID de usuario inválido";
    } else {
        switch ($_POST['action']) {
            case 'activate':
                try {
                    $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");
                    $stmt->execute([$target_user_id]);
                    $message = "Usuario activado correctamente";
                    // Registrar actividad si la función existe
                    if (function_exists('logActivity')) {
                        logActivity($user_id, 'activate_user', "Usuario activado ID: $target_user_id");
                    }
                } catch (PDOException $e) {
                    $error = "Error al activar usuario: " . $e->getMessage();
                }
                break;

            case 'deactivate':
                try {
                    $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
                    $stmt->execute([$target_user_id]);
                    $message = "Usuario desactivado correctamente";
                    // Registrar actividad si la función existe
                    if (function_exists('logActivity')) {
                        logActivity($user_id, 'deactivate_user', "Usuario desactivado ID: $target_user_id");
                    }
                } catch (PDOException $e) {
                    $error = "Error al desactivar usuario: " . $e->getMessage();
                }
                break;

            case 'change_role':
                $new_role = $_POST['new_role'] ?? '';

                if (in_array($new_role, ['usuario', 'admin', 'superadmin'])) {
                    try {
                        // Verificar que no estamos modificando el último superadmin
                        if ($new_role !== 'superadmin') {
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'superadmin'");
                            $stmt->execute();
                            $superadmin_count = $stmt->fetch()['total'];

                            $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
                            $stmt->execute([$target_user_id]);
                            $target_user = $stmt->fetch();

                            if ($target_user['rol'] === 'superadmin' && $superadmin_count <= 1) {
                                $error = "No se puede cambiar el rol del único superadministrador";
                                break;
                            }
                        }

                        $stmt = $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
                        $stmt->execute([$new_role, $target_user_id]);
                        $message = "Rol actualizado correctamente";
                        // Registrar actividad si la función existe
                        if (function_exists('logActivity')) {
                            logActivity($user_id, 'change_user_role', "Rol cambiado a $new_role para usuario ID: $target_user_id");
                        }
                    } catch (PDOException $e) {
                        $error = "Error al cambiar rol: " . $e->getMessage();
                    }
                } else {
                    $error = "Rol inválido";
                }
                break;

            case 'delete':
                try {
                    // Obtener información del usuario
                    $stmt = $pdo->prepare("SELECT nombre, rol FROM usuarios WHERE id = ?");
                    $stmt->execute([$target_user_id]);
                    $target_user = $stmt->fetch();

                    if (!$target_user) {
                        $error = "Usuario no encontrado";
                        break;
                    }

                    // Verificar que no sea superadmin
                    if ($target_user['rol'] === 'superadmin') {
                        $error = "No se puede eliminar un superadministrador";
                        break;
                    }

                    // Verificar que el usuario actual tenga permiso
                    if ($user_role !== 'superadmin' && $user_role === 'admin' && $target_user['rol'] === 'admin') {
                        $error = "Los administradores no pueden eliminar otros administradores";
                        break;
                    }

                    // Verificar que no se esté eliminando a sí mismo
                    if ($target_user_id == $user_id) {
                        $error = "No puedes eliminarte a ti mismo";
                        break;
                    }

                    // Eliminar usuario
                    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                    if ($stmt->execute([$target_user_id])) {
                        $message = "Usuario eliminado correctamente";
                        // Registrar actividad si la función existe
                        if (function_exists('logActivity')) {
                            logActivity($user_id, 'delete_user', "Usuario eliminado: " . $target_user['nombre']);
                        }
                    } else {
                        $error = "Error al eliminar usuario";
                    }
                } catch (PDOException $e) {
                    $error = "Error en la base de datos: " . $e->getMessage();
                }
                break;
        }
    }
}

// Paginación
$items_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Obtener usuarios con filtros
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (nombre LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($role_filter) {
    $sql .= " AND rol = ?";
    $params[] = $role_filter;
}

if ($status_filter !== '') {
    $sql .= " AND activo = ?";
    $params[] = (int)$status_filter;
}

$sql .= " ORDER BY fecha_registro DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($sql);

    // Vincular parámetros dinámicamente
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }

    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuarios = [];
    $error = "Error al cargar usuarios: " . $e->getMessage();
}

// Total de usuarios para paginación
$count_sql = "SELECT COUNT(*) as total FROM usuarios WHERE 1=1";
$count_params = [];

if ($search) {
    $count_sql .= " AND (nombre LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $count_params[] = $search_term;
    $count_params[] = $search_term;
}

if ($role_filter) {
    $count_sql .= " AND rol = ?";
    $count_params[] = $role_filter;
}

if ($status_filter !== '') {
    $count_sql .= " AND activo = ?";
    $count_params[] = (int)$status_filter;
}

try {
    $stmt = $pdo->prepare($count_sql);

    if ($count_params) {
        foreach ($count_params as $index => $param) {
            $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    }

    $stmt->execute();
    $total_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_usuarios = $total_result['total'] ?? 0;
} catch (PDOException $e) {
    $total_usuarios = 0;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin</title>
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

        /* Sidebar */

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
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .filter-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            font-size: 14px;
            min-width: 150px;
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
            background: #f8f9fa;
        }

        .role-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .role-superadmin {
            background: #dc3545;
            color: white;
        }

        .role-admin {
            background: #007bff;
            color: white;
        }

        .role-usuario {
            background: #28a745;
            color: white;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
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
            gap: 8px;
            flex-wrap: wrap;
        }

        .clear-filters {
            padding: 10px 15px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .clear-filters:hover {
            background: #5a6268;
        }

        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            margin: 0;
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            animation: modalSlide 0.3s;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .close-modal {
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }

        .close-modal:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .page-link {
            padding: 8px 15px;
            background: #f8f9fa;
            color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            font-size: 14px;
        }

        .page-link:hover {
            background: #e9ecef;
        }

        .page-link.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes modalSlide {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

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
                align-items: stretch;
                gap: 10px;
            }

            .admin-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .search-box {
                min-width: 100%;
                width: 100%;
            }

            .filter-options {
                flex-direction: column;
                gap: 10px;
            }

            .filter-select {
                width: 100%;
            }

            .user-stats {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 0 -10px;
                padding: 0 10px;
            }

            .admin-table {
                display: table;
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
            }

            .btn-sm {
                width: 100%;
                padding: 8px 12px;
                font-size: 0.85rem;
                justify-content: center;
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

                .user-stats h3 {
                    font-size: 1.8rem;
                }
            }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="content-header">
                <h1>Gestión de Usuarios</h1>
                <p>Administra los usuarios del sistema</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas rápidas -->
            <div class="user-stats">
                <?php
                try {
                    // Total usuarios
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
                    $total = $stmt->fetch()['total'] ?? 0;

                    // Usuarios activos
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
                    $activos = $stmt->fetch()['total'] ?? 0;

                    // Administradores
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol IN ('admin', 'superadmin')");
                    $admins = $stmt->fetch()['total'] ?? 0;

                    // Nuevos este mes
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE MONTH(fecha_registro) = MONTH(CURDATE())");
                    $nuevos_mes = $stmt->fetch()['total'] ?? 0;
                } catch (PDOException $e) {
                    $total = $activos = $admins = $nuevos_mes = 0;
                }
                ?>

                <div class="stat-card">
                    <h3>Total Usuarios</h3>
                    <div class="stat-number"><?php echo $total; ?></div>
                </div>

                <div class="stat-card">
                    <h3>Usuarios Activos</h3>
                    <div class="stat-number"><?php echo $activos; ?></div>
                </div>

                <div class="stat-card">
                    <h3>Administradores</h3>
                    <div class="stat-number"><?php echo $admins; ?></div>
                </div>

                <div class="stat-card">
                    <h3>Nuevos este mes</h3>
                    <div class="stat-number"><?php echo $nuevos_mes; ?></div>
                </div>
            </div>

            <!-- Filtros y búsqueda -->
            <div class="admin-actions">
                <div class="search-box">
                    <form method="GET" action="" id="searchForm">
                        <input type="text" name="search" id="searchUsers"
                            placeholder="Buscar por nombre o email..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fas fa-search"></i>
                    </form>
                </div>

                <div class="filter-options">
                    <select name="role" class="filter-select" onchange="applyFilters()" id="roleFilter">
                        <option value="">Todos los roles</option>
                        <option value="usuario" <?php echo $role_filter === 'usuario' ? 'selected' : ''; ?>>Usuarios</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administradores</option>
                        <option value="superadmin" <?php echo $role_filter === 'superadmin' ? 'selected' : ''; ?>>Super Admin</option>
                    </select>

                    <select name="status" class="filter-select" onchange="applyFilters()" id="statusFilter">
                        <option value="">Todos los estados</option>
                        <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Activos</option>
                        <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactivos</option>
                    </select>

                    <?php if ($search || $role_filter || $status_filter !== ''): ?>
                        <a href="gestion-usuarios.php" class="clear-filters">
                            <i class="fas fa-times"></i> Limpiar filtros
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabla de usuarios -->
            <div class="table-container">
                <?php if (empty($usuarios)): ?>
                    <div style="padding: 40px; text-align: center;">
                        <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <p style="color: #666;">No se encontraron usuarios</p>
                    </div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                    <td>
                                        <div class="user-info">
                                            <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $usuario['rol']; ?>">
                                            <?php echo getRoleName($usuario['rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status <?php echo $usuario['activo'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($usuario['fecha_registro']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- Verificar permisos -->
                                            <?php
                                            $canEdit = true;
                                            if ($usuario['rol'] === 'superadmin' && ($user_role !== 'superadmin' || $user_id == $usuario['id'])) {
                                                $canEdit = false;
                                            }
                                            ?>

                                            <?php if ($canEdit): ?>
                                                <!-- Cambiar estado -->
                                                <?php if ($usuario['activo']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($usuario['id']); ?>">
                                                        <input type="hidden" name="action" value="deactivate">
                                                        <button type="submit" class="btn-sm btn-warning"
                                                            onclick="return confirm('¿Desactivar este usuario?')"
                                                            title="Desactivar usuario">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($usuario['id']); ?>">
                                                        <input type="hidden" name="action" value="activate">
                                                        <button type="submit" class="btn-sm btn-success"
                                                            title="Activar usuario">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Cambiar rol -->
                                                <?php if ($user_role === 'superadmin' && $usuario['rol'] !== 'superadmin'): ?>
                                                    <button class="btn-sm btn-info"
                                                        onclick="openRoleModal(<?php echo $usuario['id']; ?>, '<?php echo $usuario['rol']; ?>')"
                                                        title="Cambiar rol">
                                                        <i class="fas fa-user-cog"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Eliminar -->
                                                <?php if ($usuario['rol'] !== 'superadmin' && ($user_role === 'superadmin' || ($user_role === 'admin' && $usuario['rol'] === 'usuario'))): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($usuario['id']); ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" class="btn-sm btn-danger"
                                                            onclick="return confirm('¿Eliminar permanentemente este usuario? Esta acción no se puede deshacer.')"
                                                            title="Eliminar usuario">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 12px;">Acciones restringidas</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Paginación -->
                <?php if ($total_usuarios > $items_per_page): ?>
                    <div class="pagination">
                        <?php
                        $total_pages = ceil($total_usuarios / $items_per_page);
                        $url_base = "gestion-usuarios.php?";

                        // Agregar parámetros de filtro
                        $query_params = [];
                        if ($search) $query_params[] = "search=" . urlencode($search);
                        if ($role_filter) $query_params[] = "role=" . urlencode($role_filter);
                        if ($status_filter !== '') $query_params[] = "status=" . urlencode($status_filter);

                        $url_suffix = $query_params ? "&" . implode("&", $query_params) : "";

                        // Botón anterior
                        if ($current_page > 1) {
                            echo '<a href="' . $url_base . 'page=' . ($current_page - 1) . $url_suffix . '" class="page-link">&laquo;</a>';
                        }

                        // Páginas
                        $start = max(1, $current_page - 2);
                        $end = min($total_pages, $start + 4);

                        for ($i = $start; $i <= $end; $i++) {
                            $active = $i == $current_page ? ' active' : '';
                            echo '<a href="' . $url_base . 'page=' . $i . $url_suffix . '" class="page-link' . $active . '">' . $i . '</a>';
                        }

                        // Botón siguiente
                        if ($current_page < $total_pages) {
                            echo '<a href="' . $url_base . 'page=' . ($current_page + 1) . $url_suffix . '" class="page-link">&raquo;</a>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para cambiar rol -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cambiar Rol de Usuario</h3>
                <span class="close-modal" onclick="closeRoleModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="roleForm">
                    <input type="hidden" name="user_id" id="modalUserId">
                    <input type="hidden" name="action" value="change_role">

                    <div class="form-group">
                        <label for="modalUserRole">Seleccionar Nuevo Rol:</label>
                        <select name="new_role" id="modalUserRole" class="form-control" required>
                            <option value="usuario">Usuario</option>
                            <option value="admin">Administrador</option>
                            <?php if ($user_role === 'superadmin'): ?>
                                <option value="superadmin">Super Administrador</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeRoleModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Cambiar Rol</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Aplicar filtros automáticamente
        function applyFilters() {
            const search = document.getElementById('searchUsers').value;
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value;

            let url = 'gestion-usuarios.php?';
            const params = [];

            if (search) params.push('search=' + encodeURIComponent(search));
            if (role) params.push('role=' + encodeURIComponent(role));
            if (status !== '') params.push('status=' + encodeURIComponent(status));

            window.location.href = url + params.join('&');
        }

        // Búsqueda automática después de 500ms sin escribir
        let searchTimeout;
        document.getElementById('searchUsers').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        });

        // Modal de roles
        function openRoleModal(userId, currentRole) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUserRole').value = currentRole;
            document.getElementById('roleModal').style.display = 'block';
        }

        function closeRoleModal() {
            document.getElementById('roleModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('roleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRoleModal();
            }
        });

        // Evitar envío duplicado de formularios
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.disabled = true;
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                        // Restaurar después de 3 segundos si hay un error
                        setTimeout(() => {
                            if (submitBtn.disabled) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }
                        }, 3000);
                    }
                });
            });

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