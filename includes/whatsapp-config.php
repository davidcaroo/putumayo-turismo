<?php
// includes/whatsapp-config.php

class WhatsAppConfig {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Obtener todos los asesores activos
    public function getAsesoresActivos() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM whatsapp_asesores 
                WHERE activo = 1 
                ORDER BY orden ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            error_log("Error obteniendo asesores WhatsApp: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener configuración general de WhatsApp
    public function getConfig() {
        $config = [];
        
        // Configuración por defecto
        $defaults = [
            'whatsapp_titulo' => 'Chat con Asesores',
            'whatsapp_descripcion' => 'Selecciona un asesor para chatear',
            'whatsapp_mensaje_default' => 'Hola, estoy interesado en información sobre turismo',
            'whatsapp_color_primario' => '#25D366',
            'whatsapp_color_secundario' => '#128C7E',
            'whatsapp_mostrar_horarios' => '1',
            'whatsapp_mostrar_especialidades' => '1',
            'whatsapp_posicion' => 'derecha', // derecha, izquierda
            'whatsapp_auto_abrir' => '0'
        ];
        
        // Obtener configuración de la BD
        foreach ($defaults as $key => $default) {
            $config[$key] = $this->getConfigValue($key, $default);
        }
        
        return $config;
    }
    
    private function getConfigValue($key, $default = '') {
        try {
            $stmt = $this->pdo->prepare("SELECT valor FROM configuracion WHERE config_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['valor'] : $default;
        } catch(Exception $e) {
            return $default;
        }
    }
    
    // Formatear número de WhatsApp
    public function formatWhatsAppNumber($number) {
        // Eliminar todo excepto números y +
        $clean = preg_replace('/[^0-9+]/', '', $number);
        
        // Si no empieza con +, agregar código de país por defecto (Colombia)
        if (substr($clean, 0, 1) !== '+') {
            $clean = '+57' . ltrim($clean, '0');
        }
        
        return $clean;
    }
    
    // Crear URL de WhatsApp
    public function createWhatsAppURL($number, $message = '') {
        $cleanNumber = $this->formatWhatsAppNumber($number);
        $encodedMessage = urlencode($message);
        return "https://wa.me/{$cleanNumber}?text={$encodedMessage}";
    }
}
?>