<?php 
include 'includes/header.php';

// =============== FUNCIONES PARA OBTENER CONFIGURACIÓN ===============
if(!function_exists('getConfigValue')) {
    function getConfigValue($key, $default = '') {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT valor FROM configuraciones WHERE config_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['valor'] : $default;
        } catch(Exception $e) {
            error_log("Error obteniendo configuración $key: " . $e->getMessage());
            return $default;
        }
    }
}

// =============== OBTENER CONFIGURACIÓN ACTUAL ===============
$config_keys = [
    // Colores y apariencia
    'primary_color', 'secondary_color', 'accent_color', 'font_family',
    
    // Textos del sitio
    'site_name', 'site_description'
];

$config = [];
foreach ($config_keys as $key) {
    // Valores por defecto
    $default = '';
    if ($key === 'site_name') $default = 'Putumayo Turismo';
    if ($key === 'site_description') $default = 'Descubre la belleza del Putumayo';
    if ($key === 'primary_color') $default = '#2E8B57';
    if ($key === 'secondary_color') $default = '#267349';
    if ($key === 'accent_color') $default = '#2196F3';
    if ($key === 'font_family') $default = "'Inter', sans-serif";
    
    $config[$key] = getConfigValue($key, $default);
}

// Obtener imágenes de la galería
$stmt = $pdo->prepare("SELECT * FROM galeria WHERE activo = 1 ORDER BY fecha_subida DESC");
$stmt->execute();
$imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías únicas
$stmt = $pdo->prepare("SELECT DISTINCT categoria FROM galeria WHERE activo = 1 AND categoria IS NOT NULL");
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
/* Variables CSS dinámicas según configuración */
:root {
    --primary-color: <?php echo htmlspecialchars($config['primary_color']); ?>;
    --secondary-color: <?php echo htmlspecialchars($config['secondary-color']); ?>;
    --accent-color: <?php echo htmlspecialchars($config['accent_color']); ?>;
    --font-family: <?php echo htmlspecialchars($config['font_family']); ?>;
    --text-color: #2c3e50;
    --text-light: #7f8c8d;
    --bg-color: #ffffff;
    --card-bg: #ffffff;
    --border-color: #e1e8ed;
    --shadow: 0 5px 15px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}

/* Aplicar la fuente principal */
body {
    font-family: var(--font-family);
}

[data-theme="oscuro"] {
    --text-color: #ecf0f1;
    --text-light: #bdc3c7;
    --bg-color: #1a1a1a;
    --card-bg: #2c3e50;
    --border-color: #34495e;
    --shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.section {
    padding: 5rem 0;
    background: var(--bg-color);
}

.section-title {
    text-align: center;
    margin-bottom: 3rem;
}

.section-title h2 {
    color: var(--text-color);
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.section-title p {
    color: var(--text-light);
    font-size: 1.2rem;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.galeria-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.galeria-card {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: var(--transition);
    aspect-ratio: 1;
    background: var(--card-bg);
}

.galeria-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.2);
}

[data-theme="oscuro"] .galeria-card:hover {
    box-shadow: 0 15px 30px rgba(0,0,0,0.5);
}

.galeria-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.galeria-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    opacity: 0;
    transition: var(--transition);
    display: flex;
    align-items: flex-end;
    padding: 1.5rem;
}

.galeria-card:hover .galeria-overlay {
    opacity: 1;
}

.galeria-content {
    color: white;
    width: 100%;
}

.galeria-content h3 {
    margin-bottom: 0.5rem;
    font-size: 1.2rem;
    color: white;
}

.galeria-content p {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 1rem;
    color: #f8f9fa;
}

.galeria-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-view, .btn-download {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 0.5rem;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
}

.btn-view:hover, .btn-download:hover {
    background: var(--secondary-color);
    transform: scale(1.1);
}

.filtros-gallery {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 2rem;
}

