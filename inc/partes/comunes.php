<?php
/* ============================================================================
   Piezas comunes a todas las páginas públicas
   ----------------------------------------------------------------------------
   Ficha de producto, carrito, aviso flotante, barra promocional, cabecera,
   pie y botón flotante de WhatsApp. Estaban copiados en cada página HTML;
   ahora se escriben una vez.

   Todas reciben $raiz (prefijo hasta la raíz: '' | '../' | '../../').
   Las que cambian entre la portada y las landings llevan un interruptor
   explícito, en vez de dos versiones parecidas del mismo bloque.
   ========================================================================== */

declare(strict_types=1);

/* --------------------------------------------------------------------------
   Ficha de producto (modal). La rellena store.js al tocar una tarjeta.
   -------------------------------------------------------------------------- */
function sn_modal_producto(): void
{
    ?>
<div class="fixed inset-0 z-[200] flex items-center justify-center p-md bg-black/60 opacity-0 pointer-events-none transition-opacity duration-300" id="modal-producto" onclick="if (event.target === this) cerrarFichaProducto()">
<div class="bg-surface rounded-3xl overflow-hidden max-w-4xl w-full shadow-2xl relative max-h-[90vh] overflow-y-auto scroll-suave">
<button aria-label="Cerrar" class="absolute top-4 right-4 text-on-surface-variant hover:text-on-surface z-10 bg-surface/80 rounded-full p-2" onclick="cerrarFichaProducto()">
<span class="material-symbols-outlined">close</span>
</button>
<div class="grid md:grid-cols-2">
<div class="bg-surface-container p-md md:p-lg flex items-center justify-center">
<div class="w-full rounded-2xl overflow-hidden" id="ficha-imagen"></div>
</div>
<div class="p-md md:p-xl space-y-md">
<span class="inline-block bg-primary/10 text-primary px-3 py-1 rounded-full font-label-caps text-[11px] uppercase" id="ficha-categoria">Categoría</span>
<h2 class="font-headline-md text-headline-md-mobile text-on-surface leading-tight" id="ficha-titulo">Nombre del producto</h2>

<div>
<div class="flex items-center gap-sm">
<span class="text-3xl font-bold text-primary" id="ficha-precio">S/ 0.00</span>
<span class="hidden bg-error text-on-error px-2 py-1 rounded-full text-[11px] font-bold" id="ficha-descuento">-0%</span>
</div>
<del class="hidden text-on-surface-variant text-sm" id="ficha-precio-original">S/ 0.00</del>
<p class="text-xs text-on-surface-variant mt-1">Precio de venta al público</p>
</div>

<div class="flex items-center gap-xs text-primary bg-primary/10 rounded-full px-sm py-2 w-fit">
<span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">stars</span>
<span class="font-label-caps text-sm">Acumulas <strong id="ficha-puntos">0.00 pts</strong></span>
</div>

<p class="font-body-md text-on-surface-variant text-sm" id="ficha-descripcion">Descripción del producto.</p>

<!-- Aviso obligatorio de precios por cantidad -->
<div class="flex gap-xs items-start bg-primary/5 border border-primary/20 rounded-2xl p-sm">
<span class="material-symbols-outlined text-primary text-lg shrink-0">sell</span>
<p class="font-body-md text-xs text-on-surface-variant" data-nota-cantidad></p>
</div>

<div class="space-y-xs pt-xs">
<button class="w-full bg-primary text-on-primary py-md rounded-full font-title-sm text-base hover:brightness-110 transition-all shadow-md flex items-center justify-center gap-xs active:scale-[0.98]"
        id="ficha-agregar" onclick="agregarDesdeFicha()">
<span class="material-symbols-outlined">add_shopping_cart</span>
                        Agregar al pedido
                    </button>
<a class="w-full bg-action-whatsapp text-white py-md rounded-full font-title-sm text-base hover:brightness-105 transition-all shadow-md shadow-action-whatsapp/25 flex items-center justify-center gap-xs active:scale-[0.98]"
   href="#" id="ficha-whatsapp" target="_blank" rel="noopener">
<svg class="w-6 h-6 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
                        Consultar por WhatsApp
                    </a>
</div>
</div>
</div>
</div>
</div>
<?php
}

