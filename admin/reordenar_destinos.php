<?php
// reordenar_destinos.php
include '../includes/config.php';

// Verificar permisos
if(!isLoggedIn() || (!hasRole('superadmin') && !hasRole('admin'))) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    // Reordenar secuencialmente
    $stmt = $pdo->query("SET @count = 0");
    $stmt = $pdo->query("UPDATE destinos SET orden = (@count := @count + 1) ORDER BY orden, nombre");
    
    echo json_encode(['success' => true, 'message' => 'Destinos reordenados']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}