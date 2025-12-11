<?php
require_once 'config.php';

// Obtener categoría seleccionada del filtro
$categoria_filtro = isset($_GET['categoria']) ? limpiar_entrada($_GET['categoria']) : '';
$busqueda = isset($_GET['busqueda']) ? limpiar_entrada($_GET['busqueda']) : '';

// Obtener todas las categorías disponibles
try {
    $stmt_categorias = $pdo->prepare("SELECT DISTINCT categoria FROM tiendas ORDER BY categoria");
    $stmt_categorias->execute();
    $categorias_disponibles = $stmt_categorias->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $categorias_disponibles = [];
}

// Verificar si el usuario está logueado y es cliente para mostrar favoritos
$mostrar_favoritos = (esta_logueado() && $_SESSION['rol'] === 'cliente');
$favoritos_usuario = [];

if ($mostrar_favoritos) {
    try {
        $stmt_favoritos = $pdo->prepare("SELECT tienda_id FROM favoritos WHERE usuario_id = ?");
        $stmt_favoritos->execute([$_SESSION['user_id']]);
        $favoritos_usuario = $stmt_favoritos->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {
        $favoritos_usuario = [];
    }
}

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if ($categoria_filtro) {
    $where_conditions[] = "t.categoria = ?";
    $params[] = $categoria_filtro;
}

if ($busqueda) {
    $where_conditions[] = "(t.nombre LIKE ? OR t.descripcion LIKE ? OR t.categoria LIKE ?)";
    $search_term = "%{$busqueda}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Construir la cláusula WHERE solo si hay condiciones
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Obtener tiendas con información del vendedor y calificaciones
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nombre as vendedor_nombre, u.es_premium,
               COALESCE(AVG(c.estrellas), 0) as promedio_calificacion,
               COUNT(c.id) as total_reseñas,
               (SELECT url_imagen FROM galeria_tiendas ft WHERE ft.tienda_id = t.id LIMIT 1) as foto_principal
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id
        {$where_clause}
        GROUP BY t.id, u.nombre, u.es_premium
        ORDER BY u.es_premium DESC, t.es_destacado DESC, t.fecha_registro DESC
    ");
    $stmt->execute($params);
    $tiendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $tiendas = [];
    $error = "Error al cargar las tiendas: " . $e->getMessage();
}

// Obtener estadísticas generales
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tiendas");
    $total_tiendas = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT vendedor_id) as total FROM tiendas");
    $total_vendedores = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM calificaciones");
    $total_reseñas = $stmt->fetch()['total'];
} catch(PDOException $e) {
    $total_tiendas = 0;
    $total_vendedores = 0;
    $total_reseñas = 0;
}

// Configurar variables para el header
$page_title = "Directorio de Tiendas";
$page_description = "Descubre los mejores negocios locales de tu comunidad universitaria";
$body_class = "directorio-page";
$additional_css = ['css/directorio-styles.css'];

