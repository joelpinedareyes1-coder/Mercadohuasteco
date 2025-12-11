<?php
require_once 'config.php';

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    header("Location: auth.php");
    exit();
}

// Verificar que sea Premium
$stmt = $pdo->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || $usuario['es_premium'] != 1) {
    header("Location: panel_vendedor.php?error=no_premium");
    exit();
}

// Obtener la tienda del vendedor
$stmt = $pdo->prepare("SELECT id FROM tiendas WHERE vendedor_id = ? AND activo = 1");
$stmt->execute([$_SESSION['user_id']]);
$tienda = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tienda) {
    header("Location: panel_vendedor.php?error=no_tienda");
    exit();
}

$tienda_id = $tienda['id'];
$mensaje = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Crear nueva oferta
    if (isset($_POST['crear_oferta'])) {
        $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $fecha_expiracion = isset($_POST['fecha_expiracion']) ? $_POST['fecha_expiracion'] : '';
        $porcentaje_descuento = isset($_POST['porcentaje_descuento']) ? (int)$_POST['porcentaje_descuento'] : null;
        $link_producto = isset($_POST['link_producto']) ? trim($_POST['link_producto']) : null;
        $imagen_oferta = isset($_POST['imagen_oferta']) ? trim($_POST['imagen_oferta']) : null;
        $categoria_oferta = isset($_POST['categoria_oferta']) ? $_POST['categoria_oferta'] : 'descuento';
        $codigo_cupon = isset($_POST['codigo_cupon']) ? strtoupper(trim($_POST['codigo_cupon'])) : null;
        $stock_limitado = isset($_POST['stock_limitado']) && !empty($_POST['stock_limitado']) ? (int)$_POST['stock_limitado'] : null;
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        $color_badge = isset($_POST['color_badge']) ? $_POST['color_badge'] : '#FFD700';
        $terminos_condiciones = isset($_POST['terminos_condiciones']) ? trim($_POST['terminos_condiciones']) : null;
        
        if (empty($titulo)) {
            $error = "El título es obligatorio";
        } elseif (strlen($titulo) > 100) {
            $error = "El título no puede exceder 100 caracteres";
        } elseif (empty($fecha_expiracion)) {
            $error = "La fecha de expiración es obligatoria";
        } elseif (strtotime($fecha_expiracion) < strtotime('today')) {
            $error = "La fecha de expiración debe ser futura";
        } elseif ($porcentaje_descuento !== null && ($porcentaje_descuento < 1 || $porcentaje_descuento > 100)) {
            $error = "El porcentaje debe estar entre 1 y 100";
        } elseif ($stock_limitado !== null && $stock_limitado < 1) {
            $error = "El stock debe ser mayor a 0";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO cupones_ofertas (id_tienda, titulo, descripcion, fecha_expiracion, porcentaje_descuento, link_producto, imagen_oferta, categoria_oferta, codigo_cupon, stock_limitado, destacado, color_badge, terminos_condiciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$tienda_id, $titulo, $descripcion, $fecha_expiracion, $porcentaje_descuento, $link_producto, $imagen_oferta, $categoria_oferta, $codigo_cupon, $stock_limitado, $destacado, $color_badge, $terminos_condiciones]);
                $mensaje = "¡Oferta creada exitosamente!";
            } catch(PDOException $e) {
                $error = "Error al crear la oferta: " . $e->getMessage();
            }
        }
    }
    
    // Pausar oferta
    if (isset($_POST['pausar_oferta'])) {
        $oferta_id = (int)$_POST['oferta_id'];
        try {
            $stmt = $pdo->prepare("UPDATE cupones_ofertas SET estado = 'pausado' WHERE id = ? AND id_tienda = ?");
            $stmt->execute([$oferta_id, $tienda_id]);
            $mensaje = "Oferta pausada";
        } catch(PDOException $e) {
            $error = "Error al pausar la oferta";
        }
    }
    
    // Activar oferta
    if (isset($_POST['activar_oferta'])) {
        $oferta_id = (int)$_POST['oferta_id'];
        try {
            $stmt = $pdo->prepare("UPDATE cupones_ofertas SET estado = 'activo' WHERE id = ? AND id_tienda = ?");
            $stmt->execute([$oferta_id, $tienda_id]);
            $mensaje = "Oferta activada";
        } catch(PDOException $e) {
            $error = "Error al activar la oferta";
        }
    }
    
    // Eliminar oferta
    if (isset($_POST['eliminar_oferta'])) {
        $oferta_id = (int)$_POST['oferta_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM cupones_ofertas WHERE id = ? AND id_tienda = ?");
            $stmt->execute([$oferta_id, $tienda_id]);
            $mensaje = "Oferta eliminada";
        } catch(PDOException $e) {
            $error = "Error al eliminar la oferta";
        }
    }
}

