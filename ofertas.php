<?php
require_once 'config.php';

// Configuración de la página
$page_title = "Ofertas y Cupones";
$page_description = "Descubre las mejores ofertas y cupones de descuento de tiendas Premium en Mercado Huasteco";
$body_class = "ofertas-page";
$additional_css = ['css/ofertas-styles.css'];

// Obtener todas las ofertas activas de tiendas Premium
try {
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.titulo,
            c.descripcion,
            c.fecha_expiracion,
            c.fecha_inicio,
            t.id as tienda_id,
            t.nombre_tienda,
            t.logo,
            t.categoria,
            u.es_premium,
            (SELECT url_imagen FROM galeria_tiendas gt WHERE gt.tienda_id = t.id AND gt.activo = 1 LIMIT 1) as foto_tienda
        FROM cupones_ofertas c
        INNER JOIN tiendas t ON c.id_tienda = t.id
        INNER JOIN usuarios u ON t.vendedor_id = u.id
        WHERE c.estado = 'activo'
        AND (c.fecha_expiracion IS NULL OR c.fecha_expiracion >= CURDATE())
        AND t.activo = 1
        AND u.es_premium = 1
        ORDER BY c.id DESC
    ");
    $stmt->execute();
    $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $ofertas = [];
    $error = "Error al cargar las ofertas: " . $e->getMessage();
}

// Obtener categorías disponibles para filtros
$categorias_disponibles = [];
foreach ($ofertas as $oferta) {
    if (!in_array($oferta['categoria'], $categorias_disponibles)) {
        $categorias_disponibles[] = $oferta['categoria'];
    }
}
sort($categorias_disponibles);

include 'includes/header.php';
?>

<div class="container" style="margin-top: 100px;">
    <nav class="breadcrumb-modern">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="index.php"><i class="fas fa-home me-1"></i>Inicio</a>
            </li>
            <li class="breadcrumb-item active">Ofertas</li>
        </ol>
    </nav>
</div>

<!-- Hero Section -->
<section class="ofertas-hero">
    <div class="container text-center">
        <div class="hero-icon">
            <i class="fas fa-tags"></i>
        </div>
        <h1 class="display-4 mb-3">Ofertas y Cupones Exclusivos</h1>
        <p class="lead mb-4">
            Descubre las mejores ofertas de nuestras tiendas Premium
        </p>
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($ofertas); ?></div>
                <div class="stat-label">Ofertas Activas</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_unique(array_column($ofertas, 'tienda_id'))); ?></div>
                <div class="stat-label">Tiendas Participantes</div>
            </div>
        </div>
    </div>
</section>

<!-- Filtros -->
<?php if (!empty($categorias_disponibles)): ?>
<div class="container mt-4">
    <div class="filtros-ofertas">
        <h5 class="mb-3">
            <i class="fas fa-filter me-2"></i>Filtrar por Categoría
        </h5>
        <div class="d-flex flex-wrap gap-2">
            <button class="filter-btn active" onclick="filtrarPorCategoria('todas')">
                <i class="fas fa-th-large me-1"></i>Todas
                <span class="badge bg-light text-dark ms-1"><?php echo count($ofertas); ?></span>
            </button>
            <?php foreach ($categorias_disponibles as $categoria): ?>
                <?php
                $count = count(array_filter($ofertas, function($o) use ($categoria) {
                    return $o['categoria'] === $categoria;
                }));
                ?>
                <button class="filter-btn" onclick="filtrarPorCategoria('<?php echo htmlspecialchars($categoria); ?>')">
                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($categoria); ?>
                    <span class="badge bg-primary ms-1"><?php echo $count; ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Contenido Principal -->
