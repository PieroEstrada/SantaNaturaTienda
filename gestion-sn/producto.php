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

$p = null;
foreach ($productos as $x) {
    if ((int) $x['id'] === $id) { $p = $x; break; }
}
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
    if ($imagen !== '' && !is_file(SN_RAIZ . '/' . $imagen)) {
        $errores[] = 'La imagen «' . $imagen . '» no existe en la carpeta img/.';
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
                   . ($publicado ? '' : ' (queda retirado, no se ve en la web)'));
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
    ];
}

/* --------------------------------------------------------------------------
   Formulario
   -------------------------------------------------------------------------- */
$val       = static fn(string $k, $def = '') => $p[$k] ?? $def;
$catsAct   = (array) ($p['categorias'] ?? ['Packs']);
$publicado = $p === null ? true : (($p['activo'] ?? true) !== false);
$imagenes  = sn_imagenes();

sn_cabecera($esNuevo ? 'Nuevo producto' : 'Editar producto');
?>
<h1><?= $esNuevo ? 'Nuevo producto' : 'Editar: ' . h((string) $val('producto')) ?></h1>
<p class="sub">
  Los puntos, el porcentaje de descuento y el precio por cantidad se calculan solos
  desde los precios que escribas. No hay que rellenarlos a mano.
</p>

<?php foreach ($errores as $e): ?>
  <p class="aviso mal"><?= h($e) ?></p>
<?php endforeach; ?>

<form method="post" class="caja">
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

<p style="margin:10px 0 0"><img id="vistaFoto" class="foto" style="width:88px;height:88px" alt=""
   src="<?= $val('imagen') ? '../' . h((string) $val('imagen')) : '' ?>"
   <?= $val('imagen') ? '' : 'hidden' ?>></p>

<label>Contenido / descripción
  <span class="pista">En los packs, empieza por «Contiene: …». Es lo que se lee en la tarjeta.</span>
  <textarea name="descripcion"><?= h((string) $val('descripcion')) ?></textarea>
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

<label style="margin-top:20px">
  <span style="display:inline-flex;align-items:center;gap:8px;font-weight:600">
    <input type="checkbox" name="publicado" value="1" style="width:auto" <?= $publicado ? 'checked' : '' ?>>
    Visible en la web
  </span>
  <span class="pista">Desmárcalo para guardarlo sin publicarlo (por ejemplo, mientras consigues la foto).</span>
</label>

<p style="margin:22px 0 0;display:flex;gap:10px;flex-wrap:wrap">
  <button class="btn"><?= $esNuevo ? 'Crear producto' : 'Guardar cambios' ?></button>
  <a class="btn gris" href="index.php">Cancelar</a>
</p>
</form>

<?php if (!$esNuevo): ?>
<p class="mini" style="margin-top:14px">
  Cada vez que guardas se archiva una copia del catálogo anterior en <code>inc/copias/</code>.
</p>
<?php endif; ?>

<script>
function verFoto() {
  var s = document.getElementById('selImagen'), v = document.getElementById('vistaFoto');
  if (s.value) { v.src = '../' + s.value; v.hidden = false; } else { v.hidden = true; }
}
</script>
<?php
sn_pie();
