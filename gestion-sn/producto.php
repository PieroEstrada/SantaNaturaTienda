<?php
/* ============================================================================
   Panel de gestión — alta y edición de un producto, un pack o una promo
   ----------------------------------------------------------------------------
   Lo primero que se elige es QUÉ se está dando de alta, porque de eso depende
   casi todo lo demás:

     · Producto individual — se vende solo. No lleva nada dentro.
     · Pack                — combo fijo del catálogo. Va en la sección «Packs».
     · Promo               — combo de temporada. NO va en «Packs»: dónde
                             aparece lo deciden sus categorías.

   Los dos combos comparten lo mismo: se eligen los productos que llevan dentro
   (con su foto, que es como se reconocen de un vistazo), se suma lo que valen
   por separado y esa suma es el precio normal de partida. El precio de venta y
   el porcentaje de descuento se calculan el uno al otro, así que no pueden
   contradecirse. Los puntos se escriben a mano; si se dejan en blanco, se
   aplica la regla de siempre (pvp / 6).

   Nada de descuentos inventados: el badge SIEMPRE sale de la resta entre los
   dos precios, nunca de un número escrito a mano.
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/catalogo.php';
require __DIR__ . '/_layout.php';

sn_exigir_sesion();

$productos = sn_productos();
$id        = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$esNuevo   = $id === 0;

/** Los tres tipos, con el texto que se lee en el formulario. */
const SN_TIPOS = [
    'individual' => ['Producto individual', 'Se vende solo. No lleva otros productos dentro.'],
    'pack'       => ['Pack',                'Combo fijo. Se marca solo en la categoría «Packs» y sale en esa sección de la web.'],
    'promo'      => ['Promo',               'Combo de temporada. No entra en «Packs»: dónde aparece lo deciden las categorías que marques.'],
];

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

/**
 * Qué es una ficha ya guardada. No hace falta un campo nuevo en products.js:
 * está en «Packs» -> pack; lleva productos dentro y no está en «Packs» ->
 * promo; no lleva nada dentro -> producto individual.
 */
function sn_tipo_de(?array $p): string
{
    if ($p === null) {
        return 'individual';
    }
    if (in_array('Packs', (array) ($p['categorias'] ?? []), true)) {
        return 'pack';
    }
    return empty($p['contiene']) ? 'individual' : 'promo';
}

$errores = [];

/* --------------------------------------------------------------------------
   Guardar
   -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sn_csrf_valido($_POST['csrf'] ?? null)) {
        $errores[] = 'La sesión caducó. Vuelve a enviar el formulario.';
    }

    $tipo = (string) ($_POST['tipo'] ?? 'individual');
    if (!isset(SN_TIPOS[$tipo])) {
        $tipo = 'individual';
    }
    $esCombo = $tipo !== 'individual';

    $nombre    = trim((string) ($_POST['producto'] ?? ''));
    $pvpTxt    = str_replace(',', '.', trim((string) ($_POST['pvp'] ?? '')));
    $baseTxt   = str_replace(',', '.', trim((string) ($_POST['precio_original'] ?? '')));
    $puntosTxt = str_replace(',', '.', trim((string) ($_POST['puntos'] ?? '')));
    $imagen    = trim((string) ($_POST['imagen'] ?? ''));
    $descr     = trim((string) ($_POST['descripcion'] ?? ''));
    $cats      = array_values(array_filter((array) ($_POST['categorias'] ?? []), 'is_string'));
    $publicado = ($_POST['publicado'] ?? '') === '1';

    // La línea de precios de un combo es siempre «Packs»: de ella depende la
    // escala de descuento por cantidad, y la de un combo no es la de un
    // producto suelto. En los combos ni siquiera se enseña el desplegable.
    $linea = $esCombo ? 'Packs' : (string) ($_POST['categoria'] ?? 'Línea General');

    /* Contenido del combo: una fila por producto, con su cantidad y si va como
       regalo. Se admite el mismo producto dos veces (2 incluidos + 1 de
       regalo, que es como están armados varios packs), pero no repetido en la
       misma modalidad: eso saldría en la ficha como «2 X, 3 X». */
    $contiene = [];
    $filas    = $esCombo ? (array) ($_POST['cont_id'] ?? []) : [];
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
            $errores[] = 'Un combo no puede contenerse a sí mismo.';
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
    // quedarse diciendo lo que el combo llevaba el mes pasado.
    if ($contiene) {
        $descr = sn_texto_contenido($contiene, $productos);
    }

    /* La categoría «Packs» no se marca a mano: la pone el tipo. Un pack la
       lleva siempre, y una promo o un producto suelto no la llevan nunca, o al
       recargar la ficha volvería marcada como pack. */
    $cats = array_values(array_diff($cats, ['Packs']));
    if ($tipo === 'pack') {
        $cats[] = 'Packs';
    }
    $cats = array_values(array_unique($cats));

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
        $errores[] = 'El precio normal no es un número.';
    }

    $pvp = is_numeric($pvpTxt) ? (float) $pvpTxt : 0.0;
    if ($base !== null && $base <= $pvp) {
        $errores[] = 'El precio normal (' . $baseTxt . ') tiene que ser MAYOR que el precio de venta ('
                   . $pvpTxt . '); si no, no hay descuento que mostrar. Déjalo vacío si no está en oferta.';
    }

    $puntos = ($puntosTxt === '') ? null : (is_numeric($puntosTxt) ? (float) $puntosTxt : null);
    if ($puntosTxt !== '' && $puntos === null) {
        $errores[] = 'Los puntos no son un número.';
    }
    if ($puntos !== null && $puntos < 0) {
        $errores[] = 'Los puntos no pueden ser negativos.';
    }

    /* Solo se exige contenido al dar de alta. Los packs que ya estaban en el
       catálogo no lo tienen todavía —llevan su frase escrita a mano— y si esto
       se exigiera siempre, no se les podría ni corregir una errata hasta
       haberles armado la lista entera. */
    if ($esCombo && !$contiene && $esNuevo) {
        $errores[] = ($tipo === 'pack' ? 'Un pack' : 'Una promo') . ' tiene que llevar al menos un producto dentro. '
                   . 'Elígelos arriba, o cambia el tipo a «Producto individual».';
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
        $reg = [
            'id'        => $esNuevo ? sn_siguiente_id($productos) : $id,
            'categoria' => $linea,
            'producto'  => $nombre,
            // Los puntos los pone quien da de alta la ficha, porque en los
            // combos no siempre salen de una fórmula. En blanco se aplica la
            // regla de siempre: pvp/6 en combos y sobre el precio de lista en
            // los productos sueltos.
            'puntos'    => $puntos !== null
                ? round($puntos, 2)
                : ($esCombo ? sn_puntos_pack($pvp) : round(($base ?? $pvp) / 6, 2)),
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
        $reg['descuentos'] = $esCombo
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
        'pvp' => $pvpTxt, 'precio_original' => $baseTxt, 'puntos' => $puntosTxt,
        'imagen' => $imagen, 'descripcion' => $descr, 'categorias' => $cats,
        'activo' => $publicado ? true : false,
        'contiene' => $contiene,
    ];
} else {
    $tipo = sn_tipo_de($p);
}

