# Panel de gestión — estado y pendientes

Actualizado: 28 de agosto de 2026.
Rama: `feat/landing-ads`.

---

## Cómo se entra

`https://santanatura.inmuno.lat/gestion-sn/` (en local, `http://localhost/SantaNaturaTienda/gestion-sn/`).

No hay enlace desde ninguna página pública: la URL no se enseña, está en
`Disallow` del `robots.txt` y todas las pantallas llevan `<meta noindex>`.

La primera vez pide crear la contraseña y la guarda cifrada en
`inc/admin-config.php`, que **no va al repositorio**. Si se pierde, se borra ese
archivo y el panel vuelve a pedirla.

---

## Lo que ya funciona

**Catálogo** (`gestion-sn/index.php`)
- Listado con foto, precios, descuento y puntos.
- Buscador y filtros: activos, desactivados, packs, sin foto.
- Cuatro contadores arriba (activos, desactivados, packs, activos sin foto).
- Dos acciones por fila: **Editar** y **Activar/Desactivar**.
  Desactivar esconde el producto de toda la web sin borrarlo.

**Ficha de producto o pack** (`gestion-sn/producto.php`)
- Alta y edición: nombre, precios, línea, categorías, foto y descripción.
- **Subir fotos desde el panel**: JPG/PNG/WEBP hasta 5 MB, van a `img/` con el
  nombre del producto. Ya no hace falta FTP para publicar algo nuevo.
  El formato se comprueba leyendo la imagen, no por la extensión.
- **Qué lleva dentro el pack**: lista de productos del propio catálogo con
  cantidad y marca de «de regalo». Se guarda en el campo `contiene`, y de ahí se
  escribe sola la frase «Contiene: … De regalo: …» de la ficha, así que ya no
  puede quedarse desfasada. Muestra la suma de los productos sueltos para saber
  cuánto ahorra el pack.
- Los puntos, el globo de descuento y el precio por cantidad se calculan solos.

**Aviso emergente** (`gestion-sn/popup.php`)
- Cartel de promo flash configurable: título, texto, imagen, botón, rango de
  fechas, retraso y cada cuánto se le repite a la misma persona.
- Interruptor de encendido y vista previa en vivo.
- Sale en la portada. En `/packs` y `/packs/colageno` viene **apagado** a
  propósito (tapar la oferta a quien llega de un anuncio baja la conversión y
  Google penaliza los intersticiales); hay una casilla para encenderlo.
- Se guarda en `inc/popup.json`, fuera del alcance web y fuera del repositorio.

**Seguridad y respaldo** (ya estaba, sigue igual)
- Contraseña con hash, bloqueo tras 5 intentos, sesión de 8 h, CSRF en todo
  formulario que escribe.
- Antes de cada guardado se archiva una copia del catálogo en `inc/copias/`
  (se conservan las 30 últimas). La escritura de `products.js` es atómica y se
  valida antes de publicarse: un guardado que saliera roto no tumba la web.

**Comprobaciones**
```
C:/xampp/php/php.exe scripts/verificar-panel.php    # panel y catálogo
node scripts/verificar-paridad.js                   # PHP vs JS de las tarjetas
```
Las dos pasan al 100 % en este commit.

---

## Sobre la base de datos MySQL: no hace falta, y hoy sería más lento

Medido en esta máquina, con los 104 productos:

```
sn_productos(): 0.698 ms por llamada   (products.js son 53 KB)
sn_catalogo():  0.785 ms
```

Abrir la conexión a MySQL sola cuesta más que eso. Además `products.js` tiene
que seguir existiendo tal cual porque **el navegador lo carga** para el catálogo,
el buscador y el carrito: con una base de datos habría que generar igualmente
ese archivo, así que serían dos fuentes de verdad en vez de una, más un servicio
más que puede caerse.

Cuándo sí tocaría replantearlo (y entonces sí merece la pena):

- Pedidos guardados en la web, con historial y estados.
- Control de stock real.
- Varias personas editando a la vez (el archivo se guarda entero: el último en
  guardar pisa al anterior).
- Más de unos pocos miles de productos.

Mientras el panel sea una persona editando un catálogo de ~100 fichas, el
archivo gana.

---

## Lo que queda pendiente

### 1. Repasar el diseño en pantalla real
Rehíce el panel entero (barra lateral, tarjetas de datos, tabla, interruptores,
tema claro y oscuro según el sistema, vista de móvil en fichas apiladas). Lo
probé por HTTP, pero **no lo he visto en un navegador**. Falta abrirlo y ajustar
lo que chirríe. `gestion-sn/_layout.php` tiene todo el CSS en un sitio.

### 2. Armar el contenido de los packs que ya existen
El editor está listo, pero los 30 packs vigentes siguen con su frase escrita a
mano; ninguno tiene todavía el campo `contiene`. Hay dos caminos:

- **A mano, según haga falta**: cada vez que se toque un pack, se le arma la
  lista. Sin riesgo, pero lento.
- **Con un script de una vez**: leer la frase «Contiene: 2 Tocosh, …» e intentar
  casar cada trozo con un producto del catálogo. El problema es que las frases
  usan nombres cortos («Tocosh», «EnfoK+») y el catálogo los tiene largos
  («Allín surka x 200 g»), así que el casado sería aproximado y **habría que
  revisar pack por pack** antes de guardar. Si se hace, que el script escriba un
  informe y no toque `products.js` hasta que se apruebe.

### 3. Decidir si la ficha del producto enseña el contenido
Ahora mismo `contiene` solo se usa para escribir la frase. Se podría pintar en
la ficha como una lista con enlaces a cada producto. Es trabajo de front
(`inc/render.php` y su gemelo `render-productos.js`, que hay que tocar a la vez;
`scripts/verificar-paridad.js` avisa si se separan).

### 4. Probar el aviso emergente con una promo de verdad
Funciona y se muestra, pero solo lo he probado con texto de prueba. Falta verlo
con una promo real, con imagen, y comprobar en móvil que no tapa el botón de
WhatsApp.

### 5. Subir `inc/admin-config.php` al servidor
No va en el repositorio a propósito. En producción hay que entrar una vez al
panel y crear la contraseña allí, o el panel pedirá crearla al primero que
llegue a la URL.

---

## Cosas que arreglé de paso

- El menú del panel enlazaba a `../index.html`, que ya no existe (es `.php`).
- Guardar desde XAMPP convertía los finales de línea de `products.js` a LF y
  dejaba el archivo mezclado, con las 104 líneas marcadas como cambiadas en cada
  commit. Ahora se respeta el final de línea que tenga el archivo.
- Dos guardados dentro del mismo segundo se pisaban la copia de seguridad.
- El `.gitignore` estaba en UTF-16 y git no aplicaba sus reglas:
  `inc/admin-config.php` y `inc/copias/` aparecían como archivos sin seguir.
  Reescrito en UTF-8.
