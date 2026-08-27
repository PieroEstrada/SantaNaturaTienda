#!/usr/bin/env node
/* ============================================================================
   Santa Natura — Paridad entre los dos renderizadores
   ----------------------------------------------------------------------------
   QUÉ COMPRUEBA

   La tarjeta de producto se dibuja en dos sitios:
     · inc/render.php          → las landings /packs y /packs/colageno, en el
                                 servidor, para que el HTML llegue completo.
     · render-productos.js     → la portada, en el navegador, porque el
                                 catálogo se filtra y pagina sin recargar.

   Son dos lenguajes distintos, así que no hay forma de compartir el código sin
   meter un bundler (que el proyecto no quiere). Lo que sí se puede es
   comprobar que las dos salidas siguen siendo IGUALES, y avisar en cuanto se
   separen. Eso es lo que hace este script.

   CUÁNDO CORRERLO

       node scripts/verificar-paridad.js

   Cada vez que toques el maquetado de la tarjeta en cualquiera de los dos
   archivos. Si sale en rojo, has cambiado uno y te has olvidado del otro.

   Necesita PHP en el PATH, o la variable PHP_BIN apuntando al ejecutable:
       PHP_BIN=C:/xampp/php/php.exe node scripts/verificar-paridad.js
   ========================================================================== */

'use strict';
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const { execFileSync } = require('child_process');

const RAIZ = path.resolve(__dirname, '..');
const PHP = process.env.PHP_BIN || (fs.existsSync('C:/xampp/php/php.exe') ? 'C:/xampp/php/php.exe' : 'php');

/* --- Lado JavaScript ------------------------------------------------------ */
function cargarJs() {
    const codigo = ['config.js', 'products.js', 'render-productos.js']
        .map((a) => fs.readFileSync(path.join(RAIZ, a), 'utf8'))
        .join('\n;\n');
    const ctx = vm.createContext({ console });
    vm.runInContext(
        codigo + ';globalThis.__api = { CATALOGO, tarjetaProducto, fijarRaiz, seleccionPacks, seleccionColageno };',
        ctx,
        { filename: 'sitio.js' }
    );
    return ctx.__api;
}

/* --- Lado PHP ------------------------------------------------------------- */
function tarjetaPhp(id, raiz, opciones) {
    const php = JSON.stringify(opciones)
        .replace(/"(\w+)":/g, "'$1' => ")
        .replace(/^\{/, '[').replace(/\}$/, ']')
        .replace(/true/g, 'true').replace(/false/g, 'false');

    const guion = `<?php
require __DIR__ . '/../inc/render.php';
$p = null;
foreach (sn_catalogo() as $x) { if ((int)$x['id'] === ${id}) { $p = $x; break; } }
if (!$p) { fwrite(STDERR, 'sin producto ${id}'); exit(1); }
echo sn_tarjeta($p, ${JSON.stringify(raiz)}, ${php});
`;
    const tmp = path.join(RAIZ, 'scripts', '_paridad_tmp.php');
    fs.writeFileSync(tmp, guion, 'utf8');
    try {
        return execFileSync(PHP, [tmp], { encoding: 'utf8' });
    } finally {
        fs.unlinkSync(tmp);
    }
}

/* --- Comparación ---------------------------------------------------------- */
/** Ignora diferencias que no cambian lo que ve el navegador. */
const normalizar = (html) => html
    .replace(/>\s+</g, '><')
    .replace(/\s+/g, ' ')
    .trim();

function main() {
    const S = cargarJs();

    // Se prueba con casos que ejercitan las ramas: con y sin descuento, con y
    // sin foto, con y sin descripción, y las dos variantes de carga de imagen.
    const conDescuento = S.CATALOGO.find((p) => p.precio_original && p.imagen && p.descripcion);
    const sinDescuento = S.CATALOGO.find((p) => !p.precio_original);
    const sinFoto      = S.CATALOGO.find((p) => !p.imagen);

    // Variante de landing (con CTA, contenido y ahorro) y variante de portada
    // (sin extras), que son las dos formas en que se usa la tarjeta.
    const LANDING = { ctaWhatsApp: true, contenido: true, ahorro: true };
    const PORTADA = {};

    const casos = [
        { p: conDescuento, raiz: '../',   o: { ...LANDING, ansiosa: true,  prioritaria: true },  nota: 'landing: con descuento + foto, primera tarjeta' },
        { p: conDescuento, raiz: '../',   o: { ...LANDING, ansiosa: false, prioritaria: false }, nota: 'landing: con descuento + foto, diferida' },
        { p: sinDescuento, raiz: '../',   o: { ...LANDING, ansiosa: true,  prioritaria: false }, nota: 'landing: sin precio base (sin badge ni ahorro)' },
        { p: sinFoto,      raiz: '../../', o: { ...LANDING, ansiosa: false, prioritaria: false }, nota: 'landing: sin foto (placeholder de marca)' },
        { p: conDescuento, raiz: '',      o: { ...PORTADA, ansiosa: true,  prioritaria: false }, nota: 'portada: tarjeta sin extras' },
        { p: conDescuento, raiz: '',      o: { ...PORTADA, ansiosa: false, prioritaria: false, enCarrito: 3 }, nota: 'portada: con globo del carrito' },
    ].filter((c) => c.p);

    let fallos = 0;

    for (const c of casos) {
        S.fijarRaiz(c.raiz);
        const js = normalizar(S.tarjetaProducto(c.p, c.o));
        const php = normalizar(tarjetaPhp(c.p.id, c.raiz, c.o));

        if (js === php) {
            console.log('  OK   ' + c.nota + '  (' + c.p.producto + ')');
            continue;
        }

        fallos++;
        console.log('  MAL  ' + c.nota + '  (' + c.p.producto + ')');
        // Primera diferencia, con algo de contexto a cada lado.
        let i = 0;
        while (i < js.length && i < php.length && js[i] === php[i]) i++;
        const ventana = (s) => s.slice(Math.max(0, i - 60), i + 90);
        console.log('        js  …' + ventana(js));
        console.log('        php …' + ventana(php));
    }

    // La selección también está duplicada: debe elegir los mismos productos.
    const cmp = (nombre, idsJs, idsPhp) => {
        if (idsJs.join(',') === idsPhp.join(',')) {
            console.log('  OK   selección de ' + nombre + ': ' + idsJs.join(', '));
        } else {
            fallos++;
            console.log('  MAL  selección de ' + nombre);
            console.log('        js  ' + idsJs.join(', '));
            console.log('        php ' + idsPhp.join(', '));
        }
    };

    const idsPhp = (fn) => {
        const tmp = path.join(RAIZ, 'scripts', '_paridad_sel.php');
        fs.writeFileSync(tmp, `<?php require __DIR__ . '/../inc/render.php';
echo implode(',', array_column(${fn}(sn_catalogo()), 'id'));`, 'utf8');
        try {
            return execFileSync(PHP, [tmp], { encoding: 'utf8' }).trim().split(',').map(Number);
        } finally {
            fs.unlinkSync(tmp);
        }
    };

    cmp('/packs', S.seleccionPacks(S.CATALOGO).map((p) => p.id), idsPhp('sn_seleccion_packs'));
    cmp('/packs/colageno', S.seleccionColageno(S.CATALOGO).map((p) => p.id), idsPhp('sn_seleccion_colageno'));

    console.log('');
    if (fallos) {
        console.error(fallos + ' diferencia(s). inc/render.php y render-productos.js se han separado.');
        process.exitCode = 1;
    } else {
        console.log('Los dos renderizadores coinciden.');
    }
}

main();
