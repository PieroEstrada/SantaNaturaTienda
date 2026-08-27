<?php
/* ============================================================================
   Santa Natura — Acceso al panel
   ----------------------------------------------------------------------------
   El panel puede cambiar los precios de una web con tráfico pagado, así que
   NO basta con esconder la URL: cualquiera que la descubra (una barra de
   navegador, un historial, un enlace pegado por error) entraría.

   Por eso van juntas las dos cosas:
     · URL discreta   — no se enlaza desde ninguna página y está fuera de
                        robots.txt y del sitemap, para que no la indexe Google.
     · Contraseña     — guardada como hash (password_hash), nunca en claro,
                        en inc/admin-config.php, que está fuera del control de
                        versiones.

   La sesión dura mientras el navegador esté abierto y caduca a las 8 horas.
   ========================================================================== */

declare(strict_types=1);

const SN_CONFIG_ADMIN   = __DIR__ . '/admin-config.php';
const SN_SESION_MAX     = 8 * 3600;      // 8 horas
const SN_INTENTOS_MAX   = 5;             // por ventana
const SN_VENTANA        = 900;           // 15 minutos

function sn_config_admin(): array
{
    if (!is_file(SN_CONFIG_ADMIN)) {
        return [];
    }
    $cfg = require SN_CONFIG_ADMIN;
    return is_array($cfg) ? $cfg : [];
}

function sn_hay_password(): bool
{
    return (sn_config_admin()['password_hash'] ?? '') !== '';
}

function sn_iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        // En producción (https) la cookie no debe viajar por http.
        'secure'   => (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_name('sn_admin');
    session_start();
}

function sn_autenticado(): bool
{
    sn_iniciar_sesion();
    if (empty($_SESSION['admin'])) {
        return false;
    }
    if (time() - (int) ($_SESSION['desde'] ?? 0) > SN_SESION_MAX) {
        sn_cerrar_sesion();
        return false;
    }
    return true;
}

/** ¿Se agotaron los intentos desde esta sesión? */
function sn_bloqueado(): bool
{
    sn_iniciar_sesion();
    $intentos = $_SESSION['intentos'] ?? [];
    $intentos = array_filter($intentos, static fn($t) => $t > time() - SN_VENTANA);
    $_SESSION['intentos'] = array_values($intentos);
    return count($intentos) >= SN_INTENTOS_MAX;
}

function sn_login(string $password): bool
{
    sn_iniciar_sesion();

    if (sn_bloqueado()) {
        return false;
    }

    $hash = sn_config_admin()['password_hash'] ?? '';
    if ($hash === '' || !password_verify($password, $hash)) {
        $_SESSION['intentos'][] = time();
        return false;
    }

    // Sesión nueva al entrar: evita fijación de sesión.
    session_regenerate_id(true);
    $_SESSION['admin']    = true;
    $_SESSION['desde']    = time();
    $_SESSION['intentos'] = [];
    $_SESSION['csrf']     = bin2hex(random_bytes(32));
    return true;
}

function sn_cerrar_sesion(): void
{
    sn_iniciar_sesion();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $c = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
    }
    session_destroy();
}

/* --------------------------------------------------------------------------
   CSRF: sin esto, otra web abierta en el mismo navegador podría hacer que tu
   sesión guardara cambios sin que te enteres.
   -------------------------------------------------------------------------- */

function sn_csrf(): string
{
    sn_iniciar_sesion();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function sn_csrf_valido(?string $enviado): bool
{
    sn_iniciar_sesion();
    return is_string($enviado)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $enviado);
}

/** Corta la ejecución si no hay sesión válida. */
function sn_exigir_sesion(): void
{
    if (!sn_autenticado()) {
        header('Location: index.php');
        exit;
    }
}
