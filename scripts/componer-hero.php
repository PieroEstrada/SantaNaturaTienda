<?php
/**
 * Genera img/hero-portada.jpg, la foto grande de la portada.
 * ===========================================================================
 *
 * POR QUÉ EXISTE ESTE SCRIPT
 * Hasta septiembre de 2026 el hero era una imagen de la maqueta de Stitch
 * servida desde lh3.googleusercontent.com: un servidor ajeno que puede dejar
 * de responder cualquier día, a 512x279 (poca resolución para una portada) y
 * justo el elemento que Google mide como LCP. Además era una ilustración
 * generada por IA: al ampliarla, las etiquetas de los frascos salían
 * deformadas y con faltas de ortografía.
 *
 * Aquí se arma una foto propia con las fotos REALES de los productos, que la
 * tienda oficial publica a resolución completa (1080-1600 px). El resultado
 * son 1600x1280 px, la proporción 5:4 del marco del hero en escritorio.
 *
 * CÓMO SE USA
 *     C:/xampp/php/php.exe -d extension=gd scripts/componer-hero.php
 *
 * La primera vez descarga las fotos de santanatura.com.pe y las deja en
 * scripts/.hero-fuentes/ (está en .gitignore); las siguientes las reutiliza.
 * Con --refrescar vuelve a descargarlas.
 *
 * OJO: XAMPP trae GD apagado en el php.ini de consola, de ahí el
 * "-d extension=gd" de la línea de arriba.
 *
 * PARA CAMBIAR LA COMPOSICIÓN: toca $PIEZAS. Las piezas NO pueden solaparse:
 * la mezcla es "multiplicar", que no tapa lo que hay debajo, así que dos
 * productos encimados se transparentarían el uno al otro.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!function_exists('imagecreatetruecolor')) {
    fwrite(STDERR, "Falta la extensión GD. Llama al script así:\n"
        . "  C:/xampp/php/php.exe -d extension=gd scripts/componer-hero.php\n");
    exit(1);
}

$RAIZ    = dirname(__DIR__);
$FUENTES = __DIR__ . '/.hero-fuentes/';
$SALIDA  = $RAIZ . '/img/hero-portada.jpg';
$ANCHO   = 1600;
$ALTO    = 1280;
$REFRESCAR = in_array('--refrescar', $argv, true);

/* Fotos originales de la tienda oficial (las mismas de las que salen las
   miniaturas de img/, pero sin recortar a 800x800). */
$FUENTE_URL = [
    'aloe-vera-3.jpg'      => 'https://santanatura.com.pe/wp-content/uploads/2021/10/aloe-vera-3.jpg',
    'enfok-frontal-1.jpg'  => 'https://santanatura.com.pe/wp-content/uploads/2026/04/enfok-frontal-1.jpg',
    'una-de-gato-1.jpg'    => 'https://santanatura.com.pe/wp-content/uploads/2021/10/una-de-gato-1.jpg',
    'toxizero-3.jpg'       => 'https://santanatura.com.pe/wp-content/uploads/2021/10/toxizero-3.jpg',
    'productossn-1.jpg'    => 'https://santanatura.com.pe/wp-content/uploads/2021/10/productossn-1.jpg',
    'chancapiedra.jpg'     => 'https://santanatura.com.pe/wp-content/uploads/2021/10/chancapiedra.jpg',
];

/* archivo, alto en px, centro X, base Y (donde se apoya), velo (0-1 de blanco,
   para alejar las de atrás). El orden del array es el orden de dibujo. */
$PIEZAS = [
    // Fila de atrás: más pequeñas y ligeramente aclaradas.
    ['aloe-vera-3.jpg',      420,  300,  570, 0.12],
    ['enfok-frontal-1.jpg',  400,  800,  570, 0.12],
    ['una-de-gato-1.jpg',    420, 1300,  570, 0.12],

    // Fila de delante, apoyadas todas en la misma línea.
    ['toxizero-3.jpg',       340,  300, 1190, 0.00],
    ['productossn-1.jpg',    560,  800, 1190, 0.00],
    ['chancapiedra.jpg',     520, 1300, 1190, 0.00],
];

/* --------------------------------------------------------------------------
   0. Fotos de origen
   -------------------------------------------------------------------------- */
if (!is_dir($FUENTES)) mkdir($FUENTES, 0777, true);

foreach ($FUENTE_URL as $archivo => $url) {
    $ruta = $FUENTES . $archivo;
    if (is_file($ruta) && !$REFRESCAR) continue;
    echo "descargando $archivo… ";
    $datos = @file_get_contents($url, false, stream_context_create([
        'http' => ['timeout' => 60, 'user_agent' => 'SantaNaturaTienda/1.0'],
    ]));
    if ($datos === false || strlen($datos) < 10000) {
        fwrite(STDERR, "\nNo se pudo descargar $url\n");
        exit(1);
    }
    file_put_contents($ruta, $datos);
    echo round(strlen($datos) / 1024) . " KB\n";
}

/* --------------------------------------------------------------------------
   1. Fondo: degradado radial suave, del blanco del centro al verde claro de
      los bordes (los tonos de surface-container del tema).
   -------------------------------------------------------------------------- */
$lienzo = imagecreatetruecolor($ANCHO, $ALTO);
$cx = $ANCHO * 0.50;
$cy = $ALTO * 0.46;
$rmax = sqrt($cx * $cx + $cy * $cy);

