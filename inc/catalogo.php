<?php
/* ============================================================================
   Santa Natura — Acceso al catálogo desde PHP
   ----------------------------------------------------------------------------
   products.js sigue siendo la ÚNICA fuente de verdad. No se duplica en una
   base de datos ni en un JSON aparte: el navegador lo carga tal cual como
   siempre, y PHP lo lee de aquí para el panel de administración.

   Se puede hacer porque el array PRODUCTS está escrito en JSON estricto
   (claves y textos entre comillas dobles), así que basta con recortar el
   trozo entre «const PRODUCTS = [» y el «];» que lo cierra y pasarlo por
   json_decode. Todo lo demás del archivo (la cabecera con la documentación,
   COLECCIONES, CATALOGO, CATEGORIAS) se conserva byte a byte al guardar.
   ========================================================================== */

declare(strict_types=1);

const SN_RAIZ         = __DIR__ . '/..';
const SN_PRODUCTS_JS  = SN_RAIZ . '/products.js';
const SN_COPIAS       = SN_RAIZ . '/inc/copias';

/** Marcadores que delimitan el array dentro del archivo. */
const SN_ABRE  = 'const PRODUCTS = [';
const SN_CIERRA = "\n];";

/**
 * Lee products.js y devuelve ['cabecera', 'productos' => array, 'pie'].
 * cabecera y pie son el texto literal de antes y después del array; hay que
 * devolverlos intactos al guardar.
 */
function sn_leer_archivo(): array
{
    $texto = @file_get_contents(SN_PRODUCTS_JS);
    if ($texto === false) {
        throw new RuntimeException('No puedo leer products.js. Revisa permisos de lectura.');
    }

    $ini = strpos($texto, SN_ABRE);
    if ($ini === false) {
        throw new RuntimeException('products.js no contiene «' . SN_ABRE . '». ¿Se editó a mano?');
    }
    $inicioArray = $ini + strlen(SN_ABRE) - 1;      // apunta al «[»

    $fin = strpos($texto, SN_CIERRA, $inicioArray);
    if ($fin === false) {
        throw new RuntimeException('No encuentro el cierre del array PRODUCTS.');
    }
    // SN_CIERRA es "\n];": el «]» es el segundo carácter, no el último.
    $finArray = $fin + 1;

    $json = substr($texto, $inicioArray, $finArray - $inicioArray + 1);

    // El array lleva comentarios /* … */ entre registros: JSON no los admite,
    // así que se quitan solo para decodificar. Al guardar se reescribe entero.
    $json = preg_replace('#/\*.*?\*/#s', '', $json);

    $productos = json_decode($json, true);
    if (!is_array($productos)) {
        throw new RuntimeException('El array PRODUCTS no es JSON válido: ' . json_last_error_msg());
    }

    return [
        // La cabecera incluye el «[» y el pie empieza en el «;»: así, al
        // guardar, basta con intercalar las líneas entre los dos y el resto
        // del archivo (documentación, COLECCIONES, CATALOGO…) queda intacto.
        'cabecera'  => substr($texto, 0, $inicioArray + 1),
        'productos' => $productos,
        'pie'       => substr($texto, $finArray + 1),
    ];
}

/** Solo los productos: es lo que necesita casi todo el panel. */
function sn_productos(): array
{
    return sn_leer_archivo()['productos'];
}

/** Lo que la web publica: todo lo que no esté marcado como retirado. */
function sn_catalogo(): array
{
    return array_values(array_filter(
        sn_productos(),
        static fn(array $p): bool => ($p['activo'] ?? true) !== false
    ));
}

/**
 * Orden de las claves en el archivo. Se respeta para que el diff de git siga
 * siendo legible y products.js no cambie de forma en cada guardado.
 */
const SN_ORDEN_CLAVES = [
    'id', 'activo', 'categoria', 'producto', 'puntos', 'pvp',
    'precio_original', 'etiqueta_descuento', 'imagen', 'descripcion',
    'contiene', 'categorias', 'descuentos',
];

