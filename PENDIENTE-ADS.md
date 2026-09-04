# Pendiente antes (y después) de encender Google Ads

Revisión del 3 de septiembre de 2026, actualizada el 4 de septiembre. Este
archivo no se sirve por web: el `.htaccess` de la raíz bloquea los `.md`.

Está ordenado por lo que cuesta dinero, no por lo que cuesta trabajo. Marca con
`[x]` lo que vayas cerrando.

**Ya hecho el 3 de septiembre:** página 404 propia (`404.php`), Tailwind
compilado en vez del CDN, y el repaso de seguridad (ver `DESPLIEGUE.md`).

**Ya hecho el 4 de septiembre:** preguntas frecuentes en las dos landings (7),
foto de portada propia y en alta (9) y favicon (11). Los puntos 5 y 6 quedan
descartados por decisión tuya; abajo se explica qué implica.

---

## 🔴 Bloqueante — no enciendas la campaña sin esto

> **Paso a paso desde cero de los puntos 1 y 2: `MEDICION.md`.** Lo de aquí
> abajo es el porqué; allí está el dónde pulsar.

### [ ] 1. Medición de conversiones

**Dónde:** `config.js` → `ADS_CONFIG.conversionId` y `conversionLabel` están
vacíos, y `inc/partes/cabeza.php` → `SN_GTAG_ADS` también.

**Qué pasa ahora:** el código para disparar la conversión ya existe y está bien
hecho, pero se autodesactiva solo mientras esos dos valores estén vacíos
(`store.js` → `dispararConversionAds()` comprueba `ADS_CONFIG.conversionId`).
O sea: la campaña funcionaría, pero sin registrar ni una conversión.

**Por qué importa:** sin conversiones no sabes qué palabra clave trae contactos,
no puedes usar Maximizar conversiones ni CPA objetivo (necesitan datos para
aprender), y Google reparte tu presupuesto a ciegas. Es la diferencia entre
gastar y aprender.

**Cómo:** Google Ads → Objetivos → Conversiones → tu acción de conversión →
Configuración de etiqueta. Verás una línea así:

```
gtag('event', 'conversion', {'send_to': 'AW-123456789/AbC-D_efGhIjKlMn'})
```

Lo de antes de la barra va en `conversionId`; lo de después, en
`conversionLabel`. El mismo `AW-…` va también en `SN_GTAG_ADS` de `cabeza.php`.
Ya no hay que descomentar nada: la etiqueta se escribe sola en cuanto esas
constantes dejen de estar vacías.

**Ojo con qué estás midiendo:** la conversión es el *clic* al botón de WhatsApp,
no una venta cerrada. Sirve para optimizar, pero no confundas conversiones con
pedidos: el cierre pasa por el asesor y eso Google no lo ve.

### [ ] 2. Google Analytics (GA4)

No hay ninguno instalado. Sin él no sabes si la gente llega y se va a los tres
segundos, ni por dónde abandona, ni qué productos mira.

Se instala en el mismo sitio y con la misma etiqueta que el de Ads: pegar el
`G-…` en `SN_GTAG_GA4` de `cabeza.php`. Con las dos constantes vacías la web no
carga NADA de Google —ni una petición, ni una cookie—, que es la razón de que
hoy no haga falta aviso de cookies.

---

## 🟠 Riesgo de que te desaprueben los anuncios

### [ ] 3. Afirmaciones de salud en las descripciones

Salud es categoría restringida en Google Ads, y en Perú DIGEMID no permite
atribuir propiedades terapéuticas a un suplemento. Decir que un producto
*previene*, *combate* o *desinflama* una dolencia es justo lo que se revisa.

Se editan desde el panel (`/gestion-sn/` → el producto → Descripción). Son
estas, con su id:

| id | Producto | Estado | Frase a revisar |
|---|---|---|---|
| 34 | Chancapiedra x 500 ml | **activo** | «indispensable para **prevenir cálculos** en los riñones y en la vesícula» |
| 10 | Caja sachet 15u toxizero | **activo** | «**combate el estreñimiento**, limpia el colon y desintoxica las vías digestivas» |
| 44 | Frota-T x 90 g | **activo** | «**alivia el dolor** muscular y articular de forma natural» |
| 102 | PACK CRECIMIENTO **GARANTIZADO** DS30 | **activo** | el problema está en el nombre, no en la descripción |
| 67 | PACK KALMAPROSS INTEGRAL | oculto | «concentrado **desinflamante natural para la próstata**» |
| 71 | PACK HOMBRE VIRILIDAD | oculto | «Kalmapross como **desinflamante natural para la próstata**» |

**Buena noticia:** ninguno de estos seis sale en las dos landings de Ads
(`/packs/` muestra los ids 88, 96, 90, 89, 92, 98, 91, 93; `/packs/colageno/`
los 13, 14, 15, 89, 101, 93, 104). Pero Google revisa el sitio entero, y los
cuatro activos sí están en el catálogo de la portada.

