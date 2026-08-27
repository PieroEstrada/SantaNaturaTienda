<?php
/* Cabecera y pie comunes del panel. No se enlaza desde la web pública. */
declare(strict_types=1);

function sn_cabecera(string $titulo, bool $conSesion = true): void
{
    ?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Fuera de los buscadores: esta URL no debe aparecer nunca en Google, y
     menos en un dominio con campañas de Ads activas. -->
<meta name="robots" content="noindex, nofollow, noarchive">
<title><?= htmlspecialchars($titulo, ENT_QUOTES) ?> · Gestión Santa Natura</title>
<style>
  :root {
    --verde:#1b5e3f; --verde-claro:#e8f2ec; --texto:#1a1c1a; --suave:#5c6660;
    --borde:#d7e0da; --fondo:#f6f8f6; --blanco:#fff; --rojo:#b3261e; --ambar:#8a6100;
  }
  * { box-sizing:border-box; }
  body { margin:0; font:16px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
         background:var(--fondo); color:var(--texto); }
  header.sn { background:var(--verde); color:#fff; padding:12px 20px;
              display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
  header.sn b { font-size:17px; }
  header.sn nav { margin-left:auto; display:flex; gap:14px; align-items:center; }
  header.sn a { color:#cfe6d9; text-decoration:none; font-size:14px; }
  header.sn a:hover { color:#fff; text-decoration:underline; }
  main { max-width:1180px; margin:0 auto; padding:20px; }
  h1 { font-size:22px; margin:0 0 4px; }
  h2 { font-size:17px; margin:28px 0 10px; }
  .sub { color:var(--suave); font-size:14px; margin:0 0 20px; }
  .caja { background:var(--blanco); border:1px solid var(--borde); border-radius:12px; padding:18px; }
  table { width:100%; border-collapse:collapse; background:var(--blanco);
          border:1px solid var(--borde); border-radius:12px; overflow:hidden; }
  th,td { padding:9px 10px; text-align:left; border-bottom:1px solid var(--borde); font-size:14px; vertical-align:middle; }
  th { background:var(--verde-claro); font-size:12px; text-transform:uppercase;
       letter-spacing:.05em; color:var(--verde); position:sticky; top:0; }
  tr:last-child td { border-bottom:0; }
  tr.off { opacity:.5; }
  td.num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
  .btn { display:inline-block; background:var(--verde); color:#fff; border:0; cursor:pointer;
         padding:9px 16px; border-radius:999px; font-size:14px; text-decoration:none; }
  .btn:hover { filter:brightness(1.12); }
  .btn.gris { background:#5c6660; }
  .btn.rojo { background:var(--rojo); }
  .btn.mini { padding:5px 11px; font-size:13px; }
  input,select,textarea { font:inherit; padding:9px 11px; border:1px solid var(--borde);
                          border-radius:8px; width:100%; background:#fff; color:var(--texto); }
  textarea { min-height:88px; resize:vertical; }
  label { display:block; font-size:13px; font-weight:600; margin:14px 0 5px; }
  label .pista { display:block; font-weight:400; color:var(--suave); font-size:12px; margin-top:2px; }
  .rejilla { display:grid; grid-template-columns:repeat(auto-fit,minmax(215px,1fr)); gap:0 18px; }
  .aviso { padding:11px 14px; border-radius:9px; margin:0 0 16px; font-size:14px; }
  .aviso.ok { background:#e4f3ea; border:1px solid #a8d5bd; color:#14512f; }
  .aviso.mal { background:#fbe6e4; border:1px solid #e8b4af; color:#7d1c16; }
  .aviso.ojo { background:#fdf3dd; border:1px solid #e6d09a; color:var(--ambar); }
  .chips { display:flex; flex-wrap:wrap; gap:7px; margin-top:5px; }
  .chips label { display:inline-flex; align-items:center; gap:5px; margin:0; font-weight:400;
                 font-size:13px; background:#fff; border:1px solid var(--borde);
                 padding:5px 10px; border-radius:999px; cursor:pointer; }
  .chips input { width:auto; }
  .chips label:has(input:checked) { background:var(--verde-claro); border-color:var(--verde); color:var(--verde); font-weight:600; }
  .barra { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:14px; }
  .barra input[type=search] { max-width:290px; }
  .pill { font-size:11px; padding:2px 8px; border-radius:999px; background:var(--verde-claro); color:var(--verde); font-weight:600; }
  .pill.off { background:#eceeed; color:var(--suave); }
  .mini { font-size:12px; color:var(--suave); }
  .foto { width:38px; height:38px; object-fit:contain; background:#fff;
          border:1px solid var(--borde); border-radius:6px; }
  .sinfoto { width:38px; height:38px; border:1px dashed var(--borde); border-radius:6px;
             display:grid; place-items:center; font-size:16px; color:#b9c4bd; }
  @media (max-width:720px){ table,thead,tbody,th,td,tr{display:block} thead{display:none}
    td{border:0;padding:4px 12px} tr{border-bottom:1px solid var(--borde);padding:10px 0}
    td.num{text-align:left} }
</style>
</head>
<body>
<header class="sn">
  <b>Gestión Santa Natura</b>
  <?php if ($conSesion): ?>
  <nav>
    <a href="index.php">Catálogo</a>
    <a href="producto.php">Nuevo producto</a>
    <a href="../index.html" target="_blank" rel="noopener">Ver la web</a>
    <a href="salir.php">Salir</a>
  </nav>
  <?php endif; ?>
</header>
<main>
<?php
}

function sn_pie(): void
{
    ?>
</main>
</body>
</html>
<?php
}

/** Muestra y consume el mensaje guardado tras un guardado o un error. */
function sn_mensaje_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    [$tipo, $texto] = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<p class="aviso ' . htmlspecialchars($tipo, ENT_QUOTES) . '">'
       . htmlspecialchars($texto, ENT_QUOTES) . '</p>';
}

function sn_flash(string $tipo, string $texto): void
{
    $_SESSION['flash'] = [$tipo, $texto];
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES);
}