// Obtener ofertas del vendedor
try {
    $stmt = $pdo->prepare("
        SELECT *, 
               CASE 
                   WHEN fecha_expiracion < CURDATE() THEN 'expirado'
                   ELSE estado 
               END as estado_real
        FROM cupones_ofertas 
        WHERE id_tienda = ? 
        ORDER BY fecha_creacion DESC
    ");
    $stmt->execute([$tienda_id]);
    $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $ofertas = [];
    $error = "Error al cargar ofertas";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Ofertas - Mercado Huasteco</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f6f5f7 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        .header-premium {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .card-oferta {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #FFD700;
            transition: transform 0.3s;
        }
        
        .card-oferta:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .card-oferta.expirado {
            opacity: 0.6;
            border-left-color: #dc3545;
        }
        
        .card-oferta.pausado {
            opacity: 0.7;
            border-left-color: #ffc107;
        }
        
        .badge-estado {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .btn-crear {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-crear:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 165, 0, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <div class="header-premium">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-ticket-perforated-fill me-2"></i>Mis Ofertas</h1>
                    <p class="mb-0">Gestiona tus cupones y promociones</p>
                </div>
                <a href="panel_vendedor.php" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>Volver al Panel
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i><?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario para crear oferta -->
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="mb-4"><i class="bi bi-plus-circle me-2"></i>Crear Nueva Oferta</h3>
                <form method="POST">
                    <input type="hidden" name="crear_oferta" value="1">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Título de la Oferta *</label>
                            <input type="text" name="titulo" class="form-control" required maxlength="100"
                                   placeholder="Ej: 2x1 en todos los productos">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Fecha de Expiración *</label>
                            <input type="date" name="fecha_expiracion" class="form-control" required
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Categoría de Oferta *</label>
                            <select name="categoria_oferta" class="form-select">
                                <option value="descuento">💰 Descuento</option>
                                <option value="2x1">🎁 2x1</option>
                                <option value="3x2">🎉 3x2</option>
                                <option value="envio_gratis">🚚 Envío Gratis</option>
                                <option value="regalo">🎁 Regalo</option>
                                <option value="temporada">🌟 Temporada</option>
                                <option value="otro">📌 Otro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Porcentaje de Descuento</label>
                            <div class="input-group">
                                <input type="number" name="porcentaje_descuento" class="form-control" 
                                       min="1" max="100" placeholder="Ej: 20">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Opcional - Aparecerá destacado en la oferta</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción (Opcional)</label>
                        <textarea name="descripcion" class="form-control" rows="3"
                                  placeholder="Describe los detalles de tu oferta..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Link del Producto</label>
                            <input type="url" name="link_producto" class="form-control" 
                                   placeholder="https://ejemplo.com/producto">
                            <small class="text-muted">URL directa al producto en oferta</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Imagen de la Oferta</label>
                            <input type="url" name="imagen_oferta" class="form-control" 
                                   placeholder="https://ejemplo.com/imagen.jpg">
                            <small class="text-muted">URL de imagen promocional</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Código de Cupón</label>
                            <input type="text" name="codigo_cupon" class="form-control" 
                                   placeholder="VERANO2024" maxlength="50" style="text-transform: uppercase;">
                            <small class="text-muted">Código que los clientes pueden usar (opcional)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Stock Limitado</label>
                            <input type="number" name="stock_limitado" class="form-control" 
                                   min="1" placeholder="100">
                            <small class="text-muted">Cantidad de cupones disponibles (opcional)</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Color del Badge</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="color" name="color_badge" class="form-control form-control-color" 
                                       value="#FFD700" title="Elige un color">
                                <small class="text-muted">Color personalizado para destacar</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="destacado" id="destacado">
                                <label class="form-check-label fw-bold" for="destacado">
                                    <i class="bi bi-star-fill text-warning me-1"></i>
                                    Marcar como Oferta Destacada
                                </label>
                                <small class="d-block text-muted">Aparecerá primero en la lista</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Términos y Condiciones</label>
                        <textarea name="terminos_condiciones" class="form-control" rows="3"
                                  placeholder="Ej: Válido solo para compras mayores a $500. No acumulable con otras promociones."></textarea>
                        <small class="text-muted">Especifica las condiciones de uso de la oferta</small>
                    </div>
                    
                    <button type="submit" class="btn-crear">
                        <i class="bi bi-plus-circle me-2"></i>Crear Oferta
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de ofertas -->
        <h3 class="mb-4">Mis Ofertas (<?php echo count($ofertas); ?>)</h3>
        
        <?php if (empty($ofertas)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>
                Aún no has creado ninguna oferta. ¡Crea tu primera promoción arriba!
            </div>
        <?php else: ?>
            <?php foreach ($ofertas as $oferta): ?>
                <div class="card-oferta <?php echo $oferta['estado_real']; ?>">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <h4 class="mb-0"><?php echo htmlspecialchars($oferta['titulo']); ?></h4>
                                <?php if ($oferta['porcentaje_descuento']): ?>
                                    <span class="badge bg-danger fs-6"><?php echo $oferta['porcentaje_descuento']; ?>% OFF</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($oferta['categoria_oferta']): ?>
                                <span class="badge bg-secondary mb-2">
                                    <?php 
                                    $categorias = [
                                        'descuento' => '💰 Descuento',
                                        '2x1' => '🎁 2x1',
                                        '3x2' => '🎉 3x2',
                                        'envio_gratis' => '🚚 Envío Gratis',
                                        'regalo' => '🎁 Regalo',
                                        'temporada' => '🌟 Temporada',
                                        'otro' => '📌 Otro'
                                    ];
                                    echo $categorias[$oferta['categoria_oferta']] ?? $oferta['categoria_oferta'];
                                    ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($oferta['descripcion']): ?>
                                <p class="text-muted mb-2"><?php echo nl2br(htmlspecialchars($oferta['descripcion'])); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($oferta['imagen_oferta']): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($oferta['imagen_oferta']); ?>" 
                                         alt="Imagen oferta" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($oferta['codigo_cupon']): ?>
                                <div class="mb-2">
                                    <span class="badge bg-primary">
                                        <i class="bi bi-ticket-perforated me-1"></i>
                                        Código: <?php echo htmlspecialchars($oferta['codigo_cupon']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($oferta['stock_limitado']): ?>
                                <div class="mb-2">
                                    <?php 
                                    $stock_restante = $oferta['stock_limitado'] - ($oferta['stock_usado'] ?? 0);
                                    $porcentaje_usado = ($oferta['stock_usado'] / $oferta['stock_limitado']) * 100;
                                    ?>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-box-seam me-1"></i>
                                        Stock: <?php echo $stock_restante; ?> / <?php echo $oferta['stock_limitado']; ?> disponibles
                                    </small>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar <?php echo $stock_restante < 10 ? 'bg-danger' : 'bg-success'; ?>" 
                                             style="width: <?php echo 100 - $porcentaje_usado; ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($oferta['destacado']): ?>
                                <div class="mb-2">
                                    <span class="badge" style="background: linear-gradient(135deg, #FFD700, #FFA500);">
                                        <i class="bi bi-star-fill me-1"></i>Destacada
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-3 flex-wrap">
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>
                                    Expira: <?php echo date('d/m/Y', strtotime($oferta['fecha_expiracion'])); ?>
                                </small>
                                
                                <?php if ($oferta['link_producto']): ?>
                                    <small>
                                        <a href="<?php echo htmlspecialchars($oferta['link_producto']); ?>" target="_blank" class="text-decoration-none">
                                            <i class="bi bi-link-45deg me-1"></i>Ver producto
                                        </a>
                                    </small>
                                <?php endif; ?>
                                
                                <small class="text-muted">
                                    <i class="bi bi-eye me-1"></i>
                                    <?php echo $oferta['vistas'] ?? 0; ?> vistas
                                </small>
                                
                                <small class="text-muted">
                                    <i class="bi bi-cursor me-1"></i>
                                    <?php echo $oferta['clics'] ?? 0; ?> clics
                                </small>
                            </div>
                        </div>
                        <div>
                            <?php if ($oferta['estado_real'] === 'activo'): ?>
                                <span class="badge bg-success badge-estado">Activa</span>
                            <?php elseif ($oferta['estado_real'] === 'pausado'): ?>
                                <span class="badge bg-warning badge-estado">Pausada</span>
                            <?php else: ?>
                                <span class="badge bg-danger badge-estado">Expirada</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <?php if ($oferta['estado_real'] === 'activo'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="pausar_oferta" value="1">
                                <input type="hidden" name="oferta_id" value="<?php echo $oferta['id']; ?>">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pause-circle me-1"></i>Pausar
                                </button>
                            </form>
                        <?php elseif ($oferta['estado_real'] === 'pausado'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="activar_oferta" value="1">
                                <input type="hidden" name="oferta_id" value="<?php echo $oferta['id']; ?>">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-play-circle me-1"></i>Activar
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta oferta?');">
                            <input type="hidden" name="eliminar_oferta" value="1">
                            <input type="hidden" name="oferta_id" value="<?php echo $oferta['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="bi bi-trash me-1"></i>Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
