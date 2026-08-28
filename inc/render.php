<?php
/* ============================================================================
   Santa Natura — Render de las landings en el servidor
   ----------------------------------------------------------------------------
   POR QUÉ EXISTE ESTE ARCHIVO

   Antes las landings eran HTML fijo que generaba scripts/build-landings.js con
   Node. Al añadir el panel de administración eso dejó de servir: si cambias un
   precio en el panel, el HTML pre-generado se queda con el precio viejo hasta
   que alguien vuelva a correr el build a mano — y con tráfico de Ads pagado
   eso significa pagar clics para enseñar un precio que ya no existe.

   Ahora /packs y /packs/colageno son PHP y arman las tarjetas en cada visita
   leyendo products.js. El HTML sigue llegando completo al navegador y al robot
   de Google (que es lo que pedía el encargo), pero no hay paso de compilación
   ni ventana de desfase: guardas en el panel y la landing ya está actualizada.

   OJO AL MANTENER: la tarjeta se dibuja en dos sitios, aquí (landings, en el
   servidor) y en render-productos.js (portada, en el navegador). Si cambias el
   maquetado de una, cambia la otra. `node scripts/verificar-paridad.js`
   compara las dos salidas y avisa si se han separado.
   ========================================================================== */

declare(strict_types=1);
require_once __DIR__ . '/catalogo.php';

/* --------------------------------------------------------------------------
   Configuración compartida con el navegador (config.js)
   -------------------------------------------------------------------------- */

function sn_config_js(string $constante, string $porDefecto = ''): string
{
    static $texto = null;
    if ($texto === null) {
        $texto = (string) @file_get_contents(SN_RAIZ . '/config.js');
    }
    if (preg_match('/const\s+' . preg_quote($constante, '/') . "\s*=\s*'([^']*)'/", $texto, $m)) {
        return $m[1];
    }
    return $porDefecto;
}

function sn_whatsapp(): string { return sn_config_js('WHATSAPP_NUMERO'); }
function sn_site_url(): string { return rtrim(sn_config_js('SITE_URL'), '/'); }

function sn_enlace_whatsapp(string $mensaje): string
{
    return 'https://wa.me/' . sn_whatsapp() . '?text=' . rawurlencode($mensaje);
}

