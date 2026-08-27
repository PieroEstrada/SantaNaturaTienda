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
   ========================================================================== */

declare(strict_types=1);

/** Iconos que usan todas las páginas. Cada página suma los suyos. */
const SN_ICONOS_BASE = [
    'add_shopping_cart', 'apps', 'arrow_forward', 'close', 'dark_mode', 'eco',
    'expand_more', 'light_mode', 'local_shipping', 'menu', 'payments',
    'schedule', 'search', 'sell', 'shopping_bag', 'shopping_cart', 'stars',
    'storefront', 'verified',
];

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
<link rel="canonical" href="<?= sn_e($pagina['canonical']) ?>"/>

<!-- ==========================================================================
     Google Ads — etiqueta de conversión (PENDIENTE DE ACTIVAR)
     --------------------------------------------------------------------------
     Descomenta estas cuatro líneas y cambia AW-XXXXXXXXXX por tu ID de
     conversión; el MISMO valor va en config.js → ADS_CONFIG.conversionId.
     Al estar aquí, se activa de golpe en TODAS las páginas.
     ========================================================================== -->
<!--
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-XXXXXXXXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'AW-XXXXXXXXXX');
</script>
-->

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
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
        try {
            // Cada token de color apunta a una variable CSS con los canales RGB
            // (definidas en styles.css). Así, al alternar la clase .dark en <html>,
            // todo el catálogo cambia de tema sin tocar cada elemento, y las clases
            // con opacidad (bg-surface/90, text-primary/50, …) siguen funcionando.
            const TOKENS_COLOR = [
                "inverse-on-surface", "tertiary-fixed", "on-tertiary-fixed", "primary",
                "primary-container", "on-primary-fixed-variant", "surface-container-highest",
                "on-surface-variant", "tertiary-container", "secondary-fixed", "secondary-fixed-dim",
                "on-secondary-fixed-variant", "primary-fixed", "inverse-primary", "tertiary-fixed-dim",
                "on-secondary-fixed", "on-tertiary-container", "surface-bright", "error-container",
                "on-primary", "secondary", "on-primary-container", "on-secondary-container",
                "surface-container-high", "on-primary-fixed", "background", "secondary-container",
                "on-error-container", "outline", "on-surface", "outline-variant", "surface-container",
                "on-background", "on-error", "surface-container-low", "error", "surface-tint",
                "inverse-surface", "surface-dim", "surface", "on-tertiary", "primary-fixed-dim",
                "on-tertiary-fixed-variant", "surface-variant", "surface-container-lowest",
                "on-secondary", "tertiary"
            ];
            const colors = {};
            TOKENS_COLOR.forEach((t) => { colors[t] = `rgb(var(--c-${t}) / <alpha-value>)`; });

            // Colores de marca que NO cambian entre tema claro y oscuro:
            // el verde oficial de WhatsApp, el dorado de las estrellas de
            // valoración y el blanco puro del pie de página.
            colors["action-whatsapp"] = "#25D366";
            colors["rating-gold"] = "#FFC107";
            colors["botanical-white"] = "#FFFFFF";

            tailwind.config = {
                darkMode: "class",
                theme: {
                    extend: {
                        colors,
                        "borderRadius": {
                            "DEFAULT": "0.5rem",
                            "lg": "0.5rem",
                            "xl": "0.75rem",
                            "full": "9999px"
                        },
                        "spacing": {
                            "xs": "8px",
                            "lg": "40px",
                            "md": "24px",
                            "xl": "64px",
                            "container-max": "1280px",
                            "sm": "16px",
                            "base": "4px",
                            "gutter": "24px"
                        },
                        // Los títulos usan Plus Jakarta Sans (más carácter y
                        // mejor peso en negrita); el cuerpo se queda en Inter.
                        "fontFamily": {
                            "display-lg-mobile": ["Plus Jakarta Sans", "Inter"],
                            "label-md": ["Inter"],
                            "label-caps": ["Inter"],
                            "body-lg": ["Inter"],
                            "headline-md-mobile": ["Plus Jakarta Sans", "Inter"],
                            "headline-md": ["Plus Jakarta Sans", "Inter"],
                            "body-md": ["Inter"],
                            "display-lg": ["Plus Jakarta Sans", "Inter"],
                            "title-lg": ["Plus Jakarta Sans", "Inter"],
                            "title-sm": ["Plus Jakarta Sans", "Inter"]
                        },
                        "fontSize": {
                            "display-lg-mobile": ["36px", {"lineHeight": "42px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                            "label-md": ["14px", {"lineHeight": "20px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                            "label-caps": ["12px", {"lineHeight": "16px", "letterSpacing": "0.1em", "fontWeight": "700"}],
                            "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                            "headline-md-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                            "headline-md": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                            "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                            "display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                            "title-lg": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                            "title-sm": ["20px", {"lineHeight": "28px", "fontWeight": "600"}]
                        }
                    },
                },
            }
        } catch (_e) {}
    </script>
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
