<?php
/**
 * Consulta de la bitácora de auditoría (hr_absence_audit_log).
 * Solo consultable por usuarios autorizados (Administrador / Recursos_Humanos).
 * El log es de solo lectura: no existe endpoint de edición/borrado y la BD lo bloquea con triggers.
 */
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['data' => [], 'error' => 'Acceso denegado']);
    exit;
}

try {
    if (!isset($connect) || !$connect) throw new Exception('Sin conexión a BD de ausencias.');

    // Mapa id_user => nombre (BD principal) para mostrar el autor de cada acción.
    $nombres = [];
    if (isset($connect) && $connect) {
        try {
            foreach ($connect->query("SELECT id, name, username FROM users")->fetchAll(PDO::FETCH_ASSOC) as $u) {
                $nom = trim((string) ($u['name'] ?? ''));
                if ($nom === '') $nom = trim((string) ($u['username'] ?? ''));
                $nombres[(int) $u['id']] = $nom;
            }
        } catch (Throwable $e) { /* si falla el mapa, se usa el id */ }
    }

    $rows = $connect->query("
        SELECT log_id, action_user_id, action_executed, affected_table, record_id, old_value, new_value, created_at
        FROM hr_absence_audit_log
        ORDER BY created_at DESC, log_id DESC
        LIMIT 5000
    ")->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $uid = (int) $r['action_user_id'];
        $data[] = [
            'log_id'      => (int) $r['log_id'],
            'usuario'     => $nombres[$uid] ?? ('Usuario ' . $uid),
            'fecha_hora'  => $r['created_at'],
            'accion'      => $r['action_executed'],
            'tabla'       => $r['affected_table'],
            'record_id'   => $r['record_id'],
            'old_value'   => $r['old_value'],
            'new_value'   => $r['new_value'],
        ];
    }

    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
