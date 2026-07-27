<?php
/**
 * Resolución del flujo de aprobación de Vacaciones y Permisos.
 *
 * El flujo se determina por el DEPARTAMENTO del solicitante (hr_workflow_departments,
 * ver migración 20260726-02): cada departamento tiene a lo sumo UN flujo asignado y el
 * paso "Jefe de Departamento" se resuelve con el jefe configurado en
 * medic9ue_medi_rrhh_interviews.departaments.id_jefe (vista Jefes de Departamento).
 *
 * Orden de resolución:
 *   1) Flujo asignado al departamento del solicitante.
 *   2) Flujo configurado en el tipo de ausencia (hr_absence_types.workflow_id).
 *   3) Flujo por defecto (workflow_id = 1).
 */

require_once __DIR__ . '/jefe_lib.php';

if (!function_exists('medidata_workflow_por_departamento')) {
    /** Flujo asignado a un departamento (hr_workflow_departments), o null. */
    function medidata_workflow_por_departamento(PDO $connect, ?int $departamento_id): ?int
    {
        if (!$departamento_id) return null;
        try {
            $stmt = $connect->prepare("SELECT workflow_id FROM hr_workflow_departments WHERE department_id = ? LIMIT 1");
            $stmt->execute([$departamento_id]);
            $wf = $stmt->fetchColumn();
            return $wf ? (int) $wf : null;
        } catch (Throwable $e) {
            return null; // tabla aún no migrada: se usa el fallback por tipo/default
        }
    }
}

if (!function_exists('medidata_workflow_para_usuario')) {
    /**
     * Flujo que procesará una solicitud del usuario (departamento -> tipo -> default 1).
     * Mismo criterio en submit_absence_request.php y fetch_request_flow.php.
     */
    function medidata_workflow_para_usuario(PDO $connect, int $user_id, ?int $type_id = null): int
    {
        $departamento_id = medidata_user_departamento($connect, $user_id);
        $workflow_id = medidata_workflow_por_departamento($connect, $departamento_id);
        if ($workflow_id !== null) {
            return $workflow_id;
        }

        if ($type_id) {
            try {
                $stmt = $connect->prepare("SELECT workflow_id FROM hr_absence_types WHERE type_id = ?");
                $stmt->execute([$type_id]);
                $t = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($t && !empty($t['workflow_id'])) {
                    return (int) $t['workflow_id'];
                }
            } catch (Throwable $e) { /* cae al default */ }
        }

        return 1;
    }
}
