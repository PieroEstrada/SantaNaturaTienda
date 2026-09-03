<?php
/* ============================================================================
   Portada — catálogo completo
   ----------------------------------------------------------------------------
   El <head>, la cabecera, el carrito, la ficha de producto, el pie y el botón
   flotante son los mismos de todo el sitio y viven en inc/partes/. Aquí queda
   solo lo propio de esta página.
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/inc/render.php';
require __DIR__ . '/inc/popup.php';
require __DIR__ . '/inc/partes/cabeza.php';
require __DIR__ . '/inc/partes/comunes.php';

sn_cabeza([
    'titulo'      => "Santa Natura — Catálogo Oficial y Pedidos por WhatsApp",
    'descripcion' => "Catálogo oficial Santa Natura. Pide por WhatsApp de 8:00 a 23:00. Envíos y recojo en tienda a nivel nacional: Lima, Ayacucho, Tarapoto, Huánuco y más.",
    'canonical'   => "https://santanatura.inmuno.lat/",
    'clave'       => "home",
    'raiz'        => '',
    'iconos'      => ["account_tree","add","celebration","check_circle","chevron_left","chevron_right","diamond","factory","flag","flight_takeoff","grid_view","group","groups","handshake","history","inventory_2","local_florist","local_mall","military_tech","person","redeem","remove","search_off","send","star","support_agent","trending_up","tune","verified_user","workspace_premium"],
    'extra'       => <<<'HTML'

<style id="hero-ajustes">
    /* Marco de la foto: proporcion fija, asi nunca queda ni aplastada ni gigante. */
    #inicio .hero-media {
        position: relative;
        display: block;
        width: 100%;
        height: auto;
        min-height: 0;
        padding: 0;
        aspect-ratio: 4 / 3;          /* movil: mas alta, se aprecia mejor */
        border-radius: 1.25rem;
        overflow: hidden;
        background: rgb(var(--c-surface-container, 240 244 240) / 1);
    }
    @media (min-width: 768px) {
        #inicio .hero-media { aspect-ratio: 5 / 4; }   /* escritorio */
    }

    /* La foto llena el marco sin deformarse. */
    #inicio #hero-imagen {
        position: absolute;
        inset: 0;
        z-index: 1;
        width: 100%;
        height: 100%;
        max-width: none;
        object-fit: cover;
        object-position: 50% 42%;     /* sube el encuadre: se ven caras y productos */
        display: block;
        transform: scale(1);
        transition: transform .6s cubic-bezier(.22,.61,.36,1);
    }
    #inicio .hero-marco:hover #hero-imagen { transform: scale(1.045); }

    /* Icono de respaldo mientras carga (o si la foto falla). */
    #inicio .hero-media__respaldo {
        position: absolute;
        inset: 0;
        z-index: 0;
        display: grid;
        place-items: center;
        font-size: 4.5rem;
        opacity: .35;
    }

    /* Velo suave abajo-izquierda: da contraste a la tarjeta y evita que la
       foto "compita" con el texto. */
    #inicio .hero-media::after {
        content: "";
        position: absolute;
        inset: 0;
        z-index: 2;
        pointer-events: none;
        background: linear-gradient(to top right, rgba(0,0,0,.22), rgba(0,0,0,0) 55%);
        animation: none;
        opacity: 1;
    }

    /* LA CLAVE: la tarjeta de productos SIEMPRE por encima de la foto. */
    #inicio .hero-marco { z-index: 1; }
    #inicio #hero-contador { z-index: 30; }
</style>

HTML,
]);
?>
<body class="bg-background text-on-background font-body-md overflow-x-hidden">

<!-- ==========================================================================
     Iconos SVG reutilizables
     --------------------------------------------------------------------------
     Se declaran una sola vez y se insertan donde haga falta con:
        <svg class="w-6 h-6"><use href="#ico-whatsapp"></use></svg>
     El icono hereda el color del texto (fill="currentColor"), así que funciona
     igual sobre el verde de WhatsApp que sobre cualquier otro fondo.
     ========================================================================== -->
<?php sn_simbolo_whatsapp(); ?>

<!-- ==========================================================================
     Popup de bienvenida — precios especiales por cantidad
     ========================================================================== -->
