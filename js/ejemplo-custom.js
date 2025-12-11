/**
 * JavaScript específico para la página de ejemplo
 * Demuestra cómo añadir funcionalidades específicas de página
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Página de ejemplo cargada correctamente');
    
    // Inicializar funcionalidades específicas
    initEjemploFunctionality();
    initAnimaciones();
    initFormularioContacto();
    initEfectosVisuales();
});

/**
 * Funcionalidad específica de ejemplo
 */
function initEjemploFunctionality() {
    const ejemploBtn = document.getElementById('ejemploBtn');
    
    if (ejemploBtn) {
        ejemploBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Efecto de ripple
            createRippleEffect(this, e);
            
            // Mostrar mensaje personalizado
            setTimeout(() => {
                showCustomAlert('¡Excelente!', 'Has probado la funcionalidad de ejemplo. La plantilla base está funcionando correctamente.', 'success');
            }, 300);
        });
    }
}

/**
 * Inicializar animaciones de entrada
 */
function initAnimaciones() {
    // Observador de intersección para animaciones
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    // Observar elementos para animar
    document.querySelectorAll('.card-modern').forEach(card => {
        observer.observe(card);
    });
}

/**
 * Funcionalidad del formulario de contacto
 */
function initFormularioContacto() {
    const form = document.querySelector('form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validación básica
            const inputs = this.querySelectorAll('input[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    showFieldError(input, 'Este campo es obligatorio');
                } else {
                    clearFieldError(input);
                }
            });
            
            // Validación de email
            const emailInput = this.querySelector('input[type="email"]');
            if (emailInput && emailInput.value && !isValidEmail(emailInput.value)) {
                isValid = false;
                showFieldError(emailInput, 'Por favor ingresa un email válido');
            }
            
            if (isValid) {
                // Simular envío
                showLoadingState(this);
                
                setTimeout(() => {
                    hideLoadingState(this);
                    showCustomAlert('¡Mensaje Enviado!', 'Tu mensaje ha sido enviado correctamente. Te responderemos pronto.', 'success');
                    this.reset();
                }, 2000);
            }
        });
        
        // Validación en tiempo real
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    showFieldError(this, 'Este campo es obligatorio');
                } else {
                    clearFieldError(this);
                }
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    }
}

/**
 * Efectos visuales adicionales
 */
function initEfectosVisuales() {
    // Efecto parallax sutil
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.bg-gradient-primary');
        
        parallaxElements.forEach(element => {
            const speed = 0.5;
            element.style.transform = `translateY(${scrolled * speed}px)`;
        });
    });
    
    // Efecto hover mejorado para las tarjetas
    document.querySelectorAll('.card-modern').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Contador animado para números
    animateCounters();
}

/**
 * Crear efecto ripple en botones
 */
function createRippleEffect(button, event) {
    const ripple = document.createElement('span');
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.classList.add('ripple-effect');
    
    button.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

/**
 * Mostrar alerta personalizada
 */
function showCustomAlert(title, message, type = 'info') {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type}-modern alert-dismissible fade show position-fixed`;
    alertContainer.style.cssText = `
        top: 100px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: var(--shadow-hover);
    `;
    
    alertContainer.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${getIconForType(type)} me-2"></i>
            <div>
                <strong>${title}</strong><br>
                <small>${message}</small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertContainer);
    
    // Auto-remove después de 5 segundos
    setTimeout(() => {
        if (alertContainer.parentNode) {
            alertContainer.remove();
        }
    }, 5000);
}

/**
 * Obtener icono según el tipo de alerta
 */
function getIconForType(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Mostrar error en campo de formulario
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('is-invalid');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Limpiar error de campo de formulario
 */
function clearFieldError(field) {
    field.classList.remove('is-invalid');
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Validar email
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Mostrar estado de carga en formulario
 */
function showLoadingState(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
        submitBtn.classList.add('loading-custom');
    }
}

/**
 * Ocultar estado de carga en formulario
 */
function hideLoadingState(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Enviar Mensaje';
        submitBtn.classList.remove('loading-custom');
    }
}

/**
 * Animar contadores
 */
function animateCounters() {
    const counters = document.querySelectorAll('[data-count]');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-count'));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current);
        }, 16);
    });
}

/**
 * Utilidades adicionales
 */

// Debounce function para optimizar eventos
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function para scroll events
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Agregar estilos CSS dinámicamente
const dynamicStyles = `
    .ripple-effect {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .is-invalid {
        border-color: var(--danger-color) !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: var(--danger-color);
    }
`;

// Inyectar estilos
const styleSheet = document.createElement('style');
styleSheet.textContent = dynamicStyles;
document.head.appendChild(styleSheet);

console.log('✅ JavaScript de ejemplo inicializado correctamente');