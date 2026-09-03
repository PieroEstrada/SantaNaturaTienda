# Subir la web al hosting

Revisión hecha el 3 de septiembre de 2026. Este archivo no se sirve por web
(el `.htaccess` de la raíz bloquea los `.md`).

---

## 1. Qué pide el servidor

| | |
|---|---|
| PHP | **8.0 o superior** (el código usa `match` y `str_contains`). Aquí se probó con 8.2 |
| Servidor | Apache o LiteSpeed, con `AllowOverride All` para que lean los `.htaccess` |
| Base de datos | ninguna |
| Extensiones | `gd` o `exif` no hacen falta; sí `iconv` (nombres de archivo) y `getimagesize`, que vienen de serie |

**Si el hosting fuera Nginx**, los tres `.htaccess` no se leen y hay que pedirle
al proveedor que bloquee `/inc/`, `/scripts/`, los `.md`, los `.xlsx` y la
ejecución de PHP dentro de `/img/`. Sin eso, el panel queda expuesto.

---

## 2. Qué se sube y qué no

Se sube todo **menos** esto:

```
.git/                     historial del repositorio
.claude/                  configuración local del editor
build/                    el generador de tailwind.css (ver punto 5)
inc/copias/               copias del catálogo de la máquina local
products.js.tmp           restos de una escritura a medias
Santa_Natura_Lista_de_Precios.xlsx   archivo de origen, no lo usa la web
```

`inc/copias/` la crea el panel solo la primera vez que guardes algo.

**Sí hay que subir `tailwind.css`** (está en la raíz). Es la hoja de estilos ya
generada: sin ella la web se ve como texto plano.

---

## 3. Orden de los pasos (importa)

**1. Sube los archivos.**

**2. Crea la contraseña del panel ANTES de dar el dominio a nadie.**

Es el punto delicado del despliegue. `inc/admin-config.php` guarda el hash de la
contraseña y **no está en el repositorio**, así que en el servidor no existe
hasta que lo pongas. Y mientras no exista, `/gestion-sn/` le enseña la pantalla
de «crear contraseña» a **cualquiera** que escriba la dirección: el primero que
la rellene se queda con los precios, las fotos y el aviso emergente de la web.

Dos formas de cerrarlo:

- **Copiar el archivo por FTP** desde tu equipo:
  `C:\xampp\htdocs\SantaNaturaTienda\inc\admin-config.php`
  → misma ruta en el servidor. Así el panel nunca llega a estar abierto y sigues
  entrando con la contraseña que ya usas en local.
- **O entrar tú primero** a `https://santanatura.inmuno.lat/gestion-sn/` nada más
  terminar la subida y crearla ahí, antes de encender los anuncios.

Lo mismo vale el día que olvides la contraseña: entre borrar
`inc/admin-config.php` y crear la nueva, el panel está abierto. Hazlo seguido.

**3. Permisos de escritura.** El panel necesita escribir en:

```
products.js        el catálogo
img/               las fotos que subas
inc/               popup.json y la carpeta copias/
```

En un hosting compartido normal (todo del mismo usuario) no hay que tocar nada.
Si el panel se queja de permisos, `755` en las carpetas y `644` en los archivos.

**4. Activa el HTTPS y descomenta la redirección.** En el `.htaccess` de la raíz,
apartado 5. Hazlo **después** de comprobar que el candado ya funciona: si lo
descomentas antes, la web deja de cargar.

Importa más de lo que parece: la cookie de sesión del panel solo se marca
`secure` cuando la visita llega por https (`inc/auth.php`). Por http, la
contraseña y la sesión viajan en claro.

**5. Comprueba que está todo cerrado.** Abre estas direcciones; las cuatro
primeras tienen que cargar y las seis siguientes dar error 403 o 404:

```
/                                     200
/packs/                               200
/afiliacion.php                       200
/gestion-sn/                          200 (pide contraseña)

/tailwind.css                         200 (los estilos: si falla, web sin diseño)

/inc/admin-config.php                 403
/inc/popup.json                       403
/scripts/verificar-panel.php          403
/build/tailwind.config.js             403
/PENDIENTE-PANEL.md                   403
/DESPLIEGUE.md                        403
/.gitignore                           403
/img/                                 403 (sin listado)
```

Si alguna de las de abajo devuelve 200, el hosting no está leyendo los
`.htaccess` y hay que resolverlo antes de encender los anuncios.

---

## 5. Los estilos: cuándo hay que regenerarlos

Los estilos salen de Tailwind, que convierte las clases del HTML (`bg-primary`,
`rounded-full`, `md:grid-cols-3`…) en CSS de verdad. Ese cálculo se hace **en tu
ordenador**, una vez, y produce `tailwind.css`.

```
cd build
npm install        (solo la primera vez, o al cambiar de ordenador)
npm run build      genera ../tailwind.css
npm run dev        se queda vigilando y regenera cada vez que guardas
```

**Cuándo hay que regenerar:** solo si tocas clases de diseño en los `.php` o en
`store.js` / `render-productos.js`. **No** hace falta al cambiar precios, fotos,
productos o el aviso emergente desde el panel: eso no toca estilos.

**Si te olvidas de regenerar**, la clase nueva sale sin estilo (ese trozo se ve
desmaquetado). No rompe la web, pero se nota.

**La trampa a evitar:** Tailwind solo genera las clases que encuentra escritas
enteras en el código. Si algún día armas un nombre a pedazos —`'text-' + color`—
no puede verlo y ese estilo no existirá. Escribe la clase completa en las dos
ramas del condicional, como está hecho hoy.

Antes esto se cargaba desde `cdn.tailwindcss.com`: 409 KB (124 KB comprimidos)
de JavaScript que cada visitante bajaba y ejecutaba para que le calcularan el CSS
en su propio teléfono, en cada visita. Ahora son 40 KB de CSS (7 KB comprimidos)
que además el navegador cachea. Es un 94 % menos de descarga y, sobre todo, deja
de ser código que hay que ejecutar antes de poder pintar la página.

---

## 6. Lo que queda pendiente (no bloquea la subida)

Todo lo que falta para la campaña de Google Ads está en **`PENDIENTE-ADS.md`**,
con el detalle de qué es cada cosa, dónde está en el código y por qué importa.
El resumen:

- **Bloqueante:** no hay medición de conversiones ni Analytics. La campaña
  funcionaría, pero sin registrar una sola conversión.
- **Riesgo de desaprobación:** cuatro descripciones de producto con
  afirmaciones de salud, y cifras de ingresos en la página de afiliación.
- **Falta:** RUC, dirección y correo; páginas de privacidad, términos y
  devoluciones; favicon; etiquetas `og:`.
- **Restan conversión:** 23 productos activos sin foto, la foto del hero
  alojada en `googleusercontent.com`, y los enlaces de Facebook e Instagram
  del pie apuntando a `#`.
