/**
 * DESKTOP ENHANCEMENTS - Mercado Huasteco
 * Mejoras de interactividad y animaciones para desktop
 */

(function() {
    'use strict';

    // Detectar si es desktop
    function isDesktop() {
        return window.innerWidth > 768;
    }

    // ===== ANIMACIONES AL SCROLL =====
    function initScrollAnimations() {
        if (!isDesktop()) return;

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    // Opcional: dejar de observar después de animar
                    // observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observar elementos con clase animate-in
        document.querySelectorAll('.animate-in, .tienda-item, .stat-card, .step-card').forEach(el => {
            observer.observe(el);
        });
    }

    // ===== PARALLAX SUAVE EN HERO =====
    function initParallax() {
        if (!isDesktop()) return;

        const heroBackground = document.querySelector('.hero-background');
        if (!heroBackground) return;

        let ticking = false;

        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;
                    const parallaxSpeed = 0.5;
                    heroBackground.style.transform = `translateY(${scrolled * parallaxSpeed}px)`;
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    // ===== SMOOTH SCROLL PARA ANCLAS =====
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '#registro') return;

                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    const headerHeight = isDesktop() ? 80 : 70;
                    const targetPosition = target.offsetTop - headerHeight;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    // ===== CONTADOR ANIMADO PARA ESTADÍSTICAS =====
    function initCounters() {
        if (!isDesktop()) return;

        const counters = document.querySelectorAll('.stat-number[data-count]');
        if (counters.length === 0) return;

        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                    animateCounter(entry.target);
                    entry.target.classList.add('counted');
                }
            });
        }, observerOptions);

        counters.forEach(counter => observer.observe(counter));
    }

    function animateCounter(element) {
        const target = parseInt(element.getAttribute('data-count'));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        };

        updateCounter();
    }

    // ===== HOVER 3D EN TARJETAS =====
    function init3DHover() {
        if (!isDesktop()) return;

        const cards = document.querySelectorAll('.tienda-card, .tienda-card-modern, .stat-card');

        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;

                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-10px)`;
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    }

    // ===== LAZY LOADING DE IMÁGENES =====
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.add('loaded');
                            imageObserver.unobserve(img);
                        }
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    // ===== HEADER SCROLL EFFECT =====
    function initHeaderScroll() {
        if (!isDesktop()) return;

        const header = document.querySelector('.mi-navbar-principal');
        if (!header) return;

        let lastScroll = 0;
        let ticking = false;

        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const currentScroll = window.pageYOffset;

                    if (currentScroll > 100) {
                        header.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.15)';
                        header.style.background = 'rgba(255, 255, 255, 0.98)';
                    } else {
                        header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.08)';
                        header.style.background = 'rgba(255, 255, 255, 0.95)';
                    }

                    // Auto-hide header al scroll down (opcional)
                    // if (currentScroll > lastScroll && currentScroll > 200) {
                    //     header.style.transform = 'translateY(-100%)';
                    // } else {
                    //     header.style.transform = 'translateY(0)';
                    // }

                    lastScroll = currentScroll;
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    // ===== TOOLTIPS =====
    function initTooltips() {
        if (!isDesktop()) return;

        const tooltipElements = document.querySelectorAll('[data-tooltip]');

        tooltipElements.forEach(el => {
            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = el.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);

            el.addEventListener('mouseenter', (e) => {
                const rect = el.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) + 'px';
                tooltip.style.top = rect.top - 40 + 'px';
                tooltip.classList.add('show');
            });

            el.addEventListener('mouseleave', () => {
                tooltip.classList.remove('show');
            });
        });

        // Agregar estilos para tooltips
        if (!document.querySelector('#tooltip-styles')) {
            const style = document.createElement('style');
            style.id = 'tooltip-styles';
            style.textContent = `
                .custom-tooltip {
                    position: fixed;
                    background: #333;
                    color: white;
                    padding: 8px 12px;
                    border-radius: 6px;
                    font-size: 0.85rem;
                    pointer-events: none;
                    opacity: 0;
                    transform: translate(-50%, 0);
                    transition: opacity 0.3s ease;
                    z-index: 10000;
                    white-space: nowrap;
                }
                .custom-tooltip.show {
                    opacity: 1;
                }
                .custom-tooltip::after {
                    content: '';
                    position: absolute;
                    top: 100%;
                    left: 50%;
                    transform: translateX(-50%);
                    border: 6px solid transparent;
                    border-top-color: #333;
                }
            `;
            document.head.appendChild(style);
        }
    }

    // ===== FILTROS CON ANIMACIÓN =====
    function initFilterAnimations() {
        const filterButtons = document.querySelectorAll('.filter-btn');

        filterButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remover active de todos
                filterButtons.forEach(b => b.classList.remove('active'));
                // Agregar active al clickeado
                this.classList.add('active');

                // Animar las tarjetas
                const items = document.querySelectorAll('.tienda-item');
                items.forEach((item, index) => {
                    item.style.animation = 'none';
                    setTimeout(() => {
                        item.style.animation = `fadeInScale 0.5s ease ${index * 0.1}s both`;
                    }, 10);
                });
            });
        });
    }

    // ===== BÚSQUEDA EN TIEMPO REAL =====
    function initLiveSearch() {
        const searchInput = document.querySelector('#searchInput, .search-input-modern');
        if (!searchInput) return;

        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.toLowerCase();

            searchTimeout = setTimeout(() => {
                const items = document.querySelectorAll('.tienda-item');
                let visibleCount = 0;

                items.forEach(item => {
                    const nombre = item.dataset.nombre || '';
                    const descripcion = item.dataset.descripcion || '';
                    const categoria = item.dataset.categoria || '';

                    const matches = nombre.includes(query) ||
                        descripcion.includes(query) ||
                        categoria.includes(query);

                    if (matches || query === '') {
                        item.style.display = '';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                // Actualizar contador
                const counter = document.querySelector('#results-count');
                if (counter) {
                    counter.textContent = visibleCount;
                }
            }, 300);
        });
    }

    // ===== INICIALIZACIÓN =====
    function init() {
        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        // Inicializar todas las funcionalidades
        initScrollAnimations();
        initParallax();
        initSmoothScroll();
        initCounters();
        init3DHover();
        initLazyLoading();
        initHeaderScroll();
        initTooltips();
        initFilterAnimations();
        initLiveSearch();

        // Reinicializar en resize (con debounce)
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (isDesktop()) {
                    init3DHover();
                    initParallax();
                }
            }, 250);
        });

        console.log('✅ Desktop enhancements initialized');
    }

    // Iniciar
    init();

})();
