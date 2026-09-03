<?php
/* ============================================================================
   Panel de gestión — aviso emergente (promos flash)
   ----------------------------------------------------------------------------
   Una sola pantalla para encender, escribir y programar el cartel que sale al
   entrar en la web. Lo que se guarda aquí se ve en la web al recargar: no hay
   que regenerar nada.
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/popup.php';
require __DIR__ . '/_layout.php';

sn_exigir_sesion();

$config  = sn_popup_config();
$errores = [];

/* --------------------------------------------------------------------------
   Guardar
   -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sn_csrf_valido($_POST['csrf'] ?? null)) {
        $errores[] = 'La sesión caducó. Vuelve a enviar el formulario.';
    }

    $nuevo = [
        'activo'       => ($_POST['activo'] ?? '') === '1',
        'titulo'       => trim((string) ($_POST['titulo'] ?? '')),
        'texto'        => trim((string) ($_POST['texto'] ?? '')),
        'imagen'       => trim((string) ($_POST['imagen'] ?? '')),
        'boton_texto'  => trim((string) ($_POST['boton_texto'] ?? '')),
        'boton_enlace' => trim((string) ($_POST['boton_enlace'] ?? '')),
        'desde'        => trim((string) ($_POST['desde'] ?? '')),
        'hasta'        => trim((string) ($_POST['hasta'] ?? '')),
        'frecuencia'   => (string) ($_POST['frecuencia'] ?? 'dia'),
        'retraso'      => (int) ($_POST['retraso'] ?? 3),
        'en_landings'  => ($_POST['en_landings'] ?? '') === '1',
    ];

    // Una foto nueva para el cartel se sube aquí mismo, igual que en las fichas.
    $subida = $_FILES['foto'] ?? null;
    if (is_array($subida) && (int) ($subida['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $nuevo['imagen'] = sn_subir_imagen($subida, $nuevo['titulo'] !== '' ? $nuevo['titulo'] : 'aviso');
        } catch (Throwable $e) {
            $errores[] = $e->getMessage();
        }
    } elseif ($nuevo['imagen'] !== '' && !sn_imagen_valida($nuevo['imagen'])) {
        $errores[] = 'La imagen «' . $nuevo['imagen'] . '» no está en la carpeta img/. '
                   . 'Elígela del desplegable o sube una nueva.';
    }

    /* El enlace del botón se escribe a mano y acaba en un href de la web
       pública. Sin filtrar el esquema, un «javascript:…» pegado ahí por
       descuido (o copiado de cualquier sitio) se ejecutaría en el navegador de
       cada visitante. Solo pasan los enlaces que tienen sentido en un cartel:
       una dirección web, un WhatsApp, un correo o un teléfono. */
    $enlace = $nuevo['boton_enlace'];
    if ($enlace !== '' && !preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $enlace)) {
        $errores[] = 'El enlace del botón tiene que empezar por https:// (o mailto:, tel:, / o #). '
                   . 'Lo que hay escrito ahora no es una dirección válida.';
    }

    if ($nuevo['activo'] && $nuevo['titulo'] === '' && $nuevo['texto'] === '') {
        $errores[] = 'Escribe al menos un título o un texto: un cartel vacío tapa la web sin decir nada.';
    }
    foreach (['desde' => 'inicial', 'hasta' => 'final'] as $campo => $cual) {
        $v = $nuevo[$campo];
        if ($v !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            $errores[] = 'La fecha ' . $cual . ' no tiene el formato AAAA-MM-DD.';
        }
    }
    if ($nuevo['desde'] !== '' && $nuevo['hasta'] !== '' && $nuevo['hasta'] < $nuevo['desde']) {
        $errores[] = 'La fecha final es anterior a la inicial: así el aviso no saldría ningún día.';
    }
    if (!in_array($nuevo['frecuencia'], ['siempre', 'dia', 'una'], true)) {
        $errores[] = 'Esa frecuencia no existe.';
    }
    if ($nuevo['retraso'] < 0 || $nuevo['retraso'] > 60) {
        $errores[] = 'El retraso tiene que estar entre 0 y 60 segundos.';
    }
    if ($nuevo['boton_enlace'] !== '' && !preg_match('#^(https?://|/|\#)#i', $nuevo['boton_enlace'])) {
        $errores[] = 'El enlace del botón debe empezar por https://, por / o por #.';
    }

    if (!$errores) {
        try {
            sn_popup_guardar($nuevo);
            $vigente = sn_popup_vigente($nuevo);
            sn_flash('ok', $nuevo['activo']
                ? ($vigente
                    ? 'Guardado. El aviso ya se está mostrando en la web.'
                    : 'Guardado y encendido, pero hoy no sale: está fuera de las fechas que pusiste.')
                : 'Guardado. El aviso está apagado: no lo ve nadie.');
            header('Location: popup.php');
            exit;
        } catch (Throwable $e) {
            $errores[] = $e->getMessage();
        }
    }

    $config = $nuevo;      // repinta con lo que se escribió
}

