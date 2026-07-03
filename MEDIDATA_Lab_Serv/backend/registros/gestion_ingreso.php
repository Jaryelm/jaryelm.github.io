<?php
declare(strict_types=1);

include_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/../php/gestion_ingresos_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    medidata_ingreso_json(['success' => false, 'message' => 'Método no permitido.'], 405);
}

if (!isset($connect) || !($connect instanceof PDO)) {
    medidata_ingreso_json(['success' => false, 'message' => 'No hay conexión a la base de datos.'], 500);
}

$accion = trim((string) ($_POST['accion'] ?? ''));
$idord = (int) ($_POST['idord'] ?? 0);
$motivo = trim((string) ($_POST['motivo'] ?? ''));
$usuario = isset($_SESSION['name']) ? (string) $_SESSION['name'] : 'Sistema';

if ($idord <= 0) {
    medidata_ingreso_json(['success' => false, 'message' => 'Identificador de ingreso inválido.'], 400);
}

try {
    if ($accion === 'obtener') {
        $ingreso = medidata_ingreso_obtener($connect, $idord);
        if (!$ingreso) {
            medidata_ingreso_json(['success' => false, 'message' => 'Ingreso no encontrado.'], 404);
        }
        $ingreso['placed_on'] = $ingreso['placed_on']
            ? date('Y-m-d', strtotime((string) $ingreso['placed_on']))
            : '';
        medidata_ingreso_json(['success' => true, 'ingreso' => $ingreso]);
    }

    if ($motivo === '') {
        medidata_ingreso_json(['success' => false, 'message' => 'Debe indicar el motivo de la corrección.'], 400);
    }

    if ($accion === 'actualizar') {
        $resultado = medidata_ingreso_actualizar($connect, $idord, [
            'placed_on' => trim((string) ($_POST['placed_on'] ?? '')),
            'invoice_number' => trim((string) ($_POST['invoice_number'] ?? '')),
            'method' => trim((string) ($_POST['method'] ?? '')),
            'total_price' => $_POST['total_price'] ?? 0,
        ], $motivo, $usuario);

        medidata_ingreso_json([
            'success' => true,
            'message' => 'Ingreso actualizado correctamente.',
            'data' => $resultado,
        ]);
    }

    if ($accion === 'eliminar') {
        medidata_ingreso_eliminar($connect, $idord, $motivo);
        medidata_ingreso_json([
            'success' => true,
            'message' => 'Ingreso revertido a Pendiente. Puede cobrarlo de nuevo con los datos correctos.',
        ]);
    }

    medidata_ingreso_json(['success' => false, 'message' => 'Acción no válida.'], 400);
} catch (Throwable $e) {
    error_log('gestion_ingreso.php: ' . $e->getMessage());
    medidata_ingreso_json(['success' => false, 'message' => $e->getMessage()], 500);
}
