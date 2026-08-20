/* ============================================================================
   Santa Natura — Lógica de la tienda
   ----------------------------------------------------------------------------
   Modelo de venta: catálogo interactivo SIN pasarela de pago. El carrito arma
   un resumen del pedido y redirige a WhatsApp para cerrar la venta.

   Depende de products.js (constantes PRODUCTS y COLECCIONES).
   ========================================================================== */

/* --------------------------------------------------------------------------
   1. Configuración editable
   -------------------------------------------------------------------------- */

const CONFIG = {
    // Número de WhatsApp del asesor, con código de país y sin signos ni espacios.
    // Se usa en TODOS los botones de la web. Cambiarlo aquí lo cambia en todos.
    whatsapp: '51923729480',

    // Aviso de precios especiales por cantidad. Se muestra en la ficha del
    // producto, en el carrito y viaja dentro del mensaje de WhatsApp.
    notaCantidad: '¿Llevas mayor cantidad? Solicita tu precio especial con descuento al enviar tu pedido por WhatsApp.',

    // Imagen grande del Hero (columna derecha de la portada).
    // Cambia SOLO esta línea para poner la foto real de tus productos: acepta
    // una URL (https://…) o una ruta dentro del proyecto ('img/hero.jpg').
    // Se recomienda una foto horizontal de al menos 1200 px de ancho.
    heroImagen: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBqG9y0bjIcgMdE3FMn7UHAU_LGr2bVGAs7xwFENL-5IH5DC2cOZIkyKiFtagOBYwt0LvkEDQC5SlM4_ocueZob4EYRQDuU52bOSLWaNmsNkMhmlvI_MCfBEBBXPLV_B3yg9u1jduE73NscYHGLICStSb8_ptn_7E2ksWBNFUf96Kg6QwDClJwTYeJBD1_NyJi8XcCcYKX7n6EkhoTX4FfeHCOWQy57sNp0-v_XE0QReor8oXLWHqV_tA',

    // Texto alternativo de esa imagen (accesibilidad y SEO).
    heroImagenAlt: 'Productos naturales Santa Natura',

    // Clave con la que se guarda el carrito en el navegador del cliente.
    storageKey: 'sn_carrito_v1'
};

/* --------------------------------------------------------------------------
   2. Utilidades
   -------------------------------------------------------------------------- */

/**
 * Igual que soles(), pero con separador de miles y sin decimales cuando el
 * monto es exacto: 1050 -> "S/ 1,050". SOLO para mostrar en pantalla; los
 * cálculos y los mensajes de WhatsApp siguen usando soles(), que es el
 * formato con el que el asesor está acostumbrado a leer los importes.
 */
const solesEnPantalla = (monto) => {
    const n = Number(monto);
    const decimales = Number.isInteger(n) ? 0 : 2;
    return `S/ ${n.toLocaleString('es-PE', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: 2
    })}`;
};

/** Formatea un monto como precio peruano: 50 -> "S/ 50.00" */
const soles = (monto) => `S/ ${Number(monto).toFixed(2)}`;

/** Formatea puntos con 2 decimales: 8.33 -> "8.33 pts" */
const pts = (puntos) => `${Number(puntos).toFixed(2)} pts`;

/** Quita tildes y pasa a minúsculas, para que el buscador sea tolerante. */
const normalizar = (texto) => String(texto)
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '');

/** Escapa texto antes de insertarlo como HTML. */
const escapar = (texto) => String(texto).replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
));

/** Busca un producto por su id. */
const buscarProducto = (id) => PRODUCTS.find((p) => p.id === Number(id));

/** Abre WhatsApp con un mensaje ya redactado. */
const enlaceWhatsApp = (mensaje) =>
    `https://wa.me/${CONFIG.whatsapp}?text=${encodeURIComponent(mensaje)}`;

/* --------------------------------------------------------------------------
   3. Estado del catálogo y del carrito
   -------------------------------------------------------------------------- */

const filtros = {
    busqueda: '',
    categoria: '',        // '' (todos) o el nombre de una categoría del sitio
    precioMin: null,      // null = sin tope; si no, soles
    precioMax: null,
    orden: 'popular',     // 'popular' | 'precio-asc' | 'precio-desc' | 'puntos-asc' | 'puntos-desc'
    pagina: 1,
    porPagina: 24         // 12 | 24 | 48 | 96
};

/** Precio más barato y más caro del catálogo; son los topes del filtro. */
const LIMITES_PRECIO = {
    min: Math.floor(Math.min(...PRODUCTS.map((p) => p.pvp))),
    max: Math.ceil(Math.max(...PRODUCTS.map((p) => p.pvp)))
};

/** Carrito en memoria: { idProducto: cantidad } */
let carrito = cargarCarrito();

function cargarCarrito() {
    try {
        const guardado = JSON.parse(localStorage.getItem(CONFIG.storageKey) || '{}');
        // Descartamos ids que ya no existen en la lista de precios vigente.
        return Object.fromEntries(
            Object.entries(guardado).filter(([id, cant]) => buscarProducto(id) && cant > 0)
        );
    } catch (_e) {
        return {};
    }
}

function guardarCarrito() {
    try {
        localStorage.setItem(CONFIG.storageKey, JSON.stringify(carrito));
    } catch (_e) {
        // Si el navegador bloquea el almacenamiento, el carrito igual funciona
        // durante la visita; solo no se recuerda al volver.
    }
}

/* --------------------------------------------------------------------------
   4. Cálculos del pedido
   -------------------------------------------------------------------------- */

/** Devuelve las líneas del pedido con sus subtotales y puntos. */
function lineasDelPedido() {
    return Object.entries(carrito).map(([id, cantidad]) => {
        const producto = buscarProducto(id);
        return {
            producto,
            cantidad,
            subtotal: producto.pvp * cantidad,
            puntos: producto.puntos * cantidad
        };
    });
}

/** Totales del pedido: unidades, monto en soles y puntos acumulados. */
function totalesDelPedido() {
    return lineasDelPedido().reduce((acc, l) => ({
        unidades: acc.unidades + l.cantidad,
        monto: acc.monto + l.subtotal,
        puntos: acc.puntos + l.puntos
    }), { unidades: 0, monto: 0, puntos: 0 });
}

/* --------------------------------------------------------------------------
   5. Operaciones del carrito
   -------------------------------------------------------------------------- */

function agregarAlCarrito(id, cantidad = 1) {
    const producto = buscarProducto(id);
    if (!producto) return;
    carrito[producto.id] = (carrito[producto.id] || 0) + cantidad;
    guardarCarrito();
    pintarCarrito();
    pintarCatalogo();
    avisar(`${producto.producto} agregado al pedido`);
}

function cambiarCantidad(id, delta) {
    const producto = buscarProducto(id);
    if (!producto) return;
    const nueva = (carrito[producto.id] || 0) + delta;
    if (nueva <= 0) delete carrito[producto.id];
    else carrito[producto.id] = nueva;
    guardarCarrito();
    pintarCarrito();
    pintarCatalogo();
}

function quitarDelCarrito(id) {
    delete carrito[Number(id)];
    guardarCarrito();
    pintarCarrito();
    pintarCatalogo();
}

function vaciarCarrito() {
    carrito = {};
    guardarCarrito();
    pintarCarrito();
    pintarCatalogo();
}

