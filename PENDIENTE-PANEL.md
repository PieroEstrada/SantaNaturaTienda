# Panel de gestión — estado y pendientes

Actualizado: 29 de agosto de 2026.
Rama: `feat/landing-ads`.

---

## Cómo se entra

`https://santanatura.inmuno.lat/gestion-sn/` (en local, `http://localhost/SantaNaturaTienda/gestion-sn/`).

No hay enlace desde ninguna página pública: la URL no se enseña, está en
`Disallow` del `robots.txt` y todas las pantallas llevan `<meta noindex>`.

La primera vez pide crear la contraseña y la guarda cifrada en
`inc/admin-config.php`, que **no va al repositorio**.

**Si se olvida la contraseña**: se borra `inc/admin-config.php` y al volver a
entrar el panel enseña otra vez la pantalla de crearla. No se pierde nada: el
catálogo vive en `products.js` y el aviso emergente en `inc/popup.json`, ninguno
de los dos depende de la contraseña. En local es
`C:\xampp\htdocs\SantaNaturaTienda\inc\admin-config.php`; en el servidor, por
FTP o por el gestor de archivos del hosting. No se puede recuperar la que había:
solo se guarda el hash, que no se puede deshacer.

---

## Lo que ya funciona

**Catálogo** (`gestion-sn/index.php`)
- Listado con foto, precios, descuento y puntos.
- Buscador y filtros: activos, desactivados, packs, sin foto.
- Cuatro contadores arriba (activos, desactivados, packs, activos sin foto).
- Dos acciones por fila: **Editar** y **Activar/Desactivar**.
  Desactivar esconde el producto de toda la web sin borrarlo.
- **El color del botón dice lo que va a pasar**, sin tener que leerlo: verde
  para publicar o activar, rojo para retirar o quitar, gris para lo que no
  cambia nada (editar, cancelar, volver). En una tabla de 104 filas el rojo va
  de contorno y no macizo: el aviso se ve igual y no queda un muro de color.

**Ficha de producto, pack o promo** (`gestion-sn/producto.php`)
- Lo primero que se elige es **qué se está dando de alta**, y de ahí sale casi
  todo lo demás:
  - **Producto individual** — se vende solo. No lleva nada dentro y no puede
    entrar en la categoría «Packs».
  - **Pack** — combo fijo. Se marca solo en «Packs» y sale en esa sección.
  - **Promo** — combo de temporada. **No** entra en «Packs»: dónde aparece lo
    deciden sus categorías (Top Ventas, Favoritos de la semana, «Para …»).

  No hace falta un campo nuevo en `products.js`: el tipo se deduce al abrir la
  ficha (está en «Packs» → pack; lleva productos dentro y no está en «Packs» →
  promo; no lleva nada → individual). La casilla «Packs» se ve pero está
  bloqueada, para que se entienda que la pone el tipo y no la mano.
- **Elegir los productos del combo con la foto delante.** Una parrilla con la
  imagen, el nombre y el precio de cada producto, con buscador y una casilla
  para ver también los desactivados. Se toca y se añade; si ya está, le sube la
  cantidad. Cada línea elegida lleva su miniatura, su cantidad y el interruptor
  **«De regalo»** (la fila se pone ámbar y ese producto no suma al precio).
- **Precios que no pueden contradecirse.** La suma de lo que lleva dentro, a
  precio normal, rellena sola el «Precio normal»; a partir de ahí el **precio de
  venta y el porcentaje de descuento se calculan el uno al otro** en los dos
  sentidos. El badge de la web sigue saliendo SIEMPRE de la resta de los dos
  precios, nunca de un número escrito a mano.
- **Los puntos se escriben a mano**, que es como se manejan de verdad en los
  combos. Al lado hay un botón con el resultado de la regla de siempre (pvp/6,
  o sobre el precio de lista en un producto suelto) por si se quiere usar. En
  blanco, se aplica esa regla.
