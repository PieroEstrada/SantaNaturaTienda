/* ============================================================================
   Santa Natura — Render de productos (módulo compartido)
   ----------------------------------------------------------------------------
   Todo lo que dibuja una tarjeta de producto vive AQUÍ y en ningún otro sitio.
   Lo usan dos consumidores distintos:

     1. El navegador  — store.js lo llama para pintar el catálogo del index,
        las miniaturas del carrito y la ficha del producto.
     2. Node          — scripts/build-landings.js lo llama para pre-generar el
        HTML de las tarjetas de /packs y /packs/colageno, de modo que esas dos
        páginas lleguen al navegador (y al robot de Google) con los productos
        ya escritos, sin depender de JavaScript.

   Por eso las funciones de este archivo son PURAS: arman cadenas de texto y no
   tocan el DOM ni leen estado global. Todo lo que necesitan llega por parámetro.

   Depende de: config.js (WHATSAPP_NUMERO, mensajeDeProducto) y products.js
   (COLECCIONES). Cárgalo siempre después de esos dos.
   ========================================================================== */

/* --------------------------------------------------------------------------
   Formato
   -------------------------------------------------------------------------- */

/** Formatea un monto como precio peruano: 50 -> "S/ 50.00" */
const soles = (monto) => `S/ ${Number(monto).toFixed(2)}`;

/** Formatea puntos con 2 decimales: 8.33 -> "8.33 pts" */
const pts = (puntos) => `${Number(puntos).toFixed(2)} pts`;

/** Escapa texto antes de insertarlo como HTML. */
const escapar = (texto) => String(texto).replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
));

/* --------------------------------------------------------------------------
   WhatsApp
   -------------------------------------------------------------------------- */

/**
 * Arma el enlace a WhatsApp con el mensaje ya redactado.
 * encodeURIComponent es obligatorio: los nombres del catálogo traen tildes,
 * comas, barras y la «x» de los formatos ("… x 10 g c/u").
 */
const enlaceWhatsApp = (mensaje) =>
    `https://wa.me/${WHATSAPP_NUMERO}?text=${encodeURIComponent(mensaje)}`;

/* --------------------------------------------------------------------------
   Categorías
   --------------------------------------------------------------------------
   Un producto está en VARIAS categorías a la vez (campo `categorias`). La
   relación madre → hijas está en COLECCIONES (products.js).
   -------------------------------------------------------------------------- */

/** Nombres que cuentan para una categoría: ella misma más sus subcategorías. */
function nombresDe(nombre) {
    const c = COLECCIONES.find((x) => x.nombre === nombre);
    return c ? [c.nombre, ...c.hijas] : [nombre];
}

/** ¿El producto entra en esta categoría (o en alguna de sus hijas)? */
function estaEn(producto, nombre) {
    const suyas = producto.categorias || [];
    return nombresDe(nombre).some((n) => suyas.includes(n));
}

/**
 * Orden para elegir QUÉ categoría se muestra en la tarjeta y en la ficha.
 * Primero las que dicen qué ES el producto ("Colágenos", "Batidos") y al final
 * las de escaparate ("Top Ventas", "Favoritos de…"), que no describen nada.
 * Lo que no esté en esta lista queda al final, en el orden que traiga.
 */
const ETIQUETAS_PREFERIDAS = [
    'Packs Colágeno', 'Packs Hombre', 'Packs',
    'Colágenos', 'Batidos', 'Harinas del mar y tierra',
    'Concentrados', 'Bebidas', 'Jarabes de Miel', 'Jarabes naturales',
    'Propóleos', 'Aceites', 'Algarrobina', 'Miel', 'Vinagres',
    'Frotaciones', 'Cuidado del cabello',
    'Cocina Natural', 'Refuerzos', 'Bebidas y concentrados',
    'Detox', 'Descanso y relax', 'Peso / Grasa'
];

function etiquetaCategoria(p) {
    const suyas = p.categorias || [];
    if (!suyas.length) return p.categoria;
    const rango = (c) => {
        const i = ETIQUETAS_PREFERIDAS.indexOf(c);
        return i === -1 ? ETIQUETAS_PREFERIDAS.length : i;
    };
    return [...suyas].sort((a, b) => rango(a) - rango(b))[0];
}

