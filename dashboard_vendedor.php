<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea vendedor
if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor') {
    header("Location: auth.php");
    exit();
}

// Obtener información de la tienda del vendedor
$tienda_info = null;
$estadisticas = [
    'total_visitas' => 0,
    'total_calificaciones' => 0,
    'promedio_calificacion' => 0,
    'total_fotos' => 0
];

try {
    // Obtener información de la tienda
    $stmt = $pdo->prepare("
        SELECT t.*, 
               COALESCE(AVG(c.estrellas), 0) as promedio_calificacion,
               COUNT(c.id) as total_calificaciones,
               (SELECT COUNT(*) FROM galeria_tiendas g WHERE g.tienda_id = t.id AND g.activo = 1) as total_fotos
        FROM tiendas t 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE t.vendedor_id = ? AND t.activo = 1
        GROUP BY t.id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tienda_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tienda_info) {
        $estadisticas = [
            'total_visitas' => $tienda_info['clics'],
            'total_calificaciones' => $tienda_info['total_calificaciones'],
            'promedio_calificacion' => $tienda_info['promedio_calificacion'],
            'total_fotos' => $tienda_info['total_fotos']
        ];
    }
} catch(PDOException $e) {
    $error_tienda = "Error al cargar información de la tienda: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Vendedor - Mercado Huasteco</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Variables CSS - Mismas que auth.php */
        :root {
            --primary-color: #006666;
            --secondary-color: #CC5500;
            --accent-color: #FF6B6B;
            --success-color: #28a745;
            --text-dark: #333333;
            --text-light: #ffffff;
            --background-light: #f6f5f7;
            --shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
            --shadow-light: 0 5px 15px rgba(0,0,0,0.1);
            --shadow-card: 0 8px 25px rgba(0,0,0,0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--success-color), var(--secondary-color));
            min-height: 100vh;
            color: var(--text-dark);
        }

        /* Header moderno */
        .modern-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-light);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--success-color);
            text-decoration: none;
        }

        .logo i {
            margin-right: 0.5rem;
            color: var(--secondary-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--success-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .user-details h3 {
            font-size: 1rem;
            color: var(--text-dark);
            margin-bottom: 0.2rem;
        }

        .user-details p {
            font-size: 0.8rem;
            color: #666;
            margin: 0;
        }

        /* Contenedor principal */
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Cards modernos */
        .modern-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-card);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .modern-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--success-color), var(--secondary-color));
        }

        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        /* Botones modernos */
        .btn-modern {
            background: linear-gradient(135deg, var(--success-color), var(--secondary-color));
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0.5rem 0.5rem 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
            text-decoration: none;
            color: white;
        }

        .btn-modern.btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .btn-modern.btn-info {
            background: linear-gradient(135deg, #17a2b8, #20c997);
        }

        .btn-modern.btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
        }

        .btn-modern.btn-outline {
            background: transparent;
            border: 2px solid var(--success-color);
            color: var(--success-color);
        }

        .btn-modern.btn-outline:hover {
            background: var(--success-color);
            color: white;
        }

        /* Estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--success-color), var(--secondary-color));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-card);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--success-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Información de tienda */
        .store-info {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(204, 85, 0, 0.1));
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            border: 2px solid rgba(40, 167, 69, 0.2);
        }

        .store-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--success-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .store-status.inactive {
            background: #dc3545;
        }

        /* Efectos y animaciones */
        .welcome-text {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--success-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .subtitle {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                padding: 0 1rem;
            }
            
            .logo {
                font-size: 1.5rem;
            }
            
            .main-container {
                padding: 0 1rem;
            }
            
            .modern-card {
                padding: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .user-details {
                display: none;
            }
        }

        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modern-card, .stat-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <!-- Header moderno -->
    <header class="modern-header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-store"></i>Mercado Huasteco
            </a>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($_SESSION['nombre']); ?></h3>
                    <p><i class="fas fa-store"></i> Vendedor</p>
                </div>
                <a href="logout.php" class="btn-modern btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>
    
    <div class="main-container">
        <!-- Bienvenida principal -->
        <div class="modern-card">
            <h1 class="welcome-text">¡Hola <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</h1>
            <p class="subtitle">
                Bienvenido a tu panel de vendedor en Mercado Huasteco. Gestiona tu tienda, analiza tus estadísticas 
                y conecta con más clientes de la comunidad universitaria.
            </p>
            
            <!-- Información de la tienda -->
            <?php if ($tienda_info): ?>
                <div class="store-info">
                    <div class="store-status <?php echo $tienda_info['activo'] ? '' : 'inactive'; ?>">
                        <i class="fas fa-<?php echo $tienda_info['activo'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        Tienda <?php echo $tienda_info['activo'] ? 'Activa' : 'Inactiva'; ?>
                        <?php if ($tienda_info['destacada']): ?>
                            <i class="fas fa-star" style="margin-left: 0.5rem;"></i> Destacada
                        <?php endif; ?>
                    </div>
                    <h3 style="font-size: 1.5rem; color: var(--text-dark); margin-bottom: 0.5rem;">
                        <?php echo htmlspecialchars($tienda_info['nombre_tienda']); ?>
                    </h3>
                    <p style="color: #666; margin-bottom: 1rem;">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($tienda_info['categoria']); ?>
                    </p>
                    <p style="color: #666; line-height: 1.5;">
                        <?php echo htmlspecialchars($tienda_info['descripcion']); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="store-info" style="text-align: center; background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(253, 126, 20, 0.1)); border-color: rgba(220, 53, 69, 0.2);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #dc3545; margin-bottom: 1rem;"></i>
                    <h3 style="color: #dc3545; margin-bottom: 1rem;">No tienes una tienda registrada</h3>
                    <p style="color: #666; margin-bottom: 1.5rem;">
                        Para comenzar a vender, necesitas registrar tu tienda primero.
                    </p>
                    <a href="panel_vendedor.php" class="btn-modern">
                        <i class="fas fa-plus"></i> Registrar Mi Tienda
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Acciones principales -->
            <div style="margin-top: 2rem;">
                <a href="panel_vendedor.php" class="btn-modern">
                    <i class="fas fa-cog"></i> Gestionar Tienda
                </a>
                <a href="galeria_vendedor.php" class="btn-modern btn-primary">
                    <i class="fas fa-images"></i> Mi Galería
                </a>
                <a href="estadisticas_vendedor.php" class="btn-modern btn-info">
                    <i class="fas fa-chart-bar"></i> Estadísticas
                </a>
                <?php if ($tienda_info): ?>
                    <a href="tienda_detalle.php?id=<?php echo $tienda_info['id']; ?>" 
                       class="btn-modern btn-secondary" target="_blank">
                        <i class="fas fa-eye"></i> Ver Mi Tienda
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estadísticas -->
        <?php if ($tienda_info): ?>
            <div class="modern-card">
                <h2 style="font-size: 2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem;">
                    <i class="fas fa-chart-line" style="color: var(--success-color); margin-right: 0.5rem;"></i>
                    Estadísticas de tu Tienda
                </h2>
                <p class="subtitle" style="margin-bottom: 1rem;">
                    Resumen del rendimiento de tu tienda en Mercado Huasteco
                </p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($estadisticas['total_visitas']); ?></div>
                        <div class="stat-label">Visitas Totales</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($estadisticas['promedio_calificacion'], 1); ?></div>
                        <div class="stat-label">Calificación Promedio</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-number"><?php echo $estadisticas['total_calificaciones']; ?></div>
                        <div class="stat-label">Reseñas Recibidas</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        <div class="stat-number"><?php echo $estadisticas['total_fotos']; ?></div>
                        <div class="stat-label">Fotos en Galería</div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="estadisticas_vendedor.php" class="btn-modern btn-info" style="font-size: 1.1rem; padding: 15px 30px;">
                        <i class="fas fa-chart-pie"></i> Ver Estadísticas Detalladas
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Acciones rápidas -->
        <div class="modern-card">
            <h2 style="font-size: 2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem;">
                <i class="fas fa-bolt" style="color: var(--secondary-color); margin-right: 0.5rem;"></i>
                Acciones Rápidas
            </h2>
            <p class="subtitle" style="margin-bottom: 2rem;">
                Herramientas esenciales para gestionar tu presencia en Mercado Huasteco
            </p>
            
            <div class="stats-grid">
                <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='panel_vendedor.php'">
                    <div class="stat-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3 style="color: var(--text-dark); margin: 1rem 0 0.5rem 0;">Gestionar Tienda</h3>
                    <p style="color: #666; font-size: 0.9rem;">Edita información, horarios y configuración</p>
                </div>
                
                <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='galeria_vendedor.php'">
                    <div class="stat-icon">
                        <i class="fas fa-camera"></i>
                    </div>
                    <h3 style="color: var(--text-dark); margin: 1rem 0 0.5rem 0;">Subir Fotos</h3>
                    <p style="color: #666; font-size: 0.9rem;">Añade imágenes atractivas de tus productos</p>
                </div>
                
                <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='directorio.php'">
                    <div class="stat-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 style="color: var(--text-dark); margin: 1rem 0 0.5rem 0;">Ver Directorio</h3>
                    <p style="color: #666; font-size: 0.9rem;">Explora la competencia y tendencias</p>
                </div>
                
                <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='mi_perfil.php'">
                    <div class="stat-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <h3 style="color: var(--text-dark); margin: 1rem 0 0.5rem 0;">Mi Perfil</h3>
                    <p style="color: #666; font-size: 0.9rem;">Actualiza tu información personal</p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript para efectos -->
    <script>
        // Animación de entrada para las tarjetas
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.modern-card, .stat-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                card.style.transitionDelay = (index * 0.1) + 's';
                observer.observe(card);
            });
        });

        // Efecto hover mejorado para botones
        document.querySelectorAll('.btn-modern').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.05)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-2px) scale(1)';
            });
        });

        // Efecto hover para tarjetas de acciones rápidas
        document.querySelectorAll('.stat-card[onclick]').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
                this.style.cursor = 'pointer';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-5px) scale(1)';
            });
        });
    </script>
</body>
</html>