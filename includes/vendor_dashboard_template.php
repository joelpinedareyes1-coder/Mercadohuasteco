<?php
// Verificar que el usuario esté logueado y sea vendedor
if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor') {
    header("Location: auth.php");
    exit();
}

// Obtener información básica de la tienda para el sidebar
$sidebar_tienda_info = null;
try {
    $stmt = $pdo->prepare("SELECT id, nombre_tienda, activo, destacada FROM tiendas WHERE vendedor_id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $sidebar_tienda_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Error silencioso
}

// Determinar la página actual para el menú activo
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Panel Vendedor - Mercado Huasteco</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Asistente Chispitas CSS -->
    <?php include 'asistente_bateria.php'; ?>
    
    <style>
        /* Variables CSS - Mismas que auth.php */
        :root {
            --primary-color: #006666;
            --secondary-color: #CC5500;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --text-dark: #333333;
            --text-light: #ffffff;
            --text-muted: #6c757d;
            --background-light: #f8f9fa;
            --background-dark: #343a40;
            --border-color: #dee2e6;
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
            --shadow-light: 0 5px 15px rgba(0,0,0,0.1);
            --shadow-card: 0 8px 25px rgba(0,0,0,0.15);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --sidebar-width: 280px;
            --navbar-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--background-light);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Layout principal */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--success-color), var(--primary-color));
            color: var(--text-light);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-light);
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-light);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .sidebar-logo i {
            color: var(--warning-color);
        }

        .sidebar.collapsed .sidebar-logo .logo-text {
            display: none;
        }

        /* Información del vendedor en sidebar */
        .vendor-info {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .vendor-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .sidebar.collapsed .vendor-avatar {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }

        .vendor-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .vendor-store {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar.collapsed .vendor-info .vendor-name,
        .sidebar.collapsed .vendor-info .vendor-store {
            display: none;
        }

        /* Menú de navegación */
        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            text-decoration: none;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: var(--text-light);
            border-right: 3px solid var(--warning-color);
        }

        .nav-link i {
            width: 20px;
            margin-right: 1rem;
            text-align: center;
        }

        .sidebar.collapsed .nav-link {
            padding: 1rem;
            justify-content: center;
        }

        .sidebar.collapsed .nav-link .nav-text {
            display: none;
        }

        /* Contenido principal */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
        }

        .sidebar.collapsed + .main-content {
            margin-left: 70px;
        }

        /* Navbar superior */
        .top-navbar {
            background: white;
            height: var(--navbar-height);
            box-shadow: var(--shadow-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--text-dark);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: var(--background-light);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-dropdown {
            position: relative;
        }

        .user-menu-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: none;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-lg);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-menu-btn:hover {
            background: var(--background-light);
        }

        .user-avatar-small {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, var(--success-color), var(--primary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Contenido de la página */
        .page-content {
            padding: 2rem;
        }

        /* Cards modernos */
        .card-modern {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-card);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.05), rgba(0, 102, 102, 0.05));
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title i {
            color: var(--success-color);
        }

        .card-body {
            padding: 2rem;
        }

        /* Formularios modernos */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-label i {
            color: var(--success-color);
            margin-right: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--success-color);
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* Botones modernos */
        .btn-modern {
            background: linear-gradient(135deg, var(--success-color), var(--primary-color));
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius-lg);
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
        }

        .btn-modern.btn-secondary {
            background: linear-gradient(135deg, var(--text-muted), var(--background-dark));
        }

        .btn-modern.btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #fd7e14);
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

        .w-100 {
            width: 100% !important;
        }

        /* Alertas */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius-lg);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
            color: var(--success-color);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(253, 126, 20, 0.1));
            color: var(--danger-color);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        /* Badges */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-lg);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bg-success {
            background: var(--success-color) !important;
            color: white;
        }

        .bg-danger {
            background: var(--danger-color) !important;
            color: white;
        }

        .bg-warning {
            background: var(--warning-color) !important;
            color: var(--text-dark);
        }

        /* Utilidades */
        .d-flex {
            display: flex !important;
        }

        .justify-content-between {
            justify-content: space-between !important;
        }

        .align-items-center {
            align-items: center !important;
        }

        .gap-2 {
            gap: 0.5rem !important;
        }

        .gap-3 {
            gap: 1rem !important;
        }

        .mb-2 {
            margin-bottom: 0.5rem !important;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .mb-4 {
            margin-bottom: 1.5rem !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .text-success {
            color: var(--success-color) !important;
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .text-info {
            color: var(--info-color) !important;
        }

        .text-warning {
            color: var(--warning-color) !important;
        }

        /* Grid system */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -0.75rem;
        }

        .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6,
        .col-md-7, .col-md-8, .col-md-9, .col-md-10, .col-md-11, .col-md-12 {
            padding: 0 0.75rem;
        }

        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; }
        .col-md-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
        .col-md-12 { flex: 0 0 100%; max-width: 100%; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .main-content {
                margin-left: 70px;
            }

            .page-content {
                padding: 1rem;
            }

            .top-navbar {
                padding: 0 1rem;
            }

            .page-title {
                font-size: 1.2rem;
            }

            .col-md-4, .col-md-6, .col-md-8, .col-md-12 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .card-body {
                padding: 1.5rem;
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

        .card-modern {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Función para mostrar notificaciones */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius-lg);
            color: white;
            font-weight: 600;
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            box-shadow: var(--shadow-card);
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, var(--success-color), #20c997);
        }

        .notification.error {
            background: linear-gradient(135deg, var(--danger-color), #fd7e14);
        }
        
        /* ===== RESPONSIVE MEJORADO PARA MÓVILES ===== */
        @media (max-width: 768px) {
            /* Ocultar sidebar por defecto en móvil */
            .sidebar {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                height: 100vh !important;
                transform: translateX(-100%) !important;
                transition: transform 0.3s ease !important;
                z-index: 1050 !important;
                width: 280px !important;
                box-shadow: 2px 0 10px rgba(0,0,0,0.3) !important;
            }
            
            /* Mostrar sidebar cuando esté activo */
            .sidebar.active {
                transform: translateX(0) !important;
            }
            
            /* El contenido principal ocupa todo el ancho */
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            /* Header móvil visible */
            .mobile-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 12px 20px !important;
                background: linear-gradient(135deg, var(--primary-color), var(--success-color)) !important;
                color: white !important;
                position: sticky !important;
                top: 0 !important;
                z-index: 1000 !important;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;
            }
            
            .hamburger-btn {
                background: none !important;
                border: none !important;
                color: white !important;
                font-size: 1.5rem !important;
                cursor: pointer !important;
                padding: 8px !important;
                border-radius: 5px !important;
                transition: background 0.3s ease !important;
            }
            
            .hamburger-btn:hover {
                background: rgba(255,255,255,0.2) !important;
            }
            
            .mobile-logo {
                font-weight: 600 !important;
                font-size: 1.1rem !important;
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
            }
            
            /* Overlay para cerrar sidebar */
            .sidebar-overlay {
                display: none !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background: rgba(0, 0, 0, 0.5) !important;
                z-index: 1040 !important;
                backdrop-filter: blur(2px) !important;
            }
            
            .sidebar-overlay.active {
                display: block !important;
            }
            
            /* Ocultar navbar superior en móvil */
            .top-navbar {
                display: none !important;
            }
            
            /* Ajustar contenido de página */
            .page-content {
                padding: 15px !important;
            }
            
            /* Cards más compactas */
            .card-modern {
                margin-bottom: 15px !important;
            }
            
            .card-header {
                padding: 1rem 1.5rem !important;
            }
            
            .card-body {
                padding: 1.5rem !important;
            }
            
            .card-title {
                font-size: 1.1rem !important;
            }
            
            /* Formularios más compactos */
            .form-group {
                margin-bottom: 1rem !important;
            }
            
            .form-control {
                padding: 0.6rem 0.8rem !important;
                font-size: 0.9rem !important;
            }
            
            /* Botones más grandes para touch */
            .btn-modern {
                padding: 0.8rem 1.2rem !important;
                font-size: 0.9rem !important;
                min-height: 44px !important;
            }
            
            /* Grid responsive */
            .row {
                margin: 0 -10px !important;
            }
            
            .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6,
            .col-md-7, .col-md-8, .col-md-9, .col-md-10, .col-md-11, .col-md-12 {
                padding: 0 10px !important;
                flex: 0 0 100% !important;
                max-width: 100% !important;
                margin-bottom: 15px !important;
            }
            
            /* Sidebar navigation más touch-friendly */
            .nav-link {
                padding: 1.2rem 1.5rem !important;
                font-size: 0.95rem !important;
            }
            
            .nav-link i {
                width: 25px !important;
                font-size: 1.1rem !important;
            }
            
            /* Vendor info más compacta */
            .vendor-avatar {
                width: 50px !important;
                height: 50px !important;
                font-size: 1.2rem !important;
            }
            
            .vendor-name {
                font-size: 0.95rem !important;
            }
            
            .vendor-store {
                font-size: 0.8rem !important;
            }
        }
        
        @media (max-width: 576px) {
            /* Móviles muy pequeños */
            .mobile-header {
                padding: 10px 15px !important;
            }
            
            .mobile-logo {
                font-size: 1rem !important;
            }
            
            .page-content {
                padding: 10px !important;
            }
            
            .card-header {
                padding: 0.8rem 1rem !important;
            }
            
            .card-body {
                padding: 1rem !important;
            }
            
            .sidebar {
                width: 260px !important;
            }
            
            .form-control {
                font-size: 16px !important; /* Evita zoom en iOS */
            }
        }
        
        /* Header móvil oculto por defecto */
        .mobile-header {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Header móvil -->
    <div class="mobile-header">
        <button class="hamburger-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="mobile-logo">
            <i class="fas fa-store"></i> Mercado Huasteco
        </div>
        <div></div> <!-- Espaciador -->
    </div>
    
    <!-- Overlay para cerrar sidebar en móvil -->
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>
    
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <!-- Logo -->
            <div class="sidebar-header">
                <a href="dashboard_vendedor.php" class="sidebar-logo">
                    <i class="fas fa-store"></i>
                    <span class="logo-text">Mercado Huasteco</span>
                </a>
            </div>

            <!-- Información del vendedor -->
            <div class="vendor-info">
                <div class="vendor-avatar">
                    <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                </div>
                <div class="vendor-name"><?php echo htmlspecialchars($_SESSION['nombre']); ?></div>
                <div class="vendor-store">
                    <?php if ($sidebar_tienda_info): ?>
                        <i class="fas fa-store"></i> <?php echo htmlspecialchars($sidebar_tienda_info['nombre_tienda']); ?>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle"></i> Sin tienda
                    <?php endif; ?>
                </div>
            </div>

            <!-- Navegación -->
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard_vendedor.php" class="nav-link <?php echo $current_page === 'dashboard_vendedor.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="panel_vendedor.php" class="nav-link <?php echo $current_page === 'panel_vendedor.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span class="nav-text">Gestionar Tienda</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="galeria_vendedor.php" class="nav-link <?php echo $current_page === 'galeria_vendedor.php' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i>
                        <span class="nav-text">Mi Galería</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="estadisticas_vendedor.php" class="nav-link <?php echo $current_page === 'estadisticas_vendedor.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">Estadísticas</span>
                    </a>
                </div>
                <?php 
                // Verificar si es Premium para mostrar funciones Premium
                $es_premium_sidebar = false;
                try {
                    $stmt_premium = $pdo->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
                    $stmt_premium->execute([$_SESSION['user_id']]);
                    $user_premium = $stmt_premium->fetch(PDO::FETCH_ASSOC);
                    $es_premium_sidebar = ($user_premium && $user_premium['es_premium'] == 1);
                } catch(PDOException $e) {
                    // Error silencioso
                }
                
                if ($es_premium_sidebar): 
                ?>
                <div class="nav-item">
                    <a href="mis_ofertas.php" class="nav-link <?php echo $current_page === 'mis_ofertas.php' ? 'active' : ''; ?>" style="background: rgba(255, 215, 0, 0.1);">
                        <i class="fas fa-ticket-alt" style="color: #FFD700;"></i>
                        <span class="nav-text">Mis Ofertas</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="gestionar_resenas.php" class="nav-link <?php echo $current_page === 'gestionar_resenas.php' ? 'active' : ''; ?>" style="background: rgba(40, 167, 69, 0.1);">
                        <i class="fas fa-comments" style="color: #28a745;"></i>
                        <span class="nav-text">Gestionar Reseñas</span>
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($sidebar_tienda_info): ?>
                <div class="nav-item">
                    <a href="tienda_detalle.php?id=<?php echo $sidebar_tienda_info['id']; ?>" 
                       class="nav-link" target="_blank">
                        <i class="fas fa-eye"></i>
                        <span class="nav-text">Ver Mi Tienda</span>
                    </a>
                </div>
                <?php endif; ?>
                <div class="nav-item">
                    <a href="directorio.php" class="nav-link">
                        <i class="fas fa-search"></i>
                        <span class="nav-text">Explorar Directorio</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="mi_perfil.php" class="nav-link">
                        <i class="fas fa-user-edit"></i>
                        <span class="nav-text">Mi Perfil</span>
                    </a>
                </div>
                <div class="nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-text">Cerrar Sesión</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Contenido principal -->
        <main class="main-content">
            <!-- Navbar superior -->
            <header class="top-navbar">
                <div class="navbar-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title"><?php echo isset($page_title) ? $page_title : 'Panel Vendedor'; ?></h1>
                </div>
                <div class="navbar-right">
                    <div class="user-dropdown">
                        <button class="user-menu-btn">
                            <div class="user-avatar-small">
                                <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Contenido de la página -->
            <div class="page-content">
            
    <!-- JavaScript para funcionalidad móvil -->
    <script>
        // Función para toggle del sidebar en móvil
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }
        
        // Función para cerrar sidebar
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (sidebar && overlay) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        }
        
        // Toggle del sidebar desktop
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    // Solo en desktop
                    if (window.innerWidth > 768) {
                        sidebar.classList.toggle('collapsed');
                    }
                });
            }
            
            // Cerrar sidebar móvil al hacer clic en un enlace
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        closeSidebar();
                    }
                });
            });
            
            // Cerrar sidebar móvil al redimensionar ventana
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeSidebar();
                }
            });
        });
    </script>