/* --------------------------------------------------------------------------
   Descuento
   --------------------------------------------------------------------------
   NO hay una escala fija de descuentos: cada producto y cada pack trae el suyo
   en el campo `etiqueta_descuento` ("-25%"). Aquí SOLO se lee ese dato para
   poder ordenar; no se calcula, no se infiere y no se inventa ninguno. Un
   producto sin `etiqueta_descuento` no lleva globo y va después en el orden.
   -------------------------------------------------------------------------- */

/** Porcentaje que ya trae el producto, o null si no tiene descuento. */
function descuentoDe(p) {
    const m = /(\d+(?:[.,]\d+)?)/.exec(p.etiqueta_descuento || '');
    return m ? parseFloat(m[1].replace(',', '.')) : null;
}

/**
 * Orden por relevancia comercial para las landings de Ads:
 * primero el mayor descuento; los que no tienen descuento van al final,
 * ordenados por precio descendente (como pidió el encargo).
 */
function porRelevancia(a, b) {
    const A = descuentoDe(a);
    const B = descuentoDe(b);
    if (A !== null && B !== null && A !== B) return B - A;
    if (A !== null && B === null) return -1;
    if (A === null && B !== null) return 1;
    return b.pvp - a.pvp;
}

/* --------------------------------------------------------------------------
   Productos vetados en las landings de pago
   --------------------------------------------------------------------------
   Siguen publicados en el catálogo de la portada; solo NO aparecen en /packs
   ni en /packs/colageno.

   «GARANTIZADO» sobre crecimiento infantil es una promesa de resultado en
   salud, justo lo que las políticas de Google Ads sancionan. No compensa
   arriesgar la cuenta por un pack.
   -------------------------------------------------------------------------- */
const VETADOS_EN_ADS = ['PACK CRECIMIENTO GARANTIZADO DS30'];

const vetado = (p) => VETADOS_EN_ADS.includes(p.producto);

/* --------------------------------------------------------------------------
   Selección de productos de cada landing
   -------------------------------------------------------------------------- */

/** Cuántos productos se muestran arriba del pliegue en cada landing. */
const PRODUCTOS_POR_LANDING = 8;

/**
 * /packs — cobertura de NECESIDADES, no de descuento.
 *
 * Todos los packs vigentes están al mismo 30%, así que ordenar por descuento
 * ya no distingue nada. Lo que decide la compra es si el visitante encuentra
 * SU problema, y a esta landing llegan anuncios de intenciones muy distintas.
 * Por eso se reserva un hueco por familia, y dentro de cada familia gana el
 * más barato: así la primera pantalla no se siente cara.
 *
 * El último hueco es el pack más económico que quede sin usar, para abrir la
 * rejilla con un precio de entrada bajo.
 */
/**
 * `re` casa contra el NOMBRE del pack y manda sobre `cats`. Hace falta porque
 * las categorías se solapan: el PACK MUJER también está en «Para los Huesos»
 * (lleva colágeno y Carti Mix), así que por categoría y precio se colaba en el
 * hueco de articulaciones y dejaba la familia «mujer» vacía. El nombre dice de
 * qué va el pack sin ambigüedad; las categorías quedan como red de seguridad.
 */
const FAMILIAS_PACKS = [
    { clave: 'defensas / invierno',    re: /DEFENSA|INVIERNO|ESCUDO|PROTECTOR/,      cats: ['Para las Defensas', 'Para los Pulmones'] },
    { clave: 'articulaciones',         re: /ARTICULACION|HUESO/,                     cats: ['Para los Huesos'] },
    { clave: 'digestivo / intestinal', re: /DIGESTIV|DIGESTION|INTESTINAL/,          cats: ['Para el Estomágo'] },
    { clave: 'hígado / renal',         re: /HIGADO|H[ÍI]GADO|RENAL/,                 cats: ['Detox', 'Para las Vías Urinarias'] },
    { clave: 'metabolismo / peso',     re: /METABOLISMO|PESO|GRASA|GLUCOSA/,         cats: ['Peso / Grasa'] },
    { clave: 'mujer',                  re: /MUJER/,                                  cats: ['Favoritos de las mujeres'] },
    { clave: 'masculino',              re: /MASCULINO|HOMBRE|VIRILIDAD|KALMAPROSS/,  cats: ['Packs Hombre', 'Favoritos de los hombres'] }
];