/* --------------------------------------------------------------------------
   Formulario
   -------------------------------------------------------------------------- */
$val       = static fn(string $k, $def = '') => $p[$k] ?? $def;
$catsAct   = (array) ($p['categorias'] ?? []);
$publicado = $p === null ? true : (($p['activo'] ?? true) !== false);
$imagenes  = sn_imagenes();

sn_definir_acciones(static function (): void { ?>
  <a class="btn gris" href="index.php">Volver al catálogo</a>
<?php });

sn_cabecera(
    $esNuevo ? 'Nuevo producto' : (string) $val('producto'),
    true,
    'El descuento y el precio de venta se calculan el uno al otro. Los puntos los pones tú.'
);
?>

<?php foreach ($errores as $e): ?>
  <p class="aviso mal"><?= h($e) ?></p>
<?php endforeach; ?>

<form method="post" class="caja" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= h(sn_csrf()) ?>">

<label>¿Qué estás dando de alta?
  <span class="pista">De esto depende si hay que elegir productos dentro y en qué sección de la web aparece.</span>
</label>
<div class="tipos">
  <?php foreach (SN_TIPOS as $clave => [$titulo, $explica]): ?>
    <label>
      <input type="radio" name="tipo" value="<?= h($clave) ?>"
             <?= $tipo === $clave ? 'checked' : '' ?> onchange="cambiarTipo()">
      <span><b><?= h($titulo) ?></b><small><?= h($explica) ?></small></span>
    </label>
  <?php endforeach; ?>
</div>

<label style="margin-top:22px">Nombre
  <span class="pista">Tal como debe verse en la web y en el mensaje de WhatsApp.</span>
  <input name="producto" required value="<?= h((string) $val('producto')) ?>" autofocus>
</label>

<!-- ---------------------------------------------------------------------- -->
<div id="bloqueContenido" hidden>
  <div class="seccion">
    <h2>Qué lleva dentro</h2>
    <p class="mini">La frase de la ficha («Contiene: …») se escribe sola con lo que pongas aquí.</p>
  </div>

  <!-- Los packs que ya estaban llevan su contenido escrito a mano dentro de la
       descripción. Esto lo lee y propone la lista, para no tener que buscar
       veinte productos uno a uno. Propone: no guarda nada hasta que se revisa. -->
  <p class="aviso ojo" id="desdeFrase" hidden>
    <span>
      Este <b id="quePone">pack</b> todavía lleva su contenido escrito dentro de la
      descripción. Puedo leerla y armar la lista por ti; después la revisas y guardas.
      <br>
      <button type="button" class="btn gris mini" style="margin-top:9px"
              onclick="armarDesdeFrase()">Armar la lista desde la frase</button>
    </span>
  </p>
  <div id="informeFrase" hidden></div>

  <div class="selector">
    <div class="cabeza">
      <input type="search" id="buscaProd" placeholder="Buscar producto por nombre…" oninput="dibujarParrilla()">
      <label class="mini" style="margin:0;display:inline-flex;gap:7px;align-items:center;font-weight:500;white-space:nowrap">
        <input type="checkbox" id="verBajas" onchange="dibujarParrilla()"> Ver también los desactivados
      </label>
    </div>
    <div class="parrilla" id="parrilla"></div>
    <p class="mini" style="margin:11px 0 0">Toca un producto para añadirlo. Si ya está en la lista, le sube la cantidad.</p>
  </div>

  <div id="contenido" style="margin-top:14px"></div>

  <p class="mini" id="sumaContenido" style="margin:10px 0 0"></p>
