<?php
require_once 'config.php';
require_once 'funciones_config.php';

// Verificar que el usuario esté logueado y sea vendedor
if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor') {
    header("Location: auth.php");
    exit();
}

// Configuración de la página
$page_title = "Gestionar Tienda";

$mensaje = '';
$error = '';

// Manejar mensajes de la URL
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ya_premium':
            $mensaje = '¡Ya eres Premium! Disfruta de todos los beneficios de tu suscripción activa.';
            break;
        case 'suscripcion_pendiente':
            $mensaje = 'Ya tienes una suscripción en proceso. Por favor, completa el pago pendiente.';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'suscripcion_error':
            $error = 'Hubo un error al procesar tu suscripción. Por favor, intenta nuevamente o contacta a soporte.';
            break;
    }
}

// Procesar registro/actualización de tienda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tienda') {
    $nombre_tienda = limpiar_entrada($_POST['nombre_tienda']);
    $descripcion = limpiar_entrada($_POST['descripcion']);
    $categoria = limpiar_entrada($_POST['categoria']);
    $categoria_personalizada = limpiar_entrada($_POST['categoria_personalizada'] ?? '');
    
    // Si seleccionó "Otros" y escribió una categoría personalizada, usar esa
    if ($categoria === 'Otros' && !empty($categoria_personalizada)) {
        $categoria = $categoria_personalizada;
    }
    
    $url_tienda = limpiar_entrada($_POST['url_tienda']);
    $telefono_wa = isset($_POST['telefono_wa']) ? limpiar_entrada($_POST['telefono_wa']) : '';
    
    // Redes sociales (solo para Premium)
    $link_facebook = isset($_POST['link_facebook']) ? limpiar_entrada($_POST['link_facebook']) : '';
    $link_instagram = isset($_POST['link_instagram']) ? limpiar_entrada($_POST['link_instagram']) : '';
    $link_tiktok = isset($_POST['link_tiktok']) ? limpiar_entrada($_POST['link_tiktok']) : '';
    
    // Video Premium (solo para Premium)
    $link_video = isset($_POST['link_video']) ? limpiar_entrada($_POST['link_video']) : '';
    
    // Google Maps Premium (solo para Premium)
    $google_maps_src = isset($_POST['google_maps_src']) ? limpiar_entrada($_POST['google_maps_src']) : '';
    
    // Limpiar el número de WhatsApp (solo números)
    if (!empty($telefono_wa)) {
        $telefono_wa = preg_replace('/[^0-9]/', '', $telefono_wa);
    }
    
    // Validaciones
    if (empty($nombre_tienda) || empty($descripcion) || empty($categoria) || empty($url_tienda)) {
        $error = "Los campos marcados con * son obligatorios.";
    } elseif (!filter_var($url_tienda, FILTER_VALIDATE_URL)) {
        $error = "La URL de la tienda no es válida.";
    } elseif (!empty($telefono_wa) && (strlen($telefono_wa) < 10 || strlen($telefono_wa) > 15)) {
        $error = "El número de WhatsApp debe tener entre 10 y 15 dígitos.";
    } else {
        // Procesar logo si se subió
        $logo_path = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/logos/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'logo_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                    $logo_path = $upload_path;
                }
            } else {
                $error = "Solo se permiten imágenes para el logo (JPG, PNG, GIF, WebP).";
            }
        }

        
        if (empty($error)) {
            try {
                // Verificar si ya tiene una tienda
                $stmt = $pdo->prepare("SELECT id, logo FROM tiendas WHERE vendedor_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $tienda_existente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($tienda_existente) {
                    // Actualizar tienda existente
                    $sql = "UPDATE tiendas SET nombre_tienda = ?, descripcion = ?, categoria = ?, url_tienda = ?, telefono_wa = ?, link_facebook = ?, link_instagram = ?, link_tiktok = ?, link_video = ?, google_maps_src = ?";
                    $params = [$nombre_tienda, $descripcion, $categoria, $url_tienda, $telefono_wa, $link_facebook, $link_instagram, $link_tiktok, $link_video, $google_maps_src];
                    
                    if ($logo_path) {
                        $sql .= ", logo = ?";
                        $params[] = $logo_path;
                        
                        // Eliminar logo anterior si existe
                        if ($tienda_existente['logo'] && file_exists($tienda_existente['logo'])) {
                            unlink($tienda_existente['logo']);
                        }
                    }

                    
                    $sql .= " WHERE vendedor_id = ?";
                    $params[] = $_SESSION['user_id'];
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $mensaje = "Información de tu tienda actualizada exitosamente.";
                } else {
                    // Crear nueva tienda
                    $stmt = $pdo->prepare("INSERT INTO tiendas (vendedor_id, nombre_tienda, descripcion, categoria, url_tienda, telefono_wa, link_facebook, link_instagram, link_tiktok, logo, link_video, google_maps_src) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $nombre_tienda, $descripcion, $categoria, $url_tienda, $telefono_wa, $link_facebook, $link_instagram, $link_tiktok, $logo_path, $link_video, $google_maps_src]);
                    
                    $mensaje = "¡Tienda registrada exitosamente! Ya puedes comenzar a recibir visitas.";
                }
            } catch(PDOException $e) {
                $error = "Error al guardar la tienda: " . $e->getMessage();
            }
        }
    }
}

