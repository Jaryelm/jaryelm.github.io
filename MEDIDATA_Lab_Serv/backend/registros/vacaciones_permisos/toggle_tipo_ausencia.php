<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/audit_lib.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    global $connect; if (!$connect) throw new Exception("No db connection"); $pdo = $connect;

    $type_id = $_POST['type_id'] ?? '';
    $current_status = $_POST['current_status'] ?? 0;

    if (empty($type_id)) {
        echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
        exit;
    }

    $new_status = ($current_status == 1) ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE hr_absence_types SET status = ? WHERE type_id = ?");
    $stmt->execute([$new_status, $type_id]);

    medidata_audit_log($pdo, (int) ($_SESSION['id'] ?? 0), 'TOGGLE_ABSENCE_TYPE', 'hr_absence_types', $type_id, ['status' => (int) $current_status], ['status' => $new_status]);

    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
?>
