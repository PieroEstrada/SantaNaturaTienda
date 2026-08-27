/* ============================================================================
   Santa Natura — Configuración compartida
   ----------------------------------------------------------------------------
   Este archivo se carga PRIMERO en todas las páginas (index.html, /packs y
   /packs/colageno). Aquí viven los datos que antes estaban escritos a mano en
   el HTML o repartidos por store.js, para que exista un solo lugar que editar.

   Orden de carga obligatorio en cada página:
       config.js  →  products.js  →  render-productos.js  →  store.js
   ========================================================================== */

/* --------------------------------------------------------------------------
   Número de WhatsApp del asesor
   --------------------------------------------------------------------------
   Con código de país, sin signos ni espacios. Se usa en TODOS los botones de
   la web y también al pre-generar las landings con scripts/build-landings.js.
   Cambiarlo aquí lo cambia en todos lados. NO lo escribas en ningún otro sitio.
   -------------------------------------------------------------------------- */
const WHATSAPP_NUMERO = '51924729480';

/* --------------------------------------------------------------------------
   Fecha de la lista de precios vigente
   --------------------------------------------------------------------------
   Antes estaba quemada en el pie de index.html. Ahora la pintan todas las
   páginas desde aquí, en cualquier elemento con [data-lista-precios].
   Formato: MES AÑO en mayúsculas (el pie ya lo muestra en versalitas).
   -------------------------------------------------------------------------- */
const PRICE_LIST_DATE = 'AGOSTO 2026';

/* --------------------------------------------------------------------------
   Google Ads — medición del clic a WhatsApp
   --------------------------------------------------------------------------
   El clic a WhatsApp es el evento de conversión de la campaña.

   DÓNDE SACAR ESTOS DOS VALORES:
     Google Ads → Objetivos → Conversiones → (tu acción de conversión) →
     "Configuración de etiqueta" → "Usar Google Tag Manager" o "Instalar la
     etiqueta manualmente". Ahí verás una línea así:

         gtag('event', 'conversion', {'send_to': 'AW-123456789/AbC-D_efGhIjKlMn'})

     La parte de ANTES de la barra ('AW-123456789') va en conversionId.
     La parte de DESPUÉS de la barra ('AbC-D_efGhIjKlMn') va en conversionLabel.

   Mientras estén vacías NO se dispara ninguna conversión (pero el dataLayer
   sigue registrando los clics, así que Tag Manager ya puede leerlos).

   Falta también descomentar el snippet de gtag.js en el <head> de las tres
   páginas y poner ahí el mismo AW-… de conversionId.
   -------------------------------------------------------------------------- */
const ADS_CONFIG = {
    conversionId: '',      // p. ej. 'AW-123456789'
    conversionLabel: ''    // p. ej. 'AbC-D_efGhIjKlMn'
};

/* --------------------------------------------------------------------------
   Dominio del sitio
   --------------------------------------------------------------------------
   Se usa para las URLs absolutas del JSON-LD y de <link rel="canonical">.
   Sin barra final.
   -------------------------------------------------------------------------- */
const SITE_URL = 'https://santanatura.inmuno.lat';

/* --------------------------------------------------------------------------
   Mensaje de WhatsApp prellenado, según la página
   --------------------------------------------------------------------------
   La clave sale de <html data-pagina="…">. Los botones genéricos (el flotante,
   el del header, la barra promocional) usan el mensaje de su página; los
   botones con un <a data-wa="texto propio"> conservan el suyo.
   -------------------------------------------------------------------------- */
const MENSAJES_WA = {
    home:     'Hola, quiero hacer una consulta sobre los productos Santa Natura.',
    packs:    'Hola, vengo de Google y quiero información sobre los packs con descuento.',
    colageno: 'Hola, vengo de Google y quiero información sobre los packs de colágeno con descuento.'
};

/* Mensaje que usa el CTA de WhatsApp de cada tarjeta de producto. Recibe el
   nombre REAL del producto (campo `producto` de products.js), nunca el texto
   que se ve en el DOM. */
const mensajeDeProducto = (nombre) => `Hola, quiero información sobre: ${nombre}`;

/* Exporta para scripts/build-landings.js (en el navegador `module` no existe). */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { WHATSAPP_NUMERO, PRICE_LIST_DATE, ADS_CONFIG, SITE_URL, MENSAJES_WA, mensajeDeProducto };
}