// Obtener información de la tienda del vendedor
$tienda_info = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM tiendas WHERE vendedor_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $tienda_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Error silencioso
}

// Obtener estadísticas básicas
$estadisticas = [
    'total_visitas' => 0,
    'total_calificaciones' => 0,
    'promedio_calificacion' => 0,
    'total_fotos' => 0
];

if ($tienda_info) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.clics as total_visitas,
                COALESCE(AVG(c.estrellas), 0) as promedio_calificacion,
                COUNT(c.id) as total_calificaciones,
                (SELECT COUNT(*) FROM galeria_tiendas g WHERE g.tienda_id = t.id AND g.activo = 1) as total_fotos
            FROM tiendas t 
            LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
            WHERE t.id = ?
            GROUP BY t.id
        ");
        $stmt->execute([$tienda_info['id']]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats) {
            $estadisticas = $stats;
        }
    } catch(PDOException $e) {
        // Error silencioso
    }
}

// Incluir template del dashboard
include 'includes/vendor_dashboard_template.php';
?>

<!-- Contenido específico de la página -->
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <?php if ($mensaje): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

<!-- Estadísticas rápidas -->
<?php if ($tienda_info): ?>
    <div class="row justify-content-center mb-4">
        <div class="col-12">
            <div class="row">
                <div class="col-md-3">
            <div class="card-modern text-center" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1)); border: 1px solid rgba(40, 167, 69, 0.2);">
                <div class="card-body">
                    <i class="fas fa-eye" style="font-size: 2rem; color: var(--success-color); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--success-color); margin-bottom: 0.5rem;"><?php echo number_format($estadisticas['total_visitas']); ?></h3>
                    <p class="text-muted mb-0">Visitas Totales</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern text-center" style="background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(253, 126, 20, 0.1)); border: 1px solid rgba(255, 193, 7, 0.2);">
                <div class="card-body">
                    <i class="fas fa-star" style="font-size: 2rem; color: var(--warning-color); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--warning-color); margin-bottom: 0.5rem;"><?php echo number_format($estadisticas['promedio_calificacion'], 1); ?></h3>
                    <p class="text-muted mb-0">Calificación</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern text-center" style="background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(32, 201, 151, 0.1)); border: 1px solid rgba(23, 162, 184, 0.2);">
                <div class="card-body">
                    <i class="fas fa-comments" style="font-size: 2rem; color: var(--info-color); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--info-color); margin-bottom: 0.5rem;"><?php echo $estadisticas['total_calificaciones']; ?></h3>
                    <p class="text-muted mb-0">Reseñas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern text-center" style="background: linear-gradient(135deg, rgba(0, 102, 102, 0.1), rgba(204, 85, 0, 0.1)); border: 1px solid rgba(0, 102, 102, 0.2);">
                <div class="card-body">
                    <i class="fas fa-images" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;"><?php echo $estadisticas['total_fotos']; ?></h3>
                    <p class="text-muted mb-0">Fotos</p>
                </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// Obtener información del usuario para los banners
$usuario_info = obtenerInfoUsuario($pdo, $_SESSION['user_id']);
$es_premium_activo = $usuario_info && esPremiumActivo($usuario_info['fecha_expiracion_premium']);

// Banner de "Ya eres Premium" (solo si ES Premium activo)
if ($es_premium_activo):
    $fecha_expiracion = new DateTime($usuario_info['fecha_expiracion_premium']);
    $dias_restantes = (new DateTime())->diff($fecha_expiracion)->days;