/** Más barato primero: dentro de cada familia gana el de menor ticket. */
const porPrecio = (a, b) => a.pvp - b.pvp;

function seleccionPacks(productos, limite = PRODUCTOS_POR_LANDING) {
    const tiene = (p, n) => (p.categorias || []).includes(n);
    const packs = productos.filter((p) => tiene(p, 'Packs') && !vetado(p));

    const elegidos = [];
    const yaEsta = (p) => elegidos.some((e) => e.id === p.id);
    const libres = () => packs.filter((p) => !yaEsta(p));

    for (const fam of FAMILIAS_PACKS) {
        if (elegidos.length >= limite) break;
        // 1) por nombre; 2) si no hay, por categoría.
        const porNombre = libres().filter((p) => fam.re.test(p.producto)).sort(porPrecio);
        const porCat = libres().filter((p) => fam.cats.some((c) => tiene(p, c))).sort(porPrecio);
        const cand = porNombre[0] || porCat[0];
        if (cand) elegidos.push(cand);
    }

    // Últimos huecos: el pack de ticket más bajo que quede sin usar, para que
    // la primera pantalla no se sienta cara.
    while (elegidos.length < limite) {
        const cand = libres().sort(porPrecio)[0];
        if (!cand) break;
        elegidos.push(cand);
    }

    return elegidos.sort(porPrecio);
}

/**
 * /packs/colageno — packs que llevan colágeno más los colágenos individuales,
 * para que quien busca «colágeno santa natura» encuentre también el producto
 * suelto y no solo packs de varios cientos de soles.
 */
function seleccionColageno(productos, limite = PRODUCTOS_POR_LANDING) {
    const tiene = (p, n) => (p.categorias || []).includes(n);
    const vivos = productos.filter((p) => !vetado(p));

    const individuales = vivos
        .filter((p) => tiene(p, 'Colágenos') && !tiene(p, 'Packs'))
        .sort(porPrecio);

    const packs = vivos
        .filter((p) => tiene(p, 'Packs Colágeno'))
        .sort(porPrecio);

    const cupoIndividuales = Math.min(individuales.length, limite);
    return packs.slice(0, limite - cupoIndividuales)
        .concat(individuales.slice(0, cupoIndividuales))
        .slice(0, limite)
        .sort(porPrecio);
}

/* --------------------------------------------------------------------------
   Tarjetas
   -------------------------------------------------------------------------- */

/**
 * Imagen del producto, o placeholder de marca si todavía no tiene foto.
 * `compacto` se usa en miniaturas (carrito), donde no cabe el texto de marca.
 */
/**
 * @param opciones.ansiosa  true = sin loading="lazy". Se usa en las primeras
 *                          tarjetas de las landings: están arriba del pliegue,
 *                          y diferir su descarga retrasa el LCP en móvil, que
 *                          es de donde llega casi todo el tráfico de Ads.
 * @param opciones.prioritaria true = fetchpriority="high". Solo la PRIMERA
 *                          imagen de la página; marcar varias no prioriza nada.
 */
function bloqueImagen(producto, alto = 'h-52', compacto = false, opciones = {}) {
    // El marco siempre se dibuja con el placeholder de marca detrás. Si el
    // producto tiene foto, va encima; y si el archivo no existe (nombre mal
    // escrito, imagen aún no subida) el onerror la quita y vuelve a verse el
    // placeholder, sin romper la altura de la tarjeta ni mostrar el icono de
    // imagen rota del navegador.
    const marca = compacto
        ? `<span class="material-symbols-outlined text-2xl text-primary/40" style="font-variation-settings: 'FILL' 1;">eco</span>`
        : `<span class="material-symbols-outlined text-5xl text-primary/40" style="font-variation-settings: 'FILL' 1;">eco</span>
           <span class="font-label-md text-[10px] uppercase tracking-wide text-outline">Santa Natura</span>`;

    // object-contain (y no cover) para que el envase se vea completo: las fotos
    // del catálogo son cuadradas y con fondo blanco, así que recortar los bordes
    // cortaría la etiqueta.
    // width/height fijos: las fotos del catálogo son cuadradas de 300x300 y
    // declararlo evita que la tarjeta salte de alto mientras carga (CLS).
    const carga = opciones.ansiosa ? '' : ' loading="lazy"';
    const prioridad = opciones.prioritaria ? ' fetchpriority="high"' : '';

    const foto = producto.imagen
        ? `<img src="${escapar(rutaImagen(producto.imagen))}" alt="${escapar(producto.producto)}"
                width="300" height="300"${carga}${prioridad} onerror="this.remove()"
                class="absolute inset-0 w-full h-full object-contain ${compacto ? '' : 'p-2'} bg-white transition-transform duration-500 group-hover:scale-105"/>`
        : '';

    return `<div class="img-placeholder relative overflow-hidden w-full ${alto} flex flex-col items-center justify-center gap-xs bg-surface-container">
                ${marca}
                ${foto}
            </div>`;
}