/* --------------------------------------------------------------------------
   6. Mensaje de pedido para WhatsApp
   -------------------------------------------------------------------------- */

/**
 * Arma el resumen del pedido tal como llega al asesor:
 * detalle línea por línea, total en soles, puntos acumulados y el aviso de
 * precios especiales por cantidad.
 */
function mensajeDelPedido() {
    const lineas = lineasDelPedido();
    const { monto, puntos } = totalesDelPedido();

    const detalle = lineas.map((l, i) =>
        `${i + 1}. ${l.producto.producto}\n` +
        `   ${l.cantidad} x ${soles(l.producto.pvp)} = ${soles(l.subtotal)}`
    ).join('\n');

    return [
        '*NUEVO PEDIDO - Santa Natura*',
        '',
        detalle,
        '',
        `*TOTAL: ${soles(monto)}*`,
        `Puntos acumulados: ${pts(puntos)}`,
        '',
        `_${CONFIG.notaCantidad}_`
    ].join('\n');
}

function enviarPedidoPorWhatsApp() {
    if (!Object.keys(carrito).length) {
        avisar('Tu pedido está vacío. Agrega productos para continuar.');
        return;
    }
    window.open(enlaceWhatsApp(mensajeDelPedido()), '_blank', 'noopener');
}

/* --------------------------------------------------------------------------
   6.b Afiliación

   Los cuatro paquetes de ingreso de la tabla oficial vigente. Se declaran aquí
   (y no en el HTML) para que index.html y afiliacion.html muestren SIEMPRE los
   mismos precios: al cambiarlos aquí se actualizan las dos páginas, el
   desplegable del formulario y los mensajes de WhatsApp.

   Los cuatro montos van escritos uno a uno, aunque dos se puedan deducir, para
   que la tabla sea auditable de un vistazo contra el documento de la empresa:

     puntos        Puntos del paquete (la misma unidad que usa products.js).
     precioPublico Lo que ese paquete costaría a precio de lista = puntos * 6.
     descuento     Descuento permanente del socio; coincide con las claves del
                   objeto `descuentos` que trae cada producto en products.js.
     inversion     Lo que realmente paga el socio = precioPublico - descuento.

   La equivalencia «1 punto = S/ 6» está comprobada contra products.js: se
   cumple exacta en los 52 productos de Línea General y Línea Convencional
   (tomando precio_original cuando existe y, si no, pvp). Los packs y la Línea
   Consumo Saludable siguen otra escala, por eso no entran en la comprobación.
   -------------------------------------------------------------------------- */

/** Soles de precio público que vale un punto. */
const PUNTO_EN_SOLES = 6;

const PLANES_AFILIACION = [
    {
        id: 'basico',
        nombre: 'Plan Básico',
        puntos: 50,
        precioPublico: 300,
        descuento: 20,
        inversion: 240,
        icono: 'local_mall',
        beneficios: [
            '<strong>20% de descuento</strong> permanente en todo el catálogo',
            'Eliges los productos de tu paquete hasta completar sus puntos',
            'Asesoría inicial para arrancar con tus primeras ventas'
        ]
    },
    {
        id: 'profesional',
        nombre: 'Plan Profesional',
        puntos: 100,
        precioPublico: 600,
        descuento: 25,
        inversion: 450,
        icono: 'trending_up',
        beneficios: [
            '<strong>25% de descuento</strong> permanente en todo el catálogo',
            'Eliges los productos de tu paquete hasta completar sus puntos',
            'Seguimiento de tu asesor mientras formas tu cartera de clientes'
        ]
    },
    {
        id: 'empresarial',
        nombre: 'Plan Empresarial',
        puntos: 250,
        precioPublico: 1500,
        descuento: 30,
        inversion: 1050,
        icono: 'stars',
        destacado: true,
        etiqueta: 'El más elegido',
        beneficios: [
            '<strong>30% de descuento</strong> permanente en todo el catálogo',
            'Eliges los productos de tu paquete hasta completar sus puntos',
            'Acompañamiento para armar y capacitar a tu propio equipo'
        ]
    },
    {
        id: 'millonario',
        nombre: 'Plan Millonario',
        puntos: 450,
        precioPublico: 2700,
        descuento: 40,
        etiqueta: 'Máximo descuento',
        inversion: 1620,
        icono: 'workspace_premium',
        beneficios: [
            '<strong>40% de descuento</strong> permanente en todo el catálogo',
            'Eliges los productos de tu paquete hasta completar sus puntos',
            'El margen más alto del plan: compras al mejor precio disponible'
        ]
    }
];

/* Comprobación de desarrollo. No corrige nada ni interrumpe la página: solo
   avisa por consola si al editar la tabla se rompe la aritmética oficial, que
   es justo el error que pasaría desapercibido al copiar cifras a mano. */
(function comprobarPlanes() {
    PLANES_AFILIACION.forEach((plan) => {
        const inversionEsperada = plan.precioPublico * (1 - plan.descuento / 100);
        if (Math.abs(plan.inversion - inversionEsperada) > 0.01) {
            console.warn(
                `[PLANES_AFILIACION] ${plan.nombre}: la inversión declarada es ` +
                `${plan.inversion}, pero ${plan.descuento}% de descuento sobre ` +
                `${plan.precioPublico} da ${inversionEsperada}.`
            );
        }

        const publicoEsperado = plan.puntos * PUNTO_EN_SOLES;
        if (Math.abs(plan.precioPublico - publicoEsperado) > 0.01) {
            console.warn(
                `[PLANES_AFILIACION] ${plan.nombre}: el precio público declarado ` +
                `es ${plan.precioPublico}, pero ${plan.puntos} puntos a ` +
                `S/ ${PUNTO_EN_SOLES} dan ${publicoEsperado}.`
            );
        }
    });
})();

const buscarPlan = (id) => PLANES_AFILIACION.find((p) => p.id === id);

/** Plan más barato: es el que define el «afíliate desde S/ …» de la web. */
const planDeEntrada = () =>
    PLANES_AFILIACION.reduce(
        (menor, plan) => (!menor || plan.inversion < menor.inversion ? plan : menor),
        null
    );

/** Rango de descuentos de la tabla, p. ej. «20–40%». */
function rangoDeDescuentos() {
    const valores = PLANES_AFILIACION.map((p) => p.descuento);
    return `${Math.min(...valores)}–${Math.max(...valores)}%`;
}

/** Lo que el socio deja de pagar frente al precio de lista del paquete. */
const ahorroDelPlan = (plan) => plan.precioPublico - plan.inversion;

/**
 * Cuántos productos entran, en promedio, en un paquete de N puntos.
 *
 * Se promedia solo sobre productos individuales: los packs no forman parte del
 * kit de afiliación y su puntaje alto hundiría la cuenta (un paquete de 50
 * puntos parecería de 2 artículos en vez de 4). Devuelve null si el catálogo
 * no está cargado, para que quien llame pueda omitir la línea sin romperse.
 */
function productosAproximados(puntos) {
    if (typeof PRODUCTS === 'undefined' || !Array.isArray(PRODUCTS)) return null;

    const sueltos = PRODUCTS.filter((p) => p.categoria !== 'Packs' && p.puntos > 0);
    if (!sueltos.length) return null;

    const promedio = sueltos.reduce((suma, p) => suma + p.puntos, 0) / sueltos.length;
    const cuantos = Math.round(puntos / promedio);
    return cuantos > 0 ? cuantos : null;
}