</div>

<!-- ---------------------------------------------------------------------- -->
<div class="seccion">
  <h2>Precios y puntos</h2>
  <p class="mini" id="pistaPrecios"></p>
</div>

<div class="rejilla">
  <label>Precio normal
    <span class="pista" id="pistaBase">El de antes de la oferta. Déjalo vacío si no hay descuento.</span>
    <span class="unidad izq"><i>S/</i>
      <input name="precio_original" id="campoBase" inputmode="decimal"
             value="<?= h((string) $val('precio_original')) ?>" oninput="baseAMano()">
    </span>
  </label>

  <label>Descuento
    <span class="pista">Sale de comparar los dos precios, y si lo escribes tú se recalcula el precio de venta.</span>
    <span class="unidad der"><i>%</i>
      <input id="campoDcto" inputmode="decimal" oninput="dctoAPrecio()">
    </span>
  </label>

  <label>Precio de venta
    <span class="pista">El que se cobra hoy.</span>
    <span class="unidad izq"><i>S/</i>
      <input name="pvp" id="campoPvp" required inputmode="decimal"
             value="<?= h((string) $val('pvp')) ?>" oninput="precioADcto()">
    </span>
  </label>

  <label>Puntos
    <span class="pista" id="pistaPuntos">Déjalo vacío y se calculan solos.</span>
    <input name="puntos" id="campoPuntos" inputmode="decimal" placeholder="se calculan solos"
           value="<?= h((string) $val('puntos')) ?>">
  </label>
</div>

<p style="margin:12px 0 0;display:flex;gap:9px;flex-wrap:wrap">
  <button type="button" class="btn gris mini" id="botonSuma" hidden onclick="usarSuma()">Usar la suma</button>
  <button type="button" class="btn gris mini" onclick="usarPuntos()"><span>Usar los puntos calculados (<span id="puntosSugeridos">—</span>)</span></button>
</p>

<!-- ---------------------------------------------------------------------- -->
<div class="seccion"><h2>Foto</h2></div>

<div class="rejilla">
  <label>De las que ya están subidas
    <span class="pista">Están en la carpeta img/. Sin foto sale el icono de la marca.</span>
    <select name="imagen" id="selImagen" onchange="verFoto()">
      <option value="">— sin foto —</option>
      <?php foreach ($imagenes as $img): ?>
        <option value="<?= h($img) ?>" <?= $val('imagen') === $img ? 'selected' : '' ?>><?= h(basename($img)) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>…o sube una nueva
    <span class="pista">
      JPG, PNG o WEBP, hasta <?= (int) (SN_FOTO_MAX / 1024 / 1024) ?> MB. Manda sobre lo elegido al lado.
      Lo ideal es cuadrada, de unos 800×800.
    </span>
    <input type="file" name="foto" id="subirFoto" accept="image/jpeg,image/png,image/webp" onchange="verSubida()">
  </label>
</div>

<p style="margin:12px 0 0"><img id="vistaFoto" class="foto" style="width:96px;height:96px" alt=""
   src="<?= $val('imagen') ? '../' . h((string) $val('imagen')) : '' ?>"
   <?= $val('imagen') ? '' : 'hidden' ?>></p>

<!-- ---------------------------------------------------------------------- -->
<div class="seccion"><h2>Dónde aparece y qué dice</h2></div>

<label id="bloqueLinea">Línea de precios
  <span class="pista">Interna, no se muestra. Decide la escala de descuento por cantidad.</span>
  <select name="categoria" id="campoLinea">
    <?php foreach (['Línea General', 'Línea Convencional', 'Línea Consumo Saludable', 'Packs'] as $l): ?>
      <option value="<?= h($l) ?>" <?= $val('categoria', 'Línea General') === $l ? 'selected' : '' ?>><?= h($l) ?></option>
    <?php endforeach; ?>
  </select>
</label>

<label>Descripción de la ficha
  <span class="pista" id="pistaDescripcion"></span>
  <textarea name="descripcion" id="campoDescripcion"><?= h((string) $val('descripcion')) ?></textarea>
</label>

<label>Categorías
  <span class="pista" id="pistaCategorias"></span>
</label>
<div class="chips">
  <?php foreach (sn_categorias_validas() as $c): ?>
    <label<?= $c === 'Packs' ? ' id="chipPacks"' : '' ?>>
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
  <button class="btn"><?= $esNuevo ? 'Crear' : 'Guardar cambios' ?></button>
  <a class="btn gris" href="index.php">Cancelar</a>
</p>
</form>

<?php if (!$esNuevo): ?>
<p class="mini" style="margin-top:14px">
  Cada vez que guardas se archiva una copia del catálogo anterior en <code>inc/copias/</code>.
</p>
<?php endif; ?>

<?php
/* Catálogo que se puede meter dentro de un combo: todo menos el propio
   producto que se está editando. Va al navegador como JSON para que elegir un
   producto no cueste una recarga. Se manda la foto porque es lo que hace que
   se reconozcan de un vistazo, y el precio de lista porque es con el que se
   calcula el precio normal del combo. */