**Cómo redactarlo:** ya lo estás haciendo bien en otras fichas. El patrón que
funciona es describir el uso, no prometer el resultado:

- ❌ «indispensable para prevenir cálculos en los riñones»
- ✅ «Uso tradicional: la medicina tradicional peruana la asocia al cuidado
  renal. Presentación de 500 ml.»

Y quitar «GARANTIZADO» del nombre del pack 102.

### [ ] 4. Cifras de ingresos en `afiliacion.php`

La página trae «S/ 1,500 a S/ 2,000» y «S/ 4,000 a S/ 5,000 de ingreso
estimado». Google restringe la publicidad de oportunidades de negocio.

**Lo que ya está bien:** llevas el descargo («no constituyen una promesa de
ingresos») y quitaste «Afíliate» de las dos landings, que era lo importante.

**Lo que falta decidir:** la **portada** tiene una sección entera de planes de
afiliación que enlaza ahí. Si el anuncio apunta a la portada, el revisor la ve.

> **Recomendación: que los anuncios apunten a `/packs/` y `/packs/colageno/`,
> nunca a la portada.** Es gratis y quita el problema de encima.

---

## 🟡 Información de negocio que falta (política de Tergiversación)

### [~] 5. Identidad del negocio — DESCARTADO el 4/9/2026

No aparecen en ninguna página: **RUC, dirección, correo ni teléfono**. Solo
WhatsApp. Y la barra promocional dice «recojo en tienda a nivel nacional» sin
decir en qué tienda ni dónde.

**Decisión tuya:** no publicar datos personales por seguridad. Se respeta.

Lo que hay que saber para no llevarse un susto: Google pide identidad clara en
sitios que venden, y si un revisor abre la web y no encuentra quién está detrás,
puede desaprobar por Tergiversación. Si eso llega a pasar, la salida más barata
**no** es publicar tu dirección de casa: basta con datos de negocio, que son
públicos igualmente (RUC y razón social salen en la consulta RUC de SUNAT) y un
correo de contacto que no sea el personal. Si prefieres no poner nada, ten
preparada la respuesta por si Google la pide.

### [~] 6. Páginas legales — DESCARTADO el 4/9/2026

No existe ninguna: ni privacidad, ni términos, ni envíos y devoluciones.

**Decisión tuya:** para una landing de aterrizaje no hacen falta. Se respeta, y
lo de envíos y devoluciones ya está cubierto de otra forma: las preguntas
frecuentes del punto 7 responden envío, plazo, pago y qué hacer si el producto
llega mal.

Dos matices que conviene tener por escrito, para el día que cambie algo:

- La **política de privacidad** deja de ser opcional el día que actives
  **remarketing o audiencias** en Google Ads: la política de datos de Google la
  exige, y en Perú aplica la Ley 29733 de Protección de Datos Personales. Hoy no
  hace falta porque no hay ni píxel de remarketing ni formulario que guarde
  datos: `enviarAfiliacion()` solo abre WhatsApp con el mensaje escrito, no
  almacena nada en tu servidor.
- Si algún día se venden productos con pago en línea (hoy no: todo se cierra por
  WhatsApp), términos y devoluciones pasan a ser obligatorios.

Si hay que hacerlas, se hacen como `afiliacion.php`: PHP normal con
`sn_cabeza()`, `sn_header()` y `sn_pie()`, enlazadas desde el pie.

---

## 🟢 Calidad de la página de destino (te abarata o encarece el clic)

### [x] 7. ~~La landing `/packs/` está delgada~~ — HECHO el 4/9/2026

Se añadió un bloque de **preguntas frecuentes** a las dos landings, debajo de
`sn_bloque_cobertura()`. Son siete comunes (`SN_FAQ` en `inc/render.php`) más
una propia de cada página: en `/packs/`, qué se gana comprando el pack en vez de
los productos sueltos; en `/packs/colageno/`, en qué se diferencian el Plus, el
Premium y el de maracuyá y camu camu.

Se abre y se cierra con `<details>`/`<summary>`, sin una línea de JavaScript, y
lleva su JSON-LD de tipo `FAQPage`.

**Al editarlas, dos reglas:**

1. Solo lo que la web cumpla de verdad. Nada de «llega en 24 horas» ni de un
   porcentaje de devolución que después no se sostenga: el envío y la fecha se
   cierran por WhatsApp, caso por caso, y eso es justo lo que dicen.
2. Nada de propiedades curativas, por lo mismo del punto 3. La última pregunta
   («¿Son medicamentos?») está puesta a propósito para dejarlo claro ante un
   revisor de Google.

