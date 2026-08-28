<?php
/* ============================================================================
   Landing de Google Ads — Packs Santa Natura
   ----------------------------------------------------------------------------
   Se renderiza en el servidor en cada visita, así que el HTML llega completo
   al navegador y al robot de Google, y refleja al instante lo que guardes en
   el panel de gestión: no hay paso de compilación que recordar.

   Sin «Afíliate»: en una landing pagada compite con el botón de compra y
   además atrae revisión de políticas de Google Ads. En la portada se conserva.

   El maquetado de la tarjeta vive en inc/render.php; la portada usa su gemelo
   en render-productos.js. Si tocas uno, toca el otro:
   node scripts/verificar-paridad.js los compara.
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/../inc/render.php';
require __DIR__ . '/../inc/popup.php';
require __DIR__ . '/../inc/partes/cabeza.php';
require __DIR__ . '/../inc/partes/comunes.php';

$raiz      = '../';
$seleccion = sn_seleccion_packs(sn_catalogo());
$urlPagina = sn_site_url() . '/packs/';

sn_cabeza([
    'titulo'      => 'Packs Santa Natura con descuento — Pide por WhatsApp',
    'descripcion' => 'Packs Santa Natura con descuento. Pide por WhatsApp de 8:00 a 23:00. Envíos y recojo en tienda a nivel nacional: Lima, Ayacucho, Tarapoto, Huánuco y más.',
    'canonical'   => 'https://santanatura.inmuno.lat/packs/',
    'clave'       => 'packs',
    'raiz'        => $raiz,
    'extra'       => sn_jsonld($seleccion, $urlPagina, 'Packs Santa Natura con descuento'),
]);
?>
<body class="bg-background text-on-background font-body-md overflow-x-hidden">

<?php sn_simbolo_whatsapp(); ?>
<?php sn_modal_producto(); ?>
<?php sn_carrito('Ver los packs'); ?>
<?php sn_barra_promo('🌿 Envíos y recojo en tienda a nivel nacional · Atención de 8:00 a 23:00'); ?>
<?php sn_header($raiz, false, $raiz . 'index.php'); ?>

<!-- ==========================================================================
     Encabezado de la landing + productos
     --------------------------------------------------------------------------
     Mobile-first: el H1, la prueba de confianza y los 8 packs entran de una,
     sin grilla de 24 ni paginación. Quien llega desde un anuncio no debe buscar.
     ========================================================================== -->
<main class="max-w-container-max mx-auto px-md md:px-lg py-lg space-y-lg">

<section class="text-center space-y-sm max-w-3xl mx-auto">
<span class="inline-block bg-error/10 text-error px-3 py-1 rounded-full font-label-caps text-[11px] uppercase tracking-wider">Precios vigentes</span>
<h1 class="font-headline-md text-headline-md-mobile md:text-display-lg text-primary leading-tight">Packs Santa Natura con descuento</h1>
<p class="font-body-md text-on-surface-variant md:text-body-lg">
    Combinaciones armadas de productos naturales, con el descuento que ya trae cada pack.
    Eliges, agregas al pedido y cierras por WhatsApp con tu asesor: sin pagos en línea.
</p>

<div class="flex flex-wrap items-center justify-center gap-x-md gap-y-xs pt-xs text-sm text-on-surface-variant">
<span class="inline-flex items-center gap-1.5">
<span class="material-symbols-outlined text-lg text-primary">local_shipping</span>
    Envíos a nivel nacional
</span>
<span class="inline-flex items-center gap-1.5">
<span class="material-symbols-outlined text-lg text-primary">storefront</span>
    Recojo en tienda
</span>
<span class="inline-flex items-center gap-1.5">
<span class="material-symbols-outlined text-lg text-primary">verified</span>
    Distribuidor autorizado
</span>
</div>

<div class="pt-sm">
<a class="inline-flex items-center justify-center gap-2 bg-action-whatsapp text-white px-lg py-3 rounded-full font-title-sm text-base shadow-lg shadow-action-whatsapp/25 hover:brightness-105 transition-all active:scale-[0.98]"
   data-wa="" data-wa-origen="generico" href="#" target="_blank" rel="noopener">
<svg class="w-6 h-6 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
    Consultar por WhatsApp
</a>
</div>
</section>

<!-- La oferta se escribe desde los datos: si algún pack de la selección deja de
     estar al 30%, la franja cambia sola a un texto genérico en vez de anunciar
     un descuento que no se cumple. -->
<?= sn_franja_oferta($seleccion) ?>

<!-- Las tarjetas las escribe PHP en el servidor (inc/render.php), así que el
     robot de Google y el visitante las ven de inmediato, sin esperar a
     JavaScript. store.js solo las repinta al cargar para reflejar el carrito
     real del visitante; los ids de data-seleccion se lo dicen. -->
<section aria-label="Packs destacados">
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-sm md:gap-gutter"
     id="grid-seleccion" data-seleccion="<?= implode(',', array_column($seleccion, 'id')) ?>">
<?= sn_rejilla($seleccion, $raiz) ?>
</div>
</section>

<!-- Envíos, horario y formas de pago: es lo que promete el anuncio, así que
     tiene que estar en el HTML servido, no inyectado por JavaScript. -->
<?= sn_bloque_cobertura() ?>

<!-- Enlace discreto al catálogo completo -->
<p class="text-center">
<a class="inline-flex items-center gap-1.5 text-on-surface-variant hover:text-primary transition-colors font-label-caps text-sm" href="<?= $raiz ?>index.php#productos">
    Ver todo el catálogo
    <span class="material-symbols-outlined text-lg">arrow_forward</span>
</a>
</p>

</main>

<?php sn_pie($raiz, false); ?>
<?php sn_boton_flotante(); ?>
<?php sn_scripts($raiz); ?>
<?php sn_popup($raiz, true); ?>
</body>
</html>