?>
<div class="row justify-content-center mb-4">
    <div class="col-12">
        <div class="card-modern" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; overflow: hidden; position: relative;">
            <div class="card-body" style="padding: 2rem; position: relative; z-index: 2;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-crown" style="font-size: 2.5rem; color: #FFD700;"></i>
                            </div>
                            <div>
                                <h2 style="color: white; margin: 0; font-weight: 800; font-size: 1.8rem;">
                                    ¡Ya Eres Premium! 🎉
                                </h2>
                                <p style="color: rgba(255,255,255,0.95); margin: 0.5rem 0 0 0; font-size: 1.1rem;">
                                    Disfruta de todas las funciones exclusivas de tu membresía
                                </p>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(255,255,255,0.15); border-radius: 10px; backdrop-filter: blur(10px);">
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                                <div>
                                    <div style="color: rgba(255,255,255,0.9); font-size: 0.9rem; margin-bottom: 0.25rem;">
                                        Tu suscripción expira el:
                                    </div>
                                    <div style="color: white; font-size: 1.3rem; font-weight: 700;">
                                        <?php echo $fecha_expiracion->format('d/m/Y'); ?>
                                    </div>
                                </div>
                                <div>
                                    <div style="color: rgba(255,255,255,0.9); font-size: 0.9rem; margin-bottom: 0.25rem;">
                                        Días restantes:
                                    </div>
                                    <div style="color: #FFD700; font-size: 1.3rem; font-weight: 700;">
                                        <?php echo $dias_restantes; ?> días
                                    </div>
                                </div>
                                <div>
                                    <a href="gestionar_suscripcion.php" class="btn-modern" style="background: rgba(255,255,255,0.25); color: white; border: 2px solid white; padding: 0.75rem 1.5rem; font-weight: 600;">
                                        <i class="fas fa-cog me-2"></i>
                                        Gestionar Suscripción
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 15px; padding: 1.5rem; border: 2px solid rgba(255,255,255,0.3);">
                            <div style="color: white; font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                                Funciones Activas:
                            </div>
                            <div style="display: grid; gap: 0.5rem; text-align: left;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: white; font-size: 0.9rem;">
                                    <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                    <span>Fotos Ilimitadas</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: white; font-size: 0.9rem;">
                                    <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                    <span>Videos</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: white; font-size: 0.9rem;">
                                    <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                    <span>Cupones y Ofertas</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: white; font-size: 0.9rem;">
                                    <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                    <span>WhatsApp Directo</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: white; font-size: 0.9rem;">
                                    <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                    <span>Insignia Premium</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Decoración de fondo -->
            <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; z-index: 1;"></div>
            <div style="position: absolute; bottom: -30px; left: -30px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%; z-index: 1;"></div>
        </div>
    </div>
