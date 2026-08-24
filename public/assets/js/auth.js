/**
 * Stockly — Vistas de autenticación (vanilla JS)
 * Compartido por login, registro y "olvidé mi contraseña".
 * Módulos:
 *   1. initAuthBackground -> red de formas flotantes conectadas (canvas)
 *   2. initPasswordToggle -> mostrar/ocultar contraseña en cualquier campo
 */

document.addEventListener('DOMContentLoaded', function () {
    initAuthBackground();
    initPasswordToggle();
});

/* --------------------------------------------------------------------
 * 1. Fondo animado: formas de inventario flotando y conectándose
 *
 * Técnica: canvas 2D (no SVG) porque son ~20-30 elementos moviéndose y
 * recalculando distancias entre sí en cada frame — con SVG eso implica
 * tocar el DOM constantemente (un nodo por forma + por línea), mientras
 * que canvas dibuja todo en un solo buffer de píxeles por frame, mucho
 * más liviano para este caso.
 *
 * Cada "partícula" es una forma simple (cuadrado, rombo o etiqueta) que
 * representa productos/inventario de forma abstracta. Se mueven muy
 * lento y rebotan en los bordes; cuando dos quedan lo bastante cerca,
 * se traza una línea tenue entre ellas (más opaca cuanto más cerca),
 * y esas líneas se recalculan en cada frame -por eso la red se ve viva.
 * ------------------------------------------------------------------ */
