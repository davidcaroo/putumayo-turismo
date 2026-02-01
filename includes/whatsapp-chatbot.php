<?php
// includes/whatsapp-chatbot.php

// Incluir configuración
require_once 'whatsapp-config.php';

class WhatsAppChatbot {
    private $config;
    private $asesores;
    
    public function __construct($pdo) {
        $whatsappConfig = new WhatsAppConfig($pdo);
        $this->config = $whatsappConfig->getConfig();
        $this->asesores = $whatsappConfig->getAsesoresActivos();
    }
    
    // Renderizar el chatbot
    public function render() {
        if (empty($this->asesores)) {
            return ''; // No mostrar si no hay asesores
        }
        
        $positionClass = $this->config['whatsapp_posicion'] === 'izquierda' ? 'left-position' : 'right-position';
        $autoOpen = $this->config['whatsapp_auto_abrir'] === '1' ? 'auto-open' : '';
        
        ob_start();
        ?>
        <!-- WhatsApp Chatbot -->
        <div class="whatsapp-chatbot-container <?php echo $positionClass; ?> <?php echo $autoOpen; ?>">
            <!-- Botón flotante -->
            <button class="whatsapp-toggle-btn" 
                    style="background: <?php echo htmlspecialchars($this->config['whatsapp_color_primario']); ?>;"
                    aria-label="Abrir chat de WhatsApp">
                <i class="fab fa-whatsapp"></i>
                <span class="notification-badge"><?php echo count($this->asesores); ?></span>
            </button>
            
            <!-- Panel del chat -->
            <div class="whatsapp-chat-panel">
                <div class="chat-header" 
                     style="background: <?php echo htmlspecialchars($this->config['whatsapp_color_primario']); ?>;">
                    <div class="header-content">
                        <div class="whatsapp-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="header-text">
                            <h4><?php echo htmlspecialchars($this->config['whatsapp_titulo']); ?></h4>
                            <p><?php echo htmlspecialchars($this->config['whatsapp_descripcion']); ?></p>
                        </div>
                    </div>
                    <button class="close-chat-btn" aria-label="Cerrar chat">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="chat-body">
                    <div class="asesores-list">
                        <?php foreach ($this->asesores as $asesor): 
                            $avatar = !empty($asesor['avatar']) ? $asesor['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($asesor['nombre']) . '&background=' . substr($this->config['whatsapp_color_secundario'], 1) . '&color=fff';
                            $whatsappURL = (new WhatsAppConfig($GLOBALS['pdo']))->createWhatsAppURL(
                                $asesor['numero_whatsapp'],
                                $this->config['whatsapp_mensaje_default']
                            );
                        ?>
                        <div class="asesor-card" data-asesor-id="<?php echo $asesor['id']; ?>">
                            <div class="asesor-avatar">
                                <img src="<?php echo htmlspecialchars($avatar); ?>" 
                                     alt="<?php echo htmlspecialchars($asesor['nombre']); ?>">
                            </div>
                            <div class="asesor-info">
                                <h5><?php echo htmlspecialchars($asesor['nombre']); ?></h5>
                                <p class="asesor-cargo"><?php echo htmlspecialchars($asesor['cargo']); ?></p>
                                
                                <?php if ($this->config['whatsapp_mostrar_especialidades'] === '1' && !empty($asesor['especialidad'])): ?>
                                <p class="asesor-especialidad">
                                    <i class="fas fa-star"></i>
                                    <?php echo htmlspecialchars($asesor['especialidad']); ?>
                                </p>
                                <?php endif; ?>
                                
                                <?php if ($this->config['whatsapp_mostrar_horarios'] === '1' && !empty($asesor['horario'])): ?>
                                <p class="asesor-horario">
                                    <i class="fas fa-clock"></i>
                                    <?php echo htmlspecialchars($asesor['horario']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo $whatsappURL; ?>" 
                               target="_blank" 
                               class="chat-btn"
                               style="background: <?php echo htmlspecialchars($this->config['whatsapp_color_primario']); ?>;"
                               data-asesor="<?php echo htmlspecialchars($asesor['nombre']); ?>">
                                <i class="fab fa-whatsapp"></i>
                                <span>Chatear</span>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="chat-footer">
                        <div class="custom-message">
                            <textarea class="custom-message-input" 
                                      placeholder="Escribe tu mensaje personalizado..."><?php echo htmlspecialchars($this->config['whatsapp_mensaje_default']); ?></textarea>
                            <button class="update-message-btn">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <p class="disclaimer">
                            <i class="fas fa-info-circle"></i>
                            Al hacer clic en "Chatear", serás redirigido a WhatsApp
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>