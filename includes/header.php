<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title . ' - Mercado Huasteco' : 'Mercado Huasteco - Conectando el talento de la región'; ?></title>
    
    <!-- Meta tags para SEO -->
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Mercado Huasteco es la plataforma que conecta el talento de la región con las mejores tiendas y servicios locales.'; ?>">
    <meta name="keywords" content="tiendas universitarias, directorio local, emprendedores, estudiantes, servicios locales, Huasteca">
    <meta name="author" content="Mercado Huasteco">
    
    <!-- Open Graph para redes sociales -->
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title . ' - Mercado Huasteco' : 'Mercado Huasteco'; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? $page_description : 'Conectando el talento de la región'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Montserrat (Consistente con auth.php) -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Estilos modernos globales -->
    <link rel="stylesheet" href="css/mercado-huasteco-modern.css">
    <!-- Header responsivo con hamburguesa -->
    <link rel="stylesheet" href="css/header-responsive.css">
    <!-- Header móvil optimizado -->
    <link rel="stylesheet" href="css/header-mobile.css">
    <!-- Desktop responsive -->
    <link rel="stylesheet" href="css/desktop-responsive.css">
    <!-- Animaciones globales -->
    <link rel="stylesheet" href="css/animations.css">
    
    <!-- Estilos específicos de página -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo $css_file; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Asistente Chispitas CSS -->
    <?php include 'includes/asistente_bateria.php'; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    
    <!-- JavaScript para mejoras móviles -->
    <script src="js/mobile-enhancements.js" defer></script>
    <!-- JavaScript para mejoras desktop -->
    <script src="js/desktop-enhancements.js" defer></script>
</head>
<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">
    <!-- Header Responsivo con Hamburguesa -->
    <header class="mi-navbar-principal">
        <!-- Logo y nombre (siempre visible) -->
        <div class="navbar-logo-area">
            <img src="img/logo.png" alt="Logo Mercado Huasteco" class="logo-sombrero">
            <div class="logo-text">
                <h1>Mercado Huasteco</h1>
                <p>Conectando el talento de la región.</p>
            </div>
        </div>
        
        <!-- Botón hamburguesa (solo móvil) -->
        <button class="btn-menu-movil" onclick="toggleMobileMenu()" aria-label="Menú de navegación">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Navegación (desktop siempre visible, móvil oculta) -->
        <nav class="navbar-links-area" id="navbarLinks">
            <a href="index.php" class="nav-btn <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </a>
            <a href="directorio.php" class="nav-btn <?php echo basename($_SERVER['PHP_SELF']) == 'directorio.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Directorio</span>
            </a>
            <a href="ofertas.php" class="nav-btn <?php echo basename($_SERVER['PHP_SELF']) == 'ofertas.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Ofertas</span>
            </a>
            
            <?php if (esta_logueado()): ?>
                <!-- Usuario logueado -->
                <div class="user-dropdown">
                    <button class="user-btn nav-btn" onclick="toggleDropdown()">
                        <i class="fas fa-user-circle"></i>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu-custom" id="userDropdown">
                        <a href="<?php echo obtener_dashboard_url(); ?>" class="dropdown-item">
                            <i class="fas fa-tachometer-alt"></i> Mi Panel
                        </a>
                        <a href="mi_perfil.php" class="dropdown-item">
                            <i class="fas fa-user-edit"></i> Mi Perfil
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Usuario no logueado -->
                <a href="auth.php" class="nav-btn auth-btn login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Iniciar Sesión</span>
                </a>
                <a href="auth.php#registro" class="nav-btn auth-btn register-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Registrarse</span>
                </a>
            <?php endif; ?>
        </nav>
    </header>

    <!-- Contenido principal -->
    <main class="main-content">
        <!-- Mensajes globales de sesión -->
        <?php if (isset($_SESSION['mensaje_global'])): ?>
            <div class="container" style="margin-top: 100px;">
                <div class="alert alert-success-modern alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['mensaje_global']; unset($_SESSION['mensaje_global']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_global'])): ?>
            <div class="container" style="margin-top: 100px;">
                <div class="alert alert-danger-modern alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $_SESSION['error_global']; unset($_SESSION['error_global']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