/* --------------------------------------------------------------------------
   Carrito lateral
   -------------------------------------------------------------------------- */
function sn_carrito(string $textoVacio = 'Ver el catálogo'): void
{
    ?>
<div class="fixed inset-0 z-[190] bg-black/50 opacity-0 pointer-events-none transition-opacity duration-300" id="carrito-fondo" onclick="cerrarCarrito()"></div>

<aside class="fixed top-0 right-0 h-full w-full max-w-md z-[195] bg-surface shadow-2xl flex flex-col translate-x-full transition-transform duration-300" id="carrito-panel">
<header class="flex items-center justify-between p-md border-b border-outline-variant/30">
<div>
<h2 class="font-headline-md text-xl text-on-surface">Tu pedido</h2>
<p class="text-xs text-on-surface-variant" id="carrito-unidades">0 unidades</p>
</div>
<button aria-label="Cerrar carrito" class="text-on-surface-variant hover:text-on-surface rounded-full p-2 hover:bg-surface-container transition-colors" onclick="cerrarCarrito()">
<span class="material-symbols-outlined">close</span>
</button>
</header>

<div class="flex-1 overflow-y-auto px-md scroll-suave">
<div id="carrito-lineas"></div>
<div class="hidden py-xl text-center space-y-sm" id="carrito-vacio">
<span class="material-symbols-outlined text-5xl text-outline-variant">shopping_bag</span>
<p class="font-body-md text-on-surface-variant text-sm">Aún no agregas productos.</p>
<button class="text-primary font-label-caps text-sm hover:underline" onclick="cerrarCarrito()"><?= sn_e($textoVacio) ?></button>
</div>
</div>

<div class="hidden border-t border-outline-variant/30 p-md space-y-md bg-surface-container-low" id="carrito-resumen">
<div class="flex items-center justify-between">
<span class="font-body-md text-on-surface-variant">Total</span>
<span class="text-2xl font-bold text-on-surface" id="carrito-total">S/ 0.00</span>
</div>
<div class="flex items-center justify-between text-primary">
<span class="font-label-caps text-sm flex items-center gap-xs">
<span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">stars</span>
                Puntos acumulados
            </span>
<span class="font-bold" id="carrito-puntos">0.00 pts</span>
</div>

<!-- Aviso obligatorio de precios por cantidad -->
<div class="flex gap-xs items-start bg-primary/5 border border-primary/20 rounded-2xl p-sm">
<span class="material-symbols-outlined text-primary text-lg shrink-0">sell</span>
<p class="font-body-md text-xs text-on-surface-variant" data-nota-cantidad></p>
</div>

<button class="w-full bg-action-whatsapp text-white py-md rounded-full font-title-sm text-base hover:brightness-105 transition-all shadow-lg shadow-action-whatsapp/25 flex items-center justify-center gap-xs active:scale-[0.98]"
        data-wa-origen="carrito" onclick="enviarPedidoPorWhatsApp()">
<svg class="w-6 h-6 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
            Enviar pedido por WhatsApp
        </button>
<p class="text-[11px] text-center text-on-surface-variant">El pago se coordina directamente con tu asesor. No cobramos en línea.</p>
<button class="w-full text-xs text-outline hover:text-error" onclick="vaciarCarrito()">Vaciar pedido</button>
</div>
</aside>

<!-- Aviso flotante -->
<div class="fixed bottom-28 left-1/2 -translate-x-1/2 z-[210] bg-inverse-surface text-inverse-on-surface px-md py-3 rounded-full shadow-xl text-sm font-label-caps opacity-0 translate-y-4 transition-all duration-300 pointer-events-none max-w-[90vw] text-center" id="aviso"></div>
<?php
}