/**
 * Prefijo para las rutas de img/. Las landings viven en subcarpetas (/packs/,
 * /packs/colageno/), así que necesitan subir uno o dos niveles. En el navegador
 * lo dice <html data-raiz="../">; en Node lo fija el build.
 */
let RAIZ_ACTIVA = '';
function fijarRaiz(raiz) { RAIZ_ACTIVA = raiz || ''; }
function rutaImagen(ruta) {
    // Las URLs absolutas (http…) se dejan tal cual.
    return /^(https?:)?\/\//.test(ruta) ? ruta : RAIZ_ACTIVA + ruta;
}

/**
 * Tarjeta de producto del catálogo.
 *
 * opciones.enCarrito   número de unidades ya en el pedido (globo verde).
 * opciones.ctaWhatsApp añade un botón directo a WhatsApp con el nombre REAL del
 *                      producto en el mensaje. Se usa solo en las landings de
 *                      Ads, donde el objetivo es abrir el chat cuanto antes.
 * opciones.contenido   muestra el «Contiene: …» del producto (campo
 *                      `descripcion`). En las landings de packs es el dato que
 *                      decide la compra: sin él la tarjeta solo dice un nombre
 *                      en mayúsculas y un precio, y no se sabe qué se lleva.
 */
function tarjetaProducto(p, opciones = {}) {
    const enCarrito = opciones.enCarrito || 0;

    // El ahorro sale SIEMPRE de la resta de los dos precios del dato. Si el
    // producto no trae precio_original, no hay ahorro que enseñar: ni badge,
    // ni tachado, ni «Ahorras». Nada estimado.
    // Solo se dibuja en las landings (opciones.ahorro): en la portada la
    // tarjeta conserva su «Precio de venta al público» de siempre.
    const ahorro = (opciones.ahorro && p.precio_original) ? p.precio_original - p.pvp : null;

    const contenido = opciones.contenido && p.descripcion
        ? `<p class="text-[11px] text-on-surface-variant leading-snug linea-3 mb-sm" title="${escapar(p.descripcion)}">${escapar(p.descripcion)}</p>`
        : '';

    // La etiqueta del CTA es corta a propósito: a 390 px la tarjeta mide
    // 156 px y "Pedir por WhatsApp" se cortaba en "Pedir por Whats…". El icono
    // y el verde ya dicen por dónde se pide.
    const ctaWhatsApp = opciones.ctaWhatsApp
        ? `<a class="w-full bg-action-whatsapp text-white py-2 rounded-full font-label-caps text-[11px] md:text-xs hover:brightness-105 transition-all flex items-center justify-center gap-xs active:scale-[0.98]"
              href="${escapar(enlaceWhatsApp(mensajeDeProducto(p.producto)))}"
              target="_blank" rel="noopener"
              data-wa-origen="producto" data-wa-producto="${escapar(p.producto)}"
              onclick="event.stopPropagation()">
               <svg class="w-4 h-4 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
               <span class="truncate">Pedir ahora</span>
           </a>`
        : '';

    return `
    <article id="producto-${p.id}" class="bg-surface rounded-3xl shadow-sm overflow-hidden border border-outline-variant/50 group hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col cursor-pointer"
             onclick="abrirFichaProducto(${p.id})">
        <div class="relative overflow-hidden bg-surface-container">
            ${bloqueImagen(p, 'h-40 sm:h-48 md:h-52', false, {
                ansiosa: opciones.ansiosa,
                prioritaria: opciones.prioritaria
            })}
            <div class="absolute top-2 left-2 md:top-3 md:left-3 bg-surface/90 backdrop-blur text-primary px-2 md:px-3 py-1 rounded-full text-[9px] md:text-[10px] font-label-caps uppercase tracking-wider max-w-[70%] truncate">
                ${escapar(etiquetaCategoria(p))}
            </div>
            ${p.etiqueta_descuento ? `<div class="absolute top-2 right-2 md:top-3 md:right-3 bg-error text-on-error px-2 py-1 rounded-full text-[10px] font-bold shadow-md">${escapar(p.etiqueta_descuento)}</div>` : ''}
            ${enCarrito ? `<div class="absolute bottom-2 right-2 md:bottom-3 md:right-3 bg-primary text-white w-7 h-7 rounded-full grid place-items-center text-xs font-bold shadow">${enCarrito}</div>` : ''}
        </div>

        <div class="p-sm md:p-md flex-1 flex flex-col">
            <h3 class="font-headline-md text-[13px] md:text-sm text-on-surface leading-snug linea-2 mb-xs" title="${escapar(p.producto)}">
                ${escapar(p.producto)}
            </h3>

            ${contenido}

            <div class="flex items-center gap-1 text-primary mb-sm">
                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">stars</span>
                <span class="font-label-caps text-xs">${pts(p.puntos)}</span>
            </div>

            <div class="mt-auto space-y-sm">
                <div>
                    <div class="flex items-baseline flex-wrap gap-x-2 gap-y-0">
                        <span class="text-lg md:text-xl font-bold text-on-surface">${soles(p.pvp)}</span>
                        ${p.precio_original ? `<del class="text-on-surface-variant text-xs md:text-sm leading-tight">${soles(p.precio_original)}</del>` : ''}
                    </div>
                    ${ahorro !== null
                        ? `<p class="text-[11px] font-bold text-error leading-tight mt-0.5">Ahorras ${soles(ahorro)}</p>`
                        : '<p class="text-[11px] text-on-surface-variant leading-tight mt-0.5">Precio de venta al público</p>'}
                </div>

                <button class="w-full bg-primary text-on-primary py-2 rounded-full font-label-caps text-[11px] md:text-xs hover:brightness-110 active:scale-[0.98] transition-all flex items-center justify-center gap-xs"
                        data-add-producto="${escapar(p.producto)}"
                        onclick="event.stopPropagation(); agregarAlCarrito(${p.id})">
                    <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                    <span class="truncate">Agregar al pedido</span>
                </button>
                ${ctaWhatsApp}
            </div>
        </div>
    </article>`;
}

