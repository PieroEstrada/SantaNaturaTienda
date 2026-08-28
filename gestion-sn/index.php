<?php
/* ============================================================================
   Panel de gestión — pantalla principal
   ----------------------------------------------------------------------------
   Tres estados:
     1. No hay contraseña configurada  -> pantalla para crearla.
     2. Hay contraseña pero no sesión  -> formulario de acceso.
     3. Sesión activa                  -> listado del catálogo.
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/catalogo.php';
require __DIR__ . '/_layout.php';

sn_iniciar_sesion();

/* --------------------------------------------------------------------------
   1. Primer arranque: crear la contraseña
   -------------------------------------------------------------------------- */
if (!sn_hay_password()) {
    $hashGenerado = null;
    $error = null;

    if (($_POST['accion'] ?? '') === 'crear') {
        $p1 = (string) ($_POST['password'] ?? '');
        $p2 = (string) ($_POST['password2'] ?? '');

        if (strlen($p1) < 10) {
            $error = 'La contraseña debe tener al menos 10 caracteres. Este panel cambia los precios de una web con anuncios pagados: no la dejes corta.';
        } elseif ($p1 !== $p2) {
            $error = 'Las dos contraseñas no coinciden.';
        } else {
            $hash = password_hash($p1, PASSWORD_DEFAULT);
            $contenido = "<?php\n/* Configuración del panel. NO subir a git. */\nreturn [\n    'password_hash' => " . var_export($hash, true) . ",\n];\n";

            if (@file_put_contents(SN_CONFIG_ADMIN, $contenido) !== false) {
                header('Location: index.php');
                exit;
            }
            // Sin permisos de escritura: se muestra para pegarlo a mano.
            $hashGenerado = $contenido;
        }
    }

    sn_cabecera('Configurar acceso', false);
    ?>
    <div class="caja">
      <div class="marca">
        <div class="hoja">🌿</div>
        <div><b>Santa Natura</b><span>Panel de gestión</span></div>
      </div>

      <h2 style="margin:0 0 4px">Configura el acceso</h2>
      <p class="sub">Todavía no hay contraseña. Créala ahora: hasta entonces el panel no deja entrar a nadie.</p>

      <?php if ($error): ?><p class="aviso mal"><?= h($error) ?></p><?php endif; ?>

      <?php if ($hashGenerado !== null): ?>
        <p class="aviso ojo">No tengo permiso para escribir <code>inc/admin-config.php</code>.
        Crea ese archivo en el servidor y pega dentro exactamente esto:</p>
        <textarea readonly style="min-height:150px;font-family:ui-monospace,monospace;font-size:13px"><?= h($hashGenerado) ?></textarea>
        <p class="mini">Después recarga esta página.</p>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="accion" value="crear">
          <label>Contraseña
            <span class="pista">Mínimo 10 caracteres. Se guarda cifrada; nadie puede leerla después, así que apúntala.</span>
            <input type="password" name="password" required autofocus autocomplete="new-password">
          </label>
          <label>Repítela
            <input type="password" name="password2" required autocomplete="new-password">
          </label>
          <p style="margin:20px 0 0"><button class="btn" style="width:100%">Crear contraseña</button></p>
        </form>
      <?php endif; ?>
    </div>
    <?php
    sn_pie();
    exit;
}

/* --------------------------------------------------------------------------
   2. Acceso
   -------------------------------------------------------------------------- */
if (!sn_autenticado()) {
    $error = null;

    if (($_POST['accion'] ?? '') === 'entrar') {
        if (sn_bloqueado()) {
            $error = 'Demasiados intentos fallidos. Espera 15 minutos.';
        } elseif (sn_login((string) ($_POST['password'] ?? ''))) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Contraseña incorrecta.';
            // Retraso pequeño: encarece probar contraseñas a lo bruto.
            usleep(600000);
        }
    }

    sn_cabecera('Acceso', false);
    ?>
    <div class="caja">
      <div class="marca">
        <div class="hoja">🌿</div>
        <div><b>Santa Natura</b><span>Panel de gestión</span></div>
      </div>
      <?php if ($error): ?><p class="aviso mal"><?= h($error) ?></p><?php endif; ?>
      <form method="post">
        <input type="hidden" name="accion" value="entrar">
        <label>Contraseña
          <input type="password" name="password" required autofocus autocomplete="current-password">
        </label>
        <p style="margin:20px 0 0"><button class="btn" style="width:100%">Entrar</button></p>
      </form>
    </div>
    <?php
    sn_pie();
    exit;
}