for ($y = 0; $y < $ALTO; $y++) {
    for ($x = 0; $x < $ANCHO; $x++) {
        $d = sqrt(($x - $cx) ** 2 + ($y - $cy) ** 2) / $rmax;   // 0 centro, 1 esquina
        $t = min(1.0, $d * 1.15);
        $t = $t * $t;                                            // caída suave
        imagesetpixel($lienzo, $x, $y, imagecolorallocate(
            $lienzo,
            (int) round(255 - $t * 46),
            (int) round(255 - $t * 25),
            (int) round(255 - $t * 44)
        ));
    }
}

/* --------------------------------------------------------------------------
   2. Utilidades
   -------------------------------------------------------------------------- */

/** Carga JPG o PNG; el PNG se aplana sobre blanco. */
function cargar(string $ruta) {
    $info = getimagesize($ruta);
    $im = $info[2] === IMAGETYPE_PNG ? imagecreatefrompng($ruta) : imagecreatefromjpeg($ruta);
    if ($info[2] === IMAGETYPE_PNG) {
        $plano = imagecreatetruecolor(imagesx($im), imagesy($im));
        imagefill($plano, 0, 0, imagecolorallocate($plano, 255, 255, 255));
        imagecopy($plano, $im, 0, 0, 0, 0, imagesx($im), imagesy($im));
        imagedestroy($im);
        $im = $plano;
    }
    return $im;
}

/** Caja del contenido: descarta el fondo blanco (y el ruido del JPEG). */
function recuadro($im, int $umbral = 246): array {
    $an = imagesx($im);
    $al = imagesy($im);
    $x0 = $an; $y0 = $al; $x1 = -1; $y1 = -1;
    for ($y = 0; $y < $al; $y += 2) {
        for ($x = 0; $x < $an; $x += 2) {
            $c = imagecolorat($im, $x, $y);
            if ((($c >> 16) & 0xFF) < $umbral || (($c >> 8) & 0xFF) < $umbral || ($c & 0xFF) < $umbral) {
                if ($x < $x0) $x0 = $x;
                if ($y < $y0) $y0 = $y;
                if ($x > $x1) $x1 = $x;
                if ($y > $y1) $y1 = $y;
            }
        }
    }
    return [$x0, $y0, max(1, $x1 - $x0 + 1), max(1, $y1 - $y0 + 1)];
}

/**
 * Pega en modo multiplicar: el blanco del recorte no tapa nada y la sombra
 * que trae la propia foto se funde con el degradado. $velo aclara la pieza.
 */
function multiplicar($destino, $pieza, int $px, int $py, float $velo = 0.0): void {
    $an = imagesx($pieza);   $al = imagesy($pieza);
    $dAn = imagesx($destino); $dAl = imagesy($destino);
    for ($y = 0; $y < $al; $y++) {
        $dy = $py + $y;
        if ($dy < 0 || $dy >= $dAl) continue;
        for ($x = 0; $x < $an; $x++) {
            $dx = $px + $x;
            if ($dx < 0 || $dx >= $dAn) continue;
            $c = imagecolorat($pieza, $x, $y);
            $fr = ($c >> 16) & 0xFF; $fg = ($c >> 8) & 0xFF; $fb = $c & 0xFF;
            if ($fr >= 251 && $fg >= 251 && $fb >= 251) continue;   // fondo puro
            if ($velo > 0) {
                $fr = (int) round($fr + (255 - $fr) * $velo);
                $fg = (int) round($fg + (255 - $fg) * $velo);
                $fb = (int) round($fb + (255 - $fb) * $velo);
            }
            $b = imagecolorat($destino, $dx, $dy);
            imagesetpixel($destino, $dx, $dy, imagecolorallocate(
                $destino,
                (int) (((($b >> 16) & 0xFF) * $fr) / 255),
                (int) (((($b >> 8) & 0xFF) * $fg) / 255),
                (int) ((($b & 0xFF) * $fb) / 255)
            ));
        }
    }
}

/* --------------------------------------------------------------------------
   3. Montaje
   -------------------------------------------------------------------------- */
foreach ($PIEZAS as [$archivo, $alto, $centroX, $baseY, $velo]) {
    $ruta = $FUENTES . $archivo;
    if (!is_file($ruta)) { fwrite(STDERR, "falta $archivo\n"); continue; }

    $src = cargar($ruta);
    [$cx0, $cy0, $cAn, $cAl] = recuadro($src);

    $escala = $alto / $cAl;
    $nAn = max(1, (int) round($cAn * $escala));
    $nAl = max(1, (int) round($cAl * $escala));

    $dst = imagecreatetruecolor($nAn, $nAl);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, $cx0, $cy0, $nAn, $nAl, $cAn, $cAl);

    multiplicar($lienzo, $dst, (int) round($centroX - $nAn / 2), (int) round($baseY - $nAl), $velo);

    imagedestroy($src);
    imagedestroy($dst);
    echo "  colocado $archivo  {$nAn}x{$nAl}\n";
}

imagejpeg($lienzo, $SALIDA, 88);
imagedestroy($lienzo);
echo "-> img/hero-portada.jpg  {$ANCHO}x{$ALTO}  " . round(filesize($SALIDA) / 1024) . " KB\n";
