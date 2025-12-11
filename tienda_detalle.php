<?php
require_once 'config.php';

// Obtener ID de la tienda
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: directorio.php");
    exit();
}

$tienda_id = (int)$_GET['id'];

// Obtener información de la tienda con promedio de calificaciones
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nombre as vendedor_nombre,
               COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
               COUNT(c.id) as total_calificaciones
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE t.id = ? AND t.activo = 1 AND u.activo = 1
        GROUP BY t.id, u.nombre
    ");
    $stmt->execute([$tienda_id]);
    $tienda = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tienda) {
        header("Location: directorio.php");
        exit();
    }
} catch(PDOException $e) {
    header("Location: directorio.php");
    exit();
}

// ============================================
// SISTEMA MEJORADO DE CONTEO DE VISITAS
// ============================================
// Solo cuenta visitas reales de clientes potenciales
// Excluye: dueño de la tienda y refreshes de página

$contar_visita = false;

// 1. FILTRO: ¿Hay un usuario logueado?
if (isset($_SESSION['user_id'])) {
    // 2. FILTRO: ¿El usuario logueado es el DUEÑO de esta tienda?
    if ($_SESSION['user_id'] != $tienda['vendedor_id']) {
        // No es el dueño, SÍ podría ser una visita válida
        $contar_visita = true;
    }
    // Si SÍ es el dueño, $contar_visita sigue en 'false' y no se cuenta
} else {
    // No hay nadie logueado (es un visitante público), SÍ contamos la visita
    $contar_visita = true;
}

// 3. FILTRO FINAL: ¿Ya la vio en esta sesión? (evita refreshes/F5)
if ($contar_visita === true) {
    // Inicializar array de vistas recientes si no existe
    if (!isset($_SESSION['vistas_recientes'])) {
        $_SESSION['vistas_recientes'] = array();
    }
    
    // Verificar si esta tienda NO está en el array de vistas recientes
    if (!in_array($tienda_id, $_SESSION['vistas_recientes'])) {
        // ¡VISITA VÁLIDA! Incrementar contador
        try {
            $stmt = $pdo->prepare("UPDATE tiendas SET clics = COALESCE(clics, 0) + 1 WHERE id = ? AND activo = 1");
            $stmt->execute([$tienda_id]);
            
            // Registrar visita detallada para estadísticas
            $ip_visitante = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt_visita = $pdo->prepare("INSERT INTO visitas_tienda (tienda_id, fecha_visita, ip_visitante, user_agent) VALUES (?, NOW(), ?, ?)");
            $stmt_visita->execute([$tienda_id, $ip_visitante, $user_agent]);
            
            // Agregar al array de sesión para no volver a contarla
            $_SESSION['vistas_recientes'][] = $tienda_id;
            
            // Log para debugging (opcional)
            error_log("Visita válida registrada para tienda $tienda_id");
        } catch(PDOException $e) {
            // Continuar sin incrementar si hay error
            error_log("Error incrementando clics: " . $e->getMessage());
        }
    }
    // Si ya estaba en el array, no hace nada (evita contar refreshes)
}
// Si $contar_visita es false (es el dueño), no se ejecuta nada

// ============================================
// FIN DEL SISTEMA DE CONTEO DE VISITAS
// ============================================

// Obtener todas las calificaciones aprobadas de la tienda
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre as usuario_nombre
        FROM calificaciones c
        INNER JOIN usuarios u ON c.user_id = u.id
        WHERE c.tienda_id = ? AND c.activo = 1 AND c.esta_aprobada = 1
        ORDER BY c.fecha_calificacion DESC
    ");
    $stmt->execute([$tienda_id]);
    $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: registrar cuántas calificaciones se encontraron
    error_log("Calificaciones encontradas para tienda $tienda_id: " . count($calificaciones));
} catch(PDOException $e) {
    $calificaciones = [];
    error_log("Error obteniendo calificaciones: " . $e->getMessage());
}

// Obtener fotos de la galería de la tienda
try {
    $stmt = $pdo->prepare("
        SELECT * FROM galeria_tiendas 
        WHERE tienda_id = ? AND activo = 1 
        ORDER BY fecha_subida DESC
    ");
    $stmt->execute([$tienda_id]);
    $fotos_galeria = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $fotos_galeria = [];
}

// Verificar si el usuario ya calificó esta tienda
$ya_califico = false;
$calificacion_usuario = null;
if (esta_logueado()) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM calificaciones WHERE user_id = ? AND tienda_id = ? AND activo = 1");
        $stmt->execute([$_SESSION['user_id'], $tienda_id]);
        $calificacion_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        $ya_califico = ($calificacion_usuario !== false);
    } catch(PDOException $e) {
        $ya_califico = false;
    }
}

// Procesar nueva calificación
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && esta_logueado()) {
    // Debug: verificar que se reciben los datos
    error_log("POST recibido - Estrellas: " . (isset($_POST['estrellas']) ? $_POST['estrellas'] : 'NO SET'));
    error_log("POST recibido - Comentario: " . (isset($_POST['comentario']) ? substr($_POST['comentario'], 0, 50) : 'NO SET'));
    
    $estrellas = isset($_POST['estrellas']) ? (int)$_POST['estrellas'] : 0;
    $comentario = isset($_POST['comentario']) ? limpiar_entrada($_POST['comentario']) : '';
    
    // Validaciones mejoradas
    if ($estrellas < 1 || $estrellas > 5) {
        $error = "La calificación debe ser entre 1 y 5 estrellas.";
    } elseif (empty($comentario)) {
        $error = "El comentario es obligatorio.";
    } elseif (strlen($comentario) < 10) {
        $error = "El comentario debe tener al menos 10 caracteres.";
    } elseif (strlen($comentario) > 1000) {
        $error = "El comentario no puede exceder 1000 caracteres.";
    } elseif ($_SESSION['user_id'] == $tienda['vendedor_id']) {
        $error = "No puedes calificar tu propia tienda.";
    } else {
        try {
            // Verificar si existe una calificación (activa o inactiva)
            $stmt_check = $pdo->prepare("SELECT id, activo FROM calificaciones WHERE user_id = ? AND tienda_id = ?");
            $stmt_check->execute([$_SESSION['user_id'], $tienda_id]);
            $calificacion_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($calificacion_existente) {
                // Actualizar calificación existente (activa o inactiva)
                $stmt = $pdo->prepare("UPDATE calificaciones SET estrellas = ?, comentario = ?, fecha_calificacion = CURRENT_TIMESTAMP, activo = 1, esta_aprobada = 1 WHERE user_id = ? AND tienda_id = ?");
                $stmt->execute([$estrellas, $comentario, $_SESSION['user_id'], $tienda_id]);
                $mensaje = "Tu reseña ha sido " . ($calificacion_existente['activo'] ? 'actualizada' : 'publicada') . " exitosamente.";
                error_log("Reseña actualizada/reactivada exitosamente");
            } else {
                // Crear nueva calificación
                $stmt = $pdo->prepare("INSERT INTO calificaciones (user_id, tienda_id, estrellas, comentario, esta_aprobada, activo) VALUES (?, ?, ?, ?, 1, 1)");
                $stmt->execute([$_SESSION['user_id'], $tienda_id, $estrellas, $comentario]);
                $mensaje = "Tu reseña ha sido publicada exitosamente.";
                error_log("Nueva reseña creada exitosamente");
            }
            
            // Recargar página para mostrar cambios
            header("Location: tienda_detalle.php?id=" . $tienda_id . "&success=1");
            exit();
            
        } catch(PDOException $e) {
            error_log("Error PDO: " . $e->getMessage());
            $error = "Error al guardar la reseña: " . $e->getMessage();
        }
    }
}

// Función para mostrar estrellas
function mostrar_estrellas($promedio, $total_calificaciones = 0, $size = 'normal') {
    $estrellas_html = '';
    $promedio_redondeado = round($promedio, 1);
    $class_size = ($size === 'large') ? 'fs-4' : '';
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $promedio_redondeado) {
            $estrellas_html .= '<i class="bi bi-star-fill text-warning ' . $class_size . '"></i>';
        } elseif ($i - 0.5 <= $promedio_redondeado) {
            $estrellas_html .= '<i class="bi bi-star-half text-warning ' . $class_size . '"></i>';
        } else {
            $estrellas_html .= '<i class="bi bi-star text-muted ' . $class_size . '"></i>';
        }
    }
    
    if ($total_calificaciones > 0) {
        $estrellas_html .= ' <span class="text-muted">(' . $promedio_redondeado . ' - ' . $total_calificaciones . ' reseñas)</span>';
    } else {
        $estrellas_html .= ' <span class="text-muted">Sin reseñas aún</span>';
    }
    
    return $estrellas_html;
}

function mostrar_estrellas_calificacion($estrellas) {
    $estrellas_html = '<span class="stars-container stars-medium">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $estrellas) {
            $estrellas_html .= '<i class="bi bi-star-fill star-icon filled"></i>';
        } else {
            $estrellas_html .= '<i class="bi bi-star star-icon empty"></i>';
        }
    }
    $estrellas_html .= '</span>';
    return $estrellas_html;
}

// Función mejorada para mostrar estrellas con más opciones
function mostrar_estrellas_avanzado($promedio, $total_calificaciones = 0, $size = 'normal', $show_number = true) {
    $promedio_redondeado = round($promedio, 1);
    
    // Determinar clase de tamaño
    $size_class = '';
    switch($size) {
        case 'small':
            $size_class = 'stars-small';
            break;
        case 'large':
            $size_class = 'stars-large';
            break;
        case 'xl':
            $size_class = 'stars-large';
            break;
        default:
            $size_class = 'stars-medium';
    }
    
    $estrellas_html = '<span class="stars-container ' . $size_class . '">';
    
    // Generar estrellas
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($promedio_redondeado)) {
            $estrellas_html .= '<i class="bi bi-star-fill star-icon filled"></i>';
        } elseif ($i - 0.5 <= $promedio_redondeado) {
            $estrellas_html .= '<i class="bi bi-star-half star-icon half"></i>';
        } else {
            $estrellas_html .= '<i class="bi bi-star star-icon empty"></i>';
        }
    }
    
    $estrellas_html .= '</span>';
    
    // Añadir información numérica si se solicita
    if ($show_number && $total_calificaciones > 0) {
        $estrellas_html .= ' <span class="text-muted ms-2">(' . $promedio_redondeado . ' • ' . $total_calificaciones . ' reseñas)</span>';
    } elseif ($show_number) {
        $estrellas_html .= ' <span class="text-muted ms-2">Sin reseñas</span>';
    }
    
    return $estrellas_html;
}

