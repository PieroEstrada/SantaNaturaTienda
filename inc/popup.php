<?php
/* ============================================================================
   Santa Natura — Aviso emergente (promos flash)
   ----------------------------------------------------------------------------
   Un cartel que aparece al entrar en la web para anunciar algo que dura poco:
   un 2x1 del fin de semana, un envío gratis, un pack nuevo. Se configura entero
   desde el panel (/gestion-sn/popup.php) y se enciende y apaga con un
   interruptor, sin tocar código ni subir nada por FTP.

   POR QUÉ UN ARCHIVO Y NO UNA BASE DE DATOS
   Es un solo registro que se lee una vez por visita y se escribe cada varios
   días. Un JSON en disco se resuelve en microsegundos; una consulta a MySQL
   costaría más que todo lo que ahorra.

   Vive en inc/, que el .htaccess no sirve por web: nadie puede pedir el JSON
   directamente ni ver una promo antes de que se publique.

   CUIDADO CON LAS LANDINGS DE ADS
   En /packs y /packs/colageno el aviso viene apagado por defecto. Un cartel que
   tapa la oferta justo cuando alguien acaba de llegar de un anuncio pagado baja
   la conversión, y los intersticiales que cubren el contenido nada más entrar
   son de lo poco que Google penaliza de forma explícita. Se puede encender,
   pero es una decisión, no el ajuste por defecto.
   ========================================================================== */

declare(strict_types=1);
// render.php trae sn_e() y sn_enlace_whatsapp(), y a su vez el catálogo.
require_once __DIR__ . '/render.php';

const SN_POPUP_ARCHIVO = __DIR__ . '/popup.json';

/** Cómo se reparte por defecto. Todo apagado: nunca sale sin quererlo. */
const SN_POPUP_BASE = [
    'activo'       => false,
    'titulo'       => '',
    'texto'        => '',
    'imagen'       => '',
    'boton_texto'  => 'Pedir por WhatsApp',
    'boton_enlace' => '',       // vacío = WhatsApp con el mensaje de la página
    'desde'        => '',       // 'AAAA-MM-DD' o vacío
    'hasta'        => '',
    'frecuencia'   => 'dia',    // siempre | dia | una
    'retraso'      => 3,        // segundos desde que carga la página
    'en_landings'  => false,
];

function sn_popup_config(): array
{
    if (!is_file(SN_POPUP_ARCHIVO)) {
        return SN_POPUP_BASE;
    }
    $datos = json_decode((string) @file_get_contents(SN_POPUP_ARCHIVO), true);
    return is_array($datos) ? array_merge(SN_POPUP_BASE, $datos) : SN_POPUP_BASE;
}

