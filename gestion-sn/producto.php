<?php
/* ============================================================================
   Panel de gestión — alta y edición de un producto o pack
   ----------------------------------------------------------------------------
   Las reglas del catálogo (puntos = pvp/6 en packs, descuentos = pvp*0.90,
   badge calculado desde los dos precios) se aplican solas, para que no haya
   que recordarlas ni se metan a mano cifras que no cuadren.

   Nada de descuentos inventados: si no escribes un precio base, el producto
   sale sin badge y sin «Ahorras». El badge SIEMPRE sale de la resta.
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/catalogo.php';
require __DIR__ . '/_layout.php';

sn_exigir_sesion();

$productos = sn_productos();
$id        = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$esNuevo   = $id === 0;

$busca = static function (array $lista, int $id): ?array {
    foreach ($lista as $x) {
        if ((int) $x['id'] === $id) { return $x; }
    }
    return null;
};

$p = $busca($productos, $id);
if (!$esNuevo && $p === null) {
    sn_flash('mal', 'No existe el producto ' . $id . '.');
    header('Location: index.php');
    exit;
}

$errores = [];

/* --------------------------------------------------------------------------
   Guardar
   -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sn_csrf_valido($_POST['csrf'] ?? null)) {
        $errores[] = 'La sesión caducó. Vuelve a enviar el formulario.';
    }

    $nombre = trim((string) ($_POST['producto'] ?? ''));
    $linea  = (string) ($_POST['categoria'] ?? 'Packs');
    $pvpTxt = str_replace(',', '.', trim((string) ($_POST['pvp'] ?? '')));
    $baseTxt = str_replace(',', '.', trim((string) ($_POST['precio_original'] ?? '')));
    $imagen = trim((string) ($_POST['imagen'] ?? ''));
    $descr  = trim((string) ($_POST['descripcion'] ?? ''));
    $cats   = array_values(array_filter((array) ($_POST['categorias'] ?? []), 'is_string'));
    $publicado = ($_POST['publicado'] ?? '') === '1';

    /* Contenido del pack: una fila por producto, con su cantidad y si va como
       regalo. Se admite el mismo producto dos veces (2 incluidos + 1 de
       regalo, que es como están armados varios packs), pero no repetido en la
       misma modalidad: eso saldría en la ficha como «2 X, 3 X». */
    $contiene = [];
    $filas    = (array) ($_POST['cont_id'] ?? []);
    $cants    = (array) ($_POST['cont_cant'] ?? []);
    $tipos    = (array) ($_POST['cont_tipo'] ?? []);
    $indice   = sn_por_id($productos);

    foreach ($filas as $i => $refId) {
        $refId = (int) $refId;
        if ($refId <= 0) {
            continue;                       // fila vacía: se ignora sin ruido
        }
        if (!isset($indice[$refId])) {
            $errores[] = 'El producto ' . $refId . ' del contenido ya no existe en el catálogo.';
            continue;
        }
        if (!$esNuevo && $refId === $id) {
            $errores[] = 'Un pack no puede contenerse a sí mismo.';
            continue;
        }

        $cant = (int) ($cants[$i] ?? 1);
        if ($cant < 1 || $cant > 99) {
            $errores[] = 'La cantidad de «' . $indice[$refId]['producto'] . '» tiene que estar entre 1 y 99.';
            continue;
        }

        $esRegalo = (($tipos[$i] ?? 'incluido') === 'regalo');
        $clave    = $refId . ($esRegalo ? 'r' : 'i');

        if (isset($contiene[$clave])) {
            $errores[] = '«' . $indice[$refId]['producto'] . '» está repetido en el contenido. '
                       . 'Súmalo en una sola línea con la cantidad total.';
            continue;
        }

        $fila = ['id' => $refId, 'cant' => $cant];
        if ($esRegalo) { $fila['regalo'] = true; }
        $contiene[$clave] = $fila;
    }
    $contiene = array_values($contiene);

    // Con contenido armado, la frase de la ficha se escribe sola: así no puede
    // quedarse diciendo lo que el pack llevaba el mes pasado.
    if ($contiene) {
        $descr = sn_texto_contenido($contiene, $productos);
    }

    // ¿Viene una foto nueva en el formulario? Se sube más abajo, solo si el
    // resto de los datos es válido: así un formulario rechazado no deja
    // imágenes sueltas acumulándose en img/.
    $subida    = $_FILES['foto'] ?? null;
    $haySubida = is_array($subida)
              && (int) ($subida['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($nombre === '') {
        $errores[] = 'El nombre del producto es obligatorio.';
    }
    if (!is_numeric($pvpTxt) || (float) $pvpTxt <= 0) {
        $errores[] = 'El precio de venta debe ser un número mayor que cero.';
    }

    $base = ($baseTxt === '') ? null : (is_numeric($baseTxt) ? (float) $baseTxt : null);
    if ($baseTxt !== '' && $base === null) {
        $errores[] = 'El precio base no es un número.';
    }

    $pvp = is_numeric($pvpTxt) ? (float) $pvpTxt : 0.0;
    if ($base !== null && $base <= $pvp) {
        $errores[] = 'El precio base (' . $baseTxt . ') tiene que ser MAYOR que el precio de venta ('
                   . $pvpTxt . '); si no, no hay descuento que mostrar. Déjalo vacío si el producto no está en oferta.';
    }

    $validas = sn_categorias_validas();
    foreach ($cats as $c) {
        if (!in_array($c, $validas, true)) {
            $errores[] = 'La categoría «' . $c . '» no existe en el menú del sitio.';
        }
    }
    if (!$cats) {
        $errores[] = 'Elige al menos una categoría, o el producto no aparecerá en ninguna sección.';
    }
    if (!$haySubida && $imagen !== '' && !is_file(SN_RAIZ . '/' . $imagen)) {
        $errores[] = 'La imagen «' . $imagen . '» no existe en la carpeta img/.';
    }

    // La foto se guarda con todo lo demás ya validado. Si falla la subida, el
    // producto no se toca: mejor volver al formulario que publicar una ficha
    // apuntando a una imagen que no llegó a existir.
    if (!$errores && $haySubida) {
        try {
            $imagen = sn_subir_imagen($subida, $nombre);
        } catch (Throwable $e) {
            $errores[] = $e->getMessage();
        }
    }

    if (!$errores) {
        $esPack = in_array('Packs', $cats, true);

        $reg = [
            'id'        => $esNuevo ? sn_siguiente_id($productos) : $id,
            'categoria' => $linea,
            'producto'  => $nombre,
            // Puntos: regla del catálogo para packs. En productos sueltos la
            // referencia es el precio de lista, así que se usa el base si lo hay.
            'puntos'    => $esPack ? sn_puntos_pack($pvp) : round(($base ?? $pvp) / 6, 2),
            'pvp'       => round($pvp, 2),
        ];
        if (!$publicado) {
            $reg['activo'] = false;
        }
        if ($base !== null) {
            $reg['precio_original']    = round($base, 2);
            $reg['etiqueta_descuento'] = sn_etiqueta_descuento($pvp, $base);
        }
        if ($imagen !== '')  { $reg['imagen'] = $imagen; }
        if ($descr !== '')   { $reg['descripcion'] = $descr; }
        if ($contiene)       { $reg['contiene'] = $contiene; }

        sort($cats, SORT_NATURAL | SORT_FLAG_CASE);
        $reg['categorias'] = $cats;
        $reg['descuentos'] = $esPack
            ? sn_descuentos_pack($pvp)
            : ($p['descuentos'] ?? sn_descuentos_pack($pvp));

        try {
            if ($esNuevo) {
                $productos[] = $reg;
            } else {
                foreach ($productos as $i => $x) {
                    if ((int) $x['id'] === $id) { $productos[$i] = $reg; break; }
                }
            }
            sn_guardar($productos);
            sn_flash('ok', ($esNuevo ? 'Creado: ' : 'Guardado: ') . $nombre
                   . ($publicado ? '' : ' (queda desactivado, no se ve en la web)'));
            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            $errores[] = $e->getMessage();
        }
    }

    // Al fallar, se repinta con lo que el usuario escribió.
    $p = [
        'id' => $id, 'categoria' => $linea, 'producto' => $nombre,
        'pvp' => $pvpTxt, 'precio_original' => $baseTxt, 'imagen' => $imagen,
        'descripcion' => $descr, 'categorias' => $cats,
        'activo' => $publicado ? true : false,
        'contiene' => $contiene,
    ];
}