<!-- <div class="fixed inset-0 z-[200] flex items-center justify-center p-md bg-black/60 opacity-0 pointer-events-none transition-opacity duration-500" id="popup-oferta" onclick="if (event.target === this) cerrarPopup()">
<div class="bg-surface rounded-3xl overflow-hidden max-w-lg w-full shadow-2xl relative">
<button aria-label="Cerrar" class="absolute top-4 right-4 text-on-surface-variant hover:text-on-surface z-10" onclick="cerrarPopup()">
<span class="material-symbols-outlined">close</span>
</button>
<div class="img-placeholder h-40 grid place-items-center bg-surface-container">
<span class="material-symbols-outlined text-7xl text-primary/50" style="font-variation-settings: 'FILL' 1;">local_florist</span>
</div>
<div class="p-lg text-center space-y-md">
<div class="inline-block bg-tertiary-fixed text-on-tertiary-fixed px-sm py-1 rounded-full font-label-md text-xs">PRECIOS ESPECIALES 🌿</div>
<h2 class="font-headline-md text-headline-md-mobile text-primary">¿Llevas mayor cantidad?</h2>
<p class="font-body-md text-on-surface-variant" data-nota-cantidad>Solicita tu precio especial con descuento al enviar tu pedido por WhatsApp.</p>
<a class="flex items-center justify-center gap-xs w-full bg-[#25D366] text-white py-md rounded-xl font-title-lg hover:brightness-105 transition-all shadow-lg pulse-whatsapp"
   data-wa="Hola, quiero consultar por el precio especial por cantidad de los productos Santa Natura." href="#" target="_blank" rel="noopener">
<svg class="w-6 h-6 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
                Consultar por WhatsApp
            </a>
</div>
</div>
</div> -->

<!-- ==========================================================================
     Popup de bienvenida — Rediseñado
     ========================================================================== -->
<div class="fixed inset-0 z-[200] flex items-center justify-center p-4 sm:p-md bg-black/70 backdrop-blur-md opacity-0 pointer-events-none transition-all duration-300" id="popup-oferta" onclick="if (event.target === this) cerrarPopup()">
  <div class="bg-surface rounded-3xl overflow-hidden max-w-md w-full shadow-2xl relative border border-outline-variant/40 transform scale-95 transition-transform duration-300">
    
    <!-- Botón Cerrar -->
    <button aria-label="Cerrar" class="absolute top-4 right-4 text-on-surface-variant hover:text-on-surface z-10 bg-surface/80 hover:bg-surface rounded-full p-2 backdrop-blur-sm transition-all" onclick="cerrarPopup()">
      <span class="material-symbols-outlined text-lg">close</span>
    </button>

    <!-- Encabezado con degradado suave -->
    <div class="relative bg-gradient-to-br from-primary/20 via-primary/5 to-transparent p-8 text-center flex flex-col items-center justify-center border-b border-outline-variant/30">
      <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-3 shadow-inner">
        <span class="material-symbols-outlined text-4xl text-primary" style="font-variation-settings: 'FILL' 1;">local_florist</span>
      </div>
      <span class="inline-flex items-center gap-1.5 bg-primary/10 text-primary px-3 py-1 rounded-full font-label-caps text-xs tracking-wider uppercase">
        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span> Precios Especiales 🌿
      </span>
    </div>

    <!-- Cuerpo del Popup -->
    <div class="p-6 text-center space-y-4">
      <h2 class="font-headline-md text-2xl font-bold text-on-surface">¿Compras por cantidad?</h2>
      <p class="font-body-md text-sm text-on-surface-variant leading-relaxed" data-nota-cantidad>
        Obtén un descuento exclusivo y precios de distribuidor solicitando tu cotización directamente por WhatsApp.
      </p>

      <div class="pt-2">
        <a class="flex items-center justify-center gap-2 w-full bg-action-whatsapp text-white py-3.5 px-6 rounded-full font-title-sm text-base hover:brightness-105 transition-all shadow-lg shadow-action-whatsapp/25 hover:shadow-xl active:scale-[0.98]"
           data-wa="Hola, quiero consultar por el precio especial por cantidad de los productos Santa Natura." href="#" target="_blank" rel="noopener">
          <svg class="w-5 h-5 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
          Consultar por WhatsApp
        </a>
      </div>

      <button class="text-xs text-on-surface-variant hover:text-primary transition-colors font-label-caps" onclick="cerrarPopup()">
        Continuar viendo el catálogo
      </button>
    </div>

  </div>
</div>


<!-- ==========================================================================
     Ficha de producto
     ========================================================================== -->
<?php sn_modal_producto(); ?>
</div>

<!-- ==========================================================================
     Carrito / Resumen del pedido
     ========================================================================== -->
<?php sn_carrito(); ?>

<!-- ==========================================================================
     Barra promocional
     ========================================================================== -->
<?php sn_barra_promo("🌿 Catálogo oficial vigente · Envíos y recojo en tienda a nivel nacional"); ?>

