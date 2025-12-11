/**
 * Mejoras de experiencia móvil para Mercado Huasteco
 * Este archivo contiene funcionalidades específicas para dispositivos móviles
 */

// Detectar si es un dispositivo móvil
function isMobile() {
    return window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Detectar si es iOS
function isIOS() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
}

// Detectar si es Android
function isAndroid() {
    return /Android/i.test(navigator.userAgent);
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function () {
    if (isMobile()) {
        initMobileEnhancements();
    }
});

// Función principal de mejoras móviles
function initMobileEnhancements() {
    // Aplicar clase móvil al body
    document.body.classList.add('mobile-device');

    // Inicializar todas las mejoras
    setupTouchEnhancements();
    setupScrollEnhancements();
    setupFormEnhancements();
    setupNavigationEnhancements();
    setupPerformanceEnhancements();
    setupAccessibilityEnhancements();

    // Mejoras específicas para autenticación
    setupAuthMobileEnhancements();

    console.log('✅ Mejoras móviles inicializadas');
}

// Mejoras de touch y gestos
function setupTouchEnhancements() {
    // Mejorar el tap en elementos clickeables
    const clickableElements = document.querySelectorAll('button, .btn, a, input[type="submit"], input[type="button"]');

    clickableElements.forEach(element => {
        // Agregar clase para estilos touch
        element.classList.add('touch-target');

        // Mejorar feedback visual
        element.addEventListener('touchstart', function () {
            this.classList.add('touch-active');
        });

        element.addEventListener('touchend', function () {
            setTimeout(() => {
                this.classList.remove('touch-active');
            }, 150);
        });

        element.addEventListener('touchcancel', function () {
            this.classList.remove('touch-active');
        });
    });

    // Prevenir doble tap zoom en botones
    clickableElements.forEach(element => {
        element.addEventListener('touchend', function (e) {
            e.preventDefault();
            this.click();
        });
    });
}

// Mejoras de scroll
function setupScrollEnhancements() {
    // Scroll suave para iOS
    if (isIOS()) {
        document.body.style.webkitOverflowScrolling = 'touch';
    }

    // Mejorar scroll en modales
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.style.webkitOverflowScrolling = 'touch';
    });

    // Scroll to top suave
    window.scrollToTop = function () {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };
}

// Mejoras de formularios
function setupFormEnhancements() {
    const inputs = document.querySelectorAll('input, textarea, select');

    inputs.forEach(input => {
        // Prevenir zoom en iOS
        if (isIOS()) {
            if (input.type === 'text' || input.type === 'email' || input.type === 'password' || input.tagName === 'TEXTAREA') {
                input.style.fontSize = '16px';
            }
        }

        // Mejorar UX de inputs
        input.addEventListener('focus', function () {
            // Scroll al input en móvil
            setTimeout(() => {
                this.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 300);

            // Agregar clase de focus
            this.classList.add('mobile-focused');
        });

        input.addEventListener('blur', function () {
            this.classList.remove('mobile-focused');
        });
    });

    // Mejorar selects en móvil
    const selects = document.querySelectorAll('select');
    selects.forEach(select => {
        select.addEventListener('change', function () {
            this.classList.add('has-value');
        });
    });
}

// Mejoras de navegación
function setupNavigationEnhancements() {
    // Mejorar menú hamburguesa
    const hamburgerBtn = document.querySelector('.btn-menu-movil, .hamburger-btn');
    const mobileMenu = document.querySelector('.navbar-links-area');

    if (hamburgerBtn && mobileMenu) {
        hamburgerBtn.addEventListener('click', function (e) {
            e.preventDefault();
            toggleMobileMenu();
        });

        // Cerrar menú al hacer clic en un enlace
        const menuLinks = mobileMenu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function () {
                if (mobileMenu.classList.contains('mobile-active')) {
                    toggleMobileMenu();
                }
            });
        });

        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', function (e) {
            if (!hamburgerBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
                if (mobileMenu.classList.contains('mobile-active')) {
                    mobileMenu.classList.remove('mobile-active');
                }
            }
        });
    }

    // Mejorar sidebar en dashboard
    const sidebarToggle = document.querySelector('.hamburger-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (sidebarToggle && sidebar) {
        // Gestos de swipe para abrir/cerrar sidebar
        let startX = 0;
        let currentX = 0;
        let isDragging = false;

        document.addEventListener('touchstart', function (e) {
            startX = e.touches[0].clientX;
            isDragging = true;
        });

        document.addEventListener('touchmove', function (e) {
            if (!isDragging) return;
            currentX = e.touches[0].clientX;
        });

        document.addEventListener('touchend', function (e) {
            if (!isDragging) return;
            isDragging = false;

            const diffX = currentX - startX;

            // Swipe desde la izquierda para abrir
            if (startX < 50 && diffX > 100) {
                if (sidebar && !sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            }

            // Swipe hacia la izquierda para cerrar
            if (diffX < -100 && sidebar && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        });
    }
}

// Mejoras de rendimiento
function setupPerformanceEnhancements() {
    // Lazy loading para imágenes
    const images = document.querySelectorAll('img[data-src]');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback para navegadores sin IntersectionObserver
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    }

    // Debounce para eventos de resize
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            handleResize();
        }, 250);
    });
}

