/**
 * Chispitas - Asistente Interactivo
 * Directorio de Tiendas Locales
 * Versión: 2.0 - Con efectos especiales
 * Última actualización: <?php echo date('Y-m-d H:i:s'); ?>
 */

class BateriaAsistente {
    constructor() {
        console.log('🎉 Chispitas v2.0 - Asistente con efectos especiales cargado!');
        this.isMenuVisible = false;
        this.isMensajeVisible = false;
        this.frasesDB = {}; // Frases organizadas por tipo desde la API

        this.init();
    }

    init() {
        // Verificar que estamos en una página permitida
        if (!this.shouldShowAssistant()) {
            return;
        }

        // Crear el HTML del asistente
        this.createAssistantHTML();

        // Cargar frases desde la base de datos
        this.loadFrasesFromDB();
    }

    shouldShowAssistant() {
        // Lista de páginas donde NO debe aparecer el asistente
        const excludedPages = [
            'auth.php',
            'auth.php',
            'dashboard_vendedor.php',
            'dashboard_admin.php',
            'panel_vendedor.php'
        ];

        const currentPage = window.location.pathname.split('/').pop();
        return !excludedPages.includes(currentPage);
    }

    createAssistantHTML() {
        const assistantHTML = `
            <div id="bateria-asistente" class="bateria-asistente">
                <div id="bateria-mensaje" class="bateria-mensaje">
                    <div class="mensaje-contenido">
                        <span id="mensaje-texto"></span>
                        <button class="btn-cerrar-mensaje" onclick="bateriaAsistente.hideMessage()">&times;</button>
                    </div>
                    <div class="mensaje-flecha"></div>
                </div>
                
                <div id="chispitas-menu" class="chispitas-menu">
                    <button id="btn-ayuda" class="btn-menu">¿Qué es esto?</button>
                    <button id="btn-funciones" class="btn-menu">💡 Tips de uso</button>
                    <button id="btn-motivar" class="btn-menu">✨ ¡Motívame!</button>
                    <button id="btn-interactivo" class="btn-menu">🎉 ¡Click aquí!</button>
                    <button id="btn-seguridad" class="btn-menu">🔐 Seguridad</button>
                    <button id="btn-vendedores" class="btn-menu">🏪 Para Vendedores</button>
                    <button id="btn-cerrar-menu" class="btn-menu btn-cerrar">Cerrar</button>
                </div>
                
                <img src="img/asistente_animado.gif" 
                     alt="Chispitas - Asistente Virtual" 
                     class="bateria-imagen"
                     title="¡Hola! Soy Chispitas, tu asistente virtual">
            </div>
        `;

        // Agregar al body
        document.body.insertAdjacentHTML('beforeend', assistantHTML);

        // Configurar event listeners
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Click en la imagen de Chispitas para mostrar/ocultar menú
        const bateriaImagen = document.querySelector('.bateria-imagen');
        if (bateriaImagen) {
            bateriaImagen.addEventListener('click', () => {
                this.toggleMenu();
            });
        }

        // Click en botón "¿Qué es esto?"
        const btnAyuda = document.getElementById('btn-ayuda');
        if (btnAyuda) {
            btnAyuda.addEventListener('click', () => {
                this.mostrarFraseAleatoria('ayuda');
            });
        }

        // Click en botón "Tips de uso"
        const btnFunciones = document.getElementById('btn-funciones');
        if (btnFunciones) {
            btnFunciones.addEventListener('click', () => {
                this.mostrarFraseAleatoria('funciones');
            });
        }

        // Click en botón "¡Motívame!"
        const btnMotivar = document.getElementById('btn-motivar');
        if (btnMotivar) {
            btnMotivar.addEventListener('click', () => {
                this.mostrarFraseAleatoria('motivacion');
            });
        }

        // Click en botón "¡Click aquí!" (Interactivo)
        const btnInteractivo = document.getElementById('btn-interactivo');
        if (btnInteractivo) {
            btnInteractivo.addEventListener('click', () => {
                this.efectoInteractivo();
            });
        }

        // Click en botón "Seguridad"
        const btnSeguridad = document.getElementById('btn-seguridad');
        if (btnSeguridad) {
            btnSeguridad.addEventListener('click', () => {
                this.mostrarFraseAleatoria('seguridad');
            });
        }

        // Click en botón "Para Vendedores"
        const btnVendedores = document.getElementById('btn-vendedores');
        if (btnVendedores) {
            btnVendedores.addEventListener('click', () => {
                this.mostrarFraseAleatoria('vendedores');
            });
        }

        // Click en botón "Cerrar"
        const btnCerrarMenu = document.getElementById('btn-cerrar-menu');
        if (btnCerrarMenu) {
            btnCerrarMenu.addEventListener('click', () => {
                this.cerrarTodo();
            });
        }
    }

