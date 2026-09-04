<?php
/**
 * Genera el favicon del sitio a partir del isotipo oficial de Santa Natura.
 * ===========================================================================
 *
 * Produce, en la raíz del proyecto:
 *   favicon.ico          16, 32 y 48 px en un solo archivo (lo pide el
 *                        navegador solo, aunque no se enlace)
 *   apple-touch-icon.png 180x180, la que usa iOS al guardar en pantalla de
 *                        inicio (sin transparencia: iOS pinta negro detrás)
 *   img/icono-192.png    la que usan Android y las pestañas de alta densidad
 *
 * El isotipo lleva un círculo blanco detrás. Es a propósito: el verde de la
 * marca es oscuro y sobre una barra de pestañas en tema oscuro casi no se
 * distingue. Con el círculo se lee igual de bien en claro y en oscuro.
 * Si algún día se prefiere el logo "a pelo", pon $FONDO = false.
 *
 * CÓMO SE USA
 *     C:/xampp/php/php.exe -d extension=gd scripts/generar-favicon.php
 *
 * (XAMPP trae GD apagado en el php.ini de consola, de ahí el -d extension=gd.)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!function_exists('imagecreatetruecolor')) {
    fwrite(STDERR, "Falta la extensión GD. Llama al script así:\n"
        . "  C:/xampp/php/php.exe -d extension=gd scripts/generar-favicon.php\n");
    exit(1);
}

$RAIZ    = dirname(__DIR__);
$FUENTES = __DIR__ . '/.hero-fuentes/';
$ORIGEN  = $FUENTES . 'isotipo-sn-192.png';
$URL     = 'https://santanatura.com.pe/wp-content/uploads/2025/11/cropped-favicon-web-sn-1-192x192.png';

$FONDO   = true;    // círculo blanco detrás del isotipo
$MARGEN  = 0.12;    // aire alrededor del isotipo, en tanto por uno

if (!is_dir($FUENTES)) mkdir($FUENTES, 0777, true);
if (!is_file($ORIGEN)) {
    echo "descargando el isotipo oficial… ";
    $datos = @file_get_contents($URL, false, stream_context_create([
        'http' => ['timeout' => 60, 'user_agent' => 'SantaNaturaTienda/1.0'],
    ]));
    if ($datos === false || strlen($datos) < 1000) {
        fwrite(STDERR, "\nNo se pudo descargar $URL\n");
        exit(1);
    }
    file_put_contents($ORIGEN, $datos);
    echo round(strlen($datos) / 1024) . " KB\n";
}

/**
 * Dibuja el icono al tamaño pedido. Se compone al cuádruple y se reduce
 * después: así el borde del círculo sale suave sin tener que antialiasear a
 * mano (GD no antialiasea las elipses rellenas).
 *
 * @param bool $opaco true rellena TODO el cuadrado de blanco (iOS), false deja
 *                    transparente lo de fuera del círculo.
 */
function icono(string $origen, int $lado, bool $fondo, bool $opaco, float $margen) {
    $g = $lado * 4;

    $lienzo = imagecreatetruecolor($g, $g);
    imagealphablending($lienzo, false);
    imagesavealpha($lienzo, true);
    imagefilledrectangle($lienzo, 0, 0, $g, $g, imagecolorallocatealpha($lienzo, 255, 255, 255, $opaco ? 0 : 127));
    imagealphablending($lienzo, true);

    if ($fondo && !$opaco) {
        imagefilledellipse($lienzo, (int) ($g / 2), (int) ($g / 2), $g, $g, imagecolorallocate($lienzo, 255, 255, 255));
    }

    $src = imagecreatefrompng($origen);
    $lado_src = imagesx($src);
    $dentro = (int) round($g * (1 - 2 * $margen));
    $pos = (int) round(($g - $dentro) / 2);
    imagecopyresampled($lienzo, $src, $pos, $pos, 0, 0, $dentro, $dentro, $lado_src, $lado_src);
    imagedestroy($src);

    $fin = imagecreatetruecolor($lado, $lado);
    imagealphablending($fin, false);
    imagesavealpha($fin, true);
    imagefilledrectangle($fin, 0, 0, $lado, $lado, imagecolorallocatealpha($fin, 255, 255, 255, 127));
    imagealphablending($fin, false);
    imagecopyresampled($fin, $lienzo, 0, 0, 0, 0, $lado, $lado, $g, $g);
    imagedestroy($lienzo);

    return $fin;
}

/** Devuelve el PNG de una imagen como cadena. */
function png_en_texto($im): string {
    ob_start();
    imagepng($im, null, 9);
    return ob_get_clean();
}

/**
 * Arma un .ico con varias medidas. Cada medida va dentro como PNG, que es lo
 * que entienden todos los navegadores desde hace más de quince años y ocupa
 * mucho menos que el mapa de bits antiguo.
 */
function escribir_ico(array $pngs, string $destino): void {
    $n = count($pngs);
    $ico = pack('vvv', 0, 1, $n);              // reservado, tipo=icono, cuántas
    $desplazamiento = 6 + 16 * $n;

    foreach ($pngs as $lado => $datos) {
        $ico .= pack('CCCCvvVV',
            $lado >= 256 ? 0 : $lado,           // ancho (0 = 256)
            $lado >= 256 ? 0 : $lado,           // alto
            0,                                  // colores de la paleta
            0,                                  // reservado
            1,                                  // planos
            32,                                 // bits por píxel
            strlen($datos),
            $desplazamiento
        );
        $desplazamiento += strlen($datos);
    }
    foreach ($pngs as $datos) $ico .= $datos;

    file_put_contents($destino, $ico);
}

/* --------------------------------------------------------------------------
   Salidas
   -------------------------------------------------------------------------- */
$pngs = [];
foreach ([16, 32, 48] as $lado) {
    $im = icono($ORIGEN, $lado, $FONDO, false, $MARGEN);
    $pngs[$lado] = png_en_texto($im);
    imagedestroy($im);
}
escribir_ico($pngs, $RAIZ . '/favicon.ico');
echo "-> favicon.ico  16+32+48  " . round(filesize($RAIZ . '/favicon.ico') / 1024, 1) . " KB\n";

$im = icono($ORIGEN, 180, $FONDO, true, $MARGEN + 0.04);   // iOS: sin transparencia
imagepng($im, $RAIZ . '/apple-touch-icon.png', 9);
imagedestroy($im);
echo "-> apple-touch-icon.png  180x180  " . round(filesize($RAIZ . '/apple-touch-icon.png') / 1024, 1) . " KB\n";

$im = icono($ORIGEN, 192, $FONDO, false, $MARGEN);
imagepng($im, $RAIZ . '/img/icono-192.png', 9);
imagedestroy($im);
echo "-> img/icono-192.png  192x192  " . round(filesize($RAIZ . '/img/icono-192.png') / 1024, 1) . " KB\n";
