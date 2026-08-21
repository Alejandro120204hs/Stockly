/**
 * Stockly — Landing page interactions (vanilla JS)
 * Módulos:
 *   1. initMobileNav      -> menú hamburguesa en navbar
 *   2. initHeroBoxes      -> tilt 3D + paralaje de las cajas del hero
 *   3. initScrollReveal   -> animación de aparición al hacer scroll
 *   4. initContactForm    -> validación y feedback del formulario de contacto
 */

document.addEventListener('DOMContentLoaded', function () {
    initMobileNav();
    initHeroBoxes();
    initScrollReveal();
    initContactForm();
});

/* --------------------------------------------------------------------
 * 1. Navbar móvil
 * ------------------------------------------------------------------ */
function initMobileNav() {
    var toggle = document.querySelector('.navbar__toggle');
    var links = document.querySelector('.navbar__links');

    if (!toggle || !links) {
        return;
    }

    toggle.addEventListener('click', function () {
        var isOpen = links.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // Cierra el menú al elegir un link (mejora la navegación en móvil)
    links.querySelectorAll('.navbar__link').forEach(function (link) {
        link.addEventListener('click', function () {
            links.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
}

/* --------------------------------------------------------------------
 * 2. Cajas 3D del hero: tilt siguiendo el cursor + paralaje por profundidad
 *
 * Cada .hero-box tiene tres capas anidadas:
 *   .hero-box            -> posición base + animación CSS de flotación
 *   .hero-box__parallax  -> desplazamiento (translate) según el mouse
 *   .hero-box__inner     -> rotación 3D (tilt) según el mouse
 *
 * Separar las capas evita que la animación CSS (flotación) y las
 * transformaciones controladas por JS (tilt/paralaje) se pisen entre sí.
 * ------------------------------------------------------------------ */
function initHeroBoxes() {
    var stage = document.querySelector('.hero-stage');

    if (!stage) {
        return;
    }

    var boxes = Array.prototype.map.call(stage.querySelectorAll('.hero-box'), function (box) {
        return {
            el: box,
            parallaxEl: box.querySelector('.hero-box__parallax'),
            innerEl: box.querySelector('.hero-box__inner'),
            depth: parseFloat(box.dataset.depth) || 0.5,
            baseRx: parseFloat(box.dataset.baseRx) || 0,
            baseRy: parseFloat(box.dataset.baseRy) || 0
        };
    });

    // Soporte táctil: sin puntero fino no hay "hover", así que se omite el tilt
    // y las cajas se quedan solo con la flotación CSS (evita jank en móvil).
    var supportsFinePointer = window.matchMedia('(pointer: fine)').matches;
    if (!supportsFinePointer || boxes.length === 0) {
        return;
    }

    // Posición del cursor normalizada entre -1 y 1, relativa a la ventana completa.
    // Se escucha en window (no en el stage) para que el tilt reaccione al mouse
    // en cualquier parte de la página, no solo dentro del área de las cajas.
    var targetX = 0;
    var targetY = 0;
    var currentX = 0;
    var currentY = 0;

    window.addEventListener('mousemove', function (event) {
        targetX = (event.clientX / window.innerWidth) * 2 - 1;
        targetY = (event.clientY / window.innerHeight) * 2 - 1;
    });

    document.addEventListener('mouseleave', function () {
        targetX = 0;
        targetY = 0;
    });

    var TILT_STRENGTH = 22;      // grados máximos de rotación añadidos por el mouse
    var PARALLAX_STRENGTH = 26;  // píxeles máximos de desplazamiento por el mouse
    var EASING = 0.07;           // suavizado del movimiento (menor = más "flotante")

    function renderFrame() {
        // Interpola la posición actual hacia la posición objetivo (smoothing)
        currentX += (targetX - currentX) * EASING;
        currentY += (targetY - currentY) * EASING;

        boxes.forEach(function (box) {
            var tiltY = box.baseRy + currentX * TILT_STRENGTH * box.depth;
            var tiltX = box.baseRx - currentY * TILT_STRENGTH * box.depth;
            box.innerEl.style.transform =
                'rotateX(' + tiltX + 'deg) rotateY(' + tiltY + 'deg)';

            var moveX = currentX * PARALLAX_STRENGTH * box.depth;
            var moveY = currentY * PARALLAX_STRENGTH * box.depth;
            box.parallaxEl.style.transform =
                'translate3d(' + moveX + 'px, ' + moveY + 'px, 0)';
        });

        requestAnimationFrame(renderFrame);
    }

    requestAnimationFrame(renderFrame);
}

/* --------------------------------------------------------------------
 * 3. Revelado de secciones al hacer scroll (IntersectionObserver)
 * ------------------------------------------------------------------ */
function initScrollReveal() {
    var elements = document.querySelectorAll('.reveal');

    if (elements.length === 0) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        elements.forEach(function (el) {
            el.classList.add('is-visible');
        });
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -40px 0px'
    });

    elements.forEach(function (el) {
        observer.observe(el);
    });
}

/* --------------------------------------------------------------------
 * 4. Formulario de contacto
 *
 * Nota: este formulario aún no envía datos a un backend/endpoint real.
 * Solo valida los campos en el cliente y muestra un mensaje de
 * confirmación visual. Conectar a un endpoint queda pendiente.
 * ------------------------------------------------------------------ */
function initContactForm() {
    var form = document.querySelector('.contact-form form');

    if (!form) {
        return;
    }

    var status = form.querySelector('.form-status');

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (status) {
            status.textContent = 'Gracias por escribirnos. Te contactaremos pronto.';
            status.classList.add('is-visible');
        }

        form.reset();
    });
}
