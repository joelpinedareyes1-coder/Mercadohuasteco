<?php
require_once 'config.php';

// Configuración de la página
$page_title = "Inicio";
$page_description = "Mercado Huasteco - Conectando el talento de la región con las mejores tiendas y servicios locales";
$body_class = "home-page";

// Obtener tiendas Premium (destacadas)
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nombre as vendedor_nombre, u.es_premium,
               COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
               COUNT(c.id) as total_calificaciones,
               (SELECT url_imagen FROM galeria_tiendas gt WHERE gt.tienda_id = t.id AND gt.activo = 1 LIMIT 1) as foto_principal
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE t.activo = 1 AND u.es_premium = 1
        GROUP BY t.id, u.nombre, u.es_premium
        ORDER BY RAND()
        LIMIT 6
    ");
    $stmt->execute();
    $tiendas_destacadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $tiendas_destacadas = [];
}

// Obtener estadísticas generales
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tiendas WHERE activo = 1");
    $total_tiendas = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol IN ('cliente', 'vendedor') AND activo = 1");
    $total_usuarios = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM calificaciones WHERE activo = 1");
    $total_reseñas = $stmt->fetch()['total'];
} catch(PDOException $e) {
    $total_tiendas = 0;
    $total_usuarios = 0;
    $total_reseñas = 0;
}

// CSS adicional específico para la página de inicio

$additional_css = ['css/home-styles.css'];
?>


<?php

 include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-background"></div>
    <div class="container">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6">
                <div class="hero-content animate-fade-in-left">
                    <h1 class="hero-title">
                        Conectando <span class="text-gradient-primary">Estudiantes</span> 
                        con <span class="text-gradient-secondary">Oportunidades</span> Locales
                    </h1>
                    <p class="hero-subtitle">
                        Descubre las mejores tiendas y servicios cerca de tu universidad. 
                        Una plataforma diseñada por y para la comunidad estudiantil.
                    </p>
                    <div class="hero-actions">
                        <a href="directorio.php" class="btn btn-primary-modern btn-lg-modern">
                            <i class="fas fa-search me-2"></i>Explorar Tiendas
                        </a>
                        <?php if (!esta_logueado()): ?>
                            <a href="auth.php" class="btn btn-secondary-modern btn-lg-modern ms-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                            </a>
                            <a href="auth.php#registro" class="btn btn-outline-modern btn-lg-modern ms-3">
                                <i class="fas fa-user-plus me-2"></i>Únete Ahora
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image animate-fade-in-right">
                    <div class="hero-card">
                        <img src="img/logo.png" alt="Logo Mercado Huasteco" class="hero-logo">
                        <h3>Mercado Huasteco</h3>
                        <p>Conectando el talento de la región</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Estadísticas -->
<section class="stats-section section-padding animate-in">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="stat-card hover-lift">
                    <div class="stat-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stat-number" data-count="<?php echo $total_tiendas; ?>">0</div>
                    <div class="stat-label">Tiendas Registradas</div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card hover-lift">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number" data-count="<?php echo $total_usuarios; ?>">0</div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card hover-lift">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number" data-count="<?php echo $total_reseñas; ?>">0</div>
                    <div class="stat-label">Reseñas Publicadas</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tiendas Destacadas -->