// Mejoras de accesibilidad
function setupAccessibilityEnhancements() {
    // Mejorar contraste en modo oscuro
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.body.classList.add('dark-mode-preferred');
    }

    // Mejorar navegación por teclado
    const focusableElements = document.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );

    focusableElements.forEach(element => {
        element.addEventListener('focus', function () {
            this.classList.add('keyboard-focused');
        });

        element.addEventListener('blur', function () {
            this.classList.remove('keyboard-focused');
        });
    });

    // Anunciar cambios importantes para lectores de pantalla
    window.announceToScreenReader = function (message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.style.position = 'absolute';
        announcement.style.left = '-10000px';
        announcement.textContent = message;
        document.body.appendChild(announcement);

        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    };
}

// Manejar cambios de orientación
function handleResize() {
    // Ajustar altura de viewport en móvil
    if (isMobile()) {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }

    // Cerrar menús abiertos al cambiar orientación
    const mobileMenu = document.querySelector('.navbar-links-area');
    if (mobileMenu && mobileMenu.classList.contains('mobile-active')) {
        mobileMenu.classList.remove('mobile-active');
    }

    const sidebar = document.querySelector('.sidebar');
    if (sidebar && sidebar.classList.contains('active') && window.innerWidth > 768) {
        closeSidebar();
    }
}

// Funciones globales para compatibilidad
window.toggleMobileMenu = function () {
    const mobileMenu = document.querySelector('.navbar-links-area');
    const hamburgerBtn = document.querySelector('.btn-menu-movil');

    if (mobileMenu) {
        mobileMenu.classList.toggle('mobile-active');

        // Cambiar icono del hamburger
        if (hamburgerBtn) {
            const icon = hamburgerBtn.querySelector('i');
            if (icon) {
                if (mobileMenu.classList.contains('mobile-active')) {
                    icon.className = 'fas fa-times';
                } else {
                    icon.className = 'fas fa-bars';
                }
            }
        }

        // Anunciar cambio para accesibilidad
        if (window.announceToScreenReader) {
            const isOpen = mobileMenu.classList.contains('mobile-active');
            window.announceToScreenReader(isOpen ? 'Menú abierto' : 'Menú cerrado');
        }
    }
};

