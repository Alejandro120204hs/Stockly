<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stockly — Control de inventario para cualquier negocio</title>
    <meta name="description" content="Stockly organiza tu inventario por categorías, detecta entradas y salidas en tiempo real, calcula tus ganancias reales y gestiona tus facturas electrónicas. Un sistema, cualquier rubro.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|work-sans:400,500,600,700" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/preloader.css') }}">
</head>
<body>

    <!-- ==========================================================
         PRELOADER: revelación del nombre "Stockly" letra por letra
         + línea de progreso
         ========================================================== -->
    <div class="preloader" id="preloader" aria-hidden="true">
        <div class="preloader__logo">
            <span class="preloader__letter">S</span>
            <span class="preloader__letter">t</span>
            <span class="preloader__letter">o</span>
            <span class="preloader__letter">c</span>
            <span class="preloader__letter">k</span>
            <span class="preloader__letter">l</span>
            <span class="preloader__letter">y</span>
        </div>

        <div class="preloader__line-wrap">
            <div class="preloader__line-track">
                <div class="preloader__line-fill" id="preloaderLineFill"></div>
            </div>
            <span class="preloader__percent" id="preloaderPercent">0%</span>
        </div>

        <p class="preloader__label">Cargando...</p>
    </div>

    <!-- ==========================================================
         SECCIÓN 1: NAVBAR + HERO
         ========================================================== -->
    <header class="navbar" id="navbar">
        <div class="container navbar__inner">
            <a href="{{ url('/') }}" class="navbar__brand">
                <svg class="navbar__mark" viewBox="0 0 32 32" fill="none">
                    <path d="M16 3 27 9v14L16 29 5 23V9Z" stroke="#4A7C6F" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M5 9 16 15 27 9M16 15v14" stroke="#C9B99A" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
                Stockly
            </a>

            <nav class="navbar__links" id="navbarLinks">
                <a class="navbar__link" href="#hero">Inicio</a>
                <a class="navbar__link" href="#caracteristicas">Características</a>
                <a class="navbar__link" href="#como-funciona">Cómo funciona</a>
                <a class="navbar__link" href="#contacto">Contacto</a>
            </nav>

            <div class="navbar__actions">
                <a class="navbar__login" href="{{ route('login') }}">Iniciar sesión</a>
                <a class="button button--primary" href="{{ route('register') }}">Comenzar gratis</a>

                <button class="navbar__toggle" type="button" aria-label="Abrir menú" aria-expanded="false">
                    <span class="navbar__toggle-bar"></span>
                    <span class="navbar__toggle-bar"></span>
                    <span class="navbar__toggle-bar"></span>
                </button>
            </div>
        </div>
    </header>

    <section class="hero" id="hero">
        <div class="container hero__grid">

            <div class="hero__content">
                <p class="eyebrow">Inventario inteligente, sin importar el rubro</p>

                <h1 class="hero__title">
                    Todo tu inventario, tus ventas <em>y tus facturas</em>, en un solo lugar.
                </h1>

                <p class="hero__subtitle">
                    Stockly organiza tu stock por categorías, detecta entradas y salidas de forma automática,
                    calcula tus ganancias reales y gestiona tus facturas electrónicas. Un mismo sistema para
                    cualquier tipo de negocio.
                </p>

                <div class="hero__actions">
                    <a class="button button--primary" href="{{ route('register') }}">Comenzar gratis</a>
                    <a class="button button--ghost" href="#como-funciona">Ver cómo funciona</a>
                </div>

                <div class="hero__tags">
                    <span class="hero__tag">Licoreras</span>
                    <span class="hero__tag">Tiendas de ropa</span>
                    <span class="hero__tag">Comestibles</span>
                    <span class="hero__tag">Y cualquier otro rubro</span>
                </div>
            </div>

            <!-- Escenario 3D: cajas de inventario genéricas con tilt por mouse -->
            <div class="hero-stage" id="heroStage">
                <div class="hero-stage__floor"></div>

                <div class="hero-box"
                     style="--size:130px; --pos-top:6%; --pos-left:6%; --base-rx:-18deg; --base-ry:24deg; --float-duration:7.5s; --float-delay:0s;"
                     data-depth="0.4" data-base-rx="-18" data-base-ry="24">
                    <div class="hero-box__parallax">
                        <div class="hero-box__inner">
                            <div class="hero-box__face hero-box__face--front"><span class="hero-box__band"></span></div>
                            <div class="hero-box__face hero-box__face--back"></div>
                            <div class="hero-box__face hero-box__face--right"></div>
                            <div class="hero-box__face hero-box__face--left"></div>
                            <div class="hero-box__face hero-box__face--top"></div>
                            <div class="hero-box__face hero-box__face--bottom"></div>
                        </div>
                    </div>
                </div>

                <div class="hero-box"
                     style="--size:96px; --pos-top:2%; --pos-left:58%; --base-rx:14deg; --base-ry:-26deg; --float-duration:6s; --float-delay:0.6s;"
                     data-depth="0.95" data-base-rx="14" data-base-ry="-26">
                    <div class="hero-box__parallax">
                        <div class="hero-box__inner">
                            <div class="hero-box__face hero-box__face--front"><span class="hero-box__band"></span></div>
                            <div class="hero-box__face hero-box__face--back"></div>
                            <div class="hero-box__face hero-box__face--right"></div>
                            <div class="hero-box__face hero-box__face--left"></div>
                            <div class="hero-box__face hero-box__face--top"></div>
                            <div class="hero-box__face hero-box__face--bottom"></div>
                        </div>
                    </div>
                </div>

                <div class="hero-box"
                     style="--size:64px; --pos-top:42%; --pos-left:2%; --base-rx:-10deg; --base-ry:-16deg; --float-duration:5s; --float-delay:1.1s;"
                     data-depth="1.3" data-base-rx="-10" data-base-ry="-16">
                    <div class="hero-box__parallax">
                        <div class="hero-box__inner">
                            <div class="hero-box__face hero-box__face--front"><span class="hero-box__band"></span></div>
                            <div class="hero-box__face hero-box__face--back"></div>
                            <div class="hero-box__face hero-box__face--right"></div>
                            <div class="hero-box__face hero-box__face--left"></div>
                            <div class="hero-box__face hero-box__face--top"></div>
                            <div class="hero-box__face hero-box__face--bottom"></div>
                        </div>
                    </div>
                </div>

                <div class="hero-box"
                     style="--size:150px; --pos-top:52%; --pos-left:56%; --base-rx:12deg; --base-ry:30deg; --float-duration:8.5s; --float-delay:0.3s;"
                     data-depth="0.55" data-base-rx="12" data-base-ry="30">
                    <div class="hero-box__parallax">
                        <div class="hero-box__inner">
                            <div class="hero-box__face hero-box__face--front"><span class="hero-box__band"></span></div>
                            <div class="hero-box__face hero-box__face--back"></div>
                            <div class="hero-box__face hero-box__face--right"></div>
                            <div class="hero-box__face hero-box__face--left"></div>
                            <div class="hero-box__face hero-box__face--top"></div>
                            <div class="hero-box__face hero-box__face--bottom"></div>
                        </div>
                    </div>
                </div>

                <div class="hero-box"
                     style="--size:56px; --pos-top:74%; --pos-left:28%; --base-rx:-20deg; --base-ry:12deg; --float-duration:5.5s; --float-delay:1.7s;"
                     data-depth="1.5" data-base-rx="-20" data-base-ry="12">
                    <div class="hero-box__parallax">
                        <div class="hero-box__inner">
                            <div class="hero-box__face hero-box__face--front"><span class="hero-box__band"></span></div>
                            <div class="hero-box__face hero-box__face--back"></div>
                            <div class="hero-box__face hero-box__face--right"></div>
                            <div class="hero-box__face hero-box__face--left"></div>
                            <div class="hero-box__face hero-box__face--top"></div>
                            <div class="hero-box__face hero-box__face--bottom"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <main>
        <!-- ==========================================================
             SECCIÓN 2: CARACTERÍSTICAS PRINCIPALES
             ========================================================== -->
        <section class="features" id="caracteristicas">
            <div class="container">
                <div class="section-heading reveal">
                    <p class="eyebrow">Características</p>
                    <h2 class="section-heading__title">Cuatro funciones, un solo panel de control</h2>
                    <p class="section-heading__text">
                        No son módulos sueltos: trabajan juntos para que sepas en todo momento qué tienes,
                        qué se mueve y cuánto estás ganando realmente.
                    </p>
                </div>

                <div class="feature-grid">
                    <article class="feature-card feature-card--wide feature-card--dark reveal">
                        <div>
                            <div class="feature-card__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 7 12 3l8 4-8 4-8-4Z"/>
                                    <path d="M4 7v10l8 4 8-4V7"/>
                                    <path d="M12 11v10"/>
                                </svg>
                            </div>
                            <h3 class="feature-card__title">Inventario por categorías</h3>
                            <p class="feature-card__text">
                                Organiza tu stock como tiene sentido para tu negocio: aguardiente, whisky y ron
                                en una licorera; camisas, pantalones y calzado en una tienda de ropa. Tú defines
                                las categorías.
                            </p>
                        </div>
                    </article>

                    <article class="feature-card feature-card--narrow reveal">
                        <div>
                            <div class="feature-card__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 3v11M7 14 3.5 10.5M7 14l3.5-3.5"/>
                                    <path d="M17 21V10M17 10l3.5 3.5M17 10l-3.5 3.5"/>
                                </svg>
                            </div>
                            <h3 class="feature-card__title">Entradas y salidas automáticas</h3>
                            <p class="feature-card__text">
                                Cada movimiento de stock se registra solo, en tiempo real, sin planillas manuales.
                            </p>
                        </div>
                    </article>

                    <article class="feature-card feature-card--narrow reveal">
                        <div>
                            <div class="feature-card__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 20h18"/>
                                    <path d="M6 20V12M12 20V6M18 20v-9"/>
                                    <path d="M15 4h4v4"/>
                                    <path d="M19 4 12 11l-3-3-5 5"/>
                                </svg>
                            </div>
                            <h3 class="feature-card__title">Ventas y ganancias reales</h3>
                            <p class="feature-card__text">
                                Calcula la ganancia neta de cada venta, no solo el ingreso bruto. Sabes qué
                                productos realmente te dejan margen.
                            </p>
                        </div>
                    </article>

                    <article class="feature-card feature-card--wide reveal">
                        <div>
                            <div class="feature-card__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 2h9l3 3v17H6Z"/>
                                    <path d="M15 2v3h3"/>
                                    <path d="m9.5 13 2 2 3.5-4"/>
                                </svg>
                            </div>
                            <h3 class="feature-card__title">Facturación electrónica</h3>
                            <p class="feature-card__text">
                                Verifica las facturas electrónicas que recibes de tus proveedores y emite las
                                tuyas propias sin salir de Stockly.
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- ==========================================================
             SECCIÓN 3: CÓMO FUNCIONA / CASOS DE USO
             ========================================================== -->
        <section class="how-it-works" id="como-funciona">
            <div class="container">
                <div class="section-heading section-heading--center reveal">
                    <p class="eyebrow">Cómo funciona</p>
                    <h2 class="section-heading__title">Tres pasos. Cualquier rubro.</h2>
                    <p class="section-heading__text">
                        Stockly se adapta a la forma en que ya organizas tu negocio, no al revés.
                    </p>
                </div>

                <div class="process-steps">
                    <div class="process-step reveal">
                        <span class="process-step__index">01</span>
                        <h3 class="process-step__title">Organiza por categorías</h3>
                        <p class="process-step__text">
                            Crea las categorías que tienen sentido para tu inventario y carga tus productos.
                        </p>
                    </div>
                    <div class="process-step reveal">
                        <span class="process-step__index">02</span>
                        <h3 class="process-step__title">Stockly detecta los movimientos</h3>
                        <p class="process-step__text">
                            Cada entrada y salida se registra automáticamente, sin doble digitación.
                        </p>
                    </div>
                    <div class="process-step reveal">
                        <span class="process-step__index">03</span>
                        <h3 class="process-step__title">Ves ventas, ganancias y facturas</h3>
                        <p class="process-step__text">
                            Un panel con tus números reales y tus facturas electrónicas, siempre al día.
                        </p>
                    </div>
                </div>

                <div class="use-cases__header reveal">
                    <div class="section-heading">
                        <p class="eyebrow">Casos de uso</p>
                        <h2 class="section-heading__title">El mismo sistema, adaptado a tu rubro</h2>
                    </div>
                </div>

                <div class="use-case-track">
                    <article class="use-case-card reveal">
                        <span class="use-case-card__tag">Licorera</span>
                        <h3 class="use-case-card__title">Aguardiente, whisky y ron, cada uno con su margen</h3>
                        <p class="use-case-card__text">
                            Separa el inventario por tipo de licor y detecta al instante qué categoría rota más
                            rápido y cuál deja más ganancia.
                        </p>
                        <div class="use-case-card__categories">
                            <span class="category-chip">Aguardiente</span>
                            <span class="category-chip">Whisky</span>
                            <span class="category-chip">Ron</span>
                            <span class="category-chip">Vino</span>
                        </div>
                    </article>

                    <article class="use-case-card reveal">
                        <span class="use-case-card__tag">Tienda de ropa</span>
                        <h3 class="use-case-card__title">De la percha al punto de venta, sin perder el conteo</h3>
                        <p class="use-case-card__text">
                            Organiza por tipo de prenda y talla, y controla las salidas por venta en tiempo real.
                        </p>
                        <div class="use-case-card__categories">
                            <span class="category-chip">Camisas</span>
                            <span class="category-chip">Pantalones</span>
                            <span class="category-chip">Calzado</span>
                            <span class="category-chip">Accesorios</span>
                        </div>
                    </article>

                    <article class="use-case-card reveal">
                        <span class="use-case-card__tag">Comestibles</span>
                        <h3 class="use-case-card__title">Rotación diaria, controlada categoría por categoría</h3>
                        <p class="use-case-card__text">
                            Ideal para productos de alta rotación: sabes qué se está agotando antes de que
                            falte en el estante.
                        </p>
                        <div class="use-case-card__categories">
                            <span class="category-chip">Lácteos</span>
                            <span class="category-chip">Enlatados</span>
                            <span class="category-chip">Bebidas</span>
                            <span class="category-chip">Aseo</span>
                        </div>
                    </article>

                    <article class="use-case-card reveal">
                        <span class="use-case-card__tag">Cualquier negocio</span>
                        <h3 class="use-case-card__title">Ferretería, farmacia, papelería... tú decides</h3>
                        <p class="use-case-card__text">
                            Stockly es un sistema global: define tus propias categorías y adáptalo a cómo
                            funciona tu negocio.
                        </p>
                        <div class="use-case-card__categories">
                            <span class="category-chip">Tus categorías</span>
                            <span class="category-chip">Tus productos</span>
                            <span class="category-chip">Tus reglas</span>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <!-- ==========================================================
         SECCIÓN 4: FOOTER + CONTACTO
         ========================================================== -->
    <footer class="footer" id="contacto">
        <div class="container footer__grid">

            <div class="footer__about">
                <div class="footer__brand">
                    <svg class="footer__mark" viewBox="0 0 32 32" fill="none">
                        <path d="M16 3 27 9v14L16 29 5 23V9Z" stroke="#C9B99A" stroke-width="1.6" stroke-linejoin="round"/>
                        <path d="M5 9 16 15 27 9M16 15v14" stroke="#4A7C6F" stroke-width="1.6" stroke-linejoin="round"/>
                    </svg>
                    Stockly
                </div>
                <p class="footer__lead">
                    Sistema global de administración e inventario para empresas de cualquier rubro:
                    licoreras, tiendas de ropa, comestibles y más.
                </p>

                <div class="footer__columns">
                    <div>
                        <p class="footer__col-title">Producto</p>
                        <div class="footer__link-list">
                            <a class="footer__link" href="#caracteristicas">Características</a>
                            <a class="footer__link" href="#como-funciona">Cómo funciona</a>
                            <a class="footer__link" href="{{ route('login') }}">Iniciar sesión</a>
                        </div>
                    </div>
                    <div>
                        <p class="footer__col-title">Legal</p>
                        <div class="footer__link-list">
                            <a class="footer__link" href="#">Privacidad</a>
                            <a class="footer__link" href="#">Términos</a>
                        </div>
                    </div>
                </div>

                <div class="footer__social">
                    <a class="footer__social-link" href="#" aria-label="Correo electrónico">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="m3 7 9 6 9-6"/>
                        </svg>
                    </a>
                    <a class="footer__social-link" href="#" aria-label="LinkedIn">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="3"/>
                            <path d="M8 10v6M8 7.5v.01M12 16v-3.5c0-1.2.9-2.1 2-2.1s2 .9 2 2.1V16"/>
                        </svg>
                    </a>
                    <a class="footer__social-link" href="#" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="5"/>
                            <circle cx="12" cy="12" r="3.5"/>
                            <circle cx="17" cy="7" r="0.8" fill="#C9B99A" stroke="none"/>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="contact-form reveal">
                <h3 class="contact-form__title">Contactanos</h3>
                <p class="contact-form__subtitle">
                    Cuéntanos sobre tu negocio y te ayudamos a poner Stockly en marcha.
                </p>

                <form method="POST" action="#" novalidate>
                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-label" for="contactName">Nombre</label>
                            <input class="form-input" type="text" id="contactName" name="name" placeholder="Tu nombre" required>
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="contactEmail">Correo</label>
                            <input class="form-input" type="email" id="contactEmail" name="email" placeholder="tucorreo@empresa.com" required>
                        </div>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="contactMessage">Mensaje</label>
                        <textarea class="form-textarea" id="contactMessage" name="message" placeholder="Cuéntanos sobre tu negocio y tu inventario" required></textarea>
                    </div>

                    <button class="button button--primary button--block" type="submit">Enviar mensaje</button>

                    <p class="form-status" role="status"></p>
                </form>
            </div>

        </div>

        <div class="container footer__bottom">
            <p>&copy; {{ date('Y') }} Stockly. Todos los derechos reservados.</p>
            <div class="footer__bottom-links">
                <a href="#">Privacidad</a>
                <a href="#">Términos</a>
            </div>
        </div>
    </footer>

    <script src="{{ asset('assets/js/preloader.js') }}" defer></script>
    <script src="{{ asset('assets/js/landing.js') }}" defer></script>
</body>
</html>
