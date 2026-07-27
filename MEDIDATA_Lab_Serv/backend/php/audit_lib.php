<?php
/**
 * Bitácora de auditoría del módulo Vacaciones y Permisos.
 * Registra en hr_absence_audit_log (tabla centralizada en la BD principal
 * medic9ue_medi_data); usar siempre la conexión principal $connect.
 *
 * Nunca lanza: si el registro de auditoría falla, no debe abortar la operación de negocio
 * (se deja rastro en el error_log). La INMUTABILIDAD del log se garantiza a nivel de BD
 * mediante triggers (ver backend/scripts/vacaciones_permisos/20260724-02-audit_log_immutable_*.sql).
 */

if (!function_exists('medidata_audit_log')) {
    /**
     * @param PDO         $pdo             Conexión principal ($connect)
     * @param int|null    $action_user_id  Usuario que ejecuta la acción ($_SESSION['id'])
     * @param string      $action          Acción ejecutada (ej. 'UPDATE_POLICY')
     * @param string      $affected_table  Tabla afectada
     * @param int|string|null $record_id   ID del registro afectado
     * @param mixed       $old_value       Valor anterior (string o array/obj → JSON)
     * @param mixed       $new_value       Valor nuevo (string o array/obj → JSON)
     */
    function medidata_audit_log(PDO $pdo, ?int $action_user_id, string $action, string $affected_table, $record_id = null, $old_value = null, $new_value = null): void
    {
        try {
            $norm = function ($v) {
                if ($v === null) return null;
                if (is_string($v)) return $v;
                return json_encode($v, JSON_UNESCAPED_UNICODE);
            };
            $rid = ($record_id === null || $record_id === '') ? null : (int) $record_id;

            $stmt = $pdo->prepare("
                INSERT INTO hr_absence_audit_log
                    (action_user_id, action_executed, affected_table, record_id, old_value, new_value)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$action_user_id, $action, $affected_table, $rid, $norm($old_value), $norm($new_value)]);
        } catch (Throwable $e) {
            error_log('medidata_audit_log: ' . $e->getMessage());
        }
    }
}