</div>
<?php 
// Banner de Premium (solo si NO es Premium activo)
elseif ($usuario_info && !$es_premium_activo):
?>
<div class="row justify-content-center mb-4">
    <div class="col-12">
        <div class="card-modern" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; overflow: hidden; position: relative;">
            <div class="card-body" style="padding: 2.5rem; position: relative; z-index: 2;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                            <i class="fas fa-crown" style="font-size: 3rem; color: #FFD700;"></i>
                            <div>
                                <h2 style="color: white; margin: 0; font-weight: 800; font-size: 1.8rem;">
                                    ¡Lleva tu Tienda al Siguiente Nivel!
                                </h2>
                                <p style="color: rgba(255,255,255,0.9); margin: 0.5rem 0 0 0; font-size: 1.1rem;">
                                    Desbloquea todas las funciones Premium por solo <strong>$150 MXN/mes</strong>
                                </p>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: white;">
                                <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                <span>Hasta 10 fotos</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: white;">
                                <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                <span>Videos Promocionales</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: white;">
                                <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                <span>Cupones y Ofertas</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: white;">
                                <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                <span>WhatsApp Directo</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: white;">
                                <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                <span>Redes Sociales</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: white;">
                                <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                <span>Google Maps</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: white;">
                                <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                <span>Responder Reseñas</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: white;">
                                <i class="fas fa-check-circle" style="color: #4ade80;"></i>
                                <span>Insignia Premium</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 15px; padding: 2rem; border: 2px solid rgba(255,255,255,0.3);">
                            <div style="font-size: 3rem; color: #FFD700; margin-bottom: 1rem;">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div style="color: white; font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
                                $150 HUASTECOINS 🛒🤠                                   
                            </div>
                            <div style="color: rgba(255,255,255,0.9); font-size: 1rem; margin-bottom: 1.5rem;">
                                MXN / 30 días
                            </div>
                            <a href="crear_pago_mp.php" class="btn-modern" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; font-weight: 800; padding: 1rem 2rem; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4); width: 100%; justify-content: center;">
                                <i class="fas fa-rocket me-2"></i>
                                ¡Activar Premium Ahora!
                            </a>
                            <p style="color: rgba(255,255,255,0.8); font-size: 0.85rem; margin-top: 1rem; margin-bottom: 0;">
                                <i class="fas fa-lock me-1"></i> Pago seguro con Mercado Pago
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Decoración de fondo -->
            <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; z-index: 1;"></div>
            <div style="position: absolute; bottom: -30px; left: -30px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%; z-index: 1;"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <!-- Formulario de tienda -->
    <div class="col-md-10 col-lg-8">
        <div class="card-modern">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-store"></i>
                    <?php echo $tienda_info ? 'Editar Información de la Tienda' : 'Registrar Mi Tienda'; ?>
                </h3>
            </div>
            <div class="card-body" style="padding: 2rem;">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="tienda">
                    
                    <!-- Sección 1: Información Básica -->
                    <div class="form-section mb-4">
                        <h5 class="section-title mb-3" style="color: var(--primary-color); font-weight: 600; border-bottom: 2px solid var(--primary-color); padding-bottom: 0.5rem;">
                            <i class="fas fa-info-circle me-2"></i>Información Básica
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold" for="nombre_tienda">
                                        <i class="fas fa-store text-primary me-2"></i>Nombre de la Tienda *
                                    </label>
                                    <input type="text" id="nombre_tienda" name="nombre_tienda" 
                                           class="form-control form-control-lg" required 
                                           value="<?php echo $tienda_info ? htmlspecialchars($tienda_info['nombre_tienda']) : ''; ?>"
                                           placeholder="Ej: Tienda de Tecnología Juan"
                                           style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold" for="categoria">
                                        <i class="fas fa-tag text-warning me-2"></i>Categoría *
                                    </label>
                                    <select id="categoria" name="categoria" 
                                            class="form-control form-control-lg" required 
                                            onchange="toggleCategoriaPersonalizada()"
                                            style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease;">
                                        <option value="">Selecciona una categoría</option>
                                        <option value="Tecnología" <?php echo ($tienda_info && $tienda_info['categoria'] === 'Tecnología') ? 'selected' : ''; ?>>Tecnología</option>
                                        <option value="Ropa y Accesorios" <?php echo ($tienda_info && $tienda_info['categoria'] === 'Ropa y Accesorios') ? 'selected' : ''; ?>>Ropa y Accesorios</option>
                                        <option value="Comida y Bebidas" <?php echo ($tienda_info && $tienda_info['categoria'] === 'Comida y Bebidas') ? 'selected' : ''; ?>>Comida y Bebidas</option>
                                        <option value="Libros y Papelería" <?php echo ($tienda_info && $tienda_info['categoria'] === 'Libros y Papelería') ? 'selected' : ''; ?>>Libros y Papelería</option>
                                        <option value="Deportes" <?php echo ($tienda_info && $tienda_info['categoria'] === 'Deportes') ? 'selected' : ''; ?>>Deportes</option>
                                        <option value="Salud y Belleza" <?php echo ($tienda_info && $tienda_info['categoria'] === 'Salud y Belleza') ? 'selected' : ''; ?>>Salud y Belleza</option>
                                        <option value="Hogar y Jardín" <?php echo ($tienda_info && $tienda_info['categoria'] === 'Hogar y Jardín') ? 'selected' : ''; ?>>Hogar y Jardín</option>
                                        <option value="Servicios" <?php echo ($tienda_info && $tienda_info['categoria'] === 'Servicios') ? 'selected' : ''; ?>>Servicios</option>
                                        <option value="Otros" <?php echo ($tienda_info && !in_array($tienda_info['categoria'], ['Tecnología', 'Ropa y Accesorios', 'Comida y Bebidas', 'Libros y Papelería', 'Deportes', 'Salud y Belleza', 'Hogar y Jardín', 'Servicios'])) ? 'selected' : ''; ?>>Otros</option>
                                    </select>
                                </div>
                                
                                <!-- Campo para categoría personalizada -->
                                <div class="form-group mb-3" id="categoria_personalizada_group" style="display: none;">
                                    <label class="form-label fw-semibold" for="categoria_personalizada">
                                        <i class="fas fa-edit text-info me-2"></i>Escribe tu categoría
                                    </label>
                                    <input type="text" id="categoria_personalizada" name="categoria_personalizada" 
                                           class="form-control form-control-lg" 
                                           value="<?php echo ($tienda_info && !in_array($tienda_info['categoria'], ['Tecnología', 'Ropa y Accesorios', 'Comida y Bebidas', 'Libros y Papelería', 'Deportes', 'Salud y Belleza', 'Hogar y Jardín', 'Servicios'])) ? htmlspecialchars($tienda_info['categoria']) : ''; ?>"
                                           placeholder="Ej: Artesanías, Mascotas, Electrónicos, etc."
                                           style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección 2: Descripción y URL -->
                    <div class="form-section mb-4">
                        <h5 class="section-title mb-3" style="color: var(--success-color); font-weight: 600; border-bottom: 2px solid var(--success-color); padding-bottom: 0.5rem;">
                            <i class="fas fa-align-left me-2"></i>Descripción y Enlaces
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold" for="descripcion">
                                        <i class="fas fa-file-alt text-success me-2"></i>Descripción de la Tienda *
                                    </label>
                                    <textarea id="descripcion" name="descripcion" 
                                              class="form-control" required rows="5"
                                              placeholder="Describe tu tienda, productos y servicios..."
                                              style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease; resize: vertical;"><?php echo $tienda_info ? htmlspecialchars($tienda_info['descripcion']) : ''; ?></textarea>
                                    <small class="text-muted">Incluye palabras clave que tus clientes buscarían</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold" for="url_tienda">
                                        <i class="fas fa-link text-info me-2"></i>URL de tu Tienda *
                                    </label>
                                    <input type="url" id="url_tienda" name="url_tienda" 
                                           class="form-control form-control-lg" required 
                                           value="<?php echo $tienda_info ? htmlspecialchars($tienda_info['url_tienda']) : ''; ?>"
                                           placeholder="https://mitienda.com"
                                           style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease;">
                                    <small class="text-muted">Enlace principal donde los clientes pueden ver tus productos</small>
                                </div>
                                
                                <!-- Campo de WhatsApp (Solo Premium) -->
                                <?php
                                // Verificar si el usuario es Premium
                                $stmt_premium = $pdo->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
                                $stmt_premium->execute([$_SESSION['user_id']]);
                                $usuario_premium = $stmt_premium->fetch(PDO::FETCH_ASSOC);
                                $es_premium = $usuario_premium && $usuario_premium['es_premium'] == 1;
                                ?>
                                
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold" for="telefono_wa">
                                        <i class="fab fa-whatsapp text-success me-2"></i>WhatsApp
                                        <?php if ($es_premium): ?>
                                            <span class="badge bg-warning text-dark ms-2">
                                                <i class="fas fa-crown me-1"></i>Premium
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary ms-2">
                                                <i class="fas fa-lock me-1"></i>Solo Premium
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="tel" id="telefono_wa" name="telefono_wa" 
                                           class="form-control form-control-lg" 
                                           value="<?php echo $tienda_info && isset($tienda_info['telefono_wa']) ? htmlspecialchars($tienda_info['telefono_wa']) : ''; ?>"
                                           placeholder="52181XXXXXXX (con código de país)"
                                           maxlength="15"
                                           <?php echo !$es_premium ? 'disabled' : ''; ?>
                                           style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease;">
                                    <small class="text-muted">
                                        <?php if ($es_premium): ?>
                                            Incluye código de país (ej: 52 para México). Los clientes podrán contactarte directamente.
                                        <?php else: ?>
                                            <i class="fas fa-info-circle me-1"></i>Actualiza a Premium para habilitar contacto directo por WhatsApp
                                        <?php endif; ?>
                                    </small>
                                </div>
                                
                                <!-- Campos de Redes Sociales (Solo Premium) -->
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold" for="link_facebook">
                                        <i class="fab fa-facebook text-primary me-2"></i>Facebook
                                        <?php if ($es_premium): ?>
                                            <span class="badge bg-warning text-dark ms-2">
                                                <i class="fas fa-crown me-1"></i>Premium
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary ms-2">
                                                <i class="fas fa-lock me-1"></i>Solo Premium
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="url" id="link_facebook" name="link_facebook" 
                                           class="form-control form-control-lg" 
                                           value="<?php echo $tienda_info && isset($tienda_info['link_facebook']) ? htmlspecialchars($tienda_info['link_facebook']) : ''; ?>"
                                           placeholder="https://facebook.com/tutienda"
                                           <?php echo !$es_premium ? 'disabled' : ''; ?>
                                           style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease;">
                                    <small class="text-muted">
                                        <?php if ($es_premium): ?>
                                            URL completa de tu página de Facebook
                                        <?php else: ?>
                                            <i class="fas fa-info-circle me-1"></i>Actualiza a Premium para agregar tus redes sociales
                                        <?php endif; ?>
                                    </small>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold" for="link_instagram">
                                        <i class="fab fa-instagram text-danger me-2"></i>Instagram
                                        <?php if ($es_premium): ?>
                                            <span class="badge bg-warning text-dark ms-2">
                                                <i class="fas fa-crown me-1"></i>Premium
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary ms-2">
                                                <i class="fas fa-lock me-1"></i>Solo Premium
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="url" id="link_instagram" name="link_instagram" 
                                           class="form-control form-control-lg" 
                                           value="<?php echo $tienda_info && isset($tienda_info['link_instagram']) ? htmlspecialchars($tienda_info['link_instagram']) : ''; ?>"
                                           placeholder="https://instagram.com/tutienda"
                                           <?php echo !$es_premium ? 'disabled' : ''; ?>
                                           style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease;">
                                    <small class="text-muted">
                                        <?php if ($es_premium): ?>
                                            URL completa de tu perfil de Instagram
                                        <?php else: ?>
                                            <i class="fas fa-info-circle me-1"></i>Actualiza a Premium para agregar tus redes sociales
                                        <?php endif; ?>
                                    </small>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold" for="link_tiktok">
                                        <i class="fab fa-tiktok text-dark me-2"></i>TikTok
                                        <?php if ($es_premium): ?>
                                            <span class="badge bg-warning text-dark ms-2">
                                                <i class="fas fa-crown me-1"></i>Premium
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary ms-2">
                                                <i class="fas fa-lock me-1"></i>Solo Premium
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="url" id="link_tiktok" name="link_tiktok" 
                                           class="form-control form-control-lg" 
                                           value="<?php echo $tienda_info && isset($tienda_info['link_tiktok']) ? htmlspecialchars($tienda_info['link_tiktok']) : ''; ?>"
                                           placeholder="https://tiktok.com/@tutienda"
                                           <?php echo !$es_premium ? 'disabled' : ''; ?>
                                           style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease;">
                                    <small class="text-muted">
                                        <?php if ($es_premium): ?>
                                            URL completa de tu perfil de TikTok
                                        <?php else: ?>
                                            <i class="fas fa-info-circle me-1"></i>Actualiza a Premium para agregar tus redes sociales
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección 3: Logo -->
                    <div class="form-section mb-4">
                        <h5 class="section-title mb-3" style="color: var(--warning-color); font-weight: 600; border-bottom: 2px solid var(--warning-color); padding-bottom: 0.5rem;">
                            <i class="fas fa-image me-2"></i>Logo de la Tienda
                        </h5>
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold" for="logo">
                                        <i class="fas fa-upload text-warning me-2"></i>Subir Logo
                                    </label>
                                    <input type="file" id="logo" name="logo" 
                                           class="form-control form-control-lg" accept="image/*"
                                           style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease;">
                                    <small class="text-muted">Formatos: JPG, PNG, GIF, WebP (Recomendado: 200x200px)</small>
                                </div>
                            </div>
                            <?php if ($tienda_info && $tienda_info['logo']): ?>
                            <div class="col-md-6">
                                <div class="text-center">
                                    <div class="logo-preview-container" style="display: inline-block; position: relative; background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(248,250,252,0.9)); padding: 15px; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                                        <img src="<?php echo htmlspecialchars($tienda_info['logo']); ?>" 
                                             alt="Logo actual" 
                                             class="logo-preview"
                                             style="width: 100px; height: 100px; object-fit: contain; object-position: center; border-radius: 15px; border: 3px solid #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                    </div>
                                    <small class="d-block text-muted mt-2" style="font-weight: 500;">Logo actual</small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Sección 4: Video Premium -->
                    <div class="form-section mb-4">
                        <h5 class="section-title mb-3" style="color: var(--accent-color); font-weight: 600; border-bottom: 2px solid var(--accent-color); padding-bottom: 0.5rem;">
                            <i class="fas fa-video me-2"></i>Video de Presentación
                            <?php if ($es_premium): ?>
                                <span class="badge bg-warning text-dark ms-2">
                                    <i class="fas fa-crown me-1"></i>Premium
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary ms-2">
                                    <i class="fas fa-lock me-1"></i>Solo Premium
                                </span>
                            <?php endif; ?>
                        </h5>
                        
                        <?php if (!$es_premium): ?>
                            <div class="alert alert-info" style="border-radius: 12px; border-left: 4px solid var(--info-color);">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>¡Muestra tu negocio con un video!</strong>
                                <p class="mb-0 mt-2">Los usuarios Premium pueden agregar un video de YouTube o Vimeo que aparecerá en su página de tienda. Perfecto para mostrar productos, hacer tours virtuales o presentar tu negocio.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold" for="link_video">
                                        <i class="fab fa-youtube text-danger me-2"></i>URL del Video (YouTube o Vimeo)
                                        <?php if ($es_premium): ?>
                                            <span class="badge bg-warning text-dark ms-2">
                                                <i class="fas fa-crown me-1"></i>Premium
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="url" 
                                           id="link_video" 
                                           name="link_video" 
                                           class="form-control form-control-lg" 
                                           placeholder="https://www.youtube.com/embed/..."
                                           value="<?php echo $tienda_info ? htmlspecialchars($tienda_info['link_video'] ?? '') : ''; ?>"
                                           <?php echo !$es_premium ? 'disabled' : ''; ?>
                                           style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease;">
                                    
                                    <?php if ($es_premium): ?>
                                    <div class="alert alert-info mt-2" style="border-radius: 10px; border-left: 4px solid #0dcaf0;">
                                        <strong><i class="fas fa-lightbulb me-2"></i>¿Cómo obtener la URL del video?</strong>
                                        <ol class="mb-0 mt-2 small">
                                            <li>Ve a tu video en <a href="https://www.youtube.com" target="_blank">YouTube</a></li>
                                            <li>Haz clic en <strong>"Compartir"</strong> debajo del video</li>
                                            <li>Selecciona <strong>"Incorporar"</strong></li>
                                            <li>Copia la URL que está dentro de <code>src="..."</code></li>
                                            <li>Pega esa URL aquí</li>
                                        </ol>
                                        <small class="text-muted mt-2 d-block">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Ejemplo: https://www.youtube.com/embed/dQw4w9WgXcQ
                                        </small>
                                    </div>
                                    <?php else: ?>
                                    <small class="text-muted">
                                        <i class="fas fa-crown me-1"></i>Actualiza a Premium para agregar un video de presentación
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Google Maps (Solo Premium) -->
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold" for="google_maps_src">
                                        <i class="fas fa-map-marked-alt text-danger me-2"></i>URL de Google Maps (Embed)
                                        <?php if ($es_premium): ?>
                                            <span class="badge bg-warning text-dark ms-2">
                                                <i class="fas fa-crown me-1"></i>Premium
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="url" 
                                           id="google_maps_src" 
                                           name="google_maps_src" 
                                           class="form-control form-control-lg" 
                                           placeholder="https://www.google.com/maps/embed?pb=..."
                                           value="<?php echo $tienda_info ? htmlspecialchars($tienda_info['google_maps_src'] ?? '') : ''; ?>"
                                           <?php echo !$es_premium ? 'disabled' : ''; ?>
                                           style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.75rem 1rem; transition: all 0.3s ease;">
                                    
                                    <?php if ($es_premium): ?>
                                    <div class="alert alert-info mt-2" style="border-radius: 10px; border-left: 4px solid #0dcaf0;">
                                        <strong><i class="fas fa-lightbulb me-2"></i>¿Cómo obtener la URL del mapa?</strong>
                                        <ol class="mb-0 mt-2 small">
                                            <li>Ve a <a href="https://www.google.com/maps" target="_blank">Google Maps</a></li>
                                            <li>Busca tu negocio o dirección</li>
                                            <li>Haz clic en <strong>"Compartir"</strong></li>
                                            <li>Selecciona <strong>"Incorporar un mapa"</strong></li>
                                            <li>Copia la URL que está dentro de <code>src="..."</code></li>
                                            <li>Pega esa URL aquí</li>
                                        </ol>
                                        <small class="text-muted mt-2 d-block">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Ejemplo: https://www.google.com/maps/embed?pb=1234567890...
                                        </small>
                                    </div>
                                    <?php else: ?>
                                    <small class="text-muted">
                                        <i class="fas fa-crown me-1"></i>Actualiza a Premium para mostrar tu ubicación con Google Maps
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botón de envío -->
                    <div class="text-center mt-4">
                        <button type="submit" class="btn-modern btn-primary btn-lg px-5 py-3" style="border-radius: 12px; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                            <i class="fas fa-save me-2"></i>
                            <?php echo $tienda_info ? 'Actualizar Tienda' : 'Registrar Tienda'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Panel de información centrado -->