<?php if (!empty($tiendas_destacadas)): ?>
<section class="featured-section section-padding bg-light-modern animate-in">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="section-title">
                    <img src="img/premium-badge.svg" alt="Premium" style="width: 40px; height: 40px; vertical-align: middle; margin-right: 10px;">
                    Tiendas Premium
                </h2>
                <p class="section-subtitle">Descubre las tiendas verificadas y destacadas de nuestra comunidad</p>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($tiendas_destacadas as $tienda): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="tienda-card hover-lift">
                        <div class="tienda-image">
                            <?php if ($tienda['foto_principal']): ?>
                                <img src="<?php echo htmlspecialchars($tienda['foto_principal']); ?>" 
                                     alt="<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>">
                            <?php else: ?>
                                <div class="tienda-placeholder">
                                    <i class="fas fa-store"></i>
                                </div>
                            <?php endif; ?>
                            <div class="tienda-badge premium-badge">
                                <img src="img/premium-badge.svg" alt="Premium" style="width: 20px; height: 20px; margin-right: 5px;">
                                PREMIUM
                            </div>
                        </div>
                        
                        <div class="tienda-content">
                            <h5 class="tienda-title"><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></h5>
                            <p class="tienda-category">
                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($tienda['categoria']); ?>
                            </p>
                            <p class="tienda-description">
                                <?php echo htmlspecialchars(substr($tienda['descripcion'], 0, 100)) . '...'; ?>
                            </p>
                            
                            <div class="tienda-rating">
                                <div class="stars">
                                    <?php 
                                    $rating = round($tienda['promedio_estrellas']);
                                    for ($i = 1; $i <= 5; $i++): 
                                    ?>
                                        <i class="fas fa-star<?php echo $i <= $rating ? '' : ' text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-text">
                                    <?php echo number_format($tienda['promedio_estrellas'], 1); ?> 
                                    (<?php echo $tienda['total_calificaciones']; ?> reseñas)
                                </span>
                            </div>
                            
                            <div class="tienda-actions">
                                <a href="tienda_detalle.php?id=<?php echo $tienda['id']; ?>" 
                                   class="btn btn-primary-modern">
                                    <i class="fas fa-eye me-1"></i>Ver Detalles
                                </a>
                                <a href="<?php echo htmlspecialchars($tienda['url_tienda']); ?>" 
                                   target="_blank" 
                                   class="btn btn-outline-modern">
                                    <i class="fas fa-external-link-alt me-1"></i>Visitar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="directorio.php" class="btn btn-secondary-modern btn-lg-modern">
                <i class="fas fa-th-large me-2"></i>Ver Todas las Tiendas
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Cómo Funciona -->
<section class="how-it-works-section section-padding animate-in" id="como-funciona">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="section-title">¿Cómo Funciona Mercado Huasteco?</h2>
                <p class="section-subtitle">Tres simples pasos para conectarte con las mejores tiendas</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="step-card text-center">
                    <div class="step-number">1</div>
                    <div class="step-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h4>Explora</h4>
                    <p>Navega por nuestro directorio de tiendas locales cerca de tu universidad</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="step-card text-center">
                    <div class="step-number">2</div>
                    <div class="step-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h4>Descubre</h4>
                    <p>Lee reseñas de otros estudiantes y encuentra las mejores opciones</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="step-card text-center">
                    <div class="step-number">3</div>
                    <div class="step-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h4>Conecta</h4>
                    <p>Visita las tiendas y comparte tu experiencia con la comunidad</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section">
    <div class="cta-background"></div>
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <div class="cta-content">
                    <h2 class="cta-title">¿Tienes una Tienda?</h2>
                    <p class="cta-subtitle">
                        Únete a nuestra comunidad y conecta con miles de estudiantes universitarios
                    </p>
                    <div class="cta-actions">
                        <?php if (!esta_logueado()): ?>
                            <a href="auth.php#registro" class="btn btn-accent-modern btn-lg-modern">
                                <i class="fas fa-store me-2"></i>Registrar mi Tienda
                            </a>
                        <?php else: ?>
                            <a href="<?php echo obtener_dashboard_url(); ?>" class="btn btn-accent-modern btn-lg-modern">
                                <i class="fas fa-tachometer-alt me-2"></i>Mi Panel de Control
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // ============================================
    // ANIMACIÓN DE CONTADORES
    // ============================================
    function animateCounters() {
        const counters = document.querySelectorAll("[data-count]");
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute("data-count"));
            const duration = 2000;
            const increment = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                counter.textContent = Math.floor(current);
            }, 16);
        });
    }
    
    // ============================================
    // OBSERVADOR DE INTERSECCIÓN PARA ANIMACIONES
    // ============================================
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                if (entry.target.classList.contains("stats-section")) {
                    animateCounters();
                }
                entry.target.classList.add("animate-in");
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    // Observar secciones para animaciones
    document.querySelectorAll(".stats-section, .featured-section, .how-it-works-section").forEach(section => {
        observer.observe(section);
    });
    
    // ============================================
    // SMOOTH SCROLL SOLO PARA CLICKS (NO AL CARGAR)
    // ============================================
    // Este código solo se activa cuando el usuario hace CLICK en un enlace
    // NO se ejecuta automáticamente al cargar la página
    document.querySelectorAll("a[href^='#']").forEach(anchor => {
        anchor.addEventListener("click", function (e) {
            const href = this.getAttribute("href");
            
            // Manejar enlace de registro especial
            if (href === "#registro") {
                // No prevenir default, dejar que navegue a auth.php#registro
                return;
            }
            
            // Prevenir el comportamiento por defecto
            e.preventDefault();
            
            // Buscar el elemento destino
            const target = document.querySelector(href);
            
            if (target) {
                // Hacer smooth scroll SOLO cuando se hace click
                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
                
                // Actualizar la URL con el hash
                history.pushState(null, null, href);
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?> 
?>