<div class="container mt-5 mb-5">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger-modern">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($ofertas)): ?>
        <div class="no-ofertas">
            <i class="fas fa-tags"></i>
            <h3 class="mt-3">No hay ofertas disponibles</h3>
            <p class="mb-4">Aún no hay ofertas activas. Vuelve pronto para descubrir nuevas promociones.</p>
            <a href="directorio.php" class="btn btn-primary-modern">
                <i class="fas fa-store me-2"></i>Ver Tiendas
            </a>
        </div>
    <?php else: ?>
        <!-- Contador de resultados -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-tags me-2 text-primary-modern"></i>
                <span id="results-count"><?php echo count($ofertas); ?></span> 
                <?php echo count($ofertas) === 1 ? 'oferta disponible' : 'ofertas disponibles'; ?>
            </h4>
        </div>

        <!-- Grid de Ofertas -->
        <div class="row" id="ofertas-grid">
            <?php foreach ($ofertas as $oferta): ?>
                <div class="col-lg-4 col-md-6 mb-4 oferta-item" 
                     data-categoria="<?php echo strtolower(htmlspecialchars($oferta['categoria'])); ?>">
                    
                    <div class="oferta-card">
                        <!-- Imagen de la tienda -->
                        <div class="oferta-image">
                            <?php if ($oferta['foto_tienda']): ?>
                                <img src="<?php echo htmlspecialchars($oferta['foto_tienda']); ?>" 
                                     alt="<?php echo htmlspecialchars($oferta['nombre_tienda']); ?>">
                            <?php else: ?>
                                <div class="oferta-placeholder">
                                    <i class="fas fa-store"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Badge de oferta -->
                            <div class="oferta-badge">
                                <i class="fas fa-percent"></i>
                                OFERTA
                            </div>
                        </div>
                        
                        <!-- Contenido de la oferta -->
                        <div class="oferta-content">
                            <!-- Título de la oferta -->
                            <h3 class="oferta-titulo">
                                <?php echo htmlspecialchars($oferta['titulo']); ?>
                            </h3>
                            
                            <!-- Descripción -->
                            <p class="oferta-descripcion">
                                <?php echo htmlspecialchars($oferta['descripcion']); ?>
                            </p>
                            
                            <!-- Información de la tienda -->
                            <div class="tienda-info">
                                <?php if ($oferta['logo']): ?>
                                    <img src="<?php echo htmlspecialchars($oferta['logo']); ?>" 
                                         alt="<?php echo htmlspecialchars($oferta['nombre_tienda']); ?>"
                                         class="tienda-logo">
                                <?php else: ?>
                                    <div class="tienda-logo-placeholder">
                                        <i class="fas fa-store"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="tienda-detalles">
                                    <h5 class="tienda-nombre">
                                        <?php echo htmlspecialchars($oferta['nombre_tienda']); ?>
                                        <span class="badge-premium-small" title="Tienda Premium">
                                            <img src="img/premium-badge.svg" alt="Premium">
                                        </span>
                                    </h5>
                                    <p class="tienda-categoria">
                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($oferta['categoria']); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Fecha de expiración -->
                            <?php if ($oferta['fecha_expiracion']): ?>
                                <?php
                                $fecha_exp = new DateTime($oferta['fecha_expiracion']);
                                $hoy = new DateTime();
                                $dias_restantes = $hoy->diff($fecha_exp)->days;
                                $urgente = $dias_restantes <= 3;
                                ?>
                                <div class="oferta-expiracion <?php echo $urgente ? 'urgente' : ''; ?>">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php if ($dias_restantes == 0): ?>
                                        <strong>¡Último día!</strong>
                                    <?php elseif ($dias_restantes == 1): ?>
                                        Expira mañana
                                    <?php else: ?>
                                        Válido hasta el <?php echo $fecha_exp->format('d/m/Y'); ?>
                                        <?php if ($urgente): ?>
                                            <strong>(¡Solo <?php echo $dias_restantes; ?> días!)</strong>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Botón de acción -->
                            <a href="tienda_detalle.php?id=<?php echo $oferta['tienda_id']; ?>" 
                               class="btn-ver-oferta">
                                <i class="fas fa-store me-2"></i>Ver Tienda
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Mensaje cuando no hay resultados de filtro -->
        <div id="no-filter-results" class="no-ofertas" style="display: none;">
            <i class="fas fa-filter"></i>
            <h3 class="mt-3">No hay ofertas en esta categoría</h3>
            <p class="mb-4">Prueba con otra categoría o explora todas las ofertas.</p>
            <button class="btn btn-outline-modern" onclick="filtrarPorCategoria('todas')">
                <i class="fas fa-th-large me-2"></i>Ver Todas las Ofertas
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Call to Action para Vendedores -->
<?php if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor'): ?>
<section class="cta-ofertas">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="text-white mb-4">¿Quieres publicar tus ofertas aquí?</h2>
                <p class="text-white mb-4" style="font-size: 1.1rem; opacity: 0.9;">
                    Hazte Premium y llega a miles de clientes potenciales con tus ofertas exclusivas
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <?php if (!esta_logueado()): ?>
                        <a href="auth.php#registro" class="btn btn-light btn-modern">
                            <i class="fas fa-crown me-2"></i>Registrar mi Tienda
                        </a>
                    <?php else: ?>
                        <a href="<?php echo obtener_dashboard_url(); ?>" class="btn btn-light btn-modern">
                            <i class="fas fa-crown me-2"></i>Hacerme Premium
                        </a>
                    <?php endif; ?>
                    <a href="directorio.php" class="btn btn-outline-light btn-modern">
                        <i class="fas fa-store me-2"></i>Ver Tiendas
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
// Función para filtrar ofertas por categoría
function filtrarPorCategoria(categoria) {
    const ofertas = document.querySelectorAll('.oferta-item');
    const noResults = document.getElementById('no-filter-results');
    const ofertasGrid = document.getElementById('ofertas-grid');
    const resultsCount = document.getElementById('results-count');
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    let visibleCount = 0;
    
    // Actualizar botones activos
    filterButtons.forEach(btn => {
        btn.classList.remove('active');
        if ((categoria === 'todas' && btn.textContent.includes('Todas')) ||
            btn.textContent.includes(categoria)) {
            btn.classList.add('active');
        }
    });
    
    // Filtrar ofertas
    ofertas.forEach(oferta => {
        const ofertaCategoria = oferta.dataset.categoria;
        
        if (categoria === 'todas' || ofertaCategoria === categoria.toLowerCase()) {
            oferta.style.display = 'block';
            visibleCount++;
        } else {
            oferta.style.display = 'none';
        }
    });
    
    // Actualizar contador
    if (resultsCount) {
        resultsCount.textContent = visibleCount;
    }
    
    // Mostrar/ocultar mensaje de no resultados
    if (visibleCount === 0) {
        noResults.style.display = 'block';
        ofertasGrid.style.display = 'none';
    } else {
        noResults.style.display = 'none';
        ofertasGrid.style.display = 'flex';
    }
    
    // Feedback háptico en móviles
    if (navigator.vibrate) {
        navigator.vibrate(30);
    }
}

// Animaciones de entrada
document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease-out';
            }
        });
    }, observerOptions);

    // Observar todas las cards de ofertas
    document.querySelectorAll('.oferta-card').forEach(card => {
        observer.observe(card);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