/* --------------------------------------------------------------------------
   Formulario
   -------------------------------------------------------------------------- */
$val       = static fn(string $k, $def = '') => $p[$k] ?? $def;
$catsAct   = (array) ($p['categorias'] ?? ['Packs']);
$publicado = $p === null ? true : (($p['activo'] ?? true) !== false);
$imagenes  = sn_imagenes();

sn_definir_acciones(static function (): void { ?>
  <a class="btn gris" href="index.php">Volver al catálogo</a>
<?php });

sn_cabecera(
    $esNuevo ? 'Nuevo producto' : (string) $val('producto'),
    true,
    'Los puntos, el descuento y el precio por cantidad se calculan solos desde los precios.'
);
?>

<?php foreach ($errores as $e): ?>
  <p class="aviso mal"><?= h($e) ?></p>
<?php endforeach; ?>

<form method="post" class="caja" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= h(sn_csrf()) ?>">

<label>Nombre del producto
  <span class="pista">Tal como debe verse en la web y en el mensaje de WhatsApp.</span>
  <input name="producto" required value="<?= h((string) $val('producto')) ?>" autofocus>
</label>

<div class="rejilla">
  <label>Precio de venta (S/)
    <span class="pista">El que se cobra hoy.</span>
    <input name="pvp" required inputmode="decimal" value="<?= h((string) $val('pvp')) ?>">
  </label>

  <label>Precio base (S/) <span style="font-weight:400">— opcional</span>
    <span class="pista">El de antes de la oferta. Déjalo vacío si no hay descuento: la tarjeta saldrá sin globo rojo y sin «Ahorras».</span>
    <input name="precio_original" inputmode="decimal" value="<?= h((string) $val('precio_original')) ?>">
  </label>

  <label>Línea de precios
    <span class="pista">Interna, no se muestra. Decide la escala de descuento por cantidad.</span>
    <select name="categoria">
      <?php foreach (['Packs', 'Línea General', 'Línea Convencional', 'Línea Consumo Saludable'] as $l): ?>
        <option value="<?= h($l) ?>" <?= $val('categoria', 'Packs') === $l ? 'selected' : '' ?>><?= h($l) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Foto
    <span class="pista">De las que ya están subidas en la carpeta img/.</span>
    <select name="imagen" id="selImagen" onchange="verFoto()">
      <option value="">— sin foto (sale el icono de la marca) —</option>
      <?php foreach ($imagenes as $img): ?>
        <option value="<?= h($img) ?>" <?= $val('imagen') === $img ? 'selected' : '' ?>><?= h(basename($img)) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