/* --------------------------------------------------------------------------
   Formulario
   -------------------------------------------------------------------------- */
$imagenes = sn_imagenes();
$vigente  = sn_popup_vigente($config);

sn_definir_acciones(static function (): void { ?>
  <a class="btn gris" href="../index.php" target="_blank" rel="noopener">Ver la web</a>
<?php });

sn_cabecera('Aviso emergente', true, 'El cartel de promociones que sale al entrar en la web.');
sn_mensaje_flash();
?>

<?php foreach ($errores as $e): ?>
  <p class="aviso mal"><?= h($e) ?></p>
<?php endforeach; ?>

<p class="aviso <?= $vigente ? 'ok' : 'ojo' ?>">
  <span>
    <?php if ($vigente): ?>
      <strong>Ahora mismo se está mostrando.</strong>
      Sale en la portada<?= !empty($config['en_landings']) ? ' y en las landings de anuncios' : '' ?>,
      <?= (int) $config['retraso'] ?> segundos después de cargar la página.
    <?php elseif (!empty($config['activo'])): ?>
      <strong>Encendido, pero hoy no sale.</strong>
      Está fuera del rango de fechas configurado.
    <?php else: ?>
      <strong>Apagado.</strong> Ningún visitante lo ve. Enciéndelo abajo cuando la promo esté lista.
    <?php endif; ?>
  </span>
</p>

<form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:22px;align-items:start">
<input type="hidden" name="csrf" value="<?= h(sn_csrf()) ?>">

<div class="caja">

<label class="palanca" style="margin-top:0">
  <input type="checkbox" name="activo" value="1" <?= !empty($config['activo']) ? 'checked' : '' ?>>
  <span class="via"></span>
  <span>Mostrar el aviso en la web</span>
</label>

<h2>Qué dice</h2>

<label>Título
  <span class="pista">Corto y con la promesa concreta. Es lo primero y a veces lo único que se lee.</span>
  <input name="titulo" id="cTitulo" maxlength="80" value="<?= h((string) $config['titulo']) ?>"
         placeholder="Ej.: 30 % en todos los packs">
</label>

<label>Texto
  <span class="pista">Una o dos frases: qué incluye y hasta cuándo. Los saltos de línea se respetan.</span>
  <textarea name="texto" id="cTexto" maxlength="400" placeholder="Ej.: Solo hasta el domingo. Envíos a todo el Perú y recojo en tienda."><?= h((string) $config['texto']) ?></textarea>
</label>

<div class="rejilla">
  <label>Texto del botón
    <input name="boton_texto" maxlength="40" value="<?= h((string) $config['boton_texto']) ?>">
  </label>

  <label>Enlace del botón
    <span class="pista">Vacío = abre WhatsApp con el mensaje de siempre.</span>
    <input name="boton_enlace" value="<?= h((string) $config['boton_enlace']) ?>"
           placeholder="Vacío, /packs/ o https://…">
  </label>
</div>

<h2>Imagen</h2>

<div class="rejilla">
  <label>De las que ya están subidas
    <select name="imagen" id="cImagen" onchange="pintar()">
      <option value="">— sin imagen —</option>
      <?php foreach ($imagenes as $img): ?>
        <option value="<?= h($img) ?>" <?= $config['imagen'] === $img ? 'selected' : '' ?>><?= h(basename($img)) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>…o sube una nueva
    <span class="pista">JPG, PNG o WEBP, hasta <?= (int) (SN_FOTO_MAX / 1024 / 1024) ?> MB. Apaisada se ve mejor.</span>
    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
  </label>
</div>

<h2>Cuándo sale</h2>