    toggleMenu() {
        const menu = document.getElementById('chispitas-menu');
        if (!menu) return;

        // Ocultar mensaje si está visible
        this.hideMessage();

        // Toggle del menú
        if (this.isMenuVisible) {
            menu.classList.remove('menu-visible');
            this.isMenuVisible = false;
        } else {
            menu.classList.add('menu-visible');
            this.isMenuVisible = true;
        }
    }

    async loadFrasesFromDB() {
        try {
            const response = await fetch('api/get_chispitas_menu.php');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const frases = await response.json();

            // Organizar frases por tipo
            this.frasesDB = {};
            frases.forEach(frase => {
                if (!this.frasesDB[frase.tipo]) {
                    this.frasesDB[frase.tipo] = [];
                }
                this.frasesDB[frase.tipo].push(frase.respuesta);
            });

            console.log(`✅ Chispitas cargó ${frases.length} frases organizadas por categoría`);

        } catch (error) {
            console.error('❌ Error cargando frases de Chispitas:', error);
            // Usar frases de respaldo
            this.setupFallbackFrases();
        }
    }

    setupFallbackFrases() {
        this.frasesDB = {
            'ayuda': [
                '¡Hola! Soy Chispitas 🔋 Tu asistente virtual de Mercado Huasteco. Te ayudo a descubrir y conectar con los mejores negocios locales de la comunidad.',
                '🌟 Mercado Huasteco es tu directorio local favorito donde encuentras tiendas reales con sitios web propios. ¡Conectamos emprendedores locales contigo!'
            ],
            'funciones': [
                '🔍 Tip: Usa la barra de búsqueda para encontrar tiendas por nombre en tiempo real. Los filtros por categoría te ayudan a encontrar exactamente lo que necesitas.',
                '⭐ Las tiendas destacadas (con borde naranja) han demostrado excelencia en servicio, calidad y satisfacción del cliente.'
            ],
            'motivacion': [
                '🌟 Tu actitud determina tu altitud. Cada día es una nueva oportunidad para crecer, aprender y ser la mejor versión de ti mismo.',
                '💪 La constancia es la clave del éxito. Los grandes logros no llegan de la noche a la mañana, sino paso a paso, día a día.'
            ],
            'seguridad': [
                '🛡️ Tu seguridad es nuestra máxima prioridad. Ve a "Mi Perfil" para configurar tu pregunta secreta. Tus datos están protegidos con encriptación avanzada.',
                '🔐 Las respuestas a preguntas secretas se guardan encriptadas, nunca en texto plano. ¡Tu privacidad está garantizada!'
            ],
            'vendedores': [
                '🏪 Consejo de oro: La mejor publicidad es un cliente satisfecho. Un cliente feliz no solo regresa, sino que trae a 10 amigos más.',
                '📊 Los datos te dicen qué pasó, pero los clientes te dicen por qué pasó. Combina análisis numérico con feedback humano.'
            ]
        };
    }

    mostrarFraseAleatoria(tipo) {
        let frases = this.frasesDB[tipo];

        if (!frases || frases.length === 0) {
            // Fallback si no hay frases de ese tipo
            frases = ['¡Hola! Soy Chispitas 🔋 Estoy aquí para ayudarte con Mercado Huasteco.'];
        }

        // Seleccionar frase aleatoria
        const fraseAleatoria = frases[Math.floor(Math.random() * frases.length)];

        // Mostrar la frase
        this.displayMessage(fraseAleatoria);
        this.hideMenu();
    }

    cerrarTodo() {
        this.hideMenu();
        this.hideMessage();
    }

    hideMenu() {
        const menu = document.getElementById('chispitas-menu');
        if (menu) {
            menu.classList.remove('menu-visible');
            this.isMenuVisible = false;
        }
    }

    displayMessage(texto) {
        const mensajeElement = document.getElementById('bateria-mensaje');
        const textoElement = document.getElementById('mensaje-texto');

        if (mensajeElement && textoElement) {
            textoElement.innerHTML = texto;
            mensajeElement.classList.add('mensaje-visible');
            this.isMensajeVisible = true;
        }
    }

    hideMessage() {
        const mensajeElement = document.getElementById('bateria-mensaje');

        if (mensajeElement) {
            mensajeElement.classList.remove('mensaje-visible');
            this.isMensajeVisible = false;
        }
    }

