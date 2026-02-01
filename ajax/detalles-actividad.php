<?php
// admin/ajax/detalles-actividad.php
include '../includes/config.php';

if(!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID no especificado']);
    exit;
}

$actividad_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM actividades WHERE id = ?");
    $stmt->execute([$actividad_id]);
    $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($actividad) {
        echo json_encode(['success' => true, 'actividad' => $actividad]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Actividad no encontrada']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>