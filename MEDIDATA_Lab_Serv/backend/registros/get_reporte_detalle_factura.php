<?php
/**
 * DataTables server-side: Detalle Factura (ingresos cobrados)
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

    $fromWhere = " FROM orders o
        WHERE o.invoice_status = 'Cobrada'
          AND o.invoice_number IS NOT NULL
          AND o.invoice_number <> ''
          AND DATE(o.placed_on) BETWEEN :desde AND :hasta";

    $params = [':desde' => $desde, ':hasta' => $hasta];

    if ($searchValue !== '') {
        $fromWhere .= ' AND (CAST(o.idord AS CHAR) LIKE :searchId OR o.invoice_number LIKE :searchFac OR o.method LIKE :searchMet OR o.processed_by LIKE :searchUsr)';
        medidata_reporte_dt_like_params($params, $searchValue, [':searchId', ':searchFac', ':searchMet', ':searchUsr']);
    }

    $stmtCount = $connect->prepare('SELECT COUNT(o.idord)' . $fromWhere);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $totalRecords = (int) $stmtCount->fetchColumn();

    $orderColumn = (int) ($_GET['order'][0]['column'] ?? 0);
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $columns = [
        0 => 'o.placed_on',
        1 => 'o.idord',
        2 => 'o.invoice_number',
        3 => 'o.method',
        4 => 'o.processed_by',
        5 => 'o.total_price',
        6 => 'o.placed_on',
    ];
    $orderBy = $columns[$orderColumn] ?? 'o.placed_on';

    $query = 'SELECT o.idord, o.placed_on, o.invoice_number, o.method, o.processed_by, o.total_price'
        . $fromWhere
        . " ORDER BY {$orderBy} {$orderDir}, o.idord DESC LIMIT :start, :length";

    $stmt = $connect->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fechaRaw = $row['placed_on'] ?? '';
        $data[] = [
            'idord' => (int) $row['idord'],
            'fecha' => $fechaRaw ? date('d-m-Y', strtotime($fechaRaw)) : '-',
            'fecha_iso' => $fechaRaw,
            'num_orden' => (string) $row['idord'],
            'factura' => $row['invoice_number'] ?? '-',
            'metodo' => $row['method'] ?? '-',
            'usuario' => $row['processed_by'] ?? '-',
            'total' => fmt_lempiras_dt($row['total_price'] ?? 0),
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    medidata_reporte_dt_json_error((int) ($_GET['draw'] ?? 1), 'get_reporte_detalle_factura', $e);
}