function initAuthBackground() {
    var canvas = document.getElementById('authCanvas');
    if (!canvas || !canvas.getContext) {
        return;
    }

    var ctx = canvas.getContext('2d');
    var reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    // Colores de la paleta "a mano": canvas no puede leer variables CSS,
    // así que se copian aquí los mismos valores de auth.css. Opacidades
    // subidas a propósito (antes 0.55/0.4) para que la red se note
    // claramente sobre el fondo oscuro sin competir con la card.
    var COLOR_SHAPE = 'rgba(201, 185, 154, 0.85)'; // sand
    var COLOR_LINE_RGB = '74, 124, 111';           // sage, sin alpha (se arma abajo)
    var LINE_MAX_OPACITY = 0.6;
    var SHAPE_LINE_WIDTH = 1.6;
    var LINE_WIDTH = 1.3;
    var MAX_LINK_DISTANCE = 140; // px: a esta distancia o menos, dos formas se conectan

    var width = 0;
    var height = 0;
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var particles = [];
    var rafId = null;
    var resizeTimer = null;

    /**
     * Menos partículas en pantallas chicas: mismo efecto visual, pero sin
     * pedirle a un celular que calcule cientos de distancias por frame.
     */
    function particleCountFor(w) {
        if (w < 560) return 12;
        if (w < 960) return 18;
        return 26;
    }

    function resizeCanvas() {
        width = canvas.clientWidth;
        height = canvas.clientHeight;
        canvas.width = Math.round(width * dpr);
        canvas.height = Math.round(height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function createParticles() {
        var shapes = ['square', 'diamond', 'tag'];
        var count = particleCountFor(width);

        particles = [];
        for (var i = 0; i < count; i++) {
            particles.push({
                x: Math.random() * width,
                y: Math.random() * height,
                // Velocidad baja a propósito (subida respecto a la primera
                // versión, que casi no se notaba): debe sentirse como que
                // flota con calma, nunca como que "vuela" por la pantalla.
                vx: (Math.random() - 0.5) * 0.3,
                vy: (Math.random() - 0.5) * 0.3,
                size: 10 + Math.random() * 10,
                rotation: Math.random() * Math.PI * 2,
                rotationSpeed: (Math.random() - 0.5) * 0.0016,
                shape: shapes[i % shapes.length]
            });
        }
    }

    /** Dibuja una sola forma (cuadrado, rombo o "etiqueta") en su posición actual. */
    function drawParticle(p) {
        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate(p.rotation);
        ctx.strokeStyle = COLOR_SHAPE;
        ctx.lineWidth = SHAPE_LINE_WIDTH;

        var s = p.size;

        if (p.shape === 'square') {
            ctx.strokeRect(-s / 2, -s / 2, s, s);
        } else if (p.shape === 'diamond') {
            ctx.beginPath();
            ctx.moveTo(0, -s / 2);
            ctx.lineTo(s / 2, 0);
            ctx.lineTo(0, s / 2);
            ctx.lineTo(-s / 2, 0);
            ctx.closePath();
            ctx.stroke();
        } else {
            // "tag": un rectángulo redondeado angosto, como una etiqueta de producto
            var h = s * 0.62;
            var r = Math.min(6, h / 2);
            ctx.beginPath();
            if (ctx.roundRect) {
                ctx.roundRect(-s / 2, -h / 2, s, h, r);
            } else {
                ctx.rect(-s / 2, -h / 2, s, h);
            }
            ctx.stroke();
        }

        ctx.restore();
    }

    /** Traza una línea entre cada par de formas cuya distancia es menor al umbral. */
    function drawConnections() {
        for (var i = 0; i < particles.length; i++) {
            for (var j = i + 1; j < particles.length; j++) {
                var a = particles[i];
                var b = particles[j];
                var dx = a.x - b.x;
                var dy = a.y - b.y;
                var distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < MAX_LINK_DISTANCE) {
                    // Más cerca => línea más opaca; en el límite, casi invisible.
                    var strength = 1 - distance / MAX_LINK_DISTANCE;
                    ctx.strokeStyle = 'rgba(' + COLOR_LINE_RGB + ', ' + (strength * LINE_MAX_OPACITY) + ')';
                    ctx.lineWidth = LINE_WIDTH;
                    ctx.beginPath();
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.stroke();
                }
            }
        }
    }

    function advance() {
        particles.forEach(function (p) {
            p.x += p.vx;
            p.y += p.vy;
            p.rotation += p.rotationSpeed;

            // Rebote suave en los bordes (en vez de teletransportar al otro
            // lado): mantiene el movimiento predecible y sin saltos.
            if (p.x < 0 || p.x > width) {
                p.vx *= -1;
            }
            if (p.y < 0 || p.y > height) {
                p.vy *= -1;
            }
        });
    }

    function render() {
        ctx.clearRect(0, 0, width, height);
        drawConnections();
        particles.forEach(drawParticle);
    }

    function frame() {
        advance();
        render();
        rafId = window.requestAnimationFrame(frame);
    }

    resizeCanvas();
    createParticles();
    render();

    // Con prefers-reduced-motion se deja un solo frame estático (la red
    // se ve, pero no se mueve) en vez de cancelar el fondo por completo.
    if (!reducedMotion) {
        rafId = window.requestAnimationFrame(frame);
    }

    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(function () {
            resizeCanvas();
            createParticles();
            render();
        }, 200);
    });

    // Si el usuario cambia de pestaña, se detiene el loop: no tiene sentido
    // seguir calculando algo que no se está viendo.
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            if (rafId) {
                window.cancelAnimationFrame(rafId);
                rafId = null;
            }
        } else if (!reducedMotion && !rafId) {
            rafId = window.requestAnimationFrame(frame);
        }
    });
}

/* --------------------------------------------------------------------
 * 2. Mostrar/ocultar contraseña
 *
 * Alterna el atributo type entre "password" y "text" en el input
 * correspondiente y sincroniza el ícono (ojo / ojo tachado) y los
 * atributos ARIA del botón. Funciona para cualquier cantidad de campos
 * de contraseña en la página (login tiene 1, registro tiene 2).
 * ------------------------------------------------------------------ */
function initPasswordToggle() {
    var toggles = document.querySelectorAll('.auth-form__toggle-password');

    toggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var field = toggle.closest('.form-field');
            var input = field ? field.querySelector('.form-input') : null;

            if (!input) {
                return;
            }

            var willShowPassword = input.type === 'password';

            input.type = willShowPassword ? 'text' : 'password';
            toggle.classList.toggle('is-visible', willShowPassword);
            toggle.setAttribute('aria-pressed', String(willShowPassword));
            toggle.setAttribute(
                'aria-label',
                willShowPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'
            );
        });
    });
}