// Función para extraer ID de video de YouTube o Vimeo
function extraer_video_id($url) {
    if (empty($url)) {
        return null;
    }
    
    $video_info = ['platform' => null, 'id' => null];
    
    // YouTube
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $matches)) {
        $video_info['platform'] = 'youtube';
        $video_info['id'] = $matches[1];
    } elseif (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $matches)) {
        $video_info['platform'] = 'youtube';
        $video_info['id'] = $matches[1];
    } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $matches)) {
        $video_info['platform'] = 'youtube';
        $video_info['id'] = $matches[1];
    }
    // Vimeo
    elseif (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        $video_info['platform'] = 'vimeo';
        $video_info['id'] = $matches[1];
    }
    
    return $video_info['id'] ? $video_info : null;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tienda['nombre_tienda']); ?> - Mercado Huasteco</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Colores principales - Consistentes con auth.php */
            --primary-color: #006666;
            --secondary-color: #CC5500;
            --accent-color: #FF6B6B;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-color: #333333;
            --light-color: #f6f5f7;
            
            /* Colores de texto - Consistentes con auth.php */
            --text-primary: #333333;
            --text-secondary: #666666;
            --text-muted: #999999;
            --text-light: #ffffff;
            
            /* Colores de fondo - Consistentes con auth.php */
            --bg-primary: #ffffff;
            --bg-secondary: #f6f5f7;
            --bg-muted: #eee;
            --bg-overlay: rgba(255, 255, 255, 0.95);
            
            /* Bordes redondeados consistentes */
            --border-radius-sm: 8px;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --border-radius-xl: 20px;
            --border-radius-2xl: 24px;
            --border-radius-round: 50px;
            
            /* Sombras consistentes - Basadas en auth.php */
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 5px 15px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.2);
            --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.25);
            --shadow-2xl: 0 25px 50px rgba(0, 0, 0, 0.3);
            
            /* Transiciones consistentes */
            --transition-fast: all 0.15s ease;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Espaciado consistente */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            
            /* Tipografía - Consistente con auth.php */
            --font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-weight-normal: 400;
            --font-weight-medium: 500;
            --font-weight-semibold: 600;
            --font-weight-bold: 700;
            --font-weight-extrabold: 800;
        }

        body {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, #e2e8f0 100%);
            font-family: var(--font-family);
            line-height: 1.6;
            color: var(--text-primary);
        }

        /* ===== TIPOGRAFÍA UNIFICADA ===== */
        h1, h2, h3, h4, h5, h6 {
            font-weight: var(--font-weight-bold);
            letter-spacing: -0.025em;
            color: var(--text-primary);
        }

        .lead {
            font-size: 1.125rem;
            font-weight: var(--font-weight-normal);
            opacity: 0.9;
            color: var(--text-secondary);
        }

        /* ===== SISTEMA DE BOTONES UNIFICADO ===== */
        .btn-modern {
            border-radius: var(--border-radius);
            font-weight: var(--font-weight-semibold);
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            text-decoration: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--text-light);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary-modern:hover {
            background: linear-gradient(135deg, #1d4ed8, #0891b2);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: var(--text-light);
        }

        .btn-secondary-modern {
            background: var(--bg-primary);
            color: var(--text-primary);
            border: 2px solid var(--primary-color);
            box-shadow: var(--shadow-xs);
        }

        .btn-secondary-modern:hover {
            background: var(--primary-color);
            color: var(--text-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-success-modern {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: var(--text-light);
            box-shadow: var(--shadow-sm);
        }

        .btn-success-modern:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: var(--text-light);
        }

        .btn-outline-modern {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline-modern:hover {
            background: var(--primary-color);
            color: var(--text-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .btn-sm-modern {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: var(--border-radius-sm);
        }

        .btn-lg-modern {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: var(--border-radius-lg);
        }

        .btn-round-modern {
            border-radius: var(--border-radius-round);
            padding: 0.75rem 2rem;
        }

        /* ===== SISTEMA DE CAMPOS DE TEXTO UNIFICADO ===== */
        .form-control-modern {
            border-radius: var(--border-radius);
            border: 2px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: var(--transition);
            font-family: var(--font-family);
            font-size: 0.95rem;
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        .form-control-modern:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
            background: var(--bg-primary);
        }

        .form-control-modern::placeholder {
            color: var(--text-muted);
        }

        .form-label-modern {
            font-weight: var(--font-weight-semibold);
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
            font-size: 0.9rem;
        }

        /* ===== SISTEMA DE TARJETAS UNIFICADO ===== */
        .card-modern {
            background: var(--bg-overlay);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-xl);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .card-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color));
        }

        .card-modern:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .card-header-modern {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
            padding-bottom: var(--spacing-md);
            border-bottom: 2px solid rgba(37, 99, 235, 0.1);
        }

        .card-icon-modern {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 1.5rem;
        }

        /* ===== SISTEMA DE ALERTAS UNIFICADO ===== */
        .alert-modern {
            border-radius: var(--border-radius);
            border: none;
            padding: var(--spacing-md) var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-weight: var(--font-weight-medium);
        }

        .alert-success-modern {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(6, 182, 212, 0.1));
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger-modern {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(245, 101, 101, 0.1));
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .alert-info-modern {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(6, 182, 212, 0.1));
            color: var(--info-color);
            border-left: 4px solid var(--info-color);
        }

        .alert-warning-modern {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(251, 191, 36, 0.1));
            color: var(--warning-color);
            border-left: 4px solid var(--warning-color);
        }

        /* ===== SISTEMA DE BADGES UNIFICADO ===== */
        .badge-modern {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--border-radius-round);
            font-size: 0.85rem;
            font-weight: var(--font-weight-semibold);
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        .badge-success-modern {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .badge-warning-modern {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border-color: rgba(245, 158, 11, 0.2);
        }

        .badge-danger-modern {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border-color: rgba(239, 68, 68, 0.2);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="rgba(255,255,255,0.1)"/><stop offset="100%" stop-color="rgba(255,255,255,0)"/></radialGradient></defs><circle cx="200" cy="300" r="150" fill="url(%23a)"/><circle cx="800" cy="200" r="100" fill="url(%23a)"/></svg>');
            opacity: 0.4;
        }
        
        .tienda-logo {
            width: 220px;
            height: 220px;
            object-fit: contain;
            object-position: center;
            border-radius: var(--border-radius-lg);
            border: 6px solid rgba(255, 255, 255, 0.9);
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px;
        }
        
        .tienda-logo:hover {
            transform: scale(1.05) rotate(2deg);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .logo-placeholder {
            width: 220px;
            height: 220px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            color: var(--primary-color);
            border: 6px solid rgba(255, 255, 255, 0.9);
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 2;
        }
        
        /* Alias para compatibilidad con código existente */
        .info-card {
            background: var(--bg-overlay);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-xl);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color));
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
            padding-bottom: var(--spacing-md);
            border-bottom: 2px solid rgba(37, 99, 235, 0.1);
        }

        .info-card-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 1.5rem;
        }
        
        .calificacion-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .calificacion-card:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-visitar {
            background: linear-gradient(135deg, var(--success-color), var(--secondary-color));
            border: none;
            font-weight: var(--font-weight-bold);
            padding: 1.2rem 2.5rem;
            font-size: 1.1rem;
            border-radius: var(--border-radius-round);
            color: var(--text-light);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            transition: var(--transition);
            box-shadow: var(--shadow-md);
        }
        
        .btn-visitar:hover {
            background: linear-gradient(135deg, #059669, #0891b2);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            color: var(--text-light);
        }

        .btn-visitar-funcional {
            background: linear-gradient(135deg, var(--success-color), var(--secondary-color)) !important;
            border: none !important;
            font-weight: var(--font-weight-bold) !important;
            padding: 1.2rem 2.5rem !important;
            font-size: 1.1rem !important;
            border-radius: var(--border-radius-round) !important;
            color: var(--text-light) !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: var(--spacing-sm) !important;
            transition: var(--transition) !important;
            box-shadow: var(--shadow-md) !important;
            position: relative !important;
            z-index: 1000 !important;
            pointer-events: auto !important;
        }
        
        .btn-visitar-funcional:hover {
            background: linear-gradient(135deg, #059669, #0891b2) !important;
            transform: translateY(-3px) !important;
            box-shadow: var(--shadow-lg) !important;
            color: var(--text-light) !important;
            text-decoration: none !important;
        }
        
        /* Botón de WhatsApp */
        .btn-whatsapp {
            background: linear-gradient(135deg, #25D366, #128C7E) !important;
            border: none !important;
            color: white !important;
            padding: 1rem 2rem !important;
            border-radius: 50px !important;
            font-weight: 700 !important;
            font-size: 1.1rem !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3) !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .btn-whatsapp i {
            font-size: 1.5rem !important;
        }
        
        .btn-whatsapp:hover {
            background: linear-gradient(135deg, #128C7E, #075E54) !important;
            transform: translateY(-3px) scale(1.05) !important;
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.5) !important;
            color: white !important;
            text-decoration: none !important;
        }
        
        .btn-whatsapp::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-whatsapp:hover::before {
            width: 300px;
            height: 300px;
        }
        
        /* Botones de Redes Sociales en Header */
        .btn-social-header {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            margin: 0 0.25rem;
        }
        
        .btn-social-header::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.4s, height 0.4s;
        }
        
        .btn-social-header:hover::before {
            width: 100px;
            height: 100px;
        }
        
        .btn-social-header:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .btn-social-header.facebook {
            background: linear-gradient(135deg, #1877f2, #0c63d4);
        }
        
        .btn-social-header.facebook:hover {
            background: linear-gradient(135deg, #0c63d4, #084d9e);
        }
        
        .btn-social-header.instagram {
            background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }
        
        .btn-social-header.instagram:hover {
            background: linear-gradient(135deg, #e6683c 0%, #dc2743 25%, #cc2366 50%, #bc1888 75%, #a01070 100%);
        }
        
        .btn-social-header.tiktok {
            background: linear-gradient(135deg, #000000, #1a1a1a);
        }
        
        .btn-social-header.tiktok:hover {
            background: linear-gradient(135deg, #1a1a1a, #333333);
        }
        
        .btn-social-header.share {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .btn-social-header.share:hover {
            background: linear-gradient(135deg, #764ba2, #5a3d7a);
        }
        
        .btn-social-header.report {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }
        
        .btn-social-header.report:hover {
            background: linear-gradient(135deg, #f5576c, #d43f54);
        }
        
        /* Animación de éxito al compartir */
        @keyframes shareSuccess {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .btn-social-header.share.success {
            animation: shareSuccess 0.5s ease;
        }
        
        /* Notificación moderna */
        .notificacion-moderna {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-family: var(--font-family);
            font-weight: 500;
            z-index: 10000;
            opacity: 0;
            transform: translateX(400px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .notificacion-moderna.show {
            opacity: 1;
            transform: translateX(0);
        }
        
        .notificacion-moderna.success {
            border-left: 4px solid var(--success-color);
        }
        
        .notificacion-moderna.success i {
            color: var(--success-color);
            font-size: 1.5rem;
        }
        
        .notificacion-moderna.error {
            border-left: 4px solid var(--danger-color);
        }
        
        .notificacion-moderna.error i {
            color: var(--danger-color);
            font-size: 1.5rem;
        }
        
        /* Responsive para notificaciones */
        @media (max-width: 768px) {
            .notificacion-moderna {
                top: 10px;
                right: 10px;
                left: 10px;
                transform: translateY(-100px);
            }
            
            .notificacion-moderna.show {
                transform: translateY(0);
            }
        }
        
        /* Video Responsive */
        .video-section {
            margin-bottom: 2rem;
        }
        
        .video-responsive {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            background: #000;
        }
        
        .video-responsive iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: var(--border-radius-lg);
        }
        
        /* Animación de pulso para redes sociales */
        @keyframes pulse-social {
            0%, 100% {
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            }
            50% {
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            }
        }
        
        .social-link {
            animation: pulse-social 2s ease-in-out infinite;
        }
        
        .social-link:hover {
            animation: none;
        }
        
        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .stars-container {
            display: flex;
            gap: 2px;
        }

        .star-icon {
            font-size: 1.8rem;
            color: var(--warning-color);
            filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.3));
        }

        .star-empty {
            color: #e5e7eb;
        }

        .rating-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-left: 0.5rem;
        }

        .rating-count {
            color: #6b7280;
            font-size: 0.95rem;
        }
        


        .gallery-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .gallery-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--warning-color), var(--accent-color));
        }

        .gallery-carousel {
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            position: relative;
        }

        .carousel-item img {
            height: 500px;
            object-fit: cover;
            width: 100%;
            transition: var(--transition);
        }

        .carousel-item:hover img {
            transform: scale(1.02);
        }

        .carousel-caption {
            background: linear-gradient(transparent, rgba(0,0,0,0.9));
            border-radius: var(--border-radius);
            padding: 1.5rem;
            backdrop-filter: blur(10px);
        }

        .gallery-thumbnails {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            overflow-x: auto;
            padding: 1rem 0;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-color) transparent;
        }

        .gallery-thumbnails::-webkit-scrollbar {
            height: 6px;
        }

        .gallery-thumbnails::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
            border-radius: 3px;
        }

        .gallery-thumbnails::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .thumbnail-item {
            flex-shrink: 0;
            width: 100px;
            height: 75px;
            border-radius: var(--border-radius);
            overflow: hidden;
            cursor: pointer;
            transition: var(--transition);
            border: 3px solid transparent;
            position: relative;
        }

        .thumbnail-item:hover {
            border-color: var(--primary-color);
            transform: scale(1.1) rotate(2deg);
            box-shadow: var(--shadow-md);
        }

        .thumbnail-item.active {
            border-color: var(--warning-color);
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }

        .thumbnail-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .gallery-placeholder {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(139, 92, 246, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: var(--primary-color);
            border: 2px dashed var(--primary-color);
            font-weight: 600;
        }

        .gallery-placeholder i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            opacity: 0.7;
        }

        .meta-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .meta-badge {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        .destacado-badge {
            background: linear-gradient(135deg, var(--warning-color), #f97316);
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .breadcrumb-modern {
            background: transparent;
            padding: 1rem 0;
        }

        .breadcrumb-modern .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb-modern .breadcrumb-item.active {
            color: #6b7280;
        }

        .form-modern {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-control {
            border-radius: var(--border-radius);
            border: 2px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .alert-modern {
            border-radius: var(--border-radius);
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success-modern {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(6, 182, 212, 0.1));
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger-modern {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(245, 101, 101, 0.1));
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        /* Estilos para las tarjetas de información detallada */
        .info-detail-card {
            background: rgba(255, 255, 255, 0.8);
            border-radius: var(--border-radius);
            padding: 1.25rem;
            border: 1px solid rgba(37, 99, 235, 0.1);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 1rem;
            height: 100%;
        }

        .info-detail-card:hover {
            background: rgba(255, 255, 255, 1);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .info-detail-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .info-detail-card h6 {
            color: var(--primary-color);
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .description-section {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.05), rgba(139, 92, 246, 0.05));
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            border-left: 4px solid var(--primary-color);
        }

        .description-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #4a5568;
        }

        /* Mejoras en las reseñas */
        .calificacion-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .calificacion-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), transparent);
            border-radius: 0 0 0 100px;
        }

        .calificacion-card:hover {
            transform: translateX(8px);
            box-shadow: var(--shadow-lg);
            border-left-color: var(--warning-color);
        }

        .usuario-avatar {
            width: 50px;
            height: 50px;
            background: linear);
            
       
      
            justify;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
            margin-right: 1rem;
            flex;
        }

        .calificacion-header {
            display: flex;
            align-;
            marem;
        }

        .calificacion-met
            flex: 1;
        }

        .usuario-nombre {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .calificacion-fecha {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .calificacion-estrellas {
            margin-left: auto;
            font-size: 1.25rem;
        }

        .calificacion-comentario {
            font-size: 1rem;
            line-hei
            color: #4a5568;
            margin-bottom: 0;
            position: relative;
            z-index: 1;
        }

        /* Estrellas de calificación interactivas */
        .rating-star {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 5px;
        }
        
        .rating-star:hover,
        .rating-star.active {
            color: #ffc107;
            transform: scale(1.1);
        }

        /* ===== SISTEMA DE ESTRELLAS MEJORADO ===== */
        .stars-container {
            display: inline-flex;
            align-items: center;
            gap: 0.15rem;
        }

        .star-icon {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.1));
        }

        .star-icon.filled {
            color: #fbbf24 !important;
            animation: starGlow 2s ease-in-out infinite alternate;
        }

        .star-icon.half {
            color: #fbbf24 !important;
        }

        .star-icon.empty {
            color: #e5e7eb !important;
        }

        .star-icon:hover {
            transform: scale(1.1);
            filter: drop-shadow(0 2px 8px rgba(251, 191, 36, 0.4));
        }

        /* Animación de brillo para estrellas */
        @keyframes starGlow {
            0% { 
                filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.1)) drop-shadow(0 0 8px rgba(251, 191, 36, 0.3));
            }
            100% { 
                filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.1)) drop-shadow(0 0 12px rgba(251, 191, 36, 0.6));
            }
        }

        /* Tamaños de estrellas */
        .stars-large .star-icon {
            font-size: 1.5rem;
        }

        .stars-medium .star-icon {
            font-size: 1.1rem;
        }

        .stars-small .star-icon {
            font-size: 0.9rem;
        }

        /* Efecto hover para contenedores */
        .stars-container:hover .star-icon.filled {
            animation-duration: 0.8s;
        }

        /* Mejoras para estrellas existentes */
        .bi-star-fill {
            color: #fbbf24 !important;
            filter: drop-shadow(0 1px 3px rgba(251, 191, 36, 0.3));
            transition: all 0.3s ease;
        }

        .bi-star-half {
            color: #fbbf24 !important;
            filter: drop-shadow(0 1px 3px rgba(251, 191, 36, 0.3));
            transition: all 0.3s ease;
        }

        .bi-star {
            color: #e5e7eb !important;
            transition: all 0.3s ease;
        }

        /* Hover effects para estrellas Bootstrap Icons */
        .bi-star-fill:hover,
        .bi-star-half:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 2px 8px rgba(251, 191, 36, 0.5));
        }

        /* Efectos de carga suave */
        .fade-in {
            animation: fadeIn 0.6s ease-in-ou
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: tra20px);
            }
            to {
                opacity: 1;
                transform: tra);
            }
        }

        /* Estilos para las tarjetas de información detallada */
        .info-detail-card {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(37, 99, 235, 0.1);
            border-radius: var(--border-radius);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
            height: 100%;
        }

        .info-detail-card:hover {
            background: rgba(255, 255, 255, 1);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .info-detail-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .info-detail-card h6 {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .description-section {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.05), rgba(139, 92, 246, 0.05));
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            border-left: 4px solid var(--primary-color);
        }

        .description-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #4a5568;
        }

        /* Mejoras en las reseñas */
        .reseñas-section {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .reseñas-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--success-color), var(--secondary-color));
        }

        .reseña-item {
            background: rgba(255, 255, 255, 0.9);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--warning-color);
            transition: var(--transition);
            position: relative;
        }

        .reseña-item:hover {
            transform: translateX(8px);
            box-shadow: var(--shadow-md);
            background: rgba(255, 255, 255, 1);
        }

        .reseña-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .reseña-usuario-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .reseña-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .reseña-meta {
            text-align: right;
        }

        .reseña-fecha {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .reseña-estrellas {
            color: var(--warning-color);
            font-size: 1.1rem;
        }

        .reseña-comentario {
            font-size: 1rem;
            line-height: 1.7;
            color: #374151;
            margin-bottom: 0;
        }

        /* Formulario de reseña mejorado */
        .form-reseña-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-xl);
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .form-reseña-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-color), var(--warning-color));
        }

        /* Estadísticas de la tienda */
        .tienda-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.9);
            border-radius: var(--border-radius);
            padding: 1.5rem 1rem;
            text-align: center;
            border: 1px solid rgba(37, 99, 235, 0.1);
            transition: var(--transition);
        }

        .stat-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-sm);
            border-color: var(--primary-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Animación para las estrellas de calificación */
        @keyframes starPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        .rating-input.hover {
            animation: starPulse 0.3s ease-in-out;
        }

        /* Scrollbar personalizado para reseñas */
        .reseñas-list::-webkit-scrollbar {
            width: 6px;
        }

        .reseñas-list::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
            border-radius: var(--border-radius-sm);
        }

        .reseñas-list::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: var(--border-radius-sm);
        }

        .reseñas-list::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* ===== SISTEMA DE MODALES UNIFICADO ===== */
        .modal-modern .modal-content {
            border-radius: var(--border-radius-xl);
            border: none;
            box-shadow: var(--shadow-2xl);
            backdrop-filter: blur(20px);
        }

        .modal-modern .modal-header {
            border-bottom: 2px solid rgba(37, 99, 235, 0.1);
            padding: var(--spacing-lg);
        }

        .modal-modern .modal-body {
            padding: var(--spacing-xl);
        }

        .modal-modern .modal-footer {
            border-top: 2px solid rgba(37, 99, 235, 0.1);
            padding: var(--spacing-lg);
        }

        /* ===== SISTEMA DE DROPDOWN UNIFICADO ===== */
        .dropdown-menu-modern {
            border-radius: var(--border-radius-lg);
            border: none;
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
            background: var(--bg-overlay);
            padding: var(--spacing-sm);
        }

        .dropdown-item-modern {
            border-radius: var(--border-radius);
            padding: var(--spacing-sm) var(--spacing-md);
            transition: var(--transition-fast);
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .dropdown-item-modern:hover {
            background: var(--primary-color);
            color: var(--text-light);
            transform: translateX(2px);
        }

        /* ===== SISTEMA DE NAVEGACIÓN UNIFICADO ===== */
        .navbar-modern {
            backdrop-filter: blur(20px);
            background: rgba(31, 41, 55, 0.95);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-link-modern {
            color: rgba(255, 255, 255, 0.8);
            font-weight: var(--font-weight-medium);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--border-radius);
            transition: var(--transition-fast);
        }

        .nav-link-modern:hover {
            color: var(--text-light);
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        /* ===== SISTEMA DE BREADCRUMBS UNIFICADO ===== */
        .breadcrumb-modern {
            background: transparent;
            padding: var(--spacing-md) 0;
        }

        .breadcrumb-modern .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: var(--font-weight-medium);
            transition: var(--transition-fast);
        }

        .breadcrumb-modern .breadcrumb-item a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .breadcrumb-modern .breadcrumb-item.active {
            color: var(--text-secondary);
            font-weight: var(--font-weight-semibold);
        }

        /* ===== SISTEMA DE TOOLTIPS UNIFICADO ===== */
        .tooltip-modern {
            background: var(--dark-color);
            color: var(--text-light);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: var(--font-weight-medium);
            box-shadow: var(--shadow-md);
        }

        /* ===== EFECTOS DE HOVER GLOBALES ===== */
        .hover-lift {
            transition: var(--transition);
        }

        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .hover-scale {
            transition: var(--transition);
        }

        .hover-scale:hover {
            transform: scale(1.02);
        }

        .hover-glow {
            transition: var(--transition);
        }

        .hover-glow:hover {
            box-shadow: 0 0 20px rgba(37, 99, 235, 0.3);
        }

        /* Responsive mejorado */
        @media (max-width: 768px) {
            .hero-section {
                padding: 2rem 0;
            }
            
            .tienda-logo,
            .logo-placeholder {
                width: 150px;
                height: 150px;
                padding: 8px;
            }
            
            .info-card {
                padding: 1.5rem;
            }
            
            .info-card-header {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }
            
            .meta-badges {
                flex-direction: column;
            }

            .info-detail-card {
                padding: 1rem;
            }

            .description-section {
                padding: 1.5rem;
            }

            .gallery-thumbnails {
                gap: 0.5rem;
            }

            .thumbnail-item {
                width: 80px;
                height: 60px;
            }

            .calificacion-card {
                padding: 1.25rem;
            }

            .usuario-avatar {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .info-detail-card {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }

            .rating-input {
                font-size: 2rem;
            }
        }nslateY(0nslateY(t; 0.4) 158, 11,5, rgba(240 4px 8pxrop-shadow(lter: d(10deg rotate1.15)rm: scale(olor);-warning-co(-tive {ion-fa(--transiton: vartivaslas interacstrelt: 1.6;gha {bottom: 1rgin-ems: centeritnk: 0-shrinter ce-content:ms: center;   align-ite   play: flex;   dis  dius: 50%;border-rant-color) var(--accer),y-colorimar, var(--pdient(135deg-gra
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand text-light fw-bold" href="index.php">
                <i class="bi bi-shop me-2"></i>Mercado Huasteco
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link-modern" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-modern" href="directorio.php">Directorio</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (esta_logueado()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link-modern dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['nombre']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-modern">
                                <li><a class="dropdown-item-modern" href="<?php echo obtener_dashboard_url(); ?>">
                                    <i class="bi bi-speedometer2"></i>Mi Panel
                                </a></li>
                                <li><hr class="dropdown-divider" style="margin: var(--spacing-sm) 0;"></li>
                                <li><a class="dropdown-item-modern" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i>Cerrar Sesión
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn-modern btn-outline-modern btn-sm-modern ms-2" href="auth.php" style="border-color: rgba(255,255,255,0.3); color: rgba(255,255,255,0.9);">
                                <i class="bi bi-person-plus me-1"></i>Iniciar Sesión
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="container" style="margin-top: 80px;">
        <nav class="breadcrumb-modern">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="index.php"><i class="bi bi-house me-1"></i>Inicio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="directorio.php">Directorio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="directorio.php?categoria=<?php echo urlencode($tienda['categoria']); ?>">
                        <?php echo htmlspecialchars($tienda['categoria']); ?>
                    </a>
                </li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></li>
            </ol>
        </nav>
    </div>

    <!-- Hero Section -->
    <?php
    // Verificar si el vendedor es Premium y tiene banner
    $stmt_vendedor_banner = $pdo->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
    $stmt_vendedor_banner->execute([$tienda['vendedor_id']]);
    $vendedor_banner_info = $stmt_vendedor_banner->fetch(PDO::FETCH_ASSOC);
    $vendedor_es_premium_banner = $vendedor_banner_info && $vendedor_banner_info['es_premium'] == 1;
    $tiene_banner = $vendedor_es_premium_banner && !empty($tienda['url_banner']) && file_exists($tienda['url_banner']);
    ?>
    <section class="hero-section <?php echo $tiene_banner ? 'hero-with-banner' : ''; ?>" 
             <?php if ($tiene_banner): ?>
             style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.6)), url('<?php echo htmlspecialchars($tienda['url_banner']); ?>');"
             <?php endif; ?>>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <?php if (!empty($tienda['logo']) && file_exists($tienda['logo'])): ?>
                        <img src="<?php echo htmlspecialchars($tienda['logo']); ?>" 
                             class="tienda-logo" 
                             alt="<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>">
                    <?php else: ?>
                        <div class="logo-placeholder mx-auto">
                            <i class="bi bi-shop"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="display-4 mb-3"><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></h1>
                    <p class="lead mb-3"><?php echo htmlspecialchars($tienda['descripcion']); ?></p>
                    
                    <div class="mb-3">
                        <?php echo mostrar_estrellas_avanzado($tienda['promedio_estrellas'], $tienda['total_calificaciones'], 'large', true); ?>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge-modern">
                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($tienda['categoria']); ?>
                        </span>
                        <span class="badge-modern">
                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($tienda['vendedor_nombre']); ?>
                        </span>
                        <span class="badge-modern">
                            <i class="bi bi-mouse"></i> <?php echo number_format($tienda['clics']); ?> visitas
                        </span>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2">
                        <?php 
                        $url_original = $tienda['url_tienda'] ?? '';
                        $url_limpia = trim($url_original);
                        ?>
                        
                        <?php if (!empty($url_limpia)): ?>
                            <?php 
                            $url_tienda = $url_limpia;
                            // Asegurar que la URL tenga protocolo
                            if (!preg_match('/^https?:\/\//', $url_tienda)) {
                                $url_tienda = 'https://' . $url_tienda;
                            }
                            ?>
                            <a href="<?php echo htmlspecialchars($url_tienda); ?>" 
                               target="_blank" 
                               class="btn-visitar-funcional"
                               rel="noopener noreferrer"
                               style="position: relative; z-index: 1000; pointer-events: auto;"
                               onclick="console.log('Clic en botón - URL: <?php echo htmlspecialchars($url_tienda); ?>'); return true;">
                                <i class="bi bi-box-arrow-up-right"></i> Visitar Tienda Oficial
                            </a>
                        <?php else: ?>
                            <button class="btn-visitar-funcional" 
                                    style="opacity: 0.6; cursor: not-allowed; position: relative; z-index: 1000;" 
                                    onclick="alert('Esta tienda no tiene URL configurada.');" 
                                    title="URL no disponible">
                                <i class="bi bi-exclamation-triangle"></i> URL No Disponible
                            </button>
                        <?php endif; ?>
                        
                        <!-- Botón de WhatsApp (Solo Premium) -->
                        <?php
                        // Verificar si el vendedor es Premium y tiene WhatsApp configurado
                        $stmt_vendedor = $pdo->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
                        $stmt_vendedor->execute([$tienda['vendedor_id']]);
                        $vendedor_info = $stmt_vendedor->fetch(PDO::FETCH_ASSOC);
                        $vendedor_es_premium = $vendedor_info && $vendedor_info['es_premium'] == 1;
                        
                        if ($vendedor_es_premium && !empty($tienda['telefono_wa'])):
                            // Limpiar el número de WhatsApp
                            $num_wa = preg_replace('/[^0-9]/', '', $tienda['telefono_wa']);
                        ?>
                            <a href="https://api.whatsapp.com/send?phone=<?php echo $num_wa; ?>&text=Hola,%20vi%20tu%20tienda%20en%20Mercado%20Huasteco" 
                               target="_blank" 
                               class="btn-whatsapp"
                               rel="noopener noreferrer"
                               style="position: relative; z-index: 1000; pointer-events: auto;"
                               title="Chatear por WhatsApp">
                                <i class="fab fa-whatsapp"></i> Chatear por WhatsApp
                            </a>
                        <?php endif; ?>
                        
                        <!-- Íconos de Redes Sociales (Solo Premium) -->
                        <?php if ($vendedor_es_premium): ?>
                            <?php if (!empty($tienda['link_facebook'])): ?>
                                <a href="<?php echo htmlspecialchars($tienda['link_facebook']); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="btn-social-header facebook"
                                   title="Síguenos en Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($tienda['link_instagram'])): ?>
                                <a href="<?php echo htmlspecialchars($tienda['link_instagram']); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="btn-social-header instagram"
                                   title="Síguenos en Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($tienda['link_tiktok'])): ?>
                                <a href="<?php echo htmlspecialchars($tienda['link_tiktok']); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="btn-social-header tiktok"
                                   title="Síguenos en TikTok">
                                    <i class="fab fa-tiktok"></i>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <button id="btn-compartir" class="btn-social-header share" onclick="compartirTienda()" title="Compartir tienda">
                            <i class="bi bi-share-fill"></i>
                        </button>
                        <button class="btn-social-header report" onclick="reportarTienda()" title="Reportar tienda">
                            <i class="bi bi-flag"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container mt-4">
        <div class="row">
            <!-- Información de la tienda -->
            <div class="col-lg-8">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">Información Detallada</h3>
                            <p class="text-muted mb-0">Todo lo que necesitas saber sobre esta tienda</p>
                        </div>
                    </div>
                    
                    <!-- Información básica en cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="info-detail-card hover-lift">
                                <div class="info-detail-icon">
                                    <i class="bi bi-shop"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Nombre de la Tienda</h6>
                                    <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-detail-card hover-lift">
                                <div class="info-detail-icon">
                                    <i class="bi bi-tag"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Categoría</h6>
                                    <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($tienda['categoria']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-detail-card hover-lift">
                                <div class="info-detail-icon">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Propietario</h6>
                                    <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($tienda['vendedor_nombre']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-detail-card hover-lift">
                                <div class="info-detail-icon">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Fecha de Registro</h6>
                                    <p class="mb-0 fw-semibold"><?php echo date('d/m/Y', strtotime($tienda['fecha_registro'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-detail-card hover-lift">
                                <div class="info-detail-icon">
                                    <i class="bi bi-eye"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Total de Visitas</h6>
                                    <p class="mb-0 fw-semibold"><?php echo number_format($tienda['clics']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-detail-card hover-lift">
                                <div class="info-detail-icon">
                                    <i class="bi bi-globe"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Sitio Web</h6>
                                    <a href="<?php echo htmlspecialchars($tienda['url_tienda']); ?>" 
                                       target="_blank" 
                                       class="text-decoration-none fw-semibold text-primary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Visitar tienda
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Video Premium -->
                    <?php
                    // Verificar si el vendedor es Premium y tiene video
                    $stmt_vendedor_video = $pdo->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
                    $stmt_vendedor_video->execute([$tienda['vendedor_id']]);
                    $vendedor_video_info = $stmt_vendedor_video->fetch(PDO::FETCH_ASSOC);
                    $vendedor_es_premium_video = $vendedor_video_info && $vendedor_video_info['es_premium'] == 1;
                    
                    if ($vendedor_es_premium_video && !empty($tienda['link_video'])):
                        $video_info = extraer_video_id($tienda['link_video']);
                        if ($video_info):
                    ?>
                        <div class="video-section mb-4">
                            <div class="card-modern">
                                <div class="card-header-modern">
                                    <div class="card-icon-modern">
                                        <i class="fas fa-video"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0">
                                            <i class="fas fa-crown text-warning me-2"></i>Video de Presentación
                                        </h3>
                                        <p class="text-muted mb-0">Conoce más sobre nuestro negocio</p>
                                    </div>
                                </div>
                                
                                <div class="video-responsive">
                                    <?php if ($video_info['platform'] === 'youtube'): ?>
                                        <iframe 
                                            src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video_info['id']); ?>" 
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                            allowfullscreen>
                                        </iframe>
                                    <?php elseif ($video_info['platform'] === 'vimeo'): ?>
                                        <iframe 
                                            src="https://player.vimeo.com/video/<?php echo htmlspecialchars($video_info['id']); ?>" 
                                            frameborder="0" 
                                            allow="autoplay; fullscreen; picture-in-picture" 
                                            allowfullscreen>
                                        </iframe>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endif; 
                    ?>
                    
                    <!-- Descripción mejorada -->
                    <div class="description-section">
                        <h5 class="mb-3">
                            <i class="bi bi-file-text me-2"></i>Acerca de esta tienda
                        </h5>
                        <div class="description-content">
                            <p class="lead"><?php echo nl2br(htmlspecialchars($tienda['descripcion'])); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Galería de Fotos -->
                <?php if (!empty($fotos_galeria)): ?>
                    <div class="gallery-container">
                        <div class="info-card-header">
                            <div class="info-card-icon">
                                <i class="bi bi-images"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">Galería de Fotos</h3>
                                <p class="text-muted mb-0"><?php echo count($fotos_galeria); ?> imágenes disponibles</p>
                            </div>
                        </div>
                        
                        <div id="galeriaCarousel" class="carousel slide gallery-carousel" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                <?php foreach ($fotos_galeria as $index => $foto): ?>
                                    <button type="button" data-bs-target="#galeriaCarousel" 
                                            data-bs-slide-to="<?php echo $index; ?>" 
                                            <?php echo $index === 0 ? 'class="active" aria-current="true"' : ''; ?>
                                            aria-label="Slide <?php echo $index + 1; ?>"></button>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="carousel-inner">
                                <?php foreach ($fotos_galeria as $index => $foto): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars($foto['url_imagen']); ?>" 
                                             class="d-block w-100" 
                                             alt="<?php echo htmlspecialchars($foto['descripcion'] ?: 'Imagen de ' . $tienda['nombre_tienda']); ?>"
                                             loading="lazy">
                                        
                                        <?php if (!empty($foto['descripcion'])): ?>
                                            <div class="carousel-caption d-none d-md-block">
                                                <h5 class="mb-2"><?php echo htmlspecialchars($foto['descripcion']); ?></h5>
                                                <p class="mb-0">
                                                    <i class="bi bi-calendar3"></i> 
                                                    <?php echo date('d/m/Y', strtotime($foto['fecha_subida'])); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($fotos_galeria) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#galeriaCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#galeriaCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Siguiente</span>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Miniaturas mejoradas -->
                        <?php if (count($fotos_galeria) > 1): ?>
                            <div class="gallery-thumbnails">
                                <?php foreach ($fotos_galeria as $index => $foto): ?>
                                    <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                                         data-bs-target="#galeriaCarousel" 
                                         data-bs-slide-to="<?php echo $index; ?>"
                                         onclick="setActiveThumb(this, <?php echo $index; ?>)">
                                        <img src="<?php echo htmlspecialchars($foto['url_imagen']); ?>" 
                                             alt="Miniatura <?php echo $index + 1; ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="gallery-container">
                        <div class="info-card-header">
                            <div class="info-card-icon">
                                <i class="bi bi-image"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">Galería de Fotos</h3>
                                <p class="text-muted mb-0">No hay imágenes disponibles</p>
                            </div>
                        </div>
                        
                        <div class="gallery-placeholder" style="height: 300px; border-radius: var(--border-radius-lg);">
                            <i class="bi bi-camera"></i>
                            <p class="mb-0">Esta tienda aún no ha subido fotos</p>
                            <small class="text-muted">Las imágenes aparecerán aquí cuando estén disponibles</small>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Google Maps (Solo Premium) -->
                <?php 
                // Verificar si es Premium y tiene mapa configurado
                if ($vendedor_es_premium && !empty($tienda['google_maps_src'])): 
                    // Sanitizar la URL por seguridad
                    $map_url = filter_var($tienda['google_maps_src'], FILTER_SANITIZE_URL);
                    
                    // Verificar que sea una URL válida de Google Maps
                    if ($map_url && (strpos($map_url, 'google.com/maps') !== false)):
                        
                        // Convertir URL de embed a URL normal de Google Maps
                        // Extraer el parámetro pb si existe
                        $maps_link = $map_url;
                        if (strpos($map_url, '/embed') !== false) {
                            // Es una URL de embed, convertir a URL normal
                            parse_str(parse_url($map_url, PHP_URL_QUERY), $params);
                            if (isset($params['pb'])) {
                                // Crear URL normal con el parámetro pb
                                $maps_link = 'https://www.google.com/maps?pb=' . $params['pb'];
                            } else {
                                // Si no hay pb, usar la URL base de Google Maps
                                $maps_link = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($tienda['nombre_tienda']);
                            }
                        }
                ?>
                <div class="mapa-container mb-4">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <div class="card-icon-modern">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">Nuestra Ubicación</h3>
                                <p class="text-muted mb-0">Encuéntranos fácilmente</p>
                            </div>
                        </div>
                        
                        <div class="mapa-responsive" style="position: relative; overflow: hidden; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-sm);">
                            <iframe 
                                src="<?php echo htmlspecialchars($map_url); ?>" 
                                width="100%" 
                                height="450" 
                                style="border:0; border-radius: var(--border-radius-lg); display: block;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Ubicación de <?php echo htmlspecialchars($tienda['nombre_tienda']); ?>">
                            </iframe>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <a href="<?php echo htmlspecialchars($maps_link); ?>" 
                               target="_blank" 
                               class="btn btn-outline-modern btn-sm-modern"
                               rel="noopener noreferrer">
                                <i class="bi bi-map me-2"></i>Ver en Google Maps
                            </a>
                        </div>
                    </div>
                </div>
                <?php 
                    endif;
                endif; 
                ?>

                <!-- Ofertas Especiales (Solo Premium) -->
                <?php 
                // Obtener ofertas activas de esta tienda
                if ($vendedor_es_premium):
                    try {
                        $stmt_ofertas = $pdo->prepare("
                            SELECT * FROM cupones_ofertas 
                            WHERE id_tienda = ? 
                            AND estado = 'activo' 
                            AND fecha_expiracion >= CURDATE()
                            ORDER BY fecha_creacion DESC
                        ");
                        $stmt_ofertas->execute([$tienda['id']]);
                        $ofertas_tienda = $stmt_ofertas->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!empty($ofertas_tienda)):
                ?>
                <div class="ofertas-container mb-4">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <div class="card-icon-modern" style="background: linear-gradient(135deg, #FFD700, #FFA500);">
                                <i class="bi bi-ticket-perforated-fill"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">Ofertas Especiales</h3>
                                <p class="text-muted mb-0">Aprovecha nuestras promociones</p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <?php foreach ($ofertas_tienda as $oferta): 
                                $dias_restantes = (strtotime($oferta['fecha_expiracion']) - time()) / (60 * 60 * 24);
                                $dias_restantes = ceil($dias_restantes);
                            ?>
                                <div class="col-md-6 mb-3">
                                    <div style="background: linear-gradient(135deg, #fff9e6, #ffffff); border: 2px dashed #FFD700; border-radius: 12px; padding: 1.5rem; position: relative; overflow: hidden;">
                                        <!-- Decoración de cupón -->
                                        <div style="position: absolute; top: -10px; right: -10px; width: 80px; height: 80px; background: linear-gradient(135deg, #FFD700, #FFA500); border-radius: 50%; opacity: 0.1;"></div>
                                        
                                        <div class="d-flex align-items-start mb-3">
                                            <div style="background: linear-gradient(135deg, #FFD700, #FFA500); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem; flex-shrink: 0;">
                                                <i class="bi bi-tag-fill text-white fs-4"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($oferta['titulo']); ?></h5>
                                                <?php if ($oferta['descripcion']): ?>
                                                    <p class="text-muted mb-2 small"><?php echo nl2br(htmlspecialchars($oferta['descripcion'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-event me-1"></i>
                                                Válido hasta: <?php echo date('d/m/Y', strtotime($oferta['fecha_expiracion'])); ?>
                                            </small>
                                            <?php if ($dias_restantes <= 3): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-clock-fill me-1"></i>
                                                    ¡Últimos días!
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php 
                        endif;
                    } catch(PDOException $e) {
                        // Error silencioso
                    }
                endif; 
                ?>

                <!-- Formulario de calificación (solo para usuarios logueados) -->
                <?php if (esta_logueado()): ?>
                    <?php if ($_SESSION['user_id'] == $tienda['vendedor_id']): ?>
                        <div class="form-reseña-container text-center">
                            <div class="mb-4">
                                <i class="bi bi-info-circle-fill" style="font-size: 4rem; color: var(--info-color); opacity: 0.7;"></i>
                            </div>
                            <h4 class="mb-3">Esta es tu tienda</h4>
                            <p class="text-muted mb-4 lead">
                                No puedes calificar tu propia tienda. Los clientes podrán dejar sus reseñas aquí.
                            </p>
                            <a href="panel_vendedor.php" class="btn btn-primary-modern">
                                <i class="bi bi-gear me-2"></i>Gestionar mi Tienda
                            </a>
                        </div>
                    <?php else: ?>
                    <div class="form-reseña-container">
                        <div class="info-card-header">
                            <div class="info-card-icon">
                                <i class="bi bi-star-fill"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">
                                    <?php echo $ya_califico ? 'Editar mi Reseña' : 'Escribir una Reseña'; ?>
                                </h4>
                                <p class="text-muted mb-0">
                                    <?php echo $ya_califico ? 'Modifica tu opinión sobre esta tienda' : 'Comparte tu experiencia con otros usuarios'; ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($mensaje): ?>
                            <div class="alert alert-success-modern">
                                <i class="bi bi-check-circle me-2"></i><?php echo $mensaje; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger-modern">
                                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                            <div class="alert alert-success-modern">
                                <i class="bi bi-check-circle me-2"></i>¡Tu reseña ha sido publicada exitosamente! Gracias por compartir tu experiencia.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                            <div class="alert alert-success-modern">
                                <i class="bi bi-check-circle me-2"></i>Tu reseña ha sido eliminada exitosamente.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['error']) && $_GET['error'] == 'delete'): ?>
                            <div class="alert alert-danger-modern">
                                <i class="bi bi-exclamation-triangle me-2"></i>Error al eliminar la reseña. Por favor intenta nuevamente.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Mensajes del Sistema de Reportes -->
                        <?php if (isset($_GET['reporte_enviado']) && $_GET['reporte_enviado'] == '1'): ?>
                            <div class="alert alert-success-modern">
                                <i class="bi bi-check-circle me-2"></i>¡Gracias por tu reporte! Lo revisaremos pronto y tomaremos las medidas necesarias.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['error'])): ?>
                            <?php if ($_GET['error'] == 'motivo_vacio'): ?>
                                <div class="alert alert-danger-modern">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Debes proporcionar un motivo para el reporte.
                                </div>
                            <?php elseif ($_GET['error'] == 'motivo_corto'): ?>
                                <div class="alert alert-danger-modern">
                                    <i class="bi bi-exclamation-triangle me-2"></i>El motivo del reporte debe tener al menos 10 caracteres.
                                </div>
                            <?php elseif ($_GET['error'] == 'motivo_largo'): ?>
                                <div class="alert alert-danger-modern">
                                    <i class="bi bi-exclamation-triangle me-2"></i>El motivo del reporte no puede exceder 1000 caracteres.
                                </div>
                            <?php elseif ($_GET['error'] == 'ya_reportado'): ?>
                                <div class="alert alert-warning-modern">
                                    <i class="bi bi-info-circle me-2"></i>Ya has reportado esta tienda recientemente. Espera 24 horas para enviar otro reporte.
                                </div>
                            <?php elseif ($_GET['error'] == 'db_insert'): ?>
                                <div class="alert alert-danger-modern">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Error al guardar el reporte. Por favor intenta nuevamente.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="formReseña">
                            <div class="mb-4">
                                <label for="calificacion_select" class="form-label-modern">
                                    <i class="bi bi-star me-1"></i>Tu Calificación *
                                </label>
                                <select name="estrellas" id="calificacion_select" class="form-control-modern" required>
                                    <option value="" disabled <?php echo !$calificacion_usuario ? 'selected' : ''; ?>>-- Elige una calificación --</option>
                                    <option value="5" <?php echo ($calificacion_usuario && $calificacion_usuario['estrellas'] == 5) ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ (5 Estrellas - Excelente)</option>
                                    <option value="4" <?php echo ($calificacion_usuario && $calificacion_usuario['estrellas'] == 4) ? 'selected' : ''; ?>>⭐⭐⭐⭐ (4 Estrellas - Bueno)</option>
                                    <option value="3" <?php echo ($calificacion_usuario && $calificacion_usuario['estrellas'] == 3) ? 'selected' : ''; ?>>⭐⭐⭐ (3 Estrellas - Regular)</option>
                                    <option value="2" <?php echo ($calificacion_usuario && $calificacion_usuario['estrellas'] == 2) ? 'selected' : ''; ?>>⭐⭐ (2 Estrellas - Malo)</option>
                                    <option value="1" <?php echo ($calificacion_usuario && $calificacion_usuario['estrellas'] == 1) ? 'selected' : ''; ?>>⭐ (1 Estrella - Pésimo)</option>
                                </select>
                                <small class="text-muted">Selecciona tu calificación del 1 al 5</small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="comentario" class="form-label-modern">
                                    <i class="bi bi-chat-text me-1"></i>Tu Comentario *
                                </label>
                                <textarea class="form-control-modern" 
                                          id="comentario" 
                                          name="comentario" 
                                          rows="5" 
                                          required 
                                          maxlength="1000"
                                          placeholder="Cuéntanos sobre tu experiencia: ¿Qué te gustó? ¿Qué mejorarías? ¿Recomendarías esta tienda?"
                                          style="resize: vertical;"><?php echo $calificacion_usuario ? htmlspecialchars($calificacion_usuario['comentario']) : ''; ?></textarea>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">Mínimo 10 caracteres. Sé específico y constructivo.</small>
                                    <small id="comment-counter" class="text-muted">
                                        <?php 
                                        $current_length = $calificacion_usuario ? strlen($calificacion_usuario['comentario']) : 0;
                                        echo $current_length . '/1000 caracteres';
                                        ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary-modern">
                                    <i class="bi bi-send me-2"></i>
                                    <?php echo $ya_califico ? 'Actualizar Reseña' : 'Publicar Reseña'; ?>
                                </button>
                                <?php if ($ya_califico): ?>
                                    <button type="button" class="btn-modern btn-outline-modern" style="border-color: var(--danger-color); color: var(--danger-color);" onclick="confirmarEliminarReseña()">
                                        <i class="bi bi-trash me-2"></i>Eliminar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="form-reseña-container text-center">
                        <div class="mb-4">
                            <i class="bi bi-person-plus-fill" style="font-size: 4rem; color: var(--primary-color); opacity: 0.7;"></i>
                        </div>
                        <h4 class="mb-3">¿Conoces esta tienda?</h4>
                        <p class="text-muted mb-4 lead">
                            Inicia sesión para compartir tu experiencia y ayudar a otros usuarios a tomar mejores decisiones.
                        </p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="auth.php" class="btn btn-primary-modern">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                            </a>
                            <a href="auth.php?register=1" class="btn-modern btn-outline-modern">
                                <i class="bi bi-person-plus me-2"></i>Registrarse
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar con reseñas -->
            <div class="col-lg-4">
                <!-- Estadísticas de la tienda -->
                <div class="info-card mb-4">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">Estadísticas</h4>
                            <p class="text-muted mb-0">Datos de la tienda</p>
                        </div>
                    </div>
                    
                    <div class="tienda-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($tienda['promedio_estrellas'], 1); ?></div>
                            <div class="stat-label">Rating</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $tienda['total_calificaciones']; ?></div>
                            <div class="stat-label">Reseñas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($tienda['clics']); ?></div>
                            <div class="stat-label">Visitas</div>
                        </div>
                    </div>
                </div>

                <!-- Reseñas -->
                <div class="reseñas-section">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">Reseñas de Clientes</h4>
                            <p class="text-muted mb-0"><?php echo count($calificaciones); ?> opiniones verificadas</p>
                        </div>
                    </div>
                    
                    <?php if (empty($calificaciones)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-chat-square-text" style="font-size: 4rem; color: #e5e7eb;"></i>
                            </div>
                            <h5 class="text-muted mb-2">Sin reseñas aún</h5>
                            <p class="text-muted mb-3">Esta tienda aún no tiene reseñas de clientes.</p>
                            <?php if (esta_logueado()): ?>
                                <p class="small text-primary">¡Sé el primero en compartir tu experiencia!</p>
                            <?php else: ?>
                                <a href="auth.php" class="btn-modern btn-outline-modern btn-sm-modern">
                                    <i class="bi bi-person-plus me-1"></i>Inicia sesión para reseñar
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="reseñas-list" style="max-height: 500px; overflow-y: auto; padding-right: 0.5rem;">
                            <?php foreach ($calificaciones as $calificacion): ?>
                                <div class="reseña-item">
                                    <div class="reseña-header">
                                        <div class="reseña-usuario-info">
                                            <div class="reseña-avatar">
                                                <?php echo strtoupper(substr($calificacion['usuario_nombre'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($calificacion['usuario_nombre']); ?></h6>
                                                <div class="reseña-estrellas">
                                                    <?php echo mostrar_estrellas_calificacion($calificacion['estrellas']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="reseña-meta">
                                            <div class="reseña-fecha">
                                                <?php echo date('d/m/Y', strtotime($calificacion['fecha_calificacion'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="reseña-comentario">
                                        <?php echo nl2br(htmlspecialchars($calificacion['comentario'])); ?>
                                    </div>
                                    
                                    <?php if (!empty($calificacion['respuesta_vendedor'])): ?>
                                        <!-- Respuesta del vendedor (Solo Premium) -->
                                        <div class="respuesta-vendedor-container" style="margin-top: 1rem; margin-left: 2rem; padding: 1rem; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-left: 4px solid var(--success-color); border-radius: 8px;">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-shop-window text-success me-2" style="font-size: 1.2rem;"></i>
                                                <strong style="color: var(--success-color);">Respuesta de <?php echo htmlspecialchars($tienda['nombre_tienda']); ?>:</strong>
                                                <small class="text-muted ms-auto">
                                                    <?php echo date('d/m/Y', strtotime($calificacion['fecha_respuesta'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-0" style="color: var(--text-secondary); font-style: italic;">
                                                <?php echo nl2br(htmlspecialchars($calificacion['respuesta_vendedor'])); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($calificaciones) >= 5): ?>
                            <div class="text-center mt-3">
                                <button class="btn-modern btn-outline-modern btn-sm-modern" onclick="toggleAllReviews()">
                                    <i class="bi bi-chevron-down me-1"></i>Ver todas las reseñas
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ===== SISTEMA DE ESTRELLAS MEJORADO =====
            initializeStarAnimations();
            
            function initializeStarAnimations() {
                // Agregar efectos hover a todas las estrellas
                const allStars = document.querySelectorAll('.bi-star-fill, .bi-star-half, .bi-star');
                
                allStars.forEach(star => {
                    // Efecto de entrada con delay aleatorio
                    const delay = Math.random() * 0.5;
                    star.style.animationDelay = delay + 's';
                    
                    // Hover effects
                    star.addEventListener('mouseenter', function() {
                        if (this.classList.contains('bi-star-fill') || this.classList.contains('bi-star-half')) {
                            this.style.transform = 'scale(1.1) rotate(5deg)';
                            this.style.filter = 'drop-shadow(0 3px 12px rgba(251, 191, 36, 0.6))';
                        }
                    });
                    
                    star.addEventListener('mouseleave', function() {
                        this.style.transform = 'scale(1) rotate(0deg)';
                        this.style.filter = this.classList.contains('bi-star-fill') || this.classList.contains('bi-star-half') 
                            ? 'drop-shadow(0 1px 3px rgba(251, 191, 36, 0.3))' 
                            : 'none';
                    });
                });
                
                // Animación de aparición secuencial para grupos de estrellas
                const starContainers = document.querySelectorAll('.reseña-estrellas, .calificacion-estrellas');
                starContainers.forEach((container, containerIndex) => {
                    const stars = container.querySelectorAll('.bi-star-fill, .bi-star-half, .bi-star');
                    stars.forEach((star, starIndex) => {
                        star.style.opacity = '0';
                        star.style.transform = 'scale(0.5)';
                        
                        setTimeout(() => {
                            star.style.transition = 'all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                            star.style.opacity = '1';
                            star.style.transform = 'scale(1)';
                        }, (containerIndex * 200) + (starIndex * 100));
                    });
                });
            }
            
            // Efecto de pulso para estrellas principales
            function addStarPulse() {
                const mainStars = document.querySelector('.mb-3 .bi-star-fill, .mb-3 .bi-star-half');
                if (mainStars) {
                    setInterval(() => {
                        const stars = document.querySelectorAll('.mb-3 .bi-star-fill');
                        stars.forEach((star, index) => {
                            setTimeout(() => {
                                star.style.transform = 'scale(1.1)';
                                setTimeout(() => {
                                    star.style.transform = 'scale(1)';
                                }, 200);
                            }, index * 100);
                        });
                    }, 5000); // Cada 5 segundos
                }
            }
            
            // Inicializar efectos después de un pequeño delay
            setTimeout(addStarPulse, 1000);
            // Función para las miniaturas de la galería
            window.setActiveThumb = function(element, index) {
                document.querySelectorAll('.thumbnail-item').forEach(thumb => {
                    thumb.classList.remove('active');
                });
                element.classList.add('active');
            };
            
            // Función para alternar todas las reseñas
            window.toggleAllReviews = function() {
                const reviewsList = document.querySelector('.reseñas-list');
                const button = event.target;
                
                if (reviewsList.style.maxHeight === 'none') {
                    reviewsList.style.maxHeight = '500px';
                    button.innerHTML = '<i class="bi bi-chevron-down me-1"></i>Ver todas las reseñas';
                } else {
                    reviewsList.style.maxHeight = 'none';
                    button.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Ver menos reseñas';
                }
            };
            
            // Función para confirmar eliminación de reseña
            window.confirmarEliminarReseña = function() {
                if (confirm('¿Estás seguro de que quieres eliminar tu reseña? Esta acción no se puede deshacer.')) {
                    window.location.href = 'eliminar_reseña.php?tienda_id=<?php echo $tienda_id; ?>';
                }
            };
            
            // Validación del formulario
            const form = document.getElementById('formReseña');
            const comentarioField = document.getElementById('comentario');
            const calificacionSelect = document.getElementById('calificacion_select');
            
            if (form) {
                // Validación en tiempo real del comentario
                if (comentarioField) {
                    comentarioField.addEventListener('input', function() {
                        const length = this.value.trim().length;
                        let counter = document.getElementById('comment-counter');
                        
                        if (!counter) {
                            counter = document.createElement('small');
                            counter.id = 'comment-counter';
                            counter.className = 'text-muted';
                            this.parentNode.appendChild(counter);
                        }
                        
                        counter.textContent = `${length}/1000 caracteres`;
                        
                        if (length < 10) {
                            counter.style.color = '#dc3545';
                            counter.textContent += ' (mínimo 10)';
                        } else if (length > 1000) {
                            counter.style.color = '#dc3545';
                            counter.textContent += ' (máximo excedido)';
                        } else {
                            counter.style.color = '#28a745';
                        }
                    });
                }
                
                form.addEventListener('submit', function(e) {
                    const rating = calificacionSelect ? calificacionSelect.value : '';
                    const comment = comentarioField ? comentarioField.value.trim() : '';
                    
                    console.log('Formulario enviado - Rating:', rating, 'Comentario:', comment);
                    
                    // Validaciones del lado del cliente
                    if (!rating || rating < 1 || rating > 5) {
                        e.preventDefault();
                        alert('Por favor selecciona una calificación del 1 al 5 estrellas.');
                        if (calificacionSelect) calificacionSelect.focus();
                        return false;
                    }
                    
                    if (comment.length < 10) {
                        e.preventDefault();
                        alert('El comentario debe tener al menos 10 caracteres.');
                        if (comentarioField) comentarioField.focus();
                        return false;
                    }
                    
                    if (comment.length > 1000) {
                        e.preventDefault();
                        alert('El comentario no puede exceder 1000 caracteres.');
                        if (comentarioField) comentarioField.focus();
                        return false;
                    }
                    
                    console.log('Validaciones pasadas, enviando formulario...');
                    
                    // Mostrar indicador de carga
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Enviando...';
                        submitBtn.disabled = true;
                        
                        // Restaurar botón si hay error después de 10 segundos
                        setTimeout(() => {
                            if (submitBtn.disabled) {
                                submitBtn.innerHTML = originalText;
                                submitBtn.disabled = false;
                            }
                        }, 10000);
                    }
                    
                    // Permitir que el formulario se envíe
                    return true;
                });
            }
            
            // Funcionalidad para miniaturas del carrusel
            document.querySelectorAll('.miniatura-galeria').forEach(function(miniatura) {
                miniatura.addEventListener('click', function() {
                    document.querySelectorAll('.miniatura-galeria').forEach(function(m) {
                        m.classList.remove('border-primary');
                        m.style.borderWidth = '1px';
                    });
                    
                    this.classList.add('border-primary');
                    this.style.borderWidth = '3px';
                });
            });
            
            // Marcar primera miniatura como activa
            const primeraMiniatura = document.querySelector('.miniatura-galeria');
            if (primeraMiniatura) {
                primeraMiniatura.classList.add('border-primary');
                primeraMiniatura.style.borderWidth = '3px';
            }
            
            // Sincronizar miniaturas con carrusel
            const carousel = document.getElementById('galeriaCarousel');
            if (carousel) {
                carousel.addEventListener('slide.bs.carousel', function(event) {
                    document.querySelectorAll('.miniatura-galeria').forEach(function(m) {
                        m.classList.remove('border-primary');
                        m.style.borderWidth = '1px';
                    });
                    
                    const miniaturaActiva = document.querySelector('.miniatura-galeria[data-bs-slide-to="' + event.to + '"]');
                    if (miniaturaActiva) {
                        miniaturaActiva.classList.add('border-primary');
                        miniaturaActiva.style.borderWidth = '3px';
                    }
                });
            }
        });
    </script>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="bi bi-shop me-2"></i>Mercado Huasteco</h5>
                    <p class="text-muted">
                        Conectando estudiantes con las mejores tiendas locales. 
                        Descubre, comparte y califica experiencias de compra.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-light"><i class="bi bi-facebook fs-5"></i></a>
                        <a href="#" class="text-light"><i class="bi bi-twitter fs-5"></i></a>
                        <a href="#" class="text-light"><i class="bi bi-instagram fs-5"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6>Enlaces</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted text-decoration-none">Inicio</a></li>
                        <li><a href="directorio.php" class="text-muted text-decoration-none">Directorio</a></li>
                        <li><a href="categorias.php" class="text-muted text-decoration-none">Categorías</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h6>Para Vendedores</h6>
                    <ul class="list-unstyled">
                        <li><a href="registro_vendedor.php" class="text-muted text-decoration-none">Registrar Tienda</a></li>
                        <li><a href="dashboard.php" class="text-muted text-decoration-none">Panel de Control</a></li>
                        <li><a href="ayuda.php" class="text-muted text-decoration-none">Ayuda</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h6>Información de la Tienda</h6>
                    <div class="small text-muted">
                        <p class="mb-2">
                            <i class="bi bi-shop me-2"></i>
                            <strong><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></strong>
                        </p>
                        <p class="mb-2">
                            <i class="bi bi-star-fill text-warning me-2"></i>
                            <?php echo number_format($tienda['promedio_estrellas'], 1); ?> estrellas
                        </p>
                        <p class="mb-2">
                            <i class="bi bi-eye me-2"></i>
                            <?php echo number_format($tienda['clics']); ?> visitas
                        </p>
                        <p class="mb-0">
                            <i class="bi bi-calendar3 me-2"></i>
                            Desde <?php echo date('Y', strtotime($tienda['fecha_registro'])); ?>
                        </p>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted small">
                        &copy; <?php echo date('Y'); ?> Mercado Huasteco. Todos los derechos reservados.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="btn btn-outline-light btn-sm me-2">
                        <i class="bi bi-arrow-up"></i> Volver arriba
                    </a>
                    <a href="directorio.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver al Directorio
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal de Reporte de Tienda -->
    <div class="modal fade" id="modal-reporte" tabindex="-1" aria-labelledby="modalReporteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title" id="modalReporteLabel">
                        <i class="bi bi-flag-fill me-2"></i>Reportar Tienda
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="procesar_reporte.php" id="form-reporte">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="id_tienda_reportada" value="<?php echo $tienda['id']; ?>">
                        
                        <div class="alert alert-warning" style="border-radius: 12px;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Importante:</strong> Los reportes falsos pueden resultar en la suspensión de tu cuenta.
                        </div>
                        
                        <div class="mb-3">
                            <label for="motivo_reporte" class="form-label fw-bold">
                                <i class="bi bi-chat-left-text me-2"></i>Motivo del reporte *
                            </label>
                            <textarea 
                                class="form-control" 
                                id="motivo_reporte" 
                                name="motivo_reporte" 
                                rows="5" 
                                required
                                minlength="10"
                                maxlength="1000"
                                placeholder="Describe el motivo de tu reporte (mínimo 10 caracteres)..."
                                style="border-radius: 12px; border: 2px solid #e5e7eb; resize: none;"
                            ></textarea>
                            <small class="text-muted">
                                <span id="contador-caracteres">0</span>/1000 caracteres
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <p class="mb-2 fw-bold"><i class="bi bi-info-circle me-2"></i>Motivos válidos para reportar:</p>
                            <ul class="small text-muted mb-0">
                                <li>Contenido inapropiado u ofensivo</li>
                                <li>Información falsa o engañosa</li>
                                <li>Productos o servicios ilegales</li>
                                <li>Spam o publicidad no autorizada</li>
                                <li>Violación de derechos de autor</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 2px solid #f0f0f0; padding: 1.5rem;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px; padding: 0.6rem 1.5rem;">
                            <i class="bi bi-x-circle me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger" style="border-radius: 10px; padding: 0.6rem 1.5rem; background: linear-gradient(135deg, #dc3545, #c82333); border: none;">
                            <i class="bi bi-flag-fill me-2"></i>Enviar Reporte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Botón flotante para volver arriba -->
    <button id="backToTop" class="btn btn-primary position-fixed" 
            style="bottom: 20px; right: 20px; z-index: 1000; border-radius: 50%; width: 50px; height: 50px; display: none;">
        <i class="bi bi-arrow-up"></i>
    </button>

    <script>
        // Botón volver arriba
        window.addEventListener('scroll', function() {
            const backToTop = document.getElementById('backToTop');
            if (window.pageYOffset > 300) {
                backToTop.style.display = 'block';
            } else {
                backToTop.style.display = 'none';
            }
        });

        document.getElementById('backToTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Compartir tienda con Web Share API y fallback
        async function compartirTienda() {
            const url = window.location.href;
            const titulo = <?php echo json_encode(htmlspecialchars($tienda['nombre_tienda']) . ' - Mercado Huasteco'); ?>;
            const texto = '¡Mira esta tienda en Mercado Huasteco! ' + <?php echo json_encode(htmlspecialchars(substr($tienda['descripcion'], 0, 100))); ?>;
            const btnCompartir = document.getElementById('btn-compartir');
            
            try {
                // 1. INTENTO NATIVO (Web Share API - para móviles)
                if (navigator.share) {
                    await navigator.share({
                        title: titulo,
                        text: texto,
                        url: url
                    });
                    
                    // Animación de éxito
                    btnCompartir.classList.add('success');
                    setTimeout(() => {
                        btnCompartir.classList.remove('success');
                    }, 500);
                    
                    console.log('✅ Compartido exitosamente');
                } else {
                    // 2. FALLBACK (Copiar al portapapeles - para PC)
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(url);
                        
                        // Animación de éxito
                        btnCompartir.classList.add('success');
                        
                        // Cambiar ícono temporalmente
                        const icon = btnCompartir.querySelector('i');
                        const originalClass = icon.className;
                        icon.className = 'bi bi-check-circle-fill';
                        
                        // Mostrar notificación moderna
                        mostrarNotificacion('¡Enlace copiado al portapapeles!', 'success');
                        
                        // Restaurar después de 2 segundos
                        setTimeout(() => {
                            btnCompartir.classList.remove('success');
                            icon.className = originalClass;
                        }, 2000);
                    } else {
                        // 3. FALLBACK ANTIGUO (para navegadores muy viejos)
                        const input = document.createElement('input');
                        input.value = url;
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        document.body.removeChild(input);
                        
                        mostrarNotificacion('¡Enlace copiado!', 'success');
                    }
                }
            } catch (err) {
                // Solo mostrar error si no fue cancelado por el usuario
                if (err.name !== 'AbortError') {
                    console.error('❌ Error al compartir:', err);
                    mostrarNotificacion('No se pudo compartir. Intenta de nuevo.', 'error');
                }
            }
        }
        
        // Función para mostrar notificaciones modernas
        function mostrarNotificacion(mensaje, tipo = 'success') {
            // Crear elemento de notificación
            const notif = document.createElement('div');
            notif.className = 'notificacion-moderna ' + tipo;
            notif.innerHTML = `
                <i class="bi bi-${tipo === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>
                <span>${mensaje}</span>
            `;
            
            // Agregar al body
            document.body.appendChild(notif);
            
            // Mostrar con animación
            setTimeout(() => notif.classList.add('show'), 10);
            
            // Ocultar y eliminar después de 3 segundos
            setTimeout(() => {
                notif.classList.remove('show');
                setTimeout(() => notif.remove(), 300);
            }, 3000);
        }

        // Reportar tienda - Abrir modal
        function reportarTienda() {
            const modal = new bootstrap.Modal(document.getElementById('modal-reporte'));
            modal.show();
        }
        
        // Contador de caracteres para el textarea del reporte
        const textareaReporte = document.getElementById('motivo_reporte');
        const contadorCaracteres = document.getElementById('contador-caracteres');
        
        if (textareaReporte && contadorCaracteres) {
            textareaReporte.addEventListener('input', function() {
                contadorCaracteres.textContent = this.value.length;
                
                // Cambiar color según la longitud
                if (this.value.length < 10) {
                    contadorCaracteres.style.color = '#dc3545';
                } else if (this.value.length > 900) {
                    contadorCaracteres.style.color = '#ffc107';
                } else {
                    contadorCaracteres.style.color = '#28a745';
                }
            });
        }
        
        // Validación del formulario de reporte
        const formReporte = document.getElementById('form-reporte');
        if (formReporte) {
            formReporte.addEventListener('submit', function(e) {
                const motivo = textareaReporte.value.trim();
                
                if (motivo.length < 10) {
                    e.preventDefault();
                    alert('El motivo del reporte debe tener al menos 10 caracteres.');
                    textareaReporte.focus();
                    return false;
                }
                
                if (motivo.length > 1000) {
                    e.preventDefault();
                    alert('El motivo del reporte no puede exceder 1000 caracteres.');
                    textareaReporte.focus();
                    return false;
                }
                
                // Confirmar antes de enviar
                if (!confirm('¿Estás seguro de que deseas reportar esta tienda?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    </script>
</body>
</html>