    // Efecto interactivo especial
    efectoInteractivo() {
        console.log('✨ Efecto interactivo activado!');
        // Cerrar menú primero
        this.hideMenu();

        // Agregar clase de efecto especial al asistente
        const bateriaImagen = document.querySelector('.bateria-imagen');
        if (bateriaImagen) {
            bateriaImagen.classList.add('efecto-guiño');

            // Crear estrellas alrededor del asistente
            this.crearEstrellas();

            // Mostrar mensaje especial
            const mensajesEspeciales = [
                '✨ ¡Guiño guiño! ¿Te gustó mi efecto especial? 😉',
                '🌟 ¡Sorpresa! Soy más que un simple asistente, ¡soy tu amigo digital! 🎉',
                '⭐ ¡Magia pura! ¿Viste esas estrellas? ¡Son para ti! ✨',
                '🎊 ¡Tachán! ¿No es genial tener un asistente con personalidad? 😄',
                '💫 ¡Efecto especial activado! ¿Quieres ver más trucos? 🎭'
            ];

            const mensajeAleatorio = mensajesEspeciales[Math.floor(Math.random() * mensajesEspeciales.length)];
            this.displayMessage(mensajeAleatorio);

            // Quitar efecto después de 3 segundos
            setTimeout(() => {
                bateriaImagen.classList.remove('efecto-guiño');
            }, 3000);
        }
    }

    // Crear efecto de estrellas
    crearEstrellas() {
        const asistente = document.getElementById('bateria-asistente');
        if (!asistente) return;

        // Crear 8 estrellas alrededor del asistente
        for (let i = 0; i < 8; i++) {
            const estrella = document.createElement('div');
            estrella.className = 'estrella-efecto';
            estrella.innerHTML = '⭐';

            // Posición aleatoria alrededor del asistente
            const angulo = (i * 45) * (Math.PI / 180); // 45 grados entre cada estrella
            const radio = 80 + Math.random() * 40; // Radio variable
            const x = Math.cos(angulo) * radio;
            const y = Math.sin(angulo) * radio;

            estrella.style.cssText = `
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
                font-size: ${15 + Math.random() * 10}px;
                animation: estrella-flotante-${i} 2s ease-out forwards;
                pointer-events: none;
                z-index: 10002;
                --target-x: ${x}px;
                --target-y: ${y}px;
            `;

            // Crear animación única para cada estrella
            const style = document.createElement('style');
            style.textContent = `
                @keyframes estrella-flotante-${i} {
                    0% {
                        opacity: 0;
                        transform: translate(-50%, -50%) scale(0) rotate(0deg);
                    }
                    20% {
                        opacity: 1;
                        transform: translate(calc(-50% + ${x * 0.3}px), calc(-50% + ${y * 0.3}px)) scale(1.2) rotate(180deg);
                    }
                    80% {
                        opacity: 1;
                        transform: translate(calc(-50% + ${x}px), calc(-50% + ${y}px)) scale(1) rotate(360deg);
                    }
                    100% {
                        opacity: 0;
                        transform: translate(calc(-50% + ${x * 1.2}px), calc(-50% + ${y * 1.2}px)) scale(0.5) rotate(540deg);
                    }
                }
            `;
            document.head.appendChild(style);

            asistente.appendChild(estrella);

            // Remover estrella y estilo después de la animación
            setTimeout(() => {
                if (estrella.parentNode) {
                    estrella.parentNode.removeChild(estrella);
                }
                if (style.parentNode) {
                    style.parentNode.removeChild(style);
                }
            }, 2000);
        }
    }

    // Método para recargar las frases (útil después de cambios en la base de datos)
    async reloadFrases() {
        await this.loadFrasesFromDB();
    }
}

// Inicializar el asistente cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    // Verificar que la imagen existe antes de inicializar
    const img = new Image();
    img.onload = function () {
        window.bateriaAsistente = new BateriaAsistente();
    };
    img.onerror = function () {
        console.warn('Imagen del asistente no encontrada: img/asistente_animado.gif');
    };
    img.src = 'img/asistente_animado.gif';
});

// Click fuera del asistente para cerrar menú
document.addEventListener('click', function (event) {
    if (window.bateriaAsistente && window.bateriaAsistente.isMenuVisible) {
        const asistente = document.getElementById('bateria-asistente');
        if (asistente && !asistente.contains(event.target)) {
            window.bateriaAsistente.hideMenu();
        }
    }
});