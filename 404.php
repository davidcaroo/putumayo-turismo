<?php
// 404 - Página no encontrada
include 'includes/header.php';
?>

<style>
.error-page {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
}

.error-content {
    max-width: 600px;
}

.error-code {
    font-size: 8rem;
    font-weight: bold;
    color: var(--primary-color);
    line-height: 1;
    margin-bottom: 1rem;
}

.error-title {
    font-size: 2rem;
    color: var(--text-color);
    margin-bottom: 1rem;
}

.error-message {
    font-size: 1.1rem;
    color: var(--text-light);
    margin-bottom: 2rem;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-error {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 12px 24px;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-error:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
}

.btn-secondary {
    background: var(--card-bg);
    color: var(--text-color);
    border: 2px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--bg-light);
}
</style>

<div class="error-page">
    <div class="error-content">
        <div class="error-code">404</div>
        <h1 class="error-title">Página no encontrada</h1>
        <p class="error-message">
            Lo sentimos, la página que buscas no existe o ha sido movida.
            Puede que el enlace esté roto o que hayas escrito mal la URL.
        </p>
        <div class="error-actions">
            <a href="<?php echo BASE_URL; ?>" class="btn-error">
                <i class="fas fa-home"></i>
                Volver al inicio
            </a>
            <a href="<?php echo BASE_URL; ?>destinos" class="btn-error btn-secondary">
                <i class="fas fa-map-marked-alt"></i>
                Ver destinos
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