/** Mensaje de WhatsApp para pedir información o afiliarse a un plan. */
function mensajeDeAfiliacion(datos) {
    const plan = buscarPlan(datos.plan);

    // Se arma por bloques: los campos opcionales solo entran si se llenaron,
    // pero las líneas en blanco de separación se conservan siempre.
    // La marca del negocio es «Santa Natura Network»; «Santa Natura» a secas
    // se reserva para el catálogo y los productos.
    const lineas = ['*SOLICITUD DE AFILIACIÓN - Santa Natura Network*', ''];

    lineas.push(`Nombre: ${datos.nombre}`);
    if (datos.telefono) lineas.push(`Teléfono: ${datos.telefono}`);
    if (datos.ciudad) lineas.push(`Ciudad: ${datos.ciudad}`);

    lineas.push('');
    lineas.push(plan
        ? `Plan de interés: *${plan.nombre}* (${soles(plan.inversion)} · ${plan.puntos} puntos · ${plan.descuento}% de descuento)`
        : 'Plan de interés: aún no lo decido, quiero orientación');
    if (datos.objetivo) lineas.push(`Objetivo: ${datos.objetivo}`);

    if (datos.mensaje) lineas.push('', `Comentario: ${datos.mensaje}`);

    lineas.push('', '_Enviado desde la página de afiliación._');
    return lineas.join('\n');
}

/** Botones «Afíliate ahora»: abren WhatsApp con el plan ya indicado. */
function afiliarsePorWhatsApp(idPlan) {
    const plan = buscarPlan(idPlan);
    const texto = plan
        ? `Hola, quiero afiliarme con el *${plan.nombre}* (${soles(plan.inversion)}). ¿Me explicas los pasos para registrarme?`
        : 'Hola, quiero información para afiliarme a Santa Natura Network.';
    window.open(enlaceWhatsApp(texto), '_blank', 'noopener');
}

/**
 * Formulario de afiliación (#form-afiliacion). Valida lo mínimo y abre
 * WhatsApp con la solicitud ya redactada; no hay servidor de por medio.
 */
function enviarAfiliacion(evento) {
    evento.preventDefault();
    const form = evento.target;

    const datos = {
        nombre: form.nombre.value.trim(),
        telefono: form.telefono.value.trim(),
        ciudad: form.ciudad.value.trim(),
        plan: form.plan.value,
        objetivo: form.objetivo.value,
        mensaje: form.mensaje.value.trim()
    };

    if (!datos.nombre) {
        avisar('Escribe tu nombre para poder atenderte.');
        form.nombre.focus();
        return;
    }

    window.open(enlaceWhatsApp(mensajeDeAfiliacion(datos)), '_blank', 'noopener');
    avisar('Abrimos WhatsApp con tu solicitud. ¡Te respondemos enseguida!');
}

/** Pinta las tarjetas de planes donde exista el contenedor indicado. */
function pintarPlanes(idContenedor) {
    const zona = document.getElementById(idContenedor);
    if (!zona) return;

    zona.innerHTML = PLANES_AFILIACION.map((plan) => {
        // El plan destacado va en verde sólido; los otros dos, sobre la
        // superficie normal. Se calcula aquí para no repetir clases abajo.
        const marco = plan.destacado
            ? 'bg-primary text-on-primary border-primary shadow-xl'
            : 'bg-surface text-on-surface border-outline-variant/40 hover:shadow-lg';
        const apagado = plan.destacado ? 'text-on-primary/80' : 'text-on-surface-variant';
        const boton = plan.destacado
            ? 'bg-botanical-white text-primary hover:brightness-105 shadow-lg'
            : 'bg-primary/5 text-primary border border-primary/20 hover:bg-primary hover:text-on-primary';

        // El badge del % y la línea de ahorro cambian de fondo en la tarjeta
        // destacada para no perder contraste sobre el verde sólido.
        const badge = plan.destacado
            ? 'bg-botanical-white/20 text-on-primary'
            : 'bg-primary/10 text-primary';
        const separador = plan.destacado ? 'border-on-primary/20' : 'border-outline-variant/50';

        // Con cuatro columnas la tarjeta es estrecha, así que la cuenta de
        // productos solo se dibuja si el catálogo está cargado.
        const aprox = productosAproximados(plan.puntos);

        return `
        <div class="relative flex flex-col h-full p-sm md:p-md rounded-3xl border transition-all ${marco}">
            ${plan.etiqueta ? `
            <span class="absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap bg-botanical-white text-primary px-3 py-1 rounded-full font-label-caps text-[10px] uppercase tracking-wider shadow-md">
                ${escapar(plan.etiqueta)}
            </span>` : ''}

            <div class="mt-2 mb-sm">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <h3 class="font-headline-md text-lg leading-tight">${escapar(plan.nombre)}</h3>
                    <span class="shrink-0 rounded-full px-2 py-0.5 font-label-caps text-[10px] tracking-wider ${badge}">${plan.descuento}%</span>
                </div>

                <p class="text-[13px] line-through ${apagado}">${solesEnPantalla(plan.precioPublico)}</p>
                <div class="flex items-baseline gap-1.5 flex-wrap">
                    <span class="text-2xl lg:text-3xl font-bold ${plan.destacado ? '' : 'text-primary'}">${solesEnPantalla(plan.inversion)}</span>
                    <span class="text-[13px] ${apagado}">de inversión</span>
                </div>
            </div>

            <div class="border-t border-b py-2 mb-sm space-y-0.5 ${separador}">
                <p class="font-label-caps text-[11px] uppercase tracking-wider ${plan.destacado ? '' : 'text-on-surface'}">
                    ${plan.puntos} puntos${aprox ? ` · ≈ ${aprox} productos` : ''}
                </p>
                <p class="text-[13px] ${apagado}">Ahorras ${solesEnPantalla(ahorroDelPlan(plan))} frente al precio público</p>
            </div>

            <ul class="space-y-2 mb-sm flex-1">
                ${plan.beneficios.map((b) => `
                <li class="flex items-start gap-1.5 text-[13px] leading-snug ${apagado}">
                    <span class="material-symbols-outlined text-base shrink-0 ${plan.destacado ? '' : 'text-primary'}">check_circle</span>
                    <span>${b}</span>
                </li>`).join('')}
            </ul>

            <button class="w-full py-2.5 rounded-full font-title-sm text-[15px] text-center transition-all active:scale-[0.98] ${boton}"
                    onclick="afiliarsePorWhatsApp('${plan.id}')">
                ¡Afíliate ahora!
            </button>
        </div>`;
    }).join('');
}

/**
 * Los mismos cuatro planes vistos desde el lado del margen: el descuento con
 * el que compra el socio es su ganancia al vender. Se pinta desde la constante
 * para que renombrar un plan o mover un porcentaje no deje esta rejilla vieja.
 * El plan con mayor descuento va destacado en verde sólido.
 */
