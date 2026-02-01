// whatsapp-chatbot.js
document.addEventListener('DOMContentLoaded', function() {
    const chatbotContainer = document.querySelector('.whatsapp-chatbot-container');
    const toggleBtn = document.querySelector('.whatsapp-toggle-btn');
    const closeBtn = document.querySelector('.close-chat-btn');
    const chatPanel = document.querySelector('.whatsapp-chat-panel');
    const customMessageInput = document.querySelector('.custom-message-input');
    const updateMessageBtn = document.querySelector('.update-message-btn');
    const chatButtons = document.querySelectorAll('.chat-btn');
    
    // Mostrar/ocultar panel
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const isVisible = chatPanel.style.display === 'flex';
            chatPanel.style.display = isVisible ? 'none' : 'flex';
            
            // Animar el botón
            this.classList.toggle('pulse-animation');
            
            // Cerrar automáticamente después de 5 minutos si está abierto
            if (!isVisible) {
                setTimeout(() => {
                    if (chatPanel.style.display === 'flex') {
                        chatPanel.style.display = 'none';
                        this.classList.remove('pulse-animation');
                    }
                }, 300000); // 5 minutos
            }
        });
    }
    
    // Cerrar panel
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            chatPanel.style.display = 'none';
            toggleBtn.classList.remove('pulse-animation');
        });
    }
    
    // Cerrar al hacer clic fuera del panel
    document.addEventListener('click', function(event) {
        if (!chatbotContainer.contains(event.target) && 
            chatPanel.style.display === 'flex' &&
            !toggleBtn.contains(event.target)) {
            chatPanel.style.display = 'none';
            toggleBtn.classList.remove('pulse-animation');
        }
    });
    
    // Actualizar mensaje personalizado
    if (updateMessageBtn) {
        updateMessageBtn.addEventListener('click', function() {
            const newMessage = customMessageInput.value.trim();
            if (newMessage) {
                updateAllChatLinks(newMessage);
                showNotification('Mensaje actualizado correctamente');
            }
        });
    }
    
    // Permitir Enter para actualizar mensaje (Ctrl+Enter para nueva línea)
    if (customMessageInput) {
        customMessageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.ctrlKey) {
                e.preventDefault();
                updateMessageBtn.click();
            }
        });
    }
    
    // Actualizar todos los enlaces de chat
    function updateAllChatLinks(message) {
        chatButtons.forEach(button => {
            const whatsappNumber = button.getAttribute('href').split('?')[0].split('/').pop();
            const encodedMessage = encodeURIComponent(message);
            button.href = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
        });
    }
    
    // Mostrar notificación
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'whatsapp-notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #25D366;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Seguimiento de clics en botones de chat
    chatButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const asesor = this.getAttribute('data-asesor');
            const eventData = {
                event: 'whatsapp_chat_initiated',
                asesor: asesor,
                timestamp: new Date().toISOString(),
                message: customMessageInput.value.trim()
            };
            
            // Guardar en localStorage para analytics
            saveAnalyticsEvent(eventData);
            
            // Opcional: Enviar a Google Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'whatsapp_chat', {
                    'event_category': 'engagement',
                    'event_label': asesor,
                    'value': 1
                });
            }
        });
    });
    
    // Guardar evento de analytics
    function saveAnalyticsEvent(data) {
        let analytics = JSON.parse(localStorage.getItem('whatsapp_analytics') || '[]');
        analytics.push(data);
        
        // Mantener solo los últimos 100 eventos
        if (analytics.length > 100) {
            analytics = analytics.slice(-100);
        }
        
        localStorage.setItem('whatsapp_analytics', JSON.stringify(analytics));
        
        // Enviar al servidor si hay conexión
        if (navigator.onLine) {
            sendAnalyticsToServer(data);
        }
    }
    
    // Enviar analytics al servidor
    function sendAnalyticsToServer(data) {
        fetch('/api/whatsapp-analytics.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        }).catch(error => console.error('Error sending analytics:', error));
    }
    
    // Animación del botón al cargar
    setTimeout(() => {
        if (toggleBtn) {
            toggleBtn.classList.add('pulse-animation');
            setTimeout(() => toggleBtn.classList.remove('pulse-animation'), 2000);
        }
    }, 1000);
    
    // Estilos para animaciones de notificación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { bottom: -50px; opacity: 0; }
            to { bottom: 20px; opacity: 1; }
        }
        
        @keyframes slideOut {
            from { bottom: 20px; opacity: 1; }
            to { bottom: -50px; opacity: 0; }
        }
    `;
    document.head.appendChild(style);
});