<?php
/**
 * DataTables server-side: Compras Ingresadas
 */
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/reporte_datatable_helper.php';
include_once __DIR__ . '/session_check.php';

header('Content-Type: application/json; charset=utf-8');

function fmt_lempiras_dt($valor): string
{
    return 'L. ' . number_format((float) ($valor ?? 0), 2, '.', ',');
}

try {
    $draw = (int) ($_GET['draw'] ?? 1);
    $start = max(0, (int) ($_GET['start'] ?? 0));
    $lengthRaw = (int) ($_GET['length'] ?? 10);
    $length = ($lengthRaw <= 0) ? 10 : min($lengthRaw, 100);
    $searchValue = trim((string) ($_GET['search']['value'] ?? ''));

    $desde = $_GET['fechaDesde'] ?? $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['fechaHasta'] ?? $_GET['hasta'] ?? date('Y-m-t');

    $tieneNpc = false;
    try {
        $chkNpc = $connect->query("SHOW COLUMNS FROM compras LIKE 'numero_partida_contable'");
        $tieneNpc = (bool) ($chkNpc && $chkNpc->fetch(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        $tieneNpc = false;
    }

    $fromWhere = ' FROM compras c WHERE DATE(c.fecha_emision) BETWEEN :desde AND :hasta';
    $params = [':desde' => $desde, ':hasta' => $hasta];

    if ($searchValue !== '') {
        $searchFields = [':searchId', ':searchProv', ':searchFac'];
        $fromWhere .= ' AND (CAST(c.id_compra AS CHAR) LIKE :searchId OR c.prov_datos LIKE :searchProv OR c.dato_fac LIKE :searchFac';
        if ($tieneNpc) {
            $fromWhere .= ' OR c.numero_partida_contable LIKE :searchNpc';
            $searchFields[] = ':searchNpc';
        }
        $fromWhere .= ')';
        medidata_reporte_dt_like_params($params, $searchValue, $searchFields);
    }

    $stmtCount = $connect->prepare('SELECT COUNT(c.id_compra)' . $fromWhere);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $totalRecords = (int) $stmtCount->fetchColumn();

    $orderColumn = (int) ($_GET['order'][0]['column'] ?? 1);
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $columns = [
        0 => 'c.id_compra',
        1 => 'c.fecha_emision',
        2 => 'c.prov_datos',
        3 => 'c.dato_fac',
        4 => 'c.isv_global',
        5 => 'c.sub_total',
        6 => 'c.total',
        7 => $tieneNpc ? 'c.numero_partida_contable' : 'c.fecha_registro',
        8 => 'c.fecha_registro',
    ];
    $orderBy = $columns[$orderColumn] ?? 'c.fecha_emision';

    $sqlNpc = $tieneNpc ? 'c.numero_partida_contable' : 'NULL AS numero_partida_contable';
    $query = "SELECT c.id_compra, c.fecha_emision, c.prov_datos, c.dato_fac, c.isv_global, c.sub_total, c.total, {$sqlNpc}"
        . $fromWhere
        . " ORDER BY {$orderBy} {$orderDir}, c.id_compra DESC LIMIT :start, :length";

    $stmt = $connect->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fechaRaw = $row['fecha_emision'] ?? '';
        $data[] = [
            'id_compra' => (int) $row['id_compra'],
            'numero_orden' => (string) $row['id_compra'],
            'fecha' => $fechaRaw ? date('d-m-Y', strtotime($fechaRaw)) : '-',
            'fecha_iso' => $fechaRaw,
            'proveedor' => $row['prov_datos'] ?? '-',
            'factura' => $row['dato_fac'] ?? '-',
            'impuesto' => fmt_lempiras_dt($row['isv_global'] ?? 0),
            'subtotal' => fmt_lempiras_dt($row['sub_total'] ?? 0),
            'total' => fmt_lempiras_dt($row['total'] ?? 0),
            'partida' => (string) ($row['numero_partida_contable'] ?? ''),
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    medidata_reporte_dt_json_error((int) ($_GET['draw'] ?? 1), 'get_reporte_compras_ingresadas', $e);
}