function pintarNivelesDeMargen() {
    const zona = document.getElementById('niveles-margen');
    if (!zona) return;

    const tope = Math.max(...PLANES_AFILIACION.map((p) => p.descuento));

    zona.innerHTML = PLANES_AFILIACION.map((plan) => {
        const cima = plan.descuento === tope;
        const marco = cima
            ? 'bg-primary text-on-primary border-primary shadow-xl'
            : 'bg-surface text-on-surface border-outline-variant/40 hover:shadow-lg transition-shadow';
        const circulo = cima ? 'bg-botanical-white/20' : 'bg-primary/10 text-primary';
        const apagado = cima ? 'text-on-primary/80' : 'text-on-surface-variant';

        return `
        <div class="rounded-3xl p-md border flex flex-col items-center text-center gap-xs ${marco}">
            <span class="grid place-items-center w-14 h-14 rounded-full mb-1 ${circulo}">
                <span class="material-symbols-outlined text-3xl">${escapar(plan.icono || 'workspace_premium')}</span>
            </span>
            <h5 class="font-title-sm text-base">${escapar(plan.nombre)}</h5>
            <p class="font-headline-md text-4xl font-bold ${cima ? '' : 'text-primary'}">${plan.descuento}%</p>
            <p class="font-label-caps text-[10px] uppercase tracking-wider ${apagado}">Desde ${plan.puntos} puntos</p>
        </div>`;
    }).join('');
}

/** Rellena el desplegable de planes del formulario con los mismos precios. */
function pintarSelectorDePlanes() {
    const select = document.getElementById('plan-afiliacion');
    if (!select) return;

    select.innerHTML =
        '<option value="">Aún no lo decido, quiero orientación</option>' +
        PLANES_AFILIACION.map((p) =>
            `<option value="${p.id}">${escapar(p.nombre)} — ${soles(p.inversion)} · ${p.puntos} puntos (${p.descuento}%)</option>`
        ).join('');
}

/* --------------------------------------------------------------------------
   6.c Ejemplo de venta directa

   La presentación de negocio promete «+ S/ 2,160 al mes vendiendo solo un
   colágeno por día». La cifra sale del catálogo real y aquí se recalcula en
   vez de escribirla a mano, para que nunca prometa un margen que los precios
   vigentes ya no dan: si el producto cambia de precio, cambia el ejemplo.
   -------------------------------------------------------------------------- */

/** Colágeno hidrolizado maracuyá y camu camu x 450 g: el del ejemplo oficial. */
const ID_PRODUCTO_EJEMPLO = 13;
const DIAS_DEL_EJEMPLO = 30;

/**
 * Margen de vender una unidad diaria del producto de ejemplo, comprando con
 * el mayor descuento de la tabla de planes. Devuelve null si el catálogo no
 * está cargado o el producto ya no existe, para poder ocultar el bloque.
 */
function ejemploDeVentaDiaria() {
    if (typeof PRODUCTS === 'undefined' || !Array.isArray(PRODUCTS)) return null;

    const producto = buscarProducto(ID_PRODUCTO_EJEMPLO);
    if (!producto || !producto.descuentos) return null;

    // El ejemplo se calcula con el plan de mayor descuento disponible.
    const plan = PLANES_AFILIACION.reduce(
        (mayor, p) => (!mayor || p.descuento > mayor.descuento ? p : mayor),
        null
    );
    const precioSocio = producto.descuentos[String(plan.descuento)];
    if (typeof precioSocio !== 'number') return null;

    // Precio de lista: el tachado cuando el producto está en oferta pública.
    const precioLista = producto.precio_original || producto.pvp;
    const margen = precioLista - precioSocio;
    if (margen <= 0) return null;

    return {
        producto, plan, precioLista, precioSocio, margen,
        dias: DIAS_DEL_EJEMPLO,
        mensual: margen * DIAS_DEL_EJEMPLO
    };
}

/** Rellena el ejemplo de venta de afiliacion.html; lo oculta si no se puede calcular. */
function pintarEjemploDeVenta() {
    const zona = document.getElementById('ejemplo-venta');
    if (!zona) return;

    const e = ejemploDeVentaDiaria();
    if (!e) {
        zona.hidden = true;
        return;
    }

    const valores = {
        'venta-producto': e.producto.producto,
        'venta-lista': solesEnPantalla(e.precioLista),
        'venta-socio': solesEnPantalla(e.precioSocio),
        'venta-descuento': `${e.plan.descuento}%`,
        'venta-plan': e.plan.nombre,
        'venta-margen': solesEnPantalla(e.margen),
        'venta-dias': String(e.dias),
        'venta-mensual': solesEnPantalla(e.mensual)
    };

    Object.entries(valores).forEach(([clave, texto]) => {
        zona.querySelectorAll(`[data-${clave}]`).forEach((el) => {
            el.textContent = texto;
        });
    });
}

/* --------------------------------------------------------------------------
   7. Catálogo: filtros y pintado
   -------------------------------------------------------------------------- */

/** Aplica búsqueda, categoría, rango de precio y orden. */
function productosFiltrados() {
    const q = normalizar(filtros.busqueda.trim());

    let lista = PRODUCTS.filter((p) => {
        // Filtrar por una categoría madre incluye también a sus subcategorías.
        if (filtros.categoria && !estaEn(p, filtros.categoria)) return false;

        if (q) {
            const texto = [p.producto, ...(p.categorias || [])].join(' ');
            if (!normalizar(texto).includes(q)) return false;
        }

        if (filtros.precioMin !== null && p.pvp < filtros.precioMin) return false;
        if (filtros.precioMax !== null && p.pvp > filtros.precioMax) return false;

        return true;
    });

    const criterio = {
        'precio-asc':  (a, b) => a.pvp - b.pvp,
        'precio-desc': (a, b) => b.pvp - a.pvp,
        'puntos-asc':  (a, b) => a.puntos - b.puntos,
        'puntos-desc': (a, b) => b.puntos - a.puntos
    }[filtros.orden];

    return criterio ? [...lista].sort(criterio) : lista;
}

/* --- Filtro de precio ---------------------------------------------------- */

/** Repinta el catálogo y actualiza el resumen bajo las cajas de precio. */
function aplicarFiltroPrecio() {
    reiniciarPagina();
    pintarCatalogo();
    sincronizarFiltroPrecio();
}

function sincronizarFiltroPrecio() {
    const resumen = document.getElementById('precio-resumen');
    const limpiar = document.getElementById('precio-limpiar');
    if (!resumen) return;

    const { precioMin: lo, precioMax: hi } = filtros;
    const activo = lo !== null || hi !== null;

    resumen.textContent =
        !activo             ? `Precios de ${soles(LIMITES_PRECIO.min)} a ${soles(LIMITES_PRECIO.max)}` :
        lo !== null && hi !== null ? `De ${soles(lo)} a ${soles(hi)}` :
        lo !== null         ? `Desde ${soles(lo)}` :
                              `Hasta ${soles(hi)}`;

    limpiar.classList.toggle('hidden', !activo);
}

function limpiarFiltroPrecio() {
    filtros.precioMin = null;
    filtros.precioMax = null;
    document.getElementById('precio-min').value = '';
    document.getElementById('precio-max').value = '';
    document.getElementById('precio-slider').value = LIMITES_PRECIO.max;
    aplicarFiltroPrecio();
}

/**
 * Imagen del producto, o placeholder de marca si todavía no tiene foto.
 * `compacto` se usa en miniaturas (carrito), donde no cabe el texto de marca.
 */