// Funciones para sidebar (dashboard)
window.toggleSidebar = function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (sidebar && overlay) {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');

        // Prevenir scroll del body cuando sidebar está abierto
        if (sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
};

window.closeSidebar = function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (sidebar && overlay) {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
};

// Funciones específicas para autenticación móvil
function setupAuthMobileEnhancements() {
    if (!isMobile()) return;

    // Mejorar formularios de autenticación
    const authForms = document.querySelectorAll('.form');
    authForms.forEach(form => {
        form.classList.add('auth-form');

        // Mejorar inputs
        const inputGroups = form.querySelectorAll('.input-group');
        inputGroups.forEach(group => {
            group.classList.add('auth-input-group');
        });

        // Mejorar botones
        const buttons = form.querySelectorAll('.btn, button[type="submit"]');
        buttons.forEach(btn => {
            btn.classList.add('auth-btn-primary');
        });
    });

    // Mejorar selección de rol
    const roleSelection = document.querySelector('.role-selection');
    if (roleSelection) {
        roleSelection.classList.add('auth-role-selection');

        const roleCards = roleSelection.querySelectorAll('.role-card');
        roleCards.forEach(card => {
            card.classList.add('auth-role-card');

            // Agregar feedback táctil
            card.addEventListener('touchstart', function () {
                if (navigator.vibrate) {
                    navigator.vibrate(30);
                }
            });

            // Mejorar selección visual
            const radio = card.querySelector('input[type="radio"]');
            if (radio) {
                radio.addEventListener('change', function () {
                    // Remover selección de otros cards
                    roleCards.forEach(otherCard => {
                        otherCard.classList.remove('selected');
                    });

                    // Agregar selección al card actual
                    if (this.checked) {
                        card.classList.add('selected');
                    }
                });

                // Verificar estado inicial
                if (radio.checked) {
                    card.classList.add('selected');
                }
            }
        });
    }

    // Mejorar mensajes
    const mensajes = document.querySelectorAll('.mensaje');
    mensajes.forEach(mensaje => {
        mensaje.classList.add('auth-message');

        // Hacer mensajes deslizables para cerrar
        let startX = 0;
        let currentX = 0;
        let isDragging = false;

        mensaje.addEventListener('touchstart', function (e) {
            startX = e.touches[0].clientX;
            isDragging = true;
            this.style.transition = 'none';
        });

        mensaje.addEventListener('touchmove', function (e) {
            if (!isDragging) return;
            currentX = e.touches[0].clientX;
            const diffX = currentX - startX;

            if (diffX > 0) {
                this.style.transform = `translateX(${diffX}px)`;
                this.style.opacity = Math.max(0.3, 1 - (diffX / 200));
            }
        });

        mensaje.addEventListener('touchend', function () {
            if (!isDragging) return;
            isDragging = false;

            const diffX = currentX - startX;
            this.style.transition = 'all 0.3s ease';

            if (diffX > 100) {
                // Cerrar mensaje
                this.style.transform = 'translateX(100%)';
                this.style.opacity = '0';
                setTimeout(() => {
                    this.remove();
                }, 300);
            } else {
                // Volver a posición original
                this.style.transform = 'translateX(0)';
                this.style.opacity = '1';
            }
        });
    });

    // Mejorar switches entre formularios
    const mobileSwitches = document.querySelectorAll('.mobile-switch');
    mobileSwitches.forEach(switchEl => {
        switchEl.classList.add('auth-switch');
    });

    // Mejorar opciones del formulario
    const formOptions = document.querySelectorAll('.form-options');
    formOptions.forEach(options => {
        options.classList.add('auth-form-options');

        const rememberMe = options.querySelector('.remember-me');
        if (rememberMe) {
            rememberMe.classList.add('auth-remember-me');
        }

        const forgotPassword = options.querySelector('.forgot-password');
        if (forgotPassword) {
            forgotPassword.classList.add('auth-forgot-password');
        }
    });
}

// Agregar estilos CSS para las mejoras móviles
const mobileStyles = document.createElement('style');
mobileStyles.textContent = `
    .touch-target {
        -webkit-tap-highlight-color: rgba(0, 102, 102, 0.2);
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        user-select: none;
    }
    
    .touch-active {
        transform: scale(0.98);
        opacity: 0.8;
        transition: all 0.1s ease;
    }
    
    .mobile-focused {
        border-color: #006666 !important;
        box-shadow: 0 0 0 3px rgba(0, 102, 102, 0.2) !important;
    }
    
    .keyboard-focused {
        outline: 2px solid #006666 !important;
        outline-offset: 2px !important;
    }
    
    @media (max-width: 768px) {
        .mobile-device {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        
        .lazy {
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .lazy.loaded {
            opacity: 1;
        }
        
        /* Mejoras específicas para auth */
        .auth-role-card.selected {
            animation: authPulse 0.3s ease;
        }
        
        .auth-message {
            user-select: none;
            -webkit-user-select: none;
        }
        
        /* Mejorar área táctil */
        .auth-btn-primary,
        .auth-btn-secondary,
        .auth-role-card {
            -webkit-tap-highlight-color: rgba(0, 102, 102, 0.2);
            -webkit-touch-callout: none;
        }
        
        /* Prevenir zoom en inputs */
        .auth-input-group input {
            font-size: 16px !important;
            -webkit-text-size-adjust: 100%;
        }
        
        /* Mejorar scroll */
        .auth-form {
            -webkit-overflow-scrolling: touch;
            overflow-y: auto;
        }
    }
    
    @keyframes authPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }
`;

document.head.appendChild(mobileStyles);

// Inicializar mejoras al cargar la página
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        if (isMobile()) {
            initMobileEnhancements();
        }
    });
} else {
    if (isMobile()) {
        initMobileEnhancements();
    }
}