$componibles = [];
foreach ($productos as $x) {
    if (!$esNuevo && (int) $x['id'] === $id) {
        continue;
    }
    $componibles[] = [
        'id'     => (int) $x['id'],
        'nombre' => (string) $x['producto'],
        'pvp'    => (float) $x['pvp'],
        // Precio normal: el de lista si lo tiene, y si no el que se cobra hoy.
        'lista'  => (float) ($x['precio_original'] ?? $x['pvp']),
        'img'    => (string) ($x['imagen'] ?? ''),
        'baja'   => ($x['activo'] ?? true) === false,
    ];
}
usort($componibles, static fn($a, $b) => strnatcasecmp($a['nombre'], $b['nombre']));
$vol = static fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<script>
var CATALOGO = <?= $vol($componibles) ?>;
var LINEAS   = <?= $vol(array_values((array) $val('contiene', []))) ?>;
var POR_ID   = {};
CATALOGO.forEach(function (p) { POR_ID[p.id] = p; });

// La frase tal como estaba al abrir la ficha. Hay que guardarla aparte porque
// en cuanto haya lista, la descripción se reescribe sola.
var FRASE_ORIGINAL = <?= $vol((string) $val('descripcion')) ?>;

/* El precio normal se rellena solo con la suma del contenido, pero SOLO en una
   ficha nueva. En una que ya existe, rellenarlo solo le pondría de golpe un
   globo de descuento que hoy no tiene, y eso se vería en la web. Para eso está
   el botón «Usar la suma», que lo hace cuando se pide. */
var baseAutomatico = <?= $esNuevo ? 'true' : 'false' ?>;

function num(v) { var n = parseFloat(String(v == null ? '' : v).replace(',', '.')); return isFinite(n) ? n : 0; }
function tipoActual() { return document.querySelector('input[name=tipo]:checked').value; }
function esCombo() { return tipoActual() !== 'individual'; }

/* --------------------------------------------------------------------------
   Tipo: individual / pack / promo
   --------------------------------------------------------------------------
   «Packs» no es una categoría que se marque a mano, es la consecuencia de ser
   un pack. Se deja a la vista pero bloqueada para que se entienda por qué está
   como está; el servidor la vuelve a imponer de todos modos.
   -------------------------------------------------------------------------- */
function cambiarTipo() {
  var combo = esCombo(), tipo = tipoActual();

  document.getElementById('bloqueContenido').hidden = !combo;
  document.getElementById('bloqueLinea').hidden = combo;
  document.getElementById('botonSuma').hidden = !combo;

  var chip = document.getElementById('chipPacks');
  if (chip) {
    var casilla = chip.querySelector('input');
    casilla.checked  = (tipo === 'pack');
    casilla.disabled = true;
    chip.title = tipo === 'pack'
      ? 'La pone el tipo «Pack».'
      : 'Solo la llevan los packs. Cambia el tipo arriba si esto es un pack.';
  }

  document.getElementById('pistaCategorias').textContent = combo
    ? (tipo === 'pack'
        ? 'Las «Para …» deciden en qué secciones del menú y en qué landings aparece. «Packs» la pone el tipo.'
        : 'Marca dónde quieres que salga la promo: Top Ventas, Favoritos de la semana, «Para …»…')
    : 'Las «Para …» son las que decide el menú y las landings de anuncios.';

  document.getElementById('pistaPrecios').textContent = combo
    ? 'El precio normal es la suma de lo que llevas dentro; el descuento sale de comparar los dos precios.'
    : '';

  document.getElementById('pistaBase').textContent = combo
    ? 'Lo que costarían por separado. Se rellena solo con la suma de arriba, pero puedes cambiarlo.'
    : 'El de antes de la oferta. Déjalo vacío si no hay descuento.';

  document.getElementById('pistaPuntos').textContent = combo
    ? 'Los que da este combo. En blanco se calculan como pvp / 6.'
    : 'En blanco se calculan solos sobre el precio de lista.';

  // Oferta de armar la lista leyendo la frase: solo tiene sentido en un combo
  // que todavía no tiene lista y cuya descripción dice qué lleva dentro.
  var oferta = document.getElementById('desdeFrase');
  oferta.hidden = !hayFraseQueLeer();
  document.getElementById('quePone').textContent = tipo === 'promo' ? 'promo' : 'pack';

  if (combo && !LINEAS.length) { dibujarParrilla(); }
  sumar();
}

/* --------------------------------------------------------------------------
   Armar la lista leyendo la frase escrita a mano
   --------------------------------------------------------------------------
   Los packs que ya estaban en el catálogo guardan su contenido dentro de la
   descripción («Contiene: 2 Tocosh, 1 Toxizero. De regalo: 1 Aloe.»), con
   nombres cortos que no son los del catálogo («Tocosh» contra «Caja de sachet
   15u tocosh con uña de gato y muña x 5 g c/u»). Esto los empareja.

   Nunca acierta al 100 % y no lo pretende: lo que no ve claro lo deja marcado
   para revisar, y lo que no encuentra lo dice por su nombre. Se probó contra
   los 47 packs del catálogo: 135 líneas emparejadas, 19 marcadas para revisar
   y 4 que no existen como producto suelto.
   -------------------------------------------------------------------------- */
var SN_VACIAS = ['de','del','la','las','el','los','con','y','en','un','una','al','para','su','sus','x'];