function bloqueImagen(producto, alto = 'h-52', compacto = false) {
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
    const foto = producto.imagen
        ? `<img src="${escapar(producto.imagen)}" alt="${escapar(producto.producto)}"
                loading="lazy" onerror="this.remove()"
                class="absolute inset-0 w-full h-full object-contain ${compacto ? '' : 'p-2'} bg-white transition-transform duration-500 group-hover:scale-105"/>`
        : '';

    return `<div class="img-placeholder relative overflow-hidden w-full ${alto} flex flex-col items-center justify-center gap-xs bg-surface-container">
                ${marca}
                ${foto}
            </div>`;
}

function tarjetaProducto(p) {
    const enCarrito = carrito[p.id] || 0;

    return `
    <article class="bg-surface rounded-3xl shadow-sm overflow-hidden border border-outline-variant/50 group hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col cursor-pointer"
             onclick="abrirFichaProducto(${p.id})">
        <div class="relative overflow-hidden bg-surface-container">
            ${bloqueImagen(p, 'h-40 sm:h-48 md:h-52')}
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
                    <p class="text-[11px] text-on-surface-variant leading-tight mt-0.5">Precio de venta al público</p>
                </div>

                <button class="w-full bg-primary text-on-primary py-2 rounded-full font-label-caps text-[11px] md:text-xs hover:brightness-110 active:scale-[0.98] transition-all flex items-center justify-center gap-xs"
                        onclick="event.stopPropagation(); agregarAlCarrito(${p.id})">
                    <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                    <span class="truncate">Agregar al pedido</span>
                </button>
            </div>
        </div>
    </article>`;
}

function pintarCatalogo() {
    const grid = document.getElementById('grid-productos');
    // Las subpáginas (afiliación, etc.) comparten store.js pero no tienen
    // catálogo: si no existe la rejilla, no hay nada que pintar.
    if (!grid) return;

    const lista = productosFiltrados();
    const vacio = document.getElementById('grid-vacio');

    // Si al filtrar quedan menos páginas que la que estabas viendo, retrocede.
    const paginas = Math.max(1, Math.ceil(lista.length / filtros.porPagina));
    if (filtros.pagina > paginas) filtros.pagina = paginas;

    const desde = (filtros.pagina - 1) * filtros.porPagina;
    const pagina = lista.slice(desde, desde + filtros.porPagina);

    grid.innerHTML = pagina.map(tarjetaProducto).join('');
    vacio.classList.toggle('hidden', lista.length > 0);

    document.getElementById('contador-resultados').textContent = lista.length === 0
        ? '0 productos'
        : lista.length <= filtros.porPagina
            ? `${lista.length} ${lista.length === 1 ? 'producto' : 'productos'}`
            : `Mostrando ${desde + 1}–${desde + pagina.length} de ${lista.length} productos`;

    pintarPaginacion(paginas);
}

/* --- Paginación ---------------------------------------------------------- */

/**
 * Números de página a mostrar: siempre la primera y la última, y una ventana
 * alrededor de la actual. Los saltos se marcan con null (se pintan como «…»).
 */
function numerosDePagina(actual, total) {
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

    const cerca = [actual - 1, actual, actual + 1].filter((n) => n > 1 && n < total);
    const nums = [1, ...cerca, total];

    return nums.flatMap((n, i) => (i > 0 && n - nums[i - 1] > 1 ? [null, n] : [n]));
}

function pintarPaginacion(paginas) {
    const nav = document.getElementById('paginacion');
    if (!nav) return;

    nav.classList.toggle('hidden', paginas <= 1);
    nav.classList.toggle('flex', paginas > 1);
    if (paginas <= 1) {
        nav.innerHTML = '';   // que no queden botones viejos escondidos
        return;
    }

    const actual = filtros.pagina;
    const flecha = (destino, icono, etiqueta) => `
        <button aria-label="${etiqueta}" ${destino ? '' : 'disabled'}
                onclick="irAPagina(${destino || 1})"
                class="w-9 h-9 grid place-items-center rounded-xl border border-outline-variant transition-colors ${
                    destino ? 'text-on-surface-variant hover:border-primary hover:text-primary' : 'text-outline-variant cursor-not-allowed'
                }">
            <span class="material-symbols-outlined text-lg">${icono}</span>
        </button>`;

    nav.innerHTML =
        flecha(actual > 1 ? actual - 1 : 0, 'chevron_left', 'Página anterior') +
        numerosDePagina(actual, paginas).map((n) => n === null
            ? '<span class="w-9 h-9 grid place-items-center text-outline">…</span>'
            : `<button onclick="irAPagina(${n})" aria-current="${n === actual ? 'page' : 'false'}"
                       class="min-w-9 h-9 px-2 grid place-items-center rounded-xl text-sm font-label-md transition-colors ${
                           n === actual
                               ? 'bg-primary text-on-primary'
                               : 'border border-outline-variant text-on-surface-variant hover:border-primary hover:text-primary'
                       }">${n}</button>`
        ).join('') +
        flecha(actual < paginas ? actual + 1 : 0, 'chevron_right', 'Página siguiente');
}

function irAPagina(n) {
    filtros.pagina = n;
    pintarCatalogo();
    document.getElementById('productos').scrollIntoView({ behavior: 'smooth' });
}

/** Cualquier cambio de filtro u orden devuelve el catálogo a la primera página. */
function reiniciarPagina() {
    filtros.pagina = 1;
}

/* --------------------------------------------------------------------------
   8. Categorías (menú superior, barra lateral y pie de página)

   Se navega por las categorías reales de santanatura.com.pe, definidas en
   COLECCIONES (products.js). Un producto pertenece a varias a la vez, así que
   los conteos NO suman el total del catálogo: eso es correcto, no un error.
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

function contar(nombre) {
    return PRODUCTS.filter((p) => estaEn(p, nombre)).length;
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

/** Para poder pasar cualquier nombre por un onclick sin pelear con comillas. */
const arg = (s) => escapar(JSON.stringify(s));