</div>

<label>…o sube una foto nueva
  <span class="pista">
    JPG, PNG o WEBP, hasta <?= (int) (SN_FOTO_MAX / 1024 / 1024) ?> MB. Se guarda en la carpeta
    <code>img/</code> con el nombre del producto y se usa para esta ficha, por encima de lo
    elegido arriba. Lo ideal es una imagen cuadrada, de unos 800×800.
  </span>
  <input type="file" name="foto" id="subirFoto" accept="image/jpeg,image/png,image/webp" onchange="verSubida()">
</label>

<p style="margin:10px 0 0"><img id="vistaFoto" class="foto" style="width:88px;height:88px" alt=""
   src="<?= $val('imagen') ? '../' . h((string) $val('imagen')) : '' ?>"
   <?= $val('imagen') ? '' : 'hidden' ?>></p>

<h2 style="margin-top:26px">Qué lleva dentro</h2>
<p class="mini" style="margin:0 0 10px">
  Para un pack, añade aquí los productos que lo componen con su cantidad. La frase de la
  ficha («Contiene: …») se escribe sola con lo que pongas, así que no puede quedarse
  diciendo lo del mes pasado. Deja la lista vacía si es un producto suelto.
</p>

<div id="contenido"></div>

<p style="margin:10px 0 0;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
  <button type="button" class="btn gris mini" onclick="agregarLinea()">Añadir producto</button>
  <span class="mini" id="sumaContenido"></span>
