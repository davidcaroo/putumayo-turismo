<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración de base de datos
require_once __DIR__ . '/../includes/config.php';

// Función para cargar configuraciones desde la base de datos
function cargarConfiguracionesFooter($pdo)
{
    $config = [];
    try {
        $stmt = $pdo->query("SELECT config_key, valor FROM configuracion");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['config_key']] = $row['valor'];
        }
    } catch (PDOException $e) {
        error_log("Error cargando configuraciones para footer: " . $e->getMessage());
        // Si hay error, usar valores por defecto
        $config = obtenerConfiguracionesFooterPorDefecto();
    }
    return $config;
}

// Configuración por defecto para el footer
function obtenerConfiguracionesFooterPorDefecto()
{
    return [
        'site_name' => 'Putumayo Turismo',
        'primary_color' => '#10a2c6',
        'secondary_color' => '#ff9800',
        'accent_color' => '#000000',
        'font_family' => 'Arial, sans-serif',
        'footer_text' => 'Descubre los mejores destinos turísticos del Putumayo, Colombia. Experiencias únicas en la Amazonía colombiana.',
        'social_facebook' => 'https://facebook.com/putumayoturismo',
        'social_instagram' => 'https://instagram.com/putumayoturismo',
        'social_twitter' => 'https://twitter.com/putumayoturismo',
        'social_youtube' => '',
        'social_linkedin' => '',
        'contact_email' => 'info@putumayoturismo.com',
        'contact_phone' => '+57 3025191138',
        'contact_address' => 'Mocoa, Putumayo, Colombia',
        'show_social' => '1',
        'whatsapp_number' => '+573001234567'
    ];
}

// Cargar configuraciones
$config = cargarConfiguracionesFooter($pdo);

// Definir funciones auxiliares para colores
function darkenColor($color, $amount)
{
    if (strpos($color, '#') === 0) {
        $color = substr($color, 1);

        // Convertir hex a RGB
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));

        // Oscurecer
        $r = max(0, $r - $amount);
        $g = max(0, $g - $amount);
        $b = max(0, $b - $amount);

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
    return '#1a5d37';
}

function lightenColor($color, $amount)
{
    if (strpos($color, '#') === 0) {
        $color = substr($color, 1);

        // Convertir hex a RGB
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));

        // Aclarar
        $r = min(255, $r + $amount);
        $g = min(255, $g + $amount);
        $b = min(255, $b + $amount);

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
    return '#5cd48a';
}

// Verificar si el usuario está logueado
$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['usuario_id']);
?>

