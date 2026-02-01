<?php
// admin/eventos.php
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Verificar permisos
if(!isLoggedIn() || (!hasRole('superadmin') && !hasRole('admin') && !hasRole('editor'))) {
    header('Location: ../login.php');
    exit;
}

// Obtener el mes actual para filtrar
$mes_actual = date('m');
$anio_actual = date('Y');

// Obtener mes y año del filtro si existen
$mes_filtro = isset($_GET['mes']) ? intval($_GET['mes']) : $mes_actual;
$anio_filtro = isset($_GET['anio']) ? intval($_GET['anio']) : $anio_actual;

// Validar mes (1-12)
if($mes_filtro < 1 || $mes_filtro > 12) {
    $mes_filtro = $mes_actual;
}

// Variables para el formulario
$evento_id = isset($_GET['editar']) ? intval($_GET['editar']) : 0;
$evento = null;
$categorias = [];

// Obtener categorías
try {
    $stmt = $pdo->query("SELECT * FROM categorias_eventos WHERE activo = 1 ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    error_log("Error obteniendo categorías: " . $e->getMessage());
}

// Si estamos editando, obtener el evento
if($evento_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
        $stmt->execute([$evento_id]);
        $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        error_log("Error obteniendo evento: " . $e->getMessage());
    }
}

// Procesar formularios
$mensaje = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Subir imagen si existe
        $imagen_nombre = $evento['imagen'] ?? '';
        
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $imagen = $_FILES['imagen'];
            $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if(in_array($extension, $extensiones_permitidas)) {
                if($imagen['size'] <= 5 * 1024 * 1024) { // 5MB
                    $imagen_nombre = uniqid() . '.' . $extension;
                    $upload_dir = '../uploads/eventos/';
                    
                    if(!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    if(move_uploaded_file($imagen['tmp_name'], $upload_dir . $imagen_nombre)) {
                        // Eliminar imagen anterior si existe
                        if(isset($evento['imagen']) && $evento['imagen'] && file_exists($upload_dir . $evento['imagen'])) {
                            unlink($upload_dir . $evento['imagen']);
                        }
                    } else {
                        $error = "Error al subir la imagen";
                    }
                } else {
                    $error = "La imagen es demasiado grande (máx 5MB)";
                }
            } else {
                $error = "Formato de imagen no permitido";
            }
        }
        
        if(empty($error)) {
            $datos = [
                'titulo' => trim($_POST['titulo']),
                'descripcion' => trim($_POST['descripcion']),
                'descripcion_corta' => trim($_POST['descripcion_corta']),
                'fecha_inicio' => trim($_POST['fecha_inicio']),
                'fecha_fin' => trim($_POST['fecha_fin']),
                'ubicacion' => trim($_POST['ubicacion']),
                'tipo_evento' => trim($_POST['tipo_evento']),
                'precio' => floatval($_POST['precio']),
                'capacidad_max' => intval($_POST['capacidad_max']),
                'categoria_id' => intval($_POST['categoria_id']),
                'destacado' => isset($_POST['destacado']) ? 1 : 0,
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'imagen' => $imagen_nombre
            ];
            
            if($evento_id > 0) {
                // Actualizar evento existente
                $sql = "UPDATE eventos SET 
                        titulo = :titulo,
                        descripcion = :descripcion,
                        descripcion_corta = :descripcion_corta,
                        fecha_inicio = :fecha_inicio,
                        fecha_fin = :fecha_fin,
                        ubicacion = :ubicacion,
                        tipo_evento = :tipo_evento,
                        precio = :precio,
                        capacidad_max = :capacidad_max,
                        categoria_id = :categoria_id,
                        destacado = :destacado,
                        activo = :activo,
                        imagen = :imagen,
                        updated_at = NOW()
                        WHERE id = :id";
                
                $datos['id'] = $evento_id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($datos);
                
                $mensaje = "Evento actualizado correctamente";
                logActivity($_SESSION['user_id'] ?? $_SESSION['usuario_id'], 'update_evento', 'Evento actualizado: ' . $datos['titulo']);
            } else {
                // Crear nuevo evento
                $sql = "INSERT INTO eventos (
                    titulo, descripcion, descripcion_corta, fecha_inicio, fecha_fin,
                    ubicacion, tipo_evento, precio, capacidad_max, categoria_id,
                    destacado, activo, imagen, created_at, updated_at
                ) VALUES (
                    :titulo, :descripcion, :descripcion_corta, :fecha_inicio, :fecha_fin,
                    :ubicacion, :tipo_evento, :precio, :capacidad_max, :categoria_id,
                    :destacado, :activo, :imagen, NOW(), NOW()
                )";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($datos);
                
                $evento_id = $pdo->lastInsertId();
                $mensaje = "Evento creado correctamente";
                logActivity($_SESSION['user_id'] ?? $_SESSION['usuario_id'], 'create_evento', 'Evento creado: ' . $datos['titulo']);
            }
            
            // Redirigir para evitar reenvío del formulario
            header("Location: eventos.php?mes=$mes_filtro&anio=$anio_filtro&mensaje=" . urlencode($mensaje));
            exit;
        }
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Eliminar evento
if(isset($_GET['eliminar'])) {
    $eliminar_id = intval($_GET['eliminar']);
    try {
        // Primero obtener la imagen para eliminarla
        $stmt = $pdo->prepare("SELECT imagen FROM eventos WHERE id = ?");
        $stmt->execute([$eliminar_id]);
        $evento_eliminar = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Eliminar la imagen si existe
        if($evento_eliminar && $evento_eliminar['imagen']) {
            $imagen_path = '../uploads/eventos/' . $evento_eliminar['imagen'];
            if(file_exists($imagen_path)) {
                unlink($imagen_path);
            }
        }
        
        // Eliminar el evento
        $stmt = $pdo->prepare("DELETE FROM eventos WHERE id = ?");
        $stmt->execute([$eliminar_id]);
        
        $mensaje = "Evento eliminado correctamente";
        logActivity($_SESSION['user_id'] ?? $_SESSION['usuario_id'], 'delete_evento', 'Evento eliminado ID: ' . $eliminar_id);
        
        header("Location: eventos.php?mes=$mes_filtro&anio=$anio_filtro&mensaje=" . urlencode($mensaje));
        exit;
    } catch(Exception $e) {
        $error = "Error al eliminar evento: " . $e->getMessage();
    }
}

