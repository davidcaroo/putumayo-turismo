<?php
include '../includes/config.php';
include '../includes/functions.php';

if(!isset($_GET['id'])) {
    die('ID de reserva no especificado');
}

$reserva_id = $_GET['id'];

// Obtener detalles completos de la reserva
$stmt = $pdo->prepare("SELECT r.*, u.nombre as usuario_nombre, u.email, u.telefono, 
                              d.nombre as destino_nombre, d.descripcion as destino_descripcion,
                              a.nombre as actividad_nombre, a.descripcion as actividad_descripcion,
                              a.precio as actividad_precio, a.duracion
                       FROM reservas r 
                       LEFT JOIN usuarios u ON r.usuario_id = u.id 
                       LEFT JOIN destinos d ON r.destino_id = d.id 
                       LEFT JOIN actividades a ON r.actividad_id = a.id 
                       WHERE r.id = ?");
$stmt->execute([$reserva_id]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reserva) {
    die('Reserva no encontrada');
}
?>

<div class="reservation-details">
    <div class="detail-section">
        <h4>Información del Cliente</h4>
        <div class="detail-grid">
            <div class="detail-item">
                <label>Nombre:</label>
                <span><?php echo htmlspecialchars($reserva['usuario_nombre']); ?></span>
            </div>
            <div class="detail-item">
                <label>Email:</label>
                <span><?php echo htmlspecialchars($reserva['email']); ?></span>
            </div>
            <div class="detail-item">
                <label>Teléfono:</label>
                <span><?php echo htmlspecialchars($reserva['telefono'] ?? 'No especificado'); ?></span>
            </div>
        </div>
    </div>
    
    <div class="detail-section">
        <h4>Información del Viaje</h4>
        <div class="detail-grid">
            <div class="detail-item">
                <label>Destino:</label>
                <span><?php echo htmlspecialchars($reserva['destino_nombre']); ?></span>
            </div>
            <div class="detail-item">
                <label>Actividad:</label>
                <span><?php echo htmlspecialchars($reserva['actividad_nombre']); ?></span>
            </div>
            <div class="detail-item">
                <label>Fecha de Viaje:</label>
                <span><?php echo formatDate($reserva['fecha_viaje']); ?></span>
            </div>
            <div class="detail-item">
                <label>Número de Personas:</label>
                <span><?php echo $reserva['cantidad_personas']; ?></span>
            </div>
            <div class="detail-item">
                <label>Duración:</label>
                <span><?php echo htmlspecialchars($reserva['duracion'] ?? 'No especificada'); ?></span>
            </div>
        </div>
    </div>
    
    <div class="detail-section">
        <h4>Información de Pago</h4>
        <div class="detail-grid">
            <div class="detail-item">
                <label>Precio por Persona:</label>
                <span>$<?php echo number_format($reserva['actividad_precio'], 0, ',', '.'); ?></span>
            </div>
            <div class="detail-item">
                <label>Precio Total:</label>
                <span class="price-total">$<?php echo number_format($reserva['precio_total'], 0, ',', '.'); ?></span>
            </div>
            <div class="detail-item">
                <label>Estado:</label>
                <span class="status status-<?php echo $reserva['estado']; ?>">
                    <?php echo getStatusName($reserva['estado']); ?>
                </span>
            </div>
            <div class="detail-item">
                <label>Fecha de Reserva:</label>
                <span><?php echo formatDate($reserva['fecha_creacion']); ?></span>
            </div>
        </div>
    </div>
    
    <?php if($reserva['destino_descripcion']): ?>
    <div class="detail-section">
        <h4>Descripción del Destino</h4>
        <p><?php echo htmlspecialchars($reserva['destino_descripcion']); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if($reserva['actividad_descripcion']): ?>
    <div class="detail-section">
        <h4>Descripción de la Actividad</h4>
        <p><?php echo htmlspecialchars($reserva['actividad_descripcion']); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.reservation-details {
    max-height: 60vh;
    overflow-y: auto;
}

.detail-section {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.detail-section:last-child {
    border-bottom: none;
}

.detail-section h4 {
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item label {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-color);
    opacity: 0.8;
    margin-bottom: 0.3rem;
}

.detail-item span {
    color: var(--text-color);
}

.price-total {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--secondary-color);
}
</style>