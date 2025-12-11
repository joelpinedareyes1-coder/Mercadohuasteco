<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea vendedor
if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor') {
    header("Location: auth.php");
    exit();
}

// Configuración de la página
$page_title = "Galería de Fotos";

// Obtener el ID del vendedor logueado
$vendedor_id = (int)$_SESSION['user_id'];

// Verificar que el vendedor tenga una tienda registrada
try {
    $stmt = $pdo->prepare("SELECT id, nombre_tienda FROM tiendas WHERE vendedor_id = ?");
    $stmt->execute([$vendedor_id]);
    $tienda = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tienda) {
        header("Location: panel_vendedor.php");
        exit();
    }
    
    $tienda_id = $tienda['id'];
} catch(PDOException $e) {
    header("Location: panel_vendedor.php");
    exit();
}

// Obtener información de membresía del usuario
$stmt_usuario = $pdo->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
$stmt_usuario->execute([$vendedor_id]);
$usuario_info = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

// Establecer límites según membresía
$es_premium = isset($usuario_info['es_premium']) && $usuario_info['es_premium'] == 1;
$limite_fotos = $es_premium ? 10 : 2;

// Obtener cantidad actual de fotos
$stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM galeria_tiendas WHERE tienda_id = ? AND activo = 1");
$stmt_count->execute([$tienda_id]);
$total_fotos_actual = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

$mensaje = '';
$error = '';

// Procesar subida de nueva foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_foto'])) {
    // Verificar límite de fotos PRIMERO
    if ($total_fotos_actual >= $limite_fotos) {
        $error = "Has alcanzado el límite de $limite_fotos fotos para tu membresía. ";
        if (!$es_premium) {
            $error .= "¡Actualiza a Premium para subir hasta 10 fotos!";
        }
    } else {
        $descripcion = limpiar_entrada($_POST['descripcion']);
        
        // Validar que se subió una imagen
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            $error = "Debes seleccionar una imagen para subir.";
        } else {
        $upload_dir = 'uploads/galeria/';
        
        // Crear directorio si no existe
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $error = "No se pudo crear el directorio de galería.";
            }
        }
        
        if (empty($error)) {
            $file_extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                // Verificar tamaño (máximo 5MB)
                if ($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
                    $error = "La imagen no puede ser mayor a 5MB.";
                } else {
                    $new_filename = 'galeria_' . $tienda_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_path)) {
                        // Guardar en base de datos
                        try {
                            $stmt = $pdo->prepare("INSERT INTO galeria_tiendas (tienda_id, url_imagen, descripcion) VALUES (?, ?, ?)");
                            $stmt->execute([$tienda_id, $upload_path, $descripcion]);
                            
                            $mensaje = "Foto agregada exitosamente a tu galería.";
                            
                            // Recalcular total de fotos
                            $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM galeria_tiendas WHERE tienda_id = ? AND activo = 1");
                            $stmt_count->execute([$tienda_id]);
                            $total_fotos_actual = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
                        } catch(PDOException $e) {
                            $error = "Error al guardar la foto: " . $e->getMessage();
                            // Eliminar archivo si no se pudo guardar en BD
                            if (file_exists($upload_path)) {
                                unlink($upload_path);
                            }
                        }
                    } else {
                        $error = "Error al subir la imagen.";
                    }
                }
            } else {
                $error = "Solo se permiten imágenes (JPG, PNG, GIF, WebP).";
            }
        }
        }
    }
}

// Procesar eliminación de foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_foto'])) {
    $foto_id = (int)$_POST['foto_id'];
    
    try {
        // Obtener información de la foto antes de eliminar
        $stmt = $pdo->prepare("SELECT url_imagen FROM galeria_tiendas WHERE id = ? AND tienda_id = ?");
        $stmt->execute([$foto_id, $tienda_id]);
        $foto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($foto) {
            // Eliminar de base de datos
            $stmt = $pdo->prepare("DELETE FROM galeria_tiendas WHERE id = ? AND tienda_id = ?");
            $stmt->execute([$foto_id, $tienda_id]);
            
            // Eliminar archivo físico
            if (file_exists($foto['url_imagen'])) {
                unlink($foto['url_imagen']);
            }
            
            $mensaje = "Foto eliminada exitosamente.";
            
            // Recalcular total de fotos
            $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM galeria_tiendas WHERE tienda_id = ? AND activo = 1");
            $stmt_count->execute([$tienda_id]);
            $total_fotos_actual = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
        } else {
            $error = "No se encontró la foto a eliminar.";
        }
    } catch(PDOException $e) {
        $error = "Error al eliminar la foto: " . $e->getMessage();
    }
}

