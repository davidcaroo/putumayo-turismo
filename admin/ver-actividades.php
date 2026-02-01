<?php
include '../includes/config.php';
include '../includes/functions.php';

if(!isLoggedIn() || (!hasRole('superadmin') && !hasRole('admin'))) {
    header('Location: ../login.php');
    exit;
}

$destino_id = $_GET['destino_id'] ?? 0;

// Obtener información del destino
$destino = obtenerDestinoPorId($destino_id);

// Obtener actividades asociadas
$stmt = $pdo->prepare("SELECT * FROM actividades WHERE destino_id = ? ORDER BY nombre");
$stmt->execute([$destino_id]);
$actividades = $stmt->fetchAll();

// Procesar eliminación de actividad
if(isset($_POST['eliminar_actividad'])) {
    $actividad_id = $_POST['actividad_id'] ?? 0;
    
    if($actividad_id) {
        // Obtener info de la actividad antes de eliminar
        $stmt = $pdo->prepare("SELECT nombre FROM actividades WHERE id = ?");
        $stmt->execute([$actividad_id]);
        $actividad = $stmt->fetch();
        
        // Eliminar la actividad
        $stmt = $pdo->prepare("DELETE FROM actividades WHERE id = ?");
        if($stmt->execute([$actividad_id])) {
            // Registrar actividad
            registrarActividad($_SESSION['usuario_id'], "Actividad eliminada: {$actividad['nombre']}");
            
            // Redirigir de vuelta
            header("Location: ver_actividades.php?destino_id=$destino_id&success=actividad_eliminada");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Actividades</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-secondary {
            background: #7f8c8d;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .instructions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-list"></i> Actividades del Destino</h1>
            <p><strong>Destino:</strong> <?php echo htmlspecialchars($destino['nombre'] ?? 'No encontrado'); ?></p>
            <p><strong>ID:</strong> <?php echo $destino_id; ?> | 
               <strong>Ubicación:</strong> <?php echo htmlspecialchars($destino['ubicacion'] ?? ''); ?></p>
        </div>
        
        <?php if(isset($_GET['success']) && $_GET['success'] == 'actividad_eliminada'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Actividad eliminada exitosamente.
        </div>
        <?php endif; ?>
        
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Instrucciones:</h3>
            <ol>
                <li>Para eliminar el destino, primero debes eliminar todas las actividades asociadas.</li>
                <li>Una vez que no haya actividades, podrás eliminar el destino desde la página principal.</li>
                <li>Otra opción es editar las actividades para cambiar su destino asociado.</li>
            </ol>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="gestion_destinos.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Destinos
            </a>
            
            <div>
                <span class="btn btn-info">
                    <i class="fas fa-chart-bar"></i> Total de actividades: <?php echo count($actividades); ?>
                </span>
            </div>
        </div>
        
        <?php if(empty($actividades)): ?>
        <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 5px;">
            <h3><i class="fas fa-check-circle" style="color: #28a745;"></i> No hay actividades asociadas</h3>
            <p>Este destino no tiene actividades asociadas. Ahora puedes eliminarlo.</p>
            <a href="gestion_destinos.php" class="btn btn-primary">
                <i class="fas fa-trash"></i> Eliminar Destino
            </a>
        </div>
        <?php else: ?>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Duración</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($actividades as $actividad): ?>
                <tr>
                    <td><?php echo $actividad['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($actividad['nombre']); ?></strong></td>
                    <td><?php echo htmlspecialchars(substr($actividad['descripcion'] ?? '', 0, 100)) . '...'; ?></td>
                    <td>$<?php echo number_format($actividad['precio'], 0); ?></td>
                    <td><?php echo htmlspecialchars($actividad['duracion']); ?></td>
                    <td>
                        <span style="padding: 3px 8px; border-radius: 3px; font-size: 12px; background: <?php echo $actividad['activo'] ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $actividad['activo'] ? '#155724' : '#721c24'; ?>;">
                            <?php echo $actividad['activo'] ? 'Activa' : 'Inactiva'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-danger" 
                                    onclick="confirmarEliminacionActividad(<?php echo $actividad['id']; ?>, '<?php echo htmlspecialchars(addslashes($actividad['nombre'])); ?>')">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                            <a href="#" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Modal de confirmación para eliminar actividad -->
        <div id="modalEliminarActividad" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
            <div style="background: white; margin: 10% auto; padding: 20px; width: 80%; max-width: 500px; border-radius: 10px;">
                <h3><i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i> Confirmar Eliminación</h3>
                <p id="mensajeEliminarActividad">¿Está seguro de eliminar esta actividad?</p>
                <form method="POST" id="formEliminarActividad">
                    <input type="hidden" name="eliminar_actividad" value="1">
                    <input type="hidden" id="actividad_id_eliminar" name="actividad_id" value="">
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalActividad()">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar Actividad</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
    
    <script>
        function confirmarEliminacionActividad(id, nombre) {
            document.getElementById('actividad_id_eliminar').value = id;
            document.getElementById('mensajeEliminarActividad').innerHTML = 
                '¿Está seguro de eliminar la actividad: <strong>' + nombre + '</strong>?<br>' +
                '<small>Esta acción no se puede deshacer.</small>';
            document.getElementById('modalEliminarActividad').style.display = 'block';
        }
        
        function cerrarModalActividad() {
            document.getElementById('modalEliminarActividad').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalEliminarActividad');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>