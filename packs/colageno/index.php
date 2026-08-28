<?php
/* ============================================================================
   Landing de Google Ads — Packs de Colágeno Santa Natura
   ----------------------------------------------------------------------------
   Gemela de /packs: mismo maquetado y mismos parciales, distinta selección y
   distintos textos. Ver los comentarios de ../index.php.
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/../../inc/render.php';
require __DIR__ . '/../../inc/popup.php';
require __DIR__ . '/../../inc/partes/cabeza.php';
require __DIR__ . '/../../inc/partes/comunes.php';

$raiz      = '../../';
$seleccion = sn_seleccion_colageno(sn_catalogo());
$urlPagina = sn_site_url() . '/packs/colageno/';

sn_cabeza([
    'titulo'      => 'Packs de Colágeno Santa Natura — Precios y pedidos',
    'descripcion' => 'Packs de colágeno Santa Natura. Pide por WhatsApp de 8:00 a 23:00. Envíos y recojo en tienda a nivel nacional: Lima, Ayacucho, Tarapoto, Huánuco y más.',
    'canonical'   => 'https://santanatura.inmuno.lat/packs/colageno/',
    'clave'       => 'colageno',
    'raiz'        => $raiz,
    'extra'       => sn_jsonld($seleccion, $urlPagina, 'Packs de Colágeno Santa Natura'),
]);
?>
<body class="bg-background text-on-background font-body-md overflow-x-hidden">

<?php sn_simbolo_whatsapp(); ?>
<?php sn_modal_producto(); ?>
<?php sn_carrito('Ver los colágenos'); ?>
<?php sn_barra_promo('🌿 Envíos y recojo en tienda a nivel nacional · Atención de 8:00 a 23:00'); ?>
<?php sn_header($raiz, false, $raiz . 'index.php'); ?>

<main class="max-w-container-max mx-auto px-md md:px-lg py-lg space-y-lg">

<section class="text-center space-y-sm max-w-3xl mx-auto">
<span class="inline-block bg-error/10 text-error px-3 py-1 rounded-full font-label-caps text-[11px] uppercase tracking-wider">Precios vigentes</span>
<h1 class="font-headline-md text-headline-md-mobile md:text-display-lg text-primary leading-tight">Packs de Colágeno Santa Natura</h1>
<p class="font-body-md text-on-surface-variant md:text-body-lg">
    Colágeno hidrolizado Santa Natura en packs y en presentación individual de 450 g,
    con el descuento que ya trae cada uno. Cierras tu pedido por WhatsApp: sin pagos en línea.
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

<?= sn_franja_oferta($seleccion) ?>

<section aria-label="Packs de colágeno destacados">
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-sm md:gap-gutter"
     id="grid-seleccion" data-seleccion="<?= implode(',', array_column($seleccion, 'id')) ?>">
<?= sn_rejilla($seleccion, $raiz) ?>
</div>
</section>

<?= sn_bloque_cobertura() ?>

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