<!-- ==========================================================================
     Header
     --------------------------------------------------------------------------
     Una sola fila alta (80px) con marca, botón de «Categorías», buscador y
     acciones. Las 19 categorías no caben en línea, así que todas viven en el
     mega menú, que se abre con el botón «Categorías» (y con el botón de menú
     en móvil). La fila de accesos rápidos (#nav-categorias) sigue existiendo
     oculta porque store.js la rellena; se puede volver a mostrar quitándole
     la clase «hidden» al contenedor.
     ========================================================================== -->
<?php sn_header('', true, '#inicio'); ?>

<!-- ==========================================================================
     Hero
     ========================================================================== -->
<section class="relative flex items-center py-lg md:py-xl overflow-hidden bg-surface-container-lowest" id="inicio">
<div class="max-w-container-max mx-auto px-md grid md:grid-cols-2 gap-lg md:gap-xl items-center relative z-10">
<div class="space-y-md">
<div class="inline-block bg-primary/10 text-primary px-4 py-1.5 rounded-full font-label-caps text-sm tracking-wider uppercase">Distribuidor Autorizado Oficial</div>
<h1 class="font-headline-md text-4xl md:text-5xl lg:text-6xl text-on-surface leading-tight font-bold">
                Catálogo Oficial <br/><span class="text-primary">Santa Natura</span>
</h1>
<p class="font-body-lg text-body-lg text-on-surface-variant max-w-xl leading-relaxed">
                Arma tu pedido con los precios vigentes, revisa tus puntos acumulados y envíanoslo por WhatsApp. Te confirmamos stock, precio final y envío al instante.
            </p>
<div class="flex flex-col sm:flex-row flex-wrap gap-sm md:gap-md pt-xs">
<a class="bg-primary text-on-primary px-lg py-md rounded-full font-title-sm flex items-center justify-center gap-xs shadow-lg hover:brightness-110 transition-all w-full sm:w-auto active:scale-[0.98]" href="#productos">
<span class="material-symbols-outlined">grid_view</span> Ver catálogo
</a>
<a class="pulse-whatsapp bg-action-whatsapp text-white px-lg py-md rounded-full font-title-sm flex items-center justify-center gap-xs shadow-lg hover:brightness-105 transition-all w-full sm:w-auto"
   data-wa="Hola, deseo recibir el catálogo y la lista de precios vigente de Santa Natura." href="#" target="_blank" rel="noopener">
<svg class="w-6 h-6 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
                    Pedir por WhatsApp
                </a>
</div>
</div>

<!-- ----------------------------------------------------------------------
     Columna derecha: imagen principal.

     PARA CAMBIAR LA FOTO: edita store.js → CONFIG.heroImagen (acepta una URL
     https://… o una ruta local como 'img/hero.jpg'). El src de abajo es solo
     el respaldo que se ve mientras carga el JavaScript.
     Si la imagen no existe, se muestra el fondo decorativo con la hoja.
     ---------------------------------------------------------------------- -->
<div class="relative order-last">
<div class="absolute -inset-6 bg-primary/10 blur-3xl rounded-full" aria-hidden="true"></div>
<figure class="hero-marco relative bg-surface p-2 md:p-3 rounded-3xl shadow-2xl border border-outline-variant/50 overflow-hidden">
<div class="hero-media img-placeholder">
<span class="material-symbols-outlined hero-media__respaldo" style="font-variation-settings: 'FILL' 1;">local_florist</span>
<img alt="Productos naturales Santa Natura" decoding="async" id="hero-imagen"
     width="1200" height="960" loading="eager" fetchpriority="high"
     onerror="this.style.display='none'"
     src="https://lh3.googleusercontent.com/aida-public/AB6AXuBqG9y0bjIcgMdE3FMn7UHAU_LGr2bVGAs7xwFENL-5IH5DC2cOZIkyKiFtagOBYwt0LvkEDQC5SlM4_ocueZob4EYRQDuU52bOSLWaNmsNkMhmlvI_MCfBEBBXPLV_B3yg9u1jduE73NscYHGLICStSb8_ptn_7E2ksWBNFUf96Kg6QwDClJwTYeJBD1_NyJi8XcCcYKX7n6EkhoTX4FfeHCOWQy57sNp0-v_XE0QReor8oXLWHqV_tA" />
</div>
</figure>

<!-- Contador de productos (se actualiza solo desde products.js) -->
<div class="absolute z-30 -bottom-5 left-2 md:-bottom-7 md:-left-7 bg-surface rounded-2xl shadow-2xl ring-1 ring-outline-variant/50 px-md py-sm flex items-center gap-sm whitespace-nowrap" id="hero-contador">
<span class="material-symbols-outlined text-3xl text-primary shrink-0" style="font-variation-settings: 'FILL' 1;">inventory_2</span>
<div class="leading-tight">
<p class="font-headline-md text-xl text-on-surface font-bold" id="hero-total">87</p>
<p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-wider">productos</p>
</div>
</div>
</div>
</div>
</section>

<!-- ==========================================================================
     Afiliación — resumen de los planes
     --------------------------------------------------------------------------
     Las tarjetas NO se escriben aquí: las pinta store.js desde la constante
     PLANES_AFILIACION, que es la misma que usa afiliacion.html. Así los
     precios nunca se desincronizan entre las dos páginas.
     La página completa (plan de compensación, bonos y formulario) está en
     afiliacion.html.
     ========================================================================== -->
<section class="py-xl bg-surface-container-low border-y border-outline-variant/30" id="afiliacion">
<div class="max-w-container-max mx-auto px-md">

<div class="text-center max-w-2xl mx-auto mb-lg space-y-sm">
<span class="inline-block bg-primary/10 text-primary px-4 py-1.5 rounded-full font-label-caps text-xs tracking-wider uppercase">Emprende con nosotros</span>
<h2 class="font-headline-md text-3xl md:text-4xl text-on-surface font-bold">Planes de afiliación</h2>
<p class="font-body-md text-base text-on-surface-variant">Compra con descuento de distribuidor desde tu primer pedido y accede al plan de compensación. Elige el paquete con el que quieres empezar.</p>
</div>

<!-- Tarjetas de planes (las llena store.js) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-sm md:gap-md items-stretch" id="planes-afiliacion"></div>

<div class="mt-lg bg-primary/5 border border-primary/20 rounded-3xl p-md md:p-lg flex flex-col md:flex-row items-center justify-between gap-md">
<div class="text-center md:text-left">
<h3 class="font-headline-md text-xl text-primary mb-1">¿Quieres conocer todas las formas de ganar?</h3>
<p class="font-body-md text-sm text-on-surface-variant">Descuentos por volumen, Bono Mercadeo, Bono Matricial 3x3 y regalías por generación.</p>
</div>
<a class="shrink-0 inline-flex items-center gap-2 bg-primary text-on-primary px-lg py-3 rounded-full font-title-sm text-base shadow-lg hover:brightness-110 transition-all active:scale-[0.98]" href="afiliacion.php">
                Ver el plan completo
                <span class="material-symbols-outlined">arrow_forward</span>
</a>
</div>

</div>
</section>

<!-- ==========================================================================
     Catálogo
     ========================================================================== -->
<section class="py-xl" id="productos">
<div class="max-w-container-max mx-auto px-md">

<!-- --------------------------------------------------------------------------
     Encabezado del catálogo: título, buscador a la vista y acceso a filtros
     --------------------------------------------------------------------------
     El buscador del header se pierde: está arriba del todo, es estrecho y,
     cuando alguien ya está mirando productos, ha quedado fuera de la pantalla.
     Aquí va otro ancho, con la lupa bien visible y pegado a la rejilla, que es
     donde se busca de verdad. Los tres escriben en el mismo filtro: store.js
     los mantiene sincronizados, así que da igual cuál se use.

     En móvil la columna de filtros (categorías + precio + nota) ocupaba una
     pantalla entera ANTES del primer producto: quien llegaba de un anuncio veía
     un formulario, no el catálogo. Ahora vive detrás del botón «Filtros» y se
     abre solo si se pide. En pantalla grande no cambia nada: la barra lateral
     sigue siempre desplegada.
     -------------------------------------------------------------------------- -->
<div class="mb-md space-y-sm">

<div class="flex flex-wrap justify-between items-end gap-sm">
<div>
<h2 class="font-headline-md text-3xl text-on-surface font-bold">Nuestro catálogo</h2>
<p class="font-body-md text-sm text-on-surface-variant mt-1" id="contador-resultados">0 productos</p>
</div>
<div class="flex flex-wrap items-center gap-sm">
<div class="flex items-center gap-xs">
<label class="font-label-caps text-xs text-on-surface-variant uppercase tracking-wider" for="por-pagina">Mostrar:</label>
<select class="bg-surface border-outline-variant/50 rounded-full py-1.5 px-4 text-sm focus:ring-primary focus:border-primary font-body-md" id="por-pagina">
<option value="12">12</option>
<option selected value="24">24</option>
<option value="48">48</option>
<option value="96">96</option>
</select>
</div>
<div class="flex items-center gap-xs">
<label class="font-label-caps text-xs text-on-surface-variant uppercase tracking-wider" for="orden">Ordenar:</label>
<select class="bg-surface border-outline-variant/50 rounded-full py-1.5 px-4 text-sm focus:ring-primary focus:border-primary font-body-md" id="orden">
<option value="popular">Recomendados</option>
<option value="precio-asc">Precio: de menor a mayor</option>
<option value="precio-desc">Precio: de mayor a menor</option>
<option value="puntos-asc">Puntos: de menor a mayor</option>
<option value="puntos-desc">Puntos: de mayor a menor</option>
</select>
</div>
</div>
</div>

<div class="flex items-center gap-sm">
<div class="relative flex-1 min-w-0">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary text-2xl pointer-events-none">search</span>
<input autocomplete="off" type="search" id="buscador-catalogo"
       class="w-full bg-surface border border-outline-variant rounded-full py-3 pl-12 pr-4 text-base placeholder:text-on-surface-variant/70 focus:border-primary focus:ring-1 focus:ring-primary transition-colors shadow-sm"
       placeholder="Buscar producto, pack o categoría…"
       role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="sugerencias-catalogo"/>
<ul class="hidden absolute left-0 right-0 top-full mt-2 z-[120] bg-surface border border-outline-variant rounded-2xl shadow-2xl overflow-hidden max-h-96 overflow-y-auto scroll-suave" id="sugerencias-catalogo" role="listbox"></ul>
</div>

<!-- Solo en móvil y tablet: en lg la barra lateral ya está a la vista. -->
<button type="button" id="btn-filtros" onclick="alternarFiltros()"
        class="lg:hidden shrink-0 inline-flex items-center gap-1.5 bg-surface border border-outline-variant rounded-full px-4 py-3 font-label-caps text-sm text-on-surface hover:border-primary hover:text-primary transition-colors shadow-sm"
        aria-expanded="false" aria-controls="panel-filtros">
<span class="material-symbols-outlined text-xl">tune</span>
<span class="hidden sm:inline">Filtros</span>
<span class="hidden bg-primary text-on-primary text-[10px] font-bold min-w-[18px] h-[18px] px-1 rounded-full leading-[18px] text-center" id="filtros-contador">0</span>
</button>
</div>
</div>

<div class="flex flex-col lg:flex-row gap-gutter">

<!-- Barra lateral -->
<!-- La barra lateral se queda fija al hacer scroll. Como junta varias tarjetas
     y puede pasar del alto de la pantalla, se le limita la altura y se le da
     scroll propio; si no, el filtro de precio queda fuera de alcance.
     `hidden lg:block`: plegada en móvil (la abre el botón «Filtros»), siempre
     visible a partir de lg. -->
<aside id="panel-filtros" class="hidden lg:block w-full lg:w-72 flex-shrink-0 space-y-md lg:sticky lg:top-28 lg:self-start lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto scroll-suave lg:pr-2">
<div class="bg-surface p-md rounded-3xl shadow-sm border border-outline-variant/50">
<h3 class="font-headline-md text-lg mb-sm text-on-surface">Categorías</h3>
<!-- Árbol completo (categorías y subcategorías). Se genera desde COLECCIONES
     en products.js; los conteos no suman el total porque un producto está en
     varias categorías a la vez. -->
<ul class="space-y-sm max-h-[17rem] overflow-y-auto scroll-suave pr-2 font-body-md" id="sidebar-categorias"></ul>
</div>

<!-- Filtrar por precio. Los topes (mín. y máx.) los calcula store.js con los
     precios reales del catálogo, así que no hay que tocar números aquí. -->
<div class="bg-surface p-md rounded-3xl shadow-sm border border-outline-variant/50">
<h3 class="font-headline-md text-lg mb-sm text-on-surface">Filtrar por precio</h3>

<div class="grid grid-cols-2 gap-sm">
<div>
<label class="block font-label-caps text-[10px] text-on-surface-variant mb-1 uppercase tracking-wider" for="precio-min">Mínimo</label>
<div class="relative">
<span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-outline pointer-events-none">S/</span>
<input class="w-full bg-surface-container-low border-transparent rounded-xl py-2 pl-8 pr-2 text-sm focus:ring-primary focus:border-primary transition-colors"
       id="precio-min" inputmode="numeric" min="0" step="1" type="number"/>
</div>
</div>
<div>
<label class="block font-label-caps text-[10px] text-on-surface-variant mb-1 uppercase tracking-wider" for="precio-max">Máximo</label>
<div class="relative">
<span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-outline pointer-events-none">S/</span>
<input class="w-full bg-surface-container-low border-transparent rounded-xl py-2 pl-8 pr-2 text-sm focus:ring-primary focus:border-primary transition-colors"
       id="precio-max" inputmode="numeric" min="0" step="1" type="number"/>
</div>
</div>
</div>

<!-- Barra de rango: mueve el tope máximo sin tener que escribir. -->
<input class="w-full mt-md accent-primary cursor-pointer" id="precio-slider" type="range"/>

<div class="flex items-center justify-between gap-xs mt-sm">
<p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-wider" id="precio-resumen"></p>
<button class="font-label-caps text-[10px] text-primary hover:underline uppercase tracking-wider hidden" id="precio-limpiar" onclick="limpiarFiltroPrecio()">Limpiar</button>
</div>
</div>

<div class="bg-primary/5 border border-primary/20 p-md rounded-3xl space-y-xs">
<span class="material-symbols-outlined text-primary">sell</span>
<h3 class="font-headline-md text-sm text-primary">Precios especiales</h3>
<p class="font-body-md text-xs text-on-surface-variant" data-nota-cantidad></p>
</div>

<!-- Cierre en móvil: tras elegir un filtro hay que poder volver a los productos
     sin buscar el botón de arriba ni hacer scroll a ciegas. -->
<button type="button" onclick="cerrarFiltros()"
        class="lg:hidden w-full bg-primary text-on-primary py-3 rounded-full font-title-sm shadow-md active:scale-[0.98] transition-transform">
Ver productos
</button>
</aside>

<!-- Grilla -->
<div class="flex-1">

<!-- La primera página del catálogo ya viene escrita desde el servidor, para que
     el robot de Google la lea sin ejecutar JavaScript. store.js la repinta al
     cargar, porque filtrar, buscar y paginar sí ocurren en el navegador. -->
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-sm md:gap-gutter" id="grid-productos"><?= sn_rejilla_portada(sn_catalogo()) ?></div>

<div class="hidden py-xl text-center space-y-sm" id="grid-vacio">
<span class="material-symbols-outlined text-5xl text-outline-variant">search_off</span>
<p class="font-body-md text-on-surface-variant">No encontramos productos con esos filtros.</p>
</div>

<!-- Paginación (la genera store.js; se oculta sola si todo cabe en una página) -->
<nav class="hidden flex-wrap items-center justify-center gap-2 mt-lg" id="paginacion" aria-label="Páginas del catálogo"></nav>
</div>
</div>
</div>
</section>

<!-- ==========================================================================
     Precios especiales por cantidad + puntos
     ========================================================================== -->
<section class="py-xl bg-surface-container" id="ofertas">
<div class="max-w-container-max mx-auto px-md">
<div class="bg-surface rounded-3xl border border-primary/20 shadow-xl p-lg md:p-xl grid md:grid-cols-2 gap-lg items-center relative overflow-hidden">
<!-- Mancha difuminada decorativa (no interfiere con el clic) -->
<div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/4 pointer-events-none" aria-hidden="true"></div>
<div class="space-y-md relative z-10">
<div class="inline-block bg-primary text-on-primary px-4 py-1.5 rounded-full font-label-caps text-xs tracking-wider uppercase">COMPRAS POR CANTIDAD</div>
<h2 class="font-headline-md text-3xl md:text-4xl text-primary font-bold">Mientras más llevas, mejor es tu precio</h2>
<p class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed" data-nota-cantidad></p>
<a class="pulse-whatsapp inline-flex bg-action-whatsapp text-white px-lg py-md rounded-full font-title-sm items-center gap-xs shadow-lg hover:brightness-105 transition-all mt-sm"
   data-wa="Hola, quiero cotizar una compra por cantidad y conocer mi precio especial con descuento." href="#" target="_blank" rel="noopener">
<svg class="w-6 h-6 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
                    Cotizar por cantidad
                </a>
</div>
<div class="grid gap-sm relative z-10">
<div class="flex gap-md items-start bg-surface-container-low rounded-3xl p-md hover:shadow-md transition-shadow">
<div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-2xl text-primary" style="font-variation-settings: 'FILL' 1;">shopping_cart</span>
</div>
<div>
<h3 class="font-headline-md text-lg text-on-surface mb-1">1. Arma tu pedido</h3>
<p class="font-body-md text-sm text-on-surface-variant">Agrega los productos que quieras al carrito. Verás el total y los puntos que acumulas.</p>
</div>
</div>
<div class="flex gap-md items-start bg-surface-container-low rounded-3xl p-md hover:shadow-md transition-shadow">
<div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-2xl text-primary" style="font-variation-settings: 'FILL' 1;">send</span>
</div>
<div>
<h3 class="font-headline-md text-lg text-on-surface mb-1">2. Envíalo por WhatsApp</h3>
<p class="font-body-md text-sm text-on-surface-variant">El resumen se envía automáticamente a tu asesor con el detalle completo.</p>
</div>
</div>
<div class="flex gap-md items-start bg-surface-container-low rounded-3xl p-md hover:shadow-md transition-shadow">
<div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-2xl text-primary" style="font-variation-settings: 'FILL' 1;">handshake</span>
</div>
<div>
<h3 class="font-headline-md text-lg text-on-surface mb-1">3. Cierra tu precio</h3>
<p class="font-body-md text-sm text-on-surface-variant">Confirmamos stock, tu precio especial por cantidad y coordinamos el envío.</p>
</div>
</div>
</div>
</div>
</div>
</section>

<!-- ==========================================================================
     Beneficios
     ========================================================================== -->
<section class="py-xl bg-surface-container-low" id="beneficios">
<div class="max-w-container-max mx-auto px-md">
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
<div class="flex flex-col items-center text-center gap-sm bg-surface p-lg rounded-3xl shadow-sm border border-outline-variant/30 hover:shadow-md transition-all group">
<div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-3xl text-primary">local_shipping</span>
</div>
<div>
<h3 class="font-headline-md text-lg mb-2">Envío Express</h3>
<p class="font-body-md text-sm text-on-surface-variant">Lima &amp; Provincias</p>
</div>
</div>
<div class="flex flex-col items-center text-center gap-sm bg-surface p-lg rounded-3xl shadow-sm border border-outline-variant/30 hover:shadow-md transition-all group">
<div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-3xl text-primary">verified</span>
</div>
<div>
<h3 class="font-headline-md text-lg mb-2">Garantía Total</h3>
<p class="font-body-md text-sm text-on-surface-variant">Productos 100% Originales</p>
</div>
</div>
<div class="flex flex-col items-center text-center gap-sm bg-surface p-lg rounded-3xl shadow-sm border border-outline-variant/30 hover:shadow-md transition-all group">
<div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-3xl text-primary">payments</span>
</div>
<div>
<h3 class="font-headline-md text-lg mb-2">Pago Seguro</h3>
<p class="font-body-md text-sm text-on-surface-variant">Contra entrega en Lima</p>
</div>
</div>
<div class="flex flex-col items-center text-center gap-sm bg-surface p-lg rounded-3xl shadow-sm border border-outline-variant/30 hover:shadow-md transition-all group">
<div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-3xl text-primary">support_agent</span>
</div>
<div>
<h3 class="font-headline-md text-lg mb-2">Asesoría Personalizada</h3>
<p class="font-body-md text-sm text-on-surface-variant">Te ayudamos a elegir</p>
</div>
</div>
</div>
</div>
</section>

<!-- ==========================================================================
     Sección de Testimonios
     ========================================================================== -->
<section class="py-xl bg-surface-container-low border-y border-outline-variant/40" id="testimonios">
  <div class="max-w-container-max mx-auto px-md">
    
    <div class="text-center max-w-2xl mx-auto mb-lg space-y-sm">
      <span class="bg-primary/10 text-primary px-4 py-1.5 rounded-full font-label-caps text-xs uppercase tracking-wider">
        Clientes Satisfechos
      </span>
      <h2 class="font-headline-md text-3xl md:text-4xl text-on-surface font-bold">
        Lo que dicen quienes confían en nosotros
      </h2>
      <p class="font-body-md text-base text-on-surface-variant">
        Cientos de familias peruanas cuidan su salud con nuestros productos 100% originales.
      </p>
    </div>

    <!-- Grilla de testimonios -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
      
      <!-- Tarjeta 1 -->
      <div class="testimonial-card opacity-0 bg-surface p-lg rounded-3xl shadow-sm border border-outline-variant/40 flex flex-col justify-between space-y-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
        <div class="space-y-sm">
          <div class="flex text-rating-gold gap-1">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
          </div>
          <p class="font-body-md text-base text-on-surface-variant italic leading-relaxed">
            "Excelente atención por WhatsApp. Pedí el Colágeno Premium y los batidos en cantidad para mi familia; me dieron un precio especial muy bueno y llegó súper rápido a Trujillo."
          </p>
        </div>
        <div class="flex items-center gap-sm pt-sm border-t border-outline-variant/20">
          <div class="w-12 h-12 rounded-full bg-primary-container text-on-primary-container font-headline-md flex items-center justify-center text-lg">
            MR
          </div>
          <div>
            <h3 class="font-headline-md text-base text-on-surface leading-tight">María Rodríguez</h3>
            <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-wider mt-1">Cliente verificada · Trujillo</p>
          </div>
        </div>
      </div>

      <!-- Tarjeta 2 -->
      <div class="testimonial-card opacity-0 bg-surface p-lg rounded-3xl shadow-sm border border-outline-variant/40 flex flex-col justify-between space-y-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
        <div class="space-y-sm">
          <div class="flex text-rating-gold gap-1">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
          </div>
          <p class="font-body-md text-base text-on-surface-variant italic leading-relaxed">
            "Siempre compro el Pack Articulaciones y el Kalmapross. Es muy práctico armar el carrito aquí y enviarlo al asesor para coordinar el pago contra entrega en Lima."
          </p>
        </div>
        <div class="flex items-center gap-sm pt-sm border-t border-outline-variant/20">
          <div class="w-12 h-12 rounded-full bg-primary-container text-on-primary-container font-headline-md flex items-center justify-center text-lg">
            CG
          </div>
          <div>
            <h3 class="font-headline-md text-base text-on-surface leading-tight">Carlos Gómez</h3>
            <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-wider mt-1">Cliente verificado · Lima</p>
          </div>
        </div>
      </div>

      <!-- Tarjeta 3 -->
      <div class="testimonial-card opacity-0 bg-surface p-lg rounded-3xl shadow-sm border border-outline-variant/40 flex flex-col justify-between space-y-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
        <div class="space-y-sm">
          <div class="flex text-rating-gold gap-1">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">star</span>
          </div>
          <p class="font-body-md text-base text-on-surface-variant italic leading-relaxed">
            "Súper recomendados. Todo 100% original de Santa Natura. Me explicaron el tema de los puntos y el descuento por volumen de manera clara."
          </p>
        </div>
        <div class="flex items-center gap-sm pt-sm border-t border-outline-variant/20">
          <div class="w-12 h-12 rounded-full bg-primary-container text-on-primary-container font-headline-md flex items-center justify-center text-lg">
            LV
          </div>
          <div>
            <h3 class="font-headline-md text-base text-on-surface leading-tight">Luz Valeria S.</h3>
            <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-wider mt-1">Cliente verificada · Arequipa</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ==========================================================================
     Preguntas frecuentes
     ========================================================================== -->
<section class="py-xl bg-surface">
<div class="max-w-3xl mx-auto px-md">
<h2 class="font-headline-md text-3xl md:text-4xl text-center mb-lg text-on-surface font-bold">Preguntas frecuentes</h2>
<div class="space-y-sm">

<details class="group bg-surface-container-low rounded-3xl border border-outline-variant/30 overflow-hidden transition-all duration-300">
<summary class="flex justify-between items-center gap-sm p-md cursor-pointer hover:bg-surface-container transition-colors">
<span class="font-headline-md text-lg text-on-surface">¿Cómo realizo un pedido?</span>
<span class="material-symbols-outlined transition-transform duration-300 group-open:rotate-180 shrink-0 text-primary">expand_more</span>
</summary>
<div class="p-md pt-0 text-on-surface-variant font-body-md text-base leading-relaxed">
                    Agrega los productos que quieras al carrito y presiona “Enviar pedido por WhatsApp”. El resumen con el detalle, el total y tus puntos llega automáticamente a tu asesor, que confirmará stock, precio final y envío.
                </div>
</details>

<details class="group bg-surface-container-low rounded-3xl border border-outline-variant/30 overflow-hidden transition-all duration-300">
<summary class="flex justify-between items-center gap-sm p-md cursor-pointer hover:bg-surface-container transition-colors">
<span class="font-headline-md text-lg text-on-surface">¿Puedo obtener un mejor precio?</span>
<span class="material-symbols-outlined transition-transform duration-300 group-open:rotate-180 shrink-0 text-primary">expand_more</span>
</summary>
<div class="p-md pt-0 text-on-surface-variant font-body-md text-base leading-relaxed" data-nota-cantidad></div>
</details>

<details class="group bg-surface-container-low rounded-3xl border border-outline-variant/30 overflow-hidden transition-all duration-300">
<summary class="flex justify-between items-center gap-sm p-md cursor-pointer hover:bg-surface-container transition-colors">
<span class="font-headline-md text-lg text-on-surface">¿Para qué sirven los puntos?</span>
<span class="material-symbols-outlined transition-transform duration-300 group-open:rotate-180 shrink-0 text-primary">expand_more</span>
</summary>
<div class="p-md pt-0 text-on-surface-variant font-body-md text-base leading-relaxed">
                    Cada producto otorga puntos. En el resumen de tu pedido verás cuántos acumulas en total, y tu asesor te explicará cómo se aplican según tu tipo de afiliación.
                </div>
</details>

<details class="group bg-surface-container-low rounded-3xl border border-outline-variant/30 overflow-hidden transition-all duration-300">
<summary class="flex justify-between items-center gap-sm p-md cursor-pointer hover:bg-surface-container transition-colors">
<span class="font-headline-md text-lg text-on-surface">¿Cuáles son los métodos de pago?</span>
<span class="material-symbols-outlined transition-transform duration-300 group-open:rotate-180 shrink-0 text-primary">expand_more</span>
</summary>
<div class="p-md pt-0 text-on-surface-variant font-body-md text-base leading-relaxed">
                    No cobramos en línea. El pago se coordina directamente con tu asesor por WhatsApp: pago contra entrega (Lima), Yape, Plin o transferencia bancaria.
                </div>
</details>

</div>
</div>
</section>

<!-- ==========================================================================
     Pie de página
     ========================================================================== -->
<!-- El pie va en verde oscuro con texto blanco. Se usa primary-container (y no
     primary) porque en el tema oscuro primary se aclara y el blanco dejaría de
     leerse; primary-container es verde oscuro en ambos temas. -->
<?php sn_pie(); ?>

<!-- Botón flotante de WhatsApp -->
<?php sn_boton_flotante(); ?>

<?php sn_scripts(); ?>
<?php sn_popup(); ?>
<!-- Los testimonios entran con un fundido hacia arriba, uno detrás de otro,
     la primera vez que aparecen en pantalla. Si el visitante pidió menos
     movimiento, simplemente se muestran sin animación. -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tarjetas = document.querySelectorAll('.testimonial-card');
    if (!tarjetas.length) return;

    const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (sinMovimiento || !('IntersectionObserver' in window)) {
        tarjetas.forEach((t) => t.classList.remove('opacity-0'));
        return;
    }

    const observador = new IntersectionObserver((entradas, obs) => {
        entradas.forEach((entrada) => {
            if (!entrada.isIntersecting) return;
            const i = Array.from(tarjetas).indexOf(entrada.target);
            entrada.target.style.animationDelay = `${i * 0.15}s`;
            entrada.target.classList.add('animate-fade-in-up');
            obs.unobserve(entrada.target);
        });
    }, { threshold: 0.1 });

    tarjetas.forEach((t) => observador.observe(t));
});
</script>
</body>
</html>