<div class="row justify-content-center mt-4">
    <div class="col-md-10 col-lg-8">
        <div class="row">
            <div class="col-md-6">
                <?php if ($tienda_info): ?>
                    <!-- Estado actual de la tienda -->
                    <div class="card-modern mb-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i>
                        Estado de tu Tienda
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <?php if ($tienda_info['activo']): ?>
                            <span class="badge bg-success me-2">
                                <i class="fas fa-check-circle"></i> Activa
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger me-2">
                                <i class="fas fa-times-circle"></i> Inactiva
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($tienda_info['destacada']): ?>
                            <span class="badge bg-warning">
                                <i class="fas fa-star"></i> Destacada
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-muted mb-3">
                        <strong>Registrada:</strong> <?php echo date('d/m/Y', strtotime($tienda_info['fecha_registro'])); ?>
                    </p>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="tienda_detalle.php?id=<?php echo $tienda_info['id']; ?>" 
                           class="btn-modern btn-primary" target="_blank">
                            <i class="fas fa-eye"></i> Ver Tienda
                        </a>
                        <a href="galeria_vendedor.php" class="btn-modern btn-secondary">
                            <i class="fas fa-images"></i> Galería
                        </a>
                        <?php if ($es_premium): ?>
                        <a href="mis_ofertas.php" class="btn-modern" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: white; border: none;">
                            <i class="fas fa-ticket-alt"></i> Mis Ofertas
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <!-- Consejos -->
                <div class="card-modern">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-lightbulb"></i>
                    Consejos para tu Tienda
                </h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h5 style="color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-camera"></i> Fotos Atractivas
                    </h5>
                    <p class="text-muted" style="font-size: 0.9rem;">
                        Sube fotos de alta calidad de tus productos. Las tiendas con más fotos reciben 3x más visitas.
                    </p>
                </div>
                
                <div class="mb-3">
                    <h5 style="color: var(--info-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-star"></i> Descripción Completa
                    </h5>
                    <p class="text-muted" style="font-size: 0.9rem;">
                        Describe detalladamente tus productos y servicios. Incluye palabras clave que tus clientes buscarían.
                    </p>
                </div>
                
                <div class="mb-3">
                    <h5 style="color: var(--warning-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-clock"></i> Mantén Actualizado
                    </h5>
                    <p class="text-muted" style="font-size: 0.9rem;">
                        Actualiza regularmente tu información, horarios y productos para mantener a tus clientes informados.
                    </p>
                </div>
                
                <div class="text-center mt-3">
                    <a href="estadisticas_tienda.php" class="btn-modern btn-outline">
                        <i class="fas fa-chart-line"></i> Ver Estadísticas Detalladas
                    </a>
                    <?php if ($es_premium): ?>
                    <a href="mis_ofertas.php" class="btn-modern mt-2" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: white; border: none; display: block;">
                        <i class="fas fa-ticket-alt"></i> Gestionar Mis Ofertas
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>
</div>

