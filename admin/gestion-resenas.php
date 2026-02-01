<?php
// admin/gestion-reseñas.php
include_once '../includes/config.php';
include_once '../includes/functions.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('superadmin'))) {
    header('Location: ../login.php');
    exit;
}

// Variables de sesión
$user_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? $_SESSION['usuario_rol'] ?? null;

// Procesar acciones
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['actualizar_estado'])) {
        $id = intval($_POST['id']);
        $estado = trim($_POST['estado']);
        $respuesta = trim($_POST['respuesta'] ?? '');

        try {
            $stmt = $pdo->prepare("UPDATE resenas_destino SET estado = ?, respuesta = ? WHERE id = ?");
            $stmt->execute([$estado, $respuesta, $id]);
            $message = "Reseña actualizada correctamente";
            logActivity($user_id, 'update_review', "Reseña ID $id actualizada a estado: $estado");
        } catch (PDOException $e) {
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }

    if (isset($_POST['eliminar_resena'])) {
        $id = intval($_POST['id']);

        try {
            $stmt = $pdo->prepare("DELETE FROM resenas_destino WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Reseña eliminada correctamente";
            logActivity($user_id, 'delete_review', "Reseña ID $id eliminada");
        } catch (PDOException $e) {
            $error = "Error al eliminar: " . $e->getMessage();
        }
    }
}

// Obtener reseñas con filtros
$estado_filter = $_GET['estado'] ?? 'pendiente';
$destino_filter = $_GET['destino'] ?? '';

$sql = "SELECT rd.*, d.nombre as destino_nombre FROM resenas_destino rd 
        LEFT JOIN destinos d ON rd.destino_id = d.id 
        WHERE 1=1";
$params = [];

if ($estado_filter && $estado_filter !== '') {
    $sql .= " AND rd.estado = ?";
    $params[] = $estado_filter;
}

if ($destino_filter && $destino_filter !== '') {
    $sql .= " AND rd.destino_id = ?";
    $params[] = $destino_filter;
}

$sql .= " ORDER BY rd.fecha_creacion DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reseñas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reseñas = [];
    $error = "Error al cargar reseñas: " . $e->getMessage();
}

