# Pendiente antes (y después) de encender Google Ads

Revisión del 3 de septiembre de 2026. Este archivo no se sirve por web: el
`.htaccess` de la raíz bloquea los `.md`.

Está ordenado por lo que cuesta dinero, no por lo que cuesta trabajo. Marca con
`[x]` lo que vayas cerrando.

**Ya hecho en esta revisión:** página 404 propia (`404.php`), Tailwind compilado
en vez del CDN, y el repaso de seguridad (ver `DESPLIEGUE.md`).

---

## 🔴 Bloqueante — no enciendas la campaña sin esto

### [ ] 1. Medición de conversiones

**Dónde:** `config.js` → `ADS_CONFIG.conversionId` y `conversionLabel` están
vacíos. Y en `inc/partes/cabeza.php` el bloque de `gtag.js` sigue comentado
(busca `AW-XXXXXXXXXX`).

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
`conversionLabel`. El mismo `AW-…` va también en las cuatro líneas comentadas
de `cabeza.php`, que hay que descomentar. El propio `config.js` lo explica.

**Ojo con qué estás midiendo:** la conversión es el *clic* al botón de WhatsApp,
no una venta cerrada. Sirve para optimizar, pero no confundas conversiones con
pedidos: el cierre pasa por el asesor y eso Google no lo ve.

### [ ] 2. Google Analytics (GA4)

No hay ninguno instalado. Sin él no sabes si la gente llega y se va a los tres
segundos, ni por dónde abandona, ni qué productos mira. Se instala en el mismo
sitio de `cabeza.php`, junto al de Ads.

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

### [ ] 5. Identidad del negocio

No aparecen en ninguna página: **RUC, dirección, correo ni teléfono**. Solo
WhatsApp. Y la barra promocional dice «recojo en tienda a nivel nacional» sin
decir en qué tienda ni dónde.

Google pide identidad clara en sitios que venden. Lo mínimo: RUC, una dirección
real y un correo, en el pie (`inc/partes/comunes.php` → `sn_pie()`), que sale en
todas las páginas de una vez.

### [ ] 6. Páginas legales

No existe ninguna. Hacen falta tres:

- **Política de privacidad** — obligatoria si algún día activas remarketing
  (política de datos de Google Ads), y aplica la Ley 29733 de Protección de
  Datos Personales del Perú. El formulario de afiliación pide nombre, teléfono
  y ciudad; no se guardan en tu servidor (`enviarAfiliacion()` solo abre
  WhatsApp con el mensaje escrito), pero se recogen igual.
- **Términos y condiciones**
- **Política de envíos y devoluciones** — lo que más pregunta quien compra

Se hacen como `afiliacion.php`: PHP normal usando `sn_cabeza()`, `sn_header()`
y `sn_pie()`. Enlazarlas desde el pie.

---

## 🟢 Calidad de la página de destino (te abarata o encarece el clic)

### [ ] 7. La landing `/packs/` está delgada

Su estructura completa es: barra promo → cabecera → un titular → la rejilla de
packs → pie. Google puntúa la experiencia del destino, y contenido relevante y
original es parte de la nota. Falta lo que un comprador pregunta antes de
decidir:

- cuánto tarda el envío y cuánto cuesta
- qué pasa si el producto no le gusta
- por qué fiarse (años, distribuidor autorizado, clientes)
- preguntas frecuentes

Las formas de pago y el horario **sí** están, en el pie (`sn_bloque_cobertura()`).

### [x] 8. ~~23 productos publicados sin foto~~ — HECHO el 3/9/2026

Se subieron las 23 que faltaban desde el panel. Comprobado: **los 74 productos
activos tienen foto** y ninguna ficha apunta a una imagen que no exista.

Para volver a comprobarlo cuando des de alta productos nuevos:
`/gestion-sn/?f=sinfoto`.

### [ ] 9. La foto del hero vive en un servidor ajeno

`store.js` → `CONFIG.heroImagen` apunta a una URL de `googleusercontent.com`.
Es el elemento más grande de la portada —justo el que Google mide como LCP— y
está en un servidor que no controlas y que puede dejar de servirla cualquier
día. Hay que bajarla a `img/` y cambiar esa constante.

---

## ⚪ Detalles que restan confianza

### [ ] 10. Facebook e Instagram apuntan a `#`

`inc/partes/comunes.php`, dentro de `sn_pie()`. Al estar en el pie común salen
en **todas** las páginas, landings incluidas: enlaces muertos en el destino de
un anuncio. O se ponen las URLs reales, o se quitan los dos iconos.

### [ ] 11. No hay favicon

La pestaña del navegador sale en blanco. Hace falta un `favicon.ico` (o un
`.png` de 32×32 y otro de 180×180 para iOS) y sus `<link>` en
`inc/partes/cabeza.php`.

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

1. Conversiones + GA4 (1 y 2) — **antes de gastar el primer sol**
2. Afirmaciones de salud y nombre del pack 102 (3)
3. Apuntar los anuncios solo a las landings (4) — decisión, no trabajo
4. Enlaces muertos, favicon, foto del hero a `img/` (9, 10, 11)
5. Páginas legales y datos de negocio (5 y 6)
6. Engordar la landing (7) y las fotos que faltan (8)
7. Etiquetas `og:` (12)
