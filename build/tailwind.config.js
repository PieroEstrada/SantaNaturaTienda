/* ============================================================================
   Configuración de Tailwind
   ----------------------------------------------------------------------------
   Esto vivía incrustado en el <head> de TODAS las páginas: unas 80 líneas de
   JavaScript que el navegador de cada visitante tenía que leer y ejecutar antes
   de poder pintar nada. Ahora se lee una sola vez, aquí, en tu ordenador, y de
   ahí sale ../tailwind.css ya hecho.

   Para regenerar la hoja después de tocar clases en el HTML o el JS:

       cd build
       npm run build          (una vez)
       npm run dev            (se queda vigilando y regenera al guardar)

   NO hace falta regenerar al cambiar precios, fotos o productos desde el panel:
   eso no toca estilos.
   ========================================================================== */

/* ----------------------------------------------------------------------------
   Dónde buscar clases
   ----------------------------------------------------------------------------
   Tailwind lee estos archivos y solo genera el CSS de las clases que encuentra
   escritas. Por eso la hoja pesa unos pocos KB en vez de megas.

   OJO: si algún día se arma un nombre de clase a pedazos —`text-${color}`—,
   Tailwind no puede verlo y ese estilo saldrá vacío. Hay que escribir la clase
   entera en las dos ramas del condicional, que es como está hecho hoy en
   store.js y en render-productos.js.

   No se listan aquí:
     products.js      son datos, no lleva clases
     inc/copias/      copias del catálogo que hace el panel
     gestion-sn/      el panel tiene su propio CSS, no usa Tailwind
   -------------------------------------------------------------------------- */
module.exports = {
    content: [
        '../index.php',
        '../afiliacion.php',
        '../packs/**/*.php',
        '../inc/**/*.php',
        '../config.js',
        '../render-productos.js',
        '../store.js',
    ],

    // El tema claro/oscuro se cambia poniendo y quitando la clase .dark en el
    // <html>, no por la preferencia del sistema: el visitante puede elegir.
    darkMode: 'class',

    theme: {
        extend: {
            /* Cada token apunta a una variable CSS con los canales RGB, definidas
               en styles.css. Así, al alternar .dark en el <html>, todo cambia de
               tema sin tocar cada elemento, y las clases con opacidad
               (bg-surface/90, text-primary/50…) siguen funcionando. */
            colors: Object.fromEntries([
                'inverse-on-surface', 'tertiary-fixed', 'on-tertiary-fixed', 'primary',
                'primary-container', 'on-primary-fixed-variant', 'surface-container-highest',
                'on-surface-variant', 'tertiary-container', 'secondary-fixed', 'secondary-fixed-dim',
                'on-secondary-fixed-variant', 'primary-fixed', 'inverse-primary', 'tertiary-fixed-dim',
                'on-secondary-fixed', 'on-tertiary-container', 'surface-bright', 'error-container',
                'on-primary', 'secondary', 'on-primary-container', 'on-secondary-container',
                'surface-container-high', 'on-primary-fixed', 'background', 'secondary-container',
                'on-error-container', 'outline', 'on-surface', 'outline-variant', 'surface-container',
                'on-background', 'on-error', 'surface-container-low', 'error', 'surface-tint',
                'inverse-surface', 'surface-dim', 'surface', 'on-tertiary', 'primary-fixed-dim',
                'on-tertiary-fixed-variant', 'surface-variant', 'surface-container-lowest',
                'on-secondary', 'tertiary',
            ].map((t) => [t, `rgb(var(--c-${t}) / <alpha-value>)`]).concat([
                // Colores de marca que NO cambian entre tema claro y oscuro:
                // el verde oficial de WhatsApp, el dorado de las estrellas de
                // valoración y el blanco puro del pie de página.
                ['action-whatsapp', '#25D366'],
                ['rating-gold', '#FFC107'],
                ['botanical-white', '#FFFFFF'],
            ])),

            borderRadius: {
                DEFAULT: '0.5rem',
                lg: '0.5rem',
                xl: '0.75rem',
                full: '9999px',
            },

            spacing: {
                base: '4px',
                xs: '8px',
                sm: '16px',
                md: '24px',
                lg: '40px',
                xl: '64px',
                gutter: '24px',
                'container-max': '1280px',
            },

            // Los títulos usan Plus Jakarta Sans (más carácter y mejor peso en
            // negrita); el cuerpo se queda en Inter.
            fontFamily: {
                'display-lg-mobile': ['Plus Jakarta Sans', 'Inter'],
                'label-md': ['Inter'],
                'label-caps': ['Inter'],
                'body-lg': ['Inter'],
                'headline-md-mobile': ['Plus Jakarta Sans', 'Inter'],
                'headline-md': ['Plus Jakarta Sans', 'Inter'],
                'body-md': ['Inter'],
                'display-lg': ['Plus Jakarta Sans', 'Inter'],
                'title-lg': ['Plus Jakarta Sans', 'Inter'],
                'title-sm': ['Plus Jakarta Sans', 'Inter'],
            },

            fontSize: {
                'display-lg-mobile': ['36px', { lineHeight: '42px', letterSpacing: '-0.02em', fontWeight: '700' }],
                'label-md': ['14px', { lineHeight: '20px', letterSpacing: '0.05em', fontWeight: '600' }],
                'label-caps': ['12px', { lineHeight: '16px', letterSpacing: '0.1em', fontWeight: '700' }],
                'body-lg': ['18px', { lineHeight: '28px', fontWeight: '400' }],
                'headline-md-mobile': ['24px', { lineHeight: '32px', fontWeight: '600' }],
                'headline-md': ['32px', { lineHeight: '40px', letterSpacing: '-0.01em', fontWeight: '600' }],
                'body-md': ['16px', { lineHeight: '24px', fontWeight: '400' }],
                'display-lg': ['48px', { lineHeight: '56px', letterSpacing: '-0.02em', fontWeight: '700' }],
                'title-lg': ['20px', { lineHeight: '28px', fontWeight: '600' }],
                'title-sm': ['20px', { lineHeight: '28px', fontWeight: '600' }],
            },
        },
    },

    // Los dos que traía el CDN en su URL (?plugins=forms,container-queries).
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/container-queries'),
    ],
};