// Obtener todas las fotos de la galería
try {
    $stmt = $pdo->prepare("SELECT * FROM galeria_tiendas WHERE tienda_id = ? AND activo = 1 ORDER BY fecha_subida DESC");
    $stmt->execute([$tienda_id]);
    $fotos_galeria = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $fotos_galeria = [];
}

// Incluir template del dashboard
include 'includes/vendor_dashboard_template.php';
?>

<!-- Contenido específico de la página -->
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

<style>
/* Estilos para el card de límite de fotos */
.limite-fotos-card {
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.premium-card {
    background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
    border: 2px solid #ffd700;
}

.normal-card {
    background: linear-gradient(135deg, #e3f2fd 0%, #f5f9ff 100%);
    border: 2px solid #2196f3;
}

.limite-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.limite-tipo {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.icono-premium, .icono-normal {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.icono-premium {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #000;
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
}

.icono-normal {
    background: linear-gradient(135deg, #2196f3 0%, #64b5f6 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
}

.tipo-titulo {
    font-weight: 700;
    font-size: 1.1rem;
    color: #333;
}

.tipo-subtitulo {
    font-size: 0.85rem;
    color: #666;
    margin-top: 2px;
}

.limite-contador {
    text-align: center;
}

.contador-numero {
    font-size: 2rem;
    font-weight: 800;
    color: #28a745;
    line-height: 1;
}

.contador-numero.limite-alcanzado {
    color: #dc3545;
}

.contador-label {
    font-size: 0.8rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 4px;
}

.limite-upgrade {
    background: linear-gradient(135deg, #fff3cd 0%, #fffbeb 100%);
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
    color: #856404;
}

.limite-upgrade i {
    font-size: 1.2rem;
    color: #ffc107;
}

.limite-info-premium {
    background: linear-gradient(135deg, #d4edda 0%, #e8f5e9 100%);
    border: 1px solid #28a745;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
    color: #155724;
}

.limite-info-premium i {
    font-size: 1.2rem;
    color: #28a745;
}

/* Estilos para la galería */
.foto-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.85);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.foto-card:hover .foto-overlay {
    opacity: 1;
}

.btn-foto-ver, .btn-foto-borrar {
    padding: 0.6rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 3px 10px rgba(0,0,0,0.3);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-foto-ver {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
}

.btn-foto-ver:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    color: white !important;
}

.btn-foto-borrar {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white !important;
}

.btn-foto-borrar:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(245, 87, 108, 0.5);
}
</style>

<div class="row">
    <!-- Sección de subida -->
    <div class="col-md-4">
        <div class="card-modern">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Subir Nueva Foto
                </h3>
            </div>
            <div class="card-body">
                <!-- Información de membresía y límite -->
                <div class="limite-fotos-card <?php echo $es_premium ? 'premium-card' : 'normal-card'; ?>">
                    <div class="limite-header">
                        <div class="limite-tipo">
                            <?php if ($es_premium): ?>
                                <div class="icono-premium">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <div>
                                    <div class="tipo-titulo">Membresía Premium</div>
                                    <div class="tipo-subtitulo">Beneficios exclusivos</div>
                                </div>
                            <?php else: ?>
                                <div class="icono-normal">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <div class="tipo-titulo">Membresía Normal</div>
                                    <div class="tipo-subtitulo">Plan básico</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="limite-contador">
                            <div class="contador-numero <?php echo $total_fotos_actual >= $limite_fotos ? 'limite-alcanzado' : ''; ?>">
                                <?php echo $total_fotos_actual; ?> / <?php echo $limite_fotos; ?>
                            </div>
                            <div class="contador-label">fotos</div>
                        </div>
                    </div>
                    
                    <?php if (!$es_premium): ?>
                        <div class="limite-upgrade">
                            <i class="fas fa-star"></i>
                            <span>¿Quieres más fotos? <strong>Actualiza a Premium</strong> y sube hasta <strong>10 fotos</strong></span>
                        </div>
                    <?php else: ?>
                        <div class="limite-info-premium">
                            <i class="fas fa-check-circle"></i>
                            <span>Disfruta de tu límite extendido de <strong>10 fotos</strong></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-image"></i> Seleccionar Imagen
                        </label>
                        <div class="upload-area" style="border: 2px dashed var(--border-color); border-radius: var(--border-radius); padding: 2rem; text-align: center; transition: all 0.3s ease; cursor: pointer;">
                            <i class="fas fa-cloud-upload-alt text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h5 class="mb-2">Arrastra una imagen aquí</h5>
                            <p class="text-muted mb-3">o haz clic para seleccionar</p>
                            <input type="file" class="form-control" name="imagen" accept="image/*" required style="opacity: 0; position: absolute; width: 100%; height: 100%; cursor: pointer;">
                        </div>
                        <small class="text-muted">JPG, PNG, GIF, WebP (Máximo 5MB)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="descripcion">
                            <i class="fas fa-align-left"></i> Descripción (Opcional)
                        </label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion" 
                               placeholder="Ej: Interior de la tienda, productos destacados...">
                    </div>
                    
                    <button type="submit" name="subir_foto" class="btn-modern btn-primary w-100" 
                            <?php echo $total_fotos_actual >= $limite_fotos ? 'disabled' : ''; ?>>
                        <i class="fas fa-upload"></i>
                        <?php if ($total_fotos_actual >= $limite_fotos): ?>
                            Límite Alcanzado
                        <?php else: ?>
                            Subir Foto (<?php echo $limite_fotos - $total_fotos_actual; ?> disponibles)
                        <?php endif; ?>
                    </button>
                </form>
                
                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Las fotos se mostrarán en la página de detalle de tu tienda
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Galería actual -->
    <div class="col-md-8">
        <div class="card-modern">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-images"></i>
                        Mi Galería (<?php echo count($fotos_galeria); ?> fotos)
                    </h3>
                    
                    <?php if (!empty($fotos_galeria)): ?>
                        <small class="text-muted">
                            <i class="fas fa-mouse"></i> Pasa el cursor sobre las fotos para ver opciones
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($fotos_galeria)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-images text-muted" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                        <h4 class="text-muted mb-3">Tu galería está vacía</h4>
                        <p class="text-muted">Sube tu primera foto para mostrar tu tienda a los clientes</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($fotos_galeria as $foto): ?>
                            <div class="col-lg-4 col-md-6 mb-4" id="foto-card-<?php echo $foto['id']; ?>">
                                <div class="foto-card" style="position: relative; border-radius: var(--border-radius-lg); overflow: hidden; box-shadow: var(--shadow-md); transition: all 0.3s ease;">
                                    <img src="<?php echo htmlspecialchars($foto['url_imagen']); ?>" 
                                         style="width: 100%; height: 200px; object-fit: cover;" 
                                         alt="<?php echo htmlspecialchars($foto['descripcion']); ?>">
                                    
                                    <div class="foto-overlay">
                                        <div class="text-center">
                                            <?php if ($foto['descripcion']): ?>
                                                <p class="mb-3" style="font-size: 0.9rem; font-weight: 500;"><?php echo htmlspecialchars($foto['descripcion']); ?></p>
                                            <?php endif; ?>
                                            
                                            <div style="display: flex; gap: 0.75rem; justify-content: center; margin-bottom: 1rem;">
                                                <a href="<?php echo htmlspecialchars($foto['url_imagen']); ?>" 
                                                   target="_blank" 
                                                   class="btn-foto-ver">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                <button type="button" 
                                                        class="btn-foto-borrar" 
                                                        data-id="<?php echo $foto['id']; ?>"
                                                        data-desc="<?php echo htmlspecialchars($foto['descripcion']); ?>">
                                                    <i class="fas fa-trash-alt"></i> Borrar
                                                </button>
                                            </div>
                                            
                                            <div>
                                                <small style="opacity: 0.9; font-size: 0.8rem;">
                                                    <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($foto['fecha_subida'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<?php
// JavaScript específico para la galería
$inline_js = '
    
    // Drag and drop para el área de subida
    const uploadArea = document.querySelector(".upload-area");
    const fileInput = document.querySelector("input[type=\"file\"]");
    
    if (uploadArea && fileInput) {
        uploadArea.addEventListener("dragover", function(e) {
            e.preventDefault();
            this.style.borderColor = "var(--primary-color)";
            this.style.backgroundColor = "rgba(40, 167, 69, 0.1)";
        });
        
        uploadArea.addEventListener("dragleave", function(e) {
            e.preventDefault();
            this.style.borderColor = "var(--border-color)";
            this.style.backgroundColor = "transparent";
        });
        
        uploadArea.addEventListener("drop", function(e) {
            e.preventDefault();
            this.style.borderColor = "var(--border-color)";
            this.style.backgroundColor = "transparent";
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                showNotification("Archivo seleccionado: " + files[0].name, "success");
            }
        });
        
        uploadArea.addEventListener("click", function() {
            fileInput.click();
        });
        
        fileInput.addEventListener("change", function() {
            if (this.files.length > 0) {
                showNotification("Archivo seleccionado: " + this.files[0].name, "success");
            }
        });
    }
    
    // Efectos hover para las fotos
    document.querySelectorAll(".foto-card").forEach(card => {
        const overlay = card.querySelector(".foto-overlay");
        
        card.addEventListener("mouseenter", function() {
            this.style.transform = "translateY(-5px) scale(1.02)";
            if (overlay) overlay.style.opacity = "1";
        });
        
        card.addEventListener("mouseleave", function() {
            this.style.transform = "translateY(0) scale(1)";
            if (overlay) overlay.style.opacity = "0";
        });
    });
    
    // Event listeners para botones de eliminar con AJAX
    document.querySelectorAll(".btn-foto-borrar").forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            const fotoId = this.getAttribute("data-id");
            const fotoDesc = this.getAttribute("data-desc");
            
            if (confirm("¿Estás seguro de que quieres eliminar esta foto?" + (fotoDesc ? "\\n\\n" + fotoDesc : ""))) {
                eliminarFotoAjax(fotoId);
            }
        });
    });
    
    // Función para eliminar foto con AJAX
    function eliminarFotoAjax(fotoId) {
        // Mostrar indicador de carga
        const fotoCard = document.getElementById("foto-card-" + fotoId);
        if (fotoCard) {
            fotoCard.style.opacity = "0.5";
            fotoCard.style.pointerEvents = "none";
        }
        
        // Hacer petición AJAX
        fetch("api_eliminar_foto.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "foto_id=" + fotoId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Animar y eliminar la tarjeta
                if (fotoCard) {
                    fotoCard.style.transition = "all 0.3s ease";
                    fotoCard.style.transform = "scale(0)";
                    fotoCard.style.opacity = "0";
                    
                    setTimeout(() => {
                        fotoCard.remove();
                        
                        // Actualizar contador
                        const contador = document.querySelector(".badge");
                        if (contador && data.total_fotos !== undefined) {
                            const limite = ' . $limite_fotos . ';
                            contador.textContent = data.total_fotos + " / " + limite + " fotos";
                            contador.className = data.total_fotos >= limite ? "badge bg-danger" : "badge bg-primary";
                        }
                        
                        // Actualizar botón de subir
                        const btnSubir = document.querySelector("button[name=\'subir_foto\']");
                        if (btnSubir && data.total_fotos !== undefined) {
                            const limite = ' . $limite_fotos . ';
                            if (data.total_fotos < limite) {
                                btnSubir.disabled = false;
                                btnSubir.innerHTML = "<i class=\'fas fa-upload\'></i> Subir Foto (" + (limite - data.total_fotos) + " disponibles)";
                            }
                        }
                        
                        showNotification(data.message, "success");
                    }, 300);
                }
            } else {
                // Restaurar tarjeta en caso de error
                if (fotoCard) {
                    fotoCard.style.opacity = "1";
                    fotoCard.style.pointerEvents = "auto";
                }
                showNotification(data.message, "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            // Restaurar tarjeta en caso de error
            if (fotoCard) {
                fotoCard.style.opacity = "1";
                fotoCard.style.pointerEvents = "auto";
            }
            showNotification("Error al eliminar la foto. Intenta de nuevo.", "error");
        });
    }
    
    // Event listeners para botones de ver
    document.querySelectorAll(".btn-foto-ver").forEach(btn => {
        btn.addEventListener("click", function(e) {
            // El botón ya es un enlace, no necesita JavaScript adicional
        });
    });
';

include 'includes/vendor_dashboard_footer.php';
?>