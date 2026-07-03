<?php
/**
 * Enlace de estado entre fichas de personal y cuentas de login (users).
 * Sistema: MEDIDATA - RRHH
 *
 * Regla de negocio: cuando una persona tiene a la vez ficha de personal
 * (doctor/staff_administrative/nurse/staff_general_services) y cuenta de
 * usuario (login), al DESACTIVAR cualquiera de las dos se debe desactivar
 * tambien la otra, para que aparezca una sola vez en Excolaboradores y se le
 * retire el acceso al sistema.
 *
 * La REACTIVACION no se propaga: reactivar una parte NO reactiva la otra
 * (el acceso se concede de forma explicita y controlada).
 */

if (!function_exists('medidata_staff_tables_idcols')) {
    /** Tablas de personal y su columna de id. Todas tienen columna id_user y state. */
    function medidata_staff_tables_idcols(): array
    {
        return [
            'doctor' => 'idodc',
            'staff_administrative' => 'idadm',
            'nurse' => 'idnur',
            'staff_general_services' => 'idsg',
        ];
    }
}

if (!function_exists('medidata_link_user_state_from_staff')) {
    /**
     * Al cambiar el estado de una ficha de personal, si esta enlazada a un
     * usuario (id_user) y se esta DESACTIVANDO, desactiva tambien el login.
     */
    function medidata_link_user_state_from_staff(PDO $connect, string $staffTable, string $idCol, int $staffId, int $newState): void
    {
        if ($newState !== 0) {
            return; // solo se propaga la desactivacion
        }
        $tablas = medidata_staff_tables_idcols();
        if (!isset($tablas[$staffTable]) || $tablas[$staffTable] !== $idCol || $staffId <= 0) {
            return;
        }
        try {
            $sel = $connect->prepare("SELECT id_user FROM {$staffTable} WHERE {$idCol} = :id LIMIT 1");
            $sel->execute([':id' => $staffId]);
            $idUser = $sel->fetchColumn();
            if ($idUser !== false && $idUser !== null && (int) $idUser > 0) {
                $upd = $connect->prepare('UPDATE users SET state = :state WHERE id = :id LIMIT 1');
                $upd->execute([':state' => '0', ':id' => (int) $idUser]);
            }
        } catch (Throwable $e) {
            error_log('medidata_link_user_state_from_staff: ' . $e->getMessage());
        }
    }
}

if (!function_exists('medidata_link_staff_state_from_user')) {
    /**
     * Al cambiar el estado de un usuario (login), si esta enlazado a una o mas
     * fichas de personal (id_user) y se esta DESACTIVANDO, desactiva tambien
     * esas fichas para que la persona pase a Excolaboradores una sola vez.
     */
    function medidata_link_staff_state_from_user(PDO $connect, int $userId, int $newState): void
    {
        if ($newState !== 0 || $userId <= 0) {
            return; // solo se propaga la desactivacion
        }
        foreach (medidata_staff_tables_idcols() as $tabla => $idCol) {
            try {
                $upd = $connect->prepare("UPDATE {$tabla} SET state = :state WHERE id_user = :id");
                $upd->execute([':state' => '0', ':id' => $userId]);
            } catch (Throwable $e) {
                error_log("medidata_link_staff_state_from_user ({$tabla}): " . $e->getMessage());
            }
        }
    }
}