<style>
/* Estilos mejorados para el formulario */
.form-control:focus {
    border-color: var(--primary-color) !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15) !important;
    transform: translateY(-1px);
}

.form-control-lg {
    font-size: 1rem;
    font-weight: 500;
}

.section-title {
    position: relative;
    display: inline-block;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 30px;
    height: 3px;
    background: linear-gradient(90deg, currentColor, transparent);
    border-radius: 2px;
}

.logo-preview:hover {
    transform: scale(1.08);
    box-shadow: 0 8px 30px rgba(0,0,0,0.2) !important;
    border-color: var(--primary-color) !important;
}

.logo-preview-container:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 35px rgba(0,0,0,0.12) !important;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.form-section {
    background: rgba(248, 250, 252, 0.3);
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.form-section:hover {
    background: rgba(248, 250, 252, 0.5);
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}

.card-modern {
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    border: none;
    border-radius: 20px;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, var(--primary-color), #0056b3);
    color: white;
    border: none;
    padding: 1.5rem 2rem;
}

/* Animaciones suaves */
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

.form-section {
    animation: fadeInUp 0.6s ease-out;
}

.form-section:nth-child(2) { animation-delay: 0.1s; }
.form-section:nth-child(3) { animation-delay: 0.2s; }
.form-section:nth-child(4) { animation-delay: 0.3s; }
</style>

<script>
function toggleCategoriaPersonalizada() {
    const categoriaSelect = document.getElementById('categoria');
    const categoriaPersonalizadaGroup = document.getElementById('categoria_personalizada_group');
    const categoriaPersonalizadaInput = document.getElementById('categoria_personalizada');
    
    if (categoriaSelect.value === 'Otros') {
        categoriaPersonalizadaGroup.style.display = 'block';
        categoriaPersonalizadaInput.required = true;
        categoriaPersonalizadaInput.focus();
    } else {
        categoriaPersonalizadaGroup.style.display = 'none';
        categoriaPersonalizadaInput.required = false;
        categoriaPersonalizadaInput.value = '';
    }
}

// Ejecutar al cargar la página para mostrar el campo si ya está seleccionado "Otros"
document.addEventListener('DOMContentLoaded', function() {
    toggleCategoriaPersonalizada();
});
</script>

<?php include 'includes/vendor_dashboard_footer.php'; ?>