</p>

<label style="margin-top:18px">Descripción de la ficha
  <span class="pista" id="pistaDescripcion"></span>
  <textarea name="descripcion" id="campoDescripcion"><?= h((string) $val('descripcion')) ?></textarea>
</label>

<label>Categorías
  <span class="pista">Marca «Packs» si es un pack. Las «Para …» son las que decide el menú y las landings de anuncios.</span>
</label>
<div class="chips">
  <?php foreach (sn_categorias_validas() as $c): ?>
    <label>
      <input type="checkbox" name="categorias[]" value="<?= h($c) ?>"
        <?= in_array($c, $catsAct, true) ? 'checked' : '' ?>>
      <?= h($c) ?>
    </label>
  <?php endforeach; ?>
</div>

<label class="palanca" style="margin-top:24px">
  <input type="checkbox" name="publicado" value="1" <?= $publicado ? 'checked' : '' ?>>
  <span class="via"></span>
  <span>Activo en la web</span>
</label>
<p class="mini" style="margin:6px 0 0">
  Desactivado se guarda igual, pero no se ve en ninguna parte de la web
  (por ejemplo, mientras consigues la foto).
</p>

<p style="margin:26px 0 0;display:flex;gap:10px;flex-wrap:wrap;padding-top:18px;border-top:1px solid var(--borde-suave)">
  <button class="btn"><?= $esNuevo ? 'Crear producto' : 'Guardar cambios' ?></button>
  <a class="btn gris" href="index.php">Cancelar</a>
</p>
</form>

<?php if (!$esNuevo): ?>
<p class="mini" style="margin-top:14px">
  Cada vez que guardas se archiva una copia del catálogo anterior en <code>inc/copias/</code>.
</p>
<?php endif; ?>

<?php
/* Catálogo que se puede meter dentro de un pack: todo menos el propio producto
   que se está editando. Va al navegador como JSON para que añadir una línea no
   cueste una recarga. */
$componibles = [];
foreach ($productos as $x) {
    if (!$esNuevo && (int) $x['id'] === $id) {
        continue;
    }
    $componibles[] = [
        'id'     => (int) $x['id'],
        'nombre' => (string) $x['producto'],
        'pvp'    => (float) $x['pvp'],
        'baja'   => ($x['activo'] ?? true) === false,
    ];
}
usort($componibles, static fn($a, $b) => strnatcasecmp($a['nombre'], $b['nombre']));
$vol = static fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<script>
var CATALOGO = <?= $vol($componibles) ?>;
var LINEAS   = <?= $vol(array_values((array) $val('contiene', []))) ?>;

/* --------------------------------------------------------------------------
   Contenido del pack
   --------------------------------------------------------------------------
   Las líneas se dibujan aquí y no en PHP porque se añaden y se quitan sin
   guardar; el servidor solo ve el resultado al enviar el formulario.
   -------------------------------------------------------------------------- */
function dibujarLineas() {
  var caja = document.getElementById('contenido');
  caja.innerHTML = '';

  LINEAS.forEach(function (linea, i) {
    var fila = document.createElement('div');
    fila.className = 'linea';

    var sel = document.createElement('select');
    sel.name = 'cont_id[]';
    sel.innerHTML = '<option value="">— elige un producto —</option>' +
      CATALOGO.map(function (p) {
        return '<option value="' + p.id + '"' + (p.id === linea.id ? ' selected' : '') + '>' +
               p.nombre + (p.baja ? ' (desactivado)' : '') + '</option>';
      }).join('');
    sel.onchange = function () { LINEAS[i].id = parseInt(this.value, 10) || 0; sumar(); };

    var cant = document.createElement('input');
    cant.type = 'number'; cant.name = 'cont_cant[]'; cant.min = '1'; cant.max = '99';
    cant.value = linea.cant || 1; cant.style.width = '78px'; cant.title = 'Cantidad';
    cant.onchange = function () { LINEAS[i].cant = parseInt(this.value, 10) || 1; sumar(); };

    var tipo = document.createElement('select');
    tipo.name = 'cont_tipo[]';
    tipo.style.width = '132px';
    tipo.innerHTML = '<option value="incluido">Incluido</option>' +
                     '<option value="regalo"' + (linea.regalo ? ' selected' : '') + '>De regalo</option>';
    tipo.onchange = function () { LINEAS[i].regalo = this.value === 'regalo'; sumar(); };

    var quitar = document.createElement('button');
    quitar.type = 'button'; quitar.className = 'btn gris mini'; quitar.textContent = 'Quitar';
    quitar.onclick = function () { LINEAS.splice(i, 1); dibujarLineas(); };

    [cant, sel, tipo, quitar].forEach(function (e) { fila.appendChild(e); });
    caja.appendChild(fila);
  });

  sumar();
}

