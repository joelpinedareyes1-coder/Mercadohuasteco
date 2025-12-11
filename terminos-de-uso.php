<?php
require_once 'config.php';

// Configurar variables para el header
$page_title = "Términos de Uso";
$page_description = "Términos y condiciones de uso de Mercado Huasteco";
$body_class = "terminos-page";
$additional_css = ['css/legal-pages.css'];

// Incluir el header
include 'includes/header.php'; 
?>

<div class="legal-hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="legal-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
                <h1 class="legal-title">Términos de Uso</h1>
                <p class="legal-subtitle">Última actualización: <?php echo date('d/m/Y'); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="container legal-content">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="legal-card">
                <div class="legal-section">
                    <h2><i class="fas fa-handshake me-2"></i>1. Aceptación de Términos</h2>
                    <p>Bienvenido a Mercado Huasteco. Al acceder y utilizar esta plataforma, aceptas estar sujeto a estos términos de uso, todas las leyes y regulaciones aplicables, y aceptas que eres responsable del cumplimiento de las leyes locales aplicables.</p>
                    <p>Si no estás de acuerdo con alguno de estos términos, tienes prohibido usar o acceder a este sitio.</p>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-info-circle me-2"></i>2. Descripción del Servicio</h2>
                    <p>Mercado Huasteco es una plataforma digital que conecta estudiantes universitarios con tiendas y servicios locales. Proporcionamos:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> Un directorio de tiendas verificadas</li>
                        <li><i class="fas fa-check-circle"></i> Sistema de reseñas y calificaciones</li>
                        <li><i class="fas fa-check-circle"></i> Herramientas para vendedores para gestionar sus tiendas</li>
                        <li><i class="fas fa-check-circle"></i> Búsqueda inteligente y filtros avanzados</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-user-check me-2"></i>3. Registro y Cuenta de Usuario</h2>
                    <h3>3.1 Requisitos de Registro</h3>
                    <p>Para utilizar ciertas funciones de la plataforma, debes registrarte y crear una cuenta. Al registrarte, te comprometes a:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> Proporcionar información veraz, precisa y actualizada</li>
                        <li><i class="fas fa-check-circle"></i> Mantener la seguridad de tu contraseña</li>
                        <li><i class="fas fa-check-circle"></i> Notificarnos inmediatamente sobre cualquier uso no autorizado de tu cuenta</li>
                        <li><i class="fas fa-check-circle"></i> Ser responsable de todas las actividades que ocurran bajo tu cuenta</li>
                    </ul>

                    <h3>3.2 Tipos de Cuenta</h3>
                    <div class="info-box">
                        <p><strong>Cliente:</strong> Puede explorar tiendas, dejar reseñas y gestionar favoritos.</p>
                        <p><strong>Vendedor:</strong> Puede crear y gestionar tiendas, responder reseñas y acceder a estadísticas.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-shield-alt me-2"></i>4. Responsabilidades del Usuario</h2>
                    <h3>4.1 Uso Apropiado</h3>
                    <p>Te comprometes a NO:</p>
                    <ul class="legal-list warning">
                        <li><i class="fas fa-times-circle"></i> Publicar contenido ofensivo, difamatorio o ilegal</li>
                        <li><i class="fas fa-times-circle"></i> Acosar, amenazar o intimidar a otros usuarios</li>
                        <li><i class="fas fa-times-circle"></i> Intentar acceder a cuentas de otros usuarios</li>
                        <li><i class="fas fa-times-circle"></i> Usar la plataforma para actividades fraudulentas</li>
                        <li><i class="fas fa-times-circle"></i> Publicar spam o contenido promocional no autorizado</li>
                        <li><i class="fas fa-times-circle"></i> Manipular el sistema de calificaciones</li>
                    </ul>

                    <h3>4.2 Contenido del Usuario</h3>
                    <p>Eres responsable del contenido que publicas en la plataforma, incluyendo reseñas, comentarios y fotos. Al publicar contenido, garantizas que:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> Tienes los derechos necesarios sobre el contenido</li>
                        <li><i class="fas fa-check-circle"></i> El contenido no infringe derechos de terceros</li>
                        <li><i class="fas fa-check-circle"></i> El contenido es veraz y no engañoso</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-store me-2"></i>5. Responsabilidades de los Vendedores</h2>
                    <p>Si te registras como vendedor, además de las responsabilidades generales, te comprometes a:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> Proporcionar información precisa sobre tu tienda y productos/servicios</li>
                        <li><i class="fas fa-check-circle"></i> Mantener actualizada la información de tu tienda</li>
                        <li><i class="fas fa-check-circle"></i> Responder de manera profesional a las reseñas</li>
                        <li><i class="fas fa-check-circle"></i> Cumplir con todas las leyes y regulaciones aplicables a tu negocio</li>
                        <li><i class="fas fa-check-circle"></i> No publicar información falsa o engañosa</li>
                    </ul>

                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p><strong>Importante:</strong> Mercado Huasteco es una plataforma de directorio. No somos responsables de las transacciones entre vendedores y clientes.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-star me-2"></i>6. Sistema de Reseñas y Calificaciones</h2>
                    <h3>6.1 Reseñas de Clientes</h3>
                    <p>Las reseñas deben ser honestas, basadas en experiencias reales y respetuosas. Nos reservamos el derecho de eliminar reseñas que:</p>
                    <ul class="legal-list warning">
                        <li><i class="fas fa-times-circle"></i> Contengan lenguaje ofensivo o discriminatorio</li>
                        <li><i class="fas fa-times-circle"></i> Sean falsas o fraudulentas</li>
                        <li><i class="fas fa-times-circle"></i> Violen la privacidad de terceros</li>
                        <li><i class="fas fa-times-circle"></i> Contengan spam o publicidad</li>
                    </ul>

                    <h3>6.2 Respuestas de Vendedores</h3>
                    <p>Los vendedores pueden responder a las reseñas de manera profesional y constructiva. Las respuestas deben ser respetuosas y no pueden contener ataques personales.</p>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-copyright me-2"></i>7. Propiedad Intelectual</h2>
                    <p>Todo el contenido de Mercado Huasteco, incluyendo pero no limitado a texto, gráficos, logos, iconos, imágenes, clips de audio, descargas digitales y compilaciones de datos, es propiedad de Mercado Huasteco o de sus proveedores de contenido y está protegido por las leyes de propiedad intelectual.</p>
                    
                    <h3>7.1 Licencia de Uso</h3>
                    <p>Te otorgamos una licencia limitada, no exclusiva, no transferible y revocable para acceder y usar la plataforma para fines personales y no comerciales.</p>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-ban me-2"></i>8. Limitación de Responsabilidad</h2>
                    <p>Mercado Huasteco se proporciona "tal cual" y "según disponibilidad". No garantizamos que:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-info-circle"></i> El servicio será ininterrumpido o libre de errores</li>
                        <li><i class="fas fa-info-circle"></i> Los resultados obtenidos serán precisos o confiables</li>
                        <li><i class="fas fa-info-circle"></i> La calidad de productos o servicios de vendedores cumplirá tus expectativas</li>
                    </ul>

                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p><strong>Descargo de Responsabilidad:</strong> No somos responsables de las transacciones, disputas o problemas entre usuarios y vendedores. Actuamos únicamente como plataforma de conexión.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-gavel me-2"></i>9. Terminación de Cuenta</h2>
                    <p>Nos reservamos el derecho de suspender o terminar tu cuenta en cualquier momento si:</p>
                    <ul class="legal-list warning">
                        <li><i class="fas fa-times-circle"></i> Violas estos términos de uso</li>
                        <li><i class="fas fa-times-circle"></i> Proporcionas información falsa</li>
                        <li><i class="fas fa-times-circle"></i> Participas en actividades fraudulentas</li>
                        <li><i class="fas fa-times-circle"></i> Recibes múltiples quejas de otros usuarios</li>
                    </ul>

                    <p>Puedes eliminar tu cuenta en cualquier momento desde la configuración de tu perfil.</p>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-edit me-2"></i>10. Modificaciones a los Términos</h2>
                    <p>Nos reservamos el derecho de modificar estos términos en cualquier momento. Las modificaciones entrarán en vigor inmediatamente después de su publicación en la plataforma. Tu uso continuado de Mercado Huasteco después de cualquier modificación constituye tu aceptación de los nuevos términos.</p>
                    
                    <div class="info-box">
                        <p><strong>Recomendación:</strong> Te sugerimos revisar periódicamente estos términos para estar al tanto de cualquier cambio.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-balance-scale me-2"></i>11. Ley Aplicable</h2>
                    <p>Estos términos se regirán e interpretarán de acuerdo con las leyes aplicables, sin dar efecto a ningún principio de conflictos de leyes.</p>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-envelope me-2"></i>12. Contacto</h2>
                    <p>Si tienes preguntas sobre estos términos de uso, puedes contactarnos:</p>
                    <div class="contact-info">
                        <p><i class="fas fa-envelope me-2"></i><strong>Email:</strong> legal@mercadohuasteco.com</p>
                        <p><i class="fas fa-phone me-2"></i><strong>Teléfono:</strong> +1 (555) 123-4567</p>
                        <p><i class="fas fa-map-marker-alt me-2"></i><strong>Dirección:</strong> Campus Universitario, Ciudad, País</p>
                    </div>
                </div>

                <div class="legal-footer">
                    <p><i class="fas fa-calendar-alt me-2"></i>Última actualización: <?php echo date('d \d\e F \d\e Y'); ?></p>
                    <p><i class="fas fa-check-circle me-2"></i>Al usar Mercado Huasteco, aceptas estos términos de uso.</p>
                </div>
            </div>

            <div class="legal-actions">
                <a href="index.php" class="btn btn-primary-modern">
                    <i class="fas fa-home me-2"></i>Volver al Inicio
                </a>
                <a href="politica-privacidad.php" class="btn btn-outline-modern">
                    <i class="fas fa-shield-alt me-2"></i>Política de Privacidad
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
