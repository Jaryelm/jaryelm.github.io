<?php
declare(strict_types=1);

/**
 * Un usuario no puede modificar su propio rol (evita auto-promoción a Administrador).
 * Otro administrador/IT con acceso al módulo sí puede corregir el rol de cualquier cuenta.
 */
function medidata_user_edita_propio_perfil(int $targetUserId): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $sessionId = (int) ($_SESSION['id'] ?? 0);

    return $sessionId > 0 && $targetUserId > 0 && $sessionId === $targetUserId;
}

function medidata_user_rol_bloqueado_autoedicion(int $targetUserId): bool
{
    return medidata_user_edita_propio_perfil($targetUserId);
}
