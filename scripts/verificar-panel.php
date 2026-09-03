<?php
/* Prueba del panel: autenticación, guardado y round-trip de datos.
   Deja products.js exactamente como estaba.

   SOLO POR CONSOLA:  php scripts/verificar-panel.php

   Este archivo llama a sn_guardar(), o sea que ESCRIBE en products.js. Vive
   dentro de la carpeta pública, así que sin este cerrojo bastaba con abrir
   /scripts/verificar-panel.php en el navegador —sin contraseña, sin sesión, sin
   nada— para reescribir el catálogo de la tienda y desactivar y reactivar un
   pack. La prueba lo deja todo como estaba, pero eso es una casualidad
   afortunada, no una defensa: cualquiera con la URL podía dispararlo.

   El .htaccess de la raíz también responde 404 a toda la carpeta /scripts/.
   Van los dos porque este archivo no se puede quedar dependiendo de que el
   hosting lea .htaccess (Nginx no lo hace). */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/catalogo.php';

$fallos = 0;
$ok = function (string $n, bool $cond, string $extra = '') use (&$fallos) {
    if (!$cond) { $fallos++; }
    echo ($cond ? '  OK   ' : '  MAL  ') . $n . ($extra !== '' ? '   ' . $extra : '') . PHP_EOL;
};

echo "== Lectura del catálogo ==" . PHP_EOL;
$antes = file_get_contents(SN_PRODUCTS_JS);
$productos = sn_productos();
$catalogo  = sn_catalogo();
$ok('lee PRODUCTS', count($productos) === 104, count($productos) . ' registros');
$ok('filtra CATALOGO', count($catalogo) === 74, count($catalogo) . ' publicados');
$ok('respeta activo:false', count($productos) - count($catalogo) === 30);

echo PHP_EOL . "== Round-trip: guardar sin cambiar nada ==" . PHP_EOL;
sn_guardar($productos);
$despues = file_get_contents(SN_PRODUCTS_JS);
$ok('products.js sigue siendo válido', count(sn_productos()) === 104);
$ok('idéntico byte a byte', $antes === $despues,
    $antes === $despues ? '' : 'difiere en ' . abs(strlen($antes) - strlen($despues)) . ' bytes');

if ($antes !== $despues) {
    // Muestra la primera diferencia para poder arreglarla.
    $n = min(strlen($antes), strlen($despues));
    for ($i = 0; $i < $n && $antes[$i] === $despues[$i]; $i++);
    echo '        antes:   …' . substr($antes, max(0, $i - 50), 110) . PHP_EOL;
    echo '        después: …' . substr($despues, max(0, $i - 50), 110) . PHP_EOL;
}

echo PHP_EOL . "== Retirar y volver a publicar un pack ==" . PHP_EOL;
$prods = sn_productos();
foreach ($prods as &$p) { if ((int) $p['id'] === 93) { $p['activo'] = false; } }
unset($p);
sn_guardar($prods);
$ok('el pack 93 desaparece del catálogo',
    !in_array(93, array_column(sn_catalogo(), 'id'), true));
$ok('pero sigue guardado', in_array(93, array_column(sn_productos(), 'id'), true));

$prods = sn_productos();
foreach ($prods as &$p) { if ((int) $p['id'] === 93) { unset($p['activo']); } }
unset($p);
sn_guardar($prods);
$ok('vuelve a publicarse', in_array(93, array_column(sn_catalogo(), 'id'), true));
$ok('archivo restaurado', file_get_contents(SN_PRODUCTS_JS) === $antes);

echo PHP_EOL . "== Reglas del catálogo ==" . PHP_EOL;
$ok('puntos de pack = pvp/6', abs(sn_puntos_pack(388.50) - 64.75) < 0.001);
$ok('descuentos = pvp*0.90', abs(sn_descuentos_pack(388.50)['15'] - 349.65) < 0.001);
$ok('badge desde los precios', sn_etiqueta_descuento(388.50, 555.00) === '-30%');
$ok('sin precio base, sin badge', sn_etiqueta_descuento(218.40, null) === null);
$ok('base menor que pvp, sin badge', sn_etiqueta_descuento(300.0, 200.0) === null);

echo PHP_EOL . "== Contenido de los packs ==" . PHP_EOL;
$muestra = [
    ['id' => 1, 'producto' => 'Allín Surka', 'pvp' => 50.00],
    ['id' => 2, 'producto' => 'EnfoK+',      'pvp' => 30.00],
];
$lista = [
    ['id' => 1, 'cant' => 2],
    ['id' => 2, 'cant' => 1],
    ['id' => 2, 'cant' => 1, 'regalo' => true],
];
$ok('escribe la frase de la ficha',
    sn_texto_contenido($lista, $muestra) === 'Contiene: 2 Allín Surka, 1 EnfoK+. De regalo: 1 EnfoK+.',
    sn_texto_contenido($lista, $muestra));
