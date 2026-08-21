/**
 * Stockly — Preloader (pantalla de carga inicial)
 *
 * Revela el nombre "Stockly" letra por letra EN SINCRONÍA con la línea de
 * progreso: cada letra tiene un porcentaje de "aparición" (LETTER_THRESHOLDS)
 * y se marca visible en el mismo requestAnimationFrame que actualiza el
 * ancho de la línea, para que texto y barra avancen juntos en vez de ser
 * dos animaciones independientes. La línea avanza de 0% a 100% en ~3
 * segundos; al llegar a 100% (y una vez que la página cargó), el preloader
 * se desvanece y revela la landing. Se ejecuta antes que landing.js y no
 * depende de él.
 *
 * Reglas de negocio clave:
 *   - Se muestra SIEMPRE, en cada carga de página (sin sessionStorage ni
 *     cookies): no hay lógica para saltárselo.
 *   - Nunca corta la línea a la mitad: el fade-out solo ocurre cuando la
 *     línea ya llegó a 100% Y la página ya cargó (lo que pase después).
 *   - Mientras está activo, el contenido real de la página queda con
 *     `inert` (no solo tapado visualmente): un usuario de teclado no puede
 *     enfocar links u otros controles que están escondidos detrás.
 */

(function () {
    var DURATION = 3000; // ms que tarda la línea en llegar a 100%

    // Porcentaje de la línea en el que se revela cada letra de "Stockly"
    // (mismo orden que los .preloader__letter en el HTML). Deben ser
    // tantos valores como letras.
    var LETTER_THRESHOLDS = [5, 20, 35, 50, 65, 80, 90];

    var TIMING = {
        fadeOutDuration: 550, // debe coincidir con la transición de .preloader en preloader.css
        safetyTimeout: 9000   // red de seguridad si 'load' nunca llega a disparar
    };

    var preloader = document.getElementById('preloader');
    if (!preloader) {
        return;
    }

    var lineFill = preloader.querySelector('.preloader__line-fill');
    var percentLabel = preloader.querySelector('.preloader__percent');
    var letters = Array.prototype.slice.call(preloader.querySelectorAll('.preloader__letter'));

    // Todo el contenido real de la página vive junto al preloader en <body>;
    // se vuelve "inert" mientras el preloader está activo para que el
    // scroll bloqueado (is-preloading) tenga su equivalente en teclado:
    // nada detrás del overlay debe ser enfocable hasta que se revele.
    var contentElements = Array.prototype.filter.call(document.body.children, function (el) {
        return el !== preloader;
    });

    setContentInert(true);
    document.body.classList.add('is-preloading');

    var lineComplete = false;
    // Si el documento ya terminó de cargar antes de que este script corra
    // (posible con <script defer>), 'load' ya se disparó y nunca lo veríamos:
    // se detecta ese caso directamente por readyState en vez de esperar el evento.
    var pageLoaded = document.readyState === 'complete';

    runProgressLine();
    waitForPageLoad();

    // Red de seguridad general: si algo impide que 'load' o la línea
    // completen, el preloader no debe quedar bloqueando la página para siempre.
    window.setTimeout(function () {
        lineComplete = true;
        pageLoaded = true;
        tryFinish();
    }, TIMING.safetyTimeout);

    /* -------------------------------------------------------------- */

    function setContentInert(isInert) {
        contentElements.forEach(function (el) {
            if ('inert' in el) {
                el.inert = isInert;
            } else if (isInert) {
                el.setAttribute('inert', '');
            } else {
                el.removeAttribute('inert');
            }
        });
    }

    /**
     * Avanza la línea con requestAnimationFrame en vez de una transición
     * CSS de duración fija, para que el fade-out final pueda esperar de
     * forma confiable a que realmente llegó a 100%.
     */
    function runProgressLine() {
        var start = null;

        function frame(timestamp) {
            if (start === null) {
                start = timestamp;
            }

            var elapsed = timestamp - start;
            var percent = Math.min(100, (elapsed / DURATION) * 100);

            if (lineFill) {
                lineFill.style.width = percent + '%';
            }
            if (percentLabel) {
                percentLabel.textContent = Math.round(percent) + '%';
            }
            updateLetters(percent);

            if (percent < 100) {
                window.requestAnimationFrame(frame);
            } else {
                lineComplete = true;
                tryFinish();
            }
        }

        window.requestAnimationFrame(frame);
    }

    function updateLetters(percent) {
        letters.forEach(function (letter, index) {
            var threshold = LETTER_THRESHOLDS[index];
            if (threshold !== undefined && percent >= threshold) {
                letter.classList.add('is-visible');
            }
        });
    }

    function waitForPageLoad() {
        if (pageLoaded) {
            tryFinish();
            return;
        }

        window.addEventListener('load', function () {
            pageLoaded = true;
            tryFinish();
        });
    }

    /**
     * Solo desvanece el preloader cuando AMBAS condiciones se cumplieron:
     * la línea llegó a 100% y la página ya cargó. El orden en que se
     * cumplen no importa.
     */
    function tryFinish() {
        if (lineComplete && pageLoaded) {
            fadeOutAndRemove();
        }
    }

    function fadeOutAndRemove() {
        preloader.classList.add('is-hidden');
        document.body.classList.remove('is-preloading');
        setContentInert(false);

        window.setTimeout(removePreloader, TIMING.fadeOutDuration);
    }

    function removePreloader() {
        if (preloader && preloader.parentNode) {
            preloader.parentNode.removeChild(preloader);
        }
        document.body.classList.remove('is-preloading');
        setContentInert(false);
    }
})();