- La frase de la ficha («Contiene: … De regalo: …») se escribe sola con lo que
  haya en la lista, así que no puede quedarse desfasada.
- **Armar la lista desde la frase.** Los packs que ya estaban guardan su
  contenido escrito a mano dentro de la descripción, con nombres cortos que no
  son los del catálogo («Tocosh» contra «Caja de sachet 15u tocosh con uña de
  gato y muña x 5 g c/u»). Al abrir uno de esos sale un botón que lee la frase
  y arma la lista. **Propone, no guarda**: lo que no ve claro lo deja con una
  marca ⚑ *revisar* y lo explica arriba en cristiano («el producto no dice
  “verde”», «la frase pide 75 g y el producto es de 10 g», «hay otro casi
  igual»); lo que no encuentra lo dice por su nombre para añadirlo a mano. La
  marca se quita tocándola, que es la forma de ir tachando lo revisado.
  Probado contra los 47 packs: **135 líneas emparejadas, 19 marcadas para
  revisar y 4 que no existen** como producto suelto.
- En una ficha que **ya existía**, el precio normal no se rellena solo aunque
  se arme la lista: hacerlo le pondría de golpe un globo de descuento que hoy
  no tiene, y eso se vería en la web. Para eso está el botón «Usar la suma».
- **Subir fotos desde el panel**: JPG/PNG/WEBP hasta 5 MB, van a `img/` con el
  nombre del producto. Ya no hace falta FTP para publicar algo nuevo.
  El formato se comprueba leyendo la imagen, no por la extensión.

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

### 1. ~~Repasar el diseño en pantalla real~~ — hecho el 29 de agosto
Visto en el navegador a 1366 px y a 390 px, en tema claro y en oscuro. Lo que
chirriaba y ya está arreglado (todo en `gestion-sn/_layout.php` salvo lo que se
indique):

- **Los dos botones de cada fila se partían en dos líneas.** Con 104 filas eso
  era el listado entero al doble de alto. La columna se queda ahora con lo justo
  y no parte (`td.acciones`, más `<td class="acciones">` en `index.php`).
- **«Editar» era un botón verde macizo repetido 104 veces** y le comía la
  atención al único botón principal de la pantalla. Pasa a botón gris, que es
  lo que es: no cambia nada por sí solo.
- **En tema oscuro los botones de color eran ilegibles**: letra blanca sobre el
  verde claro del modo noche, contraste 2,4:1 (el mínimo accesible es 4,5:1).
  Ahora la letra la decide el tema (`--btn-texto`), oscura sobre relleno claro.
- **Editando una ficha que ya existía, el menú marcaba «Nuevo producto».** Marca
  «Catálogo», que es de donde se viene y adonde se vuelve.
- **En el móvil no cabían las tres secciones**: el nombre de la marca ocupaba
  media tira y «Aviso emergente» quedaba fuera de la pantalla. En móvil el
  nombre se va (queda el icono), el menú usa nombres cortos («Nuevo», «Aviso») y
  «Ver la web» y «Salir» se quedan en icono. Ahora entran las tres a la vez.