// Obtener destinos para filtro
try {
    $stmt = $pdo->query("SELECT id, nombre FROM destinos ORDER BY nombre");
    $destinos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $destinos = [];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reseñas - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
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
            display: flex;
            align-items: center;
            gap: 10px;
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

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        button:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        /* ===== FILTROS ===== */
        .filters-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }

        .form-control,
        .form-select {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px;
            width: 100%;
            font-size: 14px;
            font-family: inherit;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: none;
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        /* ===== TABLA ===== */
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge-counter {
            background: var(--secondary-color);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* ===== ESTILOS ESPECÍFICOS PARA RESEÑAS ===== */
        .estrellas-resena {
            color: #ffc107;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .badge-estado {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-pendiente {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .badge-aprobado {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .badge-rechazado {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .resena-usuario {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2px;
        }

        .resena-email {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 2px;
        }

        .resena-telefono {
            font-size: 0.85rem;
            color: #95a5a6;
        }

        .resena-titulo {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            display: block;
        }

        .resena-comentario {
            font-size: 0.9rem;
            color: #495057;
            line-height: 1.4;
        }

        .resena-fecha {
            font-size: 0.85rem;
            color: #95a5a6;
        }

        .destino-nombre {
            font-weight: 600;
            color: #2c3e50;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .no-data p {
            font-size: 1.1rem;
        }

        /* ===== MODAL ESTILOS ===== */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }

        .modal-dialog {
            position: relative;
            margin: 50px auto;
            width: 90%;
            max-width: 500px;
        }

        .modal-lg {
            max-width: 800px;
        }

        .modal-content {
            background-color: #fff;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px 10px 0 0;
            background: #f8f9fa;
        }

        .modal-title {
            margin: 0;
            font-size: 1.25rem;
            color: #2c3e50;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
            padding: 5px;
            line-height: 1;
        }

        .btn-close:hover {
            color: #2c3e50;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-radius: 0 0 10px 10px;
            background: #f8f9fa;
        }

        /* ===== ACCIONES ===== */
        .acciones {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 75px 15px 15px 15px;
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
                padding: 75px 10px 15px 10px;
            }

            .content-header h1 {
                font-size: 1.5rem;
                margin-top: 0;
            }

            .content-header p {
                font-size: 1rem;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                flex-direction: column;
            }

            .filter-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .admin-table {
                min-width: 800px;
            }

            .acciones {
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            th,
            td {
                padding: 10px 8px;
                font-size: 0.9rem;
            }

            .modal-dialog {
                margin: 20px auto;
                width: 95%;
                max-height: 90vh;
                overflow-y: auto;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 70px 5px 10px 5px;
            }

            .content-header h1 {
                font-size: 1.3rem;
            }

            .content-header p {
                font-size: 12px;
            }

            .no-data {
                padding: 40px 15px;
            }

            .no-data i {
                font-size: 2.5rem;
            }

            .admin-table {
                min-width: 750px;
                font-size: 12px;
            }

            th,
            td {
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

            .rating-display {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>
                    <i class="fas fa-star text-warning"></i>
                    Gestión de Reseñas
                </h1>
                <p>Administra las reseñas de los usuarios sobre destinos turísticos</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="filters-card">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" <?php echo $estado_filter == 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                                <option value="aprobado" <?php echo $estado_filter == 'aprobado' ? 'selected' : ''; ?>>Aprobadas</option>
                                <option value="rechazado" <?php echo $estado_filter == 'rechazado' ? 'selected' : ''; ?>>Rechazadas</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="destino">Destino</label>
                            <select class="form-select" id="destino" name="destino">
                                <option value="">Todos los destinos</option>
                                <?php foreach ($destinos as $destino): ?>
                                    <option value="<?php echo $destino['id']; ?>" <?php echo $destino_filter == $destino['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($destino['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                        <a href="gestion-reseñas.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Limpiar Filtros
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tabla de reseñas -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class="fas fa-list"></i>
                        Lista de Reseñas
                        <span class="badge-counter"><?php echo count($reseñas); ?></span>
                    </h3>
                </div>

                <?php if (empty($reseñas)): ?>
                    <div class="no-data">
                        <i class="fas fa-star"></i>
                        <p>No hay reseñas con los filtros seleccionados</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Destino</th>
                                    <th>Usuario</th>
                                    <th>Calificación</th>
                                    <th>Comentario</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reseñas as $resena):
                                    // Determinar clase del badge según estado
                                    $badge_class = '';
                                    switch ($resena['estado']) {
                                        case 'pendiente':
                                            $badge_class = 'badge-pendiente';
                                            break;
                                        case 'aprobado':
                                            $badge_class = 'badge-aprobado';
                                            break;
                                        case 'rechazado':
                                            $badge_class = 'badge-rechazado';
                                            break;
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <div class="destino-nombre">
                                                <?php echo htmlspecialchars($resena['destino_nombre'] ?? 'Destino no encontrado'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="resena-usuario"><?php echo htmlspecialchars($resena['nombre']); ?></div>
                                            <div class="resena-email"><?php echo htmlspecialchars($resena['email']); ?></div>
                                            <?php if (!empty($resena['telefono'])): ?>
                                                <div class="resena-telefono"><?php echo htmlspecialchars($resena['telefono']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="estrellas-resena">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $resena['calificacion'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted"><?php echo htmlspecialchars($resena['titulo']); ?></small>
                                        </td>
                                        <td>
                                            <div class="resena-comentario">
                                                <?php
                                                $comentario = htmlspecialchars($resena['comentario']);
                                                if (strlen($comentario) > 100) {
                                                    echo substr($comentario, 0, 100) . '...';
                                                } else {
                                                    echo $comentario;
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="resena-fecha">
                                                <?php echo date('d/m/Y', strtotime($resena['fecha_creacion'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-estado <?php echo $badge_class; ?>">
                                                <?php
                                                switch ($resena['estado']) {
                                                    case 'pendiente':
                                                        echo 'Pendiente';
                                                        break;
                                                    case 'aprobado':
                                                        echo 'Aprobado';
                                                        break;
                                                    case 'rechazado':
                                                        echo 'Rechazado';
                                                        break;
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="acciones">
                                                <button class="btn btn-sm btn-info"
                                                    onclick="mostrarDetalleResena(<?php echo $resena['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning"
                                                    onclick="mostrarModalEditar(<?php echo $resena['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger"
                                                    onclick="mostrarModalEliminar(<?php echo $resena['id']; ?>)">
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
        </div>
    </div>

    <!-- Modal para Detalle de Reseña -->
    <div class="modal" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Reseña</h5>
                    <button type="button" class="btn-close" onclick="cerrarModal('detalleModal')">&times;</button>
                </div>
                <div class="modal-body" id="detalleContenido">
                    <!-- Contenido dinámico se cargará aquí -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('detalleModal')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Reseña -->
    <div class="modal" id="editarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gestionar Reseña</h5>
                    <button type="button" class="btn-close" onclick="cerrarModal('editarModal')">&times;</button>
                </div>
                <form method="POST" id="formEditar">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId">

                        <div class="form-group">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado" id="editEstado" required>
                                <option value="pendiente">Pendiente</option>
                                <option value="aprobado">Aprobado</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Respuesta (opcional)</label>
                            <textarea class="form-control" name="respuesta" id="editRespuesta" rows="4"
                                placeholder="Escribe una respuesta para el usuario..."></textarea>
                            <small class="text-muted">Esta respuesta se mostrará públicamente junto a la reseña</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModal('editarModal')">Cancelar</button>
                        <button type="submit" name="actualizar_estado" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Eliminar Reseña -->
    <div class="modal" id="eliminarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" onclick="cerrarModal('eliminarModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de eliminar esta reseña?</p>
                    <p><strong>Título:</strong> <span id="eliminarTitulo"></span></p>
                    <p><strong>Usuario:</strong> <span id="eliminarUsuario"></span></p>
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="formEliminar">
                        <input type="hidden" name="id" id="eliminarId">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModal('eliminarModal')">Cancelar</button>
                        <button type="submit" name="eliminar_resena" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Datos de las reseñas
        const reseñasData = <?php echo json_encode($reseñas); ?>;

        // Función para buscar una reseña por ID
        function buscarResenaPorId(id) {
            return reseñasData.find(r => parseInt(r.id) === parseInt(id));
        }

        // Función para mostrar detalle de reseña
        function mostrarDetalleResena(id) {
            const reseña = buscarResenaPorId(id);
            if (!reseña) {
                alert('Reseña no encontrada');
                return;
            }

            const modal = document.getElementById('detalleModal');
            const contenido = document.getElementById('detalleContenido');

            // Determinar badge según estado
            let badgeClass = '';
            let estadoTexto = '';
            switch (reseña.estado) {
                case 'pendiente':
                    badgeClass = 'badge-pendiente';
                    estadoTexto = 'Pendiente';
                    break;
                case 'aprobado':
                    badgeClass = 'badge-aprobado';
                    estadoTexto = 'Aprobado';
                    break;
                case 'rechazado':
                    badgeClass = 'badge-rechazado';
                    estadoTexto = 'Rechazado';
                    break;
            }

            // Generar estrellas
            let estrellas = '';
            for (let i = 1; i <= 5; i++) {
                estrellas += `<i class="fas fa-star ${i <= reseña.calificacion ? 'text-warning' : 'text-muted'}"></i>`;
            }

            contenido.innerHTML = `
            <div class="mb-4">
                <h6>Información del Usuario:</h6>
                <div class="border rounded p-3 bg-light mb-3">
                    <p class="mb-1"><strong>Nombre:</strong> ${reseña.nombre || 'No especificado'}</p>
                    <p class="mb-1"><strong>Email:</strong> ${reseña.email || 'No especificado'}</p>
                    ${reseña.telefono ? `<p class="mb-1"><strong>Teléfono:</strong> ${reseña.telefono}</p>` : ''}
                </div>
                
                <h6>Detalles de la Reseña:</h6>
                <div class="border rounded p-3 bg-light mb-3">
                    <p class="mb-1"><strong>Destino:</strong> ${reseña.destino_nombre || 'No especificado'}</p>
                    <p class="mb-1"><strong>Calificación:</strong> 
                        ${estrellas}
                        (${reseña.calificacion}/5)
                    </p>
                    <p class="mb-1"><strong>Fecha:</strong> ${new Date(reseña.fecha_creacion).toLocaleDateString('es-ES')}</p>
                    <p class="mb-1"><strong>Estado:</strong> 
                        <span class="badge-estado ${badgeClass}">${estadoTexto}</span>
                    </p>
                </div>
            </div>
            
            <div class="mb-4">
                <h6>Título:</h6>
                <div class="border rounded p-3 bg-light mb-3">${reseña.titulo || 'Sin título'}</div>
            </div>
            
            <div class="mb-4">
                <h6>Comentario:</h6>
                <div class="border rounded p-3 bg-light">${(reseña.comentario || '').replace(/\n/g, '<br>')}</div>
            </div>
            
            ${reseña.respuesta ? `
            <div class="mb-4">
                <h6>Respuesta del equipo:</h6>
                <div class="border rounded p-3 bg-info bg-opacity-10">
                    ${reseña.respuesta.replace(/\n/g, '<br>')}
                </div>
            </div>
            ` : ''}
        `;

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        // Función para mostrar modal de edición
        function mostrarModalEditar(id) {
            const reseña = buscarResenaPorId(id);
            if (!reseña) {
                alert('Reseña no encontrada');
                return;
            }

            document.getElementById('editId').value = reseña.id;
            document.getElementById('editEstado').value = reseña.estado;
            document.getElementById('editRespuesta').value = reseña.respuesta || '';

            const modal = document.getElementById('editarModal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        // Función para mostrar modal de eliminación
        function mostrarModalEliminar(id) {
            const reseña = buscarResenaPorId(id);
            if (!reseña) {
                alert('Reseña no encontrada');
                return;
            }

            document.getElementById('eliminarId').value = reseña.id;
            document.getElementById('eliminarTitulo').textContent = reseña.titulo || 'Sin título';
            document.getElementById('eliminarUsuario').textContent = reseña.nombre;

            const modal = document.getElementById('eliminarModal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        // Función para cerrar modales
        function cerrarModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            const modals = ['detalleModal', 'editarModal', 'eliminarModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && event.target == modal) {
                    cerrarModal(modalId);
                }
            });
        }

        // Cerrar modales con Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModal('detalleModal');
                cerrarModal('editarModal');
                cerrarModal('eliminarModal');
            }
        });

        // Auto-close alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                let valid = true;

                // Check required fields
                form.querySelectorAll('[required]').forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.style.borderColor = '#dc3545';
                        if (!input.classList.contains('error-highlighted')) {
                            input.classList.add('error-highlighted');
                            const errorMsg = document.createElement('div');
                            errorMsg.className = 'text-danger small mt-1';
                            errorMsg.textContent = 'Este campo es requerido';
                            input.parentNode.appendChild(errorMsg);

                            // Remove error on input
                            input.addEventListener('input', function() {
                                this.style.borderColor = '';
                                this.classList.remove('error-highlighted');
                                if (this.nextElementSibling && this.nextElementSibling.className === 'text-danger small mt-1') {
                                    this.nextElementSibling.remove();
                                }
                            }, {
                                once: true
                            });
                        }

                        if (!input.classList.contains('has-focus')) {
                            input.classList.add('has-focus');
                            input.focus();
                        }
                    } else {
                        input.style.borderColor = '';
                        input.classList.remove('error-highlighted');
                        input.classList.remove('has-focus');
                    }
                });

                if (!valid) {
                    e.preventDefault();
                }
            });
        });

        // Confirmation before delete
        document.getElementById('formEliminar')?.addEventListener('submit', function(e) {
            if (!confirm('¿Estás completamente seguro de eliminar esta reseña? Esta acción es irreversible.')) {
                e.preventDefault();
            }
        });

        // Close alerts on click
        document.querySelectorAll('.alert-close').forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });

        // Initialize tooltips if Bootstrap is available
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // Highlight current filter
        const currentEstado = '<?php echo $estado_filter; ?>';
        const currentDestino = '<?php echo $destino_filter; ?>';

        if (currentEstado) {
            document.querySelector(`#estado option[value="${currentEstado}"]`)?.setAttribute('selected', 'selected');
        }

        if (currentDestino) {
            document.querySelector(`#destino option[value="${currentDestino}"]`)?.setAttribute('selected', 'selected');
        }
    </script>
</body>

</html>