function pintarCategorias() {
    const activa = filtros.categoria;
    const madres = COLECCIONES.filter((c) => contar(c.nombre) > 0);

    // Fila de accesos rápidos: pastillas con las primeras categorías de
    // COLECCIONES. El resto está siempre a un clic, en el mega menú.
    // En el diseño actual esta fila está oculta en el header, por eso se
    // comprueba que exista antes de rellenarla.
    const rapidas = document.getElementById('nav-categorias');
    if (rapidas) {
        rapidas.innerHTML = [
            { nombre: '', texto: 'Todos' },
            ...madres.slice(0, 8).map((c) => ({ nombre: c.nombre, texto: c.nombre }))
        ].map((c) => `
        <button onclick="filtrarPorCategoria(${arg(c.nombre)})"
                class="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-label-caps transition-colors ${
                    activa === c.nombre
                        ? 'bg-primary text-on-primary'
                        : 'text-on-surface-variant hover:bg-surface-container hover:text-primary'
                }">${escapar(c.texto)}</button>
    `).join('');
    }

    // Mega menú: el árbol completo, en columnas.
    const mega = document.getElementById('mega-contenido');
    if (mega) mega.innerHTML = madres.map((c) => `
        <div>
            <button onclick="filtrarPorCategoria(${arg(c.nombre)})"
                    class="flex items-baseline gap-1.5 text-left font-title-lg text-sm mb-1 transition-colors ${
                        activa === c.nombre ? 'text-primary' : 'text-on-surface hover:text-primary'
                    }">
                ${escapar(c.nombre)}
                <span class="text-[11px] font-normal text-outline">${contar(c.nombre)}</span>
            </button>
            <ul class="space-y-0.5">
                ${c.hijas.filter((h) => contar(h) > 0).map((h) => `
                <li>
                    <button onclick="filtrarPorCategoria(${arg(h)})"
                            class="flex items-baseline gap-1.5 text-left text-xs transition-colors ${
                                activa === h ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary'
                            }">
                        ${escapar(h)}
                        <span class="text-[11px] text-outline">${contar(h)}</span>
                    </button>
                </li>`).join('')}
            </ul>
        </div>
    `).join('');

    // Barra lateral: el árbol completo, con las subcategorías indentadas.
    const fila = (nombre, texto, hija) => {
        const esActiva = activa === nombre;
        return `
        <li>
            <button onclick="filtrarPorCategoria(${arg(nombre)})"
                    class="w-full flex items-center justify-between gap-xs text-left transition-colors ${hija ? 'pl-sm' : ''} ${
                        esActiva ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary'
                    }">
                <span class="${hija ? 'text-xs' : 'text-sm'}">${escapar(texto)}</span>
                <span class="${esActiva ? 'bg-primary/10' : 'bg-surface-container-high'} px-2 py-0.5 rounded-full text-xs shrink-0">${
                    nombre === '' ? PRODUCTS.length : contar(nombre)
                }</span>
            </button>
        </li>`;
    };

    const barra = document.getElementById('sidebar-categorias');
    if (barra) {
        barra.innerHTML =
            fila('', 'Todos', false) +
            madres.map((c) => fila(c.nombre, c.nombre, false) +
                c.hijas.filter((h) => contar(h) > 0).map((h) => fila(h, h, true)).join('')
            ).join('');
    }

    // Enlaces del pie de página: las madres con más productos.
    const pie = document.getElementById('footer-categorias');
    if (pie) {
        pie.innerHTML = [...madres]
            .sort((a, b) => contar(b.nombre) - contar(a.nombre))
            .slice(0, 6)
            // El pie va sobre fondo verde, así que el hover aclara a blanco.
            .map((c) => `<li><button class="hover:text-botanical-white transition-colors text-left" onclick="filtrarPorCategoria(${arg(c.nombre)})">${escapar(c.nombre)}</button></li>`)
            .join('');
    }
}

/** Cadena vacía = todos los productos. */
function filtrarPorCategoria(cat) {
    // Desde una subpágina (afiliación, etc.) no hay catálogo que filtrar:
    // se salta al index con la categoría ya elegida en la dirección.
    if (!document.getElementById('grid-productos')) {
        const destino = cat
            ? `index.html?categoria=${encodeURIComponent(cat)}#productos`
            : 'index.html#productos';
        window.location.href = destino;
        return;
    }

    filtros.categoria = cat;
    reiniciarPagina();
    pintarCategorias();
    pintarCatalogo();
    document.getElementById('productos').scrollIntoView({ behavior: 'smooth' });
    cerrarMegaMenu();
}

/* --------------------------------------------------------------------------
   9. Ficha de producto (modal)
   -------------------------------------------------------------------------- */

let productoEnFicha = null;

function abrirFichaProducto(id) {
    const p = buscarProducto(id);
    if (!p) return;
    productoEnFicha = p;

    document.getElementById('ficha-imagen').innerHTML = bloqueImagen(p, 'h-56 sm:h-72 rounded-2xl');
    document.getElementById('ficha-categoria').textContent = etiquetaCategoria(p);
    document.getElementById('ficha-titulo').textContent = p.producto;
    document.getElementById('ficha-precio').textContent = soles(p.pvp);

    // Precio original tachado (se muestra solo si el producto tiene descuento)
    const fichaOriginal = document.getElementById('ficha-precio-original');
    if (fichaOriginal) {
        if (p.precio_original) {
            fichaOriginal.textContent = soles(p.precio_original);
            fichaOriginal.classList.remove('hidden');
        } else {
            fichaOriginal.textContent = '';
            fichaOriginal.classList.add('hidden');
        }
    }

    // Badge con el porcentaje de descuento
    const fichaBadge = document.getElementById('ficha-descuento');
    if (fichaBadge) {
        if (p.etiqueta_descuento) {
            fichaBadge.textContent = p.etiqueta_descuento;
            fichaBadge.classList.remove('hidden');
        } else {
            fichaBadge.textContent = '';
            fichaBadge.classList.add('hidden');
        }
    }
    document.getElementById('ficha-puntos').textContent = pts(p.puntos);
    document.getElementById('ficha-descripcion').textContent =
        p.descripcion || 'Producto original Santa Natura. Consulta a nuestro asesor por modo de uso y disponibilidad.';

    const consulta = `Hola, quiero pedir: ${p.producto} — ${soles(p.pvp)}. ¿Tienen stock disponible?\n\n_${CONFIG.notaCantidad}_`;
    document.getElementById('ficha-whatsapp').href = enlaceWhatsApp(consulta);

    abrirModal('modal-producto');
}

function cerrarFichaProducto() {
    cerrarModal('modal-producto');
    productoEnFicha = null;
}

function agregarDesdeFicha() {
    if (!productoEnFicha) return;
    agregarAlCarrito(productoEnFicha.id);
    cerrarFichaProducto();
    abrirCarrito();
}

/* --------------------------------------------------------------------------
   10. Carrito (panel lateral)
   -------------------------------------------------------------------------- */

function pintarCarrito() {
    const lineas = lineasDelPedido();
    const { unidades, monto, puntos } = totalesDelPedido();

    // Globo con la cantidad de unidades sobre el ícono del carrito
    const globo = document.getElementById('carrito-contador');
    if (globo) {
        globo.textContent = unidades;
        globo.classList.toggle('hidden', unidades === 0);
    }

    const contenedor = document.getElementById('carrito-lineas');
    if (!contenedor) return;   // página sin panel de carrito

    const vacio = document.getElementById('carrito-vacio');
    const resumen = document.getElementById('carrito-resumen');

    vacio.classList.toggle('hidden', unidades > 0);
    resumen.classList.toggle('hidden', unidades === 0);

    contenedor.innerHTML = lineas.map((l) => `
        <div class="flex gap-sm py-md border-b border-outline-variant/50">
            <div class="w-16 h-16 rounded-lg overflow-hidden shrink-0 bg-surface-container">
                ${bloqueImagen(l.producto, 'h-16', true)}
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-title-lg text-xs leading-snug text-on-surface">${escapar(l.producto.producto)}</p>
                <p class="text-[11px] text-on-surface-variant mt-0.5">${soles(l.producto.pvp)} c/u · ${pts(l.producto.puntos)}</p>

                <div class="flex items-center justify-between mt-xs">
                    <div class="flex items-center gap-2">
                        <button class="w-7 h-7 rounded-lg border border-outline-variant grid place-items-center hover:bg-surface-container"
                                aria-label="Quitar una unidad" onclick="cambiarCantidad(${l.producto.id}, -1)">
                            <span class="material-symbols-outlined text-sm">remove</span>
                        </button>
                        <span class="font-bold text-sm w-6 text-center">${l.cantidad}</span>
                        <button class="w-7 h-7 rounded-lg border border-outline-variant grid place-items-center hover:bg-surface-container"
                                aria-label="Agregar una unidad" onclick="cambiarCantidad(${l.producto.id}, 1)">
                            <span class="material-symbols-outlined text-sm">add</span>
                        </button>
                    </div>
                    <span class="font-bold text-sm text-on-surface">${soles(l.subtotal)}</span>
                </div>
            </div>
            <button class="text-outline hover:text-error self-start" aria-label="Eliminar producto"
                    onclick="quitarDelCarrito(${l.producto.id})">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>
    `).join('');

    document.getElementById('carrito-total').textContent = soles(monto);
    document.getElementById('carrito-puntos').textContent = pts(puntos);
    document.getElementById('carrito-unidades').textContent =
        `${unidades} ${unidades === 1 ? 'unidad' : 'unidades'}`;
}