function sn_soles(float $n): string { return 'S/ ' . number_format($n, 2, '.', ''); }
function sn_pts(float $n): string   { return number_format($n, 2, '.', '') . ' pts'; }
function sn_e(?string $s): string   { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

/* --------------------------------------------------------------------------
   Categorías
   -------------------------------------------------------------------------- */

const SN_ETIQUETAS_PREFERIDAS = [
    'Packs Colágeno', 'Packs Hombre', 'Packs',
    'Colágenos', 'Batidos', 'Harinas del mar y tierra',
    'Concentrados', 'Bebidas', 'Jarabes de Miel', 'Jarabes naturales',
    'Propóleos', 'Aceites', 'Algarrobina', 'Miel', 'Vinagres',
    'Frotaciones', 'Cuidado del cabello',
    'Cocina Natural', 'Refuerzos', 'Bebidas y concentrados',
    'Detox', 'Descanso y relax', 'Peso / Grasa',
];

function sn_etiqueta_categoria(array $p): string
{
    $suyas = $p['categorias'] ?? [];
    if (!$suyas) {
        return (string) ($p['categoria'] ?? '');
    }
    usort($suyas, static function (string $a, string $b): int {
        $ia = array_search($a, SN_ETIQUETAS_PREFERIDAS, true);
        $ib = array_search($b, SN_ETIQUETAS_PREFERIDAS, true);
        $ia = $ia === false ? PHP_INT_MAX : $ia;
        $ib = $ib === false ? PHP_INT_MAX : $ib;
        return $ia <=> $ib;
    });
    return $suyas[0];
}

function sn_tiene(array $p, string $cat): bool
{
    return in_array($cat, $p['categorias'] ?? [], true);
}

/* --------------------------------------------------------------------------
   Selección de cada landing — mismas reglas que render-productos.js
   -------------------------------------------------------------------------- */

/** Vetados en las landings de pago: siguen en el catálogo de la portada. */
const SN_VETADOS_EN_ADS = ['PACK CRECIMIENTO GARANTIZADO DS30'];

function sn_vetado_en_ads(array $p): bool
{
    return in_array($p['producto'] ?? '', SN_VETADOS_EN_ADS, true);
}

const SN_POR_LANDING = 8;

/**
 * Familias de necesidad. `re` casa contra el NOMBRE y manda sobre `cats`,
 * porque las categorías se solapan: el PACK MUJER también está en
 * «Para los Huesos» y por categoría se colaba en el hueco de articulaciones.
 */
const SN_FAMILIAS = [
    ['re' => '/DEFENSA|INVIERNO|ESCUDO|PROTECTOR/u',       'cats' => ['Para las Defensas', 'Para los Pulmones']],
    ['re' => '/ARTICULACION|HUESO/u',                      'cats' => ['Para los Huesos']],
    ['re' => '/DIGESTIV|DIGESTION|INTESTINAL/u',           'cats' => ['Para el Estomágo']],
    ['re' => '/HIGADO|HÍGADO|RENAL/u',                     'cats' => ['Detox', 'Para las Vías Urinarias']],
    ['re' => '/METABOLISMO|PESO|GRASA|GLUCOSA/u',          'cats' => ['Peso / Grasa']],
    ['re' => '/MUJER/u',                                   'cats' => ['Favoritos de las mujeres']],
    ['re' => '/MASCULINO|HOMBRE|VIRILIDAD|KALMAPROSS/u',   'cats' => ['Packs Hombre', 'Favoritos de los hombres']],
];

function sn_por_precio(array $a, array $b): int
{
    return $a['pvp'] <=> $b['pvp'];
}

function sn_seleccion_packs(array $catalogo, int $limite = SN_POR_LANDING): array
{
    $packs = array_values(array_filter(
        $catalogo,
        static fn(array $p) => sn_tiene($p, 'Packs') && !sn_vetado_en_ads($p)
    ));

    $elegidos = [];
    $ids = static fn(array $lista) => array_map(static fn($p) => $p['id'], $lista);
    $libres = static function () use (&$elegidos, $packs, $ids) {
        $usados = $ids($elegidos);
        $l = array_values(array_filter($packs, static fn($p) => !in_array($p['id'], $usados, true)));
        usort($l, 'sn_por_precio');
        return $l;
    };

    foreach (SN_FAMILIAS as $fam) {
        if (count($elegidos) >= $limite) break;
        $l = $libres();
        $porNombre = array_values(array_filter($l, static fn($p) => (bool) preg_match($fam['re'], $p['producto'])));
        $porCat    = array_values(array_filter($l, static function ($p) use ($fam) {
            foreach ($fam['cats'] as $c) { if (sn_tiene($p, $c)) return true; }
            return false;
        }));
        $cand = $porNombre[0] ?? $porCat[0] ?? null;
        if ($cand) $elegidos[] = $cand;
    }

    // Últimos huecos: el de ticket más bajo que quede.
    while (count($elegidos) < $limite) {
        $l = $libres();
        if (!$l) break;
        $elegidos[] = $l[0];
    }

    usort($elegidos, 'sn_por_precio');
    return $elegidos;
}

function sn_seleccion_colageno(array $catalogo, int $limite = SN_POR_LANDING): array
{
    $vivos = array_values(array_filter($catalogo, static fn(array $p) => !sn_vetado_en_ads($p)));

    $individuales = array_values(array_filter($vivos, static fn($p) => sn_tiene($p, 'Colágenos') && !sn_tiene($p, 'Packs')));
    $packs        = array_values(array_filter($vivos, static fn($p) => sn_tiene($p, 'Packs Colágeno')));
    usort($individuales, 'sn_por_precio');
    usort($packs, 'sn_por_precio');

    $cupo = min(count($individuales), $limite);
    $sel  = array_merge(
        array_slice($packs, 0, max(0, $limite - $cupo)),
        array_slice($individuales, 0, $cupo)
    );
    $sel = array_slice($sel, 0, $limite);
    usort($sel, 'sn_por_precio');
    return $sel;
}

/* --------------------------------------------------------------------------
   Tarjeta
   -------------------------------------------------------------------------- */

function sn_bloque_imagen(array $p, string $raiz, bool $ansiosa, bool $prioritaria): string
{
    $marca = '<span class="material-symbols-outlined text-5xl text-primary/40" style="font-variation-settings: \'FILL\' 1;">eco</span>
           <span class="font-label-md text-[10px] uppercase tracking-wide text-outline">Santa Natura</span>';

    $foto = '';
    if (($p['imagen'] ?? '') !== '') {
        $carga     = $ansiosa ? '' : ' loading="lazy"';
        $prioridad = $prioritaria ? ' fetchpriority="high"' : '';
        $src       = preg_match('#^(https?:)?//#', $p['imagen']) ? $p['imagen'] : $raiz . $p['imagen'];
        $foto = '<img src="' . sn_e($src) . '" alt="' . sn_e($p['producto']) . '"
                width="300" height="300"' . $carga . $prioridad . ' onerror="this.remove()"
                class="absolute inset-0 w-full h-full object-contain p-2 bg-white transition-transform duration-500 group-hover:scale-105"/>';
    }

    return '<div class="img-placeholder relative overflow-hidden w-full h-40 sm:h-48 md:h-52 flex flex-col items-center justify-center gap-xs bg-surface-container">
                ' . $marca . '
                ' . $foto . '
            </div>';
}

/**
 * Tarjeta de producto. Espejo exacto de tarjetaProducto() de
 * render-productos.js; las opciones se llaman igual en los dos lados.
 *
 *   ctaWhatsApp  botón directo a WhatsApp con el nombre real del producto.
 *   contenido    muestra el «Contiene: …» (campo descripcion).
 *   ahorro       muestra «Ahorras S/ X» en vez de «Precio de venta al público».
 *   ansiosa      sin loading="lazy" (tarjetas de arriba del pliegue).
 *   prioritaria  fetchpriority="high" (solo la primera imagen de la página).
 *   enCarrito    unidades ya en el pedido, para el globo verde.
 *
 * Las landings activan las tres primeras; la portada no, para que su tarjeta
 * siga siendo la de siempre.
 */
function sn_tarjeta(array $p, string $raiz, array $opciones = []): string
{
    $ansiosa     = (bool) ($opciones['ansiosa'] ?? false);
    $prioritaria = (bool) ($opciones['prioritaria'] ?? false);
    $enCarrito   = (int) ($opciones['enCarrito'] ?? 0);

    // El ahorro sale de la resta de los dos precios del dato. Sin precio base
    // no hay badge, ni tachado, ni «Ahorras». Nada estimado.
    $ahorro = (($opciones['ahorro'] ?? false) && isset($p['precio_original']))
        ? (float) $p['precio_original'] - (float) $p['pvp']
        : null;

    $contenido = (($opciones['contenido'] ?? false) && ($p['descripcion'] ?? '') !== '')
        ? '<p class="text-[11px] text-on-surface-variant leading-snug linea-3 mb-sm" title="' . sn_e($p['descripcion']) . '">' . sn_e($p['descripcion']) . '</p>'
        : '';

    $cta = '';
    if ($opciones['ctaWhatsApp'] ?? false) {
        $mensaje = 'Hola, quiero información sobre: ' . $p['producto'];
        $cta = '<a class="w-full bg-action-whatsapp text-white py-2 rounded-full font-label-caps text-[11px] md:text-xs hover:brightness-105 transition-all flex items-center justify-center gap-xs active:scale-[0.98]"
              href="' . sn_e(sn_enlace_whatsapp($mensaje)) . '"
              target="_blank" rel="noopener"
              data-wa-origen="producto" data-wa-producto="' . sn_e($p['producto']) . '"
              onclick="event.stopPropagation()">
               <svg class="w-4 h-4 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
               <span class="truncate">Pedir ahora</span>
           </a>';
    }

    $globo = $enCarrito > 0
        ? '<div class="absolute bottom-2 right-2 md:bottom-3 md:right-3 bg-primary text-white w-7 h-7 rounded-full grid place-items-center text-xs font-bold shadow">' . $enCarrito . '</div>'
        : '';

    $badge = ($p['etiqueta_descuento'] ?? '') !== ''
        ? '<div class="absolute top-2 right-2 md:top-3 md:right-3 bg-error text-on-error px-2 py-1 rounded-full text-[10px] font-bold shadow-md">' . sn_e($p['etiqueta_descuento']) . '</div>'
        : '';

    $tachado = isset($p['precio_original'])
        ? '<del class="text-on-surface-variant text-xs md:text-sm leading-tight">' . sn_soles((float) $p['precio_original']) . '</del>'
        : '';

    $lineaAhorro = $ahorro !== null
        ? '<p class="text-[11px] font-bold text-error leading-tight mt-0.5">Ahorras ' . sn_soles($ahorro) . '</p>'
        : '<p class="text-[11px] text-on-surface-variant leading-tight mt-0.5">Precio de venta al público</p>';

    return '
    <article id="producto-' . (int) $p['id'] . '" class="bg-surface rounded-3xl shadow-sm overflow-hidden border border-outline-variant/50 group hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col cursor-pointer"
             onclick="abrirFichaProducto(' . (int) $p['id'] . ')">
        <div class="relative overflow-hidden bg-surface-container">
            ' . sn_bloque_imagen($p, $raiz, $ansiosa, $prioritaria) . '
            <div class="absolute top-2 left-2 md:top-3 md:left-3 bg-surface/90 backdrop-blur text-primary px-2 md:px-3 py-1 rounded-full text-[9px] md:text-[10px] font-label-caps uppercase tracking-wider max-w-[70%] truncate">
                ' . sn_e(sn_etiqueta_categoria($p)) . '
            </div>
            ' . $badge . '
            ' . $globo . '
        </div>

        <div class="p-sm md:p-md flex-1 flex flex-col">
            <h3 class="font-headline-md text-[13px] md:text-sm text-on-surface leading-snug linea-2 mb-xs" title="' . sn_e($p['producto']) . '">
                ' . sn_e($p['producto']) . '
            </h3>

            ' . $contenido . '

            <div class="flex items-center gap-1 text-primary mb-sm">
                <span class="material-symbols-outlined text-sm" style="font-variation-settings: \'FILL\' 1;">stars</span>
                <span class="font-label-caps text-xs">' . sn_pts((float) $p['puntos']) . '</span>
            </div>

            <div class="mt-auto space-y-sm">
                <div>
                    <div class="flex items-baseline flex-wrap gap-x-2 gap-y-0">
                        <span class="text-lg md:text-xl font-bold text-on-surface">' . sn_soles((float) $p['pvp']) . '</span>
                        ' . $tachado . '
                    </div>
                    ' . $lineaAhorro . '
                </div>

                <button class="w-full bg-primary text-on-primary py-2 rounded-full font-label-caps text-[11px] md:text-xs hover:brightness-110 active:scale-[0.98] transition-all flex items-center justify-center gap-xs"
                        data-add-producto="' . sn_e($p['producto']) . '"
                        onclick="event.stopPropagation(); agregarAlCarrito(' . (int) $p['id'] . ')">
                    <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                    <span class="truncate">Agregar al pedido</span>
                </button>
                ' . $cta . '
            </div>
        </div>
    </article>';
}

/**
 * Rejilla de una landing. Las 4 primeras tarjetas están arriba del pliegue:
 * sin loading="lazy", y la primera además con fetchpriority="high".
 */
function sn_rejilla(array $seleccion, string $raiz): string
{
    $html = '';
    foreach (array_values($seleccion) as $i => $p) {
        $html .= sn_tarjeta($p, $raiz, [
            'ctaWhatsApp' => true,
            'contenido'   => true,
            'ahorro'      => true,
            'ansiosa'     => $i < 4,
            'prioritaria' => $i === 0,
        ]) . "\n";
    }
    return $html;
}

/**
 * Primera página del catálogo de la portada, ya escrita en el HTML.
 *
 * store.js la repinta al cargar (necesita filtrar, paginar y buscar sin
 * recargar), pero el robot de Google y quien llegue con el JavaScript aún sin
 * ejecutar ven los productos igual. Antes esa rejilla se servía vacía.
 *
 * La tarjeta va SIN los extras de las landings, para que la portada conserve
 * exactamente el mismo diseño de siempre.
 */
function sn_rejilla_portada(array $catalogo, int $porPagina = 24): string
{
    $html = '';
    foreach (array_slice(array_values($catalogo), 0, $porPagina) as $i => $p) {
        $html .= sn_tarjeta($p, '', [
            'ansiosa'     => $i < 4,
            'prioritaria' => false,   // en la portada la prioridad es del hero
        ]) . "\n";
    }
    return $html;
}

/* --------------------------------------------------------------------------
   Franja de oferta y bloque de cobertura
   -------------------------------------------------------------------------- */

/**
 * La frase se comprueba contra los datos: si algún pack de la selección no
 * está al 30%, no se publica la promesa, se muestra un texto neutro. Así el
 * panel no puede dejar la landing anunciando un descuento que no existe.
 */
function sn_franja_oferta(array $seleccion): string
{
    $packs   = array_values(array_filter($seleccion, static fn($p) => sn_tiene($p, 'Packs')));
    $conBase = array_values(array_filter($packs, static fn($p) => isset($p['precio_original'])));

    $pct = array_values(array_unique(array_map(
        static fn($p) => (int) round((1 - $p['pvp'] / $p['precio_original']) * 100),
        $conBase
    )));

    if (count($pct) === 1 && $pct[0] === 30 && $conBase) {
        $ahorroMin = min(array_map(static fn($p) => $p['precio_original'] - $p['pvp'], $conBase));
        $texto = 'Todos los packs con 30 % de descuento';
        $extra = '<span class="font-label-caps text-[11px] bg-white/20 rounded-full px-3 py-1">Ahorras desde ' . sn_soles($ahorroMin) . '</span>';
    } else {
        // Los descuentos dejaron de ser uniformes: se dice lo genérico y cierto.
        $texto = 'Packs con descuento · precios vigentes';
        $extra = '';
    }

    return '<div class="bg-error text-on-error rounded-2xl px-md py-3 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-center shadow-md">
<span class="material-symbols-outlined text-xl" style="font-variation-settings: \'FILL\' 1;">sell</span>
<span class="font-title-sm text-base md:text-lg">' . $texto . '</span>
' . $extra . '
</div>';
}

function sn_bloque_cobertura(): string
{
    $punto = static fn(string $ico, string $tit, string $det) => '
<div class="flex items-start gap-sm">
<span class="material-symbols-outlined text-2xl text-primary shrink-0" style="font-variation-settings: \'FILL\' 1;">' . $ico . '</span>
<div>
<p class="font-title-sm text-sm text-on-surface">' . $tit . '</p>
<p class="font-body-md text-[13px] text-on-surface-variant leading-snug">' . $det . '</p>
</div>
</div>';

    return '<div class="bg-surface-container-low border border-outline-variant/50 rounded-3xl p-md md:p-lg grid grid-cols-1 sm:grid-cols-3 gap-md">'
        . $punto('local_shipping', 'Envíos a todo el Perú',
            'Lima, Ayacucho, Tarapoto, Huánuco y el resto del país. Recojo en tienda donde la haya.')
        . $punto('schedule', 'Atención de 8:00 a 23:00',
            'Todos los días, por WhatsApp. Te respondemos y coordinamos la entrega contigo.')
        . $punto('payments', 'Pago contra entrega en Lima',
            'En provincias, Yape, Plin o transferencia bancaria.')
        . '</div>';
}

/* --------------------------------------------------------------------------
   JSON-LD
   -------------------------------------------------------------------------- */

function sn_jsonld(array $seleccion, string $urlPagina, string $nombreLista): string
{
    $items = [];
    foreach (array_values($seleccion) as $i => $p) {
        $producto = [
            '@type' => 'Product',
            'name'  => $p['producto'],
            'sku'   => (string) $p['id'],
            'brand' => ['@type' => 'Brand', 'name' => 'Santa Natura'],
            'url'   => $urlPagina . '#producto-' . $p['id'],
            'offers' => [
                '@type'         => 'Offer',
                'price'         => number_format((float) $p['pvp'], 2, '.', ''),
                'priceCurrency' => 'PEN',
                'availability'  => 'https://schema.org/InStock',
                'url'           => $urlPagina . '#producto-' . $p['id'],
                'seller'        => ['@type' => 'Organization', 'name' => 'Santa Natura'],
            ],
        ];
        if (($p['imagen'] ?? '') !== '')      { $producto['image'] = sn_site_url() . '/' . $p['imagen']; }
        if (($p['descripcion'] ?? '') !== '') { $producto['description'] = $p['descripcion']; }

        $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'item' => $producto];
    }

    $lista = [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => $nombreLista,
        'url'             => $urlPagina,
        'numberOfItems'   => count($items),
        'itemListElement' => $items,
    ];

    return '<script type="application/ld+json">' . "\n"
        . json_encode($lista, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . "\n" . '</script>';
}

/** Hash de contenido para el ?v= de los assets: rompe la caché al cambiarlos. */
function sn_v(string $archivo): string
{
    $ruta = SN_RAIZ . '/' . $archivo;
    return is_file($ruta) ? substr(sha1_file($ruta) ?: '', 0, 8) : '0';
}
