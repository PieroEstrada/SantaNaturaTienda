<?php
/* ============================================================================
   <head> común de todas las páginas públicas
   ----------------------------------------------------------------------------
   Antes este bloque estaba COPIADO en index.html, afiliacion.html y las dos
   landings: cuatro copias del mismo config de Tailwind, las mismas fuentes y
   el mismo script de tema. Añadir un color o un icono obligaba a tocar los
   cuatro archivos y era cuestión de tiempo que se desincronizaran.

   Ahora se escribe una sola vez. Cada página pasa lo suyo por $pagina:

     titulo      <title> de la página
     descripcion meta description (140-155 caracteres)
     canonical   URL canónica absoluta
     clave       clave de MENSAJES_WA en config.js: home | packs | colageno
     raiz        prefijo hasta la raíz: '' | '../' | '../../'
     iconos      lista de iconos de Material Symbols que usa la página
     extra       HTML suelto para el <head> (JSON-LD, estilos propios…)

   La configuración de Tailwind ya NO vive aquí: se mudó a build/tailwind.config.js
   y de ahí sale ../tailwind.css. Antes eran ~80 líneas de JavaScript incrustadas
   en el <head> de cada página que el navegador del visitante tenía que leer y
   ejecutar antes de pintar. Si tocas clases en el HTML o el JS:

       cd build && npm run build
   ========================================================================== */

declare(strict_types=1);

/** Iconos que usan todas las páginas. Cada página suma los suyos.
    'add', 'remove' y 'check_circle' los pinta el carrito, que va en TODAS las
    páginas (también en las landings de Ads): sin declararlos, los botones de
    cantidad salían en blanco justo donde se confirma el pedido. */
const SN_ICONOS_BASE = [
    'add', 'add_shopping_cart', 'apps', 'arrow_forward', 'check_circle',
    'close', 'dark_mode', 'eco', 'expand_more', 'light_mode', 'local_shipping',
    'menu', 'payments', 'remove', 'schedule', 'search', 'sell', 'shopping_bag',
    'shopping_cart', 'stars', 'storefront', 'verified',
];

/* ----------------------------------------------------------------------------
   Medición: Google Ads y Google Analytics 4
   ----------------------------------------------------------------------------
   Rellena lo que uses y déjalo vacío si no. Con los dos vacíos no se carga
   NADA: ni una petición a Google, ni cookies, ni el aviso de cookies que
   tocaría poner después. Con uno solo, se carga solo ese.

   Los dos van en la MISMA etiqueta (gtag.js). Es la forma que recomienda
   Google y ahorra una descarga: no se pone un script por producto.

   SN_GTAG_ADS  ID de conversiones de Google Ads. Empieza por AW-.
                Tiene que ser EL MISMO valor que ADS_CONFIG.conversionId de
                config.js: aquí carga la etiqueta y allí se dispara el evento
                al pulsar WhatsApp. Si solo pones uno de los dos, no se mide.
   SN_GTAG_GA4  ID de medición de Google Analytics 4. Empieza por G-.

   El paso a paso para sacar los dos valores está en MEDICION.md.
   -------------------------------------------------------------------------- */
const SN_GTAG_ADS = '';     // p. ej. 'AW-123456789'
const SN_GTAG_GA4 = '';     // p. ej. 'G-ABCD123456'