<div class="rejilla">
  <label>Desde <span style="font-weight:400">— opcional</span>
    <span class="pista">Vacío = desde ya.</span>
    <input type="date" name="desde" value="<?= h((string) $config['desde']) ?>">
  </label>

  <label>Hasta <span style="font-weight:400">— opcional</span>
    <span class="pista">Vacío = sin caducidad. Ese día todavía se ve.</span>
    <input type="date" name="hasta" value="<?= h((string) $config['hasta']) ?>">
  </label>

  <label>Cada cuánto se le enseña a la misma persona
    <select name="frecuencia">
      <?php foreach ([
          'dia'     => 'Una vez al día (recomendado)',
          'una'     => 'Una sola vez, y no vuelve',
          'siempre' => 'En cada visita',
      ] as $clave => $texto): ?>
        <option value="<?= $clave ?>" <?= $config['frecuencia'] === $clave ? 'selected' : '' ?>><?= $texto ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Segundos antes de aparecer
    <span class="pista">2 o 3 va bien: da tiempo a ver la página antes de interrumpir.</span>
    <input type="number" name="retraso" min="0" max="60" value="<?= (int) $config['retraso'] ?>">
  </label>
</div>

<label class="palanca">
  <input type="checkbox" name="en_landings" value="1" <?= !empty($config['en_landings']) ? 'checked' : '' ?>>
  <span class="via"></span>
  <span>Mostrarlo también en las landings de anuncios</span>
</label>
<p class="mini" style="margin:6px 0 0">
  Son <code>/packs</code> y <code>/packs/colageno</code>. Déjalo apagado salvo que sepas lo que haces:
  tapar la oferta a quien acaba de llegar de un anuncio pagado baja la conversión, y Google penaliza
  los carteles que cubren el contenido nada más entrar.
</p>

<p style="margin:26px 0 0;padding-top:18px;border-top:1px solid var(--borde-suave);display:flex;gap:10px;flex-wrap:wrap">
  <button class="btn">Guardar</button>
  <a class="btn gris" href="index.php">Cancelar</a>
</p>

</div>

<!-- Vista previa: no es la web real, pero enseña el orden y la longitud del
     texto, que es donde se ve si el cartel dice algo o solo ocupa. -->
<div class="caja" style="position:sticky;top:96px">
  <h2 style="margin-top:0">Vista previa</h2>
  <div style="background:#101a14;border-radius:14px;padding:22px;display:grid;place-items:center">
    <div style="background:#fff;color:#16211b;border-radius:14px;overflow:hidden;width:100%;max-width:250px;box-shadow:0 12px 30px -10px rgba(0,0,0,.5)">
      <img id="vImagen" alt="" style="width:100%;height:96px;object-fit:cover;display:none">
      <div style="padding:16px;text-align:center">
        <div id="vTitulo" style="font-weight:700;font-size:15px;color:#1b5e3f;line-height:1.25"></div>
        <div id="vTexto" style="font-size:12.5px;color:#5f6d65;margin-top:6px;line-height:1.45"></div>
        <div id="vBoton" style="margin-top:12px;background:#25D366;color:#fff;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:999px;display:inline-block"></div>
      </div>
    </div>
  </div>
  <p class="mini" style="margin:12px 0 0">
    Se actualiza mientras escribes. Para verlo de verdad, guarda y abre la web en
    una ventana privada: en la tuya puede constar como ya cerrado.
  </p>
</div>
</form>

<script>
function pintar() {
  var t  = document.getElementById('cTitulo').value.trim();
  var x  = document.getElementById('cTexto').value.trim();
  var im = document.getElementById('cImagen').value;
  var b  = document.getElementsByName('boton_texto')[0].value.trim();

  document.getElementById('vTitulo').textContent = t;
  document.getElementById('vTexto').textContent  = x;
  document.getElementById('vBoton').textContent  = b;
  document.getElementById('vBoton').style.display = b ? 'inline-block' : 'none';

  var img = document.getElementById('vImagen');
  if (im) { img.src = '../' + im; img.style.display = 'block'; } else { img.style.display = 'none'; }
}

['cTitulo', 'cTexto'].forEach(function (id) {
  document.getElementById(id).addEventListener('input', pintar);
});
document.getElementsByName('boton_texto')[0].addEventListener('input', pintar);
pintar();
</script>

<style>
  @media (max-width:1000px) { form[enctype] { grid-template-columns:1fr !important; } }
</style>
<?php
sn_pie();
