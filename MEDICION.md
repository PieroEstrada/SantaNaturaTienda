# Cómo instalar Google Analytics y las conversiones de Google Ads

Escrito el 4 de septiembre de 2026. Este archivo no se sirve por web: el
`.htaccess` de la raíz bloquea los `.md`.

Esto es lo pendiente de los puntos **1 y 2** de `PENDIENTE-ADS.md`, explicado
desde cero. Son los dos únicos puntos marcados en rojo: **sin esto, la campaña
gasta y no aprende nada**.

---

## Qué son y por qué las dos

Son dos cosas distintas que se instalan con **una sola etiqueta**:

| | Para qué sirve | Sin ella |
|---|---|---|
| **Google Analytics 4** | Ver cuánta gente entra, de dónde viene, qué mira y por dónde se va | No sabes si la gente entra y se va a los tres segundos |
| **Conversiones de Google Ads** | Decirle a Google «este clic sí trajo un contacto» | Google reparte tu presupuesto a ciegas: no puedes usar Maximizar conversiones ni CPA objetivo, porque no tienen datos con los que aprender |

**Qué cuenta como conversión aquí:** el **clic al botón de WhatsApp**. No una
venta. Sirve para optimizar la campaña, pero no confundas conversiones con
pedidos: el cierre lo hace el asesor en el chat y eso Google no lo ve.

El código para todo esto **ya está escrito** en la web. Lo único que falta es
pegar tres valores que te da Google. Nada más.

---

## Parte A — Google Analytics 4

Necesitas una cuenta de Google (te vale la misma del correo).