function sn_cabeza(array $pagina): void
{
    $raiz    = $pagina['raiz'] ?? '';
    $iconos  = array_values(array_unique(array_merge(SN_ICONOS_BASE, $pagina['iconos'] ?? [])));
    sort($iconos);
    ?>
<!DOCTYPE html>
<!-- data-pagina elige el mensaje prellenado de WhatsApp (config.js → MENSAJES_WA);
     data-raiz es la profundidad de carpeta hasta la raíz del sitio. -->
<html class="light" lang="es" data-pagina="<?= sn_e($pagina['clave'] ?? 'home') ?>" data-raiz="<?= sn_e($raiz) ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?= sn_e($pagina['titulo']) ?></title>
<meta name="description" content="<?= sn_e($pagina['descripcion']) ?>"/>
<?php /* Sin canonical no se escribe la etiqueta. Una <link rel="canonical" href="">
         vacía le dice a Google que la página canónica es ella misma con la URL
         actual, y en la página de error eso sería declarar canónica cualquier
         dirección inventada que alguien teclee. */ ?>
<?php if (($pagina['canonical'] ?? '') !== ''): ?>
<link rel="canonical" href="<?= sn_e($pagina['canonical']) ?>"/>
<?php endif; ?>

<!-- Icono de la pestaña y de la pantalla de inicio. Se generan a partir del
     isotipo oficial con:
         C:/xampp/php/php.exe -d extension=gd scripts/generar-favicon.php
     El favicon.ico lleva dentro 16, 32 y 48 px; el navegador elige. -->
<link rel="icon" href="<?= sn_e($raiz) ?>favicon.ico?v=<?= sn_v('favicon.ico') ?>" sizes="any"/>
<link rel="icon" type="image/png" sizes="192x192" href="<?= sn_e($raiz) ?>img/icono-192.png?v=<?= sn_v('img/icono-192.png') ?>"/>
<link rel="apple-touch-icon" href="<?= sn_e($raiz) ?>apple-touch-icon.png?v=<?= sn_v('apple-touch-icon.png') ?>"/>

<?php
/* ==========================================================================
   Etiqueta de Google (gtag.js): Google Ads y/o Analytics 4
   --------------------------------------------------------------------------
   No hay nada que descomentar: se escribe sola en cuanto rellenes SN_GTAG_ADS
   o SN_GTAG_GA4 ahí arriba, y se activa de golpe en TODAS las páginas. Con
   las dos vacías no sale ni una línea de HTML.

   El src lleva el primer ID y los gtag('config', …) van uno por producto: así
   es como Google mide dos productos con una sola etiqueta.
   ========================================================================== */
$sn_gtag = array_values(array_filter([SN_GTAG_ADS, SN_GTAG_GA4]));
?>
<?php if ($sn_gtag): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= sn_e($sn_gtag[0]) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
<?php foreach ($sn_gtag as $sn_id): ?>
  gtag('config', '<?= sn_e($sn_id) ?>');
<?php endforeach; ?>
</script>
<?php endif; ?>

<!-- Abren DNS+TLS con Google Fonts en paralelo al parseo del HTML. Sin esto,
     el navegador hace dos handshakes en serie (googleapis → gstatic) antes de
     bajar el primer byte de fuente. -->
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&amp;display=swap" rel="stylesheet"/>
<!-- icon_names recorta la fuente a los iconos que se usan de verdad: unos 9 KB
     en vez de los 1.1 MB de la familia completa. Si añades un icono nuevo en el
     HTML o en el JS, súmalo a SN_ICONOS_BASE (o a los iconos de esa página) o
     saldrá en blanco. El único eje variable que usamos es FILL. -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:FILL@0..1&amp;icon_names=<?= sn_e(implode(',', $iconos)) ?>&amp;display=block" rel="stylesheet"/>
<!-- Fija el tema (claro/oscuro) ANTES de pintar para evitar el parpadeo inicial.
     Respeta lo guardado por el usuario y, si no hay, la preferencia del sistema. -->
<script>
        (function () {
            try {
                var guardado = localStorage.getItem('tema');
                var oscuro = guardado
                    ? guardado === 'oscuro'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches;
                var raiz = document.documentElement;
                raiz.classList.toggle('dark', oscuro);
                raiz.classList.toggle('light', !oscuro);
            } catch (_e) {}
        })();
    </script>
<link href="<?= sn_e($raiz) ?>styles.css?v=<?= sn_v('styles.css') ?>" rel="stylesheet"/>
<!-- Tailwind, ya generado. Antes se cargaba cdn.tailwindcss.com: 409 KB de
     JavaScript que cada visitante se bajaba y ejecutaba para que le calcularan
     el CSS en su propio teléfono, en cada visita. Ahora el cálculo se hace una
     vez en build/ (npm run build) y aquí solo viaja el resultado: ~40 KB de CSS
     con las clases que la web usa de verdad. Con anuncios pagados eso es el
     primer producto visible varios cientos de milisegundos antes.

     VA DESPUÉS DE styles.css a propósito: es el orden que tenía el CDN, y hay
     reglas propias escritas contando con él (styles.css → .drawer-abierto lleva
     !important justo por eso). Si algún día se invierte, hay que revisarlas.

     Si tocas clases en el HTML o el JS, regenera:  cd build && npm run build -->
<link href="<?= sn_e($raiz) ?>tailwind.css?v=<?= sn_v('tailwind.css') ?>" rel="stylesheet"/>
<?= $pagina['extra'] ?? '' ?>
</head>
<?php
}

/** Los cuatro scripts compartidos, con sello de versión. En este orden. */
function sn_scripts(string $raiz = ''): void
{
    foreach (['config.js', 'products.js', 'render-productos.js', 'store.js'] as $js) {
        echo '<script src="' . sn_e($raiz . $js) . '?v=' . sn_v($js) . '"></script>' . "\n";
    }
}

/** Icono de WhatsApp en SVG: Material Symbols no trae logos de marca. */
function sn_simbolo_whatsapp(): void
{
    ?>
<svg class="hidden" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
<symbol id="ico-whatsapp" viewBox="0 0 24 24">
<path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
</symbol>
</svg>
<?php
}
