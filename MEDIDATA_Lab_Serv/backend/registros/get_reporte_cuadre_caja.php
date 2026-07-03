<?php
/**
 * DataTables server-side: Cuadre Caja (agrupado por fecha y cajero).
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

    $params = [];
    $baseWhere = medidata_reporte_cuadre_caja_base_where(
        $desde !== '' ? $desde : null,
        $hasta !== '' ? $hasta : null,
        $params
    );

    $groupedSelect = medidata_reporte_cuadre_caja_grouped_sql();
    $innerSql = $groupedSelect . $baseWhere . ' GROUP BY DATE(o.placed_on), o.processed_by';
    $fromSql = ' FROM (' . $innerSql . ') AS cuadre WHERE 1=1';

    $stmtTotal = $connect->prepare('SELECT COUNT(*)' . $fromSql);
    foreach ($params as $key => $value) {
        $stmtTotal->bindValue($key, $value);
    }
    $stmtTotal->execute();
    $recordsTotal = (int) $stmtTotal->fetchColumn();

    $searchWhere = medidata_reporte_cuadre_caja_search_where($searchValue, $params);
    $fromSqlFiltered = $fromSql . $searchWhere;

    $stmtFiltered = $connect->prepare('SELECT COUNT(*)' . $fromSqlFiltered);
    foreach ($params as $key => $value) {
        $stmtFiltered->bindValue($key, $value);
    }
    $stmtFiltered->execute();
    $recordsFiltered = (int) $stmtFiltered->fetchColumn();

    $orderColumn = (int) ($_GET['order'][0]['column'] ?? 0);
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $columns = [
        0 => 'cuadre.fecha',
        1 => 'cuadre.nombre',
        2 => 'cuadre.efectivo',
        3 => 'cuadre.tarjeta',
        4 => 'cuadre.otros',
    ];
    $orderBy = $columns[$orderColumn] ?? 'cuadre.fecha';

    $query = 'SELECT cuadre.*' . $fromSqlFiltered
        . " ORDER BY {$orderBy} {$orderDir}, cuadre.nombre ASC"
        . medidata_reporte_dt_limit_sql($start, $length);

    $stmt = $connect->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fechaRaw = (string) ($row['fecha'] ?? '');
        $data[] = [
            'fecha' => $fechaRaw ? date('d-m-Y', strtotime($fechaRaw)) : '-',
            'fecha_iso' => (string) ($row['fecha_orden'] ?? $fechaRaw),
            'usuario' => (string) ($row['nombre'] ?? '-') ?: '-',
            'efectivo' => medidata_reporte_fmt_lempiras($row['efectivo'] ?? 0),
            'tarjeta' => medidata_reporte_fmt_lempiras($row['tarjeta'] ?? 0),
            'otros' => medidata_reporte_fmt_lempiras($row['otros'] ?? 0),
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    medidata_reporte_dt_json_error((int) ($_GET['draw'] ?? 1), 'get_reporte_cuadre_caja', $e);
}
