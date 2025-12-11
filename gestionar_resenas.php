<?php
require_once 'config.php';

// Configuración de la página
$page_title = "Gestionar Reseñas";

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
$stmt = $pdo->prepare("SELECT id, nombre_tienda FROM tiendas WHERE vendedor_id = ? AND activo = 1");
$stmt->execute([$_SESSION['user_id']]);
$tienda = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tienda) {
    header("Location: panel_vendedor.php?error=no_tienda");
    exit();
}

$tienda_id = $tienda['id'];
$mensaje = '';
$error = '';

// Procesar respuesta a reseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder_resena'])) {
    $resena_id = (int)$_POST['resena_id'];
    $respuesta = isset($_POST['respuesta']) ? trim($_POST['respuesta']) : '';
    
    if (empty($respuesta)) {
        $error = "La respuesta no puede estar vacía";
    } elseif (strlen($respuesta) < 10) {
        $error = "La respuesta debe tener al menos 10 caracteres";
    } elseif (strlen($respuesta) > 1000) {
        $error = "La respuesta no puede exceder 1000 caracteres";
    } else {
        try {
            // Verificar que la reseña pertenezca a esta tienda
            $stmt = $pdo->prepare("SELECT id FROM calificaciones WHERE id = ? AND tienda_id = ?");
            $stmt->execute([$resena_id, $tienda_id]);
            
            if ($stmt->fetch()) {
                // Actualizar con la respuesta
                $stmt = $pdo->prepare("
                    UPDATE calificaciones 
                    SET respuesta_vendedor = ?, fecha_respuesta = NOW() 
                    WHERE id = ? AND tienda_id = ?
                ");
                $stmt->execute([$respuesta, $resena_id, $tienda_id]);
                $mensaje = "¡Respuesta publicada exitosamente!";
            } else {
                $error = "Reseña no encontrada";
            }
        } catch(PDOException $e) {
            $error = "Error al guardar la respuesta: " . $e->getMessage();
        }
    }
}

// Obtener todas las reseñas de la tienda
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre as usuario_nombre
        FROM calificaciones c
        INNER JOIN usuarios u ON c.user_id = u.id
        WHERE c.tienda_id = ? AND c.activo = 1 AND c.esta_aprobada = 1
        ORDER BY c.fecha_calificacion DESC
    ");
    $stmt->execute([$tienda_id]);
    $resenas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $resenas = [];
    $error = "Error al cargar reseñas";
}

// Incluir template del dashboard
include 'includes/vendor_dashboard_template.php';
?>

<style>
    .resena-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid #28a745;
    }
    
    .resena-card.sin-respuesta {
        border-left-color: #ffc107;
    }
    
    .resena-header {
        display: flex;
        justify-content: between;
        align-items: start;
        margin-bottom: 1rem;
    }
    
    .usuario-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .usuario-avatar {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #28a745, #006666);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1.2rem;
    }
    
    .estrellas {
        color: #ffc107;
        font-size: 1.2rem;
    }
    
    .respuesta-vendedor {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-left: 4px solid #28a745;
        padding: 1rem;
        margin-top: 1rem;
        border-radius: 8px;
    }
    
    .form-respuesta {
        margin-top: 1rem;
        padding: 1rem;
        background: #fff9e6;
        border-radius: 8px;
        border: 2px dashed #ffc107;
    }
    
    .btn-responder {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-responder:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
</style>

<div class="card-modern">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-comments"></i>
            Gestionar Reseñas de <?php echo htmlspecialchars($tienda['nombre_tienda']); ?>
        </h3>
    </div>
    <div class="card-body">
        <?php if ($mensaje): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($resenas)): ?>
            <div class="text-center" style="padding: 3rem;">
                <i class="fas fa-comment-slash" style="font-size: 4rem; color: #dee2e6; margin-bottom: 1rem;"></i>
                <h4>No hay reseñas aún</h4>
                <p class="text-muted">Cuando los clientes dejen reseñas, aparecerán aquí para que puedas responderlas.</p>
            </div>
        <?php else: ?>
            <div class="mb-3">
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Total de reseñas: <strong><?php echo count($resenas); ?></strong>
                </p>
            </div>

            <?php foreach ($resenas as $resena): ?>
                <div class="resena-card <?php echo empty($resena['respuesta_vendedor']) ? 'sin-respuesta' : ''; ?>">
                    <div class="resena-header">
                        <div class="usuario-info">
                            <div class="usuario-avatar">
                                <?php echo strtoupper(substr($resena['usuario_nombre'], 0, 1)); ?>
                            </div>
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($resena['usuario_nombre']); ?></h5>
                                <div class="estrellas">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $resena['estrellas']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <small class="text-muted">
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($resena['fecha_calificacion'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($resena['comentario'])); ?></p>
                    </div>

                    <?php if (!empty($resena['respuesta_vendedor'])): ?>
                        <!-- Respuesta ya publicada -->
                        <div class="respuesta-vendedor">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-store text-success me-2"></i>
                                <strong>Tu respuesta:</strong>
                                <small class="text-muted ms-auto">
                                    <?php echo date('d/m/Y H:i', strtotime($resena['fecha_respuesta'])); ?>
                                </small>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($resena['respuesta_vendedor'])); ?></p>
                        </div>
                    <?php else: ?>
                        <!-- Formulario para responder -->
                        <div class="form-respuesta">
                            <form method="POST">
                                <input type="hidden" name="responder_resena" value="1">
                                <input type="hidden" name="resena_id" value="<?php echo $resena['id']; ?>">
                                
                                <label class="form-label fw-bold">
                                    <i class="fas fa-reply"></i> Responder a esta reseña
                                </label>
                                <textarea 
                                    name="respuesta" 
                                    class="form-control mb-3" 
                                    rows="4" 
                                    required
                                    minlength="10"
                                    maxlength="1000"
                                    placeholder="Escribe tu respuesta profesional y amable..."
                                    style="border-radius: 8px; border: 2px solid #ffc107;"></textarea>
                                
                                <small class="text-muted d-block mb-3">
                                    <i class="fas fa-lightbulb"></i>
                                    <strong>Consejo:</strong> Agradece al cliente, aborda sus comentarios y muestra profesionalismo.
                                </small>
                                
                                <button type="submit" class="btn-responder">
                                    <i class="fas fa-paper-plane me-2"></i>Publicar Respuesta
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
include 'includes/vendor_dashboard_footer.php';
?>