<!-- JavaScript para el header responsivo -->
<script>
// Función para toggle del menú móvil
function toggleMobileMenu() {
    const navLinks = document.getElementById('navbarLinks');
    const hamburgerBtn = document.querySelector('.btn-menu-movil');
    
    if (navLinks && hamburgerBtn) {
        navLinks.classList.toggle('mobile-active');
        
        // Cambiar icono del hamburger
        const icon = hamburgerBtn.querySelector('i');
        if (navLinks.classList.contains('mobile-active')) {
            icon.className = 'fas fa-times';
        } else {
            icon.className = 'fas fa-bars';
        }
        
        // Feedback háptico
        if (navigator.vibrate) {
            navigator.vibrate(30);
        }
    }
}

// Función para dropdown de usuario
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
        
        // Agregar feedback háptico en móviles
        if (navigator.vibrate && window.innerWidth <= 768) {
            navigator.vibrate(30);
        }
    }
}

// Cerrar menús al hacer clic fuera
document.addEventListener('click', function(event) {
    const navLinks = document.getElementById('navbarLinks');
    const hamburgerBtn = document.querySelector('.btn-menu-movil');
    const userDropdown = document.querySelector('.user-dropdown');
    const dropdown = document.getElementById('userDropdown');
    
    // Cerrar menú móvil si se hace clic fuera
    if (navLinks && hamburgerBtn && 
        !navLinks.contains(event.target) && 
        !hamburgerBtn.contains(event.target)) {
        navLinks.classList.remove('mobile-active');
        hamburgerBtn.querySelector('i').className = 'fas fa-bars';
    }
    
    // Cerrar dropdown de usuario si se hace clic fuera
    if (userDropdown && dropdown && !userDropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// Cerrar menú móvil al hacer clic en un enlace
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.navbar-links-area .nav-btn');
    const navbarLinks = document.getElementById('navbarLinks');
    const hamburgerBtn = document.querySelector('.btn-menu-movil');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768 && navbarLinks && hamburgerBtn) {
                navbarLinks.classList.remove('mobile-active');
                hamburgerBtn.querySelector('i').className = 'fas fa-bars';
            }
        });
    });
    
    // Manejar enlaces de registro con hash
    const registerLinks = document.querySelectorAll('a[href*="#registro"]');
    registerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'auth.php';
            
            // Activar modo registro después de cargar
            setTimeout(() => {
                if (typeof switchToSignUp === 'function') {
                    switchToSignUp();
                }
            }, 100);
        });
    });
    
    // Cerrar menú móvil al cambiar orientación
    window.addEventListener('orientationchange', function() {
        setTimeout(() => {
            if (navbarLinks && hamburgerBtn) {
                navbarLinks.classList.remove('mobile-active');
                hamburgerBtn.querySelector('i').className = 'fas fa-bars';
            }
        }, 100);
    });
    
    // ===== SOLUCIÓN: VERIFICAR Y CORREGIR ENLACES DE AUTENTICACIÓN =====
    function verificarEnlacesAuth() {
        if (window.innerWidth <= 768) {
            const loginBtn = document.querySelector('.login-btn');
            const registerBtn = document.querySelector('.register-btn');
            
            // Verificar botón de login
            if (loginBtn) {
                const loginHref = loginBtn.getAttribute('href');
                if (loginHref !== 'auth.php') {
                    console.warn('🔧 Corrigiendo enlace de login:', loginHref, '→ auth.php');
                    loginBtn.setAttribute('href', 'auth.php');
                }
                
                // Asegurar que el texto sea correcto
                const loginSpan = loginBtn.querySelector('span');
                if (loginSpan && loginSpan.textContent !== 'Iniciar Sesión') {
                    console.warn('🔧 Corrigiendo texto de login');
                    loginSpan.textContent = 'Iniciar Sesión';
                }
            }
            
            // Verificar botón de registro
            if (registerBtn) {
                const registerHref = registerBtn.getAttribute('href');
                if (registerHref !== 'auth.php#registro') {
                    console.warn('🔧 Corrigiendo enlace de registro:', registerHref, '→ auth.php#registro');
                    registerBtn.setAttribute('href', 'auth.php#registro');
                }
                
                // Asegurar que el texto sea correcto
                const registerSpan = registerBtn.querySelector('span');
                if (registerSpan && registerSpan.textContent !== 'Registrarse') {
                    console.warn('🔧 Corrigiendo texto de registro');
                    registerSpan.textContent = 'Registrarse';
                }
            }
        }
    }
    
    // Ejecutar verificación al cargar y al cambiar tamaño
    verificarEnlacesAuth();
    window.addEventListener('resize', verificarEnlacesAuth);
});
</script>