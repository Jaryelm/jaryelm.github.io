<?php
/**
 * Asigna (o quita) el jefe de un departamento. Solo RRHH / Administrador.
 * Se audita en la bitácora por tratarse de configuración de aprobaciones.
 */
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/audit_lib.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    if (!isset($connect) || !$connect) throw new Exception('Sin conexión a BD.');

    $dep_id  = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $id_jefe = (isset($_POST['id_jefe']) && $_POST['id_jefe'] !== '') ? (int) $_POST['id_jefe'] : null;

    if ($dep_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Departamento no válido.']);
        exit;
    }

    // Valor anterior (para auditoría).
    $old = null;
    try {
        $oldStmt = $connect->prepare("SELECT id_jefe FROM medic9ue_medi_rrhh_interviews.departaments WHERE id = ?");
        $oldStmt->execute([$dep_id]);
        $old = $oldStmt->fetchColumn();
        $old = ($old !== false && $old !== null) ? (int) $old : null;
    } catch (Throwable $e) { /* columna nueva */ }

    $stmt = $connect->prepare("UPDATE medic9ue_medi_rrhh_interviews.departaments SET id_jefe = ? WHERE id = ?");
    $stmt->execute([$id_jefe, $dep_id]);

    medidata_audit_log($connect, (int) ($_SESSION['id'] ?? 0), 'ASSIGN_DEPARTMENT_HEAD', 'departaments', $dep_id, ['id_jefe' => $old], ['id_jefe' => $id_jefe]);

    echo json_encode(['success' => true, 'message' => 'Jefe de departamento actualizado.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
