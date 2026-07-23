<?php
/**
 * Endpoint público (token) — guardar solicitud de empleo.
 * Siempre responde JSON válido para evitar "Error de comunicación" en el cliente.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../bd/Conexion.php';
    require_once __DIR__ . '/rrhh_employee_form_lib.php';
    require_once __DIR__ . '/rrhh_employee_form_fields_lib.php';

    $token = trim((string) ($_POST['token'] ?? ''));
    if ($token === '') {
        echo json_encode(['success' => false, 'message' => 'Enlace no válido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fields = medidata_rrhh_employee_form_collect($_POST);
    if (empty($fields['disclaimer_agreed']) || !in_array(strtoupper((string) $fields['disclaimer_agreed']), ['SI', 'SÍ', '1', 'YES'], true)) {
        // Checkbox HTML envía "SI"; si no viene, el navegador no debió dejar enviar, pero reforzamos.
        if (!isset($_POST['disclaimer_agreed'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Debe aceptar la renuncia de responsabilidad para enviar el formulario.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $fields['disclaimer_agreed'] = trim((string) $_POST['disclaimer_agreed']);
    }

    echo json_encode(
        medidata_rrhh_employee_form_save_public($token, $fields),
        JSON_UNESCAPED_UNICODE
    );
} catch (PDOException $e) {
    error_log('rrhh_formulario_empleado_guardar PDO: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo guardar en este momento (base de datos). Intente de nuevo en unos segundos.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('rrhh_formulario_empleado_guardar: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno al guardar el formulario. Contacte a Soporte TI si persiste.',
    ], JSON_UNESCAPED_UNICODE);
}