/* --------------------------------------------------------------------------
   Barra promocional
   --------------------------------------------------------------------------
   data-wa vacío = usa el mensaje prellenado de ESTA página (config.js).
   -------------------------------------------------------------------------- */
function sn_barra_promo(string $texto): void
{
    ?>
<div class="bg-primary text-on-primary">
<a class="max-w-container-max mx-auto flex items-center justify-center gap-2 py-2 px-md text-[11px] md:text-xs font-label-caps hover:opacity-90 transition-opacity"
   data-wa="" data-wa-origen="generico" href="#" target="_blank" rel="noopener">
<span><?= $texto ?></span>
<span class="hidden sm:inline-flex items-center gap-1 bg-white/20 rounded-full px-2 py-0.5">
<svg class="w-3 h-3" aria-hidden="true"><use href="#ico-whatsapp"></use></svg> Escríbenos
</span>
</a>
</div>
<?php
}

/* --------------------------------------------------------------------------
   Cabecera
   --------------------------------------------------------------------------
   $afiliacion = false en las landings de pago: el enlace a «Afíliate» compite
   con el botón de compra y además atrae revisión de políticas de Google Ads.
   En la portada se conserva tal cual.
   -------------------------------------------------------------------------- */
function sn_header(string $raiz = '', bool $afiliacion = true, string $inicio = '#inicio'): void
{
    ?>
<header class="bg-surface/80 backdrop-blur-md sticky top-0 z-[100] border-b border-outline-variant/30 shadow-sm transition-all">
<div class="max-w-container-max mx-auto px-md md:px-lg">

<div class="flex justify-between items-center gap-md h-20">
<div class="flex items-center gap-sm shrink-0">
<button aria-label="Abrir menú" class="lg:hidden p-2 rounded-full text-on-surface-variant hover:bg-surface-container transition-colors" onclick="alternarMegaMenu()">
<span class="material-symbols-outlined">menu</span>
</button>

<a class="flex items-center gap-2 shrink-0 hover:opacity-90 active:scale-95 transition-transform" href="<?= sn_e($inicio) ?>">
<span class="grid place-items-center w-10 h-10 rounded-full bg-primary text-on-primary shrink-0">
<span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">eco</span>
</span>
<span class="leading-tight hidden sm:block">
<span class="block font-headline-md text-headline-md-mobile md:text-headline-md font-bold text-primary whitespace-nowrap">Santa Natura</span>
</span>
</a>
</div>

<div class="hidden lg:flex items-center gap-md">
<button class="inline-flex items-center gap-1.5 text-on-surface-variant hover:text-primary transition-colors font-label-caps"
        id="btn-mega" aria-expanded="false" aria-controls="mega-menu" onclick="alternarMegaMenu()">
<span class="material-symbols-outlined text-lg">apps</span>
        Categorías
        <span class="material-symbols-outlined text-lg transition-transform duration-200" id="mega-flecha">expand_more</span>
</button>
<?php if ($afiliacion): ?>
<a class="inline-flex items-center gap-1.5 text-on-surface-variant hover:text-primary transition-colors font-label-caps" href="<?= sn_e($raiz) ?>afiliacion.php">
<span class="material-symbols-outlined text-lg">workspace_premium</span>
        Afíliate
      </a>
<?php endif; ?>
</div>

<div class="flex items-center gap-sm ml-auto">
<div class="hidden lg:block relative w-64 xl:w-80">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline text-xl pointer-events-none">search</span>
<input autocomplete="off" class="w-full bg-surface-container-low border border-transparent rounded-full py-2.5 pl-12 pr-4 text-sm placeholder:text-outline focus:bg-surface focus:border-primary focus:ring-1 focus:ring-primary transition-colors" id="buscador" placeholder="Buscar…" type="search"
       role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="sugerencias"/>
<ul class="hidden absolute left-0 right-0 top-full mt-2 z-[120] bg-surface border border-outline-variant rounded-2xl shadow-2xl overflow-hidden max-h-96 overflow-y-auto scroll-suave" id="sugerencias" role="listbox"></ul>
</div>

<button aria-label="Cambiar tema claro/oscuro" class="p-2 rounded-full text-on-surface-variant hover:bg-surface-container-low hover:text-primary transition-all duration-300" onclick="alternarTema()">
<span class="material-symbols-outlined" id="icono-tema">dark_mode</span>
</button>
<button aria-label="Ver mi pedido" class="relative p-2 rounded-full text-on-surface-variant hover:bg-surface-container-low hover:text-primary transition-all duration-300" onclick="abrirCarrito()">
<span class="material-symbols-outlined">shopping_cart</span>
<span class="hidden absolute top-0 right-0 bg-primary text-on-primary text-[10px] font-bold min-w-[18px] h-[18px] px-1 rounded-full text-center leading-[18px] ring-2 ring-surface" id="carrito-contador">0</span>
</button>
<a class="hidden md:inline-flex bg-action-whatsapp text-white px-4 py-2 rounded-full font-label-caps text-sm hover:brightness-105 transition-all items-center gap-2 shadow-sm hover:opacity-90 active:scale-95"
   data-wa="" data-wa-origen="generico" href="#" target="_blank" rel="noopener">
<svg class="w-4 h-4 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg> WhatsApp
</a>
</div>
</div>

<!-- Buscador para pantallas pequeñas -->
<div class="lg:hidden pb-3">
<div class="relative w-full">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-xl pointer-events-none">search</span>
<input autocomplete="off" class="w-full bg-surface-container-low border border-transparent rounded-full py-2.5 pl-10 pr-4 text-sm placeholder:text-outline focus:bg-surface focus:border-primary focus:ring-1 focus:ring-primary transition-colors" id="buscador-movil" placeholder="Busca un producto…" type="search"
       role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="sugerencias-movil"/>
<ul class="hidden absolute left-0 right-0 top-full mt-2 z-[120] bg-surface border border-outline-variant rounded-2xl shadow-2xl overflow-hidden max-h-80 overflow-y-auto scroll-suave" id="sugerencias-movil" role="listbox"></ul>
</div>
</div>

<!-- Accesos rápidos de categoría (ocultos en el diseño actual; los rellena store.js) -->
<div class="hidden items-center gap-sm h-14 border-t border-outline-variant/30">
<nav class="flex items-center gap-2 overflow-x-auto sin-barra" id="nav-categorias"></nav>
</div>
</div>

<!-- Mega menú: el árbol completo de categorías. En móvil hace de menú lateral. -->
<div class="hidden border-t border-outline-variant/30 bg-surface shadow-xl max-h-[70vh] overflow-y-auto scroll-suave" id="mega-menu">
<?php if ($afiliacion): ?>
<div class="lg:hidden max-w-container-max mx-auto px-md pt-md">
<a class="flex items-center gap-2 bg-primary/5 border border-primary/20 text-primary rounded-2xl px-md py-3 font-title-sm text-base" href="<?= sn_e($raiz) ?>afiliacion.php">
<span class="material-symbols-outlined">workspace_premium</span>
      Afíliate y compra con descuento
    </a>
</div>
<?php endif; ?>
<div class="max-w-container-max mx-auto px-md md:px-lg py-md grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-x-lg gap-y-md" id="mega-contenido"></div>
</div>
</div>
</header>
<?php
}

