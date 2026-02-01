<?php
// includes/config.php - CORREGIDO

// Verificar si la sesión ya está iniciada antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'putumayo_turismo');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración de URLs y rutas
define('BASE_URL', 'http://localhost/putumayo_tourism/');
define('UPLOADS_URL', BASE_URL . 'uploads/');
define('UPLOADS_PATH', __DIR__ . '/../uploads/');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Incluir funciones
require_once __DIR__ . '/functions.php';
?>