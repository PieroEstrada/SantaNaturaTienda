<?php
/* ============================================================================
   Panel de gestión — armazón común (barra lateral, cabecera y estilos)
   ----------------------------------------------------------------------------
   No se enlaza desde ninguna página pública y lleva <meta noindex>.

   El panel se usa a diario y muchas veces desde el móvil, así que la pantalla
   está pensada como una herramienta de trabajo: navegación siempre a la vista,
   una acción principal clara por página y tablas que se leen de un vistazo.
   Todo el CSS vive aquí, en una sola hoja: son cinco pantallas, y separarlo en
   archivos solo añadiría peticiones y una copia más que mantener.
   ========================================================================== */

declare(strict_types=1);

/** Entradas de la barra lateral. La activa se marca sola por el archivo.
    `corto` es el nombre que se usa en el móvil, donde la barra es una tira
    horizontal y con los nombres largos la última entrada se sale de la pantalla. */
function sn_menu(): array
{
    return [
        ['archivo' => 'index.php',    'texto' => 'Catálogo',        'corto' => 'Catálogo', 'icono' => '▤'],
        ['archivo' => 'producto.php', 'texto' => 'Nuevo producto',  'corto' => 'Nuevo',    'icono' => '＋'],
        ['archivo' => 'popup.php',    'texto' => 'Aviso emergente', 'corto' => 'Aviso',    'icono' => '◧'],
    ];
}

/** Recuerda si la página se pintó con barra lateral, para cerrarla igual. */
function sn_con_sesion(?bool $valor = null): bool
{
    static $estado = true;
    if ($valor !== null) {
        $estado = $valor;
    }
    return $estado;
}