1. Entra a **[analytics.google.com](https://analytics.google.com)**.
2. Si es tu primera vez te ofrece **«Empezar a medir»**. Si ya tenías algo,
   ve abajo a la izquierda a la **rueda dentada (Administrar)** → **Crear** →
   **Propiedad**.
3. **Nombre de la cuenta**: el nombre de tu negocio. Acepta las casillas que
   pide y sigue.
4. **Nombre de la propiedad**: p. ej. `Santa Natura – Tienda`.
   **Zona horaria**: `Perú (GMT-5)`. **Moneda**: `Sol peruano (PEN)`.
   > La zona horaria decide dónde corta el día en los informes. Si la dejas en
   > otra, tus «lunes» no serán lunes. Y no se puede cambiar el pasado después.
5. Te pregunta sector y tamaño de empresa: responde lo que sea, no afecta a
   nada técnico.
6. **Elige la plataforma: «Web»**. Te pide:
   - **URL del sitio web**: `santanatura.inmuno.lat`
   - **Nombre del flujo**: `Tienda`
7. Al crearlo se abre la ficha del flujo de datos. Arriba a la derecha verás el

   **ID de medición**, con esta pinta: `G-ABCD123456`

   **Cópialo.** Es el primero de los tres valores.

Si te sale una ventana con el código para instalar, ciérrala: el código ya está
puesto en la web, tú solo necesitas ese `G-…`.

---

## Parte B — La conversión de Google Ads

Esta parte se hace **dentro de la cuenta de Google Ads**, y necesitas tener la
campaña ya creada (o al menos la cuenta).

1. Entra a **[ads.google.com](https://ads.google.com)**.
2. Menú de la izquierda: **Objetivos** → **Conversiones** → **Resumen**.
3. Botón **«+ Nueva acción de conversión»**.
4. Elige **«Sitio web»**.
5. Te pide el dominio: escribe `santanatura.inmuno.lat` y pulsa **Analizar**.
6. Google intentará detectar acciones solo. **Ignóralo**: busca abajo el enlace
   **«Añadir una acción de conversión manualmente»** y ábrelo.
7. Rellena:
   - **Categoría de objetivo**: `Contacto` (está dentro del grupo «Cliente
     potencial»). Es lo que es: alguien que escribe por WhatsApp.
   - **Nombre de la conversión**: `Clic WhatsApp`
   - **Valor**: `No usar un valor` es lo más honesto de entrada. Cuando sepas
     cuánto vale de media un chat que termina en venta, puedes volver y poner
     un valor fijo.
   - **Recuento**: **`Una`**. Con `Cada`, una persona que pulsa WhatsApp tres
     veces te cuenta tres conversiones y te infla los datos con los que Google
     aprende.
   - **Periodo de conversión**: deja el que viene.
   - **Incluir en «Conversiones»**: **sí**, activado. Es lo que hace que la
     campaña optimice hacia esto.
8. Pulsa **Crear y continuar**.
9. Ahora te ofrece cómo instalar la etiqueta. Elige **«Instalar la etiqueta tú
   mismo»** (o «Añadir la etiqueta de Google manualmente»).

   Verás dos bloques de código. **Del segundo**, el del evento, saca esta línea:

   ```
   gtag('event', 'conversion', {'send_to': 'AW-123456789/AbC-D_efGhIjKlMn'})
   ```

   De ahí salen los otros **dos valores**:

   - Lo de **antes** de la barra → `AW-123456789`
   - Lo de **después** de la barra → `AbC-D_efGhIjKlMn`

   **Cópialos.** Si cierras la ventana sin copiarlos, se vuelven a ver en
   Objetivos → Conversiones → tu acción → **Configuración de etiqueta**.

> **Si Google te dice que ya detectó una «etiqueta de Google» en el sitio**, es
> la de Analytics que acabas de instalar. Puedes decirle que la use: son la
> misma etiqueta. Los valores que necesitas son los mismos igual.

---

## Parte C — Pegar los tres valores

Ya tienes:

| Valor | Pinta |
|---|---|
| ID de medición de GA4 | `G-ABCD123456` |
| ID de conversión de Ads | `AW-123456789` |
| Etiqueta de conversión | `AbC-D_efGhIjKlMn` |

Van en **dos archivos**. Ábrelos con el Bloc de notas o con VS Code.

### 1. `inc/partes/cabeza.php`

Busca estas dos líneas, cerca del principio del archivo:

```php
const SN_GTAG_ADS = '';     // p. ej. 'AW-123456789'
const SN_GTAG_GA4 = '';     // p. ej. 'G-ABCD123456'
```

Y déjalas así, **con las comillas**:

```php
const SN_GTAG_ADS = 'AW-123456789';
const SN_GTAG_GA4 = 'G-ABCD123456';
```

### 2. `config.js`

Busca:

```js
const ADS_CONFIG = {
    conversionId: '',      // p. ej. 'AW-123456789'
    conversionLabel: ''    // p. ej. 'AbC-D_efGhIjKlMn'
};
```

Y déjalo así:

```js
const ADS_CONFIG = {
    conversionId: 'AW-123456789',
    conversionLabel: 'AbC-D_efGhIjKlMn'
};
```

> **Los dos `AW-…` tienen que ser idénticos.** `cabeza.php` **carga** la
> etiqueta y `config.js` **dispara** el evento al pulsar WhatsApp. Con uno solo
> de los dos no se mide nada, y no da ningún error: simplemente no cuenta.

**No hace falta** regenerar Tailwind (`npm run build`): esto no toca estilos.

### 3. Subir al hosting

`cabeza.php` y `config.js`, tal como explica `DESPLIEGUE.md`. Nada más.

---

## Parte D — Comprobar que funciona

**Primero, lo básico.** Abre la web ya subida, pulsa `Ctrl+U` (ver código
fuente) y busca `googletagmanager`. Si sale, la etiqueta está puesta. Si no
sale, no se subió el archivo o el valor quedó sin comillas.

**Analytics.** En GA4 → **Informes** → **Tiempo real**. Abre tu web en el móvil
y en menos de un minuto deberías verte ahí como usuario activo.

**La conversión.** Abre una landing y pulsa un botón de WhatsApp. Después:

- En GA4 → **Tiempo real**, el evento aparece casi al momento.
- En Google Ads → **Objetivos** → **Conversiones**, la columna **«Estado de la
  etiqueta»** de tu acción pasa de *«No se registran conversiones»* a
  *«Registrando conversiones»*. **Esto tarda**: entre unas horas y un día. No
  te asustes el primer rato.

Si quieres verlo al instante, instala la extensión de Chrome **Google Tag
Assistant**: te dice en el momento qué etiquetas dispara la página y con qué
valores.

> **Ojo al probar desde tu casa:** tus propias visitas y tus propios clics se
> cuentan. Para unas cuantas pruebas da igual, pero si vas a estar entrando
> todos los días, en GA4 → Administrar → **Flujos de datos** → tu flujo →
> **Configurar la etiqueta** → **Definir tráfico interno**, y añade tu IP.

---

## Qué se está midiendo exactamente

El código ya escrito en `store.js` (`conectarMedicion`) hace dos cosas cuando
alguien pulsa algo:

- **Clic a WhatsApp** → dispara la **conversión** de Google Ads y lo empuja al
  dataLayer.
- **«Agregar al pedido»** → solo al dataLayer, como evento secundario. No es
  conversión a propósito: agregar al carrito no es un contacto, y si lo contaras
  como tal la campaña optimizaría hacia gente que toquetea y no escribe.

Y `dispararConversionAds()` se autodesactiva si `conversionId` está vacío o si
`gtag` no existe: por eso hoy no pasa nada, y por eso no puede romper la web si
algún día borras los valores.

---

## Dos avisos

**Cookies.** En cuanto pongas los IDs, la web empieza a poner cookies de
analítica. En Perú hoy no te obliga a poner un aviso de cookies, pero si algún
día te llega tráfico de Europa sí haría falta. Es la razón por la que la web
viene con las dos constantes vacías: **con ellas en blanco no se carga nada de
Google, ni una petición ni una cookie.**

**Las pantallas de Google cambian.** Los nombres de los menús que salen aquí son
los de septiembre de 2026. Si un botón no está donde dice, busca por el nombre
del paso («acción de conversión», «flujo de datos», «configuración de
etiqueta»): el flujo es el mismo aunque lo muevan de sitio.