/* --------------------------------------------------------------------------
   3. Publicar / retirar (POST) — cambia solo el campo `activo`
   -------------------------------------------------------------------------- */
if (($_POST['accion'] ?? '') === 'publicar') {
    if (!sn_csrf_valido($_POST['csrf'] ?? null)) {
        sn_flash('mal', 'La sesión caducó. Vuelve a intentarlo.');
        header('Location: index.php');
        exit;
    }

    try {
        $id  = (int) ($_POST['id'] ?? 0);
        $ver = ($_POST['ver'] ?? '') === '1';

        $productos = sn_productos();
        $encontrado = null;

        foreach ($productos as &$p) {
            if ((int) $p['id'] !== $id) {
                continue;
            }
            $encontrado = $p['producto'];
            if ($ver) {
                unset($p['activo']);          // sin el campo = se ve
            } else {
                $p['activo'] = false;
            }
            break;
        }
        unset($p);

        if ($encontrado === null) {
            throw new RuntimeException('No existe el producto ' . $id . '.');
        }

        sn_guardar($productos);
        sn_flash('ok', ($ver ? 'Activado: ' : 'Desactivado: ') . $encontrado);
    } catch (Throwable $e) {
        sn_flash('mal', $e->getMessage());
    }

    header('Location: index.php' . (isset($_POST['q']) && $_POST['q'] !== '' ? '?q=' . urlencode((string) $_POST['q']) : ''));
    exit;
}

/* --------------------------------------------------------------------------
   4. Listado
   -------------------------------------------------------------------------- */
$productos = sn_productos();
$q = trim((string) ($_GET['q'] ?? ''));
$filtro = (string) ($_GET['f'] ?? '');

$vista = array_filter($productos, static function (array $p) use ($q, $filtro): bool {
    $publicado = ($p['activo'] ?? true) !== false;
    if ($filtro === 'on' && !$publicado) return false;
    if ($filtro === 'off' && $publicado) return false;
    if ($filtro === 'packs' && !in_array('Packs', $p['categorias'] ?? [], true)) return false;
    if ($filtro === 'sinfoto' && ($p['imagen'] ?? '') !== '') return false;

    if ($q === '') return true;
    $heno = mb_strtolower($p['producto'] . ' ' . implode(' ', $p['categorias'] ?? []));
    return str_contains($heno, mb_strtolower($q));
});

$total      = count($productos);
$publicados = count(array_filter($productos, static fn($p) => ($p['activo'] ?? true) !== false));
$sinFoto    = count(array_filter($productos, static fn($p) => ($p['activo'] ?? true) !== false && ($p['imagen'] ?? '') === ''));

$packs = count(array_filter($productos, static fn($p) => in_array('Packs', $p['categorias'] ?? [], true)));

sn_definir_acciones(static function (): void { ?>
  <a class="btn" href="producto.php">＋ Nuevo producto</a>
<?php });

sn_cabecera('Catálogo', true, 'Precios, fotos y contenido de todo lo que se vende.');
sn_mensaje_flash();
?>
<div class="datos">
  <div class="dato"><b><?= $publicados ?></b><span>Activos en la web</span></div>
  <div class="dato"><b><?= $total - $publicados ?></b><span>Desactivados</span></div>
  <div class="dato"><b><?= $packs ?></b><span>Packs</span></div>
  <div class="dato <?= $sinFoto > 0 ? 'ojo' : '' ?>"><b><?= $sinFoto ?></b><span>Activos sin foto</span></div>
</div>

