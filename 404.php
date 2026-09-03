<?php
/* ============================================================================
   Página de error 404
   ----------------------------------------------------------------------------
   No es una página de disculpa: es una página de rescate. Quien llega aquí ya
   está dentro —muchas veces pagando tú el clic— y lo único que importa es que
   no cierre la pestaña. Por eso lleva el buscador de siempre en la cabecera,
   los cuatro packs más vendidos listos para agregar al pedido, y el botón de
   WhatsApp. Un «página no encontrada» a secas tira ese clic a la basura.

   DOS DETALLES QUE NO SON ADORNO
   ----------------------------------------------------------------------------
   1. Devuelve un 404 de verdad (http_response_code). Si respondiera 200,
      Google lo trataría como una página normal con contenido duplicado —lo que
      llaman «soft 404»— y acabaría indexando direcciones que no existen.

   2. La raíz del sitio se calcula, no se escribe a mano. Esta página se sirve
      para CUALQUIER dirección que no exista, así que el navegador puede estar
      en /packs/loquesea/. Con rutas relativas, el CSS y las fotos apuntarían a
      esa carpeta inventada y la página saldría desnuda. SCRIPT_NAME siempre
      dice dónde está de verdad este archivo, tanto en el hosting (/) como en
      XAMPP (/SantaNaturaTienda/).

   Sin «Afíliate»: quien cae aquí venía buscando otra cosa y hay que devolverlo
   al catálogo, no abrirle una tercera puerta.
   ========================================================================== */

declare(strict_types=1);

require __DIR__ . '/inc/render.php';
require __DIR__ . '/inc/popup.php';
require __DIR__ . '/inc/partes/cabeza.php';
require __DIR__ . '/inc/partes/comunes.php';

http_response_code(404);

$raiz = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/') . '/';

/* Cuatro packs, no ocho: aquí acompañan al mensaje, no son el escaparate. */
$seleccion = array_slice(sn_seleccion_packs(sn_catalogo()), 0, 4);

sn_cabeza([
    'titulo'      => 'Página no encontrada — Santa Natura',
    'descripcion' => 'Esta página no existe o cambió de dirección. Busca el producto que necesitas o mira el catálogo completo de Santa Natura, con precios vigentes y pedidos por WhatsApp.',
    // Sin canonical: no se declara canónica una dirección que no existe.
    'canonical'   => '',
    'clave'       => 'home',
    'raiz'        => $raiz,
    'iconos'      => ['search_off', 'grid_view'],
    // Cinturón y tirantes: el 404 ya basta para que no se indexe, pero si algún
    // día el hosting devolviera 200 por su cuenta, esto lo sigue impidiendo.
    'extra'       => '<meta name="robots" content="noindex, follow"/>',
]);
?>
<body class="bg-background text-on-background font-body-md overflow-x-hidden">

<?php sn_simbolo_whatsapp(); ?>
<?php sn_modal_producto(); ?>
<?php sn_carrito('Ver el catálogo'); ?>
<?php sn_barra_promo('🌿 Envíos y recojo en tienda a nivel nacional · Atención de 8:00 a 23:00'); ?>
<?php sn_header($raiz, false, $raiz . 'index.php'); ?>

<main class="max-w-container-max mx-auto px-md md:px-lg py-lg space-y-lg">

<section class="text-center space-y-sm max-w-2xl mx-auto pt-md">
  <span class="material-symbols-outlined text-6xl md:text-7xl text-outline-variant">search_off</span>

  <p class="font-label-caps text-xs uppercase tracking-wider text-on-surface-variant">Error 404</p>

  <h1 class="font-headline-md text-headline-md-mobile md:text-headline-md text-on-surface leading-tight">
    Esta página no existe
  </h1>

  <p class="font-body-md text-on-surface-variant md:text-body-lg">
    Puede que el enlace esté mal escrito o que el producto haya cambiado de sitio.
    Busca lo que necesitas en el buscador de arriba, o entra directo al catálogo.
  </p>

  <div class="flex flex-col sm:flex-row flex-wrap items-center justify-center gap-sm pt-sm">
    <a class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-primary text-on-primary px-lg py-3 rounded-full font-title-sm text-base shadow-lg hover:brightness-110 transition-all active:scale-[0.98]"
       href="<?= sn_e($raiz) ?>index.php#productos">
      <span class="material-symbols-outlined">grid_view</span>
      Ver el catálogo
    </a>
    <a class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-surface border border-outline-variant text-on-surface px-lg py-3 rounded-full font-title-sm text-base hover:border-primary hover:text-primary transition-colors"
       href="<?= sn_e($raiz) ?>packs/">
      Ver los packs
      <span class="material-symbols-outlined">arrow_forward</span>
    </a>
  </div>

  <div class="pt-xs">
    <a class="inline-flex items-center justify-center gap-2 bg-action-whatsapp text-white px-lg py-3 rounded-full font-title-sm text-base shadow-lg shadow-action-whatsapp/25 hover:brightness-105 transition-all active:scale-[0.98]"
       data-wa="Hola, no encontré una página en la web y quiero consultar por un producto."
       data-wa-origen="generico" href="#" target="_blank" rel="noopener">
      <svg class="w-6 h-6 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
      Pregúntanos por WhatsApp
    </a>
  </div>
</section>

<!-- Los cuatro packs se pintan en el servidor, igual que en las landings, así
     que se ven aunque el JavaScript tarde. store.js los repinta al cargar para
     que el globo verde refleje el carrito real; los ids se los dice
     data-seleccion. -->
<?php if ($seleccion): ?>
<section aria-label="Packs más pedidos" class="space-y-md">
  <h2 class="font-headline-md text-2xl md:text-3xl text-on-surface text-center">
    Mientras tanto, lo más pedido
  </h2>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-sm md:gap-gutter"
       id="grid-seleccion" data-seleccion="<?= sn_e(implode(',', array_column($seleccion, 'id'))) ?>">
    <?= sn_rejilla($seleccion, $raiz) ?>
  </div>
</section>
<?php endif; ?>

<?= sn_bloque_cobertura() ?>

</main>

<?php sn_pie($raiz, false); ?>
<?php sn_boton_flotante(); ?>
<?php sn_scripts($raiz); ?>
</body>
</html>