/* --------------------------------------------------------------------------
   JSON-LD (schema.org/ItemList con Product)
   --------------------------------------------------------------------------
   Se genera desde products.js, así que refleja siempre los precios reales.
   Solo se escribe `priceValidUntil` si el producto trae descuento vigente…
   y ni eso: no inventamos fechas. Se omite.
   -------------------------------------------------------------------------- */

function jsonLdItemList(productos, urlPagina, nombreLista) {
    const items = productos.map((p, i) => {
        const producto = {
            '@type': 'Product',
            name: p.producto,
            sku: String(p.id),
            brand: { '@type': 'Brand', name: 'Santa Natura' },
            url: `${urlPagina}#producto-${p.id}`,
            offers: {
                '@type': 'Offer',
                price: Number(p.pvp).toFixed(2),
                priceCurrency: 'PEN',
                availability: 'https://schema.org/InStock',
                url: `${urlPagina}#producto-${p.id}`,
                seller: { '@type': 'Organization', name: 'Santa Natura' }
            }
        };
        if (p.imagen) producto.image = `${SITE_URL}/${p.imagen}`;
        if (p.descripcion) producto.description = p.descripcion;

        return { '@type': 'ListItem', position: i + 1, item: producto };
    });

    return {
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        name: nombreLista,
        url: urlPagina,
        numberOfItems: items.length,
        itemListElement: items
    };
}

/* Exporta para scripts/build-landings.js (en el navegador `module` no existe). */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        soles, pts, escapar, enlaceWhatsApp,
        nombresDe, estaEn, etiquetaCategoria, ETIQUETAS_PREFERIDAS,
        descuentoDe, porRelevancia,
        PRODUCTOS_POR_LANDING, seleccionPacks, seleccionColageno,
        bloqueImagen, tarjetaProducto, fijarRaiz, rutaImagen,
        jsonLdItemList
    };
}