- **El desplegable «Foto» cortaba su primera opción** («— sin foto (sale el
  icono de la ma…»). La opción es ahora «— sin foto —» y la explicación baja a
  la pista de debajo del título (`producto.php`).

Y en la segunda vuelta, con la ficha de producto ya rehecha:

- **Los desplegables eran el único campo que desentonaba**: cada navegador
  dibuja su propia flecha y no se puede colorear. Ahora la flecha es nuestra y
  cambia con el tema (`--flecha`).
- **Los campos de dinero llevan el «S/» dentro** y el de descuento su «%», en
  vez de repetirlo en la etiqueta.
- **Los recuadros de una misma fila no empezaban a la misma altura**: bastaba
  con que una pista ocupara dos líneas y la de al lado una. Ahora las pistas
  crecen hacia arriba y los campos quedan alineados abajo.
- El botón de «Seleccionar archivo», los campos de solo lectura y los
  deshabilitados tienen por fin un aspecto propio.
- **Los botones se leen por el color**: «Desactivar» y «Quitar» en rojo,
  «Activar» y «Guardar» en verde, y en gris lo que no cambia nada.

Las dos comprobaciones siguen pasando al 100 %.

### 2. Armar el contenido de los packs que ya existen
El editor está listo —y desde el 29 de agosto se eligen los productos con la
foto delante, que hace el trabajo mucho más rápido—, pero los packs siguen con
su frase escrita a mano: de los 47 packs del archivo (17 activos y 30 retirados,
los de la lista anterior) **ninguno tiene todavía el campo `contiene`**.

Eso no bloquea nada: un combo que ya existía se puede seguir guardando sin
lista, y conserva su frase. El contenido solo es obligatorio al dar de alta uno
nuevo.

**Cómo ponerse al día, pack por pack**: se abre el pack y sale el botón «Armar
la lista desde la frase» (ver arriba). Lee la descripción, deja la lista puesta
y marca lo que no tiene claro. Se revisan las marcas ⚑, se corrige lo que haga
falta y se guarda. De los 47 packs, 33 tienen una frase con lista que se puede
leer; los otros 14 solo tienen texto comercial («Cuida tu estructura ósea…») y
esos hay que armarlos a mano con la parrilla de fotos.

Se descartó el script de una pasada que lo hiciera todo de golpe: el casado es
aproximado por narices —las frases usan nombres cortos y el catálogo los tiene
largos— y hay 4 productos citados que no existen sueltos en el catálogo (un
«Gel Reductor», un «Vitamagne (citrato de magnesio)»…). Guardar 33 packs sin
mirarlos habría metido errores en la web sin que nadie se enterase.

**Ojo con la frase que sale**: al armar la lista, la descripción se reescribe
con los nombres LARGOS del catálogo, así que un pack que ponía «Contiene: 2
Concentrados de Uña de Gato, 2 Carti Mix…» pasa a poner «Contiene: 2
Concentrado de uña de gato x 500 ml, 2 Cartimix x 100 g…». Es más exacto, pero
más largo y con el número en plural y el nombre en singular. Si se prefiere más
corto, hay que decidirlo: se toca `sn_texto_contenido()` en `inc/catalogo.php`,
y afecta a lo que se lee en la tarjeta del producto en la web.

### 3. Decidir si la ficha del producto enseña el contenido
Ahora mismo `contiene` solo se usa para escribir la frase. Se podría pintar en
la ficha como una lista con enlaces a cada producto. Es trabajo de front
(`inc/render.php` y su gemelo `render-productos.js`, que hay que tocar a la vez;
`scripts/verificar-paridad.js` avisa si se separan).

### 4. Probar el aviso emergente con una promo de verdad
Funciona y se muestra, pero solo lo he probado con texto de prueba. Falta verlo
con una promo real, con imagen, y comprobar en móvil que no tapa el botón de
WhatsApp.

### 5. Crear la contraseña en el servidor
`inc/admin-config.php` no va en el repositorio a propósito. En producción hay que
entrar una vez a la URL del panel y crear la contraseña allí, cuanto antes: hasta
que exista ese archivo, el panel se la ofrece a crear **al primero que llegue a la
URL**. Ver arriba, en «Cómo se entra», qué hacer si se olvida.

### 6. Faltan 23 fotos, y 17 son de packs que ya están publicados
El contador de arriba del panel las marca en ámbar. Son las 6 que ya se sabían
(los 5 aceites esenciales y la miel con leche de oje, que no se venden online) más
los **17 packs vigentes**, que salen en la web con el icono genérico de la marca.
Con la subida de fotos del panel ya se pueden poner sin tocar FTP: filtro
«Sin foto» → Editar → «…o sube una foto nueva».

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