// Incluir el header
include 'includes/header.php'; 
?>

    <div class="container" style="margin-top: 100px;">
        <nav class="breadcrumb-modern">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="index.php"><i class="fas fa-home me-1"></i>Inicio</a>
                </li>
                <li class="breadcrumb-item active">Directorio</li>
            </ol>
        </nav>
    </div>

    <!-- Hero Section -->
    <section class="directorio-hero">
        <div class="container text-center">
            <h1 class="display-4 mb-3" style="font-weight: 800;">Directorio de Tiendas</h1>
            <p class="lead mb-4">Descubre los mejores negocios locales de tu comunidad universitaria</p>
            
            <!-- Estadísticas rápidas -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stats-number text-white"><?php echo number_format($total_tiendas); ?></div>
                            <div class="stats-label text-white-50">Tiendas</div>
                        </div>
                        <div class="col-4">
                            <div class="stats-number text-white"><?php echo number_format($total_vendedores); ?></div>
                            <div class="stats-label text-white-50">Vendedores</div>
                        </div>
                        <div class="col-4">
                            <div class="stats-number text-white"><?php echo number_format($total_reseñas); ?></div>
                            <div class="stats-label text-white-50">Reseñas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filtros y Búsqueda -->
    <div class="container">
        <div class="search-filters">
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-filter me-2"></i>Filtrar por Categoría
                    </h5>
                    <div class="d-flex flex-wrap">
                        <button class="filter-btn <?php echo empty($categoria_filtro) ? 'active' : ''; ?>" 
                                onclick="filtrarCategoria('')">
                            <i class="fas fa-th-large me-1"></i>Todas
                            <span class="badge bg-light text-dark ms-1"><?php echo count($tiendas); ?></span>
                        </button>
                        
                        <?php foreach ($categorias_disponibles as $categoria): ?>
                            <?php
                            $count_categoria = count(array_filter($tiendas, function($t) use ($categoria) {
                                return $t['categoria'] === $categoria;
                            }));
                            ?>
                            <button class="filter-btn <?php echo $categoria_filtro === $categoria ? 'active' : ''; ?>" 
                                    onclick="filtrarCategoria('<?php echo htmlspecialchars($categoria); ?>')">
                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($categoria); ?>
                                <span class="badge bg-primary ms-1"><?php echo $count_categoria; ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-search me-2"></i>Buscar Tiendas
                    </h5>
                    <div class="position-relative">
                        <input type="text" 
                               class="form-control search-input-modern" 
                               id="searchInput"
                               placeholder="Buscar por nombre, descripción..."
                               value="<?php echo htmlspecialchars($busqueda); ?>">
                        <button class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2" 
                                id="clearSearch" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if ($categoria_filtro || $busqueda): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php if ($categoria_filtro): ?>
                        Mostrando tiendas de la categoría: <strong><?php echo htmlspecialchars($categoria_filtro); ?></strong>
                    <?php endif; ?>
                    <?php if ($busqueda): ?>
                        <?php echo $categoria_filtro ? ' | ' : ''; ?>Búsqueda: <strong><?php echo htmlspecialchars($busqueda); ?></strong>
                    <?php endif; ?>
                    <a href="directorio.php" class="btn btn-sm btn-outline-info ms-2">
                        <i class="fas fa-times me-1"></i>Limpiar filtros
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Botón Mi Panel (solo visible en móvil) -->
    <?php if (esta_logueado()): ?>
    <div class="container mt-3 d-md-none">
        <a href="<?php echo obtener_dashboard_url(); ?>" class="btn btn-primary-modern w-100 mobile-panel-btn-directorio">
            <i class="fas fa-tachometer-alt me-2"></i>Mi Panel de Control
        </a>
    </div>
    <?php endif; ?>
    