/* --------------------------------------------------------------------------
   Pie de página
   --------------------------------------------------------------------------
   Va en verde oscuro con texto blanco. Se usa primary-container (y no primary)
   porque en el tema oscuro primary se aclara y el blanco dejaría de leerse;
   primary-container es verde oscuro en ambos temas.
   -------------------------------------------------------------------------- */
function sn_pie(string $raiz = '', bool $afiliacion = true): void
{
    ?>
<footer class="bg-primary-container text-botanical-white py-xl w-full mt-auto">
<div class="max-w-container-max mx-auto px-md grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg">
<div class="space-y-sm">
<span class="font-headline-md text-2xl font-bold">Santa Natura</span>
<p class="font-body-md text-sm text-botanical-white/80 leading-relaxed">Distribuidor Independiente Autorizado. Llevando lo mejor de nuestra tierra a tu hogar con productos 100% naturales.</p>
</div>
<div>
<h2 class="font-headline-md text-lg mb-md">Líneas</h2>
<ul class="space-y-xs font-body-md text-sm text-botanical-white/80" id="footer-categorias"></ul>
</div>
<div>
<h2 class="font-headline-md text-lg mb-md">Pedidos</h2>
<ul class="space-y-xs font-body-md text-sm text-botanical-white/80">
<li>Envíos y recojo en tienda a nivel nacional.</li>
<li>Atención de 8:00 a 23:00, todos los días.</li>
<li>Pago contra entrega en Lima; Yape, Plin o transferencia en provincias.</li>
<?php if ($afiliacion): ?>
<li><a class="hover:text-botanical-white transition-colors" href="<?= sn_e($raiz) ?>afiliacion.php">Afíliate y gana</a></li>
<?php endif; ?>
</ul>
</div>
<div class="space-y-md">
<h2 class="font-headline-md text-lg">Síguenos</h2>
<div class="flex gap-sm">
<!-- Logos de marca en SVG inline: Material Symbols NO incluye iconos de marca. -->
<a aria-label="Facebook" class="w-12 h-12 rounded-full bg-botanical-white/10 flex items-center justify-center text-botanical-white hover:bg-botanical-white/20 transition-colors" href="#"><svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg></a>
<a aria-label="Instagram" class="w-12 h-12 rounded-full bg-botanical-white/10 flex items-center justify-center text-botanical-white hover:bg-botanical-white/20 transition-colors" href="#"><svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.3-1.46.72-2.13 1.38C1.35 2.68.93 3.35.63 4.14.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.3.79.72 1.46 1.38 2.13.67.66 1.34 1.08 2.13 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.3 1.46-.72 2.13-1.38.66-.67 1.08-1.34 1.38-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91-.3-.79-.72-1.46-1.38-2.13C21.32 1.35 20.65.93 19.86.63 19.1.33 18.22.13 16.95.07 15.67.01 15.26 0 12 0z"/><path d="M12 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/><circle cx="18.41" cy="5.59" r="1.44"/></svg></a>
</div>
<!-- El texto lo escribe store.js desde config.js → PRICE_LIST_DATE, para que la
     fecha no vuelva a quedar quemada en el HTML de cada página. -->
<p class="font-label-caps text-[10px] text-botanical-white/60 uppercase tracking-wider" data-lista-precios></p>
</div>
</div>
<div class="max-w-container-max mx-auto mt-lg pt-md px-md border-t border-botanical-white/20 text-center">
<p class="font-label-caps text-botanical-white/60 text-xs tracking-wider uppercase">© <?= date('Y') ?> Santa Natura Distribuidor Autorizado. Todos los derechos reservados.</p>
</div>
</footer>
<?php
}

/* --------------------------------------------------------------------------
   Botón flotante de WhatsApp
   -------------------------------------------------------------------------- */
function sn_boton_flotante(): void
{
    ?>
<a aria-label="Escríbenos por WhatsApp" class="fixed bottom-6 right-6 z-[150] w-16 h-16 bg-action-whatsapp rounded-full flex items-center justify-center text-white shadow-2xl hover:scale-110 transition-transform pulse-whatsapp"
   data-wa-origen="flotante" data-wa="" href="#" target="_blank" rel="noopener">
<svg class="w-8 h-8" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
</a>
<?php
}
