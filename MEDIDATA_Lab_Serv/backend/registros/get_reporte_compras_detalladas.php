<?php
/**
 * DataTables server-side: Compras Detalladas
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

    $fromJoin = " FROM compras c
        LEFT JOIN (
            SELECT id_compra,
                SUM(CASE WHEN COALESCE(exento,0) = 1 THEN COALESCE(subtotal,0) ELSE 0 END) AS sum_exenta,
                SUM(CASE WHEN COALESCE(gravado,0) = 1
                    OR (COALESCE(exento,0) = 0 AND COALESCE(gravado,0) = 0)
                    THEN COALESCE(subtotal,0) ELSE 0 END) AS sum_gravada
            FROM detalle_compras
            GROUP BY id_compra
        ) d ON d.id_compra = c.id_compra
        WHERE DATE(c.fecha_emision) BETWEEN :desde AND :hasta";

    $params = [':desde' => $desde, ':hasta' => $hasta];

    if ($searchValue !== '') {
        $fromJoin .= ' AND (CAST(c.id_compra AS CHAR) LIKE :searchId OR c.prov_datos LIKE :searchProv OR c.dato_fac LIKE :searchFac)';
        medidata_reporte_dt_like_params($params, $searchValue, [':searchId', ':searchProv', ':searchFac']);
    }

    $stmtCount = $connect->prepare('SELECT COUNT(c.id_compra)' . $fromJoin);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $totalRecords = (int) $stmtCount->fetchColumn();

    $orderColumn = (int) ($_GET['order'][0]['column'] ?? 0);
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $columns = [
        0 => 'c.fecha_emision',
        1 => 'c.prov_datos',
        2 => 'c.dato_fac',
        3 => 'c.isv_global',
        4 => 'c.fecha_registro',
        5 => 'COALESCE(d.sum_exenta, 0)',
        6 => 'COALESCE(d.sum_gravada, 0)',
        7 => 'c.sub_total',
        8 => 'c.total',
        9 => 'c.fecha_registro',
    ];
    $orderBy = $columns[$orderColumn] ?? 'c.fecha_registro';

    $query = "SELECT c.id_compra, c.fecha_emision, c.prov_datos, c.dato_fac, c.isv_global, c.sub_total, c.total,
        COALESCE(d.sum_exenta, 0) AS sum_exenta,
        COALESCE(d.sum_gravada, 0) AS sum_gravada"
        . $fromJoin
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
            'fecha' => $fechaRaw ? date('d-m-Y', strtotime($fechaRaw)) : '-',
            'fecha_iso' => $fechaRaw,
            'proveedor' => $row['prov_datos'] ?? '-',
            'factura' => $row['dato_fac'] ?? '-',
            'impuesto' => fmt_lempiras_dt($row['isv_global'] ?? 0),
            'retencion' => 'L. -',
            'exenta' => fmt_lempiras_dt($row['sum_exenta'] ?? 0),
            'gravada' => fmt_lempiras_dt($row['sum_gravada'] ?? 0),
            'subtotal' => fmt_lempiras_dt($row['sub_total'] ?? 0),
            'total' => fmt_lempiras_dt($row['total'] ?? 0),
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    medidata_reporte_dt_json_error((int) ($_GET['draw'] ?? 1), 'get_reporte_compras_detalladas', $e);
}
