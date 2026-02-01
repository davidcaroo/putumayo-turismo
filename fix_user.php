<?php
// fix_user.php - Script para crear usuario correctamente
echo "<h2>Corrigiendo Usuario Admin</h2>";

// Configuración de la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'putumayo_turismo';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Conexión a la base de datos exitosa</p>";
    
    // Verificar si la tabla usuarios existe
    $table_exists = $pdo->query("SHOW TABLES LIKE 'usuarios'")->rowCount() > 0;
    
    if (!$table_exists) {
        echo "<p style='color: red;'>❌ La tabla 'usuarios' no existe. Creándola...</p>";
        
        // Crear tabla usuarios
        $pdo->exec("CREATE TABLE usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            rol ENUM('superadmin', 'admin', 'usuario') DEFAULT 'usuario',
            activo BOOLEAN DEFAULT FALSE,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<p style='color: green;'>✅ Tabla 'usuarios' creada</p>";
    }
    
    // Eliminar usuario existente si existe
    $pdo->exec("DELETE FROM usuarios WHERE email = 'admin@putumayoturismo.com'");
    echo "<p>✅ Usuario existente eliminado (si existía)</p>";
    
    // Crear nuevo usuario con contraseña correcta
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, 'superadmin', TRUE)");
    
    if($stmt->execute(['Administrador Principal', 'admin@putumayoturismo.com', $hashed_password])) {
        echo "<p style='color: green;'>✅ Usuario creado exitosamente</p>";
        echo "<p><strong>Email:</strong> admin@putumayoturismo.com</p>";
        echo "<p><strong>Contraseña:</strong> admin123</p>";
        echo "<p><strong>Hash generado:</strong> " . $hashed_password . "</p>";
        
        // Verificar que la contraseña funciona con PHP
        if(password_verify('admin123', $hashed_password)) {
            echo "<p style='color: green;'>✅ Contraseña verificada correctamente con PHP</p>";
        } else {
            echo "<p style='color: red;'>❌ Error en la verificación de contraseña</p>";
        }
        
        // Mostrar usuarios existentes
        echo "<h3>Usuarios en la base de datos:</h3>";
        $users = $pdo->query("SELECT id, nombre, email, rol, activo FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
        foreach($users as $user) {
            echo "<p>- {$user['nombre']} ({$user['email']}) - Rol: {$user['rol']} - Activo: " . ($user['activo'] ? 'Sí' : 'No') . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Error al crear usuario</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error de base de datos: " . $e->getMessage() . "</p>";
    
    // Intentar crear la base de datos si no existe
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<p>Intentando crear la base de datos...</p>";
        try {
            $pdo_temp = new PDO("mysql:host=$host", $user, $pass);
            $pdo_temp->exec("CREATE DATABASE $dbname");
            echo "<p style='color: green;'>✅ Base de datos creada. Por favor ejecuta este script nuevamente.</p>";
        } catch (PDOException $e2) {
            echo "<p style='color: red;'>❌ Error creando base de datos: " . $e2->getMessage() . "</p>";
        }
    }
}
?>