$ok('sin contenido, no inventa frase', sn_texto_contenido([], $muestra) === '');
$ok('ignora productos borrados', sn_texto_contenido([['id' => 999, 'cant' => 1]], $muestra) === '');
$ok('suma solo lo que se cobra', abs(sn_valor_contenido($lista, $muestra) - 130.00) < 0.001,
    'S/ ' . sn_valor_contenido($lista, $muestra));

// El contenido tiene que sobrevivir a un guardado, o el pack perdería su lista
// en el primer cambio de precio que se hiciera desde el panel.
$linea = sn_producto_a_linea(['id' => 9, 'pvp' => 10, 'contiene' => [['id' => 1, 'cant' => 2]]]);
$ok('«contiene» sobrevive al guardado', str_contains($linea, '"contiene": [{"id":1,"cant":2}]'), $linea);

echo PHP_EOL . "== Nombres de las fotos que se suben ==" . PHP_EOL;
$ok('espacios a guiones',       sn_nombre_archivo('PACK MUJER DS30') === 'pack-mujer-ds30');
$ok('sin acentos ni ñ',         sn_nombre_archivo('Colágeno Año') === 'colageno-ano');
$ok('sin barras ni puntos',     sn_nombre_archivo('../../evil.php') === 'evil-php');
$ok('nunca queda vacío',        sn_nombre_archivo('¿¿¿') === 'foto');
$ok('no se pasa de largo',      strlen(sn_nombre_archivo(str_repeat('pack ', 40))) <= 60);

try {
    sn_subir_imagen(['error' => UPLOAD_ERR_INI_SIZE, 'tmp_name' => '', 'name' => 'x.jpg', 'size' => 0]);
    $ok('avisa si la foto pesa de más', false);
} catch (Throwable $e) {
    $ok('avisa si la foto pesa de más', str_contains($e->getMessage(), 'más de lo que admite'));
}
try {
    // Un .php renombrado a .jpg: no es una imagen, no debe pasar de aquí.
    $falsa = sys_get_temp_dir() . '/sn-falsa.jpg';
    file_put_contents($falsa, "<?php echo 'hola';");
    sn_subir_imagen(['error' => UPLOAD_ERR_OK, 'tmp_name' => $falsa, 'name' => 'falsa.jpg', 'size' => 20]);
    $ok('rechaza lo que no es una imagen', false);
} catch (Throwable $e) {
    // Salta antes en is_uploaded_file (no viene de un POST real), que es la
    // otra mitad de la defensa; cualquiera de las dos vale.
    $ok('rechaza lo que no es una imagen',
        str_contains($e->getMessage(), 'no es una imagen') || str_contains($e->getMessage(), 'subida válida'));
} finally {
    @unlink(sys_get_temp_dir() . '/sn-falsa.jpg');
}

echo PHP_EOL . "== Categorías e imágenes ==" . PHP_EOL;
$cats = sn_categorias_validas();
$ok('lee COLECCIONES', count($cats) >= 30, count($cats) . ' categorías');
$ok('incluye subcategorías', in_array('Packs Colágeno', $cats, true));
$ok('respeta el typo del sitio', in_array('Para el Estomágo', $cats, true));
$ok('lista las fotos de img/', count(sn_imagenes()) >= 80, count(sn_imagenes()) . ' imágenes');

echo PHP_EOL . "== Autenticación ==" . PHP_EOL;
$hash = password_hash('prueba-larga-12345', PASSWORD_DEFAULT);
$ok('hash verificable', password_verify('prueba-larga-12345', $hash));
$ok('rechaza la incorrecta', !password_verify('otra-cosa', $hash));
$ok('sin config no hay password', !sn_hay_password() || is_file(SN_CONFIG_ADMIN));

echo PHP_EOL . "== Copias de seguridad ==" . PHP_EOL;
$copias = glob(SN_COPIAS . '/products-*.js') ?: [];
$ok('se archivaron copias', count($copias) >= 3, count($copias) . ' copias');
$ok('una copia es válida', count($copias) > 0 && str_contains((string) file_get_contents(end($copias)), 'const PRODUCTS'));

echo PHP_EOL . ($fallos === 0 ? 'TODO CORRECTO' : $fallos . ' FALLO(S)') . PHP_EOL;
exit($fallos === 0 ? 0 : 1);