function abrirCarrito() {
    const panel = document.getElementById('carrito-panel');
    if (!panel) return;
    panel.classList.add('drawer-abierto');
    document.getElementById('carrito-fondo').classList.remove('opacity-0', 'pointer-events-none');
    document.body.style.overflow = 'hidden';
}

function cerrarCarrito() {
    const panel = document.getElementById('carrito-panel');
    if (!panel) return;
    panel.classList.remove('drawer-abierto');
    document.getElementById('carrito-fondo').classList.add('opacity-0', 'pointer-events-none');
    document.body.style.overflow = '';
}

/* --------------------------------------------------------------------------
   11. Modales, avisos y menú móvil
   -------------------------------------------------------------------------- */

function abrirModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('opacity-0', 'pointer-events-none');
    document.body.style.overflow = 'hidden';
}

function cerrarModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('opacity-0', 'pointer-events-none');
    document.body.style.overflow = '';
}

function cerrarPopup() { cerrarModal('popup-oferta'); }

let temporizadorAviso = null;

/** Mensaje flotante breve (producto agregado, carrito vacío, etc.) */
function avisar(texto) {
    const aviso = document.getElementById('aviso');
    if (!aviso) return;
    aviso.textContent = texto;
    aviso.classList.remove('opacity-0', 'translate-y-4');
    clearTimeout(temporizadorAviso);
    temporizadorAviso = setTimeout(() => {
        aviso.classList.add('opacity-0', 'translate-y-4');
    }, 2200);
}

/* --------------------------------------------------------------------------
   Tema claro / oscuro
   El tema inicial ya lo fija un script en el <head> (antes de pintar).
   Aquí solo lo alternamos, lo recordamos y sincronizamos el ícono.
   -------------------------------------------------------------------------- */

/** Ajusta el ícono del botón según el tema activo. */
function sincronizarIconoTema() {
    const oscuro = document.documentElement.classList.contains('dark');
    const icono = document.getElementById('icono-tema');
    if (icono) icono.textContent = oscuro ? 'light_mode' : 'dark_mode';
}

/** Alterna entre claro y oscuro y guarda la preferencia del usuario. */
function alternarTema() {
    const raiz = document.documentElement;
    const oscuro = !raiz.classList.contains('dark');
    raiz.classList.toggle('dark', oscuro);
    raiz.classList.toggle('light', !oscuro);
    try {
        localStorage.setItem('tema', oscuro ? 'oscuro' : 'claro');
    } catch (_e) { /* almacenamiento no disponible: se usa el tema de la sesión */ }
    sincronizarIconoTema();
}

/* Mega menú de categorías. En escritorio lo abre el botón «Categorías»; en
   móvil, el botón de menú. Es el mismo panel en los dos casos. */
function alternarMegaMenu(abrir) {
    const panel = document.getElementById('mega-menu');
    if (!panel) return;
    const boton = document.getElementById('btn-mega');
    const flecha = document.getElementById('mega-flecha');

    const visible = abrir === undefined ? panel.classList.contains('hidden') : abrir;
    panel.classList.toggle('hidden', !visible);
    if (boton) boton.setAttribute('aria-expanded', String(visible));
    if (flecha) flecha.classList.toggle('rotate-180', visible);
}

function cerrarMegaMenu() {
    alternarMegaMenu(false);
}

/* --------------------------------------------------------------------------
   11.b Buscador con sugerencias

   Los dos buscadores (header y móvil) escriben en el mismo filtro, así que
   escribir en uno filtra el catálogo igual que antes; lo nuevo es la lista de
   sugerencias que sale debajo. Al elegir una se abre la ficha del producto.
   -------------------------------------------------------------------------- */

/** Hasta 7 productos cuyo nombre o categoría empiece o contenga lo escrito. */
function sugerenciasPara(texto) {
    const q = normalizar(texto.trim());
    if (q.length < 2) return [];

    const puntaje = (p) => {
        const nombre = normalizar(p.producto);
        if (nombre.startsWith(q)) return 0;              // empieza igual: lo más relevante
        if (nombre.includes(q)) return 1;
        if (normalizar((p.categorias || []).join(' ')).includes(q)) return 2;
        return 99;
    };

    return PRODUCTS
        .map((p) => ({ p, s: puntaje(p) }))
        .filter((x) => x.s < 99)
        .sort((a, b) => a.s - b.s || a.p.producto.localeCompare(b.p.producto, 'es'))
        .slice(0, 7)
        .map((x) => x.p);
}

/** Resalta en negrita el trozo que coincide con lo escrito. */
function resaltar(nombre, texto) {
    const q = normalizar(texto.trim());
    const i = normalizar(nombre).indexOf(q);
    if (q.length < 2 || i === -1) return escapar(nombre);
    return escapar(nombre.slice(0, i)) +
        `<strong class="text-primary">${escapar(nombre.slice(i, i + q.length))}</strong>` +
        escapar(nombre.slice(i + q.length));
}

function conectarBuscador(idInput, idLista) {
    const input = document.getElementById(idInput);
    const lista = document.getElementById(idLista);
    if (!input || !lista) return;

    let activa = -1;      // fila resaltada con las flechas
    let visibles = [];

    const cerrar = () => {
        lista.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        activa = -1;
    };

    const pintar = () => {
        visibles = sugerenciasPara(input.value);
        if (!visibles.length) return cerrar();

        lista.innerHTML = visibles.map((p, i) => `
            <li role="option" aria-selected="${i === activa}"
                class="flex items-center gap-sm px-sm py-2 cursor-pointer border-b border-outline-variant/40 last:border-0 ${
                    i === activa ? 'bg-primary/10' : 'hover:bg-surface-container'
                }"
                onmousedown="event.preventDefault(); elegirSugerencia(${p.id}, '${idInput}', '${idLista}')">
                <div class="w-10 h-10 rounded-lg overflow-hidden shrink-0 bg-surface-container">
                    ${bloqueImagen(p, 'h-10', true)}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-on-surface leading-snug linea-2">${resaltar(p.producto, input.value)}</p>
                    <p class="text-[11px] text-on-surface-variant">${escapar(etiquetaCategoria(p))}</p>
                </div>
                <span class="text-xs font-bold text-primary shrink-0">${soles(p.pvp)}</span>
            </li>
        `).join('');

        lista.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
    };

    input.addEventListener('input', () => {
        filtros.busqueda = input.value;
        // Mantener los dos buscadores en sintonía.
        const otro = document.getElementById(idInput === 'buscador' ? 'buscador-movil' : 'buscador');
        if (otro && otro.value !== input.value) otro.value = input.value;

        activa = -1;
        reiniciarPagina();
        pintarCatalogo();
        pintar();
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') return cerrar();
        if (!visibles.length) return;

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            activa = (activa + (e.key === 'ArrowDown' ? 1 : -1) + visibles.length) % visibles.length;
            pintar();
        } else if (e.key === 'Enter' && activa >= 0) {
            e.preventDefault();
            elegirSugerencia(visibles[activa].id, idInput, idLista);
        }
    });

    input.addEventListener('focus', pintar);
    input.addEventListener('blur', () => setTimeout(cerrar, 120));
}