function sn_cabecera(string $titulo, bool $conSesion = true, string $sub = ''): void
{
    sn_con_sesion($conSesion);
    $aqui = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
    // Editar una ficha que ya existe no es «Nuevo producto»: se llegó desde el
    // catálogo y ahí es donde hay que volver, así que se marca esa entrada.
    if ($aqui === 'producto.php' && ($_GET['id'] ?? '') !== '') {
        $aqui = 'index.php';
    }
    ?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<!-- Fuera de los buscadores: esta URL no debe aparecer nunca en Google, y
     menos en un dominio con campañas de Ads activas. -->
<meta name="robots" content="noindex, nofollow, noarchive">
<title><?= htmlspecialchars($titulo, ENT_QUOTES) ?> · Gestión Santa Natura</title>
<style>
/* --- Tokens ---------------------------------------------------------------
   Un solo juego de variables para los dos temas. El panel sigue el tema del
   sistema: quien trabaja de noche no se come una pantalla en blanco. */
:root {
  --verde:#1b5e3f; --verde-vivo:#2a7d55; --verde-tenue:#e9f3ed; --verde-borde:#bcd9c9;
  --lateral:#12331f; --lateral-alto:#1d4a2f; --lateral-texto:#b9d4c3;
  --fondo:#f1f4f2; --tarjeta:#ffffff; --texto:#16211b; --suave:#5f6d65;
  --borde:#dfe6e1; --borde-suave:#eef2ef;
  --rojo:#b3261e; --rojo-tenue:#fdecea; --ambar:#8a6100; --ambar-tenue:#fdf5e3;
  --sombra:0 1px 2px rgba(16,32,24,.06), 0 8px 24px -12px rgba(16,32,24,.18);
  --radio:14px;
  /* Texto de los botones de color. En claro el relleno es oscuro y la letra va
     blanca; en oscuro el relleno se aclara y la letra tiene que oscurecerse, o
     el botón queda ilegible. */
  --btn-texto:#ffffff;
  --anillo:rgba(42,125,85,.18);
  /* Flecha de los desplegables. Va como variable porque el color tiene que
     cambiar con el tema y dentro de un data: URI no entran las variables. */
  --flecha:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='8'><path d='M1 1.6 6 6.4 11 1.6' fill='none' stroke='%235f6d65' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'/></svg>");
}
@media (prefers-color-scheme: dark) {
  :root {
    --verde:#4ea87a; --verde-vivo:#5cbb8a; --verde-tenue:#17311f; --verde-borde:#2c5a3f;
    --lateral:#0e2417; --lateral-alto:#1a4029; --lateral-texto:#9dbcab;
    --fondo:#111714; --tarjeta:#19211c; --texto:#e6ece8; --suave:#94a49a;
    --borde:#293530; --borde-suave:#222b26;
    --rojo:#f2857c; --rojo-tenue:#361c1a; --ambar:#e0b352; --ambar-tenue:#2f2717;
    --sombra:0 1px 2px rgba(0,0,0,.4), 0 10px 30px -14px rgba(0,0,0,.7);
    --btn-texto:#07160e;
    --anillo:rgba(92,187,138,.26);
    --flecha:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='8'><path d='M1 1.6 6 6.4 11 1.6' fill='none' stroke='%2394a49a' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'/></svg>");
  }
}

* { box-sizing:border-box; }
/* `hidden` tiene que ganar siempre: varias reglas de aquí abajo fijan un
   display (los botones, las etiquetas) y sin esto seguirían viéndose. */
[hidden] { display:none !important; }
html,body { height:100%; }
body {
  margin:0; background:var(--fondo); color:var(--texto);
  font:15px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
  -webkit-font-smoothing:antialiased;
}
a { color:var(--verde); }

/* --- Armazón -------------------------------------------------------------- */
.app { display:flex; min-height:100%; }

.lateral {
  width:246px; flex:0 0 246px; background:var(--lateral); color:#fff;
  display:flex; flex-direction:column; padding:18px 14px;
  position:sticky; top:0; height:100vh;
}
.marca { display:flex; align-items:center; gap:10px; padding:6px 10px 20px; }
.marca .hoja {
  width:34px; height:34px; flex:0 0 34px; border-radius:10px; background:var(--verde-vivo);
  display:grid; place-items:center; font-size:17px;
}
.marca b { display:block; font-size:15px; letter-spacing:-.01em; line-height:1.2; }
.marca span { display:block; font-size:11px; color:var(--lateral-texto); text-transform:uppercase; letter-spacing:.08em; }

.lateral nav { display:flex; flex-direction:column; gap:3px; }
.lateral nav a {
  display:flex; align-items:center; gap:11px; padding:10px 12px; border-radius:9px;
  color:var(--lateral-texto); text-decoration:none; font-size:14px; font-weight:500;
  transition:background .12s, color .12s;
}
.lateral nav a:hover { background:var(--lateral-alto); color:#fff; }
.lateral nav a.activa { background:var(--lateral-alto); color:#fff; box-shadow:inset 3px 0 0 var(--verde-vivo); }
.lateral nav a i { font-style:normal; width:18px; text-align:center; opacity:.85; font-size:15px; }
.lateral nav a .corto { display:none; }
.lateral .abajo { margin-top:auto; padding-top:14px; border-top:1px solid rgba(255,255,255,.09); }

.cuerpo { flex:1 1 auto; min-width:0; display:flex; flex-direction:column; }

.topbar {
  display:flex; align-items:center; gap:14px; flex-wrap:wrap;
  padding:16px 26px; background:var(--tarjeta); border-bottom:1px solid var(--borde);
  position:sticky; top:0; z-index:20;
}
.topbar h1 { font-size:19px; margin:0; letter-spacing:-.015em; }
.topbar .sub { margin:2px 0 0; font-size:13px; color:var(--suave); }
.topbar .acciones { margin-left:auto; display:flex; gap:8px; align-items:center; }

main { padding:24px 26px 60px; max-width:1240px; width:100%; }
h2 { font-size:15px; margin:30px 0 12px; letter-spacing:-.01em; }
.sub { color:var(--suave); font-size:14px; margin:0 0 20px; }
.mini { font-size:12.5px; color:var(--suave); }

/* --- Tarjetas y datos ----------------------------------------------------- */
.caja {
  background:var(--tarjeta); border:1px solid var(--borde);
  border-radius:var(--radio); padding:22px; box-shadow:var(--sombra);
}
.datos { display:grid; grid-template-columns:repeat(auto-fit,minmax(158px,1fr)); gap:12px; margin-bottom:22px; }
.dato {
  background:var(--tarjeta); border:1px solid var(--borde); border-radius:12px;
  padding:14px 16px; box-shadow:var(--sombra);
}
.dato b { display:block; font-size:25px; font-weight:700; letter-spacing:-.03em; line-height:1.1; }
.dato span { display:block; font-size:12px; color:var(--suave); margin-top:3px; }
.dato.ojo b { color:var(--ambar); }

/* --- Tabla ---------------------------------------------------------------- */
.tabla {
  background:var(--tarjeta); border:1px solid var(--borde); border-radius:var(--radio);
  box-shadow:var(--sombra); overflow:hidden;
}
table { width:100%; border-collapse:collapse; }
th,td { padding:11px 14px; text-align:left; font-size:14px; vertical-align:middle; }
th {
  background:var(--borde-suave); font-size:11px; text-transform:uppercase;
  letter-spacing:.07em; color:var(--suave); font-weight:700; white-space:nowrap;
}
tbody tr { border-top:1px solid var(--borde-suave); }
tbody tr:hover { background:var(--verde-tenue); }
tbody tr.off { opacity:.55; }
td.num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
td .nombre { font-weight:600; color:var(--texto); text-decoration:none; }
td .nombre:hover { color:var(--verde); text-decoration:underline; }
/* La columna de botones se queda con lo justo y no parte los botones en dos
   líneas: con 104 filas, cada salto de línea es una fila el doble de alta. */
td.acciones { width:1%; white-space:nowrap; }
td.acciones .par { display:flex; gap:6px; align-items:center; }

/* --- Botones -------------------------------------------------------------- */
.btn {
  display:inline-flex; align-items:center; justify-content:center; gap:6px;
  background:var(--verde); color:var(--btn-texto); border:1px solid transparent; cursor:pointer;
  padding:9px 16px; border-radius:9px; font:inherit; font-size:14px; font-weight:600;
  text-decoration:none; white-space:nowrap; transition:filter .12s, background .12s;
}
.btn:hover { filter:brightness(1.09); }
.btn:active { filter:brightness(.95); }
/* El color del botón dice lo que va a pasar, sin tener que leerlo:
     verde  = se publica, se guarda, se activa
     rojo   = se retira, se quita, se borra
     gris   = no cambia nada (volver, cancelar, editar) */
.btn.gris { background:transparent; color:var(--texto); border-color:var(--borde); }
.btn.gris:hover { background:var(--borde-suave); filter:none; }
.btn.rojo { background:var(--rojo); }
/* Rojo de contorno: para el mismo aviso cuando la acción se repite en cada
   fila de una tabla larga y el relleno macizo sería un muro de color. */
.btn.rojo-suave { background:transparent; color:var(--rojo); border-color:var(--rojo); }
.btn.rojo-suave:hover { background:var(--rojo-tenue); filter:none; }
.btn.mini { padding:6px 11px; font-size:13px; border-radius:8px; }
.btn:focus-visible { outline:2px solid var(--verde-vivo); outline-offset:2px; }

.pill {
  display:inline-block; font-size:11px; font-weight:700; padding:3px 9px; border-radius:999px;
  background:var(--verde-tenue); color:var(--verde); border:1px solid var(--verde-borde);
  letter-spacing:.02em;
}
.pill.off { background:var(--borde-suave); color:var(--suave); border-color:var(--borde); }

/* --- Formularios ---------------------------------------------------------- */
input,select,textarea {
  font:inherit; padding:10px 12px; border:1px solid var(--borde); border-radius:10px;
  width:100%; background:var(--tarjeta); color:var(--texto);
  transition:border-color .12s, box-shadow .12s;
}
input:hover:not(:disabled):not([readonly]),
select:hover:not(:disabled),
textarea:hover:not([readonly]) { border-color:var(--verde-borde); }
input:focus,select:focus,textarea:focus {
  outline:none; border-color:var(--verde-vivo); box-shadow:0 0 0 3px var(--anillo);
}
input::placeholder,textarea::placeholder { color:var(--suave); opacity:.7; }
/* Un campo que se calcula solo tiene que verse distinto de uno que se escribe,
   o se pierde el tiempo intentando teclear en él. */
input[readonly],textarea[readonly] { background:var(--borde-suave); color:var(--suave); cursor:default; }
input:disabled,select:disabled { opacity:.5; cursor:not-allowed; }

/* La flecha propia del sistema no se puede colorear y cada navegador dibuja la
   suya, así que el desplegable acababa siendo el único campo que desentonaba. */
select {
  appearance:none; -webkit-appearance:none; cursor:pointer; padding-right:34px;
  background-image:var(--flecha); background-repeat:no-repeat; background-position:right 12px center;
}
select:disabled { background-image:none; }

input[type=file] { padding:7px; background:var(--borde-suave); cursor:pointer; }
input[type=file]::file-selector-button {
  font:inherit; font-size:13px; font-weight:600; margin-right:11px; cursor:pointer;
  padding:7px 13px; border-radius:8px; border:1px solid var(--borde);
  background:var(--tarjeta); color:var(--texto); transition:border-color .12s, color .12s;
}
input[type=file]::file-selector-button:hover { border-color:var(--verde-vivo); color:var(--verde); }
input[type=checkbox],input[type=radio] { width:auto; accent-color:var(--verde); }
textarea { min-height:92px; resize:vertical; }
label { display:block; font-size:13px; font-weight:600; margin:16px 0 6px; }
label .pista { display:block; font-weight:400; color:var(--suave); font-size:12.5px; margin:2px 0 6px; }
fieldset { border:0; padding:0; margin:0; }
.rejilla { display:grid; grid-template-columns:repeat(auto-fit,minmax(215px,1fr)); gap:0 18px; }
/* Los recuadros de una misma fila tienen que empezar todos a la misma altura:
   si no, basta con que una pista ocupe dos líneas y otra una para que la fila
   quede escalonada. Las pistas crecen hacia arriba y el campo se queda abajo. */
.rejilla > label { display:flex; flex-direction:column; }
.rejilla > label > input,
.rejilla > label > select,
.rejilla > label > textarea,
.rejilla > label > .unidad { margin-top:auto; }
code { background:var(--borde-suave); padding:1px 5px; border-radius:5px; font-size:12.5px; }

/* Campos con unidad: el «S/» y el «%» dentro del recuadro evitan tener que
   repetirlos en la etiqueta y dejan claro qué se espera escribir. */
.unidad { position:relative; display:block; }
.unidad > i {
  position:absolute; top:50%; transform:translateY(-50%); font-style:normal;
  color:var(--suave); font-size:13.5px; pointer-events:none;
}
.unidad.izq > i { left:12px; }
.unidad.der > i { right:13px; }
.unidad.izq input { padding-left:34px; }
.unidad.der input { padding-right:32px; }

/* Título de sección dentro de un formulario largo. */
.seccion {
  margin:30px 0 4px; padding-top:22px; border-top:1px solid var(--borde-suave);
  display:flex; align-items:baseline; gap:10px; flex-wrap:wrap;
}
.seccion h2 { margin:0; font-size:15px; }
.seccion .mini { margin:0; }

.barra {
  display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:16px;
}
.barra input[type=search] { max-width:300px; }
.barra select { width:auto; }
.barra .derecha { margin-left:auto; }

.chips { display:flex; flex-wrap:wrap; gap:7px; margin-top:6px; }
.chips label {
  display:inline-flex; align-items:center; gap:6px; margin:0; font-weight:500; font-size:13px;
  background:var(--tarjeta); border:1px solid var(--borde); padding:6px 11px;
  border-radius:999px; cursor:pointer; transition:background .12s, border-color .12s;
}
.chips label:hover { border-color:var(--verde-vivo); }
.chips label:has(input:checked) {
  background:var(--verde-tenue); border-color:var(--verde); color:var(--verde); font-weight:700;
}
.chips label:has(input:disabled) { opacity:.45; cursor:not-allowed; border-color:var(--borde); }

/* --- Qué es lo que se está dando de alta --------------------------------- */
.tipos { display:flex; gap:9px; flex-wrap:wrap; margin-top:6px; }
.tipos label {
  margin:0; flex:1 1 200px; display:flex; gap:11px; align-items:flex-start; cursor:pointer;
  border:1px solid var(--borde); border-radius:12px; padding:13px 15px; background:var(--tarjeta);
  font-weight:400; transition:border-color .12s, background .12s, box-shadow .12s;
}
.tipos label:hover { border-color:var(--verde-vivo); }
.tipos label:has(input:checked) {
  border-color:var(--verde); background:var(--verde-tenue); box-shadow:inset 0 0 0 1px var(--verde);
}
.tipos input { margin:3px 0 0; }
.tipos b { display:block; font-size:14px; }
.tipos label:has(input:checked) b { color:var(--verde); }
.tipos small { display:block; margin-top:3px; color:var(--suave); font-size:12.5px; line-height:1.45; }

/* --- Elegir productos con la foto delante -------------------------------- */
.selector {
  border:1px solid var(--borde); border-radius:12px; background:var(--borde-suave); padding:13px;
}
.selector .cabeza { display:flex; gap:9px; align-items:center; flex-wrap:wrap; margin-bottom:11px; }
.selector .cabeza input[type=search] { flex:1 1 210px; width:auto; background:var(--tarjeta); }
.parrilla {
  display:grid; grid-template-columns:repeat(auto-fill,minmax(136px,1fr));
  gap:9px; max-height:340px; overflow-y:auto; padding:2px;
}
.ficha-prod {
  display:flex; flex-direction:column; gap:7px; width:100%; text-align:left;
  border:1px solid var(--borde); border-radius:11px; background:var(--tarjeta);
  padding:9px; cursor:pointer; font:inherit; color:var(--texto);
  transition:border-color .12s, box-shadow .12s, transform .08s;
}
.ficha-prod:hover { border-color:var(--verde); box-shadow:var(--sombra); }
.ficha-prod:active { transform:scale(.98); }
.ficha-prod:focus-visible { outline:2px solid var(--verde-vivo); outline-offset:2px; }
.ficha-prod img,.ficha-prod .vacia {
  width:100%; aspect-ratio:1/1; object-fit:contain; background:#fff;
  border:1px solid var(--borde-suave); border-radius:8px; padding:3px;
}
.ficha-prod .vacia { display:grid; place-items:center; color:#9aa8a0; font-size:22px; }
.ficha-prod b { font-size:12.5px; font-weight:600; line-height:1.32; }
.ficha-prod span { font-size:12px; color:var(--suave); }
.ficha-prod .baja { color:var(--ambar); font-weight:600; }
.parrilla .nada { grid-column:1/-1; padding:22px; text-align:center; color:var(--suave); font-size:13px; }

/* Una fila por producto elegido. */
.linea {
  display:flex; gap:11px; align-items:center; flex-wrap:wrap; margin-bottom:8px;
  border:1px solid var(--borde); border-radius:11px; background:var(--tarjeta); padding:9px 11px;
}
.linea .mini-foto {
  width:42px; height:42px; flex:0 0 42px; object-fit:contain; padding:2px;
  background:#fff; border:1px solid var(--borde-suave); border-radius:8px;
}
.linea .nom { flex:1 1 190px; min-width:0; font-size:13.5px; font-weight:600; line-height:1.3; }
.linea .nom span { display:block; font-weight:400; color:var(--suave); font-size:12px; margin-top:1px; }
.linea .cant { width:74px; flex:0 0 74px; }
.linea .regalo {
  margin:0; display:inline-flex; align-items:center; gap:7px; cursor:pointer; white-space:nowrap;
  font-size:13px; font-weight:500; border:1px solid var(--borde); border-radius:999px;
  padding:7px 12px; transition:background .12s, border-color .12s;
}
.linea .regalo:has(input:checked) {
  background:var(--ambar-tenue); border-color:var(--ambar); color:var(--ambar); font-weight:700;
}
.linea.es-regalo { border-color:var(--ambar); background:var(--ambar-tenue); }
/* Línea que ha puesto el emparejador y conviene mirar antes de guardar. */
.linea.revisar { border-color:var(--ambar); border-left-width:4px; }
.linea .revisar-pill {
  border:1px solid var(--ambar); background:var(--ambar-tenue); color:var(--ambar);
  font:inherit; font-size:11.5px; font-weight:700; padding:4px 10px; border-radius:999px;
  cursor:pointer; white-space:nowrap;
}
.linea .revisar-pill:hover { filter:brightness(.95); }
@media (max-width:560px) {
  /* En un móvil, con fichas de 136 px entra una sola por fila y la foto sale
     enorme; con 104 px entran dos y se sigue reconociendo el producto. */
  .parrilla { grid-template-columns:repeat(auto-fill,minmax(104px,1fr)); }
  .linea .nom { flex:1 1 100%; }
  .linea .cant,.linea .regalo,.linea .btn { flex:1 1 auto; }
}

/* Interruptor: para «activo / desactivado» se lee mucho mejor que una casilla. */
.palanca { display:flex; align-items:center; gap:11px; margin:18px 0 0; font-weight:600; font-size:14px; cursor:pointer; }
.palanca input { position:absolute; opacity:0; pointer-events:none; }
.palanca .via {
  width:44px; height:25px; flex:0 0 44px; border-radius:999px; background:var(--borde);
  position:relative; transition:background .16s;
}
.palanca .via::after {
  content:''; position:absolute; top:3px; left:3px; width:19px; height:19px; border-radius:50%;
  background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.3); transition:transform .16s;
}
.palanca input:checked + .via { background:var(--verde); }
.palanca input:checked + .via::after { transform:translateX(19px); }
.palanca input:focus-visible + .via { outline:2px solid var(--verde-vivo); outline-offset:2px; }

/* --- Avisos --------------------------------------------------------------- */
.aviso {
  padding:12px 15px; border-radius:11px; margin:0 0 18px; font-size:14px;
  border:1px solid transparent; display:flex; gap:9px; align-items:flex-start;
}
.aviso.ok  { background:var(--verde-tenue); border-color:var(--verde-borde); color:var(--verde); }
.aviso.mal { background:var(--rojo-tenue); border-color:var(--rojo); color:var(--rojo); }
.aviso.ojo { background:var(--ambar-tenue); border-color:var(--ambar); color:var(--ambar); }
.aviso a { color:inherit; }

/* --- Fotos ---------------------------------------------------------------- */
.foto {
  width:42px; height:42px; object-fit:contain; background:#fff;
  border:1px solid var(--borde); border-radius:9px; padding:2px;
}
.sinfoto {
  width:42px; height:42px; border:1px dashed var(--borde); border-radius:9px;
  display:grid; place-items:center; font-size:15px; color:var(--suave);
}

/* --- Pantalla de acceso (sin barra lateral) ------------------------------- */
.acceso { min-height:100vh; display:grid; place-items:center; padding:24px; width:100%; }
.acceso .caja { width:100%; max-width:420px; }
.acceso .marca { justify-content:center; padding:0 0 20px; color:var(--texto); }
.acceso .marca span { color:var(--suave); }

/* --- Móvil ---------------------------------------------------------------- */
@media (max-width:860px) {
  .app { flex-direction:column; }
  .lateral {
    width:auto; flex:none; height:auto; position:static;
    flex-direction:row; align-items:center; gap:6px; padding:10px 14px; overflow-x:auto;
  }
  /* En el móvil la barra es una tira horizontal y el espacio es el que manda:
     el nombre de la marca se va (el icono ya identifica el panel) y «Ver la web»
     y «Salir» se quedan en icono, para que las tres secciones quepan a la vez
     sin tener que arrastrar la tira de lado. */
  .marca { padding:0 6px 0 0; }
  .marca > div:not(.hoja) { display:none; }
  .lateral nav { flex-direction:row; }
  .lateral nav a { padding:8px 11px; white-space:nowrap; }
  .lateral nav a .largo { display:none; }
  .lateral nav a .corto { display:inline; }
  .lateral .abajo { margin:0 0 0 auto; padding:0; border:0; }
  .lateral .abajo a { padding:8px 9px; }
  .lateral .abajo a span { display:none; }
  .topbar, main { padding-left:16px; padding-right:16px; }
}
@media (max-width:700px) {
  /* La tabla pasa a fichas apiladas: en un móvil, ocho columnas no caben sin
     que el precio acabe en una línea que hay que arrastrar para leer. */
  .tabla { background:transparent; border:0; box-shadow:none; }
  table, tbody, tr, td { display:block; width:100%; }
  thead { display:none; }
  tbody tr {
    background:var(--tarjeta); border:1px solid var(--borde); border-radius:12px;
    margin-bottom:12px; padding:12px 14px; box-shadow:var(--sombra);
  }
  tbody tr:hover { background:var(--tarjeta); }
  td { padding:3px 0; }
  td.num { text-align:left; }
  td.num::before { content:attr(data-th) ": "; color:var(--suave); font-size:12px; }
}
</style>
</head>
<body>
<?php if (!$conSesion): ?>
<div class="acceso">
<?php else: ?>
<div class="app">
  <aside class="lateral">
    <div class="marca">
      <div class="hoja">🌿</div>
      <div><b>Santa Natura</b><span>Panel de gestión</span></div>
    </div>
    <nav>
      <?php foreach (sn_menu() as $m): ?>
        <a href="<?= htmlspecialchars($m['archivo'], ENT_QUOTES) ?>"
           class="<?= $aqui === $m['archivo'] ? 'activa' : '' ?>">
          <i><?= $m['icono'] ?></i><span class="largo"><?= htmlspecialchars($m['texto'], ENT_QUOTES) ?></span><span class="corto"><?= htmlspecialchars($m['corto'], ENT_QUOTES) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="abajo">
      <nav>
        <a href="../index.php" target="_blank" rel="noopener" title="Ver la web"><i>↗</i><span>Ver la web</span></a>
        <a href="salir.php" title="Salir"><i>⏻</i><span>Salir</span></a>
      </nav>
    </div>
  </aside>

  <div class="cuerpo">
    <header class="topbar">
      <div>
        <h1><?= htmlspecialchars($titulo, ENT_QUOTES) ?></h1>
        <?php if ($sub !== ''): ?><p class="sub"><?= htmlspecialchars($sub, ENT_QUOTES) ?></p><?php endif; ?>
      </div>
      <div class="acciones"><?php sn_acciones(); ?></div>
    </header>
    <main>
<?php endif; ?>
<?php
}

/* Botonera de la derecha de la cabecera. Cada página la rellena llamando a
   sn_definir_acciones() ANTES de sn_cabecera(); si no, no sale nada. */
function sn_definir_acciones(?callable $pinta = null): void
{
    static $guardado = null;
    if ($pinta !== null) {
        $guardado = $pinta;
        return;
    }
    if ($guardado !== null) {
        ($guardado)();
    }
}

function sn_acciones(): void
{
    sn_definir_acciones();
}

function sn_pie(): void
{
    if (sn_con_sesion()) {
        ?>
    </main>
  </div>
</div>
<?php
    } else {
        ?>
</div>
<?php
    }
    ?>
</body>
</html>
<?php
}

/** Muestra y consume el mensaje guardado tras un guardado o un error. */
function sn_mensaje_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    [$tipo, $texto] = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<p class="aviso ' . htmlspecialchars($tipo, ENT_QUOTES) . '">'
       . htmlspecialchars($texto, ENT_QUOTES) . '</p>';
}

function sn_flash(string $tipo, string $texto): void
{
    $_SESSION['flash'] = [$tipo, $texto];
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES);
}
