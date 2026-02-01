<?php
// 403 - Acceso denegado
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
    color: #dc3545;
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
</style>

<div class="error-page">
    <div class="error-content">
        <div class="error-code">403</div>
        <h1 class="error-title">Acceso Denegado</h1>
        <p class="error-message">
            No tienes permisos para acceder a este recurso.
            Si crees que esto es un error, contacta al administrador.
        </p>
        <div class="error-actions">
            <a href="<?php echo BASE_URL; ?>" class="btn-error">
                <i class="fas fa-home"></i>
                Volver al inicio
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
