<?php
include '../includes/config.php';

header('Content-Type: application/json');

if(isset($_GET['destino_id'])) {
    $destino_id = $_GET['destino_id'];
    
    $stmt = $pdo->prepare("SELECT id, nombre, precio FROM actividades WHERE destino_id = ? AND activo = 1 ORDER BY nombre");
    $stmt->execute([$destino_id]);
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($actividades);
} else {
    echo json_encode([]);
}
?>