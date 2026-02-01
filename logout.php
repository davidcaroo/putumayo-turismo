<?php
include 'includes/config.php';

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redirigir al inicio
header('Location: index.php');
exit;
?>