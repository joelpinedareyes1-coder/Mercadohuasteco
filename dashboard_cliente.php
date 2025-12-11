<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea cliente
if (!esta_logueado() || $_SESSION['rol'] !== 'cliente') {
    header("Location: auth.php");
    exit();
}

// Obtener tiendas destacadas para mostrar en el dashboard
$tiendas_destacadas = [];
$error_tiendas = '';

try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nombre as vendedor_nombre,
               COALESCE(AVG(c.estrellas), 0) as calificacion_promedio,
               COUNT(c.id) as total_calificaciones,
               (SELECT url_imagen FROM galeria_tiendas gt 
                WHERE gt.tienda_id = t.id AND gt.activo = 1 LIMIT 1) as foto_principal
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE t.activo = 1 AND u.activo = 1 AND t.destacada = 1
        GROUP BY t.id, u.nombre
        ORDER BY calificacion_promedio DESC, t.clics DESC
        LIMIT 6
    ");
    $stmt->execute();
    $tiendas_destacadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_tiendas = "Error al cargar las tiendas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cliente - Mercado Huasteco</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Variables CSS - Mismas que auth.php */
        :root {
            --primary-color: #006666;
            --secondary-color: #CC5500;
            --accent-color: #FF6B6B;
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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
            color: var(--primary-color);
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        /* Botones modernos */
        .btn-modern {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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

        .btn-modern.btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .btn-modern.btn-danger {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
        }

        .btn-modern.btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-modern.btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Grid de tiendas */
        .stores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .store-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-light);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .store-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-card);
        }

        .store-image {
            height: 200px;
            background: linear-gradient(135deg, rgba(0, 102, 102, 0.1), rgba(204, 85, 0, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .store-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .store-image .placeholder {
            font-size: 4rem;
            color: var(--primary-color);
            opacity: 0.3;
        }

        .store-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .store-content {
            padding: 1.5rem;
        }

        .store-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .store-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .store-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stars {
            color: #ffc107;
            font-size: 1.1rem;
        }

        .rating-text {
            color: #666;
            font-size: 0.9rem;
        }

        .store-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
            align-items: center;
        }

        /* Mensajes de error */
        .error-card {
            background: linear-gradient(135deg, #fff5f5, #fed7d7);
            border-left: 5px solid #e53e3e;
            color: #742a2a;
        }

        .error-card h3 {
            color: #742a2a;
            margin-bottom: 1rem;
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

        .modern-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .store-card:nth-child(1) { animation-delay: 0.1s; }
        .store-card:nth-child(2) { animation-delay: 0.2s; }
        .store-card:nth-child(3) { animation-delay: 0.3s; }
        .store-card:nth-child(4) { animation-delay: 0.4s; }
        .store-card:nth-child(5) { animation-delay: 0.5s; }
        .store-card:nth-child(6) { animation-delay: 0.6s; }

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
            
            .stores-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .user-details {
                display: none;
            }
        }

        /* Efectos adicionales */
        .welcome-text {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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

        .stats-row {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-item {
            flex: 1;
            text-align: center;
            padding: 1rem;
            background: rgba(0, 102, 102, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(0, 102, 102, 0.1);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
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
                    <p><i class="fas fa-user-circle"></i> Cliente</p>
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
                Bienvenido a tu panel de cliente en Mercado Huasteco. Descubre las mejores tiendas locales, 
                explora productos únicos y conecta con emprendedores de tu comunidad universitaria.
            </p>
            
            <!-- Estadísticas rápidas -->
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($tiendas_destacadas); ?></div>
                    <div class="stat-label">Tiendas Destacadas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php 
                        $total_reseñas = 0;
                        foreach ($tiendas_destacadas as $tienda) {
                            $total_reseñas += $tienda['total_calificaciones'];
                        }
                        echo $total_reseñas;
                        ?>
                    </div>
                    <div class="stat-label">Reseñas Totales</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php 
                        $promedio_general = 0;
                        if (count($tiendas_destacadas) > 0) {
                            $suma_promedios = 0;
                            foreach ($tiendas_destacadas as $tienda) {
                                $suma_promedios += $tienda['calificacion_promedio'];
                            }
                            $promedio_general = number_format($suma_promedios / count($tiendas_destacadas), 1);
                        }
                        echo $promedio_general;
                        ?>
                    </div>
                    <div class="stat-label">Calificación Promedio</div>
                </div>
            </div>
            
            <!-- Acciones principales -->
            <div style="margin-top: 2rem;">
                <a href="mi_perfil.php" class="btn-modern">
                    <i class="fas fa-user-edit"></i> Mi Perfil
                </a>
                <a href="directorio.php" class="btn-modern">
                    <i class="fas fa-store"></i> Explorar Directorio
                </a>
                <a href="mis_favoritos.php" class="btn-modern btn-danger">
                    <i class="fas fa-heart"></i> Mis Favoritos
                </a>
            </div>
        </div>

        <!-- Mostrar error si existe -->
        <?php if ($error_tiendas): ?>
            <div class="modern-card error-card">
                <h3><i class="fas fa-exclamation-triangle"></i> Error al cargar tiendas</h3>
                <p><?php echo htmlspecialchars($error_tiendas); ?></p>
                <div style="margin-top: 1rem;">
                    <a href="diagnostico_error_calificacion.php" class="btn-modern btn-outline">
                        <i class="fas fa-tools"></i> Ejecutar Diagnóstico
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tiendas Destacadas -->
        <?php if (!empty($tiendas_destacadas)): ?>
            <div class="modern-card">
                <h2 style="font-size: 2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.5rem;">
                    <i class="fas fa-star" style="color: #ffc107; margin-right: 0.5rem;"></i>
                    Tiendas Destacadas
                </h2>
                <p class="subtitle" style="margin-bottom: 1rem;">
                    Descubre las mejores tiendas recomendadas por la comunidad estudiantil
                </p>
                
                <div class="stores-grid">
                    <?php foreach ($tiendas_destacadas as $tienda): ?>
                        <div class="store-card">
                            <div class="store-image">
                                <?php if ($tienda['foto_principal']): ?>
                                    <img src="<?php echo htmlspecialchars($tienda['foto_principal']); ?>" 
                                         alt="<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>">
                                <?php else: ?>
                                    <div class="placeholder">
                                        <i class="fas fa-store"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="store-badge">Destacada</div>
                            </div>
                            
                            <div class="store-content">
                                <h3 class="store-title">
                                    <?php echo htmlspecialchars($tienda['nombre_tienda']); ?>
                                </h3>
                                <p class="store-description">
                                    <?php echo htmlspecialchars(substr($tienda['descripcion'], 0, 120)) . (strlen($tienda['descripcion']) > 120 ? '...' : ''); ?>
                                </p>
                                
                                <div class="store-rating">
                                    <div class="stars">
                                        <?php 
                                        $rating = round($tienda['calificacion_promedio']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                    </div>
                                    <span class="rating-text">
                                        <?php echo number_format($tienda['calificacion_promedio'], 1); ?> 
                                        (<?php echo $tienda['total_calificaciones']; ?> reseñas)
                                    </span>
                                </div>
                                
                                <div class="store-actions">
                                    <a href="tienda_detalle.php?id=<?php echo $tienda['id']; ?>" 
                                       class="btn-modern" style="font-size: 0.8rem; padding: 8px 16px;">
                                        <i class="fas fa-eye"></i> Ver Detalles
                                    </a>
                                    <a href="<?php echo htmlspecialchars($tienda['url_tienda']); ?>" 
                                       target="_blank" 
                                       class="btn-modern btn-outline" style="font-size: 0.8rem; padding: 8px 16px;">
                                        <i class="fas fa-external-link-alt"></i> Visitar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 3rem;">
                    <a href="directorio.php" class="btn-modern btn-success" style="font-size: 1.1rem; padding: 15px 30px;">
                        <i class="fas fa-search"></i> Explorar Todas las Tiendas
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="modern-card">
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-store" style="font-size: 4rem; color: var(--primary-color); opacity: 0.3; margin-bottom: 1rem;"></i>
                    <h2 style="font-size: 1.8rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem;">
                        Explora Nuestro Directorio
                    </h2>
                    <p class="subtitle">
                        Aún no hay tiendas destacadas disponibles, pero puedes explorar todo nuestro directorio 
                        y descubrir increíbles emprendimientos locales.
                    </p>
                    <a href="directorio.php" class="btn-modern btn-success" style="font-size: 1.1rem; padding: 15px 30px; margin-top: 1rem;">
                        <i class="fas fa-compass"></i> Explorar Directorio Completo
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript para efectos -->
    <script>
        // Animación de entrada para las tarjetas
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.modern-card, .store-card');
            
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
    </script>
</body>
</html>