function elegirSugerencia(id, idInput, idLista) {
    document.getElementById(idLista).classList.add('hidden');
    document.getElementById(idInput).blur();
    abrirFichaProducto(id);
}

/* --------------------------------------------------------------------------
   12. Arranque
   -------------------------------------------------------------------------- */

function conectarControles() {
    // Buscadores (el del header y el de móvil), con sugerencias al escribir
    conectarBuscador('buscador', 'sugerencias');
    conectarBuscador('buscador-movil', 'sugerencias-movil');

    // Orden
    const orden = document.getElementById('orden');
    if (orden) orden.addEventListener('change', (e) => {
        filtros.orden = e.target.value;
        reiniciarPagina();
        pintarCatalogo();
    });

    // Productos por página
    const porPagina = document.getElementById('por-pagina');
    if (porPagina) porPagina.addEventListener('change', (e) => {
        filtros.porPagina = Number(e.target.value);
        reiniciarPagina();
        pintarCatalogo();
    });

    // Cerrar el mega menú al hacer clic fuera de él
    document.addEventListener('click', (e) => {
        const panel = document.getElementById('mega-menu');
        if (!panel || panel.classList.contains('hidden')) return;
        if (e.target.closest('#mega-menu') || e.target.closest('#btn-mega') || e.target.closest('[onclick*="alternarMegaMenu"]')) return;
        cerrarMegaMenu();
    });

    // Filtro por rango de precio
    const min = document.getElementById('precio-min');
    const max = document.getElementById('precio-max');
    const slider = document.getElementById('precio-slider');

    // El filtro de precio solo existe en la página del catálogo.
    if (min && max && slider) {
        // Los topes salen de los precios reales del catálogo.
        min.placeholder = LIMITES_PRECIO.min;
        max.placeholder = LIMITES_PRECIO.max;
        [min, max].forEach((campo) => {
            campo.min = LIMITES_PRECIO.min;
            campo.max = LIMITES_PRECIO.max;
        });
        slider.min = LIMITES_PRECIO.min;
        slider.max = LIMITES_PRECIO.max;
        slider.value = LIMITES_PRECIO.max;

        // Escribir en las cajas manda; la barra solo mueve el tope máximo.
        const desdeCajas = () => {
            filtros.precioMin = min.value === '' ? null : Number(min.value);
            filtros.precioMax = max.value === '' ? null : Number(max.value);
            slider.value = filtros.precioMax === null ? LIMITES_PRECIO.max : filtros.precioMax;
            aplicarFiltroPrecio();
        };
        min.addEventListener('input', desdeCajas);
        max.addEventListener('input', desdeCajas);

        slider.addEventListener('input', () => {
            max.value = slider.value;
            filtros.precioMax = Number(slider.value);
            aplicarFiltroPrecio();
        });

        sincronizarFiltroPrecio();
    }

    // Cerrar carrito / modales con la tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        cerrarCarrito();
        cerrarFichaProducto();
        cerrarPopup();
        cerrarMegaMenu();
    });

    // Desplazamiento suave en los enlaces internos
    document.querySelectorAll('a[href^="#"]').forEach((a) => {
        a.addEventListener('click', (e) => {
            const destino = a.getAttribute('href');
            if (destino === '#' || !document.querySelector(destino)) return;
            e.preventDefault();
            document.querySelector(destino).scrollIntoView({ behavior: 'smooth' });
        });
    });
}

/** Popup de bienvenida: a los 6 segundos o al llegar al 40% de la página. */
function programarPopup() {
    let mostrado = false;
    const mostrar = () => {
        if (mostrado) return;
        mostrado = true;
        abrirModal('popup-oferta');
    };

    setTimeout(mostrar, 6000);
    window.addEventListener('scroll', () => {
        const avance = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight;
        if (avance > 0.4) mostrar();
    }, { passive: true });
}

document.addEventListener('DOMContentLoaded', () => {
    // El aviso de precios por cantidad se escribe desde CONFIG para que exista
    // un solo texto oficial en toda la web.
    document.querySelectorAll('[data-nota-cantidad]').forEach((el) => {
        el.textContent = CONFIG.notaCantidad;
    });

    // Todos los enlaces sueltos a WhatsApp apuntan al número configurado.
    document.querySelectorAll('[data-wa]').forEach((el) => {
        el.href = enlaceWhatsApp(el.dataset.wa);
    });

    sincronizarIconoTema();

    // Total de productos en el hero (evita cifras desactualizadas al crecer el
    // catálogo). Solo el index: en la página de afiliación se usa el claim
    // oficial «+60 productos», porque 30 de los 87 registros son packs y la
    // cifra del catálogo infla el dato (ver comentario en afiliacion.html).
    const heroTotal = document.getElementById('hero-total');
    if (heroTotal) heroTotal.textContent = PRODUCTS.length;

    // Montos que dependen de PLANES_AFILIACION y viven dentro de textos
    // corridos del HTML. Se pintan desde aquí para que no haya cifras de plan
    // escritas a mano en las páginas.
    const entrada = planDeEntrada();
    if (entrada) {
        document.querySelectorAll('[data-monto-entrada]').forEach((el) => {
            el.textContent = solesEnPantalla(entrada.inversion);
        });
    }
    document.querySelectorAll('[data-rango-descuento]').forEach((el) => {
        el.textContent = rangoDeDescuentos();
    });

    // Imagen del hero: manda CONFIG, así hay un único lugar que editar.
    // El src del HTML es solo el respaldo inicial; si coincide no se vuelve a
    // descargar la foto.
    const heroImagen = document.getElementById('hero-imagen');
    if (heroImagen && CONFIG.heroImagen) {
        if (heroImagen.getAttribute('src') !== CONFIG.heroImagen) {
            heroImagen.style.display = '';
            heroImagen.src = CONFIG.heroImagen;
        }
        if (CONFIG.heroImagenAlt) heroImagen.alt = CONFIG.heroImagenAlt;
    }

    // Planes de afiliación: los pinta donde exista el contenedor, así el
    // index y afiliacion.html muestran exactamente los mismos precios.
    pintarPlanes('planes-afiliacion');
    pintarSelectorDePlanes();
    pintarNivelesDeMargen();
    pintarEjemploDeVenta();

    // Si se llega desde otra página con ?categoria=… (por ejemplo, al elegir
    // una categoría en el menú de la página de afiliación), el catálogo abre
    // ya filtrado.
    try {
        const cat = new URLSearchParams(window.location.search).get('categoria');
        if (cat && document.getElementById('grid-productos')) filtros.categoria = cat;
    } catch (_e) { /* navegador sin URLSearchParams: se ignora el filtro */ }

    pintarCategorias();
    pintarCatalogo();
    pintarCarrito();
    conectarControles();
    programarPopup();
});