**Queda una por confirmar contigo**, marcada con `REVISAR CON EL ASESOR` en el
código: la de «¿Y si el producto llega dañado o no es el que pedí?» promete un
cambio si avisas el mismo día con una foto. Es lo mínimo que espera quien compra
sin ver el producto, pero si tu política real es otra, cámbiala en `SN_FAQ`.

### [x] 8. ~~23 productos publicados sin foto~~ — HECHO el 3/9/2026

Se subieron las 23 que faltaban desde el panel. Comprobado: **los 74 productos
activos tienen foto** y ninguna ficha apunta a una imagen que no exista.

Para volver a comprobarlo cuando des de alta productos nuevos:
`/gestion-sn/?f=sinfoto`.

### [x] 9. ~~La foto del hero vive en un servidor ajeno~~ — HECHO el 4/9/2026

Ahora es `img/hero-portada.jpg`, 1600x1280 (la proporción 5:4 del marco), 168 KB.

No se bajó la que había, se hizo otra. La anterior era una ilustración generada
por IA de la maqueta de Stitch: a 512x279 y, al ampliarla, con las etiquetas de
los frascos deformadas y con faltas. La nueva se compone con las **fotos reales
de producto** que la tienda oficial publica a resolución completa (1080-1600 px):
aloe vera, EnfoK+, uña de gato, toxizero, colágeno hidrolizado y chancapiedra.

Se regenera con:

```
C:/xampp/php/php.exe -d extension=gd scripts/componer-hero.php
```

Para cambiar qué productos salen o dónde, se toca `$PIEZAS` dentro del script.
**Las piezas no pueden solaparse**: la mezcla es «multiplicar», que no tapa lo
que hay debajo, así que dos productos encimados se transparentarían.

Y si cambias la ruta, cámbiala **en los dos sitios**: `CONFIG.heroImagen` de
`store.js` y el `src` del `<img id="hero-imagen">` de `index.php`. Tienen que
decir exactamente lo mismo, porque store.js compara las dos cadenas y, si no
calzan, reasigna el `src` y el navegador se baja la foto dos veces. Justo la
que Google mide como LCP.

---

## ⚪ Detalles que restan confianza

### [ ] 10. Facebook e Instagram apuntan a `#`

`inc/partes/comunes.php`, dentro de `sn_pie()`. Al estar en el pie común salen
en **todas** las páginas, landings incluidas: enlaces muertos en el destino de
un anuncio. O se ponen las URLs reales, o se quitan los dos iconos.

### [x] 11. ~~No hay favicon~~ — HECHO el 4/9/2026

Sale del **isotipo oficial** de Santa Natura (el globo de hojas). Tres archivos:

- `favicon.ico` — 16, 32 y 48 px dentro del mismo archivo; el navegador elige.
- `apple-touch-icon.png` — 180x180, sin transparencia, porque iOS pinta negro
  detrás de lo que sea transparente.
- `img/icono-192.png` — Android y pantallas de alta densidad.

Los `<link>` van en `inc/partes/cabeza.php`, así que salen en todas las páginas.

Se regenera con:

```
C:/xampp/php/php.exe -d extension=gd scripts/generar-favicon.php
```

El isotipo lleva un **círculo blanco detrás a propósito**: el verde de la marca
es oscuro y sobre una barra de pestañas en tema oscuro casi no se distingue. Si
alguna vez lo prefieres «a pelo», pon `$FONDO = false` en el script.

### [ ] 12. No hay etiquetas `og:`

Al compartir cualquier página por WhatsApp sale el enlace pelado, sin foto ni
texto. Con tráfico pagado, cada compartido que no luce es un clic gratis
desaprovechado. Se ponen en `sn_cabeza()` y sirven para todas las páginas de
una vez: `og:title`, `og:description`, `og:image` (en URL absoluta, ya tienes
`sn_site_url()`), `og:url` y `og:type`.

> **Nota:** el botón de «compartir producto» que se habló el 3 de septiembre
> quedó aparcado por decisión tuya. Si algún día se retoma, necesita esto
> mismo más una URL por producto (`index.php?producto=93`) servida por PHP:
> el robot de WhatsApp no ejecuta JavaScript, así que un `#producto=93` no
> sirve. Las fotos actuales (300 px de ancho la mayoría) darían la vista
> previa pequeña, no la tarjeta grande; para eso harían falta ~1200×630.

---

## Orden sugerido

Lo que queda, de arriba abajo:

1. Conversiones + GA4 (1 y 2) — **antes de gastar el primer sol**
2. Afirmaciones de salud y nombre del pack 102 (3)
3. Apuntar los anuncios solo a las landings (4) — decisión, no trabajo
4. Enlaces muertos de Facebook e Instagram (10)
5. Etiquetas `og:` (12)

Cerrados: 7, 8, 9 y 11. Descartados por decisión: 5 y 6.