/** Serializa un producto en una línea, con el mismo estilo del archivo. */
function sn_producto_a_linea(array $p): string
{
    $partes = [];

    $claves = array_merge(
        array_values(array_filter(SN_ORDEN_CLAVES, static fn($k) => array_key_exists($k, $p))),
        array_values(array_diff(array_keys($p), SN_ORDEN_CLAVES))
    );

    foreach ($claves as $k) {
        $v = $p[$k];

        if (in_array($k, ['pvp', 'precio_original', 'puntos'], true) && is_numeric($v)) {
            // Dinero y puntos siempre con 2 decimales, como el resto del archivo.
            $valor = number_format((float) $v, 2, '.', '');
        } elseif ($k === 'descuentos' && is_array($v)) {
            $pares = [];
            foreach ($v as $q => $m) {
                $pares[] = '"' . $q . '": ' . number_format((float) $m, 2, '.', '');
            }
            $valor = '{' . implode(', ', $pares) . '}';
        } else {
            $valor = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $partes[] = '"' . $k . '": ' . $valor;
    }

    return '  {' . implode(', ', $partes) . '}';
}

/**
 * Escribe products.js con la lista dada.
 *
 * Antes de tocar nada guarda una copia con fecha en inc/copias/, para poder
 * volver atrás si un cambio sale mal. La escritura es atómica (archivo
 * temporal + rename) para que un fallo a medias no deje el catálogo roto y
 * la web caída.
 */
function sn_guardar(array $productos): void
{
    $archivo = sn_leer_archivo();

    // 1. Copia de seguridad.
    if (!is_dir(SN_COPIAS) && !@mkdir(SN_COPIAS, 0775, true) && !is_dir(SN_COPIAS)) {
        throw new RuntimeException('No puedo crear inc/copias/. Revisa permisos de escritura.');
    }
    // El sufijo evita que dos guardados dentro del mismo segundo se pisen la
    // copia: sin él, el segundo sobrescribe al primero y se pierde el estado
    // al que se querría volver.
    $sello = date('Ymd-His');
    $copia = SN_COPIAS . '/products-' . $sello . '.js';
    for ($n = 2; is_file($copia); $n++) {
        $copia = SN_COPIAS . '/products-' . $sello . '-' . $n . '.js';
    }
    if (!@copy(SN_PRODUCTS_JS, $copia)) {
        throw new RuntimeException('No pude guardar la copia de seguridad. No se ha cambiado nada.');
    }

    // 2. Reconstruir el archivo.
    //    Se respeta el fin de línea que ya tenía el archivo (CRLF en Windows,
    //    LF en el servidor). Si se escribiera siempre "\n", cada guardado desde
    //    XAMPP dejaría products.js con finales mezclados y el diff de git
    //    marcaría las 104 líneas como cambiadas aunque no se tocara ningún dato.
    $eol    = str_contains($archivo['cabecera'], "\r\n") ? "\r\n" : "\n";
    $lineas = array_map('sn_producto_a_linea', array_values($productos));
    $nuevo  = $archivo['cabecera'] . $eol . implode(',' . $eol, $lineas) . $eol . ']' . $archivo['pie'];

    // 3. Escritura atómica.
    $tmp = SN_PRODUCTS_JS . '.tmp';
    if (@file_put_contents($tmp, $nuevo, LOCK_EX) === false) {
        throw new RuntimeException('No puedo escribir en la carpeta del sitio. Revisa permisos.');
    }

    // 4. Comprobar que lo escrito se puede volver a leer ANTES de publicarlo.
    //    Si el archivo generado saliera roto, la web entera se quedaría sin
    //    catálogo; mejor abortar y dejar el anterior en su sitio.
    $ini = strpos($nuevo, SN_ABRE) + strlen(SN_ABRE) - 1;
    $fin = strpos($nuevo, SN_CIERRA, $ini) + 1;
    $comprobacion = preg_replace('#/\*.*?\*/#s', '', substr($nuevo, $ini, $fin - $ini + 1));

    if (!is_array(json_decode($comprobacion, true))) {
        @unlink($tmp);
        throw new RuntimeException('El archivo generado no es válido; no se ha publicado nada. Copia intacta en ' . basename($copia));
    }

    if (!@rename($tmp, SN_PRODUCTS_JS)) {
        @unlink($tmp);
        throw new RuntimeException('No pude reemplazar products.js.');
    }

    // No hace falta regenerar nada más: todas las páginas son PHP y calculan
    // su propio ?v=<hash> con sn_v(), así que el navegador vuelve a descargar
    // products.js en cuanto cambia. Sin ese sello, un visitante que ya estuvo
    // en la web seguiría viendo los PRECIOS ANTIGUOS hasta que se le venciera
    // la caché — y con tráfico de Ads eso es pagar clics por un precio que ya
    // no existe.
    sn_limpiar_copias();
}

/** Deja solo las 30 copias más recientes. */
function sn_limpiar_copias(int $conservar = 30): void
{
    $copias = glob(SN_COPIAS . '/products-*.js') ?: [];
    if (count($copias) <= $conservar) {
        return;
    }
    sort($copias);
    foreach (array_slice($copias, 0, count($copias) - $conservar) as $vieja) {
        @unlink($vieja);
    }
}

/** Siguiente id libre. No se reutilizan ids de productos retirados. */
function sn_siguiente_id(array $productos): int
{
    $ids = array_map(static fn($p) => (int) $p['id'], $productos);
    return $ids ? max($ids) + 1 : 1;
}

/* --------------------------------------------------------------------------
   Reglas del catálogo, las mismas que ya cumplían los 30 packs anteriores.
   -------------------------------------------------------------------------- */

/** Puntos de un pack: pvp / 6. */
function sn_puntos_pack(float $pvp): float
{
    return round($pvp / 6, 2);
}

/** Precio por cantidad de un pack: pvp * 0.90, bajo la clave "15". */
function sn_descuentos_pack(float $pvp): array
{
    return ['15' => round($pvp * 0.90, 2)];
}

/** Badge coherente con los dos precios. Null si no hay precio base. */
function sn_etiqueta_descuento(float $pvp, ?float $base): ?string
{
    if ($base === null || $base <= 0 || $base <= $pvp) {
        return null;
    }
    return '-' . (int) round((1 - $pvp / $base) * 100) . '%';
}

/** Categorías válidas, leídas de COLECCIONES en products.js. */
function sn_categorias_validas(): array
{
    $texto = (string) @file_get_contents(SN_PRODUCTS_JS);
    $ini = strpos($texto, 'const COLECCIONES = [');
    if ($ini === false) {
        return [];
    }
    $bloque = substr($texto, $ini, strpos($texto, "\n];", $ini) - $ini);

    preg_match_all("/nombre:\s*'([^']+)'/", $bloque, $madres);
    preg_match_all("/hijas:\s*\[([^\]]*)\]/", $bloque, $hijas);

    $nombres = $madres[1] ?? [];
    foreach ($hijas[1] ?? [] as $lista) {
        preg_match_all("/'([^']+)'/", $lista, $m);
        $nombres = array_merge($nombres, $m[1] ?? []);
    }

    $nombres = array_values(array_unique($nombres));
    sort($nombres, SORT_NATURAL | SORT_FLAG_CASE);
    return $nombres;
}

/* --------------------------------------------------------------------------
   Contenido de un pack
   --------------------------------------------------------------------------
   Un pack es una lista de productos del propio catálogo con sus cantidades.
   Hasta ahora eso solo existía como frase suelta dentro de `descripcion`
   («Contiene: 2 Tocosh, 2 EnfoK+…»), así que no se podía consultar ni cuadrar
   con nada: si un producto cambiaba de nombre o de precio, la frase seguía
   diciendo lo de antes y nadie se enteraba.

   Ahora el pack guarda además `contiene`, la lista de verdad, y la frase se
   escribe sola desde ella. La web sigue leyendo `descripcion` y no se entera
   del cambio: lo que gana es que la frase ya no puede mentir.

   Los packs antiguos no tienen `contiene` todavía y conservan su frase tal
   cual. En cuanto se les arma el contenido desde el panel, pasan al modo nuevo.
   -------------------------------------------------------------------------- */

/** Índice id => producto, para resolver las líneas de un pack. */
function sn_por_id(array $productos): array
{
    $indice = [];
    foreach ($productos as $p) {
        $indice[(int) $p['id']] = $p;
    }
    return $indice;
}

/**
 * Escribe la frase «Contiene: … De regalo: …» desde la lista de un pack.
 * Devuelve '' si la lista está vacía, para que el pack se quede con la
 * descripción que tuviera escrita a mano.
 */
function sn_texto_contenido(array $contiene, array $productos): string
{
    $indice = sn_por_id($productos);
    $normal = [];
    $regalo = [];

    foreach ($contiene as $linea) {
        $ref = $indice[(int) ($linea['id'] ?? 0)] ?? null;
        if ($ref === null) {
            continue;
        }
        $cant  = max(1, (int) ($linea['cant'] ?? 1));
        $texto = $cant . ' ' . $ref['producto'];
        if (!empty($linea['regalo'])) {
            $regalo[] = $texto;
        } else {
            $normal[] = $texto;
        }
    }

    $partes = [];
    if ($normal) { $partes[] = 'Contiene: ' . implode(', ', $normal) . '.'; }
    if ($regalo) { $partes[] = 'De regalo: ' . implode(', ', $regalo) . '.'; }

    return implode(' ', $partes);
}

/**
 * Suma de los precios sueltos de lo que lleva el pack. Es la referencia para
 * ponerle precio: si el pack no cuesta menos que comprar sus productos por
 * separado, no hay pack que vender.
 */
function sn_valor_contenido(array $contiene, array $productos): float
{
    $indice = sn_por_id($productos);
    $total  = 0.0;

    foreach ($contiene as $linea) {
        $ref = $indice[(int) ($linea['id'] ?? 0)] ?? null;
        if ($ref === null || !empty($linea['regalo'])) {
            continue;      // el regalo no se cobra: no suma
        }
        $total += (float) $ref['pvp'] * max(1, (int) ($linea['cant'] ?? 1));
    }

    return round($total, 2);
}

/* --------------------------------------------------------------------------
   Subida de fotos
   --------------------------------------------------------------------------
   Sin esto, dar de alta un pack nuevo se quedaba a medias: el formulario solo
   dejaba elegir entre las fotos que YA estaban en img/, así que para publicar
   uno nuevo había que entrar por FTP a subir la imagen y volver al panel. Con
   tráfico de Ads esperando la landing, eso es una tarde perdida.
   -------------------------------------------------------------------------- */

/** Tamaño máximo por foto. Por encima, la tarjeta tarda de más en móvil. */
const SN_FOTO_MAX = 5 * 1024 * 1024;

/** Solo estos tres formatos: son los que el navegador dibuja sin sorpresas. */
const SN_FOTO_TIPOS = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_WEBP => 'webp',
];

