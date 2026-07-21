<?php
declare(strict_types=1);

include_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/../php/gestion_compras_helper.php';
require_once __DIR__ . '/../php/proveedor_comercial_lib.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    medidata_gestion_json(['success' => false, 'message' => 'Método no permitido.'], 405);
}

if (!isset($connect) || !($connect instanceof PDO)) {
    medidata_gestion_json(['success' => false, 'message' => 'No hay conexión a la base de datos.'], 500);
}

$accion = trim((string) ($_POST['accion'] ?? ''));
$idCompra = (int) ($_POST['id_compra'] ?? 0);
$motivo = trim((string) ($_POST['motivo'] ?? ''));
$usuario = isset($_SESSION['name']) ? (string) $_SESSION['name'] : 'Sistema';

if ($accion === 'proveedores') {
    try {
        medidata_gestion_json([
            'success' => true,
            'proveedores' => medidata_proveedor_listar($connect),
        ]);
    } catch (Throwable $e) {
        medidata_gestion_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($idCompra <= 0) {
    medidata_gestion_json(['success' => false, 'message' => 'Identificador de compra inválido.'], 400);
}

try {
    if ($accion === 'obtener') {
        $compra = medidata_compra_obtener($connect, $idCompra);
        if (!$compra) {
            medidata_gestion_json(['success' => false, 'message' => 'Compra no encontrada.'], 404);
        }
        $compra['fecha_emision'] = $compra['fecha_emision']
            ? date('Y-m-d', strtotime((string) $compra['fecha_emision']))
            : '';
        $compra['tiene_pagos'] = medidata_compra_tiene_pagos($connect, $idCompra);
        medidata_gestion_json(['success' => true, 'compra' => $compra]);
    }

    if ($motivo === '') {
        medidata_gestion_json(['success' => false, 'message' => 'Debe indicar el motivo de la corrección.'], 400);
    }

    if ($accion === 'actualizar') {
        $resultado = medidata_compra_actualizar($connect, $idCompra, [
            'fecha_emision' => trim((string) ($_POST['fecha_emision'] ?? '')),
            'prov_datos' => trim((string) ($_POST['prov_datos'] ?? '')),
            'dato_fac' => trim((string) ($_POST['dato_fac'] ?? '')),
            'sub_total' => $_POST['sub_total'] ?? 0,
            'isv_global' => $_POST['isv_global'] ?? 0,
            'total' => $_POST['total'] ?? 0,
        ], $motivo, $usuario);

        medidata_gestion_json([
            'success' => true,
            'message' => 'Compra actualizada correctamente.',
            'data' => $resultado,
        ]);
    }

    if ($accion === 'eliminar') {
        medidata_compra_eliminar($connect, $idCompra, $motivo, $usuario);
        medidata_gestion_json([
            'success' => true,
            'message' => 'Compra eliminada correctamente. Puede registrarla de nuevo con los datos correctos.',
        ]);
    }

    medidata_gestion_json(['success' => false, 'message' => 'Acción no válida.'], 400);
} catch (Throwable $e) {
    error_log('gestion_compra.php: ' . $e->getMessage());
    medidata_gestion_json(['success' => false, 'message' => $e->getMessage()], 500);
}