/** Guarda la configuración. Escritura atómica: nunca se lee un JSON a medias. */
function sn_popup_guardar(array $config): void
{
    $limpio = [];
    foreach (SN_POPUP_BASE as $clave => $porDefecto) {
        $limpio[$clave] = $config[$clave] ?? $porDefecto;
    }

    $tmp = SN_POPUP_ARCHIVO . '.tmp';
    $json = json_encode($limpio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($json === false || @file_put_contents($tmp, $json, LOCK_EX) === false) {
        throw new RuntimeException('No puedo escribir inc/popup.json. Revisa los permisos de la carpeta inc/.');
    }
    if (!@rename($tmp, SN_POPUP_ARCHIVO)) {
        @unlink($tmp);
        throw new RuntimeException('No pude reemplazar inc/popup.json.');
    }
}

/**
 * ¿Toca enseñarlo hoy? Comprueba el interruptor, las fechas y que haya algo
 * que decir. Las fechas son inclusivas por los dos lados: «hasta el día 31»
 * significa que el 31 todavía se ve.
 */
function sn_popup_vigente(array $config, ?string $hoy = null): bool
{
    $hoy = $hoy ?? date('Y-m-d');

    if (empty($config['activo'])) {
        return false;
    }
    if (trim((string) $config['titulo']) === '' && trim((string) $config['texto']) === '') {
        return false;      // sin texto no hay nada que enseñar
    }
    if (($config['desde'] ?? '') !== '' && $hoy < $config['desde']) {
        return false;
    }
    if (($config['hasta'] ?? '') !== '' && $hoy > $config['hasta']) {
        return false;
    }
    return true;
}

/**
 * Huella del contenido. La usa el navegador como clave al recordar que ya se
 * cerró: si cambias la promo, la huella cambia y el aviso vuelve a salir
 * aunque el visitante hubiera cerrado el anterior. Sin esto, quien cerró el
 * cartel de agosto no vería nunca el de septiembre.
 */
function sn_popup_huella(array $config): string
{
    return substr(sha1((string) json_encode([
        $config['titulo'], $config['texto'], $config['imagen'],
        $config['boton_texto'], $config['boton_enlace'], $config['desde'], $config['hasta'],
    ])), 0, 10);
}

/**
 * Escribe el aviso en la página. No imprime nada si no toca.
 *
 * @param string $raiz      Prefijo hasta la raíz del sitio ('' o '../').
 * @param bool   $esLanding true en /packs y /packs/colageno.
 */
function sn_popup(string $raiz = '', bool $esLanding = false): void
{
    $c = sn_popup_config();

    if (!sn_popup_vigente($c) || ($esLanding && empty($c['en_landings']))) {
        return;
    }

    $enlace = trim((string) $c['boton_enlace']);
    if ($enlace === '' && function_exists('sn_enlace_whatsapp')) {
        $enlace = sn_enlace_whatsapp('Hola, vi la promoción en la web y quiero más información.');
    }

    $huella  = sn_popup_huella($c);
    $retraso = max(0, min(60, (int) $c['retraso'])) * 1000;
    $imagen  = (string) $c['imagen'];
    ?>
<!-- Aviso emergente configurado desde el panel. `hidden` hasta que el script
     decide que toca: así no parpadea si el visitante ya lo cerró. -->
<div id="sn-aviso" hidden
     class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-md"
     role="dialog" aria-modal="true" aria-labelledby="sn-aviso-titulo">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" data-cerrar></div>

  <div class="relative w-full sm:max-w-md bg-surface text-on-surface rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden">
    <button type="button" data-cerrar aria-label="Cerrar"
            class="absolute top-2 right-2 z-10 w-9 h-9 grid place-items-center rounded-full bg-surface/90 text-on-surface-variant hover:bg-surface-container-high transition-colors">
      <span class="material-symbols-outlined text-xl">close</span>
    </button>

    <?php if ($imagen !== ''): ?>
      <img src="<?= sn_e($raiz . $imagen) ?>" alt="" class="w-full max-h-56 object-cover">
    <?php endif; ?>

    <div class="p-lg space-y-sm text-center">
      <?php if (trim((string) $c['titulo']) !== ''): ?>
        <h2 id="sn-aviso-titulo" class="font-headline-md text-xl md:text-2xl text-primary leading-tight">
          <?= sn_e($c['titulo']) ?>
        </h2>
      <?php endif; ?>

      <?php if (trim((string) $c['texto']) !== ''): ?>
        <p class="font-body-md text-on-surface-variant"><?= nl2br(sn_e($c['texto'])) ?></p>
      <?php endif; ?>

      <?php if ($enlace !== '' && trim((string) $c['boton_texto']) !== ''): ?>
        <div class="pt-xs">
          <a href="<?= sn_e($enlace) ?>" target="_blank" rel="noopener" data-cerrar
             class="inline-flex items-center justify-center gap-2 bg-action-whatsapp text-white px-lg py-3 rounded-full font-title-sm shadow-lg shadow-action-whatsapp/25 hover:brightness-105 transition-all active:scale-[0.98]">
            <svg class="w-5 h-5 shrink-0" aria-hidden="true"><use href="#ico-whatsapp"></use></svg>
            <?= sn_e($c['boton_texto']) ?>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function () {
  var caja  = document.getElementById('sn-aviso');
  var clave = 'sn_aviso_<?= $huella ?>';
  var modo  = '<?= sn_e((string) $c['frecuencia']) ?>';

  // Recordar que ya se cerró es opcional: en modo «siempre» no se guarda nada,
  // así que tampoco se pide permiso de almacenamiento a quien solo mira.
  function visto() {
    if (modo === 'siempre') return false;
    try {
      var v = localStorage.getItem(clave);
      if (!v) return false;
      if (modo === 'una') return true;
      return v === new Date().toISOString().slice(0, 10);   // ya salió hoy
    } catch (e) { return false; }
  }

  function recordar() {
    if (modo === 'siempre') return;
    try { localStorage.setItem(clave, new Date().toISOString().slice(0, 10)); } catch (e) {}
  }

  function cerrar() {
    caja.hidden = true;
    document.documentElement.style.overflow = '';
    recordar();
  }

  if (visto()) { caja.remove(); return; }

  setTimeout(function () {
    caja.hidden = false;
    // Se bloquea el fondo mientras está abierto: si no, al arrastrar en el
    // móvil se mueve la página de detrás y el cartel parece colgado.
    document.documentElement.style.overflow = 'hidden';
  }, <?= $retraso ?>);

  caja.addEventListener('click', function (e) {
    if (e.target.closest('[data-cerrar]')) { cerrar(); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !caja.hidden) { cerrar(); }
  });
})();
</script>
<?php
}