function snNormalizar(texto) {
  return String(texto || '')
    .toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')   // fuera acentos
    .replace(/[^a-z0-9+ ]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

/** Palabras con peso: fuera las unidades (500 ml, 450 gr…) y las vacías. */
function snTokens(texto) {
  var t = snNormalizar(texto)
    .replace(/\b\d+\s*(ml|l|gr|g|kg|mg|u|un|unidades)\b/g, ' ')
    .replace(/\b(ml|gr|kg|mg)\b/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

  return t.split(' ').filter(function (p) {
    return p && SN_VACIAS.indexOf(p) === -1;
  }).map(function (p) {
    // Plural burdo: «concentrados» -> «concentrado», «colagenos» -> «colageno».
    if (p.length > 4 && p.slice(-2) === 'es') { return p.slice(0, -2); }
    if (p.length > 4 && p.slice(-1) === 's')  { return p.slice(0, -1); }
    return p;
  });
}

/**
 * Dos palabras son la misma si coinciden, o si una es principio de la otra sin
 * perder mucho por el camino. Lo segundo hace falta por cómo se abrevian los
 * nombres, pero si se admite a lo bruto, «supertrim» acaba emparejado con
 * «súper concentrado», que no tiene nada que ver.
 */
function snMismaPalabra(a, b) {
  if (a === b) { return true; }
  var corta = a.length < b.length ? a : b;
  var larga = a.length < b.length ? b : a;
  return corta.length >= 5 && larga.indexOf(corta) === 0 && corta.length / larga.length >= 0.7;
}

/** Palabras con peso del pedido que NO están en el nombre del producto. */
function snSobran(pedido, producto) {
  var b = snTokens(producto);
  return snTokens(pedido).filter(function (t) {
    if (t.length < 5) { return false; }
    return !b.some(function (u) { return snMismaPalabra(t, u); });
  });
}

/**
 * Peso de cada palabra según lo rara que sea en el catálogo. «hidrolizado» lo
 * dicen varios productos y no distingue nada; «premium» lo dice uno y decide.
 * Sin esto, «Colágeno Hidrolizado Premium» se iba al «plus».
 */
function snPesos(catalogo) {
  var veces = {}, n = catalogo.length || 1;
  catalogo.forEach(function (p) {
    var vistas = {};
    snTokens(p.nombre).forEach(function (t) {
      if (!vistas[t]) { vistas[t] = 1; veces[t] = (veces[t] || 0) + 1; }
    });
  });
  return function (t) { return Math.log(n / (1 + (veces[t] || 0))) + 0.3; };
}

/** El tamaño que declara un nombre: «x 450 g», «500 ml»… Vacío si no dice. */
function snTamano(texto) {
  var m = snNormalizar(texto).match(/(\d+)\s*(ml|kg|mg|gr|g|l)\b/);
  if (!m) { return ''; }
  return m[1] + (m[2] === 'gr' ? 'g' : m[2]);
}

/** Cuánto se parece un trozo de la frase a un producto: 0 a 1. */
function snParecido(pedido, producto, peso) {
  var a = snTokens(pedido), b = snTokens(producto);
  if (!a.length || !b.length) { return 0; }

  var punto;
  if (a.join('') === b.join('')) {
    // El nombre pegado sin espacios resuelve «Carti Mix» contra «Cartimix».
    punto = 1;
  } else {
    var suma = 0, encontrado = 0, cuantos = 0, largos = 0, largosEncontrados = 0;
    a.forEach(function (t) {
      var p = peso(t);
      suma += p;
      var hay = b.some(function (u) { return snMismaPalabra(t, u); });
      if (hay) { encontrado += p; cuantos++; }
      if (t.length >= 5) { largos++; if (hay) { largosEncontrados++; } }
    });

    // Sin ninguna palabra larga en común no hay parecido que valga: «vitamagne»
    // y «maca» comparten poco más que el hueco.
    if (largos > 0 && largosEncontrados === 0) { return 0; }

    var cobertura = suma > 0 ? encontrado / suma : 0;
    // Y se penaliza que al producto le sobre nombre: entre «Colágeno premium»
    // y «Colágeno hidrolizado plus», para un «Colágeno Hidrolizado Premium»
    // gana el que no arrastra una palabra que la frase no dice.
    var ajuste = cuantos / b.length;
    punto = cobertura * 0.8 + ajuste * 0.2;
  }

  // El tamaño desempata cuando el nombre no puede: «Carti Mix 200 gr» tiene
  // que irse al de 200 g y no al de 100 g.
  var ta = snTamano(pedido), tb = snTamano(producto);
  if (ta && tb) { punto += (ta === tb) ? 0.05 : -0.15; }

  return Math.max(0, Math.min(1, punto));
}

/** Parte «Contiene: … De regalo: …» en trozos con su cantidad delante. */
function snTrozos(frase) {
  var texto = String(frase || ''), salida = [], marcas = [];
  var re = /(contiene|de regalo)\s*:?\s*/gi, m;

  while ((m = re.exec(texto)) !== null) {
    marcas.push({
      regalo: m[1].toLowerCase().indexOf('regalo') !== -1,
      desde:  m.index + m[0].length
    });
  }

  marcas.forEach(function (marca, i) {
    var hasta = (i + 1 < marcas.length) ? texto.lastIndexOf('.', marcas[i + 1].desde) : texto.length;
    if (hasta <= marca.desde) { hasta = texto.length; }
    // Detrás del primer punto suele venir la coletilla comercial, no la lista.
    var lista = texto.slice(marca.desde, hasta).split('.')[0];

    lista.split(/\s*(?:,|\sy\s)\s*/).forEach(function (trozo) {
      var c = trozo.trim().match(/^(\d+)\s+(.*)$/);
      if (!c) { return; }               // sin cantidad delante no es una línea
      salida.push({ cant: parseInt(c[1], 10), texto: c[2].trim(), regalo: marca.regalo });
    });
  });

  return salida;
}

/** Propone la lista de contenido leyendo la frase. No guarda nada. */
function snProponerContenido(frase, catalogo) {
  var lineas = [], dudosas = [], perdidas = [];
  var peso = snPesos(catalogo);

  snTrozos(frase).forEach(function (t) {
    var mejor = null, mejorPunt = 0, segundaPunt = 0;
    catalogo.forEach(function (p) {
      var punt = snParecido(t.texto, p.nombre, peso);
      if (punt > mejorPunt) { segundaPunt = mejorPunt; mejorPunt = punt; mejor = p; }
      else if (punt > segundaPunt) { segundaPunt = punt; }
    });

    if (!mejor || mejorPunt < 0.55) { perdidas.push(t.cant + ' ' + t.texto); return; }

    // Si los dos nombres son el mismo escrito de otra forma («Carti Mix» y
    // «Cartimix»), no hay palabra que sobre por mucho que lo diga el reparto
    // en palabras sueltas.
    var mismo  = snTokens(t.texto).join('') === snTokens(mejor.nombre).join('');
    var sobran = mismo ? [] : snSobran(t.texto, mejor.nombre);
    var ta = snTamano(t.texto), tb = snTamano(mejor.nombre);
    var motivos = [];

    if (sobran.length)                  { motivos.push('el producto no dice «' + sobran.join('», «') + '»'); }
    if (ta && tb && ta !== tb)          { motivos.push('la frase pide ' + ta + ' y el producto es de ' + tb); }
    if (mejorPunt - segundaPunt < 0.06) { motivos.push('hay otro producto casi igual de parecido'); }

    if (motivos.length) {
      dudosas.push({ texto: t.cant + ' ' + t.texto, elegido: mejor.nombre, motivos: motivos });
    }
    lineas.push({ id: mejor.id, cant: t.cant, regalo: t.regalo, revisar: motivos.length > 0 });
  });

  return { lineas: lineas, dudosas: dudosas, perdidas: perdidas };
}

/** ¿Merece la pena ofrecer el botón? Solo si de la frase sale algo. */
function hayFraseQueLeer() {
  return esCombo() && !LINEAS.length && snTrozos(FRASE_ORIGINAL).length > 0;
}

function armarDesdeFrase() {
  var r = snProponerContenido(FRASE_ORIGINAL, CATALOGO);
  LINEAS = r.lineas;

  var caja = document.getElementById('informeFrase');
  caja.innerHTML = '';
  caja.hidden = false;
  document.getElementById('desdeFrase').hidden = true;

  var p = document.createElement('p');
  p.className = 'aviso ' + (r.dudosas.length || r.perdidas.length ? 'ojo' : 'ok');
  var dentro = document.createElement('span');

  var resumen = document.createElement('b');
  resumen.textContent = 'Puestos ' + r.lineas.length + ' productos desde la frase.';
  dentro.appendChild(resumen);

  if (!r.dudosas.length && !r.perdidas.length) {
    dentro.appendChild(document.createTextNode(' Todo encajó sin dudas, pero échale un ojo antes de guardar.'));
  }

  if (r.dudosas.length) {
    var t1 = document.createElement('div');
    t1.style.marginTop = '8px';
    t1.textContent = 'Revisa estos ' + r.dudosas.length + ', que no los tengo claros:';
    dentro.appendChild(t1);
    var ul = document.createElement('ul');
    ul.style.margin = '6px 0 0'; ul.style.paddingLeft = '20px';
    r.dudosas.forEach(function (d) {
      var li = document.createElement('li');
      li.textContent = '«' + d.texto + '» → ' + d.elegido + '  (' + d.motivos.join('; ') + ')';
      ul.appendChild(li);
    });
    dentro.appendChild(ul);
  }

  if (r.perdidas.length) {
    var t2 = document.createElement('div');
    t2.style.marginTop = '8px';
    t2.textContent = 'Y estos no los encontré en el catálogo; añádelos a mano si existen con otro nombre:';
    dentro.appendChild(t2);
    var ul2 = document.createElement('ul');
    ul2.style.margin = '6px 0 0'; ul2.style.paddingLeft = '20px';
    r.perdidas.forEach(function (d) {
      var li = document.createElement('li');
      li.textContent = d;
      ul2.appendChild(li);
    });
    dentro.appendChild(ul2);
  }

  var nota = document.createElement('div');
  nota.style.marginTop = '8px';
  nota.className = 'mini';
  nota.textContent = 'La frase original era: ' + FRASE_ORIGINAL;
  dentro.appendChild(nota);

  p.appendChild(dentro);
  caja.appendChild(p);

  dibujarLineas();
}

/* --------------------------------------------------------------------------
   Elegir productos: con la foto delante se reconocen mucho antes que en una
   lista de nombres largos («Caja de sachet 15u tocosh con uña de gato…»).
   -------------------------------------------------------------------------- */
function dibujarParrilla() {
  var caja  = document.getElementById('parrilla');
  var texto = (document.getElementById('buscaProd').value || '').trim().toLowerCase();
  var bajas = document.getElementById('verBajas').checked;

  var lista = CATALOGO.filter(function (p) {
    if (p.baja && !bajas) { return false; }
    return !texto || p.nombre.toLowerCase().indexOf(texto) !== -1;
  });

  caja.innerHTML = '';
  if (!lista.length) {
    var vacio = document.createElement('p');
    vacio.className = 'nada';
    vacio.textContent = texto ? 'Ningún producto se llama así.' : 'No hay productos que añadir.';
    caja.appendChild(vacio);
    return;
  }

  lista.slice(0, 120).forEach(function (p) {
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'ficha-prod';
    b.title = 'Añadir ' + p.nombre;
    b.onclick = function () { anadir(p.id); };

    if (p.img) {
      var im = document.createElement('img');
      im.src = '../' + p.img; im.alt = ''; im.loading = 'lazy';
      b.appendChild(im);
    } else {
      var hueco = document.createElement('div');
      hueco.className = 'vacia'; hueco.textContent = '🌿';
      b.appendChild(hueco);
    }

    var nom = document.createElement('b');
    nom.textContent = p.nombre;
    b.appendChild(nom);

    var pre = document.createElement('span');
    pre.textContent = 'S/ ' + p.lista.toFixed(2);
    b.appendChild(pre);

    if (p.baja) {
      var av = document.createElement('span');
      av.className = 'baja'; av.textContent = 'desactivado';
      b.appendChild(av);
    }

    caja.appendChild(b);
  });
}

function anadir(id) {
  // Si ya está como incluido, sube la cantidad en vez de repetir la línea: dos
  // líneas del mismo producto el servidor las rechaza.
  for (var i = 0; i < LINEAS.length; i++) {
    if (LINEAS[i].id === id && !LINEAS[i].regalo) {
      LINEAS[i].cant = Math.min(99, (LINEAS[i].cant || 1) + 1);
      dibujarLineas();
      return;
    }
  }
  LINEAS.push({ id: id, cant: 1, regalo: false });
  dibujarLineas();
}

/* --------------------------------------------------------------------------
   Las líneas elegidas
   --------------------------------------------------------------------------
   Se dibujan aquí y no en PHP porque se añaden y se quitan sin guardar; el
   servidor solo ve el resultado al enviar el formulario. Los campos que no se
   ven (el id y si es regalo) van en inputs ocultos para que los tres arrays
   lleguen alineados: una casilla sin marcar no se envía y descuadraría todo.
   -------------------------------------------------------------------------- */
function dibujarLineas() {
  var caja = document.getElementById('contenido');
  caja.innerHTML = '';

  LINEAS.forEach(function (linea, i) {
    var p = POR_ID[linea.id];
    var fila = document.createElement('div');
    fila.className = 'linea'
                   + (linea.regalo ? ' es-regalo' : '')
                   + (linea.revisar ? ' revisar' : '');

    var idOculto = document.createElement('input');
    idOculto.type = 'hidden'; idOculto.name = 'cont_id[]'; idOculto.value = linea.id;

    var tipoOculto = document.createElement('input');
    tipoOculto.type = 'hidden'; tipoOculto.name = 'cont_tipo[]';
    tipoOculto.value = linea.regalo ? 'regalo' : 'incluido';

    if (p && p.img) {
      var im = document.createElement('img');
      im.className = 'mini-foto'; im.src = '../' + p.img; im.alt = '';
      fila.appendChild(im);
    }

    var nom = document.createElement('div');
    nom.className = 'nom';
    nom.textContent = p ? p.nombre : 'Producto ' + linea.id + ' (ya no está en el catálogo)';
    var pie = document.createElement('span');
    pie.textContent = p ? ('S/ ' + p.lista.toFixed(2) + ' cada uno') : '';
    nom.appendChild(pie);
    fila.appendChild(nom);

    var cant = document.createElement('input');
    cant.type = 'number'; cant.name = 'cont_cant[]'; cant.min = '1'; cant.max = '99';
    cant.className = 'cant'; cant.value = linea.cant || 1; cant.title = 'Cantidad';
    cant.onchange = function () { LINEAS[i].cant = parseInt(this.value, 10) || 1; sumar(); };

    var regalo = document.createElement('label');
    regalo.className = 'regalo';
    var marca = document.createElement('input');
    marca.type = 'checkbox'; marca.checked = !!linea.regalo;
    marca.onchange = function () { LINEAS[i].regalo = this.checked; dibujarLineas(); };
    regalo.appendChild(marca);
    regalo.appendChild(document.createTextNode('De regalo'));

    // Rojo, como en el listado: quitar es la única acción de esta fila que
    // deshace algo.
    var quitar = document.createElement('button');
    quitar.type = 'button'; quitar.className = 'btn mini rojo-suave'; quitar.textContent = 'Quitar';
    quitar.onclick = function () { LINEAS.splice(i, 1); dibujarLineas(); };

    var piezas = [idOculto, tipoOculto, cant, regalo];

    // El aviso de «revisar» lo pone el emparejador de la frase; se quita al
    // tocarlo, que es la forma de ir tachando lo que ya se ha mirado.
    if (linea.revisar) {
      var aviso = document.createElement('button');
      aviso.type = 'button'; aviso.className = 'revisar-pill'; aviso.textContent = '⚑ revisar';
      aviso.title = 'Lo puse leyendo la frase y no lo tengo claro. Compruébalo y toca aquí para quitar el aviso.';
      aviso.onclick = function () { delete LINEAS[i].revisar; dibujarLineas(); };
      piezas.push(aviso);
    }

    piezas.push(quitar);
    piezas.forEach(function (e) { fila.appendChild(e); });
    caja.appendChild(fila);
  });

  sumar();
}

/* --------------------------------------------------------------------------
   Suma del contenido y frase de la ficha
   --------------------------------------------------------------------------
   Lo que llevas dentro, a precio normal, es el precio de partida del combo: si
   el combo no cuesta menos que comprarlo suelto, no hay combo que vender.
   Los regalos no suman, igual que en el cálculo del servidor.
   -------------------------------------------------------------------------- */
function sumaContenido() {
  var total = 0;
  LINEAS.forEach(function (l) {
    var p = POR_ID[l.id];
    if (!p || l.regalo) { return; }
    total += p.lista * (l.cant || 1);
  });
  return Math.round(total * 100) / 100;
}

function sumar() {
  var incluidos = [], regalos = [];
  LINEAS.forEach(function (l) {
    var p = POR_ID[l.id];
    if (!p) { return; }
    var texto = (l.cant || 1) + ' ' + p.nombre;
    if (l.regalo) { regalos.push(texto); } else { incluidos.push(texto); }
  });

  var frase = '';
  if (incluidos.length) { frase += 'Contiene: ' + incluidos.join(', ') + '.'; }
  if (regalos.length)   { frase += (frase ? ' ' : '') + 'De regalo: ' + regalos.join(', ') + '.'; }

  var campo = document.getElementById('campoDescripcion');
  var pista = document.getElementById('pistaDescripcion');

  if (frase && esCombo()) {
    // Con contenido armado manda la lista: el texto se recalcula al guardar,
    // así que dejarlo editable aquí solo serviría para perder lo escrito.
    campo.value = frase;
    campo.readOnly = true;
    pista.textContent = 'La escribe sola la lista de arriba. Para redactarla a mano, quita los productos.';
  } else {
    campo.readOnly = false;
    pista.textContent = 'Texto corto que se lee en la tarjeta del producto.';
  }

  // Si se vacía la lista, la oferta de leer la frase vuelve a tener sentido.
  document.getElementById('desdeFrase').hidden = !hayFraseQueLeer();

  var total = sumaContenido();
  var boton = document.getElementById('botonSuma');
  boton.hidden = !(esCombo() && total > 0);
  boton.textContent = 'Usar la suma (S/ ' + total.toFixed(2) + ')';

  if (esCombo() && baseAutomatico && total > 0) {
    document.getElementById('campoBase').value = total.toFixed(2);
  }

  var aviso = document.getElementById('sumaContenido');
  var pvp = num(document.getElementById('campoPvp').value);
  if (total > 0) {
    var cola = '';
    if (pvp > 0) {
      cola = pvp < total
        ? ' · el combo ahorra S/ ' + (total - pvp).toFixed(2)
        : ' · OJO: cuesta igual o más que comprarlo suelto';
    }
    aviso.textContent = 'Sumados por separado, a precio normal: S/ ' + total.toFixed(2) + cola;
  } else {
    aviso.textContent = '';
  }

  precioADcto();
}

function usarSuma() {
  var total = sumaContenido();
  if (total > 0) {
    document.getElementById('campoBase').value = total.toFixed(2);
    baseAutomatico = true;
    precioADcto();
  }
}

function baseAMano() { baseAutomatico = false; precioADcto(); }

/* --------------------------------------------------------------------------
   Precio de venta y descuento: cada uno calcula el otro
   -------------------------------------------------------------------------- */
function precioADcto() {
  var base = num(document.getElementById('campoBase').value);
  var pvp  = num(document.getElementById('campoPvp').value);
  var c    = document.getElementById('campoDcto');
  c.value = (base > 0 && pvp > 0 && base > pvp)
    ? String(Math.round((1 - pvp / base) * 1000) / 10)
    : '';
  sugerirPuntos();
}

function dctoAPrecio() {
  var base = num(document.getElementById('campoBase').value);
  var d    = num(document.getElementById('campoDcto').value);
  if (base > 0 && d > 0 && d < 100) {
    document.getElementById('campoPvp').value = (base * (1 - d / 100)).toFixed(2);
  }
  sugerirPuntos();
}

/* --------------------------------------------------------------------------
   Puntos: los escribe quien da de alta la ficha, pero teniendo delante lo que
   saldría de la regla de siempre.
   -------------------------------------------------------------------------- */
function puntosCalculados() {
  var pvp  = num(document.getElementById('campoPvp').value);
  var base = num(document.getElementById('campoBase').value);
  var ref  = esCombo() ? pvp : (base > 0 ? base : pvp);
  return Math.round((ref / 6) * 100) / 100;
}

function sugerirPuntos() {
  var v = puntosCalculados();
  document.getElementById('puntosSugeridos').textContent = v > 0 ? v.toFixed(2) : '—';
}

function usarPuntos() {
  var v = puntosCalculados();
  if (v > 0) { document.getElementById('campoPuntos').value = v.toFixed(2); }
}

/* --------------------------------------------------------------------------
   Foto
   -------------------------------------------------------------------------- */
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

dibujarParrilla();
dibujarLineas();
cambiarTipo();
</script>
<?php
sn_pie();
