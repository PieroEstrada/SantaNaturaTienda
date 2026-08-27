<?php
/* ============================================================================
   PLANTILLA — cópiala como inc/admin-config.php y pon tu propia contraseña.
   ----------------------------------------------------------------------------
   CÓMO GENERAR EL HASH

   1. Entra al panel por primera vez: /gestion-sn/index.php
      Si todavía no existe inc/admin-config.php, el panel te enseña una pantalla
      para escribir tu contraseña y te da el archivo ya hecho. Es la vía fácil.

   2. O a mano, desde la línea de comandos del servidor:
        php -r "echo password_hash('TU-CONTRASEÑA-AQUI', PASSWORD_DEFAULT), PHP_EOL;"
      y pega el resultado abajo.

   NUNCA escribas la contraseña en claro en este archivo, solo el hash.
   Este archivo está en .gitignore: no se sube al repositorio.
   ========================================================================== */

return [
    // Resultado de password_hash(). Empieza por $2y$ o $argon2.
    'password_hash' => '',
];
