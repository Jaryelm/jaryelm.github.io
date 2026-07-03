<?php
/**
 * DataTables server-side: Historial de Cierres de Caja.
 * scope=all → admin / contabilidad / auxcontable (todos los cierres).
 * Sin scope → solo cierres del usuario logueado (caja / facturación).
 */
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/reporte_datatable_helper.php';
include_once __DIR__ . '/session_check.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $draw = (int) ($_GET['draw'] ?? 1);
    $start = max(0, (int) ($_GET['start'] ?? 0));
    $lengthRaw = (int) ($_GET['length'] ?? 10);
    $length = ($lengthRaw <= 0) ? 10 : min($lengthRaw, 100);
    $searchValue = trim((string) ($_GET['search']['value'] ?? ''));

    $scope = (string) ($_GET['scope'] ?? '');
    if ($scope === 'all') {
        $whereBase = ' WHERE 1=1';
        $baseParams = [];
    } else {
        $usuario = (string) ($_SESSION['username'] ?? '');
        $whereBase = ' WHERE usuario_cierre = :usuario';
        $baseParams = [':usuario' => $usuario];
    }

    $stmtT = $connect->prepare('SELECT COUNT(*) FROM cierre_caja' . $whereBase);
    foreach ($baseParams as $key => $value) {
        $stmtT->bindValue($key, $value);
    }
    $stmtT->execute();
    $recordsTotal = (int) $stmtT->fetchColumn();

    $where = $whereBase;
    $params = $baseParams;
    if ($searchValue !== '') {
        $where .= ' AND (fecha_cierre LIKE :s0 OR nombre_completo LIKE :s1 OR usuario_cierre LIKE :s2)';
        medidata_reporte_dt_like_params($params, $searchValue, [':s0', ':s1', ':s2']);
    }

    $stmtF = $connect->prepare('SELECT COUNT(*) FROM cierre_caja' . $where);
    foreach ($params as $key => $value) {
        $stmtF->bindValue($key, $value);
    }
    $stmtF->execute();
    $recordsFiltered = (int) $stmtF->fetchColumn();

    $allowedOrderFields = [
        'fecha_cierre', 'total_ventas', 'total_facturas', 'facturas_cobradas',
        'facturas_pendientes', 'usuario_cierre', 'nombre_completo',
    ];
    $orderColIdx = (int) ($_GET['order'][0]['column'] ?? 0);
    $orderField = (string) ($_GET['columns'][$orderColIdx]['data'] ?? 'fecha_cierre');
    $orderBy = in_array($orderField, $allowedOrderFields, true) ? $orderField : 'fecha_cierre';
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

    $sql = 'SELECT fecha_cierre, total_ventas, total_facturas, facturas_cobradas, facturas_pendientes,
                   total_por_metodo, usuario_cierre, nombre_completo
            FROM cierre_caja' . $where
        . " ORDER BY {$orderBy} {$orderDir}, id DESC"
        . medidata_reporte_dt_limit_sql($start, $length);

    $stmt = $connect->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $row) {
        $data[] = [
            'fecha_cierre' => $row['fecha_cierre'],
            'total_ventas' => $row['total_ventas'],
            'total_facturas' => $row['total_facturas'],
            'facturas_cobradas' => $row['facturas_cobradas'],
            'facturas_pendientes' => $row['facturas_pendientes'],
            'total_por_metodo' => $row['total_por_metodo'],
            'usuario_cierre' => $row['usuario_cierre'],
            'nombre_completo' => $row['nombre_completo'],
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    medidata_reporte_dt_json_error((int) ($_GET['draw'] ?? 1), 'get_cierres_caja', $e);
}
