<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/colaborador_extra_lib.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($connect) || !($connect instanceof PDO)) {
        throw new RuntimeException('Conexión a base de datos no disponible.');
    }

    $tipo = trim((string) ($_POST['tipo'] ?? ''));
    $refId = (int) ($_POST['ref_id'] ?? 0);
    $field = trim((string) ($_POST['field'] ?? ''));
    $value = isset($_POST['value']) ? trim((string) $_POST['value']) : '';

    if (!in_array($field, medidata_colab_extra_text_fields(), true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Campo no permitido.']);
        exit;
    }
    if (!in_array($tipo, medidata_colab_extra_tipos(), true) || $refId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Colaborador no válido.']);
        exit;
    }

    // Validación de fechas
    if (($field === 'fecha_ingreso' || $field === 'fecha_nacimiento') && $value !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Fecha no válida (use AAAA-MM-DD).']);
            exit;
        }
    }

    $ok = medidata_colab_extra_upsert($connect, $tipo, $refId, $field, $value);
    echo json_encode(['success' => (bool) $ok, 'value' => $value]);
} catch (Throwable $e) {
    error_log('save_colaborador_extra: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar.']);
}