<!-- Contenido Principal -->
    <div class="container mt-5">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger-modern">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($tiendas)): ?>
            <div class="no-results">
                <i class="fas fa-store"></i>
                <h3 class="mt-3">No se encontraron tiendas</h3>
                <?php if ($categoria_filtro || $busqueda): ?>
                    <p class="mb-4">No hay tiendas que coincidan con los filtros aplicados.</p>
                    <a href="directorio.php" class="btn btn-primary-modern">
                        <i class="fas fa-th-large me-2"></i>Ver Todas las Tiendas
                    </a>
                <?php else: ?>
                    <p class="mb-4">Aún no hay tiendas registradas en el directorio.</p>
                    <a href="auth.php#registro" class="btn btn-primary-modern">
                        <i class="fas fa-plus me-2"></i>Registrar mi Tienda
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Contador de resultados -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="fas fa-store me-2 text-primary-modern"></i>
                    <span id="results-count"><?php echo count($tiendas); ?></span> 
                    <?php echo count($tiendas) === 1 ? 'tienda encontrada' : 'tiendas encontradas'; ?>
                </h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-modern btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-1"></i>Actualizar
                    </button>
                </div>
            </div>

            <!-- Grid de Tiendas -->
            <div class="row" id="tiendas-grid">
                <?php foreach ($tiendas as $tienda): ?>
                    <div class="col-lg-4 col-md-6 mb-4 tienda-item" 
                         data-nombre="<?php echo strtolower(htmlspecialchars($tienda['nombre'])); ?>"
                         data-categoria="<?php echo strtolower(htmlspecialchars($tienda['categoria'])); ?>"
                         data-descripcion="<?php echo strtolower(htmlspecialchars($tienda['descripcion'])); ?>">
                        
                        <div class="tienda-card-modern <?php echo $tienda['es_destacado'] ? 'destacada' : ''; ?>">
                            <!-- Imagen de la tienda -->
                            <?php if ($tienda['foto_principal']): ?>
                                <div class="tienda-image-modern" 
                                     style="background-image: url('<?php echo htmlspecialchars($tienda['foto_principal']); ?>');">
                                    <?php if ($tienda['es_destacado']): ?>
                                        <div class="badge-destacada">
                                            <i class="fas fa-star me-1"></i>Destacada
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="tienda-placeholder">
                                    <?php if ($tienda['es_destacado']): ?>
                                        <div class="badge-destacada">
                                            <i class="fas fa-star me-1"></i>Destacada
                                        </div>
                                    <?php endif; ?>
                                    <i class="fas fa-store"></i>
                                    <span>Sin imagen</span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Contenido de la tienda -->
                            <div class="tienda-content">
                                <h3 class="tienda-title">
                                    <?php echo htmlspecialchars($tienda['nombre']); ?>
                                    <?php if (isset($tienda['es_premium']) && $tienda['es_premium']): ?>
                                        <span class="badge-premium-verificado" title="Vendedor Premium Verificado - Mercado Huasteco">
                                            <img src="img/premium-badge.svg" alt="Premium">
                                        </span>
                                    <?php endif; ?>
                                </h3>
                                
                                <p class="tienda-description">
                                    <?php echo htmlspecialchars(substr($tienda['descripcion'], 0, 120)) . (strlen($tienda['descripcion']) > 120 ? '...' : ''); ?>
                                </p>
                                
                                <!-- Meta información -->
                                <div class="tienda-meta">
                                    <div class="tienda-rating">
                                        <?php 
                                        $rating = round($tienda['promedio_calificacion']);
                                        for ($i = 1; $i <= 5; $i++): 
                                        ?>
                                            <i class="fas fa-star <?php echo $i <= $rating ? 'stars-modern' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                        <span class="text-muted ms-1">
                                            (<?php echo $tienda['total_reseñas']; ?>)
                                        </span>
                                    </div>
                                    
                                    <div class="tienda-category">
                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($tienda['categoria']); ?>
                                    </div>
                                </div>
                                
                                <!-- Información del vendedor -->
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-user-circle text-muted me-2"></i>
                                    <small class="text-muted">
                                        Por <?php echo htmlspecialchars($tienda['vendedor_nombre']); ?>
                                    </small>
                                </div>
                                
                                <!-- Acciones -->
                                <div class="tienda-actions">
                                    <!-- Todos los usuarios van a la página de perfil interna -->
                                    <a href="tienda_detalle.php?id=<?php echo $tienda['id']; ?>" 
                                       class="btn-ver-tienda"
                                       title="Ver detalles de la tienda">
                                        <i class="fas fa-eye me-2"></i>Ver Tienda
                                    </a>
                                    
                                    <?php if ($mostrar_favoritos): ?>
                                        <button class="btn-favorito-modern <?php echo in_array($tienda['id'], $favoritos_usuario) ? 'active' : ''; ?>"
                                                onclick="toggleFavorito(<?php echo $tienda['id']; ?>, this)"
                                                title="<?php echo in_array($tienda['id'], $favoritos_usuario) ? 'Quitar de favoritos' : 'Agregar a favoritos'; ?>">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Mensaje cuando no hay resultados de búsqueda -->
            <div id="no-search-results" class="no-results" style="display: none;">
                <i class="fas fa-search"></i>
                <h3 class="mt-3">No se encontraron resultados</h3>
                <p class="mb-4">No hay tiendas que coincidan con tu búsqueda.</p>
                <button class="btn btn-outline-modern" onclick="clearSearch()">
                    <i class="fas fa-times me-2"></i>Limpiar búsqueda
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Call to Action -->
    <?php if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor'): ?>
        <section class="section-padding mt-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
            <div class="container text-center">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <h2 class="text-white mb-4" style="font-size: 2.5rem; font-weight: 700;">
                            ¿Tienes un Negocio?
                        </h2>
                        <p class="text-white mb-4" style="font-size: 1.2rem; opacity: 0.9;">
                            Únete a nuestra comunidad de emprendedores y haz que más estudiantes 
                            descubran tu tienda. Es gratis y fácil de usar.
                        </p>
                        
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <a href="auth.php#registro" class="btn btn-light btn-modern">
                                <i class="fas fa-plus me-2"></i>Registrar mi Tienda
                            </a>
                            <a href="index.php#como-funciona" class="btn btn-outline-light btn-modern" style="border-color: white; color: white;">
                                <i class="fas fa-info-circle me-2"></i>Cómo Funciona
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>


    
    <script>
        // Variables globales
        let todasLasTiendas = document.querySelectorAll('.tienda-item');
        let categoriaActual = '<?php echo $categoria_filtro; ?>';
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-modern');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Función para filtrar por categoría
        function filtrarCategoria(categoria) {
            // Actualizar URL sin recargar la página
            const url = new URL(window.location);
            if (categoria) {
                url.searchParams.set('categoria', categoria);
            } else {
                url.searchParams.delete('categoria');
            }
            window.history.pushState({}, '', url);
            
            // Recargar la página para aplicar el filtro
            window.location.reload();
        }

        // Búsqueda en tiempo real
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearch');
        const noResultsDiv = document.getElementById('no-search-results');
        const tiendasGrid = document.getElementById('tiendas-grid');
        const resultsCount = document.getElementById('results-count');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                if (searchTerm.length > 0) {
                    clearSearchBtn.style.display = 'block';
                } else {
                    clearSearchBtn.style.display = 'none';
                }
                
                filtrarTiendas(searchTerm);
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', clearSearch);
        }

        function clearSearch() {
            searchInput.value = '';
            clearSearchBtn.style.display = 'none';
            filtrarTiendas('');
        }

        function filtrarTiendas(searchTerm) {
            let visibleCount = 0;
            
            todasLasTiendas.forEach(tienda => {
                const nombre = tienda.dataset.nombre;
                const categoria = tienda.dataset.categoria;
                const descripcion = tienda.dataset.descripcion;
                
                const coincide = nombre.includes(searchTerm) || 
                                categoria.includes(searchTerm) || 
                                descripcion.includes(searchTerm);
                
                if (coincide) {
                    tienda.style.display = 'block';
                    visibleCount++;
                } else {
                    tienda.style.display = 'none';
                }
            });
            
            // Actualizar contador
            if (resultsCount) {
                resultsCount.textContent = visibleCount;
            }
            
            // Mostrar/ocultar mensaje de no resultados
            if (visibleCount === 0 && searchTerm.length > 0) {
                noResultsDiv.style.display = 'block';
                tiendasGrid.style.display = 'none';
            } else {
                noResultsDiv.style.display = 'none';
                tiendasGrid.style.display = 'flex';
            }
        }

        // Función para toggle favoritos (si el usuario está logueado)
        <?php if ($mostrar_favoritos): ?>
        function toggleFavorito(tiendaId, button) {
            const isActive = button.classList.contains('active');
            const action = isActive ? 'remove' : 'add';
            
            fetch('api_favoritos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    tienda_id: tiendaId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (action === 'add') {
                        button.classList.add('active');
                        button.title = 'Quitar de favoritos';
                    } else {
                        button.classList.remove('active');
                        button.title = 'Agregar a favoritos';
                    }
                } else {
                    alert('Error al actualizar favoritos: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        }
        <?php endif; ?>

        // Animaciones de entrada
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

        // Observar todas las cards de tiendas
        document.querySelectorAll('.tienda-card-modern').forEach(card => {
            observer.observe(card);
        });

        // Inicializar búsqueda si hay término en URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const busqueda = urlParams.get('busqueda');
            
            if (busqueda && searchInput) {
                searchInput.value = busqueda;
                clearSearchBtn.style.display = 'block';
                filtrarTiendas(busqueda.toLowerCase());
            }
        });
        
        // ===== ANIMACIÓN DE ESTRELLITAS EN INSIGNIA PREMIUM =====
        document.addEventListener('DOMContentLoaded', function() {
            const badges = document.querySelectorAll('.badge-premium-verificado');
            
            badges.forEach(badge => {
                badge.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Agregar clase de animación al badge
                    this.classList.add('clicked');
                    setTimeout(() => {
                        this.classList.remove('clicked');
                    }, 600);
                    
                    // Obtener posición del badge
                    const rect = this.getBoundingClientRect();
                    const centerX = rect.left + rect.width / 2;
                    const centerY = rect.top + rect.height / 2;
                    
                    // Crear estrellitas
                    const starCount = 8;
                    for (let i = 0; i < starCount; i++) {
                        createStar(centerX, centerY, i, starCount);
                    }
                    
                    // Crear confeti
                    const confettiCount = 12;
                    for (let i = 0; i < confettiCount; i++) {
                        createConfetti(centerX, centerY, i, confettiCount);
                    }
                    
                    // Sonido de éxito (opcional)
                    playSuccessSound();
                });
            });
            
            function createStar(x, y, index, total) {
                const star = document.createElement('div');
                star.className = 'star-particle';
                star.innerHTML = '⭐';
                star.style.left = x + 'px';
                star.style.top = y + 'px';
                
                // Calcular dirección de la estrella
                const angle = (360 / total) * index;
                const distance = 60 + Math.random() * 40;
                const tx = Math.cos(angle * Math.PI / 180) * distance;
                const ty = Math.sin(angle * Math.PI / 180) * distance;
                
                star.style.setProperty('--tx', tx + 'px');
                star.style.setProperty('--ty', ty + 'px');
                
                document.body.appendChild(star);
                
                // Eliminar después de la animación
                setTimeout(() => {
                    star.remove();
                }, 1000);
            }
            
            function createConfetti(x, y, index, total) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti-particle';
                confetti.style.left = x + 'px';
                confetti.style.top = y + 'px';
                
                // Calcular dirección del confeti
                const angle = (360 / total) * index + Math.random() * 30;
                const distance = 80 + Math.random() * 60;
                const cx = Math.cos(angle * Math.PI / 180) * distance;
                const cy = Math.sin(angle * Math.PI / 180) * distance + 50; // Caída hacia abajo
                
                confetti.style.setProperty('--cx', cx + 'px');
                confetti.style.setProperty('--cy', cy + 'px');
                
                // Color aleatorio dorado
                const colors = ['#ffd700', '#ffed4e', '#ffa500', '#ffb347'];
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                
                document.body.appendChild(confetti);
                
                // Eliminar después de la animación
                setTimeout(() => {
                    confetti.remove();
                }, 1200);
            }
            
            function playSuccessSound() {
                // Crear un sonido simple usando Web Audio API
                try {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.value = 800;
                    oscillator.type = 'sine';
                    
                    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                    
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.3);
                } catch (e) {
                    // Si no se puede reproducir sonido, no pasa nada
                }
            }
        });
    </script>

<?php include 'includes/footer.php'; ?>
</body>
</html>