function agregarLinea() {
  LINEAS.push({ id: 0, cant: 1, regalo: false });
  dibujarLineas();
}

/* La suma de los productos sueltos es la referencia para poner el precio: si el
   pack no cuesta menos que comprarlos por separado, no hay pack que vender.
   Los regalos no suman, igual que en el cálculo del servidor. */
function sumar() {
  var precios = {}, nombres = {};
  CATALOGO.forEach(function (p) { precios[p.id] = p.pvp; nombres[p.id] = p.nombre; });

  var total = 0, incluidos = [], regalos = [];
  LINEAS.forEach(function (l) {
    if (!l.id || !nombres[l.id]) return;
    var cant = l.cant || 1;
    var texto = cant + ' ' + nombres[l.id];
    if (l.regalo) { regalos.push(texto); } else { incluidos.push(texto); total += precios[l.id] * cant; }
  });

  var frase = '';
  if (incluidos.length) { frase += 'Contiene: ' + incluidos.join(', ') + '.'; }
  if (regalos.length)   { frase += (frase ? ' ' : '') + 'De regalo: ' + regalos.join(', ') + '.'; }

  var campo = document.getElementById('campoDescripcion');
  var pista = document.getElementById('pistaDescripcion');
  var suma  = document.getElementById('sumaContenido');

  if (frase) {
    // Con contenido armado manda la lista: el texto se recalcula al guardar,
    // así que dejarlo editable aquí solo serviría para perder lo escrito.
    campo.value = frase;
    campo.readOnly = true;
    campo.style.background = '#f2f5f3';
    pista.textContent = 'La escribe sola la lista de arriba. Para redactarla a mano, quita las líneas.';
  } else {
    campo.readOnly = false;
    campo.style.background = '';
    pista.textContent = 'Texto corto que se lee en la tarjeta del producto.';
  }

  var pvp = parseFloat(String(document.getElementsByName('pvp')[0].value).replace(',', '.'));
  var comparacion = '';
  if (total > 0 && pvp > 0) {
    comparacion = pvp < total
      ? ' · el pack ahorra S/ ' + (total - pvp).toFixed(2)
      : ' · OJO: el pack cuesta igual o más que comprarlo suelto';
  }
  suma.textContent = total > 0 ? 'Sumados por separado: S/ ' + total.toFixed(2) + comparacion : '';
}

function verFoto() {
  var s = document.getElementById('selImagen'), v = document.getElementById('vistaFoto');
  if (s.value) { v.src = '../' + s.value; v.hidden = false; } else { v.hidden = true; }
}

// Vista previa de la foto que se acaba de elegir del disco: enseña lo que se va
// a subir ANTES de guardar, que es cuando todavía se puede corregir.
function verSubida() {
  var f = document.getElementById('subirFoto'), v = document.getElementById('vistaFoto');
  if (!f.files || !f.files[0]) { verFoto(); return; }
  v.src = URL.createObjectURL(f.files[0]);
  v.hidden = false;
}

dibujarLineas();
document.getElementsByName('pvp')[0].addEventListener('input', sumar);
</script>

<style>
  .linea { display:flex; gap:8px; align-items:center; margin-bottom:8px; flex-wrap:wrap; }
  .linea select:first-of-type { flex:1 1 260px; }
  @media (max-width:520px) { .linea > * { flex:1 1 100%; } }
</style>
<?php
sn_pie();
