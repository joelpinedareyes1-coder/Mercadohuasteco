<?php
require_once 'config.php';

// Configurar variables para el header
$page_title = "Política de Privacidad";
$page_description = "Política de privacidad y protección de datos de Mercado Huasteco";
$body_class = "privacidad-page";
$additional_css = ['css/legal-pages.css'];

// Incluir el header
include 'includes/header.php'; 
?>

<div class="legal-hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="legal-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1 class="legal-title">Política de Privacidad</h1>
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
                    <h2><i class="fas fa-info-circle me-2"></i>1. Introducción</h2>
                    <p>En Mercado Huasteco, nos tomamos muy en serio la privacidad de nuestros usuarios. Esta Política de Privacidad describe cómo recopilamos, usamos, compartimos y protegemos tu información personal cuando utilizas nuestra plataforma.</p>
                    <div class="info-box">
                        <p><strong>Compromiso:</strong> Nos comprometemos a proteger tu privacidad y a ser transparentes sobre cómo manejamos tus datos personales.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-database me-2"></i>2. Información que Recopilamos</h2>
                    
                    <h3>2.1 Información que Proporcionas Directamente</h3>
                    <p>Recopilamos información que nos proporcionas voluntariamente cuando:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> <strong>Te registras:</strong> Nombre, email, contraseña, tipo de cuenta</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Creas una tienda:</strong> Nombre de la tienda, descripción, categoría, ubicación, fotos, URL</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Dejas reseñas:</strong> Calificaciones, comentarios, fotos</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Contactas con soporte:</strong> Mensajes, consultas, reportes</li>
                    </ul>

                    <h3>2.2 Información Recopilada Automáticamente</h3>
                    <p>Cuando usas Mercado Huasteco, recopilamos automáticamente:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> <strong>Información del dispositivo:</strong> Tipo de dispositivo, sistema operativo, navegador</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Información de uso:</strong> Páginas visitadas, tiempo de navegación, clics</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Dirección IP:</strong> Para seguridad y análisis geográfico</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Cookies:</strong> Para mejorar tu experiencia (ver sección de cookies)</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-cogs me-2"></i>3. Cómo Usamos tu Información</h2>
                    <p>Utilizamos la información recopilada para:</p>
                    
                    <div class="purpose-grid">
                        <div class="purpose-item">
                            <i class="fas fa-user-check"></i>
                            <h4>Proporcionar el Servicio</h4>
                            <p>Crear y gestionar tu cuenta, mostrar tu tienda, procesar reseñas</p>
                        </div>
                        <div class="purpose-item">
                            <i class="fas fa-chart-line"></i>
                            <h4>Mejorar la Plataforma</h4>
                            <p>Analizar el uso, identificar problemas, desarrollar nuevas funciones</p>
                        </div>
                        <div class="purpose-item">
                            <i class="fas fa-envelope"></i>
                            <h4>Comunicarnos Contigo</h4>
                            <p>Enviar notificaciones, actualizaciones, responder consultas</p>
                        </div>
                        <div class="purpose-item">
                            <i class="fas fa-shield-alt"></i>
                            <h4>Seguridad</h4>
                            <p>Prevenir fraudes, proteger contra abusos, cumplir con la ley</p>
                        </div>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-share-alt me-2"></i>4. Cómo Compartimos tu Información</h2>
                    <p>No vendemos tu información personal. Compartimos información solo en las siguientes circunstancias:</p>
                    
                    <h3>4.1 Información Pública</h3>
                    <div class="info-box">
                        <p><strong>Visible para todos:</strong> Nombre de usuario, reseñas, calificaciones, información de tiendas (para vendedores)</p>
                    </div>

                    <h3>4.2 Con Proveedores de Servicios</h3>
                    <p>Compartimos información con proveedores que nos ayudan a operar la plataforma:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> Servicios de hosting y almacenamiento</li>
                        <li><i class="fas fa-check-circle"></i> Servicios de análisis</li>
                        <li><i class="fas fa-check-circle"></i> Servicios de email</li>
                    </ul>

                    <h3>4.3 Por Razones Legales</h3>
                    <p>Podemos divulgar información si es requerido por ley o para:</p>
                    <ul class="legal-list warning">
                        <li><i class="fas fa-exclamation-circle"></i> Cumplir con procesos legales</li>
                        <li><i class="fas fa-exclamation-circle"></i> Proteger nuestros derechos y propiedad</li>
                        <li><i class="fas fa-exclamation-circle"></i> Prevenir fraudes o actividades ilegales</li>
                        <li><i class="fas fa-exclamation-circle"></i> Proteger la seguridad de usuarios</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-cookie-bite me-2"></i>5. Cookies y Tecnologías Similares</h2>
                    <p>Utilizamos cookies y tecnologías similares para mejorar tu experiencia en Mercado Huasteco.</p>
                    
                    <h3>5.1 Tipos de Cookies que Usamos</h3>
                    <div class="cookie-types">
                        <div class="cookie-type">
                            <h4><i class="fas fa-check-circle text-success"></i> Cookies Esenciales</h4>
                            <p>Necesarias para el funcionamiento básico de la plataforma (inicio de sesión, seguridad)</p>
                        </div>
                        <div class="cookie-type">
                            <h4><i class="fas fa-chart-bar text-info"></i> Cookies de Análisis</h4>
                            <p>Nos ayudan a entender cómo los usuarios interactúan con la plataforma</p>
                        </div>
                        <div class="cookie-type">
                            <h4><i class="fas fa-cog text-primary"></i> Cookies de Funcionalidad</h4>
                            <p>Recuerdan tus preferencias y configuraciones</p>
                        </div>
                    </div>

                    <h3>5.2 Control de Cookies</h3>
                    <p>Puedes controlar las cookies a través de la configuración de tu navegador. Ten en cuenta que deshabilitar cookies puede afectar la funcionalidad de la plataforma.</p>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-lock me-2"></i>6. Seguridad de tu Información</h2>
                    <p>Implementamos medidas de seguridad técnicas y organizativas para proteger tu información:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> <strong>Encriptación:</strong> Usamos SSL/TLS para proteger datos en tránsito</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Contraseñas:</strong> Las contraseñas se almacenan encriptadas</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Acceso limitado:</strong> Solo personal autorizado puede acceder a datos sensibles</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Monitoreo:</strong> Monitoreamos actividades sospechosas</li>
                    </ul>

                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p><strong>Importante:</strong> Ningún sistema es 100% seguro. Te recomendamos usar contraseñas fuertes y no compartir tus credenciales.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-user-shield me-2"></i>7. Tus Derechos y Opciones</h2>
                    <p>Tienes los siguientes derechos sobre tu información personal:</p>
                    
                    <div class="rights-grid">
                        <div class="right-item">
                            <i class="fas fa-eye"></i>
                            <h4>Acceso</h4>
                            <p>Solicitar una copia de tu información personal</p>
                        </div>
                        <div class="right-item">
                            <i class="fas fa-edit"></i>
                            <h4>Corrección</h4>
                            <p>Actualizar o corregir información inexacta</p>
                        </div>
                        <div class="right-item">
                            <i class="fas fa-trash-alt"></i>
                            <h4>Eliminación</h4>
                            <p>Solicitar la eliminación de tu cuenta y datos</p>
                        </div>
                        <div class="right-item">
                            <i class="fas fa-ban"></i>
                            <h4>Oposición</h4>
                            <p>Oponerte a ciertos usos de tu información</p>
                        </div>
                    </div>

                    <h3>7.1 Cómo Ejercer tus Derechos</h3>
                    <p>Para ejercer cualquiera de estos derechos:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> Accede a la configuración de tu cuenta</li>
                        <li><i class="fas fa-check-circle"></i> Contáctanos en: privacidad@mercadohuasteco.com</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-clock me-2"></i>8. Retención de Datos</h2>
                    <p>Conservamos tu información personal mientras:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> Tu cuenta esté activa</li>
                        <li><i class="fas fa-check-circle"></i> Sea necesario para proporcionar nuestros servicios</li>
                        <li><i class="fas fa-check-circle"></i> Sea requerido por ley</li>
                    </ul>

                    <p>Cuando eliminas tu cuenta, eliminamos o anonimizamos tu información personal, excepto cuando debamos conservarla por razones legales.</p>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-child me-2"></i>9. Privacidad de Menores</h2>
                    <p>Mercado Huasteco no está dirigido a menores de 18 años. No recopilamos intencionalmente información de menores.</p>
                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p><strong>Si eres padre/tutor:</strong> Si crees que tu hijo nos ha proporcionado información personal, contáctanos inmediatamente para eliminarla.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-globe me-2"></i>10. Transferencias Internacionales</h2>
                    <p>Tu información puede ser transferida y almacenada en servidores ubicados fuera de tu país de residencia. Tomamos medidas para asegurar que tu información reciba un nivel adecuado de protección.</p>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-edit me-2"></i>11. Cambios a esta Política</h2>
                    <p>Podemos actualizar esta Política de Privacidad periódicamente. Te notificaremos sobre cambios significativos mediante:</p>
                    <ul class="legal-list">
                        <li><i class="fas fa-check-circle"></i> Un aviso en la plataforma</li>
                        <li><i class="fas fa-check-circle"></i> Un email a tu dirección registrada</li>
                    </ul>
                    <p>Te recomendamos revisar esta política regularmente.</p>
                </div>

                <div class="legal-section">
                    <h2><i class="fas fa-envelope me-2"></i>12. Contacto</h2>
                    <p>Si tienes preguntas sobre esta Política de Privacidad o sobre cómo manejamos tu información:</p>
                    <div class="contact-info">
                        <p><i class="fas fa-envelope me-2"></i><strong>Email:</strong> privacidad@mercadohuasteco.com</p>
                        <p><i class="fas fa-phone me-2"></i><strong>Teléfono:</strong> +1 (555) 123-4567</p>
                        <p><i class="fas fa-map-marker-alt me-2"></i><strong>Dirección:</strong> Campus Universitario, Ciudad, País</p>
                    </div>
                </div>

                <div class="legal-footer">
                    <p><i class="fas fa-calendar-alt me-2"></i>Última actualización: <?php echo date('d \d\e F \d\e Y'); ?></p>
                    <p><i class="fas fa-shield-alt me-2"></i>Tu privacidad es importante para nosotros.</p>
                </div>
            </div>

            <div class="legal-actions">
                <a href="index.php" class="btn btn-primary-modern">
                    <i class="fas fa-home me-2"></i>Volver al Inicio
                </a>
                <a href="terminos-de-uso.php" class="btn btn-outline-modern">
                    <i class="fas fa-file-contract me-2"></i>Términos de Uso
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
