<?php
include '../includes/config.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasRole('superadmin') && !hasRole('admin')) {
    header('Location: ../login.php');
    exit;
}

// Obtener estadísticas reales desde la base de datos
$destinos_count = 0;
$usuarios_count = 0;
$reservas_count = 0;
$resenas_count = 0;

try {
    // Contar destinos activos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM destinos WHERE activo = 1");
    $stmt->execute();
    $destinos_count = $stmt->fetchColumn();

    // Contar usuarios
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios");
    $stmt->execute();
    $usuarios_count = $stmt->fetchColumn();

    // Contar reservas activas - ajustar según tu estructura de base de datos
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE estado = 'confirmada'");
        $stmt->execute();
        $reservas_count = $stmt->fetchColumn();
    } catch (Exception $e) {
        // Si la tabla no existe, asignar 0
        $reservas_count = 0;
    }

    // Contar reseñas pendientes - ajustar según tu estructura
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM testimonios WHERE aprobado = 0");
        $stmt->execute();
        $resenas_count = $stmt->fetchColumn();
    } catch (Exception $e) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reseñas WHERE estado = 'pendiente'");
            $stmt->execute();
            $resenas_count = $stmt->fetchColumn();
        } catch (Exception $e2) {
            $resenas_count = 0;
        }
    }
} catch (Exception $e) {
    // Manejar errores silenciosamente
    error_log("Error en dashboard: " . $e->getMessage());
}

// Verificar si existe la variable de sesión 'nombre'
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Administrador';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Putumayo Turismo</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos adicionales para asegurar funcionalidad */
        .admin-container {
            display: flex;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            flex: 1;
            background: #f5f7fa;
            padding: 20px;
            margin-left: 280px;
        }

        .content-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .content-header h1 {
            margin: 0 0 10px;
            color: #2c3e50;
        }

        .content-header p {
            margin: 0;
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            color: white;
            font-size: 24px;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 2rem;
            color: #2c3e50;
        }

        .stat-info p {
            margin: 5px 0 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 75px 15px 15px 15px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>Dashboard</h1>
                <p>Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $destinos_count; ?></h3>
                        <p>Destinos Activos</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196F3;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $usuarios_count; ?></h3>
                        <p>Usuarios Registrados</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #FF9800;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $reservas_count; ?></h3>
                        <p>Reservas Activas</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #9C27B0;">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $resenas_count; ?></h3>
                        <p>Reseñas Pendientes</p>
                    </div>
                </div>
            </div>

            <!-- Sección para contenido adicional -->
            <div style="background: white; border-radius: 10px; padding: 25px; margin-top: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #2c3e50;">Acciones Rápidas</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <a href="gestion-destinos.php?action=nuevo" style="background: #4CAF50; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus"></i> Nuevo Destino
                    </a>
                    <a href="gestion-usuarios.php?action=nuevo" style="background: #2196F3; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </a>
                    <a href="gestion-resenas.php?action=nuevo" style="background: #9C27B0; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-comment-medical"></i> Nueva Reseña
                    </a>
                    <a href="gestion-galeria.php?action=subir" style="background: #FF9800; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-upload"></i> Subir Imagen
                    </a>
                </div>
            </div>

            <!-- Últimas actividades -->
            <div style="background: white; border-radius: 10px; padding: 25px; margin-top: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #2c3e50;">Últimas Actividades</h3>
                <div style="margin-top: 15px;">
                    <?php
                    try {
                        // Intentar obtener últimas actividades
                        $stmt = $pdo->prepare("SELECT 'destino' as tipo, nombre, fecha_creacion FROM destinos ORDER BY fecha_creacion DESC LIMIT 3");
                        $stmt->execute();
                        $actividades = $stmt->fetchAll();

                        foreach ($actividades as $actividad):
                    ?>
                            <div style="padding: 10px 0; border-bottom: 1px solid #eee; display: flex; align-items: center;">
                                <div style="width: 30px; height: 30px; background: #4CAF50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; margin-right: 10px;">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 500;">Nuevo destino: <?php echo htmlspecialchars($actividad['nombre']); ?></div>
                                    <div style="font-size: 0.9rem; color: #7f8c8d;"><?php echo date('d/m/Y', strtotime($actividad['fecha_creacion'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($actividades)): ?>
                            <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                                <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <p>No hay actividades recientes</p>
                            </div>
                        <?php endif; ?>
                    <?php } catch (Exception $e) { ?>
                        <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                            <i class="fas fa-database" style="font-size: 2rem; margin-bottom: 10px;"></i>
                            <p>Información de actividades no disponible</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Script básico para funcionalidades del dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar la hora cada minuto
            function updateTime() {
                const now = new Date();
                const timeElement = document.getElementById('currentTime');
                if (timeElement) {
                    timeElement.textContent = now.toLocaleTimeString('es-ES', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            }

            // Verificar elementos del DOM antes de manipular
            const statCards = document.querySelectorAll('.stat-card');
            if (statCards.length > 0) {
                statCards.forEach((card, index) => {
                    // Animación escalonada para las cards
                    card.style.animationDelay = `${index * 0.1}s`;
                    card.style.opacity = '0';
                    card.style.animation = 'fadeInUp 0.5s forwards';
                });
            }

            // Agregar animación CSS
            const style = document.createElement('style');
            style.textContent = `
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
        `;
            document.head.appendChild(style);

            // Confirmación para logout
            const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
            logoutLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>

</html>