<?php if ($sinFoto > 0): ?>
  <p class="aviso ojo">
    <span>Hay <?= $sinFoto ?> productos activos <strong>sin foto</strong>: en la web salen con el icono
    genérico de la marca. <a href="?f=sinfoto">Verlos</a>.</span>
  </p>
<?php endif; ?>

<form class="barra" method="get">
  <input type="search" name="q" value="<?= h($q) ?>" placeholder="Buscar por nombre o categoría…">
  <select name="f" onchange="this.form.submit()">
    <option value="">Todos</option>
    <option value="on"      <?= $filtro === 'on' ? 'selected' : '' ?>>Solo activos</option>
    <option value="off"     <?= $filtro === 'off' ? 'selected' : '' ?>>Solo desactivados</option>
    <option value="packs"   <?= $filtro === 'packs' ? 'selected' : '' ?>>Solo packs</option>
    <option value="sinfoto" <?= $filtro === 'sinfoto' ? 'selected' : '' ?>>Sin foto</option>
  </select>
  <button class="btn gris">Filtrar</button>
  <span class="mini derecha"><?= count($vista) ?> de <?= $total ?></span>
</form>

<div class="tabla">
<table>
<thead><tr>
  <th></th><th>Producto</th><th class="num">Precio</th><th class="num">Antes</th>
  <th class="num">Dcto.</th><th class="num">Puntos</th><th>Estado</th><th></th>
</tr></thead>
<tbody>
<?php foreach ($vista as $p):
    $publicado = ($p['activo'] ?? true) !== false; ?>
  <tr class="<?= $publicado ? '' : 'off' ?>">
    <td>
      <?php if (($p['imagen'] ?? '') !== ''): ?>
        <img class="foto" src="../<?= h($p['imagen']) ?>" alt="" loading="lazy">
      <?php else: ?>
        <div class="sinfoto" title="Sin foto">·</div>
      <?php endif; ?>
    </td>
    <td>
      <a class="nombre" href="producto.php?id=<?= (int) $p['id'] ?>"><?= h($p['producto']) ?></a>
      <div class="mini">
        <?= h(implode(' · ', array_slice($p['categorias'] ?? [], 0, 4))) ?>
        <?php if (!empty($p['contiene'])): ?>
          · <?= count($p['contiene']) ?> productos dentro
        <?php endif; ?>
      </div>
    </td>
    <td class="num" data-th="Precio">S/ <?= number_format((float) $p['pvp'], 2) ?></td>
    <td class="num" data-th="Antes"><?= isset($p['precio_original']) ? 'S/ ' . number_format((float) $p['precio_original'], 2) : '—' ?></td>
    <td class="num" data-th="Descuento"><?= h($p['etiqueta_descuento'] ?? '—') ?></td>
    <td class="num" data-th="Puntos"><?= number_format((float) $p['puntos'], 2) ?></td>
    <td>
      <span class="pill <?= $publicado ? '' : 'off' ?>"><?= $publicado ? 'Activo' : 'Desactivado' ?></span>
    </td>
    <td>
      <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-top:6px">
        <a class="btn mini" href="producto.php?id=<?= (int) $p['id'] ?>">Editar</a>
        <form method="post" style="margin:0">
          <input type="hidden" name="accion" value="publicar">
          <input type="hidden" name="csrf" value="<?= h(sn_csrf()) ?>">
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <input type="hidden" name="q" value="<?= h($q) ?>">
          <input type="hidden" name="ver" value="<?= $publicado ? '0' : '1' ?>">
          <button class="btn mini gris">
            <?= $publicado ? 'Desactivar' : 'Activar' ?>
          </button>
        </form>
      </div>
    </td>
  </tr>
<?php endforeach; ?>
<?php if (!$vista): ?>
  <tr><td colspan="8" class="mini" style="padding:26px;text-align:center">Ningún producto coincide con la búsqueda.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<p class="mini" style="margin-top:16px">
  Antes de cada cambio se archiva una copia del catálogo en <code>inc/copias/</code>.
</p>
<?php
sn_pie();