// Obtener eventos del mes seleccionado
try {
    $fecha_inicio = "$anio_filtro-$mes_filtro-01";
    $fecha_fin = date('Y-m-t', strtotime($fecha_inicio));
    
    $sql = "SELECT e.*, c.nombre as categoria_nombre, c.color as categoria_color, c.icono as categoria_icono 
            FROM eventos e 
            LEFT JOIN categorias_eventos c ON e.categoria_id = c.id 
            WHERE DATE(e.fecha_inicio) BETWEEN ? AND ?
            OR DATE(e.fecha_fin) BETWEEN ? AND ?
            ORDER BY e.fecha_inicio ASC, e.destacado DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    error_log("Error obteniendo eventos: " . $e->getMessage());
    $eventos = [];
}

// Obtener eventos destacados (para la vista previa)
try {
    $sql_destacados = "SELECT * FROM eventos WHERE destacado = 1 AND activo = 1 AND fecha_inicio >= CURDATE() ORDER BY fecha_inicio LIMIT 3";
    $stmt_destacados = $pdo->query($sql_destacados);
    $eventos_destacados = $stmt_destacados->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $eventos_destacados = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Eventos - Administración</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2E8B57;
            --secondary-color: #267349;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), #1a472a);
            color: white;
            min-height: 100vh;
            position: fixed;
            width: 250px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }
        
        .evento-card {
            border-left: 5px solid var(--primary-color);
        }
        
        .evento-destacado {
            border-left: 5px solid #ffc107;
            background: linear-gradient(135deg, #fff8e1, #ffffff);
        }
        
        .badge-categoria {
            background: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .imagen-preview {
            max-width: 200px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .calendar-day {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .day-number {
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .event-item {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
        }
        
        .event-time {
            font-size: 0.85rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .month-selector {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .calendar-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="sidebar col-md-3 col-lg-2 d-md-block">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-calendar-alt me-2"></i>Eventos</h4>
                        <small>Gestión de Eventos</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="eventos.php">
                                <i class="fas fa-calendar me-2"></i>Calendario
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#agregar-evento" data-bs-toggle="modal" data-bs-target="#eventoModal">
                                <i class="fas fa-plus-circle me-2"></i>Nuevo Evento
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categorias-eventos.php">
                                <i class="fas fa-tags me-2"></i>Categorías
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mt-4 text-center">
                        <small class="text-white-50">© <?php echo date('Y'); ?> Putumayo Turismo</small>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="main-content col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Encabezado -->
                <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
                    <h2><i class="fas fa-calendar-alt me-2" style="color: var(--primary-color);"></i>Gestión de Eventos</h2>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventoModal">
                            <i class="fas fa-plus me-2"></i>Nuevo Evento
                        </button>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if(isset($_GET['mensaje'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['mensaje']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Selector de Mes -->
                <div class="month-selector">
                    <h4><i class="fas fa-calendar me-2"></i>Selecciona un Mes</h4>
                    <form method="GET" class="row g-3 align-items-center mt-3">
                        <div class="col-md-4">
                            <label class="form-label">Mes</label>
                            <select class="form-select" name="mes">
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == $mes_filtro ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Año</label>
                            <select class="form-select" name="anio">
                                <?php for($i = 2023; $i <= 2030; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == $anio_filtro ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filtrar
                            </button>
                            <a href="eventos.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-sync me-2"></i>Actual
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Estadísticas Rápidas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-calendar-day me-2"></i>Este Mes</h5>
                                <h3 class="mb-0"><?php echo count($eventos); ?></h3>
                                <small>Eventos programados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-star me-2"></i>Destacados</h5>
                                <h3 class="mb-0"><?php echo count(array_filter($eventos, fn($e) => $e['destacado'])); ?></h3>
                                <small>Eventos destacados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-users me-2"></i>Capacidad</h5>
                                <h3 class="mb-0"><?php echo array_sum(array_column($eventos, 'capacidad_max')); ?></h3>
                                <small>Cupos totales</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-ticket-alt me-2"></i>Activos</h5>
                                <h3 class="mb-0"><?php echo count(array_filter($eventos, fn($e) => $e['activo'])); ?></h3>
                                <small>Eventos activos</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendario de Eventos -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-week me-2"></i>
                            Calendario de Eventos - <?php echo date('F Y', strtotime("$anio_filtro-$mes_filtro-01")); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($eventos)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay eventos programados para este mes</h5>
                            <p class="text-muted">Crea nuevos eventos para empezar a llenar tu calendario</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventoModal">
                                <i class="fas fa-plus me-2"></i>Crear Primer Evento
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="calendar-grid">
                            <?php
                            // Agrupar eventos por día
                            $eventos_por_dia = [];
                            foreach($eventos as $evento) {
                                $fecha = date('Y-m-d', strtotime($evento['fecha_inicio']));
                                if(!isset($eventos_por_dia[$fecha])) {
                                    $eventos_por_dia[$fecha] = [];
                                }
                                $eventos_por_dia[$fecha][] = $evento;
                            }
                            
                            // Mostrar días del mes que tienen eventos
                            foreach($eventos_por_dia as $fecha => $eventos_dia):
                                $dia_numero = date('d', strtotime($fecha));
                                $dia_nombre = date('l', strtotime($fecha));
                            ?>
                            <div class="calendar-day">
                                <div class="day-header">
                                    <div>
                                        <h5 class="mb-0"><?php echo $dia_nombre; ?></h5>
                                        <small class="text-muted"><?php echo date('d M, Y', strtotime($fecha)); ?></small>
                                    </div>
                                    <div class="day-number"><?php echo $dia_numero; ?></div>
                                </div>
                                
                                <?php foreach($eventos_dia as $ev): ?>
                                <div class="event-item <?php echo $ev['destacado'] ? 'evento-destacado' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($ev['titulo']); ?></h6>
                                            <?php if($ev['destacado']): ?>
                                            <span class="badge bg-warning text-dark me-2">
                                                <i class="fas fa-star"></i> Destacado
                                            </span>
                                            <?php endif; ?>
                                            <span class="badge-categoria" style="background: <?php echo $ev['categoria_color'] ?? '#2E8B57'; ?>;">
                                                <i class="<?php echo $ev['categoria_icono'] ?? 'fas fa-calendar'; ?>"></i>
                                                <?php echo htmlspecialchars($ev['categoria_nombre'] ?? 'General'); ?>
                                            </span>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link text-dark" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="?editar=<?php echo $ev['id']; ?>&mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>">
                                                        <i class="fas fa-edit me-2"></i>Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#" onclick="confirmarEliminar(<?php echo $ev['id']; ?>)">
                                                        <i class="fas fa-trash me-2"></i>Eliminar
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <p class="text-muted small mb-2">
                                        <?php echo htmlspecialchars($ev['descripcion_corta'] ?? substr($ev['descripcion'] ?? '', 0, 100) . '...'); ?>
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="event-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('H:i', strtotime($ev['fecha_inicio'])); ?> - 
                                            <?php echo date('H:i', strtotime($ev['fecha_fin'])); ?>
                                        </div>
                                        <div class="event-time">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($ev['ubicacion'] ?? 'Por definir'); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if($ev['imagen']): ?>
                                    <div class="mt-2">
                                        <img src="../uploads/eventos/<?php echo htmlspecialchars($ev['imagen']); ?>" 
                                             alt="<?php echo htmlspecialchars($ev['titulo']); ?>" 
                                             class="img-fluid rounded" style="max-height: 100px; object-fit: cover;">
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lista de Eventos (tabla) -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Lista de Eventos - <?php echo date('F Y', strtotime("$anio_filtro-$mes_filtro-01")); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Evento</th>
                                        <th>Categoría</th>
                                        <th>Ubicación</th>
                                        <th>Capacidad</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($eventos as $ev): ?>
                                    <tr class="<?php echo $ev['destacado'] ? 'table-warning' : ''; ?>">
                                        <td>
                                            <strong><?php echo date('d M', strtotime($ev['fecha_inicio'])); ?></strong><br>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($ev['fecha_inicio'])); ?> - <?php echo date('H:i', strtotime($ev['fecha_fin'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if($ev['imagen']): ?>
                                                <img src="../uploads/eventos/<?php echo htmlspecialchars($ev['imagen']); ?>" 
                                                     alt="<?php echo htmlspecialchars($ev['titulo']); ?>" 
                                                     class="rounded me-3" width="60" height="40" style="object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($ev['titulo']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($ev['descripcion_corta'] ?? $ev['descripcion'] ?? '', 0, 50)); ?>...</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: <?php echo $ev['categoria_color'] ?? '#2E8B57'; ?>;">
                                                <i class="<?php echo $ev['categoria_icono'] ?? 'fas fa-calendar'; ?>"></i>
                                                <?php echo htmlspecialchars($ev['categoria_nombre'] ?? 'General'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($ev['ubicacion'] ?? 'Por definir'); ?></td>
                                        <td>
                                            <?php if($ev['capacidad_max'] > 0): ?>
                                            <div class="progress" style="height: 20px;">
                                                <?php 
                                                $porcentaje = $ev['inscripciones_actual'] > 0 ? ($ev['inscripciones_actual'] / $ev['capacidad_max']) * 100 : 0;
                                                $clase_progress = $porcentaje >= 90 ? 'bg-danger' : ($porcentaje >= 70 ? 'bg-warning' : 'bg-success');
                                                ?>
                                                <div class="progress-bar <?php echo $clase_progress; ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo min($porcentaje, 100); ?>%"
                                                     aria-valuenow="<?php echo $porcentaje; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?php echo $ev['inscripciones_actual']; ?>/<?php echo $ev['capacidad_max']; ?>
                                                </div>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted">Ilimitado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($ev['activo']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Activo
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-pause"></i> Inactivo
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?editar=<?php echo $ev['id']; ?>&mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" onclick="confirmarEliminar(<?php echo $ev['id']; ?>)" 
                                                   class="btn btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <a href="../evento-detalle.php?id=<?php echo $ev['id']; ?>" 
                                                   target="_blank" 
                                                   class="btn btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para crear/editar evento -->
    <div class="modal fade" id="eventoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-plus me-2"></i>
                        <?php echo $evento_id > 0 ? 'Editar Evento' : 'Nuevo Evento'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Título del Evento *</label>
                                <input type="text" class="form-control" name="titulo" 
                                       value="<?php echo htmlspecialchars($evento['titulo'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" name="categoria_id">
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo (($evento['categoria_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>
                                            style="color: <?php echo $cat['color']; ?>;">
                                        <i class="<?php echo $cat['icono']; ?>"></i> <?php echo htmlspecialchars($cat['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha y Hora de Inicio *</label>
                                <input type="datetime-local" class="form-control" name="fecha_inicio" 
                                       value="<?php echo isset($evento['fecha_inicio']) ? date('Y-m-d\TH:i', strtotime($evento['fecha_inicio'])) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha y Hora de Fin *</label>
                                <input type="datetime-local" class="form-control" name="fecha_fin" 
                                       value="<?php echo isset($evento['fecha_fin']) ? date('Y-m-d\TH:i', strtotime($evento['fecha_fin'])) : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Descripción Corta *</label>
                                <textarea class="form-control" name="descripcion_corta" rows="2" 
                                          placeholder="Breve descripción para listados y tarjetas..." required><?php echo htmlspecialchars($evento['descripcion_corta'] ?? ''); ?></textarea>
                                <small class="text-muted">Máximo 500 caracteres</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Descripción Completa</label>
                                <textarea class="form-control" name="descripcion" rows="4" 
                                          placeholder="Descripción detallada del evento..."><?php echo htmlspecialchars($evento['descripcion'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ubicación</label>
                                <input type="text" class="form-control" name="ubicacion" 
                                       value="<?php echo htmlspecialchars($evento['ubicacion'] ?? ''); ?>"
                                       placeholder="Ej: Parque Principal de Mocoa">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Evento</label>
                                <select class="form-select" name="tipo_evento">
                                    <option value="">Seleccionar tipo</option>
                                    <option value="tour" <?php echo (($evento['tipo_evento'] ?? '') == 'tour') ? 'selected' : ''; ?>>Tour</option>
                                    <option value="taller" <?php echo (($evento['tipo_evento'] ?? '') == 'taller') ? 'selected' : ''; ?>>Taller</option>
                                    <option value="conferencia" <?php echo (($evento['tipo_evento'] ?? '') == 'conferencia') ? 'selected' : ''; ?>>Conferencia</option>
                                    <option value="festival" <?php echo (($evento['tipo_evento'] ?? '') == 'festival') ? 'selected' : ''; ?>>Festival</option>
                                    <option value="exposicion" <?php echo (($evento['tipo_evento'] ?? '') == 'exposicion') ? 'selected' : ''; ?>>Exposición</option>
                                    <option value="deporte" <?php echo (($evento['tipo_evento'] ?? '') == 'deporte') ? 'selected' : ''; ?>>Deporte</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Precio ($)</label>
                                <input type="number" class="form-control" name="precio" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($evento['precio'] ?? '0.00'); ?>">
                                <small class="text-muted">0.00 para eventos gratuitos</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Capacidad Máxima</label>
                                <input type="number" class="form-control" name="capacidad_max" min="0"
                                       value="<?php echo htmlspecialchars($evento['capacidad_max'] ?? '0'); ?>">
                                <small class="text-muted">0 para capacidad ilimitada</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Imagen del Evento</label>
                                <input type="file" class="form-control" name="imagen" accept=".jpg,.jpeg,.png,.gif,.webp">
                                <?php if(isset($evento['imagen']) && $evento['imagen']): ?>
                                <div class="mt-2">
                                    <img src="../uploads/eventos/<?php echo htmlspecialchars($evento['imagen']); ?>" 
                                         class="imagen-preview" alt="Imagen actual">
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="destacado" 
                                           id="destacado" <?php echo (isset($evento['destacado']) && $evento['destacado']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="destacado">
                                        <i class="fas fa-star text-warning"></i> Evento Destacado
                                    </label>
                                    <small class="text-muted d-block">Aparecerá en posición destacada</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="activo" 
                                           id="activo" <?php echo (!isset($evento['activo']) || $evento['activo']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activo">
                                        <i class="fas fa-toggle-on text-success"></i> Evento Activo
                                    </label>
                                    <small class="text-muted d-block">Visible para el público</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            <?php echo $evento_id > 0 ? 'Actualizar Evento' : 'Crear Evento'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-abrir modal si estamos editando
        <?php if($evento_id > 0): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('eventoModal'));
            modal.show();
        });
        <?php endif; ?>
        
        // Confirmar eliminación
        function confirmarEliminar(id) {
            if(confirm('¿Estás seguro de eliminar este evento? Esta acción no se puede deshacer.')) {
                window.location.href = '?eliminar=' + id + '&mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>';
            }
        }
        
        // Validación de fechas
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInicio = document.querySelector('input[name="fecha_inicio"]');
            const fechaFin = document.querySelector('input[name="fecha_fin"]');
            
            if(fechaInicio && fechaFin) {
                fechaInicio.addEventListener('change', function() {
                    fechaFin.min = this.value;
                });
                
                fechaFin.addEventListener('change', function() {
                    if(this.value < fechaInicio.value) {
                        alert('La fecha de fin no puede ser anterior a la fecha de inicio');
                        this.value = fechaInicio.value;
                    }
                });
            }
        });
    </script>
</body>
</html>