<style>
    /* Estilos del footer con variables dinámicas */
    :root {
        --footer-primary: <?php echo htmlspecialchars($config['primary_color'] ?? '#10a2c6'); ?>;
        --footer-primary-dark: <?php echo htmlspecialchars(darkenColor($config['primary_color'] ?? '#10a2c6', 20)); ?>;
        --footer-accent: <?php echo htmlspecialchars($config['accent_color'] ?? '#000000'); ?>;
        --footer-accent-light: <?php echo htmlspecialchars(lightenColor($config['accent_color'] ?? '#000000', 30)); ?>;
        --footer-text-light: rgba(255, 255, 255, 0.9);
        --footer-text-lighter: rgba(255, 255, 255, 0.7);
        --footer-border: rgba(255, 255, 255, 0.1);
    }

    .footer {
        background: linear-gradient(135deg, var(--footer-primary), var(--footer-primary-dark));
        color: white;
        padding: 5rem 0 2rem;
        margin-top: auto;
        font-family: <?php echo htmlspecialchars($config['font_family'] ?? 'Arial, sans-serif'); ?>;
        position: relative;
        overflow: hidden;
        box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
    }

    .footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--footer-accent), var(--footer-secondary), var(--footer-accent));
        animation: shimmer 3s infinite;
    }

    @keyframes shimmer {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 3rem;
        padding: 0 20px;
        position: relative;
        z-index: 1;
    }

    .footer-section h3 {
        color: white;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        position: relative;
        display: inline-block;
    }

    .footer-section h3::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 50px;
        height: 3px;
        background: var(--footer-accent);
        border-radius: 2px;
    }

    .footer-section h4 {
        color: white;
        font-size: 1.2rem;
        margin-bottom: 1.2rem;
        font-weight: 600;
    }

    .footer-section p {
        line-height: 1.7;
        margin-bottom: 1.2rem;
        color: var(--footer-text-light);
        font-size: 0.95rem;
    }

    .footer-section ul {
        list-style: none;
        padding: 0;
    }

    .footer-section ul li {
        margin-bottom: 0.8rem;
    }

    .footer-section ul li a {
        color: var(--footer-text-light);
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
    }

    .footer-section ul li a:hover {
        color: var(--footer-accent);
        transform: translateX(5px);
    }

    .footer-section ul li a i {
        font-size: 0.8rem;
        color: var(--footer-accent);
    }

    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .contact-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        color: var(--footer-text-light);
    }

    .contact-item i {
        color: var(--footer-accent);
        font-size: 1.1rem;
        margin-top: 2px;
    }

    .contact-item span {
        line-height: 1.5;
        font-size: 0.95rem;
    }

    .social-links {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .social-links a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-radius: 50%;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .social-links a:hover {
        background: var(--footer-accent);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .newsletter-form {
        margin-top: 1rem;
    }

    .newsletter-form .form-group {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        position: relative;
    }

    .newsletter-form input[type="email"] {
        flex: 1;
        padding: 14px 18px;
        border: 2px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 10px;
        color: white;
        font-family: inherit;
        font-size: 0.95rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .newsletter-form input[type="email"]::placeholder {
        color: rgba(255, 255, 255, 0.65);
        font-size: 0.9rem;
    }

    .newsletter-form input[type="email"]:focus {
        outline: none;
        border-color: var(--footer-accent);
        background: rgba(255, 255, 255, 0.15);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 0 0 4px rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }

    .btn-newsletter {
        background: linear-gradient(135deg, var(--footer-accent), var(--footer-accent-light));
        color: white;
        border: none;
        padding: 14px 26px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        font-family: inherit;
        font-size: 0.95rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        position: relative;
        overflow: hidden;
    }

    .btn-newsletter::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-newsletter:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-newsletter:hover {
        background: linear-gradient(135deg, var(--footer-accent-light), var(--footer-accent));
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    .btn-newsletter:active {
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    }

    .btn-newsletter i {
        font-size: 1rem;
        position: relative;
        z-index: 1;
    }

    .btn-newsletter span {
        position: relative;
        z-index: 1;
    }

    .newsletter-message {
        margin-top: 8px;
        font-size: 0.85rem;
        min-height: 18px;
        padding: 6px 10px;
        border-radius: 4px;
        display: none;
    }

    .newsletter-message.success {
        background: rgba(40, 167, 69, 0.2);
        color: #28a745;
        display: block;
    }

    .newsletter-message.error {
        background: rgba(220, 53, 69, 0.2);
        color: #dc3545;
        display: block;
    }

    .newsletter-terms {
        font-size: 0.75rem;
        color: var(--footer-text-lighter);
        margin-top: 8px;
        line-height: 1.4;
    }

    .newsletter-terms a {
        color: var(--footer-accent);
        text-decoration: none;
    }

    .newsletter-terms a:hover {
        text-decoration: underline;
    }

    .footer-bottom {
        text-align: center;
        margin-top: 4rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--footer-border);
    }

    .footer-bottom-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .footer-bottom p {
        margin: 0;
        color: var(--footer-text-lighter);
        font-size: 0.9rem;
    }

    .footer-links {
        display: flex;
        gap: 1.5rem;
    }

    .footer-links a {
        color: var(--footer-text-lighter);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s;
    }

    .footer-links a:hover {
        color: var(--footer-accent);
        text-decoration: underline;
    }

    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--footer-accent), var(--footer-accent-light));
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px);
    }

    .back-to-top.visible {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .back-to-top:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    /* Responsive */
    @media (max-width: 992px) {
        .footer-container {
            grid-template-columns: repeat(2, 1fr);
            gap: 2.5rem;
        }

        .footer-section:first-child {
            grid-column: span 2;
        }
    }

    @media (max-width: 768px) {
        .footer {
            padding: 3rem 0 1.5rem;
        }

        .footer-container {
            padding: 0 20px;
            gap: 2.5rem;
        }

        .newsletter-form .form-group {
            flex-direction: column;
            gap: 10px;
        }

        .newsletter-form input[type="email"] {
            padding: 13px 16px;
            font-size: 0.95rem;
        }

        .btn-newsletter {
            width: 100%;
            justify-content: center;
            padding: 13px 20px;
            font-size: 0.95rem;
        }

        .footer-section h3 {
            font-size: 1.4rem;
        }

        .footer-section h4 {
            font-size: 1.15rem;
            margin-bottom: 1rem;
        }

        .footer-section p {
            font-size: 0.9rem;
        }

        .contact-item {
            font-size: 0.9rem;
        }

        .social-links {
            justify-content: center;
            flex-wrap: wrap;
        }

        .social-links a {
            width: 45px;
            height: 45px;
            font-size: 1.2rem;
        }

        .footer-links {
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .back-to-top {
            bottom: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
        }
    }

    @media (max-width: 480px) {
        .footer {
            padding: 2.5rem 0 1rem;
        }

        .footer-container {
            padding: 0 15px;
        }

        .newsletter-form .form-group {
            flex-direction: column;
        }

        .btn-newsletter {
            width: 100%;
            justify-content: center;
        }

        .footer-section h3 {
            font-size: 1.3rem;
        }

        .footer-section h4 {
            font-size: 1.1rem;
        }
    }

    /* Animaciones */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .footer-section {
        animation: fadeInUp 0.6s ease-out;
    }

    .footer-section:nth-child(1) {
        animation-delay: 0.1s;
    }

    .footer-section:nth-child(2) {
        animation-delay: 0.2s;
    }

    .footer-section:nth-child(3) {
        animation-delay: 0.3s;
    }

    .footer-section:nth-child(4) {
        animation-delay: 0.4s;
    }
</style>

<footer class="footer">
    <div class="footer-container">
        <!-- Logo y descripción -->
        <div class="footer-section">
            <h3><?php echo htmlspecialchars($config['site_name'] ?? 'Putumayo Turismo'); ?></h3>

            <?php if (isset($config['show_social']) && $config['show_social'] == '1'): ?>
                <div class="social-links">
                    <?php if (!empty($config['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($config['social_facebook']); ?>" target="_blank" title="Facebook" rel="noopener noreferrer">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($config['social_instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($config['social_instagram']); ?>" target="_blank" title="Instagram" rel="noopener noreferrer">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($config['social_twitter'])): ?>
                        <a href="<?php echo htmlspecialchars($config['social_twitter']); ?>" target="_blank" title="Twitter" rel="noopener noreferrer">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($config['social_youtube'])): ?>
                        <a href="<?php echo htmlspecialchars($config['social_youtube']); ?>" target="_blank" title="YouTube" rel="noopener noreferrer">
                            <i class="fab fa-youtube"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($config['social_linkedin'])): ?>
                        <a href="<?php echo htmlspecialchars($config['social_linkedin']); ?>" target="_blank" title="LinkedIn" rel="noopener noreferrer">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Enlaces rápidos -->
        <div class="footer-section">
            <h4>Enlaces Rápidos</h4>
            <ul>
                <li>
                    <a href="index.php">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                </li>
                <li>
                    <a href="destinos.php">
                        <i class="fas fa-map-marked-alt"></i> Destinos
                    </a>
                </li>
                <li>
                    <a href="galeria.php">
                        <i class="fas fa-images"></i> Galería
                    </a>
                </li>
                <li>
                    <a href="reservas.php">
                        <i class="fas fa-calendar-check"></i> Reservas
                    </a>
                </li>
                <?php if ($is_logged_in): ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Información de contacto -->
        <div class="footer-section">
            <h4>Contacto</h4>
            <div class="contact-info">
                <?php if (!empty($config['contact_address'])): ?>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($config['contact_address']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($config['contact_phone'])): ?>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($config['contact_phone']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($config['contact_email'])): ?>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($config['contact_email']); ?></span>
                    </div>
                <?php endif; ?>

                <span>Suscribirse</span>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <span>Lun - Vie: 8:00 AM - 6:00 PM</span>
                </div>

                <?php if (!empty($config['whatsapp_number'])): ?>
                    <div class="contact-item">
                        <i class="fab fa-whatsapp"></i>
                        <span>WhatsApp: <?php echo htmlspecialchars($config['whatsapp_number']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Newsletter -->
        <!--       <div class="footer-section">
            <h4>Newsletter</h4>
            <p>Suscríbete para recibir ofertas especiales y novedades</p>

            <form class="newsletter-form" id="newsletterForm" method="POST" action="newsletter-subscribe.php">
                <div class="form-group">
                    <input type="email"
                        id="newsletter-email"
                        name="email"
                        placeholder="Tu correo electrónico"
                        required
                        aria-label="Correo electrónico para newsletter">
                    <button type="submit" class="btn-newsletter">
                        <i class="fas fa-paper-plane"></i> Suscribirse
                    </button>
                </div>
                <div class="newsletter-message" id="newsletterMessage"></div>
                <p class="newsletter-terms">
                    Al suscribirte aceptas nuestra
                    <a href="privacidad.php">Política de Privacidad</a>
                </p>
            </form>
        </div> -->
    </div>

    <!-- Footer inferior -->
    <div class="footer-bottom">
        <div class="footer-bottom-content">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($config['site_name'] ?? 'Putumayo Turismo'); ?>. Todos los derechos reservados.</p>

            <div class="footer-links">
                <a href="terminos.php">Términos y Condiciones</a>
                <a href="privacidad.php">Política de Privacidad</a>
                <a href="cookies.php">Política de Cookies</a>
                <a href="contacto.php">Contacto</a>
            </div>
        </div>
    </div>

    <!-- Botón para volver arriba -->
    <a href="#" class="back-to-top" id="backToTop" aria-label="Volver arriba">
        <i class="fas fa-chevron-up"></i>
    </a>
</footer>

<script>
    // Funcionalidades del footer
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        // =============== BOTÓN VOLVER ARRIBA ===============
        const backToTop = document.getElementById('backToTop');

        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // =============== NEWSLETTER ===============
        const newsletterForm = document.getElementById('newsletterForm');
        const newsletterMessage = document.getElementById('newsletterMessage');

        if (newsletterForm) {
            newsletterForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const emailInput = document.getElementById('newsletter-email');
                const email = emailInput.value.trim();

                // Validación
                if (!isValidEmail(email)) {
                    showMessage('Por favor ingresa un email válido', 'error');
                    return;
                }

                // Deshabilitar formulario durante envío
                const submitBtn = newsletterForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                submitBtn.disabled = true;

                try {
                    // Enviar petición AJAX
                    const formData = new FormData(this);
                    const response = await fetch(this.action || '#', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        showMessage('¡Gracias por suscribirte! Te enviaremos las mejores ofertas.', 'success');
                        newsletterForm.reset();

                        // Guardar en localStorage
                        localStorage.setItem('newsletter_subscribed', 'true');
                        localStorage.setItem('newsletter_email', email);
                    } else {
                        showMessage(result.message || 'Error al suscribirse. Por favor intenta nuevamente.', 'error');
                    }

                } catch (error) {
                    console.error('Error:', error);
                    showMessage('Error de conexión. Por favor intenta nuevamente.', 'error');
                } finally {
                    // Restaurar botón
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            });
        }

        // =============== FUNCIONES AUXILIARES ===============
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function showMessage(message, type) {
            if (!newsletterMessage) return;

            newsletterMessage.textContent = message;
            newsletterMessage.className = 'newsletter-message ' + type;

            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                newsletterMessage.textContent = '';
                newsletterMessage.className = 'newsletter-message';
            }, 5000);
        }

        // =============== VERIFICAR SI YA ESTÁ SUSCRITO ===============
        if (localStorage.getItem('newsletter_subscribed') === 'true') {
            const newsletterSection = document.querySelector('.footer-section:last-child');
            const email = localStorage.getItem('newsletter_email');
            if (newsletterSection && email) {
                newsletterSection.innerHTML = `
                <h4>¡Ya estás suscrito!</h4>
                <p>Gracias por suscribirte a nuestro newsletter. Te mantendremos informado con las mejores ofertas.</p>
                <div class="contact-item">
                    <i class="fas fa-check-circle" style="color: #28a745; font-size: 1.5rem;"></i>
                    <span>Recibirás nuestras novedades en <strong>${email}</strong></span>
                </div>
            `;
            }
        }

        // =============== COPIAR TELÉFONO AL PORTAPAPELES ===============
        document.querySelectorAll('.contact-item i.fa-phone').forEach(icon => {
            icon.style.cursor = 'pointer';
            icon.title = 'Copiar número';

            icon.addEventListener('click', function() {
                const phoneNumber = this.nextElementSibling?.textContent?.trim();
                if (phoneNumber) {
                    navigator.clipboard.writeText(phoneNumber).then(() => {
                        const originalColor = this.style.color;
                        this.style.color = '#28a745';
                        this.title = '¡Copiado!';

                        setTimeout(() => {
                            this.style.color = originalColor;
                            this.title = 'Copiar número';
                        }, 2000);
                    });
                }
            });
        });

        // =============== ANIMACIÓN DE ENTRADA ===============
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, observerOptions);

        // Observar secciones del footer
        document.querySelectorAll('.footer-section').forEach(section => {
            observer.observe(section);
        });
    });
</script>