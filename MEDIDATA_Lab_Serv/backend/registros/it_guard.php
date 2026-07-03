<?php
/**
 * Guard de rol IT.
 *
 * Debe incluirse DESPUÉS de session_check.php (que valida la sesión activa).
 * Restringe el acceso a las páginas del módulo IT únicamente al rol 'IT'.
 * Es el primer guard de rol "duro" del sistema: cualquier otro rol que intente
 * abrir una página de IT por URL es devuelto al login con un aviso.
 */

$medidataRolActual = $_SESSION['rol'] ?? '';

if ($medidataRolActual !== 'IT') {
    // session_check.php cierra la sesión (session_write_close); reabrir solo
    // para dejar el mensaje flash que verá la pantalla de login.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['errMsg'] = 'Acceso restringido. Esta sección es exclusiva del personal de IT.';
    if (function_exists('session_write_close')) {
        session_write_close();
    }
    header('Location: ../../frontend/login.php');
    exit();
}
