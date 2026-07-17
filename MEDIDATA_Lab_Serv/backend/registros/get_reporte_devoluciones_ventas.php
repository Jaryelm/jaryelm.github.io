<?php
/**
 * DataTables server-side: Devoluciones de Ventas (returns + facturas anuladas).
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

    $desde = trim((string) ($_GET['fechaDesde'] ?? $_GET['desde'] ?? ''));
    $hasta = trim((string) ($_GET['fechaHasta'] ?? $_GET['hasta'] ?? ''));

    $unionSql = medidata_reporte_devoluciones_union_sql($connect);
    $fromWhere = ' FROM (' . $unionSql . ') AS dev WHERE 1=1';
    $params = [];

    if ($desde !== '' && $hasta !== '') {
        $fromWhere .= ' AND DATE(dev.fecha) BETWEEN :desde AND :hasta';
        $params[':desde'] = $desde;
        $params[':hasta'] = $hasta;
    } elseif ($desde !== '') {
        $fromWhere .= ' AND DATE(dev.fecha) >= :desde';
        $params[':desde'] = $desde;
    } elseif ($hasta !== '') {
        $fromWhere .= ' AND DATE(dev.fecha) <= :hasta';
        $params[':hasta'] = $hasta;
    }

    $searchWhere = '';
    if ($searchValue !== '') {
        $searchWhere = ' AND (
            CAST(dev.idord AS CHAR) LIKE :s0
            OR dev.invoice_number LIKE :s1
            OR dev.nomcl LIKE :s2
            OR dev.tipo_evento LIKE :s3
            OR dev.detalle LIKE :s4
            OR dev.item_type LIKE :s5
            OR dev.motivo LIKE :s6
            OR dev.procesado_por LIKE :s7
            OR CAST(dev.product_id AS CHAR) LIKE :s8
        )';
        medidata_reporte_dt_like_params($params, $searchValue, [
            ':s0', ':s1', ':s2', ':s3', ':s4', ':s5', ':s6', ':s7', ':s8',
        ]);
    }

    $stmtTotal = $connect->prepare('SELECT COUNT(*) ' . $fromWhere);
    foreach ($params as $key => $value) {
        $stmtTotal->bindValue($key, $value);
    }
    $stmtTotal->execute();
    $recordsTotal = (int) $stmtTotal->fetchColumn();

    $fromWhereFiltered = $fromWhere . $searchWhere;
    $stmtFiltered = $connect->prepare('SELECT COUNT(*) ' . $fromWhereFiltered);
    foreach ($params as $key => $value) {
        $stmtFiltered->bindValue($key, $value);
    }
    $stmtFiltered->execute();
    $recordsFiltered = (int) $stmtFiltered->fetchColumn();

    $orderColumn = (int) ($_GET['order'][0]['column'] ?? 0);
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $columns = [
        0 => 'dev.fecha',
        1 => 'dev.tipo_evento',
        2 => 'dev.idord',
        3 => 'dev.invoice_number',
        4 => 'dev.nomcl',
        5 => 'dev.item_type',
        6 => 'dev.detalle',
        7 => 'dev.cantidad',
        8 => 'dev.motivo',
        9 => 'dev.procesado_por',
        10 => 'dev.valor_item',
    ];
    $orderBy = $columns[$orderColumn] ?? 'dev.fecha';

    $query = 'SELECT dev.* ' . $fromWhereFiltered
        . " ORDER BY {$orderBy} {$orderDir}, dev.idord DESC"
        . medidata_reporte_dt_limit_sql($start, $length);

    $stmt = $connect->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = medidata_reporte_devoluciones_format_row($row);
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    medidata_reporte_dt_json_error((int) ($_GET['draw'] ?? 1), 'get_reporte_devoluciones_ventas', $e);
}
