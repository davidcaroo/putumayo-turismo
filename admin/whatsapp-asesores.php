<?php
// admin/whatsapp-asesores.php
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Verificar permisos
if(!isLoggedIn() || (!hasRole('superadmin') && !hasRole('admin'))) {
    header('Location: ../login.php');
    exit;
}

// Procesar acciones
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_asesor'])) {
            $stmt = $pdo->prepare("
                INSERT INTO whatsapp_asesores 
                (nombre, numero_whatsapp, cargo, especialidad, horario, avatar, orden, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['nombre'],
                $_POST['numero_whatsapp'],
                $_POST['cargo'],
                $_POST['especialidad'],
                $_POST['horario'],
                $_POST['avatar'] ?? '',
                $_POST['orden'] ?? 0,
                isset($_POST['activo']) ? 1 : 0
            ]);
            $message = "Asesor agregado correctamente";
        }
        
        if (isset($_POST['update_asesor'])) {
            $stmt = $pdo->prepare("
                UPDATE whatsapp_asesores 
                SET nombre = ?, numero_whatsapp = ?, cargo = ?, especialidad = ?, 
                    horario = ?, avatar = ?, orden = ?, activo = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['nombre'],
                $_POST['numero_whatsapp'],
                $_POST['cargo'],
                $_POST['especialidad'],
                $_POST['horario'],
                $_POST['avatar'] ?? '',
                $_POST['orden'] ?? 0,
                isset($_POST['activo']) ? 1 : 0,
                $_POST['id']
            ]);
            $message = "Asesor actualizado correctamente";
        }
        
        if (isset($_POST['delete_asesor'])) {
            $stmt = $pdo->prepare("DELETE FROM whatsapp_asesores WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $message = "Asesor eliminado correctamente";
        }
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Obtener asesores
$stmt = $pdo->query("SELECT * FROM whatsapp_asesores ORDER BY orden ASC");
$asesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asesores WhatsApp</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h2 class="mb-4">
            <i class="fab fa-whatsapp text-success"></i> 
            Gestión de Asesores WhatsApp
        </h2>
        
        <?php if($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Agregar Nuevo Asesor</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Número WhatsApp</label>
                            <input type="text" class="form-control" name="numero_whatsapp" 
                                   placeholder="+573001234567" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cargo</label>
                            <input type="text" class="form-control" name="cargo">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Especialidad</label>
                            <input type="text" class="form-control" name="especialidad">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Horario</label>
                            <input type="text" class="form-control" name="horario" 
                                   placeholder="Lun-Vie: 8am-6pm">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Avatar URL</label>
                            <input type="url" class="form-control" name="avatar" 
                                   placeholder="https://ejemplo.com/avatar.jpg">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Orden</label>
                            <input type="number" class="form-control" name="orden" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo" checked>
                                <label class="form-check-label" for="activo">Activo</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="add_asesor" class="btn btn-success">
                        <i class="fas fa-plus"></i> Agregar Asesor
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Asesores Existentes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Avatar</th>
                                <th>Nombre</th>
                                <th>WhatsApp</th>
                                <th>Cargo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($asesores as $asesor): ?>
                            <tr>
                                <td><?php echo $asesor['orden']; ?></td>
                                <td>
                                    <?php if($asesor['avatar']): ?>
                                    <img src="<?php echo htmlspecialchars($asesor['avatar']); ?>" 
                                         alt="Avatar" width="40" height="40" class="rounded-circle">
                                    <?php else: ?>
                                    <div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <?php echo substr($asesor['nombre'], 0, 2); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($asesor['nombre']); ?></strong>
                                    <?php if($asesor['especialidad']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($asesor['especialidad']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9+]/', '', $asesor['numero_whatsapp']); ?>" 
                                       target="_blank">
                                        <?php echo htmlspecialchars($asesor['numero_whatsapp']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($asesor['cargo']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $asesor['activo'] ? 'success' : 'danger'; ?>">
                                        <?php echo $asesor['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?php echo $asesor['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('¿Eliminar este asesor?')">
                                        <input type="hidden" name="id" value="<?php echo $asesor['id']; ?>">
                                        <button type="submit" name="delete_asesor" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    
                                    <!-- Modal de edición -->
                                    <div class="modal fade" id="editModal<?php echo $asesor['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar Asesor</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id" value="<?php echo $asesor['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nombre</label>
                                                            <input type="text" class="form-control" name="nombre" 
                                                                   value="<?php echo htmlspecialchars($asesor['nombre']); ?>" required>
                                                        </div>
                                                        <!-- Agregar más campos aquí -->
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" name="update_asesor" class="btn btn-primary">Guardar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="configuracion.php#social" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Configurar Chatbot
            </a>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>