.btn-filter {
    padding: 0.5rem 1.5rem;
    border: 2px solid var(--primary-color);
    background: transparent;
    color: var(--primary-color);
    border-radius: 25px;
    cursor: pointer;
    transition: var(--transition);
    font-weight: 500;
    font-family: var(--font-family);
}

.btn-filter.active, .btn-filter:hover {
    background: var(--primary-color);
    color: white;
}

/* Estilos para botones generales */
.btn {
    display: inline-block;
    padding: 12px 24px;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 500;
    transition: var(--transition);
    border: none;
    cursor: pointer;
    font-family: var(--font-family);
}

.btn:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    position: relative;
    margin: 2% auto;
    width: 90%;
    max-width: 800px;
    background: var(--card-bg);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

#modalImage {
    width: 100%;
    height: auto;
    max-height: 70vh;
    object-fit: contain;
}

.modal-info {
    padding: 1.5rem;
    text-align: center;
    background: var(--card-bg);
}

.modal-info h3 {
    color: var(--text-color);
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.close-modal {
    position: absolute;
    top: 15px;
    right: 25px;
    color: white;
    font-size: 35px;
    font-weight: bold;
    cursor: pointer;
    z-index: 2001;
    background: rgba(0,0,0,0.5);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.close-modal:hover {
    background: rgba(0,0,0,0.8);
    transform: rotate(90deg);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Animaciones para items de galería */
.galeria-item {
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
    transform: translateY(20px);
}

.galeria-item:nth-child(1) { animation-delay: 0.1s; }
.galeria-item:nth-child(2) { animation-delay: 0.2s; }
.galeria-item:nth-child(3) { animation-delay: 0.3s; }
.galeria-item:nth-child(4) { animation-delay: 0.4s; }
.galeria-item:nth-child(5) { animation-delay: 0.5s; }
.galeria-item:nth-child(6) { animation-delay: 0.6s; }
.galeria-item:nth-child(7) { animation-delay: 0.7s; }
.galeria-item:nth-child(8) { animation-delay: 0.8s; }

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .section {
        padding: 3rem 0;
    }
    
    .section-title h2 {
        font-size: 2rem;
    }
    
    .galeria-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .filtros-gallery {
        gap: 0.5rem;
    }
    
    .btn-filter {
        padding: 0.4rem 1rem;
        font-size: 0.9rem;
    }
    
    .modal-content {
        width: 95%;
        margin: 5% auto;
    }
}

@media (max-width: 576px) {
    .galeria-grid {
        grid-template-columns: 1fr;
    }
    
    .container {
        padding: 0 1rem;
    }
}
</style>

<section class="section" style="padding-top: 8rem;">
    <div class="container">
        <div class="section-title">
            <h2>Galería de Fotos</h2>
            <p>Momentos increíbles de nuestros visitantes en el Putumayo</p>
        </div>

        <!-- Filtros por categoría -->
        <div class="filtros-gallery">
            <button class="btn-filter active" data-categoria="todas">Todas</button>
            <?php foreach($categorias as $categoria): ?>
                <button class="btn-filter" data-categoria="<?php echo htmlspecialchars($categoria); ?>">
                    <?php echo ucfirst($categoria); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de galería -->
        <div class="galeria-grid">
            <?php foreach($imagenes as $index => $imagen): ?>
            <div class="galeria-item" 
                 data-categoria="<?php echo $imagen['categoria'] ?: 'general'; ?>"
                 style="animation-delay: <?php echo ($index * 0.1) + 0.1; ?>s;">
                <div class="galeria-card">
                    <img src="uploads/galeria/<?php echo $imagen['imagen']; ?>" 
                         alt="<?php echo htmlspecialchars($imagen['titulo']); ?>"
                         class="galeria-img">
                    <div class="galeria-overlay">
                        <div class="galeria-content">
                            <h3><?php echo htmlspecialchars($imagen['titulo']); ?></h3>
                            <?php if($imagen['descripcion']): ?>
                            <p><?php echo htmlspecialchars($imagen['descripcion']); ?></p>
                            <?php endif; ?>
                            <div class="galeria-actions">
                                <button class="btn-view" 
                                        data-imagen="uploads/galeria/<?php echo $imagen['imagen']; ?>"
                                        data-titulo="<?php echo htmlspecialchars($imagen['titulo']); ?>"
                                        aria-label="Ver imagen ampliada">
                                    <i class="fas fa-expand"></i>
                                </button>
                                <a href="uploads/galeria/<?php echo $imagen['imagen']; ?>" 
                                   download 
                                   class="btn-download"
                                   aria-label="Descargar imagen">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if(empty($imagenes)): ?>
        <div style="text-align: center; padding: 3rem;">
            <i class="fas fa-images" style="font-size: 4rem; color: var(--border-color); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-color); opacity: 0.7;">Próximamente más fotos</h3>
            <p style="opacity: 0.7;">Estamos preparando nuestra galería con las mejores imágenes del Putumayo</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal para vista ampliada -->
<div id="imageModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" aria-label="Cerrar modal">&times;</span>
        <img id="modalImage" src="" alt="">
        <div class="modal-info">
            <h3 id="modalTitle"></h3>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtros de galería
    const filterButtons = document.querySelectorAll('.btn-filter');
    const galleryItems = document.querySelectorAll('.galeria-item');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const categoria = this.getAttribute('data-categoria');
            
            // Actualizar botones activos
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filtrar items
            galleryItems.forEach(item => {
                const itemCategoria = item.getAttribute('data-categoria');
                
                if (categoria === 'todas' || itemCategoria === categoria) {
                    // Mostrar item con animación
                    item.style.display = 'block';
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0) scale(1)';
                    }, 50);
                } else {
                    // Ocultar item con animación
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(20px) scale(0.8)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
        });
    });
    
    // Modal para vista ampliada
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    const closeModal = document.querySelector('.close-modal');
    
    document.querySelectorAll('.btn-view').forEach(button => {
        button.addEventListener('click', function() {
            const imagenSrc = this.getAttribute('data-imagen');
            const titulo = this.getAttribute('data-titulo');
            
            // Pre-cargar imagen
            const preloader = new Image();
            preloader.src = imagenSrc;
            preloader.onload = function() {
                modalImg.src = imagenSrc;
                modalTitle.textContent = titulo;
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            };
        });
    });
    
    // Cerrar modal
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    });
    
    // Cerrar modal al hacer clic fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
    
    // Cerrar modal con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Navegación con flechas (si hay múltiples imágenes en modal)
        if (modal.style.display === 'block') {
            if (e.key === 'ArrowRight') {
                // Navegar a siguiente imagen
            } else if (e.key === 'ArrowLeft') {
                // Navegar a imagen anterior
            }
        }
    });
    
    // Efectos de hover en tarjetas
    galleryItems.forEach(item => {
        const card = item.querySelector('.galeria-card');
        const img = item.querySelector('.galeria-img');
        
        card.addEventListener('mouseenter', function() {
            img.style.transform = 'scale(1.05)';
        });
        
        card.addEventListener('mouseleave', function() {
            img.style.transform = 'scale(1)';
        });
    });
    
    // Inicializar animaciones
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, { threshold: 0.1 });
    
    galleryItems.forEach(item => {
        observer.observe(item);
    });
    
    // Aplicar color de acento dinámico
    const accentColor = '<?php echo htmlspecialchars($config["accent_color"]); ?>';
    if(accentColor) {
        // Aplicar color de acento a elementos específicos
        document.querySelectorAll('.btn-view:hover, .btn-download:hover').forEach(element => {
            element.style.backgroundColor = accentColor;
        });
        
        // Añadir color de acento como variable CSS
        document.documentElement.style.setProperty('--accent-color', accentColor);
    }
});
</script>

<?php include 'includes/footer.php'; ?>