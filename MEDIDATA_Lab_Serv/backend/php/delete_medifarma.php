<?php
include_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/staff_colaborador_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
medidata_staff_ensure_tables($connect);

$id = (int) ($_POST['idmf'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Identificador no válido.']);
    exit;
}

try {
    $stmt = $connect->prepare('DELETE FROM staff_medifarma WHERE idmf = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Colaborador medifarma eliminado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el registro.']);
    }
} catch (Throwable $e) {
    error_log('delete_medifarma: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el colaborador.']);
}