/** Convierte un nombre cualquiera en algo válido y legible dentro de img/. */
function sn_nombre_archivo(string $texto): string
{
    $texto = (string) preg_replace('/[^\p{L}\p{N}]+/u', '-', $texto);
    // Acentos y ñ fuera: el nombre viaja en una URL y en un <img src>.
    $texto = (string) @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = strtolower((string) preg_replace('/[^A-Za-z0-9-]+/', '', $texto));
    $texto = trim((string) preg_replace('/-+/', '-', $texto), '-');
    return $texto === '' ? 'foto' : substr($texto, 0, 60);
}

/**
 * Guarda en img/ la foto que llega del formulario y devuelve su ruta relativa
 * («img/pack-mujer.png»), lista para el campo `imagen` del producto.
 *
 * No se fía de la extensión ni del content-type que manda el navegador: los
 * dos los elige quien sube el archivo. El formato se decide leyendo la imagen
 * de verdad, así que un .php renombrado a .jpg no pasa de aquí.
 *
 * @param array  $archivo Entrada de $_FILES.
 * @param string $sugerido Nombre deseado (normalmente el del producto).
 */
function sn_subir_imagen(array $archivo, string $sugerido = ''): string
{
    $codigo = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($codigo !== UPLOAD_ERR_OK) {
        throw new RuntimeException(match ($codigo) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                'La foto pesa más de lo que admite el servidor. Redúcela y vuelve a subirla.',
            UPLOAD_ERR_PARTIAL   => 'La foto se subió a medias. Inténtalo otra vez.',
            UPLOAD_ERR_NO_FILE   => 'No llegó ninguna foto.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE =>
                'El servidor no pudo guardar la foto (carpeta temporal). Avisa a soporte del hosting.',
            default              => 'No se pudo subir la foto (error ' . $codigo . ').',
        });
    }

    $temporal = (string) ($archivo['tmp_name'] ?? '');
    if (!is_uploaded_file($temporal)) {
        throw new RuntimeException('El archivo recibido no es una subida válida.');
    }

    if ((int) ($archivo['size'] ?? 0) > SN_FOTO_MAX) {
        throw new RuntimeException('La foto pesa más de '
            . (int) (SN_FOTO_MAX / 1024 / 1024) . ' MB. Compárimela antes de subirla: '
            . 'una imagen así de pesada hace lenta la ficha en móvil.');
    }

    $info = @getimagesize($temporal);
    if (!$info || !isset(SN_FOTO_TIPOS[$info[2]])) {
        throw new RuntimeException('El archivo no es una imagen JPG, PNG o WEBP.');
    }
    $extension = SN_FOTO_TIPOS[$info[2]];

    $base = sn_nombre_archivo($sugerido !== '' ? $sugerido : pathinfo((string) ($archivo['name'] ?? ''), PATHINFO_FILENAME));

    // Nunca se pisa una foto existente: otro producto podría estar usándola.
    $nombre = $base . '.' . $extension;
    for ($n = 2; is_file(SN_RAIZ . '/img/' . $nombre); $n++) {
        $nombre = $base . '-' . $n . '.' . $extension;
    }

    if (!@move_uploaded_file($temporal, SN_RAIZ . '/img/' . $nombre)) {
        throw new RuntimeException('No puedo escribir en la carpeta img/. Revisa sus permisos.');
    }
    @chmod(SN_RAIZ . '/img/' . $nombre, 0644);

    return 'img/' . $nombre;
}

/** Fotos disponibles en img/, para el selector de imagen del panel. */
function sn_imagenes(): array
{
    $archivos = glob(SN_RAIZ . '/img/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
    $rutas = array_map(static fn($f) => 'img/' . basename($f), $archivos);
    sort($rutas, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values(array_unique($rutas));
}

/**
 * ¿Es «img/algo.jpg» de la lista de verdad?
 *
 * El formulario manda el campo `imagen` como texto, y comprobar solo que el
 * archivo exista deja pasar cualquier ruta del servidor: «inc/admin-config.php»
 * existe, y acabaría escrita en products.js —que es público— apuntando a un
 * archivo que no es una foto. Aquí se compara contra las fotos que hay en img/,
 * que es lo único que el selector ofrece.
 */
function sn_imagen_valida(string $ruta): bool
{
    return $ruta !== '' && in_array($ruta, sn_imagenes(), true);
}
